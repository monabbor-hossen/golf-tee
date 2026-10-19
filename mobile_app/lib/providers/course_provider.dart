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
  final int? selectedMin;
  final List<TeeSlot> slots;
  final bool isLoading;

  CourseState({
    CourseCondition? condition,
    this.selectedDayOffset = 0,
    this.holes = 18,
    this.players = 2,
    this.timeOfDay = 'All',
    this.selectedMin,
    this.slots = const [],
    this.isLoading = false,
  }) : condition = condition ?? CourseCondition();

  DateTime get selectedDate =>
      DateTime.now().add(Duration(days: selectedDayOffset));

  String get selectedDateString =>
      selectedDate.toIso8601String().split('T')[0];

  double get unitPrice {
    if (selectedMin == null) return 0.0;
    final isWeekend = selectedDate.weekday >= 6;
    if (selectedMin! >= 900) {
      return (holes == 18) ? 55.0 : 35.0;
    }
    if (isWeekend) {
      return (holes == 18) ? 110.0 : 62.0;
    }
    return (holes == 18) ? 85.0 : 48.0;
  }

  CourseState copyWith({
    CourseCondition? condition,
    int? selectedDayOffset,
    int? holes,
    int? players,
    String? timeOfDay,
    int? selectedMin,
    List<TeeSlot>? slots,
    bool? isLoading,
    bool clearSelectedMin = false,
  }) {
    return CourseState(
      condition: condition ?? this.condition,
      selectedDayOffset: selectedDayOffset ?? this.selectedDayOffset,
      holes: holes ?? this.holes,
      players: players ?? this.players,
      timeOfDay: timeOfDay ?? this.timeOfDay,
      selectedMin: clearSelectedMin ? null : (selectedMin ?? this.selectedMin),
      slots: slots ?? this.slots,
      isLoading: isLoading ?? this.isLoading,
    );
  }
}

class CourseNotifier extends Notifier<CourseState> {
  late final ApiService _api;

  @override
  CourseState build() {
    _api = ref.watch(apiServiceProvider);
    Future.microtask(() {
      loadHomeConditions();
      loadSlots();
    });
    return CourseState();
  }

  Future<void> loadHomeConditions() async {
    final data = await _api.fetchHomeData();
    if (data['conditions'] != null) {
      state = state.copyWith(
        condition: CourseCondition.fromJson(data['conditions']),
      );
    }
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

  void selectSlot(int? min) {
    state = state.copyWith(selectedMin: min);
  }
}

final apiServiceProvider = Provider((ref) => ApiService());

final courseProvider =
    NotifierProvider<CourseNotifier, CourseState>(CourseNotifier.new);
