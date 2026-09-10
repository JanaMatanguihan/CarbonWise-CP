import 'package:flutter/material.dart';
import 'package:carbonwise_app/services/api_service.dart';

class DepartmentRanking {
  final String department;
  final double totalEmission;
  final int totalRecords;

  DepartmentRanking({
    required this.department,
    required this.totalEmission,
    required this.totalRecords,
  });
}

class CampusRanking {
  final String campus;
  final double totalEmission;
  final int totalRecords;

  CampusRanking({
    required this.campus,
    required this.totalEmission,
    required this.totalRecords,
  });
}

class DashboardScreen extends StatefulWidget {
  const DashboardScreen({super.key});

  @override
  State<DashboardScreen> createState() => _DashboardScreenState();
}

class _DashboardScreenState extends State<DashboardScreen> {
  final ApiService _apiService = ApiService();
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

  double _toDouble(dynamic value) =>
      double.tryParse(value?.toString() ?? '0') ?? 0.0;

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
    try {
      final now = DateTime.now();
      final month = '${now.year}-${now.month.toString().padLeft(2, '0')}';

      final rankingsData = await _apiService.getDepartmentRankings(
        month: month,
      );

      final rankings = rankingsData
          .map<DepartmentRanking>((item) {
            final map = Map<String, dynamic>.from(item as Map);

            return DepartmentRanking(
              department: map['department']?.toString() ?? '',
              totalEmission: _toDouble(map['total_emission']),
              totalRecords:
                  int.tryParse(map['total_records']?.toString() ?? '0') ?? 0,
            );
          })
          .where((r) => r.department.isNotEmpty)
          .toList();

      rankings.sort((a, b) => a.totalEmission.compareTo(b.totalEmission));
      final user = await _apiService.getUserProfile();
      final myDepartment = user['department']?.toString() ?? '';
      final index = rankings.indexWhere((r) => r.department == myDepartment);

      if (!mounted) return;
      setState(() {
        _departmentRankings = rankings;
        _userDepartment = myDepartment;
        _departmentRank = index >= 0
            ? '${index + 1}${_getOrdinal(index + 1)}'
            : '-';
      });
    } catch (e) {
      print('Department Ranking Error: $e');
    }
  }

  Future<void> _loadCurrentRanking() async {
    try {
      final summary = await _apiService.getDashboardSummary();
      if (!mounted) return;
      setState(() {
        _currentRanking = summary['current_ranking']?.toString() ?? '—';
        _currentRankingDescription =
            summary['current_ranking_description']?.toString() ?? '';
      });
    } catch (e) {
      print('Current Ranking Error: $e');
    }
  }

  Future<void> _loadIndividualStatus() async {
    try {
      final records = await _apiService.getCarbonRecords('');
      final now = DateTime.now();
      final startOfWeek = DateTime(
        now.year,
        now.month,
        now.day,
      ).subtract(Duration(days: now.weekday - 1));

      double transport = 0;
      double office = 0;
      double food = 0;
      for (final raw in records) {
        final record = Map<String, dynamic>.from(raw as Map);
        final date = DateTime.tryParse(record['record_date']?.toString() ?? '');
        if (date == null || date.isBefore(startOfWeek)) continue;
        transport += _toDouble(record['transportation']);
        office += _toDouble(record['electricity']);
        food += _toDouble(record['food']);
      }

      if (!mounted) return;
      setState(() {
        transportEmission = transport;
        officeEmission = office;
        foodEmission = food;
      });
    } catch (e) {
      print('Individual Status Error: $e');
    }
  }

  Future<void> _loadCampusRankings() async {
    try {
      final now = DateTime.now();
      final month = '${now.year}-${now.month.toString().padLeft(2, '0')}';

      final rankingsData = await _apiService.getCampusRankings(month: month);

      final rankings = rankingsData
          .map<CampusRanking>((item) {
            final map = Map<String, dynamic>.from(item as Map);

            return CampusRanking(
              campus: map['campus']?.toString() ?? '',
              totalEmission: _toDouble(map['total_emission']),
              totalRecords:
                  int.tryParse(map['total_records']?.toString() ?? '0') ?? 0,
            );
          })
          .where((r) => r.campus.isNotEmpty)
          .toList();

      rankings.sort((a, b) => a.totalEmission.compareTo(b.totalEmission));

      final user = await _apiService.getUserProfile();
      final myCampus = user['campus']?.toString() ?? '';

      final index = rankings.indexWhere((r) => r.campus == myCampus);

      if (!mounted) return;

      setState(() {
        _campusRankings = rankings;
        _userCampus = myCampus;
        _campusRank = index >= 0
            ? '${index + 1}${_getOrdinal(index + 1)}'
            : '-';
      });
    } catch (e) {
      print('Campus Ranking Error: $e');
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
              "See how departments compare in total carbon emissions.",
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
                                  emission: dept.totalEmission,
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
              "kg CO₂e",
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
