/// Centralized API configuration for GolfTee mobile app.
/// Configures live and fallback backend endpoints.
class ApiConfig {
  // IMPORTANT: Must match APP_URL in admin-php/.env for production. Update before building release APK/IPA.
  // ignore: constant_identifier_names
  static const String API_BASE_URL = 'http://localhost/golf-tee/admin-php';

  static const String liveApiUrl = String.fromEnvironment(
    'API_URL',
    defaultValue: 'http://localhost/golf-tee/admin-php/api/mobile.php',
  );

  static const String apiUrl = liveApiUrl;
  static const String baseUrl = liveApiUrl;

  static const List<String> fallbackUrls = [
    liveApiUrl,
    'http://10.0.2.2/golf-tee/admin-php/api/mobile.php',
    'http://localhost:8080/admin-php/api/mobile.php',
  ];
}
