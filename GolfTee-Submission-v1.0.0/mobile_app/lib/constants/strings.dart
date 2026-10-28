class AppStrings {
  static const String courseName = 'Pine Valley GC';
  static const String courseFullName = 'Pine Valley Golf Club';

  static const String imgHero =
      'https://images.unsplash.com/photo-1758190153146-a1507e2e000d?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&q=80&w=1080';
  static const String imgTwilight =
      'https://images.unsplash.com/photo-1786375013010-51e39f91be0b?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&q=80&w=1080';
  static const String imgNews =
      'https://images.unsplash.com/photo-1761141987961-ed96a841fe21?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&q=80&w=600';

  static const List<int> pars = [
    4, 5, 3, 4, 4, 4, 3, 5, 4, 4, 3, 5, 4, 4, 3, 4, 5, 4
  ];

  static const List<int> yards = [
    412, 528, 178, 395, 441, 367, 165, 545, 420, 404, 190, 512, 388, 430, 155, 376, 560, 445
  ];

  static const List<String> friends = [
    'Mina Lee',
    'Raj Patel',
    'Daniel Brown',
    'Priya Shah',
    'Lucia Rossi'
  ];

  static const List<Map<String, dynamic>> addons = [
    {
      'id': 'cart',
      'label': 'Golf cart',
      'desc': 'Per player',
      'price': 20.0,
      'perPlayer': true,
    },
    {
      'id': 'clubs',
      'label': 'Club rental',
      'desc': 'Premium set',
      'price': 35.0,
      'perPlayer': false,
    },
    {
      'id': 'balls',
      'label': 'Range balls',
      'desc': 'Large bucket',
      'price': 8.0,
      'perPlayer': false,
    },
  ];
}
