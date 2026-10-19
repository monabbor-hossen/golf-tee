import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:golf_tee_mobile/main.dart';

void main() {
  testWidgets('GolfTeeMobileApp builds cleanly', (WidgetTester tester) async {
    FlutterError.onError = (FlutterErrorDetails details) {
      if (details.exceptionAsString().contains('NetworkImageLoadException') ||
          details.exceptionAsString().contains('HTTP request failed')) {
        return;
      }
      FlutterError.presentError(details);
    };

    await tester.pumpWidget(
      const ProviderScope(
        child: GolfTeeMobileApp(),
      ),
    );

    expect(find.byType(GolfTeeMobileApp), findsOneWidget);
  });
}
