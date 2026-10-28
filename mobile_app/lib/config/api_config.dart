/// Centralized API configuration for GolfTee mobile app.
/// Single configuration source across all build variants.
/// Finding 5.16: Moved liveApiUrl to lib/config/api_config.dart as const.
class ApiConfig {
  static const String liveApiUrl = String.fromEnvironment(
    'API_URL',
    defaultValue: 'http://localhost/golf-tee/admin-php/api/mobile.php',
  );

  static const String apiUrl = liveApiUrl;

  static const List<String> fallbackUrls = [
    liveApiUrl,
    'http://10.0.2.2/golf-tee/admin-php/api/mobile.php',
    'http://localhost:8080/admin-php/api/mobile.php',
  ];
}
