import 'dart:async';
import 'dart:io';
import 'dart:typed_data';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:golf_tee_mobile/main.dart';
import 'package:golf_tee_mobile/models/course_condition.dart';
import 'package:golf_tee_mobile/providers/member_provider.dart';
import 'package:golf_tee_mobile/screens/login_screen.dart';

void main() {
  setUpAll(() {
    HttpOverrides.global = _FakeHttpOverrides();
  });

  testWidgets('GolfTeeMobileApp builds cleanly', (WidgetTester tester) async {
    await tester.pumpWidget(
      const ProviderScope(
        child: GolfTeeMobileApp(),
      ),
    );
    expect(find.byType(GolfTeeMobileApp), findsOneWidget);
  });

  testWidgets('LoginScreen renders and displays member access options',
      (WidgetTester tester) async {
    await tester.pumpWidget(
      const ProviderScope(
        child: MaterialApp(
          home: LoginScreen(),
        ),
      ),
    );
    await tester.pump();

    expect(find.text('PINE VALLEY GOLF CLUB'), findsOneWidget);
    expect(find.text('Member Portal'), findsOneWidget);
    expect(find.text('Sign In to Portal'), findsOneWidget);
    expect(find.text('Authorized Member Access'), findsOneWidget);
  });

  testWidgets('Sign out flow transitions to LoginScreen',
      (WidgetTester tester) async {
    final container = ProviderContainer();
    addTearDown(container.dispose);

    // Initial state is logged in
    expect(container.read(memberProvider).isLoggedIn, isTrue);

    // Trigger logout
    container.read(memberProvider.notifier).logout();
    expect(container.read(memberProvider).isLoggedIn, isFalse);

    // Trigger login as Mina Lee
    final members = await container.read(memberProvider.notifier).getClubMembers();
    final mina = members.firstWhere((m) => m.id == 2);
    await container.read(memberProvider.notifier).loginAsMember(mina);

    expect(container.read(memberProvider).isLoggedIn, isTrue);
    expect(container.read(memberProvider).member.name, 'Mina Lee');
    expect(container.read(memberProvider).member.id, 2);
  });

  test('CourseCondition parses live weather and conditions correctly', () {
    final json = {
      'weather': '72°',
      'weather_desc': 'Sunny',
      'wind': '8 mph',
      'wind_desc': 'NW wind',
      'green_speed': '10.5',
      'speed_desc': 'Green speed',
      'carts': 'Open',
      'carts_desc': 'Carts on fairways',
      'updated_at': 'Updated 2:45 PM',
      'location': 'Pine Valley, NJ',
      'humidity': '42%',
      'is_live': true,
      'lat': 23.8103,
      'lng': 90.4125,
      'gps_formatted': '23.8103° N, 90.4125° E',
      'is_gps': true,
    };

    final condition = CourseCondition.fromJson(json);
    expect(condition.weather, '72°');
    expect(condition.weatherDesc, 'Sunny');
    expect(condition.wind, '8 mph');
    expect(condition.windDesc, 'NW wind');
    expect(condition.greenSpeed, '10.5');
    expect(condition.location, 'Pine Valley, NJ');
    expect(condition.isLive, isTrue);
    expect(condition.lat, 23.8103);
    expect(condition.lng, 90.4125);
    expect(condition.gpsFormatted, '23.8103° N, 90.4125° E');
    expect(condition.isGps, isTrue);
  });
}

class _FakeHttpOverrides extends HttpOverrides {
  @override
  HttpClient createHttpClient(SecurityContext? context) => _FakeHttpClient();
}

class _FakeHttpClient implements HttpClient {
  @override
  dynamic noSuchMethod(Invocation invocation) {
    if (invocation.memberName == #getUrl || invocation.memberName == #openUrl) {
      return Future.value(_FakeHttpClientRequest());
    }
    return null;
  }
}

class _FakeHttpClientRequest implements HttpClientRequest {
  @override
  dynamic noSuchMethod(Invocation invocation) {
    if (invocation.memberName == #close) {
      return Future.value(_FakeHttpClientResponse());
    }
    return null;
  }
}

class _FakeHttpClientResponse implements HttpClientResponse {
  static final _kTransparentImage = Uint8List.fromList([
    0x89, 0x50, 0x4E, 0x47, 0x0D, 0x0A, 0x1A, 0x0A, 0x00, 0x00, 0x00, 0x0D, 0x49,
    0x48, 0x44, 0x52, 0x00, 0x00, 0x00, 0x01, 0x00, 0x00, 0x00, 0x01, 0x08, 0x06,
    0x00, 0x00, 0x00, 0x1F, 0x15, 0xC4, 0x89, 0x00, 0x00, 0x00, 0x0A, 0x49, 0x44,
    0x41, 0x54, 0x78, 0x9C, 0x63, 0x00, 0x01, 0x00, 0x00, 0x05, 0x00, 0x01, 0x0D,
    0x0A, 0x2D, 0xB4, 0x00, 0x00, 0x00, 0x00, 0x49, 0x45, 0x4E, 0x44, 0xAE, 0x42,
    0x60, 0x82
  ]);

  @override
  int get statusCode => 200;

  @override
  int get contentLength => _kTransparentImage.length;

  @override
  HttpClientResponseCompressionState get compressionState =>
      HttpClientResponseCompressionState.notCompressed;

  @override
  StreamSubscription<List<int>> listen(void Function(List<int> event)? onData,
      {Function? onError, void Function()? onDone, bool? cancelOnError}) {
    return Stream<List<int>>.value(_kTransparentImage).listen(
      onData,
      onError: onError,
      onDone: onDone,
      cancelOnError: cancelOnError,
    );
  }

  @override
  dynamic noSuchMethod(Invocation invocation) => null;
}
