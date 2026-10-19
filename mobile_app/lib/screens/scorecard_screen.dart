import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../constants/colors.dart';
import '../constants/strings.dart';
import '../providers/app_provider.dart';
import '../providers/member_provider.dart';
import '../providers/scorecard_provider.dart';
import '../widgets/primary_button.dart';
import '../widgets/top_bar.dart';

class ScorecardScreen extends ConsumerWidget {
  const ScorecardScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final scorecard = ref.watch(scorecardProvider);
    final notifier = ref.read(scorecardProvider.notifier);
    final memberNotifier = ref.read(memberProvider.notifier);
    final appNotifier = ref.read(appProvider.notifier);

    final hole = scorecard.currentHole;
    final currentScore = scorecard.scores[hole];
    final par = AppStrings.pars[hole];
    final yards = AppStrings.yards[hole];
    final displayScore = currentScore == 0 ? par : currentScore;

    return SingleChildScrollView(
      padding: const EdgeInsets.only(bottom: 24),
      child: Column(
        children: [
          const TopBar(
            title: 'Scorecard',
            right: Text(
              'Blue tees',
              style: TextStyle(
                fontSize: 12,
                fontWeight: FontWeight.w700,
                color: AppColors.textMuted,
              ),
            ),
          ),
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 20),
            child: Column(
              children: [
                // Top 3-Stat Summary
                Row(
                  children: [
                    _buildStatCard(
                      label: 'Strokes',
                      val: scorecard.totalStrokes > 0
                          ? '${scorecard.totalStrokes}'
                          : '—',
                    ),
                    const SizedBox(width: 8),
                    _buildStatCard(
                      label: 'To par',
                      val: scorecard.playedCount > 0
                          ? (scorecard.toPar > 0
                              ? '+${scorecard.toPar}'
                              : (scorecard.toPar == 0 ? 'E' : '${scorecard.toPar}'))
                          : '—',
                      highlightGreen: scorecard.toPar < 0,
                    ),
                    const SizedBox(width: 8),
                    _buildStatCard(
                      label: 'Thru',
                      val: '${scorecard.playedCount}',
                    ),
                  ],
                ),
                const SizedBox(height: 16),

                // Active Hole Card (Forest Green)
                Container(
                  padding: const EdgeInsets.all(22),
                  decoration: BoxDecoration(
                    color: AppColors.forest,
                    borderRadius: BorderRadius.circular(28),
                    boxShadow: [
                      BoxShadow(
                        color: AppColors.forest.withOpacity(0.28),
                        blurRadius: 18,
                        offset: const Offset(0, 6),
                      ),
                    ],
                  ),
                  child: Column(
                    children: [
                      // Hole Header with prev/next arrows
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          _buildNavCircle(
                            icon: Icons.arrow_back_ios_new_rounded,
                            enabled: hole > 0,
                            onTap: () => notifier.prevHole(),
                          ),
                          Column(
                            children: [
                              Text(
                                'HOLE',
                                style: TextStyle(
                                  fontSize: 11,
                                  fontWeight: FontWeight.w700,
                                  color: Colors.white.withOpacity(0.6),
                                  letterSpacing: 1.0,
                                ),
                              ),
                              Text(
                                '${hole + 1}',
                                style: const TextStyle(
                                  fontSize: 42,
                                  fontWeight: FontWeight.w900,
                                  color: Colors.white,
                                  height: 1.0,
                                ),
                              ),
                              const SizedBox(height: 2),
                              Text(
                                'Par $par · $yards yds',
                                style: TextStyle(
                                  fontSize: 13,
                                  color: Colors.white.withOpacity(0.8),
                                  fontWeight: FontWeight.w500,
                                ),
                              ),
                            ],
                          ),
                          _buildNavCircle(
                            icon: Icons.arrow_forward_ios_rounded,
                            enabled: hole < 17,
                            onTap: () => notifier.nextHole(),
                          ),
                        ],
                      ),
                      const SizedBox(height: 18),

                      // Giant Stroke Counter (- / +)
                      Row(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          _buildStrokeBtn(
                            icon: Icons.remove,
                            onTap: () => notifier.decrementStroke(),
                          ),
                          const SizedBox(width: 24),
                          SizedBox(
                            width: 100,
                            child: Column(
                              children: [
                                Text(
                                  '$displayScore',
                                  style: TextStyle(
                                    fontSize: 64,
                                    fontWeight: FontWeight.w900,
                                    color: currentScore > 0
                                        ? Colors.white
                                        : Colors.white.withOpacity(0.4),
                                    height: 1.0,
                                  ),
                                ),
                                const SizedBox(height: 4),
                                Text(
                                  currentScore > 0
                                      ? _scoreLabel(displayScore, par)
                                      : 'Not played',
                                  style: const TextStyle(
                                    fontSize: 12,
                                    fontWeight: FontWeight.w800,
                                    color: AppColors.sand,
                                    letterSpacing: 0.5,
                                  ),
                                ),
                              ],
                            ),
                          ),
                          const SizedBox(width: 24),
                          _buildStrokeBtn(
                            icon: Icons.add,
                            onTap: () => notifier.incrementStroke(),
                          ),
                        ],
                      ),
                      const SizedBox(height: 20),

                      // Save & Next Button
                      SizedBox(
                        width: double.infinity,
                        height: 48,
                        child: ElevatedButton(
                          style: ElevatedButton.styleFrom(
                            backgroundColor: Colors.white,
                            foregroundColor: AppColors.forest,
                            shape: RoundedRectangleBorder(
                              borderRadius: BorderRadius.circular(16),
                            ),
                            elevation: 0,
                          ),
                          onPressed: () => notifier.saveAndAdvance(),
                          child: Text(
                            currentScore > 0
                                ? (hole < 17 ? 'Next hole' : 'Saved')
                                : 'Save $displayScore & next',
                            style: const TextStyle(
                              fontSize: 14,
                              fontWeight: FontWeight.w800,
                            ),
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 18),

                // 9-Hole Matrices (OUT & IN)
                _buildNineMatrix(
                  title: 'OUT (Front 9)',
                  startHole: 0,
                  scorecard: scorecard,
                  onSelectHole: (i) => notifier.selectHole(i),
                ),
                const SizedBox(height: 12),
                _buildNineMatrix(
                  title: 'IN (Back 9)',
                  startHole: 9,
                  scorecard: scorecard,
                  onSelectHole: (i) => notifier.selectHole(i),
                ),
                const SizedBox(height: 14),

                // Legend
                Row(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    _buildLegendItem(
                      color: AppColors.forest,
                      label: 'Birdie or better',
                      isCircle: true,
                    ),
                    const SizedBox(width: 14),
                    _buildLegendItem(
                      color: AppColors.sand,
                      label: 'Bogey',
                      isCircle: false,
                    ),
                    const SizedBox(width: 14),
                    _buildLegendItem(
                      color: AppColors.danger.withOpacity(0.3),
                      label: 'Double+',
                      isCircle: false,
                    ),
                  ],
                ),
                const SizedBox(height: 20),

                // Finish Round Button
                PrimaryButton(
                  text: scorecard.playedCount >= 9
                      ? 'Finish round · ${scorecard.totalStrokes}'
                      : 'Play ${9 - scorecard.playedCount} more holes to save',
                  isEnabled: scorecard.playedCount >= 9,
                  isLoading: scorecard.isSaving,
                  onPressed: () async {
                    final newHcp = await notifier.finishRound();
                    if (newHcp != null) {
                      memberNotifier.updateHandicap(newHcp);
                      appNotifier.showToast(
                          'Round saved! Handicap updated to $newHcp');
                    } else {
                      appNotifier.showToast('Round saved to history!');
                    }
                    appNotifier.navigateTo(AppScreen.home);
                  },
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildStatCard({
    required String label,
    required String val,
    bool highlightGreen = false,
  }) {
    return Expanded(
      child: Container(
        padding: const EdgeInsets.symmetric(vertical: 14),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(18),
          border: Border.all(color: AppColors.borderLight),
        ),
        child: Column(
          children: [
            Text(
              val,
              style: TextStyle(
                fontSize: 22,
                fontWeight: FontWeight.w900,
                color: highlightGreen ? AppColors.forest : AppColors.textMain,
              ),
            ),
            const SizedBox(height: 2),
            Text(
              label,
              style: const TextStyle(
                fontSize: 11,
                color: AppColors.textMuted,
                fontWeight: FontWeight.w500,
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildNavCircle({
    required IconData icon,
    required bool enabled,
    required VoidCallback onTap,
  }) {
    return GestureDetector(
      onTap: enabled ? onTap : null,
      child: Container(
        width: 38,
        height: 38,
        decoration: BoxDecoration(
          shape: BoxShape.circle,
          color: Colors.white.withOpacity(enabled ? 0.15 : 0.05),
        ),
        child: Icon(
          icon,
          size: 16,
          color: Colors.white.withOpacity(enabled ? 1.0 : 0.25),
        ),
      ),
    );
  }

  Widget _buildStrokeBtn({
    required IconData icon,
    required VoidCallback onTap,
  }) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        width: 50,
        height: 50,
        decoration: BoxDecoration(
          shape: BoxShape.circle,
          color: Colors.white.withOpacity(0.15),
        ),
        child: Icon(icon, color: Colors.white, size: 24),
      ),
    );
  }

  Widget _buildNineMatrix({
    required String title,
    required int startHole,
    required ScorecardState scorecard,
    required ValueChanged<int> onSelectHole,
  }) {
    final pars = AppStrings.pars.sublist(startHole, startHole + 9);
    final scores = scorecard.scores.sublist(startHole, startHole + 9);
    final totalNine = scores.fold(0, (a, b) => a + b);

    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: AppColors.borderLight),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(
                title,
                style: const TextStyle(
                  fontSize: 12,
                  fontWeight: FontWeight.w700,
                  color: AppColors.textSecondary,
                ),
              ),
              Text(
                'Total: ${totalNine > 0 ? totalNine : "—"}',
                style: const TextStyle(
                  fontSize: 12,
                  fontWeight: FontWeight.w800,
                  color: AppColors.textMain,
                ),
              ),
            ],
          ),
          const SizedBox(height: 10),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: List.generate(9, (idx) {
              final holeIdx = startHole + idx;
              final holeNum = holeIdx + 1;
              final parVal = pars[idx];
              final scoreVal = scores[idx];
              final isCurrent = scorecard.currentHole == holeIdx;

              Color cellBg = Colors.transparent;
              Color textCol = AppColors.textMain;

              if (scoreVal > 0) {
                if (scoreVal < parVal) {
                  cellBg = AppColors.forest;
                  textCol = Colors.white;
                } else if (scoreVal == parVal) {
                  cellBg = const Color(0xFFF3F4F6);
                  textCol = AppColors.textMain;
                } else if (scoreVal == parVal + 1) {
                  cellBg = AppColors.sandLight;
                  textCol = const Color(0xFF6B5A3A);
                } else {
                  cellBg = AppColors.dangerLight;
                  textCol = AppColors.danger;
                }
              }

              return GestureDetector(
                onTap: () => onSelectHole(holeIdx),
                child: Column(
                  children: [
                    Text(
                      '$holeNum',
                      style: const TextStyle(
                        fontSize: 10,
                        fontWeight: FontWeight.w600,
                        color: AppColors.textMuted,
                      ),
                    ),
                    const SizedBox(height: 4),
                    Container(
                      width: 28,
                      height: 28,
                      decoration: BoxDecoration(
                        color: cellBg,
                        borderRadius: BorderRadius.circular(8),
                        border: isCurrent
                            ? Border.all(color: AppColors.forest, width: 2)
                            : null,
                      ),
                      alignment: Alignment.center,
                      child: Text(
                        scoreVal > 0 ? '$scoreVal' : '·',
                        style: TextStyle(
                          fontSize: 13,
                          fontWeight: FontWeight.w800,
                          color: scoreVal > 0 ? textCol : Colors.grey.shade300,
                        ),
                      ),
                    ),
                  ],
                ),
              );
            }),
          ),
        ],
      ),
    );
  }

  Widget _buildLegendItem({
    required Color color,
    required String label,
    required bool isCircle,
  }) {
    return Row(
      children: [
        Container(
          width: 10,
          height: 10,
          decoration: BoxDecoration(
            color: color,
            shape: isCircle ? BoxShape.circle : BoxShape.rectangle,
            borderRadius: isCircle ? null : BorderRadius.circular(2),
          ),
        ),
        const SizedBox(width: 5),
        Text(
          label,
          style: const TextStyle(
            fontSize: 10,
            color: AppColors.textMuted,
            fontWeight: FontWeight.w600,
          ),
        ),
      ],
    );
  }

  static String _scoreLabel(int score, int par) {
    final diff = score - par;
    switch (diff) {
      case -3:
        return 'Albatross';
      case -2:
        return 'Eagle';
      case -1:
        return 'Birdie';
      case 0:
        return 'Par';
      case 1:
        return 'Bogey';
      case 2:
        return 'Double';
      case 3:
        return 'Triple';
      default:
        return diff > 0 ? '+$diff' : '$diff';
    }
  }
}
