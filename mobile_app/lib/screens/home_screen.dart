import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../constants/colors.dart';
import '../constants/strings.dart';
import '../models/course_condition.dart';
import '../providers/app_provider.dart';
import '../providers/booking_provider.dart';
import '../providers/course_provider.dart';
import '../providers/waitlist_provider.dart';
import '../providers/scorecard_provider.dart';
import '../providers/member_provider.dart';

class HomeScreen extends ConsumerWidget {
  const HomeScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final memberState = ref.watch(memberProvider);
    final isLoggedIn = memberState.isLoggedIn;
    final member = memberState.member;
    final nextBooking = ref.watch(bookingProvider).nextUpcoming;
    final waitlistReady = ref.watch(waitlistProvider).readyEntry;
    final waitlistCount = ref.watch(waitlistProvider).entries.length;
    final condition = ref.watch(courseProvider).condition;
    final scorecard = ref.watch(scorecardProvider);

    // Notify user if live weather or course conditions change
    ref.listen<CourseState>(courseProvider, (prev, next) {
      if (prev != null && prev.condition != next.condition) {
        if (prev.condition.hasWeatherChanged(next.condition)) {
          ScaffoldMessenger.of(context).hideCurrentSnackBar();
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Row(
                children: [
                  const Icon(Icons.wb_sunny_rounded, color: Colors.white, size: 18),
                  const SizedBox(width: 10),
                  Expanded(
                    child: Text(
                      'Weather updated: ${next.condition.weather} ${next.condition.weatherDesc} (${next.condition.wind})',
                      style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 13),
                    ),
                  ),
                ],
              ),
              backgroundColor: AppColors.forest,
              duration: const Duration(seconds: 3),
              behavior: SnackBarBehavior.floating,
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
            ),
          );
        }
      }
    });

    return RefreshIndicator(
      onRefresh: () async {
        await Future.wait([
          ref.read(memberProvider.notifier).loadProfile(),
          ref.read(courseProvider.notifier).loadHomeConditions(),
          ref.read(courseProvider.notifier).loadSlots(),
          ref.read(bookingProvider.notifier).loadBookings(),
          ref.read(waitlistProvider.notifier).loadWaitlist(),
        ]);
      },
      child: SingleChildScrollView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.only(bottom: 24),
        child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // ── Header: Greeting & Profile Avatar ─────────────
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 8),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Row(
                  children: [
                    GestureDetector(
                      onTap: () => ref
                          .read(appProvider.notifier)
                          .navigateTo(isLoggedIn ? AppScreen.profile : AppScreen.login),
                      child: Container(
                        width: 44,
                        height: 44,
                        decoration: BoxDecoration(
                          color: isLoggedIn ? AppColors.sand : AppColors.forest.withOpacity(0.12),
                          shape: BoxShape.circle,
                        ),
                        alignment: Alignment.center,
                        child: isLoggedIn
                            ? Text(
                                _initials(member.name),
                                style: const TextStyle(
                                  fontSize: 16,
                                  fontWeight: FontWeight.w800,
                                  color: AppColors.forest,
                                ),
                              )
                            : const Icon(
                                Icons.person_outline_rounded,
                                size: 22,
                                color: AppColors.forest,
                              ),
                      ),
                    ),
                    const SizedBox(width: 12),
                    Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          isLoggedIn ? 'Good morning,' : 'Welcome to',
                          style: TextStyle(
                            fontSize: 13,
                            color: Colors.grey.shade500,
                            fontWeight: FontWeight.w500,
                          ),
                        ),
                        Text(
                          isLoggedIn
                              ? (member.name.isNotEmpty ? member.name : 'Member')
                              : 'Pine Valley GC',
                          style: const TextStyle(
                            fontSize: 19,
                            fontWeight: FontWeight.w900,
                            color: AppColors.textMain,
                            letterSpacing: -0.3,
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
                if (isLoggedIn)
                  GestureDetector(
                    onTap: () => ref
                        .read(appProvider.notifier)
                        .navigateTo(AppScreen.bookings),
                    child: Stack(
                      children: [
                        Container(
                          width: 42,
                          height: 42,
                          decoration: BoxDecoration(
                            color: Colors.white,
                            shape: BoxShape.circle,
                            border: Border.all(color: AppColors.borderLight),
                          ),
                          child: const Icon(
                            Icons.notifications_outlined,
                            size: 20,
                            color: AppColors.textMain,
                          ),
                        ),
                        if (waitlistReady != null)
                          Positioned(
                            right: 10,
                            top: 10,
                            child: Container(
                              width: 8,
                              height: 8,
                              decoration: const BoxDecoration(
                                color: AppColors.danger,
                                shape: BoxShape.circle,
                              ),
                            ),
                          ),
                      ],
                    ),
                  )
                else
                  GestureDetector(
                    onTap: () => ref
                        .read(appProvider.notifier)
                        .navigateTo(AppScreen.login),
                    child: Container(
                      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
                      decoration: BoxDecoration(
                        color: AppColors.forest,
                        borderRadius: BorderRadius.circular(20),
                        boxShadow: [
                          BoxShadow(
                            color: AppColors.forest.withOpacity(0.22),
                            blurRadius: 8,
                            offset: const Offset(0, 3),
                          ),
                        ],
                      ),
                      child: const Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Icon(Icons.login_rounded, size: 14, color: Colors.white),
                          SizedBox(width: 6),
                          Text(
                            'Sign In',
                            style: TextStyle(
                              fontSize: 12,
                              fontWeight: FontWeight.w800,
                              color: Colors.white,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
              ],
            ),
          ),
          const SizedBox(height: 12),

          // ── Hero Banner: Next Tee Time ─────────────────────
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 20),
            child: Container(
              height: 210,
              decoration: BoxDecoration(
                borderRadius: BorderRadius.circular(28),
                boxShadow: [
                  BoxShadow(
                    color: AppColors.forest.withOpacity(0.22),
                    blurRadius: 20,
                    offset: const Offset(0, 8),
                  ),
                ],
              ),
              child: ClipRRect(
                borderRadius: BorderRadius.circular(28),
                child: Stack(
                  fit: StackFit.expand,
                  children: [
                    Image.network(
                      AppStrings.imgHero,
                      fit: BoxFit.cover,
                      errorBuilder: (_, __, ___) => Container(
                        color: AppColors.forestDark,
                      ),
                    ),
                    Container(
                      decoration: BoxDecoration(
                        gradient: LinearGradient(
                          begin: Alignment.bottomCenter,
                          end: Alignment.topCenter,
                          colors: [
                            const Color(0xFF0C2414).withOpacity(0.95),
                            const Color(0xFF0C2414).withOpacity(0.55),
                            Colors.transparent,
                          ],
                        ),
                      ),
                    ),
                    Padding(
                      padding: const EdgeInsets.all(20),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        mainAxisAlignment: MainAxisAlignment.end,
                        children: [
                          if (nextBooking != null) ...[
                            Container(
                              padding: const EdgeInsets.symmetric(
                                  horizontal: 10, vertical: 4),
                              decoration: BoxDecoration(
                                color: Colors.white.withOpacity(0.22),
                                borderRadius: BorderRadius.circular(20),
                              ),
                              child: Text(
                                'NEXT TEE TIME',
                                style: TextStyle(
                                  fontSize: 10,
                                  fontWeight: FontWeight.w800,
                                  color: Colors.white.withOpacity(0.9),
                                  letterSpacing: 0.8,
                                ),
                              ),
                            ),
                            const SizedBox(height: 6),
                            Text(
                              nextBooking.date,
                              style: TextStyle(
                                fontSize: 13,
                                color: Colors.white.withOpacity(0.8),
                              ),
                            ),
                            Row(
                              mainAxisAlignment: MainAxisAlignment.spaceBetween,
                              crossAxisAlignment: CrossAxisAlignment.end,
                              children: [
                                Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Text(
                                      nextBooking.time,
                                      style: const TextStyle(
                                        fontSize: 32,
                                        fontWeight: FontWeight.w900,
                                        color: Colors.white,
                                        height: 1.0,
                                      ),
                                    ),
                                    const SizedBox(height: 2),
                                    Text(
                                      '${nextBooking.holes} holes · ${nextBooking.players} players · ${nextBooking.tee}',
                                      style: TextStyle(
                                        fontSize: 12,
                                        color: Colors.white.withOpacity(0.8),
                                      ),
                                    ),
                                  ],
                                ),
                                ElevatedButton(
                                  style: ElevatedButton.styleFrom(
                                    backgroundColor: Colors.white,
                                    foregroundColor: AppColors.forest,
                                    shape: RoundedRectangleBorder(
                                      borderRadius: BorderRadius.circular(20),
                                    ),
                                    padding: const EdgeInsets.symmetric(
                                        horizontal: 16, vertical: 10),
                                  ),
                                  onPressed: () => ref
                                      .read(appProvider.notifier)
                                      .navigateTo(
                                          AppScreen.detail, nextBooking.id),
                                  child: const Text(
                                    'View pass',
                                    style: TextStyle(
                                      fontSize: 13,
                                      fontWeight: FontWeight.w800,
                                    ),
                                  ),
                                ),
                              ],
                            ),
                          ] else ...[
                            const Text(
                              'No rounds booked',
                              style: TextStyle(
                                fontSize: 24,
                                fontWeight: FontWeight.w900,
                                color: Colors.white,
                              ),
                            ),
                            const SizedBox(height: 4),
                            Text(
                              'Perfect conditions this week — grab a time.',
                              style: TextStyle(
                                fontSize: 13,
                                color: Colors.white.withOpacity(0.85),
                              ),
                            ),
                            const SizedBox(height: 12),
                            ElevatedButton(
                              style: ElevatedButton.styleFrom(
                                backgroundColor: Colors.white,
                                foregroundColor: AppColors.forest,
                                shape: RoundedRectangleBorder(
                                  borderRadius: BorderRadius.circular(20),
                                ),
                                padding: const EdgeInsets.symmetric(
                                    horizontal: 16, vertical: 10),
                              ),
                              onPressed: () => ref
                                  .read(appProvider.notifier)
                                  .navigateTo(AppScreen.book),
                              child: const Text(
                                'Book a tee time',
                                style: TextStyle(
                                  fontSize: 13,
                                  fontWeight: FontWeight.w800,
                                ),
                              ),
                            ),
                          ],
                        ],
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ),
          const SizedBox(height: 16),

          // ── Waitlist Claim Alert (if open) ─────────────────
          if (waitlistReady != null)
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 20),
              child: GestureDetector(
                onTap: () {
                  ref.read(courseProvider.notifier).selectSlot(waitlistReady.min);
                  ref.read(appProvider.notifier).navigateTo(AppScreen.checkout);
                },
                child: Container(
                  padding: const EdgeInsets.all(16),
                  decoration: BoxDecoration(
                    color: AppColors.waitLight,
                    borderRadius: BorderRadius.circular(20),
                    border: Border.all(color: AppColors.wait.withOpacity(0.5)),
                  ),
                  child: Row(
                    children: [
                      Container(
                        width: 40,
                        height: 40,
                        decoration: const BoxDecoration(
                          color: AppColors.wait,
                          shape: BoxShape.circle,
                        ),
                        child: const Icon(
                          Icons.notifications_active,
                          color: Colors.white,
                          size: 20,
                        ),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const Text(
                              'A waitlist spot opened',
                              style: TextStyle(
                                fontSize: 14,
                                fontWeight: FontWeight.w800,
                                color: AppColors.waitTextDark,
                              ),
                            ),
                            const SizedBox(height: 2),
                            Text(
                              '${waitlistReady.time} · claim within 15 min',
                              style: const TextStyle(
                                fontSize: 12,
                                fontWeight: FontWeight.w600,
                                color: AppColors.waitText,
                              ),
                            ),
                          ],
                        ),
                      ),
                      const Icon(
                        Icons.chevron_right_rounded,
                        color: AppColors.waitText,
                      ),
                    ],
                  ),
                ),
              ),
            ),
          if (waitlistReady != null) const SizedBox(height: 20),

          // ── Section: Course Conditions ────────────────────
          _buildSectionHeader(
            'Course conditions',
            action: condition.updatedAt,
            onActionTap: () => _showConditionsSheet(context, condition, ref),
          ),
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 20),
            child: GestureDetector(
              onTap: () => _showConditionsSheet(context, condition, ref),
              child: Row(
                children: [
                  _buildConditionBox(
                    icon: _getWeatherIcon(condition.weatherDesc),
                    val: condition.weather,
                    label: condition.weatherDesc,
                  ),
                  const SizedBox(width: 8),
                  _buildConditionBox(
                    icon: Icons.air_rounded,
                    val: condition.wind,
                    label: condition.windDesc,
                  ),
                  const SizedBox(width: 8),
                  _buildConditionBox(
                    icon: Icons.speed_rounded,
                    val: condition.greenSpeed,
                    label: condition.speedDesc,
                  ),
                  const SizedBox(width: 8),
                  _buildConditionBox(
                    icon: Icons.golf_course_rounded,
                    val: condition.carts,
                    label: condition.cartsDesc,
                  ),
                ],
              ),
            ),
          ),
          const SizedBox(height: 22),

          // ── Section: Quick Actions ────────────────────────
          _buildSectionHeader('Quick actions'),
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 20),
            child: Row(
              children: [
                _buildActionCard(
                  context: context,
                  icon: Icons.calendar_month_rounded,
                  label: 'Book',
                  onTap: () => ref
                      .read(appProvider.notifier)
                      .navigateTo(AppScreen.book),
                ),
                const SizedBox(width: 10),
                _buildActionCard(
                  context: context,
                  icon: Icons.flag_rounded,
                  label: 'Scorecard',
                  badge: scorecard.playedCount > 0
                      ? 'Hole ${scorecard.playedCount + 1}'
                      : null,
                  onTap: () => ref
                      .read(appProvider.notifier)
                      .navigateTo(AppScreen.score),
                ),
                const SizedBox(width: 10),
                _buildActionCard(
                  context: context,
                  icon: Icons.format_list_bulleted_rounded,
                  label: 'Waitlist',
                  badge: waitlistCount > 0 ? '$waitlistCount' : null,
                  onTap: () => ref
                      .read(appProvider.notifier)
                      .navigateTo(AppScreen.bookings),
                ),
              ],
            ),
          ),
          const SizedBox(height: 22),

          // ── Section: Deals For You ────────────────────────
          _buildSectionHeader('Deals for you'),
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 20),
            child: GestureDetector(
              onTap: () {
                ref.read(courseProvider.notifier).setTimeOfDay('Twilight');
                ref.read(appProvider.notifier).navigateTo(AppScreen.book);
              },
              child: Container(
                height: 120,
                decoration: BoxDecoration(
                  borderRadius: BorderRadius.circular(24),
                  boxShadow: [
                    BoxShadow(
                      color: Colors.black.withOpacity(0.08),
                      blurRadius: 12,
                      offset: const Offset(0, 4),
                    ),
                  ],
                ),
                child: ClipRRect(
                  borderRadius: BorderRadius.circular(24),
                  child: Stack(
                    fit: StackFit.expand,
                    children: [
                      Image.network(
                        AppStrings.imgTwilight,
                        fit: BoxFit.cover,
                      ),
                      Container(
                        decoration: BoxDecoration(
                          gradient: LinearGradient(
                            begin: Alignment.centerLeft,
                            end: Alignment.centerRight,
                            colors: [
                              Colors.black.withOpacity(0.75),
                              Colors.transparent,
                            ],
                          ),
                        ),
                      ),
                      Padding(
                        padding: const EdgeInsets.all(18),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Container(
                              padding: const EdgeInsets.symmetric(
                                  horizontal: 8, vertical: 3),
                              decoration: BoxDecoration(
                                color: AppColors.wait,
                                borderRadius: BorderRadius.circular(20),
                              ),
                              child: const Text(
                                'Save 35%',
                                style: TextStyle(
                                  fontSize: 10,
                                  fontWeight: FontWeight.w800,
                                  color: Colors.white,
                                ),
                              ),
                            ),
                            const SizedBox(height: 6),
                            const Text(
                              'Twilight Special',
                              style: TextStyle(
                                fontSize: 18,
                                fontWeight: FontWeight.w900,
                                color: Colors.white,
                              ),
                            ),
                            Text(
                              'Tee off after 3 PM from \$35',
                              style: TextStyle(
                                fontSize: 12,
                                color: Colors.white.withOpacity(0.85),
                              ),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            ),
          ),
          const SizedBox(height: 22),

          // ── Section: Club News ────────────────────────────
          _buildSectionHeader('Club news'),
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 20),
            child: Container(
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
                  ClipRRect(
                    borderRadius: const BorderRadius.only(
                      topLeft: Radius.circular(20),
                      bottomLeft: Radius.circular(20),
                    ),
                    child: Image.network(
                      AppStrings.imgNews,
                      width: 90,
                      height: 90,
                      fit: BoxFit.cover,
                      errorBuilder: (_, __, ___) => Container(
                        width: 90,
                        height: 90,
                        color: AppColors.border,
                      ),
                    ),
                  ),
                  Expanded(
                    child: Padding(
                      padding: const EdgeInsets.all(12),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Text(
                            'EVENT · NOV 9',
                            style: TextStyle(
                              fontSize: 10,
                              fontWeight: FontWeight.w800,
                              color: AppColors.forest,
                              letterSpacing: 0.5,
                            ),
                          ),
                          const SizedBox(height: 2),
                          const Text(
                            'Autumn Member Classic — registration is open',
                            style: TextStyle(
                              fontSize: 13,
                              fontWeight: FontWeight.w800,
                              color: AppColors.textMain,
                              height: 1.2,
                            ),
                          ),
                          const SizedBox(height: 4),
                          Text(
                            'Shotgun start 8 AM · 72 spots left',
                            style: TextStyle(
                              fontSize: 11,
                              color: Colors.grey.shade500,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    ),
  );
}

  Widget _buildSectionHeader(String title, {String? action, VoidCallback? onActionTap}) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 8),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(
            title,
            style: const TextStyle(
              fontSize: 16,
              fontWeight: FontWeight.w800,
              color: AppColors.textMain,
              letterSpacing: -0.3,
            ),
          ),
          if (action != null)
            GestureDetector(
              onTap: onActionTap,
              child: Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Text(
                    action,
                    style: const TextStyle(
                      fontSize: 12,
                      fontWeight: FontWeight.w700,
                      color: AppColors.forest,
                    ),
                  ),
                  if (onActionTap != null) ...[
                    const SizedBox(width: 3),
                    const Icon(Icons.info_outline_rounded, size: 13, color: AppColors.forest),
                  ],
                ],
              ),
            ),
        ],
      ),
    );
  }

  IconData _getWeatherIcon(String desc) {
    final d = desc.toLowerCase();
    if (d.contains('sun') || d.contains('clear')) return Icons.wb_sunny_rounded;
    if (d.contains('cloud') || d.contains('overcast')) return Icons.cloud_rounded;
    if (d.contains('rain') || d.contains('shower') || d.contains('drizzle')) return Icons.grain_rounded;
    if (d.contains('snow') || d.contains('flurr') || d.contains('ice')) return Icons.ac_unit_rounded;
    if (d.contains('thunder') || d.contains('storm')) return Icons.flash_on_rounded;
    return Icons.wb_sunny_rounded;
  }

  void _showConditionsSheet(
      BuildContext context, CourseCondition condition, WidgetRef ref) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) => Container(
        padding: const EdgeInsets.fromLTRB(20, 16, 20, 32),
        decoration: const BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.vertical(top: Radius.circular(28)),
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
                  color: AppColors.border,
                  borderRadius: BorderRadius.circular(2),
                ),
              ),
            ),
            const SizedBox(height: 18),
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        children: [
                          const Icon(Icons.location_on_rounded,
                              size: 16, color: AppColors.forest),
                          const SizedBox(width: 4),
                          Text(
                            condition.location,
                            style: const TextStyle(
                              fontSize: 16,
                              fontWeight: FontWeight.w800,
                              color: AppColors.textMain,
                            ),
                          ),
                        ],
                      ),
                      if (condition.gpsFormatted.isNotEmpty) ...[
                        const SizedBox(height: 3),
                        Row(
                          children: [
                            const Icon(Icons.gps_fixed_rounded,
                                size: 12, color: Color(0xFF16A34A)),
                            const SizedBox(width: 4),
                            Text(
                              condition.gpsFormatted,
                              style: const TextStyle(
                                fontSize: 11,
                                fontWeight: FontWeight.w700,
                                color: Color(0xFF15803D),
                              ),
                            ),
                          ],
                        ),
                      ],
                      const SizedBox(height: 2),
                      Text(
                        'Live course conditions & weather',
                        style: TextStyle(
                          fontSize: 12,
                          color: Colors.grey.shade500,
                        ),
                      ),
                    ],
                  ),
                ),
                Container(
                  padding:
                      const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                  decoration: BoxDecoration(
                    color: AppColors.forest.withOpacity(0.08),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Container(
                        width: 6,
                        height: 6,
                        decoration: const BoxDecoration(
                          color: Color(0xFF16A34A),
                          shape: BoxShape.circle,
                        ),
                      ),
                      const SizedBox(width: 6),
                      Text(
                        condition.isGps ? 'GPS Feed' : 'Live Feed',
                        style: const TextStyle(
                          fontSize: 11,
                          fontWeight: FontWeight.w700,
                          color: AppColors.forest,
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ),
            const SizedBox(height: 20),

            // Main Conditions 2x2 Cards
            Row(
              children: [
                Expanded(
                  child: _buildDetailCard(
                    icon: _getWeatherIcon(condition.weatherDesc),
                    title: condition.weather,
                    subtitle: condition.weatherDesc,
                    note: 'Humidity: ${condition.humidity}',
                    accentColor: const Color(0xFFD97706),
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: _buildDetailCard(
                    icon: Icons.air_rounded,
                    title: condition.wind,
                    subtitle: condition.windDesc,
                    note: 'Club recommendation: standard',
                    accentColor: const Color(0xFF0284C7),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 12),
            Row(
              children: [
                Expanded(
                  child: _buildDetailCard(
                    icon: Icons.speed_rounded,
                    title: '${condition.greenSpeed} Stimpmeter',
                    subtitle: condition.speedDesc,
                    note: 'Greens rolling true & firm',
                    accentColor: AppColors.forest,
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: _buildDetailCard(
                    icon: Icons.golf_course_rounded,
                    title: condition.carts,
                    subtitle: condition.cartsDesc,
                    note: 'Keep on paths near tees & greens',
                    accentColor: const Color(0xFF475569),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 20),

            // Live GPS & Refresh Buttons
            Row(
              children: [
                Expanded(
                  child: ElevatedButton.icon(
                    style: ElevatedButton.styleFrom(
                      padding: const EdgeInsets.symmetric(vertical: 14),
                      backgroundColor: const Color(0xFF0F3E22),
                      elevation: 0,
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(16),
                      ),
                    ),
                    onPressed: () async {
                      Navigator.pop(ctx);
                      ScaffoldMessenger.of(context).showSnackBar(
                        const SnackBar(
                          content: Row(
                            children: [
                              SizedBox(
                                width: 16,
                                height: 16,
                                child: CircularProgressIndicator(
                                  strokeWidth: 2,
                                  color: Colors.white,
                                ),
                              ),
                              SizedBox(width: 10),
                              Text('Acquiring live GPS location...'),
                            ],
                          ),
                          duration: Duration(seconds: 2),
                        ),
                      );
                      final success = await ref
                          .read(courseProvider.notifier)
                          .refreshWithGps();
                      if (context.mounted) {
                        final newCond = ref.read(courseProvider).condition;
                        ScaffoldMessenger.of(context).showSnackBar(
                          SnackBar(
                            backgroundColor:
                                success ? AppColors.forest : Colors.black87,
                            content: Text(
                              success
                                  ? 'GPS Locked: ${newCond.location} (${newCond.weather}, ${newCond.weatherDesc})'
                                  : 'Updated conditions for ${newCond.location}',
                            ),
                            duration: const Duration(seconds: 3),
                          ),
                        );
                      }
                    },
                    icon: const Icon(Icons.my_location_rounded,
                        size: 18, color: Colors.white),
                    label: const Text(
                      'Detect GPS',
                      style: TextStyle(
                        fontSize: 14,
                        fontWeight: FontWeight.w700,
                        color: Colors.white,
                      ),
                    ),
                  ),
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: OutlinedButton.icon(
                    style: OutlinedButton.styleFrom(
                      padding: const EdgeInsets.symmetric(vertical: 14),
                      side: const BorderSide(color: AppColors.borderLight),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(16),
                      ),
                    ),
                    onPressed: () async {
                      Navigator.pop(ctx);
                      await ref
                          .read(courseProvider.notifier)
                          .refreshWeather();
                    },
                    icon: const Icon(Icons.refresh_rounded,
                        size: 18, color: AppColors.forest),
                    label: const Text(
                      'Refresh Feed',
                      style: TextStyle(
                        fontSize: 14,
                        fontWeight: FontWeight.w700,
                        color: AppColors.forest,
                      ),
                    ),
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildDetailCard({
    required IconData icon,
    required String title,
    required String subtitle,
    required String note,
    required Color accentColor,
  }) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: AppColors.sandLight.withOpacity(0.5),
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: AppColors.borderLight),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                width: 32,
                height: 32,
                decoration: BoxDecoration(
                  color: accentColor.withOpacity(0.12),
                  shape: BoxShape.circle,
                ),
                child: Icon(icon, size: 18, color: accentColor),
              ),
              const Spacer(),
            ],
          ),
          const SizedBox(height: 10),
          Text(
            title,
            style: const TextStyle(
              fontSize: 15,
              fontWeight: FontWeight.w800,
              color: AppColors.textMain,
            ),
          ),
          const SizedBox(height: 2),
          Text(
            subtitle,
            style: TextStyle(
              fontSize: 12,
              fontWeight: FontWeight.w600,
              color: accentColor,
            ),
          ),
          const SizedBox(height: 4),
          Text(
            note,
            style: TextStyle(
              fontSize: 10,
              color: Colors.grey.shade500,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildConditionBox({
    required IconData icon,
    required String val,
    required String label,
  }) {
    return Expanded(
      child: Container(
        padding: const EdgeInsets.symmetric(vertical: 12),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(18),
          border: Border.all(color: AppColors.borderLight),
        ),
        child: Column(
          children: [
            Icon(icon, size: 20, color: AppColors.forest),
            const SizedBox(height: 4),
            Text(
              val,
              style: const TextStyle(
                fontSize: 14,
                fontWeight: FontWeight.w800,
                color: AppColors.textMain,
              ),
            ),
            const SizedBox(height: 2),
            Text(
              label,
              style: const TextStyle(
                fontSize: 10,
                fontWeight: FontWeight.w500,
                color: AppColors.textMuted,
              ),
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildActionCard({
    required BuildContext context,
    required IconData icon,
    required String label,
    required VoidCallback onTap,
    String? badge,
  }) {
    return Expanded(
      child: GestureDetector(
        onTap: onTap,
        child: Container(
          padding: const EdgeInsets.symmetric(vertical: 16),
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(20),
            border: Border.all(color: AppColors.borderLight),
          ),
          child: Stack(
            clipBehavior: Clip.none,
            alignment: Alignment.center,
            children: [
              Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Container(
                    width: 42,
                    height: 42,
                    decoration: BoxDecoration(
                      color: AppColors.forest.withOpacity(0.1),
                      shape: BoxShape.circle,
                    ),
                    child: Icon(icon, color: AppColors.forest, size: 22),
                  ),
                  const SizedBox(height: 8),
                  Text(
                    label,
                    style: const TextStyle(
                      fontSize: 13,
                      fontWeight: FontWeight.w700,
                      color: AppColors.textMain,
                    ),
                  ),
                ],
              ),
              if (badge != null)
                Positioned(
                  top: -8,
                  right: 4,
                  child: Container(
                    padding:
                        const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                    decoration: BoxDecoration(
                      color: AppColors.forest,
                      borderRadius: BorderRadius.circular(10),
                    ),
                    child: Text(
                      badge,
                      style: const TextStyle(
                        fontSize: 9,
                        fontWeight: FontWeight.w800,
                        color: Colors.white,
                      ),
                    ),
                  ),
                ),
            ],
          ),
        ),
      ),
    );
  }

  static String _initials(String name) {
    return name.split(' ').map((e) => e.isNotEmpty ? e[0] : '').take(2).join();
  }
}
