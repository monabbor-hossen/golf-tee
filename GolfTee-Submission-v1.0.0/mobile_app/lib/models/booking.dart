class Booking {
  final int id;
  final String date;
  final int min;
  final String time;
  final int holes;
  final int players;
  final List<String> partners;
  final List<String> addons;
  final double total;
  final String card;
  final String tee;
  final String status; // 'upcoming', 'past', 'cancelled'
  final int? score;

  Booking({
    required this.id,
    required this.date,
    required this.min,
    required this.time,
    required this.holes,
    required this.players,
    this.partners = const [],
    this.addons = const ['cart'],
    required this.total,
    this.card = 'v',
    this.tee = 'Hole 1',
    this.status = 'upcoming',
    this.score,
  });

  factory Booking.fromJson(Map<String, dynamic> json) {
    return Booking(
      id: json['id'] is int ? json['id'] : int.tryParse(json['id'].toString()) ?? 0,
      date: json['date']?.toString() ?? '',
      min: json['min'] is int ? json['min'] : int.tryParse(json['min'].toString()) ?? 480,
      time: json['time']?.toString() ?? '',
      holes: json['holes'] is int ? json['holes'] : int.tryParse(json['holes'].toString()) ?? 18,
      players: json['players'] is int ? json['players'] : int.tryParse(json['players'].toString()) ?? 2,
      partners: (json['partners'] as List<dynamic>?)?.map((e) => e.toString()).toList() ?? [],
      addons: (json['addons'] as List<dynamic>?)?.map((e) => e.toString()).toList() ?? ['cart'],
      total: (json['total'] is num) ? (json['total'] as num).toDouble() : double.tryParse(json['total'].toString()) ?? 170.0,
      card: json['card']?.toString() ?? 'v',
      tee: json['tee']?.toString() ?? 'Hole 1',
      status: json['status']?.toString() ?? 'upcoming',
      score: json['score'] != null ? int.tryParse(json['score'].toString()) : null,
    );
  }

  Map<String, dynamic> toJson() => {
    'id': id,
    'date': date,
    'min': min,
    'time': time,
    'holes': holes,
    'players': players,
    'partners': partners,
    'addons': addons,
    'total': total,
    'card': card,
    'tee': tee,
    'status': status,
    'score': score,
  };

  Booking copyWith({
    int? id,
    String? date,
    int? min,
    String? time,
    int? holes,
    int? players,
    List<String>? partners,
    List<String>? addons,
    double? total,
    String? card,
    String? tee,
    String? status,
    int? score,
  }) {
    return Booking(
      id: id ?? this.id,
      date: date ?? this.date,
      min: min ?? this.min,
      time: time ?? this.time,
      holes: holes ?? this.holes,
      players: players ?? this.players,
      partners: partners ?? this.partners,
      addons: addons ?? this.addons,
      total: total ?? this.total,
      card: card ?? this.card,
      tee: tee ?? this.tee,
      status: status ?? this.status,
      score: score ?? this.score,
    );
  }
}
