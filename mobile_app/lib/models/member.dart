class Member {
  final int id;
  final String name;
  final String email;
  final String phone;
  final String tier;
  final double hcp;
  final int rounds;
  final double spend;
  final String memberNumber;
  final String joined;

  Member({
    required this.id,
    required this.name,
    required this.email,
    required this.phone,
    required this.tier,
    required this.hcp,
    required this.rounds,
    required this.spend,
    this.memberNumber = '#PV-20418',
    this.joined = 'Mar 2021',
  });

  factory Member.fromJson(Map<String, dynamic> json) {
    return Member(
      id: json['id'] is int ? json['id'] : int.tryParse(json['id'].toString()) ?? 1,
      name: json['name']?.toString() ?? 'James Carter',
      email: json['email']?.toString() ?? 'james.carter@mail.com',
      phone: json['phone']?.toString() ?? '(555) 301-2201',
      tier: json['tier']?.toString() ?? 'Gold',
      hcp: (json['hcp'] is num) ? (json['hcp'] as num).toDouble() : double.tryParse(json['hcp'].toString()) ?? 8.4,
      rounds: json['rounds'] is int ? json['rounds'] : int.tryParse(json['rounds'].toString()) ?? 42,
      spend: (json['spend'] is num) ? (json['spend'] as num).toDouble() : double.tryParse(json['spend'].toString()) ?? 4820.0,
      memberNumber: json['member_number']?.toString() ?? '#PV-20418',
      joined: json['joined']?.toString() ?? 'Mar 2021',
    );
  }

  Member copyWith({
    int? id,
    String? name,
    String? email,
    String? phone,
    String? tier,
    double? hcp,
    int? rounds,
    double? spend,
    String? memberNumber,
    String? joined,
  }) {
    return Member(
      id: id ?? this.id,
      name: name ?? this.name,
      email: email ?? this.email,
      phone: phone ?? this.phone,
      tier: tier ?? this.tier,
      hcp: hcp ?? this.hcp,
      rounds: rounds ?? this.rounds,
      spend: spend ?? this.spend,
      memberNumber: memberNumber ?? this.memberNumber,
      joined: joined ?? this.joined,
    );
  }
}
