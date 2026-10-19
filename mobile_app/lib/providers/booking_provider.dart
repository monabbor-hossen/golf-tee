import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../models/booking.dart';
import '../services/api_service.dart';
import 'course_provider.dart';

class BookingState {
  final List<Booking> upcoming;
  final List<Booking> past;
  final List<String> selectedPartners;
  final List<String> selectedAddons;
  final String selectedCardId;
  final String promoCode;
  final bool promoApplied;
  final bool promoError;
  final bool isBookingLoading;

  const BookingState({
    this.upcoming = const [],
    this.past = const [],
    this.selectedPartners = const [],
    this.selectedAddons = const ['cart'],
    this.selectedCardId = 'v',
    this.promoCode = '',
    this.promoApplied = false,
    this.promoError = false,
    this.isBookingLoading = false,
  });

  Booking? get nextUpcoming {
    if (upcoming.isEmpty) return null;
    return upcoming.first;
  }

  BookingState copyWith({
    List<Booking>? upcoming,
    List<Booking>? past,
    List<String>? selectedPartners,
    List<String>? selectedAddons,
    String? selectedCardId,
    String? promoCode,
    bool? promoApplied,
    bool? promoError,
    bool? isBookingLoading,
  }) {
    return BookingState(
      upcoming: upcoming ?? this.upcoming,
      past: past ?? this.past,
      selectedPartners: selectedPartners ?? this.selectedPartners,
      selectedAddons: selectedAddons ?? this.selectedAddons,
      selectedCardId: selectedCardId ?? this.selectedCardId,
      promoCode: promoCode ?? this.promoCode,
      promoApplied: promoApplied ?? this.promoApplied,
      promoError: promoError ?? this.promoError,
      isBookingLoading: isBookingLoading ?? this.isBookingLoading,
    );
  }
}

class BookingNotifier extends Notifier<BookingState> {
  late final ApiService _api;

  @override
  BookingState build() {
    _api = ref.watch(apiServiceProvider);
    Future.microtask(() => loadBookings());
    return const BookingState();
  }

  Future<void> loadBookings() async {
    final res = await _api.fetchBookings();
    state = state.copyWith(
      upcoming: (res['upcoming'] as List<Booking>?) ?? [],
      past: (res['past'] as List<Booking>?) ?? [],
    );
  }

  void togglePartner(String name, int maxPartners) {
    final current = List<String>.from(state.selectedPartners);
    if (current.contains(name)) {
      current.remove(name);
    } else {
      if (current.length < maxPartners) {
        current.add(name);
      }
    }
    state = state.copyWith(selectedPartners: current);
  }

  void toggleAddon(String id) {
    final current = List<String>.from(state.selectedAddons);
    if (current.contains(id)) {
      current.remove(id);
    } else {
      current.add(id);
    }
    state = state.copyWith(selectedAddons: current);
  }

  void setCard(String cardId) {
    state = state.copyWith(selectedCardId: cardId);
  }

  void setPromoCode(String code) {
    state = state.copyWith(promoCode: code, promoError: false);
  }

  void applyPromo() {
    if (state.promoApplied) {
      state = state.copyWith(promoApplied: false, promoCode: '');
      return;
    }
    if (state.promoCode.trim().toUpperCase() == 'BIRDIE10') {
      state = state.copyWith(promoApplied: true, promoError: false);
    } else {
      state = state.copyWith(promoError: true);
    }
  }

  Future<Booking?> confirmBooking({
    required String date,
    required int min,
    required int holes,
    required int players,
    required double total,
  }) async {
    state = state.copyWith(isBookingLoading: true);
    final booking = await _api.bookTeeTime(
      date: date,
      min: min,
      holes: holes,
      players: players,
      partners: state.selectedPartners,
      addons: state.selectedAddons,
      total: total,
      card: state.selectedCardId,
    );

    if (booking != null) {
      state = state.copyWith(
        upcoming: [booking, ...state.upcoming],
        isBookingLoading: false,
        selectedPartners: [],
        selectedAddons: ['cart'],
        promoApplied: false,
        promoCode: '',
      );
    } else {
      state = state.copyWith(isBookingLoading: false);
    }

    return booking;
  }

  Future<bool> cancelBooking(int id) async {
    final success = await _api.cancelBooking(id);
    if (success) {
      state = state.copyWith(
        upcoming: state.upcoming.where((b) => b.id != id).toList(),
      );
    }
    return success;
  }
}

final bookingProvider =
    NotifierProvider<BookingNotifier, BookingState>(BookingNotifier.new);
