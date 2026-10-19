import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import '../constants/colors.dart';
import '../constants/strings.dart';
import '../models/tee_slot.dart';
import '../providers/app_provider.dart';
import '../providers/course_provider.dart';
import '../providers/waitlist_provider.dart';
import '../widgets/horizontal_date_selector.dart';
import '../widgets/primary_button.dart';
import '../widgets/time_slot_chip.dart';
import '../widgets/top_bar.dart';

class BookScreen extends ConsumerStatefulWidget {
  const BookScreen({super.key});

  @override
  ConsumerState<BookScreen> createState() => _BookScreenState();
}

class _BookScreenState extends ConsumerState<BookScreen> {
  TeeSlot? _waitlistModalSlot;
  bool _showOnlyOpen = false;

  String _formatMinutes(int m) {
    final h = m ~/ 60;
    final mins = m % 60;
    final period = h < 12 ? 'AM' : 'PM';
    final displayH = ((h + 11) % 12) + 1;
    return '$displayH:${mins.toString().padLeft(2, '0')} $period';
  }

  @override
  Widget build(BuildContext context) {
    final course = ref.watch(courseProvider);
    final courseNotifier = ref.read(courseProvider.notifier);
    final appNotifier = ref.read(appProvider.notifier);

    final selectedDate = course.selectedDate;
    final isWeekend = selectedDate.weekday >= 6;
    final monthYear = DateFormat('MMMM yyyy').format(selectedDate);

    // Filter slots by Time of Day
    final timeFilteredSlots = course.slots.where((s) {
      if (course.timeOfDay == 'Morning') return s.min < 660;
      if (course.timeOfDay == 'Midday') return s.min >= 660 && s.min < 900;
      if (course.timeOfDay == 'Twilight') return s.min >= 900;
      return true;
    }).toList();

    final openCount =
        timeFilteredSlots.where((s) => s.state == SlotState.available).length;

    // Filter by open only if user clicked "XX open"
    final displayedSlots = _showOnlyOpen
        ? timeFilteredSlots
            .where((s) =>
                s.state == SlotState.available ||
                course.isSlotSelected(s.min))
            .toList()
        : timeFilteredSlots;

    return Stack(
      children: [
        Column(
          children: [
            TopBar(
              title: 'Book a tee time',
              right: Container(
                padding:
                    const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                decoration: BoxDecoration(
                  color: AppColors.forest.withOpacity(0.1),
                  borderRadius: BorderRadius.circular(20),
                ),
                child: const Text(
                  AppStrings.courseName,
                  style: TextStyle(
                    fontSize: 12,
                    fontWeight: FontWeight.w700,
                    color: AppColors.forest,
                  ),
                ),
              ),
            ),
            Expanded(
              child: SingleChildScrollView(
                padding: const EdgeInsets.only(bottom: 170),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    // Month & Weekend Label
                    Padding(
                      padding: const EdgeInsets.symmetric(
                          horizontal: 20, vertical: 4),
                      child: Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Text(
                            monthYear,
                            style: const TextStyle(
                              fontSize: 17,
                              fontWeight: FontWeight.w800,
                              color: AppColors.textMain,
                            ),
                          ),
                          if (isWeekend)
                            const Text(
                              'Weekend rates',
                              style: TextStyle(
                                fontSize: 12,
                                fontWeight: FontWeight.w700,
                                color: AppColors.waitText,
                              ),
                            ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 8),

                    // 14-day date picker
                    HorizontalDateSelector(
                      selectedIndex: course.selectedDayOffset,
                      onSelected: (i) => courseNotifier.setDayOffset(i),
                    ),
                    const SizedBox(height: 16),

                    // Holes & Players Control Card
                    Padding(
                      padding: const EdgeInsets.symmetric(horizontal: 20),
                      child: Container(
                        padding: const EdgeInsets.all(12),
                        decoration: BoxDecoration(
                          color: Colors.white,
                          borderRadius: BorderRadius.circular(22),
                          border: Border.all(color: AppColors.borderLight),
                        ),
                        child: Column(
                          children: [
                            // 18 / 9 Holes Toggle
                            Container(
                              padding: const EdgeInsets.all(3),
                              decoration: BoxDecoration(
                                color: const Color(0xFFF3F4F6),
                                borderRadius: BorderRadius.circular(14),
                              ),
                              child: Row(
                                children: [18, 9].map((h) {
                                  final isSel = course.holes == h;
                                  return Expanded(
                                    child: GestureDetector(
                                      onTap: () => courseNotifier.setHoles(h),
                                      child: AnimatedContainer(
                                        duration:
                                            const Duration(milliseconds: 150),
                                        padding: const EdgeInsets.symmetric(
                                            vertical: 9),
                                        decoration: BoxDecoration(
                                          color: isSel
                                              ? Colors.white
                                              : Colors.transparent,
                                          borderRadius:
                                              BorderRadius.circular(11),
                                          boxShadow: isSel
                                              ? [
                                                  BoxShadow(
                                                    color: Colors.black
                                                        .withOpacity(0.04),
                                                    blurRadius: 4,
                                                    offset: const Offset(0, 1),
                                                  ),
                                                ]
                                              : null,
                                        ),
                                        alignment: Alignment.center,
                                        child: Text(
                                          '$h Holes',
                                          style: TextStyle(
                                            fontSize: 13,
                                            fontWeight: FontWeight.w700,
                                            color: isSel
                                                ? AppColors.forest
                                                : Colors.grey.shade600,
                                          ),
                                        ),
                                      ),
                                    ),
                                  );
                                }).toList(),
                              ),
                            ),
                            const SizedBox(height: 12),

                            // Players Counter
                            Row(
                              mainAxisAlignment:
                                  MainAxisAlignment.spaceBetween,
                              children: [
                                Expanded(
                                  child: Row(
                                    children: [
                                      const Icon(
                                        Icons.group_outlined,
                                        size: 18,
                                        color: AppColors.forest,
                                      ),
                                      const SizedBox(width: 8),
                                      const Text(
                                        'Players',
                                        style: TextStyle(
                                          fontSize: 14,
                                          fontWeight: FontWeight.w700,
                                          color: AppColors.textMain,
                                        ),
                                      ),
                                      const SizedBox(width: 6),
                                      Flexible(
                                        child: Text(
                                          '(rate per player)',
                                          style: TextStyle(
                                            fontSize: 11,
                                            color: Colors.grey.shade400,
                                            fontWeight: FontWeight.w500,
                                          ),
                                          overflow: TextOverflow.ellipsis,
                                        ),
                                      ),
                                    ],
                                  ),
                                ),
                                const SizedBox(width: 8),
                                Row(
                                  children: [
                                    _buildCircleBtn(
                                      icon: Icons.remove,
                                      enabled: course.players > 1,
                                      onTap: () => courseNotifier
                                          .setPlayers(course.players - 1),
                                    ),
                                    SizedBox(
                                      width: 32,
                                      child: Text(
                                        '${course.players}',
                                        textAlign: TextAlign.center,
                                        style: const TextStyle(
                                          fontSize: 16,
                                          fontWeight: FontWeight.w800,
                                          color: AppColors.textMain,
                                        ),
                                      ),
                                    ),
                                    _buildCircleBtn(
                                      icon: Icons.add,
                                      enabled: course.players < 4,
                                      onTap: () => courseNotifier
                                          .setPlayers(course.players + 1),
                                    ),
                                  ],
                                ),
                              ],
                            ),
                          ],
                        ),
                      ),
                    ),
                    const SizedBox(height: 16),

                    // Time of Day Filter Chips
                    SingleChildScrollView(
                      scrollDirection: Axis.horizontal,
                      padding: const EdgeInsets.symmetric(horizontal: 20),
                      child: Row(
                        children: [
                          for (final tod in ['All', 'Morning', 'Midday', 'Twilight'])
                            Padding(
                              padding: const EdgeInsets.only(right: 8),
                              child: GestureDetector(
                                onTap: () => courseNotifier.setTimeOfDay(tod),
                                child: Container(
                                  padding: const EdgeInsets.symmetric(
                                      horizontal: 16, vertical: 8),
                                  decoration: BoxDecoration(
                                    color: course.timeOfDay == tod
                                        ? AppColors.textMain
                                        : Colors.white,
                                    borderRadius: BorderRadius.circular(20),
                                    border: Border.all(
                                      color: course.timeOfDay == tod
                                          ? AppColors.textMain
                                          : AppColors.border,
                                    ),
                                  ),
                                  child: Row(
                                    children: [
                                      Text(
                                        tod,
                                        style: TextStyle(
                                          fontSize: 13,
                                          fontWeight: FontWeight.w700,
                                          color: course.timeOfDay == tod
                                              ? Colors.white
                                              : AppColors.textSecondary,
                                        ),
                                      ),
                                      if (tod == 'Twilight') ...[
                                        const SizedBox(width: 4),
                                        const Text(
                                          '−35%',
                                          style: TextStyle(
                                            fontSize: 10,
                                            fontWeight: FontWeight.w800,
                                            color: AppColors.wait,
                                          ),
                                        ),
                                      ],
                                    ],
                                  ),
                                ),
                              ),
                            ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 14),

                    // Legend & Open Count
                    Padding(
                      padding: const EdgeInsets.symmetric(horizontal: 20),
                      child: Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        crossAxisAlignment: CrossAxisAlignment.center,
                        children: [
                          Expanded(
                            child: Wrap(
                              spacing: 8,
                              runSpacing: 4,
                              crossAxisAlignment: WrapCrossAlignment.center,
                              children: [
                                _buildLegendDot(
                                    AppColors.forest.withValues(alpha: 0.8), 'Open',
                                    isBorder: true),
                                _buildLegendDot(AppColors.wait, 'Waitlist'),
                                _buildLegendDot(Colors.grey.shade400, 'Booked'),
                              ],
                            ),
                          ),
                          const SizedBox(width: 6),
                          Row(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              if (course.selectedSlotsCount > 0) ...[
                                GestureDetector(
                                  onTap: () =>
                                      courseNotifier.clearSelectedSlots(),
                                  child: Container(
                                    padding: const EdgeInsets.symmetric(
                                        horizontal: 7, vertical: 3),
                                    decoration: BoxDecoration(
                                      color: Colors.red.shade50,
                                      borderRadius: BorderRadius.circular(10),
                                    ),
                                    child: Text(
                                      'Clear (${course.selectedSlotsCount})',
                                      style: const TextStyle(
                                        fontSize: 10,
                                        fontWeight: FontWeight.w700,
                                        color: Colors.redAccent,
                                      ),
                                    ),
                                  ),
                                ),
                                const SizedBox(width: 6),
                              ],
                              GestureDetector(
                                onTap: () {
                                  setState(() {
                                    _showOnlyOpen = !_showOnlyOpen;
                                  });
                                },
                                child: AnimatedContainer(
                                  duration: const Duration(milliseconds: 200),
                                  padding: const EdgeInsets.symmetric(
                                      horizontal: 8, vertical: 4),
                                  decoration: BoxDecoration(
                                    color: _showOnlyOpen
                                        ? AppColors.forest
                                        : AppColors.forest
                                            .withValues(alpha: 0.08),
                                    borderRadius: BorderRadius.circular(14),
                                    border: Border.all(
                                      color: _showOnlyOpen
                                          ? AppColors.forest
                                          : AppColors.forest
                                              .withValues(alpha: 0.28),
                                      width: 1.1,
                                    ),
                                  ),
                                  child: Row(
                                    mainAxisSize: MainAxisSize.min,
                                    children: [
                                      Icon(
                                        _showOnlyOpen
                                            ? Icons.filter_alt
                                            : Icons.filter_alt_outlined,
                                        size: 12,
                                        color: _showOnlyOpen
                                            ? Colors.white
                                            : AppColors.forest,
                                      ),
                                      const SizedBox(width: 4),
                                      Text(
                                        _showOnlyOpen
                                            ? '$openCount open'
                                            : '$openCount open',
                                        style: TextStyle(
                                          fontSize: 11,
                                          fontWeight: FontWeight.w700,
                                          color: _showOnlyOpen
                                              ? Colors.white
                                              : AppColors.forest,
                                        ),
                                      ),
                                    ],
                                  ),
                                ),
                              ),
                            ],
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 12),

                    // 3-Column Slot Grid (or Empty State)
                    if (displayedSlots.isEmpty)
                      Padding(
                        padding: const EdgeInsets.symmetric(
                            horizontal: 20, vertical: 28),
                        child: Center(
                          child: Column(
                            children: [
                              Icon(
                                Icons.event_busy_rounded,
                                size: 36,
                                color: Colors.grey.shade400,
                              ),
                              const SizedBox(height: 8),
                              Text(
                                _showOnlyOpen
                                    ? 'No open tee times in this time window'
                                    : 'No tee times found',
                                textAlign: TextAlign.center,
                                style: TextStyle(
                                  fontSize: 13,
                                  fontWeight: FontWeight.w700,
                                  color: Colors.grey.shade600,
                                ),
                              ),
                              if (_showOnlyOpen) ...[
                                const SizedBox(height: 6),
                                TextButton(
                                  onPressed: () =>
                                      setState(() => _showOnlyOpen = false),
                                  child: const Text(
                                    'Show all slots',
                                    style: TextStyle(
                                      fontSize: 12,
                                      fontWeight: FontWeight.w700,
                                      color: AppColors.forest,
                                    ),
                                  ),
                                ),
                              ],
                            ],
                          ),
                        ),
                      )
                    else
                      Padding(
                        padding: const EdgeInsets.symmetric(horizontal: 20),
                        child: GridView.builder(
                          physics: const NeverScrollableScrollPhysics(),
                          shrinkWrap: true,
                          itemCount: displayedSlots.length,
                          gridDelegate:
                              const SliverGridDelegateWithFixedCrossAxisCount(
                            crossAxisCount: 3,
                            mainAxisSpacing: 10,
                            crossAxisSpacing: 10,
                            childAspectRatio: 1.85,
                          ),
                          itemBuilder: (context, index) {
                            final slot = displayedSlots[index];
                            final isSelected = course.isSlotSelected(slot.min);
                            final displaySlot = isSelected
                                ? slot.copyWith(state: SlotState.selected)
                                : slot;

                            return TimeSlotChip(
                              slot: displaySlot,
                              onTap: () {
                                if (slot.state == SlotState.waitlist) {
                                  setState(() => _waitlistModalSlot = slot);
                                } else if (slot.state == SlotState.available) {
                                  courseNotifier.toggleSlot(slot.min);
                                }
                              },
                            );
                          },
                        ),
                      ),
                  ],
                ),
              ),
            ),
          ],
        ),

        // Sticky Bottom Checkout Bar
        Positioned(
          left: 0,
          right: 0,
          bottom: 0,
          child: Container(
            padding: const EdgeInsets.fromLTRB(20, 10, 20, 12),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: const BorderRadius.vertical(top: Radius.circular(22)),
              border: const Border(
                top: BorderSide(color: AppColors.borderLight, width: 1.0),
              ),
              boxShadow: [
                BoxShadow(
                  color: Colors.black.withValues(alpha: 0.06),
                  blurRadius: 14,
                  offset: const Offset(0, -4),
                ),
              ],
            ),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  crossAxisAlignment: CrossAxisAlignment.center,
                  children: [
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Text(
                            course.selectedSlotsCount == 0
                                ? 'Select a tee time'
                                : course.selectedSlotsCount == 1
                                    ? '${DateFormat('E, MMM d').format(selectedDate)} · ${_formatMinutes(course.selectedMin!)}'
                                    : '${course.selectedSlotsCount} tee times selected',
                            style: TextStyle(
                              fontSize: 13,
                              fontWeight: FontWeight.w800,
                              color: course.selectedSlotsCount > 0
                                  ? AppColors.textMain
                                  : Colors.grey.shade700,
                            ),
                          ),
                          const SizedBox(height: 2),
                          Text(
                            course.selectedSlotsCount == 0
                                ? 'Tap any open slot to select'
                                : course.selectedSlotsCount == 1
                                    ? '${course.holes} holes · ${course.players} players (\$${course.unitPrice.toInt()}/player)'
                                    : '${course.sortedSelectedMins.map((m) => _formatMinutes(m)).join(', ')} · ${course.players} players',
                            style: TextStyle(
                              fontSize: 11,
                              fontWeight: FontWeight.w500,
                              color: Colors.grey.shade500,
                            ),
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(width: 12),
                    Column(
                      crossAxisAlignment: CrossAxisAlignment.end,
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Text(
                          course.selectedSlotsCount > 0
                              ? '\$${course.totalGreensFee.toInt()}'
                              : '—',
                          style: TextStyle(
                            fontSize: 19,
                            fontWeight: FontWeight.w900,
                            color: course.selectedSlotsCount > 0
                                ? AppColors.forest
                                : Colors.grey.shade400,
                            height: 1.1,
                          ),
                        ),
                        if (course.selectedSlotsCount > 0)
                          Text(
                            'total greens fee',
                            style: TextStyle(
                              fontSize: 9,
                              fontWeight: FontWeight.w600,
                              color: Colors.grey.shade400,
                            ),
                          ),
                      ],
                    ),
                  ],
                ),
                const SizedBox(height: 10),
                PrimaryButton(
                  height: 46,
                  text: course.selectedSlotsCount <= 1
                      ? 'Continue to checkout'
                      : 'Continue with ${course.selectedSlotsCount} tee times',
                  isEnabled: course.selectedSlotsCount > 0,
                  onPressed: () => appNotifier.navigateTo(AppScreen.checkout),
                ),
              ],
            ),
          ),
        ),

        // Waitlist Join Modal Bottom Sheet
        if (_waitlistModalSlot != null)
          _buildWaitlistSheet(context, _waitlistModalSlot!),
      ],
    );
  }

  Widget _buildCircleBtn({
    required IconData icon,
    required bool enabled,
    required VoidCallback onTap,
  }) {
    return GestureDetector(
      onTap: enabled ? onTap : null,
      child: Container(
        width: 34,
        height: 34,
        decoration: BoxDecoration(
          shape: BoxShape.circle,
          border: Border.all(
            color: enabled ? AppColors.border : Colors.grey.shade200,
          ),
          color: enabled ? Colors.white : Colors.grey.shade100,
        ),
        child: Icon(
          icon,
          size: 16,
          color: enabled ? AppColors.textMain : Colors.grey.shade300,
        ),
      ),
    );
  }

  Widget _buildLegendDot(Color color, String label, {bool isBorder = false}) {
    return Row(
      children: [
        Container(
          width: 8,
          height: 8,
          decoration: BoxDecoration(
            shape: BoxShape.circle,
            color: isBorder ? Colors.transparent : color,
            border: isBorder ? Border.all(color: color, width: 2) : null,
          ),
        ),
        const SizedBox(width: 5),
        Text(
          label,
          style: const TextStyle(
            fontSize: 11,
            color: AppColors.textMuted,
            fontWeight: FontWeight.w500,
          ),
        ),
      ],
    );
  }

  Widget _buildWaitlistSheet(BuildContext context, TeeSlot slot) {
    final course = ref.read(courseProvider);
    final waitlistNotifier = ref.read(waitlistProvider.notifier);
    final appNotifier = ref.read(appProvider.notifier);

    return Container(
      color: Colors.black.withOpacity(0.45),
      child: Align(
        alignment: Alignment.bottomCenter,
        child: Container(
          padding: const EdgeInsets.fromLTRB(20, 20, 20, 32),
          decoration: const BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.only(
              topLeft: Radius.circular(28),
              topRight: Radius.circular(28),
            ),
          ),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Center(
                child: Container(
                  width: 40,
                  height: 4,
                  decoration: BoxDecoration(
                    color: Colors.grey.shade300,
                    borderRadius: BorderRadius.circular(2),
                  ),
                ),
              ),
              const SizedBox(height: 16),
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  const Text(
                    'Join the waitlist',
                    style: TextStyle(
                      fontSize: 18,
                      fontWeight: FontWeight.w800,
                      color: AppColors.textMain,
                    ),
                  ),
                  GestureDetector(
                    onTap: () => setState(() => _waitlistModalSlot = null),
                    child: Container(
                      width: 32,
                      height: 32,
                      decoration: BoxDecoration(
                        color: Colors.grey.shade100,
                        shape: BoxShape.circle,
                      ),
                      child: const Icon(Icons.close, size: 18),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 16),
              Container(
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  color: AppColors.waitLight,
                  borderRadius: BorderRadius.circular(18),
                  border: Border.all(color: AppColors.wait.withOpacity(0.4)),
                ),
                child: Row(
                  children: [
                    Container(
                      width: 44,
                      height: 44,
                      decoration: const BoxDecoration(
                        color: AppColors.wait,
                        shape: BoxShape.circle,
                      ),
                      child: const Icon(
                        Icons.access_time_rounded,
                        color: Colors.white,
                        size: 22,
                      ),
                    ),
                    const SizedBox(width: 14),
                    Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          '${DateFormat('E, MMM d').format(course.selectedDate)} · ${slot.time}',
                          style: const TextStyle(
                            fontSize: 15,
                            fontWeight: FontWeight.w800,
                            color: AppColors.waitTextDark,
                          ),
                        ),
                        const SizedBox(height: 2),
                        Text(
                          '${course.holes} holes · ${course.players} players · 2 ahead of you',
                          style: const TextStyle(
                            fontSize: 12,
                            fontWeight: FontWeight.w500,
                            color: AppColors.waitText,
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 16),
              Text(
                "We'll send a push notification the moment this time opens up. You'll have 15 minutes to claim it — no charge until you do.",
                style: TextStyle(
                  fontSize: 13,
                  color: Colors.grey.shade600,
                  height: 1.4,
                ),
              ),
              const SizedBox(height: 24),
              PrimaryButton(
                text: 'Notify me',
                backgroundColor: AppColors.wait,
                onPressed: () {
                  waitlistNotifier.joinWaitlist(
                    date: course.selectedDateString,
                    min: slot.min,
                    holes: course.holes,
                    players: course.players,
                  );
                  setState(() => _waitlistModalSlot = null);
                  appNotifier.showToast("You're on the waitlist!");
                },
              ),
            ],
          ),
        ),
      ),
    );
  }
}
