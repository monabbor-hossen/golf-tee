import 'package:flutter_riverpod/flutter_riverpod.dart';

enum AppScreen {
  home,
  book,
  bookings,
  score,
  profile,
  checkout,
  success,
  detail,
  login,
}

class AppState {
  final AppScreen currentScreen;
  final List<AppScreen> history;
  final int? detailBookingId;
  final String toastMessage;

  const AppState({
    this.currentScreen = AppScreen.home,
    this.history = const [],
    this.detailBookingId,
    this.toastMessage = '',
  });

  AppState copyWith({
    AppScreen? currentScreen,
    List<AppScreen>? history,
    int? detailBookingId,
    String? toastMessage,
  }) {
    return AppState(
      currentScreen: currentScreen ?? this.currentScreen,
      history: history ?? this.history,
      detailBookingId: detailBookingId ?? this.detailBookingId,
      toastMessage: toastMessage ?? this.toastMessage,
    );
  }
}

class AppNotifier extends Notifier<AppState> {
  @override
  AppState build() => const AppState();

  void navigateTo(AppScreen screen, [int? bookingId]) {
    final isTab = [
      AppScreen.home,
      AppScreen.book,
      AppScreen.bookings,
      AppScreen.score,
      AppScreen.profile,
    ].contains(screen);

    final newHistory = isTab
        ? <AppScreen>[]
        : [...state.history, state.currentScreen];

    state = state.copyWith(
      currentScreen: screen,
      history: newHistory,
      detailBookingId: bookingId ?? state.detailBookingId,
    );
  }

  void goBack() {
    if (state.history.isEmpty) {
      state = state.copyWith(currentScreen: AppScreen.home);
      return;
    }
    final previous = state.history.last;
    final updatedHistory = List<AppScreen>.from(state.history)..removeLast();
    state = state.copyWith(
      currentScreen: previous,
      history: updatedHistory,
    );
  }

  void showToast(String message) {
    state = state.copyWith(toastMessage: message);
  }

  void clearToast() {
    state = state.copyWith(toastMessage: '');
  }
}

final appProvider = NotifierProvider<AppNotifier, AppState>(AppNotifier.new);
