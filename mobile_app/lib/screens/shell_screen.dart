import 'dart:async';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../constants/colors.dart';
import '../providers/app_provider.dart';
import 'book_screen.dart';
import 'bookings_screen.dart';
import 'checkout_screen.dart';
import 'detail_screen.dart';
import 'home_screen.dart';
import 'login_screen.dart';
import 'profile_screen.dart';
import 'scorecard_screen.dart';
import 'success_screen.dart';
import '../providers/member_provider.dart';

class ShellScreen extends ConsumerStatefulWidget {
  const ShellScreen({super.key});

  @override
  ConsumerState<ShellScreen> createState() => _ShellScreenState();
}

class _ShellScreenState extends ConsumerState<ShellScreen> {
  Timer? _toastTimer;

  @override
  void dispose() {
    _toastTimer?.cancel();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final app = ref.watch(appProvider);
    final appNotifier = ref.read(appProvider.notifier);
    final memberState = ref.watch(memberProvider);

    // Auto dismiss toast after 2.4 seconds
    if (app.toastMessage.isNotEmpty && _toastTimer == null) {
      _toastTimer = Timer(const Duration(milliseconds: 2400), () {
        appNotifier.clearToast();
        _toastTimer = null;
      });
    }

    final hideBottomNav = [
      AppScreen.checkout,
      AppScreen.success,
      AppScreen.login,
    ].contains(app.currentScreen) || !memberState.isLoggedIn;

    // Active bottom tab index (mapping checkout/success/detail back to root tab)
    int activeTabIndex = 0;
    switch (app.currentScreen) {
      case AppScreen.home:
        activeTabIndex = 0;
        break;
      case AppScreen.book:
      case AppScreen.checkout:
        activeTabIndex = 1;
        break;
      case AppScreen.bookings:
      case AppScreen.detail:
        activeTabIndex = 2;
        break;
      case AppScreen.score:
        activeTabIndex = 3;
        break;
      case AppScreen.profile:
        activeTabIndex = 4;
        break;
      case AppScreen.success:
      case AppScreen.login:
        activeTabIndex = 0;
        break;
    }

    return Scaffold(
      backgroundColor: AppColors.offwhite,
      body: SafeArea(
        child: Stack(
          children: [
            // Current Screen Content
            _buildCurrentScreen(app.currentScreen),

            // Floating Toast Alert
            if (app.toastMessage.isNotEmpty)
              Positioned(
                top: 14,
                left: 20,
                right: 20,
                child: Center(
                  child: AnimatedOpacity(
                    duration: const Duration(milliseconds: 200),
                    opacity: app.toastMessage.isNotEmpty ? 1.0 : 0.0,
                    child: Container(
                      padding: const EdgeInsets.symmetric(
                          horizontal: 20, vertical: 12),
                      decoration: BoxDecoration(
                        color: AppColors.textMain,
                        borderRadius: BorderRadius.circular(30),
                        boxShadow: [
                          BoxShadow(
                            color: Colors.black.withOpacity(0.18),
                            blurRadius: 16,
                            offset: const Offset(0, 6),
                          ),
                        ],
                      ),
                      child: Text(
                        app.toastMessage,
                        style: const TextStyle(
                          color: Colors.white,
                          fontSize: 13,
                          fontWeight: FontWeight.w700,
                        ),
                      ),
                    ),
                  ),
                ),
              ),
          ],
        ),
      ),
      bottomNavigationBar: hideBottomNav
          ? null
          : Container(
              decoration: const BoxDecoration(
                color: Colors.white,
                border: Border(
                  top: BorderSide(color: AppColors.borderLight, width: 1.2),
                ),
              ),
              child: SafeArea(
                child: Container(
                  height: 64,
                  padding: const EdgeInsets.symmetric(horizontal: 10),
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.spaceAround,
                    children: [
                      _buildNavItem(
                        icon: Icons.home_rounded,
                        label: 'Home',
                        isSelected: activeTabIndex == 0,
                        onTap: () =>
                            appNotifier.navigateTo(AppScreen.home),
                      ),
                      _buildNavItem(
                        icon: Icons.calendar_month_rounded,
                        label: 'Book',
                        isSelected: activeTabIndex == 1,
                        onTap: () =>
                            appNotifier.navigateTo(AppScreen.book),
                      ),
                      _buildNavItem(
                        icon: Icons.format_list_bulleted_rounded,
                        label: 'Bookings',
                        isSelected: activeTabIndex == 2,
                        onTap: () =>
                            appNotifier.navigateTo(AppScreen.bookings),
                      ),
                      _buildNavItem(
                        icon: Icons.flag_rounded,
                        label: 'Scorecard',
                        isSelected: activeTabIndex == 3,
                        onTap: () =>
                            appNotifier.navigateTo(AppScreen.score),
                      ),
                      _buildNavItem(
                        icon: Icons.person_rounded,
                        label: 'Profile',
                        isSelected: activeTabIndex == 4,
                        onTap: () =>
                            appNotifier.navigateTo(AppScreen.profile),
                      ),
                    ],
                  ),
                ),
              ),
            ),
    );
  }

  Widget _buildCurrentScreen(AppScreen screen) {
    final memberState = ref.watch(memberProvider);
    if (!memberState.isLoggedIn || screen == AppScreen.login) {
      return const LoginScreen();
    }

    switch (screen) {
      case AppScreen.home:
        return const HomeScreen();
      case AppScreen.book:
        return const BookScreen();
      case AppScreen.checkout:
        return const CheckoutScreen();
      case AppScreen.success:
        return const SuccessScreen();
      case AppScreen.bookings:
        return const BookingsScreen();
      case AppScreen.detail:
        return const DetailScreen();
      case AppScreen.score:
        return const ScorecardScreen();
      case AppScreen.profile:
        return const ProfileScreen();
      case AppScreen.login:
        return const LoginScreen();
    }
  }

  Widget _buildNavItem({
    required IconData icon,
    required String label,
    required bool isSelected,
    required VoidCallback onTap,
  }) {
    return GestureDetector(
      onTap: onTap,
      behavior: HitTestBehavior.opaque,
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          AnimatedContainer(
            duration: const Duration(milliseconds: 180),
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 4),
            decoration: BoxDecoration(
              color: isSelected
                  ? AppColors.forest.withOpacity(0.12)
                  : Colors.transparent,
              borderRadius: BorderRadius.circular(20),
            ),
            child: Icon(
              icon,
              size: 22,
              color: isSelected ? AppColors.forest : const Color(0xFF9CA3AF),
            ),
          ),
          const SizedBox(height: 2),
          Text(
            label,
            style: TextStyle(
              fontSize: 10,
              fontWeight: FontWeight.w700,
              color: isSelected ? AppColors.forest : const Color(0xFF9CA3AF),
            ),
          ),
        ],
      ),
    );
  }
}
