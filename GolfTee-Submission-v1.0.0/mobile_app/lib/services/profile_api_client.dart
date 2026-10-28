import 'dart:convert';
import 'package:flutter/foundation.dart';
import 'http_client.dart';
import '../models/member.dart';

/// Focused API client for member profile retrieval and modifications.
class ProfileApiClient {
  final int memberId;
  final String? authToken;

  const ProfileApiClient({this.memberId = 0, this.authToken});

  Future<Member?> getProfile() async {
    try {
      final response = await AppHttpClient.get(
        '?action=profile&member_id=$memberId',
        currentMemberId: memberId,
        authToken: authToken,
      );
      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data['member'] != null) {
          return Member.fromJson(data['member'] as Map<String, dynamic>);
        }
      }
    } catch (e) {
      debugPrint('ProfileApiClient.getProfile error: $e');
    }
    return null;
  }

  Future<bool> updateProfile(Map<String, dynamic> profileData) async {
    try {
      final response = await AppHttpClient.post(
        '?action=update_profile&member_id=$memberId',
        profileData,
        currentMemberId: memberId,
        authToken: authToken,
      );
      return response.statusCode == 200;
    } catch (e) {
      debugPrint('ProfileApiClient.updateProfile error: $e');
      return false;
    }
  }

  Future<bool> changePassword(String currentPassword, String newPassword) async {
    try {
      final response = await AppHttpClient.post(
        '?action=change_password&member_id=$memberId',
        {'current_password': currentPassword, 'password': newPassword},
        currentMemberId: memberId,
        authToken: authToken,
      );
      return response.statusCode == 200;
    } catch (e) {
      debugPrint('ProfileApiClient.changePassword error: $e');
      return false;
    }
  }
}
