import 'package:flutter/material.dart';
import 'package:supabase_flutter/supabase_flutter.dart';

class DepartmentRanking {
  final String department;
  final double averageEmission;
  final int totalRecords;

  DepartmentRanking({
    required this.department,
    required this.averageEmission,
    required this.totalRecords,
  });
}

class CampusRanking {
  final String campus;
  final double averageEmission;
  final int totalRecords;

  CampusRanking({
    required this.campus,
    required this.averageEmission,
    required this.totalRecords,
  });
}

class DashboardScreen extends StatefulWidget {
  const DashboardScreen({super.key});

  @override
  State<DashboardScreen> createState() => _DashboardScreenState();
}

class _DashboardScreenState extends State<DashboardScreen> {
  List<DepartmentRanking> _departmentRankings = [];
  final ScrollController _departmentScrollController = ScrollController();
  double transportEmission = 0;
  double officeEmission = 0;
  double foodEmission = 0;
  String _departmentRank = "-";
  String _userDepartment = "";
  String _currentRanking = "Loading...";
  String _currentRankingDescription = "";
  String _campusRank = "-";
  String _userCampus = "";

  List<CampusRanking> _campusRankings = [];

  @override
  void initState() {
    super.initState();
    _loadDepartmentRankings();
    _loadIndividualStatus();
    _loadCurrentRanking();
    _loadCampusRankings();
  }

  @override
  void dispose() {
    _departmentScrollController.dispose();
    super.dispose();
  }

  Future<void> _loadDepartmentRankings() async {
    final supabase = Supabase.instance.client;

    try {
      final users = await supabase
          .from('user_info')
          .select('g_suite, department');

      final now = DateTime.now();

      final firstDayOfMonth = DateTime(now.year, now.month, 1);

      final records = await supabase
          .from('carbon_records')
          .select('g_suite, total_emission, record_date')
          .gte(
            'record_date',
            firstDayOfMonth.toIso8601String().split('T').first,
          );

      Map<String, String> userDepartments = {};

      for (var user in users) {
        userDepartments[user['g_suite']] = user['department'];
      }

      Map<String, List<double>> departmentTotals = {};

      for (var record in records) {
        final email = record['g_suite'];
        final emission = (record['total_emission'] as num?)?.toDouble() ?? 0;

        final department = userDepartments[email];

        if (department == null) continue;

        departmentTotals.putIfAbsent(department, () => []);
        departmentTotals[department]!.add(emission);
      }

      List<DepartmentRanking> rankings = [];

      departmentTotals.forEach((department, emissions) {
        final average = emissions.reduce((a, b) => a + b) / emissions.length;

        rankings.add(
          DepartmentRanking(
            department: department,
            averageEmission: average,
            totalRecords: emissions.length,
          ),
        );
      });

      rankings.sort((a, b) => a.averageEmission.compareTo(b.averageEmission));

      final user = supabase.auth.currentUser;

      if (user != null) {
        final userInfo = await supabase
            .from('user_info')
            .select('department')
            .eq('g_suite', user.email!)
            .single();

        final myDepartment = userInfo['department'];

        final index = rankings.indexWhere((d) => d.department == myDepartment);

        setState(() {
          _departmentRankings = rankings;
          _userDepartment = myDepartment;

          if (index != -1) {
            _departmentRank = "${index + 1}${_getOrdinal(index + 1)}";
          } else {
            _departmentRank = "-";
          }
        });
      }
    } catch (e) {
      print(e);
    }
  }

  Future<void> _loadCurrentRanking() async {
    print("Loading Current Ranking...");

    try {
      final supabase = Supabase.instance.client;
      final user = supabase.auth.currentUser;

      print("Current user: ${user?.email}");

      if (user == null) return;

      final now = DateTime.now();

      final firstDayOfMonth = DateTime(now.year, now.month, 1);

      final records = await supabase
          .from('carbon_records')
          .select('g_suite, total_emission, record_date')
          .gte(
            'record_date',
            firstDayOfMonth.toIso8601String().split('T').first,
          );

      print("Records:");
      print(records);
    } catch (e) {
      print("Current Ranking Error:");
      print(e);
    }
  }

  Future<void> _loadIndividualStatus() async {
    final supabase = Supabase.instance.client;
    final user = supabase.auth.currentUser;

    if (user == null) return;

    print("Current user: ${user.email}");

    final now = DateTime.now();
    final startOfWeek = now.subtract(Duration(days: now.weekday - 1));

    print("Start of week: ${startOfWeek.toIso8601String().split('T').first}");

    final records = await supabase
        .from('carbon_records')
        .select('transportation, electricity, food, record_date, g_suite')
        .eq('g_suite', user.email!)
        .gte('record_date', startOfWeek.toIso8601String().split('T').first);

    print("Records found: ${records.length}");
    Map<String, double> userTotals = {};

    for (final record in records) {
      final email = record['g_suite'];

      if (email == null) continue;

      final emission = (record['total_emission'] as num?)?.toDouble() ?? 0;

      userTotals[email] = (userTotals[email] ?? 0) + emission;
    }

    print(userTotals);

    final rankings = userTotals.entries.toList();

    rankings.sort((a, b) => a.value.compareTo(b.value));

    print(rankings);

    final userIndex = rankings.indexWhere((entry) => entry.key == user.email);

    print("User Rank Index: $userIndex");

    final totalUsers = rankings.length;

    final topPercent = (((userIndex + 1) / totalUsers) * 100).ceil();

    String description;

    if (topPercent <= 5) {
      description = "Outstanding! You're among the greenest users this month.";
    } else if (topPercent <= 10) {
      description =
          "Excellent! You're among the lowest carbon emitters this month.";
    } else if (topPercent <= 25) {
      description = "Great job! You're doing better than most users.";
    } else if (topPercent <= 50) {
      description = "You're on the right track. Keep reducing your emissions!";
    } else {
      description =
          "Every small action counts. Keep improving your sustainability habits!";
    }

    setState(() {
      _currentRanking = "Top $topPercent%";
      _currentRankingDescription = description;
    });

    double transport = 0;
    double office = 0;
    double food = 0;

    for (final record in records) {
      transport += (record['transportation'] as num?)?.toDouble() ?? 0;
      office += (record['electricity'] as num?)?.toDouble() ?? 0;
      food += (record['food'] as num?)?.toDouble() ?? 0;
    }

    print("Transport: $transport");
    print("Office: $office");
    print("Food: $food");

    setState(() {
      transportEmission = transport;
      officeEmission = office;
      foodEmission = food;
    });
  }

  Future<void> _loadCampusRankings() async {
    final supabase = Supabase.instance.client;

    try {
      final users = await supabase.from('user_info').select('g_suite, campus');

      final now = DateTime.now();

      final firstDayOfMonth = DateTime(now.year, now.month, 1);

      final records = await supabase
          .from('carbon_records')
          .select('g_suite, total_emission, record_date')
          .gte(
            'record_date',
            firstDayOfMonth.toIso8601String().split('T').first,
          );

      Map<String, String> userCampuses = {};

      for (var user in users) {
        userCampuses[user['g_suite']] = user['campus'];
      }

      Map<String, List<double>> campusTotals = {};

      for (var record in records) {
        final email = record['g_suite'];

        if (email == null) continue;

        final emission = (record['total_emission'] as num?)?.toDouble() ?? 0;

        final campus = userCampuses[email];

        if (campus == null) continue;

        campusTotals.putIfAbsent(campus, () => []);
        campusTotals[campus]!.add(emission);
      }

      List<CampusRanking> rankings = [];

      campusTotals.forEach((campus, emissions) {
        final average = emissions.reduce((a, b) => a + b) / emissions.length;

        rankings.add(
          CampusRanking(
            campus: campus,
            averageEmission: average,
            totalRecords: emissions.length,
          ),
        );
      });

      rankings.sort((a, b) => a.averageEmission.compareTo(b.averageEmission));

      print("Campus Totals:");
      print(campusTotals);

      print("Campus Rankings:");
      print(rankings.length);

      final currentUser = supabase.auth.currentUser;

      if (currentUser != null) {
        final userInfo = await supabase
            .from('user_info')
            .select('campus')
            .eq('g_suite', currentUser.email!)
            .single();

        final myCampus = userInfo['campus'];

        final campusIndex = rankings.indexWhere((c) => c.campus == myCampus);

        setState(() {
          _campusRankings = rankings;
          _userCampus = myCampus;

          if (campusIndex != -1) {
            _campusRank = "${campusIndex + 1}${_getOrdinal(campusIndex + 1)}";
          } else {
            _campusRank = "-";
          }
        });
      }
    } catch (e) {
      print("Campus Ranking Error:");
      print(e);
    }
  }

  String _getOrdinal(int number) {
    if (number % 100 >= 11 && number % 100 <= 13) {
      return "th";
    }

    switch (number % 10) {
      case 1:
        return "st";
      case 2:
        return "nd";
      case 3:
        return "rd";
      default:
        return "th";
    }
  }

  @override
  Widget build(BuildContext context) {
    return SafeArea(
      child: SingleChildScrollView(
        physics: const BouncingScrollPhysics(),
        padding: const EdgeInsets.symmetric(horizontal: 16.0, vertical: 8.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const SizedBox(height: 10),

            // =========================
            // TOP RANKING CARDS
            // =========================
            const SizedBox(height: 12),

            const Text(
              "Your Impact",
              style: TextStyle(
                fontSize: 22,
                fontWeight: FontWeight.bold,
                color: Colors.black87,
              ),
            ),

            const SizedBox(height: 4),

            Text(
              "See how your carbon footprint compares.",
              style: TextStyle(fontSize: 13, color: Colors.grey.shade600),
            ),

            const SizedBox(height: 16),

            // =========================
            // YOUR CURRENT RANKING
            // =========================
            _buildFeaturedRankingCard(),

            const SizedBox(height: 12),

            // =========================
            // DEPARTMENT + CAMPUS
            // =========================
            Row(
              children: [
                Expanded(
                  child: _buildRankingCard(
                    title: 'Department Ranking',
                    icon: Icons.school_outlined,
                    badgeText: _departmentRank,
                    description: _userDepartment.isEmpty
                        ? 'Loading department...'
                        : _userDepartment,
                  ),
                ),

                const SizedBox(width: 12),

                Expanded(
                  child: _buildRankingCard(
                    title: 'Campus Ranking',
                    icon: Icons.business_outlined,
                    badgeText: _campusRank,
                    description: _userCampus.isEmpty
                        ? 'Loading campus...'
                        : _userCampus,
                  ),
                ),
              ],
            ),

            const SizedBox(height: 24),

            const SizedBox(height: 20),

            // =========================
            // CHART SECTION
            // =========================

            // Individual Status Section
            const Text(
              "Today's Footprint",
              style: TextStyle(
                fontSize: 20,
                fontWeight: FontWeight.bold,
                color: Colors.black87,
              ),
            ),

            const SizedBox(height: 4),

            Text(
              "Your carbon emissions by category.",
              style: TextStyle(fontSize: 13, color: Colors.grey.shade600),
            ),

            const SizedBox(height: 14),

            Container(
              width: double.infinity,
              padding: const EdgeInsets.all(18),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(22),
                boxShadow: [
                  BoxShadow(
                    color: Colors.black.withValues(alpha: 0.04),
                    blurRadius: 12,
                    offset: const Offset(0, 4),
                  ),
                ],
              ),
              child: Column(
                children: [
                  _buildFootprintItem(
                    icon: Icons.directions_car_outlined,
                    title: "Transportation",
                    value: transportEmission,
                  ),

                  const SizedBox(height: 18),

                  _buildFootprintItem(
                    icon: Icons.business_outlined,
                    title: "Office Resource",
                    value: officeEmission,
                  ),

                  const SizedBox(height: 18),

                  _buildFootprintItem(
                    icon: Icons.restaurant_outlined,
                    title: "Food Consumption",
                    value: foodEmission,
                  ),
                ],
              ),
            ),

            const SizedBox(height: 28),

            const SizedBox(height: 16),

            // Department Ranking Section
            // =========================
            // DEPARTMENT RANKING
            // =========================
            const Text(
              "Department Rankings",
              style: TextStyle(
                fontSize: 20,
                fontWeight: FontWeight.bold,
                color: Colors.black87,
              ),
            ),

            const SizedBox(height: 4),

            Text(
              "See which departments are making the biggest impact.",
              style: TextStyle(fontSize: 13, color: Colors.grey.shade600),
            ),

            const SizedBox(height: 14),

            Container(
              width: double.infinity,
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(22),
                boxShadow: [
                  BoxShadow(
                    color: Colors.black.withValues(alpha: 0.04),
                    blurRadius: 12,
                    offset: const Offset(0, 4),
                  ),
                ],
              ),
              child: _departmentRankings.isEmpty
                  ? const Center(
                      child: Padding(
                        padding: EdgeInsets.all(20),
                        child: Text(
                          "No department rankings available yet.",
                          style: TextStyle(color: Colors.black54, fontSize: 13),
                        ),
                      ),
                    )
                  : Column(
                      children: [
                        ..._departmentRankings
                            .take(5)
                            .toList()
                            .asMap()
                            .entries
                            .map((entry) {
                              final index = entry.key;
                              final dept = entry.value;

                              return Padding(
                                padding: EdgeInsets.only(
                                  bottom:
                                      index ==
                                          _departmentRankings.take(5).length - 1
                                      ? 0
                                      : 12,
                                ),
                                child: _buildDepartmentRankingItem(
                                  rank: index + 1,
                                  department: dept.department,
                                  emission: dept.averageEmission,
                                  records: dept.totalRecords,
                                ),
                              );
                            }),
                      ],
                    ),
            ),

            const SizedBox(height: 28),

            const SizedBox(height: 28),

            // =========================
            // GOING GREEN INITIATIVES
            // =========================
            const Text(
              "Going Green",
              style: TextStyle(
                fontSize: 20,
                fontWeight: FontWeight.bold,
                color: Colors.black87,
              ),
            ),

            const SizedBox(height: 4),

            Text(
              "Join upcoming activities and make a difference.",
              style: TextStyle(fontSize: 13, color: Colors.grey.shade600),
            ),

            const SizedBox(height: 14),

            Container(
              width: double.infinity,
              padding: const EdgeInsets.all(18),
              decoration: BoxDecoration(
                color: const Color(0xFF265D3B),
                borderRadius: BorderRadius.circular(24),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Top icon + label
                  Container(
                    width: 44,
                    height: 44,
                    decoration: BoxDecoration(
                      color: Colors.white.withValues(alpha: 0.15),
                      shape: BoxShape.circle,
                    ),
                    child: const Icon(
                      Icons.eco_outlined,
                      color: Colors.white,
                      size: 24,
                    ),
                  ),

                  const SizedBox(height: 18),

                  const Text(
                    "The Great Green\nClean-Up Drive",
                    style: TextStyle(
                      color: Colors.white,
                      fontSize: 21,
                      fontWeight: FontWeight.bold,
                      height: 1.2,
                    ),
                  ),

                  const SizedBox(height: 8),

                  Text(
                    "Join the campus community and help make our surroundings cleaner and greener.",
                    style: TextStyle(
                      color: Colors.white.withValues(alpha: 0.78),
                      fontSize: 12,
                      height: 1.4,
                    ),
                  ),

                  const SizedBox(height: 20),

                  _buildGreenEventDetail(
                    Icons.calendar_today_outlined,
                    "Friday, June 19, 2026",
                  ),

                  const SizedBox(height: 12),

                  _buildGreenEventDetail(
                    Icons.access_time_outlined,
                    "8:00 AM – 12:00 PM",
                  ),

                  const SizedBox(height: 12),

                  _buildGreenEventDetail(
                    Icons.location_on_outlined,
                    "Campus Facade",
                  ),

                  const SizedBox(height: 18),

                  Row(
                    mainAxisAlignment: MainAxisAlignment.end,
                    children: const [
                      Text(
                        "Bring a reusable water bottle",
                        style: TextStyle(
                          color: Colors.white70,
                          fontSize: 11,
                          fontStyle: FontStyle.italic,
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),

            const SizedBox(height: 24),
          ],
        ),
      ),
    );
  }

  Widget _buildRankingCard({
    required String title,
    required IconData icon,
    required String badgeText,
    required String description,
  }) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 14),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(18),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.04),
            blurRadius: 10,
            offset: const Offset(0, 3),
          ),
        ],
      ),
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          // Icon circle
          Container(
            width: 42,
            height: 42,
            decoration: const BoxDecoration(
              color: Color(0xFFE8F5EC),
              shape: BoxShape.circle,
            ),
            child: Icon(icon, color: Color(0xFF3AA76D), size: 22),
          ),

          const SizedBox(height: 10),

          // Ranking
          FittedBox(
            fit: BoxFit.scaleDown,
            child: Text(
              badgeText,
              style: const TextStyle(
                fontSize: 18,
                fontWeight: FontWeight.bold,
                color: Color(0xFF265D3B),
              ),
            ),
          ),

          const SizedBox(height: 4),

          // Title
          Text(
            title,
            textAlign: TextAlign.center,
            maxLines: 2,
            overflow: TextOverflow.ellipsis,
            style: const TextStyle(
              fontSize: 10,
              fontWeight: FontWeight.w600,
              color: Colors.black87,
              height: 1.2,
            ),
          ),

          const SizedBox(height: 5),

          // Description
          Text(
            description,
            textAlign: TextAlign.center,
            maxLines: 2,
            overflow: TextOverflow.ellipsis,
            style: const TextStyle(
              fontSize: 8,
              color: Colors.black54,
              height: 1.2,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildFeaturedRankingCard() {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: const Color(0xFF265D3B),
        borderRadius: BorderRadius.circular(24),
      ),
      child: Row(
        children: [
          Container(
            width: 52,
            height: 52,
            decoration: BoxDecoration(
              color: Colors.white.withValues(alpha: 0.15),
              shape: BoxShape.circle,
            ),
            child: const Icon(
              Icons.workspace_premium_outlined,
              color: Colors.white,
              size: 28,
            ),
          ),

          const SizedBox(width: 16),

          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text(
                  'Your Current Ranking',
                  style: TextStyle(
                    color: Colors.white70,
                    fontSize: 13,
                    fontWeight: FontWeight.w500,
                  ),
                ),

                const SizedBox(height: 4),

                FittedBox(
                  fit: BoxFit.scaleDown,
                  alignment: Alignment.centerLeft,
                  child: Text(
                    _currentRanking,
                    style: const TextStyle(
                      color: Colors.white,
                      fontSize: 30,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                ),

                const SizedBox(height: 4),

                Text(
                  _currentRankingDescription,
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                  style: TextStyle(
                    color: Colors.white.withValues(alpha: 0.75),
                    fontSize: 11,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildFootprintItem({
    required IconData icon,
    required String title,
    required double value,
  }) {
    return Row(
      children: [
        Container(
          width: 42,
          height: 42,
          decoration: const BoxDecoration(
            color: Color(0xFFE8F5EC),
            shape: BoxShape.circle,
          ),
          child: Icon(icon, color: const Color(0xFF3AA76D), size: 21),
        ),

        const SizedBox(width: 12),

        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                title,
                style: const TextStyle(
                  fontSize: 13,
                  fontWeight: FontWeight.w600,
                  color: Colors.black87,
                ),
              ),

              const SizedBox(height: 5),

              ClipRRect(
                borderRadius: BorderRadius.circular(10),
                child: LinearProgressIndicator(
                  value: (value / 100).clamp(0.0, 1.0),
                  minHeight: 7,
                  backgroundColor: const Color(0xFFF0F2F1),
                  valueColor: const AlwaysStoppedAnimation<Color>(
                    Color(0xFF3AA76D),
                  ),
                ),
              ),
            ],
          ),
        ),

        const SizedBox(width: 12),

        Column(
          crossAxisAlignment: CrossAxisAlignment.end,
          children: [
            Text(
              value.toStringAsFixed(2),
              style: const TextStyle(
                fontSize: 15,
                fontWeight: FontWeight.bold,
                color: Color(0xFF265D3B),
              ),
            ),
            const Text(
              "kg CO₂e",
              style: TextStyle(fontSize: 9, color: Colors.black54),
            ),
          ],
        ),
      ],
    );
  }

  Widget _buildDepartmentRankingItem({
    required int rank,
    required String department,
    required double emission,
    required int records,
  }) {
    IconData icon;
    Color iconColor;
    Color backgroundColor;

    switch (rank) {
      case 1:
        icon = Icons.workspace_premium;
        iconColor = Colors.amber;
        backgroundColor = const Color(0xFFFFF8E1);
        break;

      case 2:
        icon = Icons.workspace_premium;
        iconColor = Colors.grey;
        backgroundColor = const Color(0xFFF3F4F6);
        break;

      case 3:
        icon = Icons.workspace_premium;
        iconColor = const Color(0xFFCD7F32);
        backgroundColor = const Color(0xFFFFF3E8);
        break;

      default:
        icon = Icons.eco_outlined;
        iconColor = const Color(0xFF3AA76D);
        backgroundColor = const Color(0xFFEFF8F2);
    }

    return Row(
      children: [
        // Rank
        Container(
          width: 38,
          height: 38,
          decoration: BoxDecoration(
            color: backgroundColor,
            shape: BoxShape.circle,
          ),
          child: Center(
            child: rank <= 3
                ? Icon(icon, color: iconColor, size: 21)
                : Text(
                    "#$rank",
                    style: TextStyle(
                      color: iconColor,
                      fontSize: 13,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
          ),
        ),

        const SizedBox(width: 12),

        // Department information
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                department,
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: const TextStyle(
                  fontSize: 14,
                  fontWeight: FontWeight.w600,
                  color: Colors.black87,
                ),
              ),

              const SizedBox(height: 3),

              Text(
                "$records record(s)",
                style: TextStyle(fontSize: 11, color: Colors.grey.shade600),
              ),
            ],
          ),
        ),

        const SizedBox(width: 8),

        // Emission
        Column(
          crossAxisAlignment: CrossAxisAlignment.end,
          children: [
            Text(
              emission.toStringAsFixed(2),
              style: const TextStyle(
                fontSize: 14,
                fontWeight: FontWeight.bold,
                color: Color(0xFF265D3B),
              ),
            ),
            const Text(
              "kg CO₂e avg.",
              style: TextStyle(fontSize: 9, color: Colors.black54),
            ),
          ],
        ),
      ],
    );
  }

  Widget _buildGreenEventDetail(IconData icon, String text) {
    return Row(
      children: [
        Icon(icon, color: Colors.white.withValues(alpha: 0.85), size: 18),

        const SizedBox(width: 10),

        Expanded(
          child: Text(
            text,
            style: TextStyle(
              color: Colors.white.withValues(alpha: 0.9),
              fontSize: 13,
              fontWeight: FontWeight.w500,
            ),
          ),
        ),
      ],
    );
  }
}
