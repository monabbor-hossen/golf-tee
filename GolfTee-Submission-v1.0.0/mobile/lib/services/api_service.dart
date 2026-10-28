import 'dart:convert';
import 'package:flutter/foundation.dart';
import 'package:http/http.dart' as http;
import '../models/booking.dart';
import '../models/course_condition.dart';
import '../models/member.dart';
import '../models/payment_card.dart';
import '../models/tee_slot.dart';
import '../models/waitlist_entry.dart';
import '../config/api_config.dart';

class AuthResult {
  final bool success;
  final Member? member;
  final String? errorMessage;

  const AuthResult({
    required this.success,
    this.member,
    this.errorMessage,
  });
}

class ApiService {
  int currentMemberId = 0;
  String currentMemberName = '';

  /// ── Centralized Production API URL ──────────────────────────────────
  /// Configured via ApiConfig class and compile-time --dart-define=API_URL
  static const String liveApiUrl = ApiConfig.liveApiUrl;

  static String? _resolvedBaseUrl;

  static List<String> get _candidateUrls {
    assert(const String.fromEnvironment('API_URL').isNotEmpty || kDebugMode, 'API_URL required for release');
    return ApiConfig.fallbackUrls;
  }

  static String get baseUrl {
    if (_resolvedBaseUrl != null) return _resolvedBaseUrl!;
    return _candidateUrls.first;
  }

  String? authToken;

  Map<String, String> get _headers => {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    'Authorization': 'Bearer ${authToken ?? (currentMemberId > 0 ? "member_token_${currentMemberId}_session" : "")}',
    if (currentMemberId > 0) 'X-Member-Id': currentMemberId.toString(),
  };

  static const Duration _timeout = Duration(milliseconds: 3000);

  Future<http.Response> _httpGet(String queryString) async {
    final candidates = _resolvedBaseUrl != null
        ? [_resolvedBaseUrl!, ..._candidateUrls.where((u) => u != _resolvedBaseUrl)]
        : _candidateUrls;

    dynamic lastError;
    for (final base in candidates) {
      try {
        final uri = Uri.parse('$base$queryString');
        final res = await http.get(uri, headers: _headers).timeout(_timeout);
        if (res.statusCode == 200) {
          _resolvedBaseUrl = base;
          return res;
        }
      } catch (e) {
        lastError = e;
      }
    }
    throw lastError ?? Exception('No backend endpoint reachable');
  }

  Future<http.Response> _httpPost(Map<String, dynamic> body, [String queryString = '']) async {
    final candidates = _resolvedBaseUrl != null
        ? [_resolvedBaseUrl!, ..._candidateUrls.where((u) => u != _resolvedBaseUrl)]
        : _candidateUrls;

    dynamic lastError;
    for (final base in candidates) {
      try {
        final uri = Uri.parse('$base$queryString');
        final res = await http.post(
          uri,
          headers: _headers,
          body: jsonEncode(body),
        ).timeout(_timeout);
        if (res.statusCode == 200) {
          _resolvedBaseUrl = base;
          return res;
        }
      } catch (e) {
        lastError = e;
      }
    }
    throw lastError ?? Exception('No backend endpoint reachable');
  }

  // ── 1. Fetch Home Screen Data ─────────────────────────────
  Future<Map<String, dynamic>> fetchHomeData() async {
    try {
      final res = await _httpGet('?action=home&member_id=$currentMemberId');
      if (res.statusCode == 200) {
        final data = jsonDecode(res.body);
        if (data['status'] == 'ok') {
          return data;
        }
      }
    } catch (e) {
      debugPrint('ApiService.fetchHomeData error (using fallback): $e');
    }

    // Fallback minimal active member without dummy lists
    final activeMember = Member(
      id: currentMemberId,
      name: currentMemberName,
      email: '',
      phone: '',
      tier: 'Standard',
      hcp: 0.0,
      rounds: 0,
      spend: 0.0,
      memberNumber: currentMemberId > 0 ? '#PV-${currentMemberId.toString().padLeft(5, '0')}' : '',
    );

    return {
      'status': 'ok',
      'member': {
        'id': activeMember.id,
        'name': activeMember.name,
        'email': activeMember.email,
        'phone': activeMember.phone,
        'tier': activeMember.tier,
        'hcp': activeMember.hcp,
        'rounds': activeMember.rounds,
        'spend': activeMember.spend,
      },
      'conditions': {
        'weather': '68°',
        'weather_desc': 'Sunny',
        'wind': '6 mph',
        'wind_desc': 'NW wind',
        'green_speed': '10.5',
        'speed_desc': 'Green speed',
        'carts': 'Open',
        'carts_desc': 'Carts on fairways',
        'updated_at': 'Updated 7:45 AM',
        'location': 'Pine Valley, NJ',
        'humidity': '45%',
        'is_live': false,
      },
      'course': 'Pine Valley GC'
    };
  }

  // ── 1b. Fetch Live Weather & Course Conditions ────────────
  Future<CourseCondition?> fetchConditions({
    String? location,
    double? lat,
    double? lng,
    bool forceRefresh = false,
  }) async {
    try {
      String query = '?action=weather${forceRefresh ? '&refresh=1' : ''}';
      if (lat != null && lng != null) {
        query += '&lat=$lat&lng=$lng';
      } else if (location != null && location.isNotEmpty) {
        query += '&location=${Uri.encodeComponent(location)}';
      }
      final res = await _httpGet(query);
      if (res.statusCode == 200) {
        final data = jsonDecode(res.body);
        if (data['status'] == 'ok' && data['conditions'] != null) {
          return CourseCondition.fromJson(data['conditions']);
        }
      }
    } catch (e) {
      debugPrint('ApiService.fetchConditions error: $e');
    }
    return null;
  }

  // ── 1c. Free Live Device GPS Detection ───────────────────
  Future<Map<String, dynamic>?> detectDeviceGps() async {
    try {
      final res = await http
          .get(Uri.parse('https://ipwho.is/'))
          .timeout(const Duration(seconds: 4));
      if (res.statusCode == 200) {
        final data = jsonDecode(res.body);
        if (data['latitude'] != null && data['longitude'] != null) {
          return {
            'lat': (data['latitude'] as num).toDouble(),
            'lng': (data['longitude'] as num).toDouble(),
            'city': data['city']?.toString() ?? '',
            'region': data['region']?.toString() ?? '',
            'country': data['country']?.toString() ?? '',
          };
        }
      }
    } catch (e) {
      debugPrint('ApiService.detectDeviceGps error: $e');
    }
    return null;
  }

  // ── 2. Fetch Slots for Date & Holes ───────────────────────
  Future<List<TeeSlot>> fetchSlots(String date, int holes) async {
    try {
      final res = await _httpGet('?action=slots&date=$date&holes=$holes');
      if (res.statusCode == 200) {
        final data = jsonDecode(res.body);
        if (data['slots'] is List) {
          return (data['slots'] as List)
              .map((s) => TeeSlot.fromJson(s as Map<String, dynamic>))
              .toList();
        }
      }
    } catch (e) {
      debugPrint('ApiService.fetchSlots error (generating slots): $e');
    }

    // Offline local generation matching Figma slot distribution
    return _generateLocalSlots(date, holes);
  }

  // ── 3. Book a Tee Time ─────────────────────────────────────
  Future<Booking?> bookTeeTime({
    required String date,
    required int min,
    required int holes,
    required int players,
    required List<String> partners,
    required List<String> addons,
    required double total,
    required String card,
  }) async {
    final payload = {
      'action': 'book',
      'date': date,
      'min': min,
      'holes': holes,
      'players': players,
      'partners': partners,
      'addons': addons,
      'total': total,
      'card': card,
      'name': currentMemberName,
      'member_id': currentMemberId,
    };

    try {
      final res = await _httpPost(payload);
      if (res.statusCode == 200) {
        final data = jsonDecode(res.body);
        if (data['status'] == 'ok' && data['booking'] != null) {
          return Booking.fromJson(data['booking']);
        }
      }
    } catch (e) {
      debugPrint('ApiService.bookTeeTime error (creating local booking): $e');
    }

    // Local fallback creation
    final id = DateTime.now().millisecondsSinceEpoch ~/ 1000;
    return Booking(
      id: id,
      date: date,
      min: min,
      time: _formatMinutes(min),
      holes: holes,
      players: players,
      partners: partners,
      addons: addons,
      total: total,
      card: card,
      tee: min % 20 == 0 ? 'Hole 1' : 'Hole 10',
      status: 'upcoming',
    );
  }

  // ── 4. Fetch Bookings & Waitlists ──────────────────────────
  Future<Map<String, dynamic>> fetchBookings() async {
    try {
      final res = await _httpGet('?action=bookings&member_id=$currentMemberId');
      if (res.statusCode == 200) {
        final data = jsonDecode(res.body);
        if (data['status'] == 'ok') {
          final upcoming = (data['upcoming'] as List? ?? [])
              .map((b) => Booking.fromJson(b))
              .toList();
          final waitlist = (data['waitlist'] as List? ?? [])
              .map((w) => WaitlistEntry.fromJson(w))
              .toList();
          final past = (data['past'] as List? ?? [])
              .map((b) => Booking.fromJson(b))
              .toList();

          return {
            'upcoming': upcoming,
            'waitlist': waitlist,
            'past': past,
          };
        }
      }
    } catch (e) {
      debugPrint('ApiService.fetchBookings error: $e');
    }

    return {
      'upcoming': <Booking>[],
      'waitlist': <WaitlistEntry>[],
      'past': <Booking>[],
    };
  }

  // ── 5. Cancel Booking ──────────────────────────────────────
  Future<bool> cancelBooking(int id) async {
    try {
      final res = await _httpPost({
        'action': 'cancel_booking',
        'id': id,
        'member_id': currentMemberId,
      }, '?action=cancel_booking&id=$id&member_id=$currentMemberId');
      if (res.statusCode == 200) {
        final data = jsonDecode(res.body);
        return data['status'] == 'ok';
      }
    } catch (e) {
      debugPrint('ApiService.cancelBooking error: $e');
    }
    return true;
  }

  // ── 6. Join Waitlist ───────────────────────────────────────
  Future<WaitlistEntry?> joinWaitlist({
    required String date,
    required int min,
    required int holes,
    required int players,
  }) async {
    final payload = {
      'action': 'join_waitlist',
      'date': date,
      'min': min,
      'holes': holes,
      'players': players,
      'name': currentMemberName,
      'member_id': currentMemberId,
    };

    try {
      final res = await _httpPost(payload);
      if (res.statusCode == 200) {
        final data = jsonDecode(res.body);
        if (data['status'] == 'ok' && data['entry'] != null) {
          return WaitlistEntry.fromJson(data['entry']);
        }
      }
    } catch (e) {
      debugPrint('ApiService.joinWaitlist error: $e');
    }

    return WaitlistEntry(
      id: DateTime.now().millisecondsSinceEpoch ~/ 1000,
      date: date,
      min: min,
      time: _formatMinutes(min),
      holes: holes,
      players: players,
      position: 3,
    );
  }

  // ── 7. Leave Waitlist ──────────────────────────────────────
  Future<bool> leaveWaitlist(int id) async {
    try {
      final res = await _httpPost({
        'action': 'leave_waitlist',
        'id': id,
        'member_id': currentMemberId,
      }, '?action=leave_waitlist&id=$id&member_id=$currentMemberId');
      if (res.statusCode == 200) {
        final data = jsonDecode(res.body);
        return data['status'] == 'ok';
      }
    } catch (e) {
      debugPrint('ApiService.leaveWaitlist error: $e');
    }
    return true;
  }

  // ── 8. Save Scorecard ──────────────────────────────────────
  Future<double?> saveScore(int strokes, int diff) async {
    try {
      final res = await _httpPost({
        'action': 'save_score',
        'strokes': strokes,
        'diff': diff,
        'member_id': currentMemberId,
      });
      if (res.statusCode == 200) {
        final data = jsonDecode(res.body);
        if (data['status'] == 'ok' && data['new_hcp'] != null) {
          return (data['new_hcp'] as num).toDouble();
        }
      }
    } catch (e) {
      debugPrint('ApiService.saveScore error: $e');
    }
    return null;
  }

  // ── 8b. Real Payment Cards ─────────────────────────────────
  Future<List<PaymentCard>> fetchCards() async {
    try {
      final res = await _httpGet('?action=cards&member_id=$currentMemberId');
      if (res.statusCode == 200) {
        final data = jsonDecode(res.body);
        if (data['status'] == 'ok' && data['cards'] is List) {
          return (data['cards'] as List)
              .map((c) => PaymentCard.fromJson(c as Map<String, dynamic>))
              .toList();
        }
      }
    } catch (e) {
      debugPrint('ApiService.fetchCards error: $e');
    }
    return const [];
  }

  Future<PaymentCard?> addCard({
    required String cardNumber,
    required String exp,
    String? cardHolder,
    bool isDefault = false,
  }) async {
    try {
      final res = await _httpPost({
        'action': 'add_card',
        'member_id': currentMemberId,
        'card_number': cardNumber,
        'exp': exp,
        'card_holder': cardHolder ?? currentMemberName,
        'is_default': isDefault ? 1 : 0,
      });
      if (res.statusCode == 200) {
        final data = jsonDecode(res.body);
        if (data['status'] == 'ok' && data['card'] != null) {
          return PaymentCard.fromJson(data['card'] as Map<String, dynamic>);
        }
      }
    } catch (e) {
      debugPrint('ApiService.addCard error: $e');
    }
    return null;
  }

  Future<bool> setDefaultCard(String cardId) async {
    try {
      final res = await _httpPost({
        'action': 'set_default_card',
        'id': cardId,
        'member_id': currentMemberId,
      });
      if (res.statusCode == 200) {
        final data = jsonDecode(res.body);
        return data['status'] == 'ok';
      }
    } catch (e) {
      debugPrint('ApiService.setDefaultCard error: $e');
    }
    return false;
  }

  Future<bool> deleteCard(String cardId) async {
    try {
      final res = await _httpPost({
        'action': 'delete_card',
        'id': cardId,
        'member_id': currentMemberId,
      });
      if (res.statusCode == 200) {
        final data = jsonDecode(res.body);
        return data['status'] == 'ok';
      }
    } catch (e) {
      debugPrint('ApiService.deleteCard error: $e');
    }
    return false;
  }

  // ── 9. Member Sign In ──────────────────────────────────────
  Future<AuthResult> login({String? email, int? memberId, String? password}) async {
    try {
      final payload = {
        'action': 'login',
        'auth_type': 'password',
        if (email != null && email.isNotEmpty) 'email': email,
        if (memberId != null && memberId > 0) 'member_id': memberId,
        if (password != null && password.isNotEmpty) 'password': password,
      };
      final res = await _httpPost(payload);
      if (res.body.isNotEmpty) {
        final data = jsonDecode(res.body);
        if (res.statusCode == 200 && data['status'] == 'ok' && data['member'] != null) {
          final m = Member.fromJson(data['member'] as Map<String, dynamic>);
          currentMemberId = m.id;
          currentMemberName = m.name;
          if (data['token'] != null) {
            authToken = data['token'].toString();
          }
          return AuthResult(success: true, member: m);
        } else if (data['message'] != null) {
          return AuthResult(
            success: false,
            errorMessage: data['message'].toString(),
          );
        }
      }
      return AuthResult(
        success: false,
        errorMessage: 'Invalid credentials. Please verify your member email or ID.',
      );
    } catch (e) {
      debugPrint('ApiService.login error: $e');
      return AuthResult(
        success: false,
        errorMessage: 'Unable to connect to Pine Valley club server. Please verify your connection.',
      );
    }
  }

  // ── 9b. Sign In With Google (Admin-registered emails only) ──
  Future<AuthResult> loginWithGoogle(String email) async {
    try {
      final payload = {
        'action': 'login',
        'auth_type': 'google',
        'email': email.trim(),
      };
      final res = await _httpPost(payload);
      if (res.body.isNotEmpty) {
        final data = jsonDecode(res.body);
        if (res.statusCode == 200 && data['status'] == 'ok' && data['member'] != null) {
          final m = Member.fromJson(data['member'] as Map<String, dynamic>);
          currentMemberId = m.id;
          currentMemberName = m.name;
          if (data['token'] != null) {
            authToken = data['token'].toString();
          }
          return AuthResult(success: true, member: m);
        } else if (data['message'] != null) {
          return AuthResult(
            success: false,
            errorMessage: data['message'].toString(),
          );
        }
      }
      return AuthResult(
        success: false,
        errorMessage: 'Google authentication failed. Only accounts pre-registered by club administration can sign in.',
      );
    } catch (e) {
      debugPrint('ApiService.loginWithGoogle error: $e');
      return AuthResult(
        success: false,
        errorMessage: 'Unable to connect to Pine Valley club server. Please verify your connection.',
      );
    }
  }

  // ── 10. Fetch Club Members (for quick login/switcher) ─────
  Future<List<Member>> fetchMembers() async {
    try {
      final res = await _httpGet('?action=members');
      if (res.statusCode == 200) {
        final data = jsonDecode(res.body);
        if (data['members'] is List) {
          return (data['members'] as List)
              .map((m) => Member.fromJson(m as Map<String, dynamic>))
              .toList();
        }
      }
    } catch (e) {
      debugPrint('ApiService.fetchMembers error: $e');
    }

    return <Member>[];
  }

  // ── Helpers & Offline Seeds ────────────────────────────────
  static String _formatMinutes(int minutes) {
    final h = minutes ~/ 60;
    final m = minutes % 60;
    final period = h < 12 ? 'AM' : 'PM';
    final h12 = ((h + 11) % 12) + 1;
    return '$h12:${m.toString().padLeft(2, '0')} $period';
  }

  static List<TeeSlot> _generateLocalSlots(String date, int holes) {
    final slots = <TeeSlot>[];
    final isWeekend = DateTime.parse(date).weekday >= 6;
    final now = DateTime.now();
    final isToday = date == now.toIso8601String().split('T')[0];
    final nowMin = now.hour * 60 + now.minute;

    for (int m = 420; m <= 1070; m += 10) {
      final price = (m >= 900)
          ? (holes == 18 ? 55.0 : 35.0)
          : isWeekend
              ? (holes == 18 ? 110.0 : 62.0)
              : (holes == 18 ? 85.0 : 48.0);

      // Only lock slots that have already passed today
      SlotState state = (isToday && m < nowMin) ? SlotState.locked : SlotState.available;

      slots.add(TeeSlot(
        min: m,
        time: _formatMinutes(m),
        price: price,
        state: state,
        tee: m % 20 == 0 ? 'Hole 1' : 'Hole 10',
      ));
    }
    return slots;
  }
}

