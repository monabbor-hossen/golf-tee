import 'dart:convert';
import 'dart:io';
import 'package:flutter/foundation.dart';
import 'package:http/http.dart' as http;
import '../models/booking.dart';
import '../models/tee_slot.dart';
import '../models/waitlist_entry.dart';

class ApiService {
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
      final uri = Uri.parse('$baseUrl?action=home');
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

    // Offline fallback matching Figma design
    return {
      'status': 'ok',
      'member': {
        'id': 1,
        'name': 'James Carter',
        'email': 'james.carter@mail.com',
        'phone': '(555) 301-2201',
        'tier': 'Gold',
        'hcp': 8.4,
        'rounds': 42,
        'spend': 4820.0,
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
        'updated_at': 'Updated 7:45 AM'
      },
      'course': 'Pine Valley GC'
    };
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
      'name': 'James Carter',
      'member_id': 1,
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
      final uri = Uri.parse('$baseUrl?action=bookings&member_id=1');
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
      final uri = Uri.parse('$baseUrl?action=cancel_booking&id=$id&member_id=1');
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
      'name': 'James Carter',
      'member_id': 1,
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
      final uri = Uri.parse('$baseUrl?action=leave_waitlist&id=$id&member_id=1');
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
          'member_id': 1,
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
