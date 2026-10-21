import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../constants/colors.dart';
import '../models/member.dart';
import '../models/payment_card.dart';
import '../providers/app_provider.dart';
import '../providers/member_provider.dart';
import '../widgets/handicap_chart.dart';
import '../widgets/primary_button.dart';
import '../widgets/top_bar.dart';

class ProfileScreen extends ConsumerStatefulWidget {
  const ProfileScreen({super.key});

  @override
  ConsumerState<ProfileScreen> createState() => _ProfileScreenState();
}

class _ProfileScreenState extends ConsumerState<ProfileScreen> {
  bool _showAddCard = false;
  final _numCtrl = TextEditingController();
  final _expCtrl = TextEditingController();

  @override
  void dispose() {
    _numCtrl.dispose();
    _expCtrl.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final memberState = ref.watch(memberProvider);
    final notifier = ref.read(memberProvider.notifier);
    final appNotifier = ref.read(appProvider.notifier);

    final m = memberState.member;
    final cards = memberState.cards;
    final hcpHist = memberState.hcpHistory;
    final notifs = memberState.notifPrefs;

    return Stack(
      children: [
        SingleChildScrollView(
          padding: const EdgeInsets.only(bottom: 28),
          child: Column(
            children: [
              const TopBar(title: 'Profile'),
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 20),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    // Member Card (Forest Green)
                    Container(
                      padding: const EdgeInsets.all(22),
                      decoration: BoxDecoration(
                        color: AppColors.forest,
                        borderRadius: BorderRadius.circular(28),
                        boxShadow: [
                          BoxShadow(
                            color: AppColors.forest.withOpacity(0.25),
                            blurRadius: 18,
                            offset: const Offset(0, 6),
                          ),
                        ],
                      ),
                      child: Column(
                        children: [
                          Row(
                            children: [
                              Container(
                                width: 52,
                                height: 52,
                                decoration: const BoxDecoration(
                                  color: AppColors.sand,
                                  shape: BoxShape.circle,
                                ),
                                alignment: Alignment.center,
                                child: Text(
                                  _initials(m.name),
                                  style: const TextStyle(
                                    fontSize: 18,
                                    fontWeight: FontWeight.w800,
                                    color: AppColors.forest,
                                  ),
                                ),
                              ),
                              const SizedBox(width: 14),
                              Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text(
                                    m.name,
                                    style: const TextStyle(
                                      fontSize: 18,
                                      fontWeight: FontWeight.w800,
                                      color: Colors.white,
                                    ),
                                  ),
                                  const SizedBox(height: 3),
                                  Row(
                                    children: [
                                      Container(
                                        padding: const EdgeInsets.symmetric(
                                            horizontal: 8, vertical: 2),
                                        decoration: BoxDecoration(
                                          color: AppColors.gold,
                                          borderRadius:
                                              BorderRadius.circular(12),
                                        ),
                                        child: Text(
                                          '${m.tier} Member',
                                          style: const TextStyle(
                                            fontSize: 10,
                                            fontWeight: FontWeight.w800,
                                            color: AppColors.goldText,
                                          ),
                                        ),
                                      ),
                                      const SizedBox(width: 8),
                                      Text(
                                        m.memberNumber,
                                        style: TextStyle(
                                          fontSize: 11,
                                          color: Colors.white.withOpacity(0.6),
                                        ),
                                      ),
                                    ],
                                  ),
                                ],
                              ),
                            ],
                          ),
                          const SizedBox(height: 18),
                          Container(
                            padding: const EdgeInsets.only(top: 16),
                            decoration: BoxDecoration(
                              border: Border(
                                top: BorderSide(
                                  color: Colors.white.withOpacity(0.15),
                                ),
                              ),
                            ),
                            child: Row(
                              mainAxisAlignment: MainAxisAlignment.spaceAround,
                              children: [
                                _buildMetric('Handicap', m.hcp.toStringAsFixed(1)),
                                _buildMetric('Rounds', '${m.rounds}'),
                                 _buildMetric('Best score', memberState.bestScore > 0 ? '${memberState.bestScore}' : '—'),
                              ],
                            ),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 18),

                    // Handicap Trend Section
                    Container(
                      padding: const EdgeInsets.all(18),
                      decoration: BoxDecoration(
                        color: Colors.white,
                        borderRadius: BorderRadius.circular(22),
                        border: Border.all(color: AppColors.borderLight),
                      ),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              const Text(
                                'Handicap trend',
                                style: TextStyle(
                                  fontSize: 15,
                                  fontWeight: FontWeight.w800,
                                  color: AppColors.textMain,
                                ),
                              ),
                              if (hcpHist.length >= 2) ...[
                                Row(
                                  children: [
                                    Icon(
                                      (hcpHist.first - hcpHist.last) >= 0
                                          ? Icons.arrow_downward_rounded
                                          : Icons.arrow_upward_rounded,
                                      size: 14,
                                      color: AppColors.forest,
                                    ),
                                    const SizedBox(width: 2),
                                    Text(
                                      '${(hcpHist.first - hcpHist.last).abs().toStringAsFixed(1)} this season',
                                      style: const TextStyle(
                                        fontSize: 12,
                                        fontWeight: FontWeight.w700,
                                        color: AppColors.forest,
                                      ),
                                    ),
                                  ],
                                ),
                              ] else ...[
                                const Text(
                                  'Season stats',
                                  style: TextStyle(
                                    fontSize: 12,
                                    fontWeight: FontWeight.w600,
                                    color: AppColors.textMuted,
                                  ),
                                ),
                              ],
                            ],
                          ),
                          const SizedBox(height: 12),
                          if (hcpHist.length >= 2) ...[
                            HandicapChart(history: hcpHist),
                          ] else ...[
                            Container(
                              padding: const EdgeInsets.symmetric(vertical: 20),
                              alignment: Alignment.center,
                              child: Column(
                                children: [
                                  Icon(Icons.show_chart_rounded, size: 36, color: Colors.grey.shade300),
                                  const SizedBox(height: 8),
                                  Text(
                                    'No handicap history yet',
                                    style: TextStyle(
                                      fontSize: 13,
                                      fontWeight: FontWeight.w700,
                                      color: Colors.grey.shade700,
                                    ),
                                  ),
                                  const SizedBox(height: 4),
                                  Text(
                                    'Play rounds and record scorecards to track your handicap trend.',
                                    textAlign: TextAlign.center,
                                    style: TextStyle(
                                      fontSize: 11,
                                      color: Colors.grey.shade400,
                                    ),
                                  ),
                                ],
                              ),
                            ),
                          ],
                        ],
                      ),
                    ),
                    const SizedBox(height: 20),

                    // Saved Cards
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        const Text(
                          'Saved cards',
                          style: TextStyle(
                            fontSize: 15,
                            fontWeight: FontWeight.w800,
                            color: AppColors.textMain,
                          ),
                        ),
                        GestureDetector(
                          onTap: () => setState(() => _showAddCard = true),
                          child: const Text(
                            '+ Add card',
                            style: TextStyle(
                              fontSize: 13,
                              fontWeight: FontWeight.w700,
                              color: AppColors.forest,
                            ),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 10),
                    if (cards.isEmpty)
                      Container(
                        width: double.infinity,
                        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 16),
                        decoration: BoxDecoration(
                          color: Colors.white,
                          borderRadius: BorderRadius.circular(20),
                          border: Border.all(color: AppColors.borderLight),
                        ),
                        child: Row(
                          children: [
                            Container(
                              width: 42,
                              height: 42,
                              decoration: BoxDecoration(
                                color: Colors.grey.shade100,
                                borderRadius: BorderRadius.circular(12),
                              ),
                              child: Icon(Icons.credit_card_outlined, color: Colors.grey.shade400, size: 22),
                            ),
                            const SizedBox(width: 14),
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  const Text(
                                    'No saved cards',
                                    style: TextStyle(
                                      fontSize: 13,
                                      fontWeight: FontWeight.w700,
                                      color: AppColors.textMain,
                                    ),
                                  ),
                                  const SizedBox(height: 2),
                                  Text(
                                    'Add a card for 1-tap checkout on tee times',
                                    style: TextStyle(
                                      fontSize: 11,
                                      color: Colors.grey.shade500,
                                    ),
                                  ),
                                ],
                              ),
                            ),
                            TextButton(
                              onPressed: () => setState(() => _showAddCard = true),
                              child: const Text(
                                'Add',
                                style: TextStyle(
                                  color: AppColors.forest,
                                  fontWeight: FontWeight.w700,
                                  fontSize: 12,
                                ),
                              ),
                            ),
                          ],
                        ),
                      )
                    else
                      ...cards.map((c) => Padding(
                            padding: const EdgeInsets.only(bottom: 8),
                            child: _buildCardRow(c, notifier, appNotifier),
                          )),
                    const SizedBox(height: 16),

                    // Notifications Settings
                    const Text(
                      'Notifications',
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
                        borderRadius: BorderRadius.circular(22),
                        border: Border.all(color: AppColors.borderLight),
                      ),
                      child: Column(
                        children: [
                          _buildToggleRow(
                            title: 'Tee time reminders',
                            subtitle: '2 hours before your round',
                            value: notifs['remind'] ?? true,
                            onChanged: (v) => notifier.toggleNotif('remind', v),
                          ),
                          const Divider(height: 1, color: AppColors.borderLight),
                          _buildToggleRow(
                            title: 'Waitlist alerts',
                            subtitle: 'When a spot you want opens',
                            value: notifs['wait'] ?? true,
                            onChanged: (v) => notifier.toggleNotif('wait', v),
                          ),
                          const Divider(height: 1, color: AppColors.borderLight),
                          _buildToggleRow(
                            title: 'Deals & events',
                            subtitle: 'Twilight specials, tournaments',
                            value: notifs['promo'] ?? false,
                            onChanged: (v) => notifier.toggleNotif('promo', v),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 18),

                    // Menu Options
                    Container(
                      decoration: BoxDecoration(
                        color: Colors.white,
                        borderRadius: BorderRadius.circular(22),
                        border: Border.all(color: AppColors.borderLight),
                      ),
                      child: Column(
                        children: [
                          _buildMenuLink(
                            title: 'Personal details',
                            onTap: () => _showPersonalDetails(context, m),
                          ),
                          const Divider(height: 1, color: AppColors.borderLight),
                          _buildMenuLink(
                            title: 'Help & support',
                            onTap: () => _showHelpSupport(context, appNotifier),
                          ),
                          const Divider(height: 1, color: AppColors.borderLight),
                          _buildMenuLink(
                            title: 'Club rules & etiquette',
                            onTap: () => _showClubRules(context),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 18),

                    // Sign Out
                    Center(
                      child: TextButton.icon(
                        onPressed: () => _showSignOutDialog(
                            context, notifier, appNotifier),
                        icon: const Icon(Icons.logout_rounded,
                            size: 16, color: AppColors.danger),
                        label: const Text(
                          'Sign out',
                          style: TextStyle(
                            fontSize: 14,
                            fontWeight: FontWeight.w700,
                            color: AppColors.danger,
                          ),
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),

        // Add Card Modal
        if (_showAddCard) _buildAddCardModal(notifier, appNotifier),
      ],
    );
  }

  Widget _buildMetric(String label, String value) {
    return Column(
      children: [
        Text(
          value,
          style: const TextStyle(
            fontSize: 22,
            fontWeight: FontWeight.w900,
            color: Colors.white,
          ),
        ),
        const SizedBox(height: 2),
        Text(
          label,
          style: TextStyle(
            fontSize: 11,
            fontWeight: FontWeight.w500,
            color: Colors.white.withOpacity(0.6),
          ),
        ),
      ],
    );
  }

  Widget _buildCardRow(
    PaymentCard c,
    MemberNotifier notifier,
    AppNotifier appNotifier,
  ) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: AppColors.borderLight),
      ),
      child: Row(
        children: [
          Container(
            width: 40,
            height: 26,
            decoration: BoxDecoration(
              borderRadius: BorderRadius.circular(6),
              color: c.brand == 'Visa'
                  ? const Color(0xFF1E293B)
                  : const Color(0xFFB45309),
            ),
            child: const Icon(Icons.credit_card, size: 16, color: Colors.white),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Text(
                      '${c.brand} •••• ${c.last4}',
                      style: const TextStyle(
                        fontSize: 13,
                        fontWeight: FontWeight.w700,
                        color: AppColors.textMain,
                      ),
                    ),
                    if (c.isDefault) ...[
                      const SizedBox(width: 6),
                      Container(
                        padding: const EdgeInsets.symmetric(
                            horizontal: 6, vertical: 1),
                        decoration: BoxDecoration(
                          color: AppColors.forest.withOpacity(0.1),
                          borderRadius: BorderRadius.circular(6),
                        ),
                        child: const Text(
                          'DEFAULT',
                          style: TextStyle(
                            fontSize: 9,
                            fontWeight: FontWeight.w800,
                            color: AppColors.forest,
                          ),
                        ),
                      ),
                    ],
                  ],
                ),
                Text(
                  'Expires ${c.exp}',
                  style: const TextStyle(
                    fontSize: 11,
                    color: AppColors.textMuted,
                  ),
                ),
              ],
            ),
          ),
          if (!c.isDefault) ...[
            GestureDetector(
              onTap: () => notifier.setDefaultCard(c.id),
              child: const Text(
                'Set default',
                style: TextStyle(
                  fontSize: 12,
                  fontWeight: FontWeight.w700,
                  color: AppColors.forest,
                ),
              ),
            ),
            const SizedBox(width: 10),
            GestureDetector(
              onTap: () {
                notifier.removeCard(c.id);
                appNotifier.showToast('Card removed');
              },
              child: const Icon(
                Icons.delete_outline_rounded,
                size: 18,
                color: AppColors.textMuted,
              ),
            ),
          ],
        ],
      ),
    );
  }

  Widget _buildToggleRow({
    required String title,
    required String subtitle,
    required bool value,
    required ValueChanged<bool> onChanged,
  }) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
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
          Switch.adaptive(
            value: value,
            activeTrackColor: AppColors.forest,
            onChanged: onChanged,
          ),
        ],
      ),
    );
  }

  Widget _buildMenuLink({
    required String title,
    required VoidCallback onTap,
  }) {
    return InkWell(
      onTap: onTap,
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
        child: Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            Text(
              title,
              style: const TextStyle(
                fontSize: 13,
                fontWeight: FontWeight.w700,
                color: AppColors.textMain,
              ),
            ),
            const Icon(
              Icons.chevron_right_rounded,
              size: 20,
              color: Color(0xFFD1D5DB),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildAddCardModal(MemberNotifier notifier, AppNotifier appNotifier) {
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
                    'Add a card',
                    style: TextStyle(
                      fontSize: 18,
                      fontWeight: FontWeight.w800,
                      color: AppColors.textMain,
                    ),
                  ),
                  GestureDetector(
                    onTap: () => setState(() => _showAddCard = false),
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
              const Text(
                'Card number',
                style: TextStyle(
                  fontSize: 12,
                  fontWeight: FontWeight.w700,
                  color: AppColors.textSecondary,
                ),
              ),
              const SizedBox(height: 6),
              TextField(
                controller: _numCtrl,
                keyboardType: TextInputType.number,
                decoration: InputDecoration(
                  hintText: '1234 5678 9012 3456',
                  filled: true,
                  fillColor: const Color(0xFFF9FAFB),
                  border: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(14),
                    borderSide: const BorderSide(color: AppColors.border),
                  ),
                ),
              ),
              const SizedBox(height: 12),
              const Text(
                'Expiry',
                style: TextStyle(
                  fontSize: 12,
                  fontWeight: FontWeight.w700,
                  color: AppColors.textSecondary,
                ),
              ),
              const SizedBox(height: 6),
              TextField(
                controller: _expCtrl,
                keyboardType: TextInputType.datetime,
                decoration: InputDecoration(
                  hintText: 'MM/YY',
                  filled: true,
                  fillColor: const Color(0xFFF9FAFB),
                  border: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(14),
                    borderSide: const BorderSide(color: AppColors.border),
                  ),
                ),
              ),
              const SizedBox(height: 20),
              PrimaryButton(
                text: 'Save card',
                onPressed: () async {
                  final rawNum = _numCtrl.text.replaceAll(RegExp(r'\D'), '');
                  if (rawNum.length < 13) {
                    appNotifier.showToast('Please enter a valid 16-digit card number');
                    return;
                  }
                  final exp = _expCtrl.text.trim().isNotEmpty ? _expCtrl.text.trim() : '12/28';
                  final card = await notifier.addCard(
                    cardNumber: rawNum,
                    exp: exp,
                  );
                  if (!mounted) return;
                  if (card != null) {
                    setState(() => _showAddCard = false);
                    _numCtrl.clear();
                    _expCtrl.clear();
                    appNotifier.showToast('Card saved to your account!');
                  } else {
                    appNotifier.showToast('Could not save card. Please check details.');
                  }
                },
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

  void _showSignOutDialog(
    BuildContext context,
    MemberNotifier notifier,
    AppNotifier appNotifier,
  ) {
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(22)),
        title: const Text(
          'Sign out of Pine Valley?',
          style: TextStyle(fontWeight: FontWeight.w800, fontSize: 17),
        ),
        content: const Text(
          'You will need to sign in again to view your bookings, waitlists, and tee times.',
          style: TextStyle(fontSize: 13, height: 1.4),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx),
            child: Text(
              'Cancel',
              style: TextStyle(
                color: Colors.grey.shade600,
                fontWeight: FontWeight.w600,
              ),
            ),
          ),
          ElevatedButton(
            style: ElevatedButton.styleFrom(
              backgroundColor: AppColors.danger,
              foregroundColor: Colors.white,
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(12),
              ),
              elevation: 0,
            ),
            onPressed: () {
              Navigator.pop(ctx);
              notifier.logout();
              appNotifier.navigateTo(AppScreen.login);
              appNotifier.showToast('Signed out successfully');
            },
            child: const Text('Sign Out',
                style: TextStyle(fontWeight: FontWeight.w700)),
          ),
        ],
      ),
    );
  }

  void _showPersonalDetails(BuildContext context, Member member) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) => Container(
        padding: const EdgeInsets.fromLTRB(20, 16, 20, 32),
        decoration: const BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.vertical(top: Radius.circular(26)),
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
                  'Personal Details',
                  style: TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.w800,
                    color: AppColors.textMain,
                  ),
                ),
                IconButton(
                  icon: const Icon(Icons.close, size: 20),
                  onPressed: () => Navigator.pop(ctx),
                ),
              ],
            ),
            const SizedBox(height: 12),
            _buildDetailRow('Full Name', member.name),
            _buildDetailRow('Member Number', member.memberNumber),
            _buildDetailRow('Email', member.email),
            _buildDetailRow('Phone', member.phone.isNotEmpty ? member.phone : '(555) 301-2201'),
            _buildDetailRow('Tier Level', '${member.tier} Member'),
            _buildDetailRow('Current Handicap', 'HCP ${member.hcp}'),
            _buildDetailRow('Rounds Played', '${member.rounds} rounds'),
            _buildDetailRow('Total Spend', '\$${member.spend.toInt()}'),
            _buildDetailRow('Member Since', member.joined),
          ],
        ),
      ),
    );
  }

  Widget _buildDetailRow(String label, String value) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 8),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(
            label,
            style: const TextStyle(
              fontSize: 13,
              fontWeight: FontWeight.w500,
              color: AppColors.textMuted,
            ),
          ),
          Text(
            value,
            style: const TextStyle(
              fontSize: 13,
              fontWeight: FontWeight.w700,
              color: AppColors.textMain,
            ),
          ),
        ],
      ),
    );
  }


  void _showHelpSupport(BuildContext context, AppNotifier appNotifier) {
    showModalBottomSheet(
      context: context,
      backgroundColor: Colors.transparent,
      builder: (ctx) => Container(
        padding: const EdgeInsets.fromLTRB(20, 16, 20, 32),
        decoration: const BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.vertical(top: Radius.circular(26)),
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
              'Pine Valley Pro Shop & Support',
              style: TextStyle(
                fontSize: 18,
                fontWeight: FontWeight.w800,
                color: AppColors.textMain,
              ),
            ),
            const SizedBox(height: 14),
            _buildDetailRow('Clubhouse Phone', '(555) 301-2200'),
            _buildDetailRow('Pro Shop Direct', '(555) 301-2205'),
            _buildDetailRow('Member Inquiries', 'members@pinevalley.com'),
            _buildDetailRow('Operating Hours', '6:00 AM – 7:30 PM daily'),
            _buildDetailRow('Tee Time Booking Window', '14 days in advance'),
          ],
        ),
      ),
    );
  }

  void _showClubRules(BuildContext context) {
    showModalBottomSheet(
      context: context,
      backgroundColor: Colors.transparent,
      builder: (ctx) => Container(
        padding: const EdgeInsets.fromLTRB(20, 16, 20, 32),
        decoration: const BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.vertical(top: Radius.circular(26)),
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
              'Club Rules & Etiquette',
              style: TextStyle(
                fontSize: 18,
                fontWeight: FontWeight.w800,
                color: AppColors.textMain,
              ),
            ),
            const SizedBox(height: 14),
            _buildDetailRow('Dress Code', 'Collared shirts, soft spikes only'),
            _buildDetailRow('Pace of Play', '4 hrs 15 mins for 18 holes'),
            _buildDetailRow('Cart Regulation', '90-degree rule on fairways'),
            _buildDetailRow('Cell Phone Policy', 'Silent mode required on course'),
            _buildDetailRow('Cancellation Window', '24 hours prior to tee time'),
          ],
        ),
      ),
    );
  }
}
