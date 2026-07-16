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

        _userDepartment = userInfo['department'];

        final index = rankings.indexWhere(
          (d) => d.department == _userDepartment,
        );

        if (index != -1) {
          _departmentRank = "${index + 1}${_getOrdinal(index + 1)}";
        }
      }

      setState(() {
        _departmentRankings = rankings;
      });
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

      final currentUser = supabase.auth.currentUser;

      if (currentUser != null) {
        final myCampus = userCampuses[currentUser.email];

        final campusIndex = rankings.indexWhere((c) => c.campus == myCampus);

        setState(() {
          _userCampus = myCampus ?? "";

          if (campusIndex != -1) {
            _campusRank = "${campusIndex + 1}${_getOrdinal(campusIndex + 1)}";
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
            Row(
              children: [
                Expanded(
                  child: _buildRankingCard(
                    title: 'Your Current\nRanking',
                    icon: Icons.recycling_outlined,
                    badgeText: _currentRanking,
                    description: _currentRankingDescription,
                  ),
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: _buildRankingCard(
                    title: 'Department\nRanking',
                    icon: Icons.school_outlined,
                    badgeText: _departmentRank,
                    description: '$_userDepartment Department',
                  ),
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: _buildRankingCard(
                    title: 'Campus\nRanking',
                    icon: Icons.business_outlined,
                    badgeText: _campusRank,
                    description:
                        '$_userCampus is ranked among all BatStateU campuses.',
                  ),
                ),
              ],
            ),

            const SizedBox(height: 20),
            const Divider(color: Colors.black26, thickness: 1),
            const SizedBox(height: 12),

            // =========================
            // CHART SECTION
            // =========================

            // Individual Status Section
            _buildStatusCard(
              title: "Individual Status",
              child: Column(
                children: [
                  _buildEmissionBar("🚗 Transportation", transportEmission),

                  const SizedBox(height: 15),

                  _buildEmissionBar("🏢 Office Resource", officeEmission),

                  const SizedBox(height: 15),

                  _buildEmissionBar("🍽️ Food Consumption", foodEmission),
                ],
              ),
            ),

            const SizedBox(height: 16),

            // Department Ranking Section
            _buildStatusCard(
              title: "Department Ranking",
              child: SizedBox(
                height: 260,
                child: Scrollbar(
                  controller: _departmentScrollController,
                  thumbVisibility: true,
                  radius: const Radius.circular(20),
                  child: ListView.builder(
                    physics: const BouncingScrollPhysics(),
                    itemCount: _departmentRankings.length,
                    controller: _departmentScrollController,
                    itemBuilder: (context, index) {
                      final dept = _departmentRankings[index];

                      IconData medal;
                      Color medalColor;

                      switch (index) {
                        case 0:
                          medal = Icons.workspace_premium;
                          medalColor = Colors.amber;
                          break;

                        case 1:
                          medal = Icons.workspace_premium;
                          medalColor = Colors.grey;
                          break;

                        case 2:
                          medal = Icons.workspace_premium;
                          medalColor = const Color(0xFFCD7F32);
                          break;

                        default:
                          medal = Icons.eco;
                          medalColor = const Color(0xFF3AA76D);
                      }

                      return Container(
                        margin: const EdgeInsets.only(bottom: 8),
                        padding: const EdgeInsets.symmetric(
                          horizontal: 12,
                          vertical: 10,
                        ),
                        decoration: BoxDecoration(
                          color: const Color(0xFFF8FBF9),
                          borderRadius: BorderRadius.circular(12),
                          border: Border.all(color: Colors.green.shade100),
                        ),
                        child: Row(
                          children: [
                            Icon(medal, color: medalColor, size: 22),

                            const SizedBox(width: 10),

                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text(
                                    dept.department,
                                    style: const TextStyle(
                                      fontWeight: FontWeight.bold,
                                      fontSize: 14,
                                    ),
                                  ),

                                  const SizedBox(height: 2),

                                  Text(
                                    "Average Carbon Emission",
                                    style: TextStyle(
                                      fontSize: 10,
                                      color: Colors.grey.shade600,
                                    ),
                                  ),

                                  Text(
                                    "${dept.totalRecords} record(s)",
                                    style: TextStyle(
                                      fontSize: 9,
                                      color: Colors.green.shade700,
                                    ),
                                  ),
                                ],
                              ),
                            ),

                            Container(
                              padding: const EdgeInsets.symmetric(
                                horizontal: 12,
                                vertical: 8,
                              ),
                              decoration: BoxDecoration(
                                color: const Color(0xFF265D3B),
                                borderRadius: BorderRadius.circular(20),
                              ),
                              child: Text(
                                "${dept.averageEmission.toStringAsFixed(2)} kg CO₂e",
                                style: const TextStyle(
                                  color: Colors.white,
                                  fontWeight: FontWeight.bold,
                                  fontSize: 11,
                                ),
                              ),
                            ),
                          ],
                        ),
                      );
                    },
                  ),
                ),
              ),
            ),

            const SizedBox(height: 20),
            const Divider(color: Colors.black26, thickness: 1),
            const SizedBox(height: 16),

            // =========================
            // GOING GREEN INITIATIVES
            // =========================
            Container(
              width: double.infinity,
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(12),
                boxShadow: [
                  BoxShadow(
                    color: Colors.black.withValues(alpha: 0.05),
                    blurRadius: 6,
                    offset: const Offset(0, 2),
                  ),
                ],
              ),
              child: Column(
                children: [
                  Row(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: const [
                      Icon(Icons.campaign, color: Color(0xFF265D3B)),
                      SizedBox(width: 8),
                      Text(
                        'Going Green Initiatives',
                        style: TextStyle(
                          color: Color(0xFF265D3B),
                          fontWeight: FontWeight.bold,
                          fontSize: 16,
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 10),
                  const Text(
                    'What: The Great Green Clean-Up Drive\n'
                    'When: Friday, June 19, 2026 | 8:00 AM - 12:00 PM\n'
                    'Where: Assembly point at the Campus Facade\n'
                    'What to bring: A reusable water bottle!',
                    textAlign: TextAlign.center,
                    style: TextStyle(
                      fontSize: 13,
                      fontWeight: FontWeight.w600,
                      color: Colors.black87,
                      height: 1.4,
                    ),
                  ),
                ],
              ),
            ),

            const SizedBox(height: 16),
          ],
        ),
      ),
    );
  }

  // =========================
  // RANKING CARD WIDGET
  // =========================
  Widget _buildRankingCard({
    required String title,
    required IconData icon,
    required String badgeText,
    required String description,
  }) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 12),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(10),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.05),
            blurRadius: 4,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Column(
        children: [
          Text(
            title,
            textAlign: TextAlign.center,
            style: const TextStyle(
              fontSize: 11,
              fontWeight: FontWeight.bold,
              color: Colors.black87,
            ),
          ),
          const SizedBox(height: 8),
          Icon(icon, color: const Color(0xFF3AA76D), size: 28),
          const SizedBox(height: 6),
          Text(
            badgeText,
            style: const TextStyle(
              fontSize: 15,
              fontWeight: FontWeight.bold,
              color: Color(0xFF265D3B),
            ),
          ),
          const SizedBox(height: 4),
          Text(
            description,
            textAlign: TextAlign.center,
            maxLines: 2,
            overflow: TextOverflow.ellipsis,
            style: const TextStyle(fontSize: 8, color: Colors.black54),
          ),
        ],
      ),
    );
  }

  Widget _buildEmissionBar(String label, double value) {
    final maxValue = [
      transportEmission,
      officeEmission,
      foodEmission,
      1.0,
    ].reduce((a, b) => a > b ? a : b);

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            Text(label, style: const TextStyle(fontWeight: FontWeight.w600)),

            Text(
              "${value.toStringAsFixed(2)} kg",
              style: const TextStyle(
                color: Color(0xFF265D3B),
                fontWeight: FontWeight.bold,
              ),
            ),
          ],
        ),

        const SizedBox(height: 6),

        ClipRRect(
          borderRadius: BorderRadius.circular(10),
          child: LinearProgressIndicator(
            value: value / maxValue,
            minHeight: 14,
            backgroundColor: Colors.green.shade100,
            valueColor: const AlwaysStoppedAnimation(Color(0xFF3AA76D)),
          ),
        ),
      ],
    );
  }

  // =========================
  // STATUS CARD WIDGET
  // =========================
  Widget _buildStatusCard({required String title, required Widget child}) {
    return Container(
      padding: const EdgeInsets.all(10),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(10),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.05),
            blurRadius: 4,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            title,
            style: const TextStyle(
              fontSize: 13,
              fontWeight: FontWeight.bold,
              color: Colors.black87,
            ),
          ),
          const SizedBox(height: 10),
          child,
        ],
      ),
    );
  }
}
