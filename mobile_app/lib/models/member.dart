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
    this.memberNumber = '',
    this.joined = '',
  });

  factory Member.fromJson(Map<String, dynamic> json) {
    final rawId = json['id'] is int ? json['id'] : int.tryParse(json['id'].toString()) ?? 0;
    return Member(
      id: rawId,
      name: json['name']?.toString() ?? '',
      email: json['email']?.toString() ?? '',
      phone: json['phone']?.toString() ?? '',
      tier: json['tier']?.toString() ?? 'Standard',
      hcp: (json['hcp'] is num) ? (json['hcp'] as num).toDouble() : double.tryParse(json['hcp'].toString()) ?? 0.0,
      rounds: json['rounds'] is int ? json['rounds'] : int.tryParse(json['rounds'].toString()) ?? 0,
      spend: (json['spend'] is num) ? (json['spend'] as num).toDouble() : double.tryParse(json['spend'].toString()) ?? 0.0,
      memberNumber: json['member_number']?.toString() ?? (rawId > 0 ? '#PV-${rawId.toString().padLeft(5, '0')}' : ''),
      joined: json['joined']?.toString() ?? '',
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

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'name': name,
      'email': email,
      'phone': phone,
      'tier': tier,
      'hcp': hcp,
      'rounds': rounds,
      'spend': spend,
      'member_number': memberNumber,
      'joined': joined,
    };
  }
}
