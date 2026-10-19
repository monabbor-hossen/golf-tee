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
    );
  }
}
