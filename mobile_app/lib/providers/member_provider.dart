import 'dart:convert';
import 'dart:io';
import 'package:flutter/foundation.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../models/member.dart';
import '../models/payment_card.dart';
import '../services/api_service.dart';
import 'booking_provider.dart';
import 'course_provider.dart';
import 'waitlist_provider.dart';

class MemberState {
  final Member member;
  final bool isLoggedIn;
  final bool isLoading;
  final String? loginError;
  final List<PaymentCard> cards;
  final List<double> hcpHistory;
  final Map<String, bool> notifPrefs;
  final int bestScore;

  MemberState({
    Member? member,
    this.isLoggedIn = false,
    this.isLoading = false,
    this.loginError,
    this.cards = const [],
    this.hcpHistory = const [],
    this.notifPrefs = const {'remind': true, 'wait': true, 'promo': false},
    this.bestScore = 0,
  }) : member = member ??
            Member(
              id: 0,
              name: '',
              email: '',
              phone: '',
              tier: 'Standard',
              hcp: 0.0,
              rounds: 0,
              spend: 0.0,
            );

  MemberState copyWith({
    Member? member,
    bool? isLoggedIn,
    bool? isLoading,
    String? loginError,
    List<PaymentCard>? cards,
    List<double>? hcpHistory,
    Map<String, bool>? notifPrefs,
    int? bestScore,
  }) {
    return MemberState(
      member: member ?? this.member,
      isLoggedIn: isLoggedIn ?? this.isLoggedIn,
      isLoading: isLoading ?? this.isLoading,
      loginError: loginError,
      cards: cards ?? this.cards,
      hcpHistory: hcpHistory ?? this.hcpHistory,
      notifPrefs: notifPrefs ?? this.notifPrefs,
      bestScore: bestScore ?? this.bestScore,
    );
  }
}

class MemberNotifier extends Notifier<MemberState> {
  late final ApiService _api;

  static File _getSessionFile() {
    return File('${Directory.systemTemp.path}/golf_tee_session.json');
  }

  @override
  MemberState build() {
    _api = ref.watch(apiServiceProvider);
    Future.microtask(() => _restoreSession());
    return MemberState(
      isLoggedIn: false,
      cards: const [],
    );
  }

  Future<void> _restoreSession() async {
    try {
      final file = _getSessionFile();
      if (await file.exists()) {
        final content = await file.readAsString();
        final data = jsonDecode(content);
        if (data is Map<String, dynamic> && data['member'] != null) {
          final m = Member.fromJson(data['member'] as Map<String, dynamic>);
          _api.currentMemberId = m.id;
          _api.currentMemberName = m.name;
          state = state.copyWith(member: m, isLoggedIn: true);
          await loadProfile();
          ref.read(bookingProvider.notifier).loadBookings();
          ref.read(waitlistProvider.notifier).loadWaitlist();
          return;
        }
      }
    } catch (e) {
      debugPrint('Error restoring member session: $e');
    }
  }

  Future<void> _saveSession(Member m) async {
    try {
      final file = _getSessionFile();
      await file.writeAsString(jsonEncode({
        'member': m.toJson(),
        'saved_at': DateTime.now().toIso8601String(),
      }));
    } catch (e) {
      debugPrint('Error saving session: $e');
    }
  }

  Future<void> loadProfile() async {
    final data = await _api.fetchHomeData();
    if (data['member'] != null) {
      state = state.copyWith(
        member: Member.fromJson(data['member']),
      );
    }
    await loadCards();
  }

  Future<bool> loginWithEmail(String email, [String? password]) async {
    state = state.copyWith(isLoading: true, loginError: null);
    final result = await _api.login(email: email.trim(), password: password);
    if (result.success && result.member != null) {
      _api.currentMemberId = result.member!.id;
      _api.currentMemberName = result.member!.name;
      await _saveSession(result.member!);
      state = state.copyWith(
        member: result.member,
        isLoggedIn: true,
        isLoading: false,
        loginError: null,
      );
      await loadProfile();
      ref.read(bookingProvider.notifier).loadBookings();
      ref.read(waitlistProvider.notifier).loadWaitlist();
      return true;
    } else {
      state = state.copyWith(
        isLoading: false,
        loginError: result.errorMessage ?? 'No active member account found for "$email".',
      );
      return false;
    }
  }

  Future<bool> loginWithGoogle(String email) async {
    state = state.copyWith(isLoading: true, loginError: null);
    final result = await _api.loginWithGoogle(email.trim());
    if (result.success && result.member != null) {
      _api.currentMemberId = result.member!.id;
      _api.currentMemberName = result.member!.name;
      await _saveSession(result.member!);
      state = state.copyWith(
        member: result.member,
        isLoggedIn: true,
        isLoading: false,
        loginError: null,
      );
      await loadProfile();
      ref.read(bookingProvider.notifier).loadBookings();
      ref.read(waitlistProvider.notifier).loadWaitlist();
      return true;
    } else {
      state = state.copyWith(
        isLoading: false,
        loginError: result.errorMessage ??
            'Access Denied: The Google account "$email" has not been pre-registered by administration.',
      );
      return false;
    }
  }

  Future<bool> loginAsMember(Member m) async {
    state = state.copyWith(isLoading: true, loginError: null);
    _api.currentMemberId = m.id;
    _api.currentMemberName = m.name;
    await _saveSession(m);
    state = state.copyWith(
      member: m,
      isLoggedIn: true,
      isLoading: false,
      loginError: null,
    );
    await loadProfile();
    ref.read(bookingProvider.notifier).loadBookings();
    ref.read(waitlistProvider.notifier).loadWaitlist();
    return true;
  }

  Future<void> logout() async {
    try {
      final file = _getSessionFile();
      if (await file.exists()) {
        await file.delete();
      }
    } catch (_) {}
    _api.currentMemberId = 0;
    _api.currentMemberName = '';
    state = MemberState();
  }

  Future<List<Member>> getClubMembers() async {
    return _api.fetchMembers();
  }

  void toggleNotif(String key, bool val) {
    final updated = Map<String, bool>.from(state.notifPrefs);
    updated[key] = val;
    state = state.copyWith(notifPrefs: updated);
  }

  Future<void> loadCards() async {
    final cards = await _api.fetchCards();
    state = state.copyWith(cards: cards);
  }

  Future<void> setDefaultCard(String id) async {
    final updated = state.cards.map((c) {
      return c.copyWith(isDefault: c.id == id);
    }).toList();
    state = state.copyWith(cards: updated);
    await _api.setDefaultCard(id);
    await loadCards();
  }

  Future<bool> removeCard(String id) async {
    final updated = state.cards.where((c) => c.id != id).toList();
    state = state.copyWith(cards: updated);
    final success = await _api.deleteCard(id);
    await loadCards();
    return success;
  }

  Future<PaymentCard?> addCard({
    required String cardNumber,
    required String exp,
    String? cardHolder,
    bool isDefault = false,
  }) async {
    final card = await _api.addCard(
      cardNumber: cardNumber,
      exp: exp,
      cardHolder: cardHolder,
      isDefault: isDefault,
    );
    if (card != null) {
      await loadCards();
    }
    return card;
  }

  void updateHandicap(double newHcp) {
    final updatedHist = List<double>.from(state.hcpHistory)..add(newHcp);
    state = state.copyWith(
      member: state.member.copyWith(hcp: newHcp),
      hcpHistory: updatedHist,
    );
  }
}

final memberProvider =
    NotifierProvider<MemberNotifier, MemberState>(MemberNotifier.new);
