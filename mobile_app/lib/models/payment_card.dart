class PaymentCard {
  final String id;
  final String brand;
  final String last4;
  final String exp;
  final bool isDefault;

  PaymentCard({
    required this.id,
    required this.brand,
    required this.last4,
    required this.exp,
    this.isDefault = false,
  });

  factory PaymentCard.fromJson(Map<String, dynamic> json) {
    return PaymentCard(
      id: json['id']?.toString() ?? 'c',
      brand: json['brand']?.toString() ?? 'Visa',
      last4: json['last4']?.toString() ?? '4242',
      exp: json['exp']?.toString() ?? '09/27',
      isDefault: json['isDefault'] == true,
    );
  }

  PaymentCard copyWith({
    String? id,
    String? brand,
    String? last4,
    String? exp,
    bool? isDefault,
  }) {
    return PaymentCard(
      id: id ?? this.id,
      brand: brand ?? this.brand,
      last4: last4 ?? this.last4,
      exp: exp ?? this.exp,
      isDefault: isDefault ?? this.isDefault,
    );
  }
}

class CourseCondition {
  final String weather;
  final String weatherDesc;
  final String wind;
  final String windDesc;
  final String greenSpeed;
  final String speedDesc;
  final String carts;
  final String cartsDesc;
  final String updatedAt;
  final String location;
  final String humidity;
  final bool isLive;
  final double? lat;
  final double? lng;
  final String gpsFormatted;
  final bool isGps;

  CourseCondition({
    this.weather = '68°',
    this.weatherDesc = 'Sunny',
    this.wind = '6 mph',
    this.windDesc = 'NW wind',
    this.greenSpeed = '10.5',
    this.speedDesc = 'Green speed',
    this.carts = 'Open',
    this.cartsDesc = 'Carts',
    this.updatedAt = 'Updated 7:45 AM',
    this.location = 'Pine Valley, NJ',
    this.humidity = '45%',
    this.isLive = false,
    this.lat,
    this.lng,
    this.gpsFormatted = '',
    this.isGps = false,
  });

  factory CourseCondition.fromJson(Map<String, dynamic> json) {
    return CourseCondition(
      weather: json['weather']?.toString() ?? '68°',
      weatherDesc: json['weather_desc']?.toString() ?? 'Sunny',
      wind: json['wind']?.toString() ?? '6 mph',
      windDesc: json['wind_desc']?.toString() ?? 'NW wind',
      greenSpeed: json['green_speed']?.toString() ?? '10.5',
      speedDesc: json['speed_desc']?.toString() ?? 'Green speed',
      carts: json['carts']?.toString() ?? 'Open',
      cartsDesc: json['carts_desc']?.toString() ?? 'Carts',
      updatedAt: json['updated_at']?.toString() ?? 'Updated 7:45 AM',
      location: json['location']?.toString() ?? 'Pine Valley, NJ',
      humidity: json['humidity']?.toString() ?? '45%',
      isLive: json['is_live'] == true || json['is_live'] == 1 || json['is_live'] == '1',
      lat: json['lat'] != null ? (json['lat'] as num).toDouble() : null,
      lng: json['lng'] != null ? (json['lng'] as num).toDouble() : null,
      gpsFormatted: json['gps_formatted']?.toString() ?? '',
      isGps: json['is_gps'] == true || json['is_gps'] == 1 || json['is_gps'] == '1',
    );
  }
}
