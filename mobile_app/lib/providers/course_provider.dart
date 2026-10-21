import 'dart:async';
import 'package:flutter/widgets.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../models/course_condition.dart';
import '../models/tee_slot.dart';
import '../services/api_service.dart';

class CourseState {
  final CourseCondition condition;
  final int selectedDayOffset; // 0 to 13 days
  final int holes; // 18 or 9
  final int players; // 1 to 4
  final String timeOfDay; // 'All', 'Morning', 'Midday', 'Twilight'
  final Set<int> selectedMins;
  final List<TeeSlot> slots;
  final bool isLoading;

  CourseState({
    CourseCondition? condition,
    this.selectedDayOffset = 0,
    this.holes = 18,
    this.players = 2,
    this.timeOfDay = 'All',
    Set<int>? selectedMins,
    int? selectedMin,
    this.slots = const [],
    this.isLoading = false,
  })  : condition = condition ?? CourseCondition(),
        selectedMins = selectedMins ??
            (selectedMin != null ? {selectedMin} : const <int>{});

  DateTime get selectedDate =>
      DateTime.now().add(Duration(days: selectedDayOffset));

  String get selectedDateString =>
      selectedDate.toIso8601String().split('T')[0];

  int? get selectedMin =>
      selectedMins.isEmpty ? null : (selectedMins.toList()..sort()).first;

  List<int> get sortedSelectedMins => selectedMins.toList()..sort();

  bool isSlotSelected(int min) => selectedMins.contains(min);

  int get selectedSlotsCount => selectedMins.length;

  double unitPriceFor(int min) {
    final isWeekend = selectedDate.weekday >= 6;
    if (min >= 900) {
      return (holes == 18) ? 55.0 : 35.0;
    }
    if (isWeekend) {
      return (holes == 18) ? 110.0 : 62.0;
    }
    return (holes == 18) ? 85.0 : 48.0;
  }

  double get unitPrice {
    if (selectedMin == null) return 0.0;
    return unitPriceFor(selectedMin!);
  }

  double get totalGreensFee {
    if (selectedMins.isEmpty) return 0.0;
    double sum = 0.0;
    for (final m in selectedMins) {
      sum += unitPriceFor(m) * players;
    }
    return sum;
  }

  CourseState copyWith({
    CourseCondition? condition,
    int? selectedDayOffset,
    int? holes,
    int? players,
    String? timeOfDay,
    Set<int>? selectedMins,
    int? selectedMin,
    List<TeeSlot>? slots,
    bool? isLoading,
    bool clearSelectedMin = false,
  }) {
    Set<int> newMins;
    if (clearSelectedMin) {
      newMins = const <int>{};
    } else if (selectedMins != null) {
      newMins = selectedMins;
    } else if (selectedMin != null) {
      newMins = {selectedMin};
    } else {
      newMins = this.selectedMins;
    }

    return CourseState(
      condition: condition ?? this.condition,
      selectedDayOffset: selectedDayOffset ?? this.selectedDayOffset,
      holes: holes ?? this.holes,
      players: players ?? this.players,
      timeOfDay: timeOfDay ?? this.timeOfDay,
      selectedMins: newMins,
      slots: slots ?? this.slots,
      isLoading: isLoading ?? this.isLoading,
    );
  }
}

class CourseNotifier extends Notifier<CourseState> {
  late final ApiService _api;
  Timer? _weatherPollingTimer;
  AppLifecycleListener? _lifecycleListener;

  @override
  CourseState build() {
    _api = ref.watch(apiServiceProvider);

    _lifecycleListener = AppLifecycleListener(
      onResume: () {
        // Automatically check if weather was updated when app returns from background
        checkWeatherUpdate();
      },
    );

    ref.onDispose(() {
      _weatherPollingTimer?.cancel();
      _lifecycleListener?.dispose();
    });

    Future.microtask(() {
      loadHomeConditions();
      loadSlots();
      _startWeatherPolling();
    });
    return CourseState();
  }

  void _startWeatherPolling() {
    _weatherPollingTimer?.cancel();
    // Periodically poll backend (every 30 seconds) to check if weather was refreshed or changed
    _weatherPollingTimer = Timer.periodic(const Duration(seconds: 30), (_) {
      checkWeatherUpdate();
    });
  }

  /// Checks if weather or course conditions have updated on the backend.
  /// If conditions changed, updates state so the UI refreshes live.
  Future<bool> checkWeatherUpdate() async {
    try {
      final newCond = await _api.fetchConditions(forceRefresh: false);
      if (newCond != null && state.condition != newCond) {
        state = state.copyWith(condition: newCond);
        return true;
      }
    } catch (_) {}
    return false;
  }

  Future<void> loadHomeConditions() async {
    final data = await _api.fetchHomeData();
    if (data['conditions'] != null) {
      state = state.copyWith(
        condition: CourseCondition.fromJson(data['conditions']),
      );
    }
  }

  Future<void> refreshWeather({
    String? location,
    double? lat,
    double? lng,
    bool forceRefresh = true,
  }) async {
    final cond = await _api.fetchConditions(
      location: location,
      lat: lat,
      lng: lng,
      forceRefresh: forceRefresh,
    );
    if (cond != null) {
      state = state.copyWith(condition: cond);
    } else {
      await loadHomeConditions();
    }
  }

  Future<bool> refreshWithGps() async {
    final gps = await _api.detectDeviceGps();
    if (gps != null && gps['lat'] != null && gps['lng'] != null) {
      final cond = await _api.fetchConditions(
        lat: gps['lat'] as double,
        lng: gps['lng'] as double,
        forceRefresh: true,
      );
      if (cond != null) {
        state = state.copyWith(condition: cond);
        return true;
      }
    }
    await loadHomeConditions();
    return false;
  }

  Future<void> loadSlots() async {
    state = state.copyWith(isLoading: true);
    final slots = await _api.fetchSlots(state.selectedDateString, state.holes);
    state = state.copyWith(slots: slots, isLoading: false);
  }

  void setDayOffset(int offset) {
    state = state.copyWith(
      selectedDayOffset: offset,
      clearSelectedMin: true,
    );
    loadSlots();
  }

  void setHoles(int holes) {
    state = state.copyWith(
      holes: holes,
      clearSelectedMin: true,
    );
    loadSlots();
  }

  void setPlayers(int players) {
    state = state.copyWith(players: players.clamp(1, 4));
  }

  void setTimeOfDay(String tod) {
    state = state.copyWith(
      timeOfDay: tod,
      clearSelectedMin: true,
    );
  }

  void toggleSlot(int min) {
    final updated = Set<int>.from(state.selectedMins);
    if (updated.contains(min)) {
      updated.remove(min);
    } else {
      updated.add(min);
    }
    state = state.copyWith(selectedMins: updated);
  }

  void selectSlot(int? min) {
    if (min == null) {
      state = state.copyWith(clearSelectedMin: true);
    } else {
      toggleSlot(min);
    }
  }

  void clearSelectedSlots() {
    state = state.copyWith(clearSelectedMin: true);
  }
}

final apiServiceProvider = Provider((ref) => ApiService());

final courseProvider =
    NotifierProvider<CourseNotifier, CourseState>(CourseNotifier.new);
