import 'package:flutter/material.dart';
import '../constants/colors.dart';
import '../models/tee_slot.dart';

class TimeSlotChip extends StatelessWidget {
  final TeeSlot slot;
  final VoidCallback? onTap;

  const TimeSlotChip({
    super.key,
    required this.slot,
    this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    final isDisabled =
        slot.state == SlotState.locked || slot.state == SlotState.booked;

    Color bg;
    Color border;
    Color textColor;
    Color subColor;

    switch (slot.state) {
      case SlotState.selected:
        bg = AppColors.forest;
        border = AppColors.forest;
        textColor = Colors.white;
        subColor = Colors.white.withOpacity(0.85);
        break;
      case SlotState.waitlist:
        bg = AppColors.waitLight;
        border = AppColors.wait.withOpacity(0.5);
        textColor = AppColors.waitTextDark;
        subColor = AppColors.waitText;
        break;
      case SlotState.locked:
        bg = const Color(0xFFF3F4F6);
        border = AppColors.border;
        textColor = const Color(0xFFD1D5DB);
        subColor = const Color(0xFFD1D5DB);
        break;
      case SlotState.booked:
        bg = const Color(0xFFF3F4F6);
        border = AppColors.border;
        textColor = const Color(0xFF9CA3AF);
        subColor = const Color(0xFF9CA3AF);
        break;
      case SlotState.available:
        bg = Colors.white;
        border = AppColors.forest.withValues(alpha: 0.5);
        textColor = AppColors.forest;
        subColor = AppColors.forest.withValues(alpha: 0.8);
        break;
    }

    final parts = slot.time.split(' ');
    final timeStr = parts.isNotEmpty ? parts[0] : slot.time;
    final ampm = parts.length > 1 ? parts[1] : '';

    String bottomLabel = '\$${slot.price.toInt()}';
    if (slot.state == SlotState.selected) {
      bottomLabel = '✓ \$${slot.price.toInt()}';
    } else if (slot.state == SlotState.waitlist) {
      bottomLabel = 'Waitlist';
    } else if (slot.state == SlotState.booked) {
      bottomLabel = 'Booked';
    } else if (slot.state == SlotState.locked) {
      bottomLabel = 'Closed';
    }

    return GestureDetector(
      onTap: isDisabled ? null : onTap,
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 180),
        height: 60,
        decoration: BoxDecoration(
          color: bg,
          borderRadius: BorderRadius.circular(18),
          border: Border.all(
            color: border,
            width: slot.state == SlotState.selected ? 2.0 : 1.5,
          ),
          boxShadow: slot.state == SlotState.selected
              ? [
                  BoxShadow(
                    color: AppColors.forest.withValues(alpha: 0.35),
                    blurRadius: 10,
                    offset: const Offset(0, 4),
                  ),
                ]
              : null,
        ),
        child: Stack(
          alignment: Alignment.center,
          children: [
            if (slot.state == SlotState.selected)
              const Positioned(
                top: 4,
                right: 6,
                child: Icon(
                  Icons.check_circle_rounded,
                  size: 13,
                  color: Colors.white,
                ),
              ),
            Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.center,
                  crossAxisAlignment: CrossAxisAlignment.baseline,
                  textBaseline: TextBaseline.alphabetic,
                  children: [
                    Text(
                      timeStr,
                      style: TextStyle(
                        fontSize: 15,
                        fontWeight: FontWeight.w700,
                        color: textColor,
                        decoration: slot.state == SlotState.booked
                            ? TextDecoration.lineThrough
                            : null,
                      ),
                    ),
                    if (ampm.isNotEmpty) ...[
                      const SizedBox(width: 1),
                      Text(
                        ampm,
                        style: TextStyle(
                          fontSize: 10,
                          fontWeight: FontWeight.w600,
                          color: textColor.withValues(alpha: 0.8),
                        ),
                      ),
                    ],
                  ],
                ),
                const SizedBox(height: 1),
                Text(
                  bottomLabel,
                  style: TextStyle(
                    fontSize: 10,
                    fontWeight: FontWeight.w700,
                    letterSpacing: 0.2,
                    color: subColor,
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}
