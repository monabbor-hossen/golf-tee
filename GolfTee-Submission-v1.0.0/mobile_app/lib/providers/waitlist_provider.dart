import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../models/waitlist_entry.dart';
import '../services/api_service.dart';
import 'course_provider.dart';

class WaitlistState {
  final List<WaitlistEntry> entries;
  final bool isLoading;

  const WaitlistState({
    this.entries = const [],
    this.isLoading = false,
  });

  WaitlistEntry? get readyEntry {
    try {
      return entries.firstWhere((e) => e.position == 1);
    } catch (_) {
      return null;
    }
  }

  WaitlistState copyWith({
    List<WaitlistEntry>? entries,
    bool? isLoading,
  }) {
    return WaitlistState(
      entries: entries ?? this.entries,
      isLoading: isLoading ?? this.isLoading,
    );
  }
}

class WaitlistNotifier extends Notifier<WaitlistState> {
  late final ApiService _api;

  @override
  WaitlistState build() {
    _api = ref.watch(apiServiceProvider);
    Future.microtask(() => loadWaitlist());
    return const WaitlistState();
  }

  Future<void> loadWaitlist() async {
    final res = await _api.fetchBookings();
    if (res['waitlist'] is List<WaitlistEntry>) {
      state = state.copyWith(entries: res['waitlist'] as List<WaitlistEntry>);
    }
  }

  Future<void> joinWaitlist({
    required String date,
    required int min,
    required int holes,
    required int players,
  }) async {
    final entry = await _api.joinWaitlist(
      date: date,
      min: min,
      holes: holes,
      players: players,
    );
    if (entry != null) {
      state = state.copyWith(entries: [...state.entries, entry]);
    }
  }

  Future<void> leaveWaitlist(int id) async {
    await _api.leaveWaitlist(id);
    state = state.copyWith(
      entries: state.entries.where((e) => e.id != id).toList(),
    );
  }
}

final waitlistProvider =
    NotifierProvider<WaitlistNotifier, WaitlistState>(WaitlistNotifier.new);
