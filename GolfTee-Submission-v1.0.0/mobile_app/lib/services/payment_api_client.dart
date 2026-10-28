import 'dart:convert';
import 'package:flutter/foundation.dart';
import 'http_client.dart';
import '../models/payment_card.dart';

/// Focused API client for payment methods and cards.
class PaymentApiClient {
  final int memberId;
  final String? authToken;

  const PaymentApiClient({this.memberId = 0, this.authToken});

  Future<List<PaymentCard>> getCards() async {
    try {
      final response = await AppHttpClient.get(
        '?action=cards&member_id=$memberId',
        currentMemberId: memberId,
        authToken: authToken,
      );
      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data['cards'] is List) {
          return (data['cards'] as List).map((c) => PaymentCard.fromJson(c as Map<String, dynamic>)).toList();
        }
      }
    } catch (e) {
      debugPrint('PaymentApiClient.getCards error: $e');
    }
    return [];
  }

  Future<bool> addCard(Map<String, dynamic> cardData) async {
    try {
      final response = await AppHttpClient.post(
        '?action=add_card&member_id=$memberId',
        cardData,
        currentMemberId: memberId,
        authToken: authToken,
      );
      return response.statusCode == 200;
    } catch (e) {
      debugPrint('PaymentApiClient.addCard error: $e');
      return false;
    }
  }

  Future<bool> deleteCard(int cardId) async {
    try {
      final response = await AppHttpClient.post(
        '?action=delete_card&member_id=$memberId',
        {'card_id': cardId},
        currentMemberId: memberId,
        authToken: authToken,
      );
      return response.statusCode == 200;
    } catch (e) {
      debugPrint('PaymentApiClient.deleteCard error: $e');
      return false;
    }
  }
}
