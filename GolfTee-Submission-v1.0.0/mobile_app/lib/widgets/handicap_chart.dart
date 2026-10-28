import 'dart:math';
import 'package:flutter/material.dart';
import '../constants/colors.dart';

class HandicapChart extends StatelessWidget {
  final List<double> history;

  const HandicapChart({
    super.key,
    required this.history,
  });

  @override
  Widget build(BuildContext context) {
    if (history.isEmpty) return const SizedBox.shrink();

    return Column(
      children: [
        SizedBox(
          height: 90,
          width: double.infinity,
          child: CustomPaint(
            painter: _HandicapPainter(history),
          ),
        ),
        const SizedBox(height: 6),
        const Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            Text('May', style: TextStyle(fontSize: 10, color: AppColors.textMuted)),
            Text('Jul', style: TextStyle(fontSize: 10, color: AppColors.textMuted)),
            Text('Sep', style: TextStyle(fontSize: 10, color: AppColors.textMuted)),
            Text('Now', style: TextStyle(fontSize: 10, color: AppColors.textMuted)),
          ],
        ),
      ],
    );
  }
}

class _HandicapPainter extends CustomPainter {
  final List<double> data;

  _HandicapPainter(this.data);

  @override
  void paint(Canvas canvas, Size size) {
    if (data.length < 2) return;

    final minVal = data.reduce(min) - 0.4;
    final maxVal = data.reduce(max) + 0.4;
    final range = (maxVal - minVal) == 0 ? 1.0 : (maxVal - minVal);

    final points = <Offset>[];
    for (int i = 0; i < data.length; i++) {
      final x = (i / (data.length - 1)) * size.width;
      // Invert y: lower handicap is higher on the chart
      final y = ((data[i] - minVal) / range) * (size.height - 20) + 10;
      points.add(Offset(x, y));
    }

    // Path for gradient fill
    final fillPath = Path();
    fillPath.moveTo(0, size.height);
    fillPath.lineTo(points.first.dx, points.first.dy);

    for (int i = 0; i < points.length - 1; i++) {
      final p0 = points[i];
      final p1 = points[i + 1];
      final cx = (p0.dx + p1.dx) / 2;
      fillPath.cubicTo(cx, p0.dy, cx, p1.dy, p1.dx, p1.dy);
    }
    fillPath.lineTo(size.width, size.height);
    fillPath.close();

    final fillPaint = Paint()
      ..shader = LinearGradient(
        begin: Alignment.topCenter,
        end: Alignment.bottomCenter,
        colors: [
          AppColors.forest.withOpacity(0.28),
          AppColors.forest.withOpacity(0.0),
        ],
      ).createShader(Rect.fromLTWH(0, 0, size.width, size.height));

    canvas.drawPath(fillPath, fillPaint);

    // Stroke path
    final strokePath = Path();
    strokePath.moveTo(points.first.dx, points.first.dy);

    for (int i = 0; i < points.length - 1; i++) {
      final p0 = points[i];
      final p1 = points[i + 1];
      final cx = (p0.dx + p1.dx) / 2;
      strokePath.cubicTo(cx, p0.dy, cx, p1.dy, p1.dx, p1.dy);
    }

    final strokePaint = Paint()
      ..color = AppColors.forest
      ..strokeWidth = 2.5
      ..style = PaintingStyle.stroke
      ..strokeCap = StrokeCap.round;

    canvas.drawPath(strokePath, strokePaint);

    // Draw dots
    final dotPaint = Paint()..color = Colors.white;
    final dotBorderPaint = Paint()
      ..color = AppColors.forest
      ..strokeWidth = 2
      ..style = PaintingStyle.stroke;

    final activeDotPaint = Paint()..color = AppColors.forest;

    for (int i = 0; i < points.length; i++) {
      final isLast = i == points.length - 1;
      if (isLast) {
        canvas.drawCircle(points[i], 4.5, activeDotPaint);
      } else {
        canvas.drawCircle(points[i], 3.0, dotPaint);
        canvas.drawCircle(points[i], 3.0, dotBorderPaint);
      }
    }
  }

  @override
  bool shouldRepaint(covariant _HandicapPainter oldDelegate) =>
      oldDelegate.data != data;
}
