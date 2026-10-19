class WaitlistEntry {
  final int id;
  final String date;
  final int min;
  final String time;
  final int holes;
  final int players;
  final int position;

  WaitlistEntry({
    required this.id,
    required this.date,
    required this.min,
    required this.time,
    required this.holes,
    required this.players,
    required this.position,
  });

  factory WaitlistEntry.fromJson(Map<String, dynamic> json) {
    return WaitlistEntry(
      id: json['id'] is int ? json['id'] : int.tryParse(json['id'].toString()) ?? 0,
      date: json['date']?.toString() ?? '',
      min: json['min'] is int ? json['min'] : int.tryParse(json['min'].toString()) ?? 480,
      time: json['time']?.toString() ?? '',
      holes: json['holes'] is int ? json['holes'] : int.tryParse(json['holes'].toString()) ?? 18,
      players: json['players'] is int ? json['players'] : int.tryParse(json['players'].toString()) ?? 2,
      position: json['position'] is int ? json['position'] : int.tryParse(json['position'].toString()) ?? 1,
    );
  }

  Map<String, dynamic> toJson() => {
    'id': id,
    'date': date,
    'min': min,
    'time': time,
    'holes': holes,
    'players': players,
    'position': position,
  };
}
