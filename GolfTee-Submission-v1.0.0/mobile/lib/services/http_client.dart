import 'dart:convert';
import 'package:flutter/foundation.dart';
import 'package:http/http.dart' as http;
import '../config/api_config.dart';

/// Reusable HTTP client service for the mobile application.
class AppHttpClient {
  static String? _resolvedBaseUrl;

  static List<String> get candidateUrls {
    assert(const String.fromEnvironment('API_URL').isNotEmpty || kDebugMode, 'API_URL required for release');
    return ApiConfig.fallbackUrls;
  }

  static String get baseUrl {
    if (_resolvedBaseUrl != null) return _resolvedBaseUrl!;
    return candidateUrls.first;
  }

  static const Duration timeout = Duration(milliseconds: 3000);

  static Map<String, String> getHeaders({int currentMemberId = 0, String? authToken}) => {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    'Authorization': 'Bearer ${authToken ?? (currentMemberId > 0 ? "member_token_${currentMemberId}_session" : "")}',
    if (currentMemberId > 0) 'X-Member-Id': currentMemberId.toString(),
  };

  static Future<http.Response> get(String queryString, {int currentMemberId = 0, String? authToken}) async {
    final candidates = _resolvedBaseUrl != null
        ? [_resolvedBaseUrl!, ...candidateUrls.where((u) => u != _resolvedBaseUrl)]
        : candidateUrls;

    dynamic lastError;
    for (final base in candidates) {
      try {
        final uri = Uri.parse('$base$queryString');
        final response = await http
            .get(uri, headers: getHeaders(currentMemberId: currentMemberId, authToken: authToken))
            .timeout(timeout);
        if (response.statusCode >= 200 && response.statusCode < 500) {
          _resolvedBaseUrl = base;
          return response;
        }
      } catch (e) {
        lastError = e;
      }
    }
    throw Exception('Failed to connect to backend: $lastError');
  }

  static Future<http.Response> post(String queryString, Map<String, dynamic> body, {int currentMemberId = 0, String? authToken}) async {
    final candidates = _resolvedBaseUrl != null
        ? [_resolvedBaseUrl!, ...candidateUrls.where((u) => u != _resolvedBaseUrl)]
        : candidateUrls;

    dynamic lastError;
    for (final base in candidates) {
      try {
        final uri = Uri.parse('$base$queryString');
        final response = await http
            .post(
              uri,
              headers: getHeaders(currentMemberId: currentMemberId, authToken: authToken),
              body: jsonEncode(body),
            )
            .timeout(timeout);
        if (response.statusCode >= 200 && response.statusCode < 500) {
          _resolvedBaseUrl = base;
          return response;
        }
      } catch (e) {
        lastError = e;
      }
    }
    throw Exception('Failed to connect to backend: $lastError');
  }
}
