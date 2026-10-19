import 'dart:convert';
import 'dart:io';
import 'package:flutter/foundation.dart';
import 'package:http/http.dart' as http;
import '../models/booking.dart';
import '../models/course_condition.dart';
import '../models/member.dart';
import '../models/tee_slot.dart';
import '../models/waitlist_entry.dart';

class ApiService {
  int currentMemberId = 1;
  String currentMemberName = 'James Carter';

  static String get baseUrl {
    if (kIsWeb) {
      return 'http://localhost/golf-tee/admin-php/api/mobile.php';
    }
    try {
      if (Platform.isAndroid) {
        return 'http://10.0.2.2/golf-tee/admin-php/api/mobile.php';
      }
    } catch (_) {}
    return 'http://localhost/golf-tee/admin-php/api/mobile.php';
  }

  static const Duration _timeout = Duration(seconds: 4);

  // ── 1. Fetch Home Screen Data ─────────────────────────────
  Future<Map<String, dynamic>> fetchHomeData() async {
    try {
      final uri = Uri.parse('$baseUrl?action=home&member_id=$currentMemberId');
      final res = await http.get(uri).timeout(_timeout);
      if (res.statusCode == 200) {
        final data = jsonDecode(res.body);
        if (data['status'] == 'ok') {
          return data;
        }
      }
    } catch (e) {
      debugPrint('ApiService.fetchHomeData error (using fallback): $e');
    }

    // Offline fallback dynamic to current member
    final membersList = [
      Member(id: 1, name: 'James Carter', email: 'james.carter@mail.com', phone: '(555) 301-2201', tier: 'Gold', hcp: 8.4, rounds: 42, spend: 4820.0),
      Member(id: 2, name: 'Mina Lee', email: 'mina.lee@mail.com', phone: '(555) 301-4410', tier: 'Gold', hcp: 5.1, rounds: 67, spend: 7310.0),
      Member(id: 3, name: 'Raj Patel', email: 'r.patel@mail.com', phone: '(555) 301-8812', tier: 'Silver', hcp: 14.2, rounds: 23, spend: 2140.0),
      Member(id: 4, name: 'Sofia Kim', email: 'sofia.k@mail.com', phone: '(555) 301-0937', tier: 'Standard', hcp: 21.0, rounds: 6, spend: 410.0),
      Member(id: 5, name: 'Alejandro Gomez', email: 'agomez@mail.com', phone: '(555) 301-5521', tier: 'Silver', hcp: 11.7, rounds: 31, spend: 2980.0),
      Member(id: 7, name: 'Daniel Brown', email: 'd.brown@mail.com', phone: '(555) 301-3390', tier: 'Gold', hcp: 3.9, rounds: 88, spend: 11250.0),
      Member(id: 8, name: 'Lucia Rossi', email: 'lucia.rossi@mail.com', phone: '(555) 301-6604', tier: 'Silver', hcp: 16.5, rounds: 19, spend: 1720.0),
      Member(id: 10, name: 'Priya Shah', email: 'priya.s@mail.com', phone: '(555) 301-9183', tier: 'Gold', hcp: 9.6, rounds: 54, spend: 6040.0),
    ];
    final activeMember = membersList.firstWhere(
      (m) => m.id == currentMemberId,
      orElse: () => Member(
        id: currentMemberId,
        name: currentMemberName,
        email: '${currentMemberName.toLowerCase().replaceAll(' ', '.')}@mail.com',
        phone: '(555) 301-2201',
        tier: 'Gold',
        hcp: 8.4,
        rounds: 42,
        spend: 4820.0,
      ),
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
  }) async {
    try {
      String query = '$baseUrl?action=weather&refresh=1';
      if (lat != null && lng != null) {
        query += '&lat=$lat&lng=$lng';
      } else if (location != null && location.isNotEmpty) {
        query += '&location=${Uri.encodeComponent(location)}';
      }
      final uri = Uri.parse(query);
      final res = await http.get(uri).timeout(_timeout);
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
      final uri = Uri.parse('$baseUrl?action=slots&date=$date&holes=$holes');
      final res = await http.get(uri).timeout(_timeout);
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
      final uri = Uri.parse(baseUrl);
      final res = await http.post(
        uri,
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode(payload),
      ).timeout(_timeout);

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
      final uri = Uri.parse('$baseUrl?action=bookings&member_id=$currentMemberId');
      final res = await http.get(uri).timeout(_timeout);
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
      debugPrint('ApiService.fetchBookings error (using seeds): $e');
    }

    return _seedBookings();
  }

  // ── 5. Cancel Booking ──────────────────────────────────────
  Future<bool> cancelBooking(int id) async {
    try {
      final uri = Uri.parse('$baseUrl?action=cancel_booking&id=$id&member_id=$currentMemberId');
      final res = await http.post(uri).timeout(_timeout);
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
      final uri = Uri.parse(baseUrl);
      final res = await http.post(
        uri,
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode(payload),
      ).timeout(_timeout);

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
      final uri = Uri.parse('$baseUrl?action=leave_waitlist&id=$id&member_id=$currentMemberId');
      final res = await http.post(uri).timeout(_timeout);
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
      final uri = Uri.parse(baseUrl);
      final res = await http.post(
        uri,
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({
          'action': 'save_score',
          'strokes': strokes,
          'diff': diff,
          'member_id': currentMemberId,
        }),
      ).timeout(_timeout);

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

  // ── 9. Member Sign In ──────────────────────────────────────
  Future<Member?> login({String? email, int? memberId, String? password}) async {
    try {
      final uri = Uri.parse(baseUrl);
      final payload = {
        'action': 'login',
        if (email != null && email.isNotEmpty) 'email': email,
        if (memberId != null) 'member_id': memberId,
        if (password != null && password.isNotEmpty) 'password': password,
      };
      final res = await http.post(
        uri,
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode(payload),
      ).timeout(_timeout);

      if (res.statusCode == 200) {
        final data = jsonDecode(res.body);
        if (data['status'] == 'ok' && data['member'] != null) {
          final m = Member.fromJson(data['member'] as Map<String, dynamic>);
          currentMemberId = m.id;
          currentMemberName = m.name;
          return m;
        }
      }
    } catch (e) {
      debugPrint('ApiService.login error: $e');
    }
    return null;
  }

  // ── 10. Fetch Club Members (for quick login/switcher) ─────
  Future<List<Member>> fetchMembers() async {
    try {
      final uri = Uri.parse('$baseUrl?action=members');
      final res = await http.get(uri).timeout(_timeout);
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

    return [
      Member(id: 1, name: 'James Carter', email: 'james.carter@mail.com', phone: '(555) 301-2201', tier: 'Gold', hcp: 8.4, rounds: 42, spend: 4820),
      Member(id: 2, name: 'Mina Lee', email: 'mina.lee@mail.com', phone: '(555) 301-4410', tier: 'Gold', hcp: 5.1, rounds: 67, spend: 7310),
      Member(id: 3, name: 'Raj Patel', email: 'r.patel@mail.com', phone: '(555) 301-8812', tier: 'Silver', hcp: 14.2, rounds: 23, spend: 2140),
      Member(id: 4, name: 'Sofia Kim', email: 'sofia.k@mail.com', phone: '(555) 301-0937', tier: 'Standard', hcp: 21.0, rounds: 6, spend: 410),
      Member(id: 5, name: 'Alejandro Gomez', email: 'agomez@mail.com', phone: '(555) 301-5521', tier: 'Silver', hcp: 11.7, rounds: 31, spend: 2980),
      Member(id: 7, name: 'Daniel Brown', email: 'd.brown@mail.com', phone: '(555) 301-3390', tier: 'Gold', hcp: 3.9, rounds: 88, spend: 11250),
      Member(id: 8, name: 'Lucia Rossi', email: 'lucia.rossi@mail.com', phone: '(555) 301-6604', tier: 'Silver', hcp: 16.5, rounds: 19, spend: 1720),
      Member(id: 10, name: 'Priya Shah', email: 'priya.s@mail.com', phone: '(555) 301-9183', tier: 'Gold', hcp: 9.6, rounds: 54, spend: 6040),
    ];
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

    for (int m = 420; m <= 1070; m += 10) {
      final price = (m >= 900)
          ? (holes == 18 ? 55.0 : 35.0)
          : isWeekend
              ? (holes == 18 ? 110.0 : 62.0)
              : (holes == 18 ? 85.0 : 48.0);

      SlotState state = SlotState.available;
      final hash = (m * 7 + (holes == 9 ? 3 : 0)) % 17;
      if (hash < 4) {
        state = SlotState.booked;
      } else if (hash < 6) {
        state = SlotState.waitlist;
      } else if (hash == 6) {
        state = SlotState.locked;
      }

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

  static Map<String, dynamic> _seedBookings() {
    final now = DateTime.now();
    final d2 = now.add(const Duration(days: 2)).toIso8601String().split('T')[0];
    final d6 = now.add(const Duration(days: 6)).toIso8601String().split('T')[0];
    final dPast = now.subtract(const Duration(days: 10)).toIso8601String().split('T')[0];

    return {
      'upcoming': [
        Booking(
          id: 1,
          date: d2,
          min: 450,
          time: '7:30 AM',
          holes: 18,
          players: 4,
          partners: ['Mina Lee', 'Daniel Brown'],
          addons: ['cart'],
          total: 386.0,
          card: 'v',
          tee: 'Hole 1',
        ),
        Booking(
          id: 2,
          date: d6,
          min: 540,
          time: '9:00 AM',
          holes: 9,
          players: 2,
          partners: ['Priya Shah'],
          addons: [],
          total: 86.0,
          card: 'v',
          tee: 'Hole 10',
        ),
      ],
      'waitlist': [
        WaitlistEntry(
          id: 1,
          date: now.add(const Duration(days: 1)).toIso8601String().split('T')[0],
          min: 480,
          time: '8:00 AM',
          holes: 18,
          players: 2,
          position: 1,
        ),
        WaitlistEntry(
          id: 2,
          date: now.add(const Duration(days: 4)).toIso8601String().split('T')[0],
          min: 430,
          time: '7:10 AM',
          holes: 18,
          players: 4,
          position: 3,
        ),
      ],
      'past': [
        Booking(
          id: 3,
          date: dPast,
          min: 500,
          time: '8:20 AM',
          holes: 18,
          players: 4,
          addons: ['cart'],
          total: 386.0,
          status: 'past',
          score: 82,
        ),
      ]
    };
  }
}
