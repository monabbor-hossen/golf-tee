import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import '../constants/colors.dart';
import '../constants/strings.dart';
import '../models/booking.dart';
import '../providers/app_provider.dart';
import '../providers/booking_provider.dart';
import '../providers/course_provider.dart';
import '../providers/member_provider.dart';
import '../widgets/primary_button.dart';
import '../widgets/top_bar.dart';

class CheckoutScreen extends ConsumerWidget {
  const CheckoutScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final course = ref.watch(courseProvider);
    final booking = ref.watch(bookingProvider);
    final bookingNotifier = ref.read(bookingProvider.notifier);
    final member = ref.watch(memberProvider).member;
    final cards = ref.watch(memberProvider).cards;
    final appNotifier = ref.read(appProvider.notifier);

    final selectedMins = course.sortedSelectedMins.isNotEmpty
        ? course.sortedSelectedMins
        : [course.selectedMin ?? 480];
    final selectedSlotsCount = selectedMins.length;
    final greenFees = course.totalGreensFee > 0
        ? course.totalGreensFee
        : (course.unitPrice * course.players);

    // Addons cost
    double addOnTotal = 0.0;
    for (final addon in AppStrings.addons) {
      if (booking.selectedAddons.contains(addon['id'])) {
        final price = addon['price'] as double;
        final perPlayer = addon['perPlayer'] as bool;
        addOnTotal += price * (perPlayer ? course.players : 1) * selectedSlotsCount;
      }
    }

    // Gold member: 10% off green fees
    final memberDiscount = (greenFees * 0.1).roundToDouble();

    // Promo code: 10% off total
    final promoDiscount = booking.promoApplied
        ? ((greenFees + addOnTotal - memberDiscount) * 0.1).roundToDouble()
        : 0.0;

    final grandTotal = greenFees + addOnTotal - memberDiscount - promoDiscount;

    final dateFormatted =
        DateFormat('EEEE, MMMM d').format(course.selectedDate);
    final timeStr = selectedMins.map((m) => _formatMinutes(m)).join(' & ');
    final isWeekend = course.selectedDate.weekday >= 6;
    final rateType = selectedMins.any((m) => m >= 900)
        ? 'Twilight'
        : (isWeekend ? 'Weekend' : 'Weekday');

    return Scaffold(
      backgroundColor: AppColors.offwhite,
      body: SafeArea(
        child: Column(
          children: [
            TopBar(
              title: 'Checkout',
              onBack: () => appNotifier.goBack(),
            ),
            Expanded(
              child: SingleChildScrollView(
                padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 8),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    // Summary Green Banner
                    Container(
                      width: double.infinity,
                      padding: const EdgeInsets.all(20),
                      decoration: BoxDecoration(
                        color: AppColors.forest,
                        borderRadius: BorderRadius.circular(24),
                        boxShadow: [
                          BoxShadow(
                            color: AppColors.forest.withOpacity(0.25),
                            blurRadius: 16,
                            offset: const Offset(0, 6),
                          ),
                        ],
                      ),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            AppStrings.courseName.toUpperCase(),
                            style: TextStyle(
                              fontSize: 11,
                              fontWeight: FontWeight.w700,
                              color: Colors.white.withOpacity(0.7),
                              letterSpacing: 1.0,
                            ),
                          ),
                          const SizedBox(height: 6),
                          Text(
                            timeStr,
                            style: const TextStyle(
                              fontSize: 28,
                              fontWeight: FontWeight.w900,
                              color: Colors.white,
                              height: 1.1,
                            ),
                          ),
                          const SizedBox(height: 2),
                          Text(
                            dateFormatted,
                            style: TextStyle(
                              fontSize: 13,
                              fontWeight: FontWeight.w500,
                              color: Colors.white.withOpacity(0.85),
                            ),
                          ),
                          const SizedBox(height: 12),
                            Wrap(
                              spacing: 6,
                              runSpacing: 6,
                              children: [
                                _buildPill('${course.holes} holes'),
                                _buildPill('${course.players} players'),
                                if (selectedSlotsCount > 1)
                                  _buildPill('$selectedSlotsCount tee times'),
                                _buildPill(rateType),
                              ],
                            ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 20),

                    // Playing Partners
                    const Text(
                      'Playing partners',
                      style: TextStyle(
                        fontSize: 15,
                        fontWeight: FontWeight.w800,
                        color: AppColors.textMain,
                      ),
                    ),
                    const SizedBox(height: 2),
                    Text(
                      course.players > 1
                          ? "Invite up to ${course.players - 1} — they'll get the pass by text."
                          : 'Solo round — add players on the previous screen.',
                      style: TextStyle(
                        fontSize: 12,
                        color: Colors.grey.shade500,
                      ),
                    ),
                    const SizedBox(height: 12),
                    SingleChildScrollView(
                      scrollDirection: Axis.horizontal,
                      child: Row(
                        children: [
                          // Lead Golfer (You)
                          _buildPartnerAvatar(
                            label: 'You',
                            initials: _initials(member.name),
                            isYou: true,
                            isSelected: true,
                            onTap: null,
                          ),
                          const SizedBox(width: 14),
                          // Friends
                          ...AppStrings.friends.map((friend) {
                            final isSel =
                                booking.selectedPartners.contains(friend);
                            final maxReached = !isSel &&
                                booking.selectedPartners.length >=
                                    course.players - 1;

                            return Padding(
                              padding: const EdgeInsets.only(right: 14),
                              child: Opacity(
                                opacity: maxReached ? 0.35 : 1.0,
                                child: _buildPartnerAvatar(
                                  label: friend.split(' ')[0],
                                  initials: _initials(friend),
                                  isYou: false,
                                  isSelected: isSel,
                                  onTap: maxReached
                                      ? null
                                      : () => bookingNotifier.togglePartner(
                                          friend, course.players - 1),
                                ),
                              ),
                            );
                          }),
                        ],
                      ),
                    ),
                    const SizedBox(height: 22),

                    // Add-ons
                    const Text(
                      'Add-ons',
                      style: TextStyle(
                        fontSize: 15,
                        fontWeight: FontWeight.w800,
                        color: AppColors.textMain,
                      ),
                    ),
                    const SizedBox(height: 10),
                    Container(
                      decoration: BoxDecoration(
                        color: Colors.white,
                        borderRadius: BorderRadius.circular(20),
                        border: Border.all(color: AppColors.borderLight),
                      ),
                      child: Column(
                        children: AppStrings.addons.map((a) {
                          final isSel =
                              booking.selectedAddons.contains(a['id']);
                          final price = a['price'] as double;
                          final perPlayer = a['perPlayer'] as bool;
                          final totalCost =
                              price * (perPlayer ? course.players : 1);

                          return InkWell(
                            onTap: () => bookingNotifier.toggleAddon(a['id']),
                            child: Padding(
                              padding: const EdgeInsets.symmetric(
                                  horizontal: 16, vertical: 14),
                              child: Row(
                                children: [
                                  Container(
                                    width: 22,
                                    height: 22,
                                    decoration: BoxDecoration(
                                      color: isSel
                                          ? AppColors.forest
                                          : Colors.transparent,
                                      borderRadius: BorderRadius.circular(6),
                                      border: Border.all(
                                        color: isSel
                                            ? AppColors.forest
                                            : Colors.grey.shade300,
                                        width: 1.8,
                                      ),
                                    ),
                                    child: isSel
                                        ? const Icon(
                                            Icons.check,
                                            size: 15,
                                            color: Colors.white,
                                          )
                                        : null,
                                  ),
                                  const SizedBox(width: 12),
                                  Expanded(
                                    child: Column(
                                      crossAxisAlignment:
                                          CrossAxisAlignment.start,
                                      children: [
                                        Text(
                                          a['label'],
                                          style: const TextStyle(
                                            fontSize: 14,
                                            fontWeight: FontWeight.w700,
                                            color: AppColors.textMain,
                                          ),
                                        ),
                                        Text(
                                          a['desc'],
                                          style: TextStyle(
                                            fontSize: 11,
                                            color: Colors.grey.shade400,
                                          ),
                                        ),
                                      ],
                                    ),
                                  ),
                                  Text(
                                    '\$${totalCost.toInt()}',
                                    style: const TextStyle(
                                      fontSize: 14,
                                      fontWeight: FontWeight.w800,
                                      color: AppColors.textMain,
                                    ),
                                  ),
                                ],
                              ),
                            ),
                          );
                        }).toList(),
                      ),
                    ),
                    const SizedBox(height: 22),

                    // Payment Method
                    const Text(
                      'Payment',
                      style: TextStyle(
                        fontSize: 15,
                        fontWeight: FontWeight.w800,
                        color: AppColors.textMain,
                      ),
                    ),
                    const SizedBox(height: 10),
                    Column(
                      children: [
                        if (cards.isEmpty)
                          Padding(
                            padding: const EdgeInsets.only(bottom: 8),
                            child: Container(
                              padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                              decoration: BoxDecoration(
                                color: Colors.white,
                                borderRadius: BorderRadius.circular(16),
                                border: Border.all(color: AppColors.borderLight),
                              ),
                              child: Row(
                                children: [
                                  Icon(Icons.credit_card_outlined, size: 20, color: Colors.grey.shade400),
                                  const SizedBox(width: 10),
                                  Expanded(
                                    child: Text(
                                      'No saved cards. Add a card below or pay with Apple Pay.',
                                      style: TextStyle(fontSize: 12, color: Colors.grey.shade600),
                                    ),
                                  ),
                                ],
                              ),
                            ),
                          )
                        else
                          ...cards.map((c) {
                            final isSel = booking.selectedCardId == c.id;
                            return Padding(
                              padding: const EdgeInsets.only(bottom: 8),
                              child: _buildPaymentRow(
                                id: c.id,
                                title: '${c.brand} •••• ${c.last4}',
                                subtitle: 'Expires ${c.exp}',
                                isSelected: isSel,
                                onTap: () => bookingNotifier.setCard(c.id),
                              ),
                            );
                          }),
                        _buildPaymentRow(
                          id: 'apple',
                          title: 'Apple Pay',
                          subtitle: 'Pay with Face ID',
                          isSelected: booking.selectedCardId == 'apple',
                          onTap: () => bookingNotifier.setCard('apple'),
                        ),
                        const SizedBox(height: 6),
                        InkWell(
                          onTap: () => _showAddCardModal(context, ref),
                          borderRadius: BorderRadius.circular(16),
                          child: Container(
                            width: double.infinity,
                            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                            decoration: BoxDecoration(
                              color: Colors.white,
                              borderRadius: BorderRadius.circular(16),
                              border: Border.all(color: AppColors.borderLight),
                            ),
                            child: const Row(
                              mainAxisAlignment: MainAxisAlignment.center,
                              children: [
                                Icon(Icons.add_circle_outline_rounded, size: 18, color: AppColors.forest),
                                SizedBox(width: 8),
                                Text(
                                  'Add new payment card',
                                  style: TextStyle(
                                    fontSize: 13,
                                    fontWeight: FontWeight.w700,
                                    color: AppColors.forest,
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 18),

                    // Promo Code Box
                    Row(
                      children: [
                        Expanded(
                          child: Container(
                            height: 48,
                            padding: const EdgeInsets.symmetric(horizontal: 14),
                            decoration: BoxDecoration(
                              color: Colors.white,
                              borderRadius: BorderRadius.circular(14),
                              border: Border.all(
                                color: booking.promoError
                                    ? AppColors.danger
                                    : AppColors.border,
                              ),
                            ),
                            child: Row(
                              children: [
                                const Icon(
                                  Icons.local_offer_outlined,
                                  size: 18,
                                  color: AppColors.textMuted,
                                ),
                                const SizedBox(width: 8),
                                Expanded(
                                  child: TextField(
                                    enabled: !booking.promoApplied,
                                    onChanged: (v) =>
                                        bookingNotifier.setPromoCode(v),
                                    decoration: const InputDecoration(
                                      hintText: 'Promo code (try BIRDIE10)',
                                      hintStyle: TextStyle(
                                        fontSize: 13,
                                        color: AppColors.textMuted,
                                      ),
                                      border: InputBorder.none,
                                      isDense: true,
                                    ),
                                    style: const TextStyle(
                                      fontSize: 13,
                                      fontWeight: FontWeight.w700,
                                    ),
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ),
                        const SizedBox(width: 8),
                        SizedBox(
                          height: 48,
                          child: ElevatedButton(
                            style: ElevatedButton.styleFrom(
                              backgroundColor: AppColors.textMain,
                              foregroundColor: Colors.white,
                              shape: RoundedRectangleBorder(
                                borderRadius: BorderRadius.circular(14),
                              ),
                            ),
                            onPressed: () => bookingNotifier.applyPromo(),
                            child: Text(
                              booking.promoApplied ? 'Remove' : 'Apply',
                              style: const TextStyle(
                                fontSize: 13,
                                fontWeight: FontWeight.w700,
                              ),
                            ),
                          ),
                        ),
                      ],
                    ),
                    if (booking.promoError)
                      const Padding(
                        padding: EdgeInsets.only(top: 6),
                        child: Text(
                          "That code isn't valid.",
                          style: TextStyle(
                            fontSize: 12,
                            fontWeight: FontWeight.w700,
                            color: AppColors.danger,
                          ),
                        ),
                      ),
                    if (booking.promoApplied)
                      const Padding(
                        padding: EdgeInsets.only(top: 6),
                        child: Text(
                          'BIRDIE10 applied — 10% off',
                          style: TextStyle(
                            fontSize: 12,
                            fontWeight: FontWeight.w700,
                            color: AppColors.forest,
                          ),
                        ),
                      ),
                    const SizedBox(height: 18),

                    // Price Breakdown Card
                    Container(
                      padding: const EdgeInsets.all(16),
                      decoration: BoxDecoration(
                        color: Colors.white,
                        borderRadius: BorderRadius.circular(20),
                        border: Border.all(color: AppColors.borderLight),
                      ),
                      child: Column(
                        children: [
                          _buildCostRow(
                            selectedSlotsCount > 1
                                ? 'Green fees ($selectedSlotsCount times × ${course.players} players)'
                                : 'Green fees (${course.players} × \$${course.unitPrice.toInt()})',
                            '\$${greenFees.toInt()}',
                          ),
                          if (addOnTotal > 0) ...[
                            const SizedBox(height: 8),
                            _buildCostRow(
                                'Add-ons', '\$${addOnTotal.toInt()}'),
                          ],
                          const SizedBox(height: 8),
                          _buildCostRow(
                            '${member.tier} member discount',
                            '−\$${memberDiscount.toInt()}',
                            isDiscount: true,
                          ),
                          if (booking.promoApplied) ...[
                            const SizedBox(height: 8),
                            _buildCostRow(
                              'Promo BIRDIE10',
                              '−\$${promoDiscount.toInt()}',
                              isDiscount: true,
                            ),
                          ],
                          const Divider(height: 22, color: AppColors.borderLight),
                          Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              const Text(
                                'Total',
                                style: TextStyle(
                                  fontSize: 16,
                                  fontWeight: FontWeight.w900,
                                  color: AppColors.textMain,
                                ),
                              ),
                              Text(
                                '\$${grandTotal.toInt()}',
                                style: const TextStyle(
                                  fontSize: 19,
                                  fontWeight: FontWeight.w900,
                                  color: AppColors.textMain,
                                ),
                              ),
                            ],
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 12),
                    Center(
                      child: Text(
                        'Free cancellation up to 24 hours before your tee time.',
                        style: TextStyle(
                          fontSize: 11,
                          color: Colors.grey.shade400,
                        ),
                      ),
                    ),
                    const SizedBox(height: 16),

                    // Pay Button
                    PrimaryButton(
                      text: selectedSlotsCount > 1
                          ? 'Pay \$${grandTotal.toInt()} ($selectedSlotsCount tee times)'
                          : 'Pay \$${grandTotal.toInt()}',
                      isLoading: booking.isBookingLoading,
                      onPressed: () async {
                        if (!ref.read(memberProvider).isLoggedIn) {
                          appNotifier.showToast('Please sign in to complete your booking');
                          appNotifier.navigateTo(AppScreen.login);
                          return;
                        }
                        Booking? lastBooking;
                        final perSlotTotal = grandTotal / selectedSlotsCount;
                        for (final min in selectedMins) {
                          lastBooking = await bookingNotifier.confirmBooking(
                            date: course.selectedDateString,
                            min: min,
                            holes: course.holes,
                            players: course.players,
                            total: perSlotTotal,
                          );
                        }
                        if (lastBooking != null) {
                          ref.read(courseProvider.notifier).clearSelectedSlots();
                          ref.read(courseProvider.notifier).loadSlots();
                          ref.read(bookingProvider.notifier).loadBookings();
                          ref.read(courseProvider.notifier).loadHomeConditions();
                          appNotifier.navigateTo(AppScreen.success, lastBooking.id);
                        }
                      },
                    ),
                    const SizedBox(height: 20),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildPill(String label) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
      decoration: BoxDecoration(
        color: Colors.white.withOpacity(0.18),
        borderRadius: BorderRadius.circular(16),
      ),
      child: Text(
        label,
        style: const TextStyle(
          fontSize: 11,
          fontWeight: FontWeight.w700,
          color: Colors.white,
        ),
      ),
    );
  }

  Widget _buildPartnerAvatar({
    required String label,
    required String initials,
    required bool isYou,
    required bool isSelected,
    required VoidCallback? onTap,
  }) {
    return GestureDetector(
      onTap: onTap,
      child: Column(
        children: [
          Stack(
            clipBehavior: Clip.none,
            children: [
              Container(
                width: 48,
                height: 48,
                decoration: BoxDecoration(
                  shape: BoxShape.circle,
                  color: isYou
                      ? AppColors.forest
                      : (isSelected ? AppColors.sand : const Color(0xFFF3F4F6)),
                  border: Border.all(
                    color: isSelected ? AppColors.forest : Colors.transparent,
                    width: 2.2,
                  ),
                ),
                alignment: Alignment.center,
                child: Text(
                  initials,
                  style: TextStyle(
                    fontSize: 14,
                    fontWeight: FontWeight.w800,
                    color: isYou
                        ? Colors.white
                        : (isSelected ? AppColors.forest : Colors.grey.shade500),
                  ),
                ),
              ),
              if (isSelected && !isYou)
                Positioned(
                  right: -2,
                  bottom: -2,
                  child: Container(
                    width: 18,
                    height: 18,
                    decoration: const BoxDecoration(
                      color: AppColors.forest,
                      shape: BoxShape.circle,
                    ),
                    child: const Icon(Icons.check, size: 12, color: Colors.white),
                  ),
                ),
            ],
          ),
          const SizedBox(height: 4),
          Text(
            label,
            style: const TextStyle(
              fontSize: 11,
              fontWeight: FontWeight.w600,
              color: AppColors.textSecondary,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildPaymentRow({
    required String id,
    required String title,
    required String subtitle,
    required bool isSelected,
    required VoidCallback onTap,
  }) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(18),
          border: Border.all(
            color: isSelected ? AppColors.forest : AppColors.borderLight,
            width: isSelected ? 1.8 : 1.0,
          ),
        ),
        child: Row(
          children: [
            Container(
              width: 38,
              height: 26,
              decoration: BoxDecoration(
                borderRadius: BorderRadius.circular(6),
                color: id == 'apple' ? Colors.black : const Color(0xFF1E293B),
              ),
              child: Icon(
                id == 'apple' ? Icons.apple : Icons.credit_card,
                size: 16,
                color: Colors.white,
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    title,
                    style: const TextStyle(
                      fontSize: 13,
                      fontWeight: FontWeight.w700,
                      color: AppColors.textMain,
                    ),
                  ),
                  Text(
                    subtitle,
                    style: TextStyle(
                      fontSize: 11,
                      color: Colors.grey.shade400,
                    ),
                  ),
                ],
              ),
            ),
            Container(
              width: 18,
              height: 18,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                border: Border.all(
                  color: isSelected ? AppColors.forest : Colors.grey.shade300,
                  width: isSelected ? 5.5 : 1.5,
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildCostRow(String label, String amount, {bool isDiscount = false}) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Text(
          label,
          style: TextStyle(
            fontSize: 13,
            color: isDiscount ? AppColors.forest : Colors.grey.shade600,
            fontWeight: isDiscount ? FontWeight.w700 : FontWeight.w500,
          ),
        ),
        Text(
          amount,
          style: TextStyle(
            fontSize: 14,
            fontWeight: FontWeight.w700,
            color: isDiscount ? AppColors.forest : AppColors.textMain,
          ),
        ),
      ],
    );
  }

  static String _formatMinutes(int minutes) {
    final h = minutes ~/ 60;
    final m = minutes % 60;
    final period = h < 12 ? 'AM' : 'PM';
    final h12 = ((h + 11) % 12) + 1;
    return '$h12:${m.toString().padLeft(2, '0')} $period';
  }

  static String _initials(String name) {
    return name.split(' ').map((e) => e.isNotEmpty ? e[0] : '').take(2).join();
  }

  void _showAddCardModal(BuildContext context, WidgetRef ref) {
    final numCtrl = TextEditingController();
    final expCtrl = TextEditingController();
    final appNotifier = ref.read(appProvider.notifier);
    final memberNotifier = ref.read(memberProvider.notifier);
    final bookingNotifier = ref.read(bookingProvider.notifier);

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) => Container(
        padding: EdgeInsets.fromLTRB(20, 20, 20, MediaQuery.of(ctx).viewInsets.bottom + 24),
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
                  color: Colors.grey.shade300,
                  borderRadius: BorderRadius.circular(2),
                ),
              ),
            ),
            const SizedBox(height: 16),
            const Text(
              'Add Payment Card',
              style: TextStyle(fontSize: 18, fontWeight: FontWeight.w800, color: AppColors.textMain),
            ),
            const SizedBox(height: 14),
            const Text('Card Number', style: TextStyle(fontSize: 12, fontWeight: FontWeight.w700, color: AppColors.textSecondary)),
            const SizedBox(height: 6),
            TextField(
              controller: numCtrl,
              keyboardType: TextInputType.number,
              decoration: InputDecoration(
                hintText: '1234 5678 9012 3456',
                filled: true,
                fillColor: const Color(0xFFF9FAFB),
                border: OutlineInputBorder(borderRadius: BorderRadius.circular(14), borderSide: const BorderSide(color: AppColors.border)),
              ),
            ),
            const SizedBox(height: 12),
            const Text('Expiry (MM/YY)', style: TextStyle(fontSize: 12, fontWeight: FontWeight.w700, color: AppColors.textSecondary)),
            const SizedBox(height: 6),
            TextField(
              controller: expCtrl,
              keyboardType: TextInputType.datetime,
              decoration: InputDecoration(
                hintText: 'MM/YY',
                filled: true,
                fillColor: const Color(0xFFF9FAFB),
                border: OutlineInputBorder(borderRadius: BorderRadius.circular(14), borderSide: const BorderSide(color: AppColors.border)),
              ),
            ),
            const SizedBox(height: 20),
            PrimaryButton(
              text: 'Save and Use This Card',
              onPressed: () async {
                final rawNum = numCtrl.text.replaceAll(RegExp(r'\D'), '');
                if (rawNum.length < 13) {
                  appNotifier.showToast('Please enter a valid card number');
                  return;
                }
                final exp = expCtrl.text.trim().isNotEmpty ? expCtrl.text.trim() : '12/28';
                final card = await memberNotifier.addCard(
                  cardNumber: rawNum,
                  exp: exp,
                  isDefault: true,
                );
                if (card != null) {
                  bookingNotifier.setCard(card.id);
                  if (ctx.mounted) {
                    Navigator.pop(ctx);
                  }
                  appNotifier.showToast('Card saved and selected!');
                } else {
                  appNotifier.showToast('Could not save card. Please check details.');
                }
              },
            ),
          ],
        ),
      ),
    );
  }
}
