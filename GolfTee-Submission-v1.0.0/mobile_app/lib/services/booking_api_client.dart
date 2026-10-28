import 'dart:convert';
import 'package:flutter/foundation.dart';
import 'http_client.dart';
import '../models/tee_slot.dart';

/// Focused API client for tee-time bookings and waitlist management.
class BookingApiClient {
  final int memberId;
  final String? authToken;

  const BookingApiClient({this.memberId = 0, this.authToken});

  Future<List<TeeSlot>> getSlots(String date, {int holes = 18}) async {
    try {
      final response = await AppHttpClient.get(
        '?action=slots&date=$date&holes=$holes',
        currentMemberId: memberId,
        authToken: authToken,
      );
      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data['slots'] is List) {
          return (data['slots'] as List).map((s) => TeeSlot.fromJson(s as Map<String, dynamic>)).toList();
        }
      }
    } catch (e) {
      debugPrint('BookingApiClient.getSlots error: $e');
    }
    return [];
  }

  Future<bool> book(Map<String, dynamic> bookingData) async {
    try {
      final response = await AppHttpClient.post(
        '?action=book&member_id=$memberId',
        bookingData,
        currentMemberId: memberId,
        authToken: authToken,
      );
      return response.statusCode == 200;
    } catch (e) {
      debugPrint('BookingApiClient.book error: $e');
      return false;
    }
  }

  Future<bool> cancelBooking(int bookingId) async {
    try {
      final response = await AppHttpClient.post(
        '?action=cancel_booking&member_id=$memberId',
        {'booking_id': bookingId},
        currentMemberId: memberId,
        authToken: authToken,
      );
      return response.statusCode == 200;
    } catch (e) {
      debugPrint('BookingApiClient.cancelBooking error: $e');
      return false;
    }
  }
}
