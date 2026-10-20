import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../constants/colors.dart';
import '../models/booking.dart';
import '../providers/app_provider.dart';
import '../providers/booking_provider.dart';
import '../providers/course_provider.dart';
import '../widgets/primary_button.dart';
import '../widgets/qr_ticket_view.dart';
import '../widgets/top_bar.dart';

class DetailScreen extends ConsumerStatefulWidget {
  const DetailScreen({super.key});

  @override
  ConsumerState<DetailScreen> createState() => _DetailScreenState();
}

class _DetailScreenState extends ConsumerState<DetailScreen> {
  bool _showCancelModal = false;

  @override
  Widget build(BuildContext context) {
    final app = ref.watch(appProvider);
    final appNotifier = ref.read(appProvider.notifier);
    final bookingNotifier = ref.read(bookingProvider.notifier);
    final bookings = ref.watch(bookingProvider).upcoming;
    final past = ref.watch(bookingProvider).past;

    final all = [...bookings, ...past];
    final b = all.firstWhere(
      (x) => x.id == app.detailBookingId,
      orElse: () => all.isNotEmpty
          ? all.first
          : Booking(
              id: 1,
              date: '2026-10-24',
              min: 480,
              time: '8:00 AM',
              holes: 18,
              players: 2,
              total: 170.0,
            ),
    );

    final isUpcoming = b.status == 'upcoming';
    final isFreeCancel = b.min >= 480; // Example: full refund if booking is for morning/standard
    final refundAmount = isFreeCancel ? b.total : (b.total * 0.5).roundToDouble();

    return Stack(
      children: [
        Scaffold(
          backgroundColor: AppColors.offwhite,
          body: SafeArea(
            child: Column(
              children: [
                TopBar(
                  title: 'Tee time pass',
                  onBack: () => appNotifier.goBack(),
                ),
                Expanded(
                  child: SingleChildScrollView(
                    padding: const EdgeInsets.symmetric(
                        horizontal: 20, vertical: 8),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        // Ticket Card with QR
                        QrTicketView(booking: b),
                        const SizedBox(height: 18),

                        // Group & Payment Breakdown
                        Container(
                          decoration: BoxDecoration(
                            color: Colors.white,
                            borderRadius: BorderRadius.circular(22),
                            border: Border.all(color: AppColors.borderLight),
                          ),
                          child: Column(
                            children: [
                              // Group Avatars
                              Padding(
                                padding: const EdgeInsets.all(16),
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    const Text(
                                      'Your group',
                                      style: TextStyle(
                                        fontSize: 12,
                                        fontWeight: FontWeight.w600,
                                        color: AppColors.textMuted,
                                      ),
                                    ),
                                    const SizedBox(height: 10),
                                    Row(
                                      children: [
                                        _buildAvatar('James Carter', isLead: true),
                                        ...b.partners.map((p) => Padding(
                                              padding: const EdgeInsets.only(left: 8),
                                              child: _buildAvatar(p),
                                            )),
                                        ...List.generate(
                                          (b.players - 1 - b.partners.length)
                                              .clamp(0, 4),
                                          (index) => Padding(
                                            padding: const EdgeInsets.only(left: 8),
                                            child: _buildGuestAvatar(),
                                          ),
                                        ),
                                      ],
                                    ),
                                  ],
                                ),
                              ),
                              const Divider(
                                height: 1,
                                color: AppColors.borderLight,
                              ),
                              Padding(
                                padding: const EdgeInsets.all(16),
                                child: Row(
                                  mainAxisAlignment:
                                      MainAxisAlignment.spaceBetween,
                                  children: [
                                    const Text(
                                      'Add-ons',
                                      style: TextStyle(
                                        fontSize: 13,
                                        color: AppColors.textSecondary,
                                      ),
                                    ),
                                    Text(
                                      b.addons.isNotEmpty
                                          ? b.addons.join(', ')
                                          : 'None',
                                      style: const TextStyle(
                                        fontSize: 13,
                                        fontWeight: FontWeight.w700,
                                        color: AppColors.textMain,
                                      ),
                                    ),
                                  ],
                                ),
                              ),
                              const Divider(
                                height: 1,
                                color: AppColors.borderLight,
                              ),
                              Padding(
                                padding: const EdgeInsets.all(16),
                                child: Row(
                                  mainAxisAlignment:
                                      MainAxisAlignment.spaceBetween,
                                  children: [
                                    const Text(
                                      'Paid',
                                      style: TextStyle(
                                        fontSize: 13,
                                        color: AppColors.textSecondary,
                                      ),
                                    ),
                                    Text(
                                      '\$${b.total.toInt()} · Card',
                                      style: const TextStyle(
                                        fontSize: 13,
                                        fontWeight: FontWeight.w700,
                                        color: AppColors.textMain,
                                      ),
                                    ),
                                  ],
                                ),
                              ),
                            ],
                          ),
                        ),
                        const SizedBox(height: 16),

                        if (isUpcoming) ...[
                          // Action Buttons
                          Row(
                            children: [
                              Expanded(
                                child: _buildActionButton(
                                  icon: Icons.calendar_today_outlined,
                                  label: 'Calendar',
                                  onTap: () => appNotifier
                                      .showToast('Added to your calendar!'),
                                ),
                              ),
                              const SizedBox(width: 10),
                              Expanded(
                                child: _buildActionButton(
                                  icon: Icons.place_outlined,
                                  label: 'Directions',
                                  onTap: () => appNotifier
                                      .showToast('Opening navigation…'),
                                ),
                              ),
                            ],
                          ),
                          const SizedBox(height: 14),

                          // Cancel Button
                          Center(
                            child: TextButton(
                              onPressed: () =>
                                  setState(() => _showCancelModal = true),
                              child: const Text(
                                'Cancel booking',
                                style: TextStyle(
                                  fontSize: 14,
                                  fontWeight: FontWeight.w700,
                                  color: AppColors.danger,
                                ),
                              ),
                            ),
                          ),
                        ],
                        const SizedBox(height: 20),
                      ],
                    ),
                  ),
                ),
              ],
            ),
          ),
        ),

        // Cancel Confirmation Modal
        if (_showCancelModal)
          _buildCancelSheet(context, b, refundAmount, isFreeCancel,
              bookingNotifier, appNotifier, ref),
      ],
    );
  }

  Widget _buildAvatar(String name, {bool isLead = false}) {
    final initials =
        name.split(' ').map((e) => e.isNotEmpty ? e[0] : '').take(2).join();

    return Container(
      width: 38,
      height: 38,
      decoration: BoxDecoration(
        color: isLead ? AppColors.sand : const Color(0xFFF3F4F6),
        shape: BoxShape.circle,
        border: Border.all(
          color: isLead ? AppColors.forest : Colors.white,
          width: 1.5,
        ),
      ),
      alignment: Alignment.center,
      child: Text(
        initials,
        style: TextStyle(
          fontSize: 12,
          fontWeight: FontWeight.w800,
          color: isLead ? AppColors.forest : AppColors.textSecondary,
        ),
      ),
    );
  }

  Widget _buildGuestAvatar() {
    return Container(
      width: 38,
      height: 38,
      decoration: BoxDecoration(
        color: const Color(0xFFF3F4F6),
        shape: BoxShape.circle,
        border: Border.all(color: Colors.white, width: 1.5),
      ),
      alignment: Alignment.center,
      child: const Text(
        'G',
        style: TextStyle(
          fontSize: 12,
          fontWeight: FontWeight.w700,
          color: AppColors.textMuted,
        ),
      ),
    );
  }

  Widget _buildActionButton({
    required IconData icon,
    required String label,
    required VoidCallback onTap,
  }) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        height: 48,
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(16),
          border: Border.all(color: AppColors.border),
        ),
        child: Row(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(icon, size: 18, color: AppColors.textMain),
            const SizedBox(width: 8),
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
      ),
    );
  }

  Widget _buildCancelSheet(
    BuildContext context,
    Booking b,
    double refundAmount,
    bool isFreeCancel,
    BookingNotifier bookingNotifier,
    AppNotifier appNotifier,
    WidgetRef ref,
  ) {
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
              const Text(
                'Cancel this booking?',
                style: TextStyle(
                  fontSize: 19,
                  fontWeight: FontWeight.w900,
                  color: AppColors.textMain,
                ),
              ),
              const SizedBox(height: 4),
              Text(
                '${b.date} at ${b.time} · ${b.players} players',
                style: TextStyle(
                  fontSize: 13,
                  color: Colors.grey.shade500,
                ),
              ),
              const SizedBox(height: 16),
              Container(
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  color: isFreeCancel ? AppColors.forestLight : AppColors.waitLight,
                  borderRadius: BorderRadius.circular(18),
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      isFreeCancel ? 'Free cancellation' : 'Late cancellation',
                      style: TextStyle(
                        fontSize: 14,
                        fontWeight: FontWeight.w800,
                        color: isFreeCancel ? AppColors.forest : AppColors.waitTextDark,
                      ),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      isFreeCancel
                          ? "You'll be refunded the full \$${b.total.toInt()}. Partners will be notified."
                          : "Within 24 hours — you'll be refunded \$${refundAmount.toInt()} (50%).",
                      style: TextStyle(
                        fontSize: 12,
                        color: Colors.grey.shade700,
                        height: 1.3,
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 20),
              PrimaryButton(
                text: 'Yes, cancel booking',
                backgroundColor: AppColors.danger,
                onPressed: () async {
                  await bookingNotifier.cancelBooking(b.id);
                  ref.read(courseProvider.notifier).loadSlots();
                  ref.read(courseProvider.notifier).loadHomeConditions();
                  setState(() => _showCancelModal = false);
                  appNotifier.showToast('Cancelled · \$${refundAmount.toInt()} refunded');
                  appNotifier.navigateTo(AppScreen.bookings);
                },
              ),
              const SizedBox(height: 10),
              Center(
                child: TextButton(
                  onPressed: () => setState(() => _showCancelModal = false),
                  child: Text(
                    'Keep my tee time',
                    style: TextStyle(
                      fontSize: 13,
                      fontWeight: FontWeight.w700,
                      color: Colors.grey.shade600,
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
}
