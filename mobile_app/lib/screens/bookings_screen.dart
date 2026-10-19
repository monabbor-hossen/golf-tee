import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../constants/colors.dart';
import '../models/booking.dart';
import '../models/waitlist_entry.dart';
import '../providers/app_provider.dart';
import '../providers/booking_provider.dart';
import '../providers/course_provider.dart';
import '../providers/waitlist_provider.dart';
import '../widgets/primary_button.dart';
import '../widgets/top_bar.dart';

class BookingsScreen extends ConsumerStatefulWidget {
  const BookingsScreen({super.key});

  @override
  ConsumerState<BookingsScreen> createState() => _BookingsScreenState();
}

class _BookingsScreenState extends ConsumerState<BookingsScreen> {
  int _activeTab = 0; // 0=Upcoming, 1=Waitlist, 2=Past

  @override
  Widget build(BuildContext context) {
    final booking = ref.watch(bookingProvider);
    final waitlist = ref.watch(waitlistProvider);
    final waitlistNotifier = ref.read(waitlistProvider.notifier);

    final upcoming = booking.upcoming;
    final waitlistEntries = waitlist.entries;
    final past = booking.past;

    return Column(
      children: [
        const TopBar(title: 'My bookings'),
        // 3-Segment Tab Bar
        Padding(
          padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 4),
          child: Container(
            padding: const EdgeInsets.all(3),
            decoration: BoxDecoration(
              color: Colors.grey.shade200,
              borderRadius: BorderRadius.circular(16),
            ),
            child: Row(
              children: [
                _buildSegmentTab(
                  index: 0,
                  label: 'Upcoming',
                  count: upcoming.length,
                ),
                _buildSegmentTab(
                  index: 1,
                  label: 'Waitlist',
                  count: waitlistEntries.length,
                ),
                _buildSegmentTab(
                  index: 2,
                  label: 'Past',
                  count: past.length,
                ),
              ],
            ),
          ),
        ),
        const SizedBox(height: 12),

        // Tab Content
        Expanded(
          child: _activeTab == 0
              ? _buildUpcomingList(upcoming)
              : _activeTab == 1
                  ? _buildWaitlistList(waitlistEntries, waitlistNotifier)
                  : _buildPastList(past),
        ),
      ],
    );
  }

  Widget _buildSegmentTab({
    required int index,
    required String label,
    required int count,
  }) {
    final isSel = _activeTab == index;

    return Expanded(
      child: GestureDetector(
        onTap: () => setState(() => _activeTab = index),
        child: AnimatedContainer(
          duration: const Duration(milliseconds: 180),
          padding: const EdgeInsets.symmetric(vertical: 9),
          decoration: BoxDecoration(
            color: isSel ? Colors.white : Colors.transparent,
            borderRadius: BorderRadius.circular(13),
            boxShadow: isSel
                ? [
                    BoxShadow(
                      color: Colors.black.withOpacity(0.04),
                      blurRadius: 4,
                      offset: const Offset(0, 1),
                    ),
                  ]
                : null,
          ),
          alignment: Alignment.center,
          child: Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Text(
                label,
                style: TextStyle(
                  fontSize: 13,
                  fontWeight: FontWeight.w700,
                  color: isSel ? AppColors.forest : Colors.grey.shade600,
                ),
              ),
              const SizedBox(width: 4),
              Text(
                '$count',
                style: TextStyle(
                  fontSize: 11,
                  fontWeight: FontWeight.w600,
                  color: isSel
                      ? AppColors.forest.withOpacity(0.7)
                      : Colors.grey.shade400,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildUpcomingList(List<Booking> list) {
    if (list.isEmpty) {
      return _buildEmptyState(
        title: 'No upcoming rounds',
        actionLabel: 'Book a tee time',
        onTap: () =>
            ref.read(appProvider.notifier).navigateTo(AppScreen.book),
      );
    }

    return ListView.separated(
      padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 4),
      itemCount: list.length,
      separatorBuilder: (_, __) => const SizedBox(height: 10),
      itemBuilder: (context, i) {
        final b = list[i];
        return GestureDetector(
          onTap: () => ref
              .read(appProvider.notifier)
              .navigateTo(AppScreen.detail, b.id),
          child: Container(
            padding: const EdgeInsets.all(14),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(20),
              border: Border.all(color: AppColors.borderLight),
              boxShadow: [
                BoxShadow(
                  color: Colors.black.withOpacity(0.02),
                  blurRadius: 6,
                  offset: const Offset(0, 2),
                ),
              ],
            ),
            child: Row(
              children: [
                Container(
                  width: 50,
                  padding: const EdgeInsets.symmetric(vertical: 8),
                  decoration: BoxDecoration(
                    color: AppColors.forest.withOpacity(0.1),
                    borderRadius: BorderRadius.circular(14),
                  ),
                  child: Column(
                    children: [
                      const Text(
                        'DATE',
                        style: TextStyle(
                          fontSize: 9,
                          fontWeight: FontWeight.w800,
                          color: AppColors.forest,
                        ),
                      ),
                      Text(
                        b.date.split('-').last,
                        style: const TextStyle(
                          fontSize: 18,
                          fontWeight: FontWeight.w900,
                          color: AppColors.forest,
                          height: 1.1,
                        ),
                      ),
                    ],
                  ),
                ),
                const SizedBox(width: 14),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        children: [
                          Text(
                            b.time,
                            style: const TextStyle(
                              fontSize: 15,
                              fontWeight: FontWeight.w800,
                              color: AppColors.textMain,
                            ),
                          ),
                          const SizedBox(width: 8),
                          Text(
                            b.date,
                            style: const TextStyle(
                              fontSize: 11,
                              fontWeight: FontWeight.w600,
                              color: AppColors.forest,
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 2),
                      Text(
                        '${b.holes} holes · ${b.players} players · ${b.tee}',
                        style: TextStyle(
                          fontSize: 12,
                          color: Colors.grey.shade500,
                        ),
                      ),
                    ],
                  ),
                ),
                const Icon(
                  Icons.chevron_right_rounded,
                  color: Color(0xFFD1D5DB),
                ),
              ],
            ),
          ),
        );
      },
    );
  }

  Widget _buildWaitlistList(
      List<WaitlistEntry> list, WaitlistNotifier waitlistNotifier) {
    if (list.isEmpty) {
      return _buildEmptyState(
        title: "You're not on any waitlists",
        actionLabel: 'Find a time',
        onTap: () =>
            ref.read(appProvider.notifier).navigateTo(AppScreen.book),
      );
    }

    return ListView.separated(
      padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 4),
      itemCount: list.length,
      separatorBuilder: (_, __) => const SizedBox(height: 10),
      itemBuilder: (context, i) {
        final w = list[i];
        final isTop = w.position == 1;

        return Container(
          padding: const EdgeInsets.all(16),
          decoration: BoxDecoration(
            color: isTop ? AppColors.waitLight : Colors.white,
            borderRadius: BorderRadius.circular(20),
            border: Border.all(
              color: isTop
                  ? AppColors.wait.withOpacity(0.5)
                  : AppColors.borderLight,
            ),
          ),
          child: Column(
            children: [
              Row(
                children: [
                  Container(
                    width: 44,
                    height: 44,
                    decoration: BoxDecoration(
                      color: isTop ? AppColors.wait : const Color(0xFFF3F4F6),
                      shape: BoxShape.circle,
                    ),
                    child: Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Text(
                          'NO.',
                          style: TextStyle(
                            fontSize: 9,
                            fontWeight: FontWeight.w800,
                            color: isTop ? Colors.white : Colors.grey.shade500,
                            height: 1.0,
                          ),
                        ),
                        Text(
                          '${w.position}',
                          style: TextStyle(
                            fontSize: 16,
                            fontWeight: FontWeight.w900,
                            color: isTop ? Colors.white : AppColors.textMain,
                            height: 1.0,
                          ),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(width: 14),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          '${w.date} · ${w.time}',
                          style: const TextStyle(
                            fontSize: 15,
                            fontWeight: FontWeight.w800,
                            color: AppColors.textMain,
                          ),
                        ),
                        const SizedBox(height: 2),
                        Text(
                          '${w.holes} holes · ${w.players} players',
                          style: TextStyle(
                            fontSize: 12,
                            color: Colors.grey.shade500,
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 12),
              if (isTop) ...[
                PrimaryButton(
                  text: 'Claim spot · 14:52 left',
                  height: 44,
                  backgroundColor: AppColors.wait,
                  onPressed: () {
                    ref.read(courseProvider.notifier).selectSlot(w.min);
                    ref
                        .read(appProvider.notifier)
                        .navigateTo(AppScreen.checkout);
                  },
                ),
              ] else ...[
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Text(
                      "We'll notify you when a spot opens",
                      style: TextStyle(
                        fontSize: 12,
                        color: Colors.grey.shade500,
                      ),
                    ),
                    GestureDetector(
                      onTap: () {
                        waitlistNotifier.leaveWaitlist(w.id);
                        ref
                            .read(appProvider.notifier)
                            .showToast('Left the waitlist');
                      },
                      child: const Text(
                        'Leave',
                        style: TextStyle(
                          fontSize: 13,
                          fontWeight: FontWeight.w700,
                          color: AppColors.danger,
                        ),
                      ),
                    ),
                  ],
                ),
              ],
            ],
          ),
        );
      },
    );
  }

  Widget _buildPastList(List<Booking> list) {
    if (list.isEmpty) {
      return _buildEmptyState(
        title: 'No past rounds recorded',
        actionLabel: 'Book a tee time',
        onTap: () =>
            ref.read(appProvider.notifier).navigateTo(AppScreen.book),
      );
    }

    return ListView.separated(
      padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 4),
      itemCount: list.length,
      separatorBuilder: (_, __) => const SizedBox(height: 10),
      itemBuilder: (context, i) {
        final b = list[i];

        return Container(
          padding: const EdgeInsets.all(16),
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(20),
            border: Border.all(color: AppColors.borderLight),
          ),
          child: Row(
            children: [
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      '${b.date} · ${b.time}',
                      style: const TextStyle(
                        fontSize: 14,
                        fontWeight: FontWeight.w800,
                        color: AppColors.textMain,
                      ),
                    ),
                    const SizedBox(height: 2),
                    Text(
                      '${b.holes} holes · ${b.players} players · \$${b.total.toInt()}',
                      style: TextStyle(
                        fontSize: 12,
                        color: Colors.grey.shade500,
                      ),
                    ),
                  ],
                ),
              ),
              if (b.score != null) ...[
                Column(
                  children: [
                    Text(
                      '${b.score}',
                      style: const TextStyle(
                        fontSize: 18,
                        fontWeight: FontWeight.w900,
                        color: AppColors.textMain,
                      ),
                    ),
                    const Text(
                      'Score',
                      style: TextStyle(
                        fontSize: 10,
                        color: AppColors.textMuted,
                      ),
                    ),
                  ],
                ),
                const SizedBox(width: 14),
              ],
              ElevatedButton(
                style: ElevatedButton.styleFrom(
                  backgroundColor: AppColors.forest.withOpacity(0.1),
                  foregroundColor: AppColors.forest,
                  elevation: 0,
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(20),
                  ),
                  padding:
                      const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
                ),
                onPressed: () {
                  ref.read(courseProvider.notifier).setHoles(b.holes);
                  ref.read(courseProvider.notifier).setPlayers(b.players);
                  ref.read(appProvider.notifier).navigateTo(AppScreen.book);
                },
                child: const Text(
                  'Book again',
                  style: TextStyle(
                    fontSize: 12,
                    fontWeight: FontWeight.w700,
                  ),
                ),
              ),
            ],
          ),
        );
      },
    );
  }

  Widget _buildEmptyState({
    required String title,
    required String actionLabel,
    required VoidCallback onTap,
  }) {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Container(
            width: 56,
            height: 56,
            decoration: BoxDecoration(
              color: AppColors.forest.withOpacity(0.1),
              shape: BoxShape.circle,
            ),
            child: const Icon(
              Icons.flag_rounded,
              size: 28,
              color: AppColors.forest,
            ),
          ),
          const SizedBox(height: 14),
          Text(
            title,
            style: const TextStyle(
              fontSize: 15,
              fontWeight: FontWeight.w700,
              color: AppColors.textSecondary,
            ),
          ),
          const SizedBox(height: 8),
          GestureDetector(
            onTap: onTap,
            child: Text(
              actionLabel,
              style: const TextStyle(
                fontSize: 14,
                fontWeight: FontWeight.w800,
                color: AppColors.forest,
              ),
            ),
          ),
        ],
      ),
    );
  }
}
