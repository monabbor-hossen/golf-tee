enum SlotState {
  available,
  selected,
  locked,
  booked,
  waitlist;

  static SlotState fromString(String val) {
    switch (val.toLowerCase()) {
      case 'selected':
        return SlotState.selected;
      case 'locked':
        return SlotState.locked;
      case 'booked':
        return SlotState.booked;
      case 'waitlist':
        return SlotState.waitlist;
      case 'available':
      default:
        return SlotState.available;
    }
  }

  String toStr() {
    switch (this) {
      case SlotState.selected:
        return 'selected';
      case SlotState.locked:
        return 'locked';
      case SlotState.booked:
        return 'booked';
      case SlotState.waitlist:
        return 'waitlist';
      case SlotState.available:
        return 'available';
    }
  }
}

class TeeSlot {
  final int min;
  final String time;
  final double price;
  final SlotState state;
  final String tee;

  TeeSlot({
    required this.min,
    required this.time,
    required this.price,
    required this.state,
    this.tee = 'Hole 1',
  });

  factory TeeSlot.fromJson(Map<String, dynamic> json) {
    return TeeSlot(
      min: json['min'] is int ? json['min'] : int.tryParse(json['min'].toString()) ?? 420,
      time: json['time']?.toString() ?? '',
      price: (json['price'] is num) ? (json['price'] as num).toDouble() : double.tryParse(json['price'].toString()) ?? 85.0,
      state: SlotState.fromString(json['state']?.toString() ?? 'available'),
      tee: json['tee']?.toString() ?? 'Hole 1',
    );
  }

  TeeSlot copyWith({
    int? min,
    String? time,
    double? price,
    SlotState? state,
    String? tee,
  }) {
    return TeeSlot(
      min: min ?? this.min,
      time: time ?? this.time,
      price: price ?? this.price,
      state: state ?? this.state,
      tee: tee ?? this.tee,
    );
  }
}
