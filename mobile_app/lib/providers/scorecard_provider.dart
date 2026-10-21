import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../constants/strings.dart';
import '../services/api_service.dart';
import 'course_provider.dart';

class ScorecardState {
  final int currentHole; // 0 to 17
  final List<int> scores; // 18 elements
  final bool isSaving;

  ScorecardState({
    this.currentHole = 0,
    List<int>? scores,
    this.isSaving = false,
  }) : scores = scores ?? List.filled(18, 0);

  int get playedCount => scores.where((s) => s > 0).length;

  int get totalStrokes => scores.fold(0, (a, b) => a + b);

  int get parPlayed {
    int par = 0;
    for (int i = 0; i < scores.length; i++) {
      if (scores[i] > 0) par += AppStrings.pars[i];
    }
    return par;
  }

  int get toPar => totalStrokes - parPlayed;

  int nineHoles(int from) {
    return scores.sublist(from, from + 9).fold(0, (a, b) => a + b);
  }

  int parNine(int from) {
    return AppStrings.pars.sublist(from, from + 9).fold(0, (a, b) => a + b);
  }

  ScorecardState copyWith({
    int? currentHole,
    List<int>? scores,
    bool? isSaving,
  }) {
    return ScorecardState(
      currentHole: currentHole ?? this.currentHole,
      scores: scores ?? this.scores,
      isSaving: isSaving ?? this.isSaving,
    );
  }
}

class ScorecardNotifier extends Notifier<ScorecardState> {
  late final ApiService _api;

  @override
  ScorecardState build() {
    _api = ref.watch(apiServiceProvider);
    return ScorecardState(
      scores: List.filled(18, 0),
      currentHole: 0,
    );
  }

  void selectHole(int hole) {
    state = state.copyWith(currentHole: hole.clamp(0, 17));
  }

  void nextHole() {
    if (state.currentHole < 17) {
      state = state.copyWith(currentHole: state.currentHole + 1);
    }
  }

  void prevHole() {
    if (state.currentHole > 0) {
      state = state.copyWith(currentHole: state.currentHole - 1);
    }
  }

  void setStroke(int stroke) {
    final updated = List<int>.from(state.scores);
    updated[state.currentHole] = stroke.clamp(1, 12);
    state = state.copyWith(scores: updated);
  }

  void incrementStroke() {
    final cur = state.scores[state.currentHole];
    final par = AppStrings.pars[state.currentHole];
    final target = (cur == 0 ? par : cur) + 1;
    setStroke(target);
  }

  void decrementStroke() {
    final cur = state.scores[state.currentHole];
    final par = AppStrings.pars[state.currentHole];
    final target = (cur == 0 ? par : cur) - 1;
    setStroke(target);
  }

  void saveAndAdvance() {
    if (state.scores[state.currentHole] == 0) {
      setStroke(AppStrings.pars[state.currentHole]);
    }
    if (state.currentHole < 17) {
      nextHole();
    }
  }

  Future<double?> finishRound() async {
    state = state.copyWith(isSaving: true);
    final newHcp = await _api.saveScore(state.totalStrokes, state.toPar);
    state = state.copyWith(
      isSaving: false,
      scores: List.filled(18, 0),
      currentHole: 0,
    );
    return newHcp;
  }
}

final scorecardProvider =
    NotifierProvider<ScorecardNotifier, ScorecardState>(ScorecardNotifier.new);
