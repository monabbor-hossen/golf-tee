import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../models/member.dart';
import '../models/payment_card.dart';
import '../services/api_service.dart';
import 'course_provider.dart';

class MemberState {
  final Member member;
  final List<PaymentCard> cards;
  final List<double> hcpHistory;
  final Map<String, bool> notifPrefs;
  final int bestScore;

  MemberState({
    Member? member,
    this.cards = const [],
    this.hcpHistory = const [10.2, 9.9, 9.7, 9.2, 9.0, 8.7, 8.4],
    this.notifPrefs = const {'remind': true, 'wait': true, 'promo': false},
    this.bestScore = 79,
  }) : member = member ??
            Member(
              id: 1,
              name: 'James Carter',
              email: 'james.carter@mail.com',
              phone: '(555) 301-2201',
              tier: 'Gold',
              hcp: 8.4,
              rounds: 42,
              spend: 4820.0,
            );

  MemberState copyWith({
    Member? member,
    List<PaymentCard>? cards,
    List<double>? hcpHistory,
    Map<String, bool>? notifPrefs,
    int? bestScore,
  }) {
    return MemberState(
      member: member ?? this.member,
      cards: cards ?? this.cards,
      hcpHistory: hcpHistory ?? this.hcpHistory,
      notifPrefs: notifPrefs ?? this.notifPrefs,
      bestScore: bestScore ?? this.bestScore,
    );
  }
}

class MemberNotifier extends Notifier<MemberState> {
  late final ApiService _api;

  @override
  MemberState build() {
    _api = ref.watch(apiServiceProvider);
    Future.microtask(() => loadProfile());
    return MemberState(
      cards: [
        PaymentCard(
          id: 'v',
          brand: 'Visa',
          last4: '4242',
          exp: '09/27',
          isDefault: true,
        ),
        PaymentCard(
          id: 'm',
          brand: 'Mastercard',
          last4: '8810',
          exp: '03/26',
          isDefault: false,
        ),
      ],
    );
  }

  Future<void> loadProfile() async {
    final data = await _api.fetchHomeData();
    if (data['member'] != null) {
      state = state.copyWith(
        member: Member.fromJson(data['member']),
      );
    }
  }

  void toggleNotif(String key, bool val) {
    final updated = Map<String, bool>.from(state.notifPrefs);
    updated[key] = val;
    state = state.copyWith(notifPrefs: updated);
  }

  void setDefaultCard(String id) {
    final updated = state.cards.map((c) {
      return c.copyWith(isDefault: c.id == id);
    }).toList();
    state = state.copyWith(cards: updated);
  }

  void removeCard(String id) {
    final updated = state.cards.where((c) => c.id != id).toList();
    state = state.copyWith(cards: updated);
  }

  void addCard(String brand, String last4, String exp) {
    final newCard = PaymentCard(
      id: 'c_${DateTime.now().millisecondsSinceEpoch}',
      brand: brand,
      last4: last4,
      exp: exp,
      isDefault: false,
    );
    state = state.copyWith(cards: [...state.cards, newCard]);
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
