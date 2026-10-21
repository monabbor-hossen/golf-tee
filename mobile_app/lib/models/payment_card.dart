class PaymentCard {
  final String id;
  final String brand;
  final String last4;
  final String exp;
  final String cardHolder;
  final bool isDefault;

  PaymentCard({
    required this.id,
    required this.brand,
    required this.last4,
    required this.exp,
    this.cardHolder = '',
    this.isDefault = false,
  });

  factory PaymentCard.fromJson(Map<String, dynamic> json) {
    return PaymentCard(
      id: json['id']?.toString() ?? 'c',
      brand: json['brand']?.toString() ?? 'Visa',
      last4: json['last4']?.toString() ?? '4242',
      exp: json['exp']?.toString() ?? '09/27',
      cardHolder: json['card_holder']?.toString() ?? '',
      isDefault: json['isDefault'] == true || json['isDefault'] == 1 || json['isDefault'] == '1',
    );
  }

  PaymentCard copyWith({
    String? id,
    String? brand,
    String? last4,
    String? exp,
    String? cardHolder,
    bool? isDefault,
  }) {
    return PaymentCard(
      id: id ?? this.id,
      brand: brand ?? this.brand,
      last4: last4 ?? this.last4,
      exp: exp ?? this.exp,
      cardHolder: cardHolder ?? this.cardHolder,
      isDefault: isDefault ?? this.isDefault,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'brand': brand,
      'last4': last4,
      'exp': exp,
      'card_holder': cardHolder,
      'isDefault': isDefault,
    };
  }
}
