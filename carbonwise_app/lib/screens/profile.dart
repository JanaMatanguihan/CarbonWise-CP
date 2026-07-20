import 'package:flutter/material.dart';
import 'package:supabase_flutter/supabase_flutter.dart';
import 'package:carbonwise_app/services/api_service.dart';
import 'package:carbonwise_app/screens/edit_profile.dart';
import 'package:carbonwise_app/utils/profile_refresh_notifier.dart';
import 'package:intl/intl.dart';

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

class ProfileScreen extends StatefulWidget {
  const ProfileScreen({super.key});

  @override
  State<ProfileScreen> createState() => _ProfileScreenState();
}

class _ProfileScreenState extends State<ProfileScreen> {
  final ApiService _apiService = ApiService();

  double getAverageEmission() {
    if (last4Weeks.isEmpty) return 0;

    final now = DateTime.now();

    final monthlyRecords = last4Weeks.where((record) {
      final recordDate = DateTime.parse(record["record_date"]);

      return recordDate.year == now.year && recordDate.month == now.month;
    }).toList();

    if (monthlyRecords.isEmpty) return 0;

    double total = 0;

    for (final record in monthlyRecords) {
      total += (record["total_emission"] ?? 0).toDouble();
    }

    return total / monthlyRecords.length;
  }

  String getSustainabilityLevel() {
    final avg = getAverageEmission();

    if (avg <= 5) {
      return "Eco Champion";
    } else if (avg <= 10) {
      return "Green Advocate";
    } else if (avg <= 20) {
      return "Conscious User";
    } else {
      return "Sustainability Builder";
    }
  }

  String getSustainabilityMessage() {
    final avg = getAverageEmission();

    if (avg <= 5) {
      return "Excellent! Your emissions are consistently very low.";
    } else if (avg <= 10) {
      return "Great job! You're maintaining sustainable habits.";
    } else if (avg <= 20) {
      return "You're making progress. Every small action counts.";
    } else {
      return "You're improving. Try lowering this week's emissions.";
    }
  }

  double getSustainabilityProgress() {
    final avg = getAverageEmission();

    if (avg <= 5) return 1.0;
    if (avg <= 10) return 0.75;
    if (avg <= 20) return 0.50;
    return 0.25;
  }

  Color getSustainabilityColor() {
    final avg = getAverageEmission();

    if (avg <= 5) return Colors.green;
    if (avg <= 10) return primaryGreen;
    if (avg <= 20) return Colors.orange;
    return Colors.redAccent;
  }

  IconData getSustainabilityIcon() {
    final avg = getAverageEmission();

    if (avg <= 5) {
      return Icons.emoji_events;
    } else if (avg <= 10) {
      return Icons.eco;
    } else if (avg <= 20) {
      return Icons.energy_savings_leaf;
    } else {
      return Icons.trending_up;
    }
  }

  Map<String, dynamic>? userInfo;
  bool isLoadingProfile = true;

  List<Map<String, dynamic>> recentActivities = [];
  bool isLoadingTimeline = true;

  double carbonScore = 0.0;
  bool isLoadingScore = true;

  double transportationEmission = 0;
  double officeEmission = 0;
  double foodEmission = 0;

  List<DepartmentRanking> _departmentRankings = [];
  String _departmentRank = "";
  String? _userDepartment;

  String _selectedBreakdown = "Transportation";

  String _transportItem = "";
  String _officeItem = "";
  String _foodItem = "";

  IconData get currentIcon {
    switch (_selectedBreakdown) {
      case "Transportation":
        return Icons.directions_bus;

      case "Office Resource":
        return Icons.computer;

      case "Food Consumption":
        return Icons.restaurant;

      default:
        return Icons.eco;
    }
  }

  String get currentTitle {
    switch (_selectedBreakdown) {
      case "Transportation":
        return "Transportation";

      case "Office Resource":
        return "Office Resource";

      case "Food Consumption":
        return "Food Consumption";

      default:
        return "";
    }
  }

  double get currentEmission {
    switch (_selectedBreakdown) {
      case "Transportation":
        return transportationEmission;

      case "Office Resource":
        return officeEmission;

      case "Food Consumption":
        return foodEmission;

      default:
        return 0;
    }
  }

  String _getOrdinal(int number) {
    if (number >= 11 && number <= 13) {
      return "${number}th";
    }

    switch (number % 10) {
      case 1:
        return "${number}st";
      case 2:
        return "${number}nd";
      case 3:
        return "${number}rd";
      default:
        return "${number}th";
    }
  }

  String _weekdayPattern = "Loading...";
  String _highestImpactPattern = "Loading...";
  String _insightPattern = "Loading...";

  List<Map<String, dynamic>> last4Weeks = [];

  bool hasStartedJourney() {
    return carbonScore > 0;
  }

  bool hasEcoCommute() {
    return transportationEmission > 0 && transportationEmission <= 5;
  }

  bool hasGreenStreak() {
    final uniqueDates = <String>{};

    for (final activity in recentActivities) {
      uniqueDates.add(activity["time"]);
    }

    return uniqueDates.length >= 7;
  }

  bool hasEnergySaver() {
    return officeEmission > 0 && officeEmission <= 10;
  }

  bool hasPlantLover() {
    return foodEmission > 0 && foodEmission <= 8;
  }

  bool hasEcoChampion() {
    return carbonScore > 0 && carbonScore <= 20;
  }

  @override
  void initState() {
    super.initState();
    _loadUserInfo();
    _loadCarbonScore();
    _loadRecentActivities();
    _loadDepartmentRankings();
    _loadLast4Weeks();
    _loadPatterns();
    profileRefreshNotifier.addListener(_refreshProfileData);
  }

  @override
  void dispose() {
    profileRefreshNotifier.removeListener(_refreshProfileData);
    super.dispose();
  }

  void _refreshProfileData() {
    if (mounted) {
      _loadUserInfo();
    }
  }

  Future<void> _loadRecentActivities() async {
    try {
      final user = Supabase.instance.client.auth.currentUser;

      if (user == null || user.email == null) return;

      final records = await _apiService.getRecentActivities(user.email!);

      List<Map<String, dynamic>> activities = [];

      for (final record in records) {
        final date = record['record_date']?.toString() ?? '';

        final transportation =
            double.tryParse(record['transportation'].toString()) ?? 0.0;

        final electricity =
            double.tryParse(record['electricity'].toString()) ?? 0.0;

        final food = double.tryParse(record['food'].toString()) ?? 0.0;

        if (transportation > 0) {
          activities.add({
            'category': 'Transportation',
            'title': 'Transportation',
            'impact': '+${transportation.toStringAsFixed(2)} kg',
            'time': date,
            'icon': Icons.directions_bus,
            'color': Colors.blue,
          });
        }

        if (electricity > 0) {
          activities.add({
            'category': 'Office Resource',
            'title': 'Office Resource',
            'impact': '+${electricity.toStringAsFixed(2)} kg',
            'time': date,
            'icon': Icons.wb_incandescent,
            'color': Colors.amber,
          });
        }

        if (food > 0) {
          activities.add({
            'category': 'Food Consumption',
            'title': 'Food Consumption',
            'impact': '+${food.toStringAsFixed(2)} kg',
            'time': date,
            'icon': Icons.flatware,
            'color': Colors.purple,
          });
        }
      }

      setState(() {
        recentActivities = activities.take(3).toList();
        isLoadingTimeline = false;
      });
    } catch (e) {
      print("Timeline Error: $e");

      setState(() {
        isLoadingTimeline = false;
      });
    }
  }

  Future<void> _loadCarbonScore() async {
    final user = Supabase.instance.client.auth.currentUser;

    print("Current user email: ${user?.email}");

    if (user == null || user.email == null) {
      print("No logged in user.");
      return;
    }

    final record = await _apiService.getLatestCarbonScore(user.email!);

    print("Database record: $record");

    setState(() {
      carbonScore = (record?['total_emission'] ?? 0).toDouble();

      transportationEmission = (record?['transportation'] ?? 0).toDouble();
      officeEmission = (record?['electricity'] ?? 0).toDouble();
      foodEmission = (record?['food'] ?? 0).toDouble();

      _transportItem = record?["transport_item"] ?? "";
      _officeItem = record?["office_item"] ?? "";
      _foodItem = record?["food_item"] ?? "";

      isLoadingScore = false;
    });

    print("Carbon Score: $carbonScore");
    print("Transportation: $transportationEmission");
    print("Office Resource: $officeEmission");
    print("Food: $foodEmission");

    print(record);
  }

  Future<void> _loadUserInfo() async {
    final user = Supabase.instance.client.auth.currentUser;

    if (user == null || user.email == null) {
      setState(() {
        isLoadingProfile = false;
      });
      return;
    }

    final response = await _apiService.getUserInfo(user.email!);

    setState(() {
      userInfo = response;
      isLoadingProfile = false;
    });
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
          _departmentRank = _getOrdinal(index + 1);
        }
      }

      setState(() {
        _departmentRankings = rankings;
      });
    } catch (e) {
      print(e);
    }
  }

  Future<void> _loadLast4Weeks() async {
    final user = Supabase.instance.client.auth.currentUser;

    if (user == null) return;

    final data = await _apiService.getLast4WeeksRecords(user.email!);

    setState(() {
      last4Weeks = data.reversed.toList();
    });
  }

  Future<void> _loadPatterns() async {
    final user = Supabase.instance.client.auth.currentUser;

    if (user == null) return;

    final records = await Supabase.instance.client
        .from('carbon_records')
        .select()
        .eq('email', user.email!);

    if (records.isEmpty) {
      setState(() {
        _weekdayPattern = "No emission records yet.";
        _highestImpactPattern = "No emission records yet.";
        _insightPattern = "Start tracking your emissions!";
      });
      return;
    }

    double weekdayTotal = 0;
    double weekendTotal = 0;

    double transportTotal = 0;
    double electricityTotal = 0;
    double foodTotal = 0;

    for (final record in records) {
      final date = DateTime.parse(record['record_date']);

      final transport = (record['transportation'] ?? 0).toDouble();

      final electricity = (record['electricity'] ?? 0).toDouble();

      final food = (record['food'] ?? 0).toDouble();

      transportTotal += transport;
      electricityTotal += electricity;
      foodTotal += food;

      if (date.weekday <= 5) {
        weekdayTotal += transport + electricity + food;
      } else {
        weekendTotal += transport + electricity + food;
      }
    }

    // Highest impact activity
    String highestActivity = "Transportation";
    double highestValue = transportTotal;

    if (electricityTotal > highestValue) {
      highestActivity = "Electricity usage";
      highestValue = electricityTotal;
    }

    if (foodTotal > highestValue) {
      highestActivity = "Food consumption";
      highestValue = foodTotal;
    }

    final totalEmission = transportTotal + electricityTotal + foodTotal;

    final percentage = totalEmission == 0
        ? 0
        : (highestValue / totalEmission * 100);

    setState(() {
      if (weekdayTotal > weekendTotal && weekendTotal > 0) {
        final diff = ((weekdayTotal - weekendTotal) / weekendTotal * 100)
            .round();

        _weekdayPattern =
            "You emit about $diff% more CO₂ on weekdays than weekends.";
      } else if (weekendTotal > weekdayTotal && weekdayTotal > 0) {
        final diff = ((weekendTotal - weekdayTotal) / weekdayTotal * 100)
            .round();

        _weekdayPattern =
            "You emit about $diff% more CO₂ on weekends than weekdays.";
      } else {
        _weekdayPattern =
            "Your weekday and weekend emissions are nearly the same.";
      }

      _highestImpactPattern =
          "Your highest impact activity is $highestActivity.";

      _insightPattern =
          "$highestActivity contributes ${percentage.toStringAsFixed(0)}% of your total emissions.";
    });
  }

  static const Color primaryGreen = Color(0xFF3AA76D);
  static const Color darkGreen = Color(0xFF1E5631);
  static const Color lightBgGrey = Color(0xFFEFEFEF);
  static const Color badgeGrey = Color(0xFFCCEAD8);
  static const Color textMuted = Colors.black54;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: lightBgGrey,
      body: SingleChildScrollView(
        child: Column(
          children: [
            _buildProfileHeader(),
            Padding(
              padding: const EdgeInsets.symmetric(
                horizontal: 12.0,
                vertical: 10.0,
              ),
              child: Column(
                children: [
                  _buildScoreRow(),
                  const SizedBox(height: 12),
                  _buildCarbonBreakdownCard(),
                  const SizedBox(height: 12),
                  _buildAchievementsCard(),
                  const SizedBox(height: 12),
                  _buildDepartmentComparisonCard(),
                  const SizedBox(height: 12),
                  _buildPatternsAndTimelineRow(),
                  const SizedBox(height: 12), // Bottom breathing room
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  // 1. Profile Header
  Widget _buildProfileHeader() {
    final String fullName = userInfo?['full_name'] ?? 'Loading...';
    final String department = userInfo?['department'] ?? '';
    final String campus = userInfo?['campus'] ?? '';
    final String? profilePicture = userInfo?['profile_picture'];

    return Container(
      width: double.infinity,
      padding: const EdgeInsets.only(top: 36, bottom: 24, left: 36, right: 20),
      decoration: const BoxDecoration(
        color: primaryGreen,
        borderRadius: BorderRadius.only(
          bottomLeft: Radius.circular(24),
          bottomRight: Radius.circular(24),
        ),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          CircleAvatar(
            radius: 36,
            backgroundColor: Colors.white,
            backgroundImage: profilePicture != null && profilePicture.isNotEmpty
                ? NetworkImage(
                    "$profilePicture?t=${DateTime.now().millisecondsSinceEpoch}",
                  )
                : null,
            child: profilePicture == null || profilePicture.isEmpty
                ? const Icon(Icons.person, size: 44, color: primaryGreen)
                : null,
          ),

          const SizedBox(width: 16),

          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  isLoadingProfile ? 'Loading...' : fullName,
                  style: const TextStyle(
                    color: Colors.white,
                    fontSize: 19,
                    fontWeight: FontWeight.bold,
                  ),
                ),

                const SizedBox(height: 3),

                Text(
                  isLoadingProfile ? '' : '$department\n$campus',
                  style: TextStyle(
                    color: Colors.white.withValues(alpha: 0.9),
                    fontSize: 12,
                    fontStyle: FontStyle.italic,
                    height: 1.3,
                  ),
                ),

                const SizedBox(height: 12),

                SizedBox(
                  height: 34,
                  child: OutlinedButton.icon(
                    icon: const Icon(Icons.edit, size: 16),
                    label: const Text(
                      "Edit Profile",
                      style: TextStyle(
                        fontSize: 11,
                        fontWeight: FontWeight.bold,
                      ),
                    ),

                    style: OutlinedButton.styleFrom(
                      foregroundColor: Colors.white,
                      side: const BorderSide(color: Colors.white, width: 1),
                      backgroundColor: Colors.white.withValues(alpha: 0.12),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(20),
                      ),
                      padding: const EdgeInsets.symmetric(horizontal: 16),
                    ),

                    onPressed: () async {
                      if (userInfo == null) return;

                      final updated = await showDialog<bool>(
                        context: context,
                        builder: (_) => EditProfileDialog(
                          fullName: userInfo!['full_name'] ?? '',
                          studentNumber: userInfo!['sr_code'] ?? '',
                          email: userInfo!['g_suite'] ?? '',
                          department: userInfo!['department'] ?? '',
                          campus: userInfo!['campus'] ?? '',
                          profilePicture: userInfo!['profile_picture'],
                        ),
                      );

                      if (updated == true) {
                        _loadUserInfo();
                      }

                      if (updated == true) {
                        await _loadUserInfo();
                        await _loadCarbonScore();
                        await _loadRecentActivities();
                      }
                    },
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  // 2. Score & Sustainability Row
  Widget _buildScoreRow() {
    return Row(
      children: [
        Expanded(
          child: _buildMetricCard(
            title: 'Your Carbon Score',
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              mainAxisSize: MainAxisSize.min,
              children: [
                Row(
                  crossAxisAlignment: CrossAxisAlignment.baseline,
                  textBaseline: TextBaseline.alphabetic,
                  children: [
                    Text(
                      isLoadingScore ? '--' : carbonScore.toStringAsFixed(1),
                      style: const TextStyle(
                        fontSize: 32,
                        fontWeight: FontWeight.bold,
                        color: darkGreen,
                      ),
                    ),
                    const SizedBox(width: 4),
                    Flexible(
                      child: Text(
                        'kg CO₂ / week',
                        overflow: TextOverflow.ellipsis,
                        style: TextStyle(
                          fontSize: 11,
                          fontWeight: FontWeight.bold,
                          color: Colors.black.withValues(alpha: 0.7),
                        ),
                      ),
                    ),
                  ],
                ),
                const Text(
                  '12% less than last week',
                  style: TextStyle(
                    color: primaryGreen,
                    fontSize: 10.5,
                    fontStyle: FontStyle.italic,
                    fontWeight: FontWeight.bold,
                  ),
                ),
              ],
            ),
          ),
        ),
        const SizedBox(width: 10),
        Expanded(
          child: _buildMetricCard(
            title: 'Your Sustainability Level',
            child: Row(
              children: [
                Stack(
                  alignment: Alignment.center,
                  children: [
                    SizedBox(
                      width: 36,
                      height: 36,
                      child: CircularProgressIndicator(
                        value: getSustainabilityProgress(),
                        strokeWidth: 4,
                        backgroundColor: Colors.grey.shade200,
                        valueColor: AlwaysStoppedAnimation<Color>(
                          getSustainabilityColor(),
                        ),
                      ),
                    ),

                    Icon(
                      getSustainabilityIcon(),
                      color: getSustainabilityColor(),
                      size: 18,
                    ),
                  ],
                ),

                const SizedBox(width: 10),

                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        getSustainabilityLevel(),
                        style: TextStyle(
                          fontWeight: FontWeight.bold,
                          fontSize: 12,
                          color: getSustainabilityColor(),
                        ),
                      ),

                      const SizedBox(height: 2),

                      Text(
                        getSustainabilityMessage(),
                        maxLines: 2,
                        overflow: TextOverflow.ellipsis,
                        style: const TextStyle(fontSize: 9, color: textMuted),
                      ),

                      const SizedBox(height: 5),

                      Text(
                        "${DateFormat('MMMM').format(DateTime.now())} Average: ${getAverageEmission().toStringAsFixed(1)} kg CO₂",
                        style: const TextStyle(
                          fontSize: 9,
                          fontStyle: FontStyle.italic,
                          color: Colors.grey,
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
        ),
      ],
    );
  }

  // 3. Carbon Emission Breakdown Card
  Widget _buildCarbonBreakdownCard() {
    String currentTitle = "";

    switch (_selectedBreakdown) {
      case "Transportation":
        currentTitle = _transportItem;
        break;

      case "Office Resource":
        currentTitle = _officeItem;
        break;

      case "Food Consumption":
        currentTitle = _foodItem;
        break;
    }

    double maxEmission = 1;

    for (final item in last4Weeks) {
      double value;

      switch (_selectedBreakdown) {
        case "Transportation":
          value = (item["transportation"] ?? 0).toDouble();
          break;

        case "Office Resource":
          value = (item["electricity"] ?? 0).toDouble();
          break;

        case "Food Consumption":
          value = (item["food"] ?? 0).toDouble();
          break;

        default:
          value = (item["total_emission"] ?? 0).toDouble();
      }

      if (value > maxEmission) {
        maxEmission = value;
      }
    }
    return _buildSectionCard(
      title: 'Your Carbon Emission Breakdown',
      child: Column(
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceAround,
            children: [
              _buildSubTab(
                'Transportation',
                _selectedBreakdown == "Transportation",
                onTap: () {
                  setState(() {
                    _selectedBreakdown = "Transportation";
                  });
                },
              ),

              _buildSubTab(
                'Office Resource',
                _selectedBreakdown == "Office Resource",
                onTap: () {
                  setState(() {
                    _selectedBreakdown = "Office Resource";
                  });
                },
              ),

              _buildSubTab(
                'Food Consumption',
                _selectedBreakdown == "Food Consumption",
                onTap: () {
                  setState(() {
                    _selectedBreakdown = "Food Consumption";
                  });
                },
              ),
            ],
          ),
          const Divider(height: 16, thickness: 1),
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Left Content
              Expanded(
                flex: 4,
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text(
                      'This Week',
                      style: TextStyle(
                        fontWeight: FontWeight.bold,
                        fontSize: 12,
                      ),
                    ),
                    const SizedBox(height: 4),
                    Row(
                      crossAxisAlignment: CrossAxisAlignment.baseline,
                      textBaseline: TextBaseline.alphabetic,
                      children: [
                        Text(
                          isLoadingScore
                              ? '--'
                              : currentEmission.toStringAsFixed(2),
                          style: const TextStyle(
                            fontSize: 24,
                            fontWeight: FontWeight.bold,
                            color: primaryGreen,
                          ),
                        ),
                        const SizedBox(width: 2),
                        Text(
                          'kg CO₂',
                          style: TextStyle(
                            fontSize: 11,
                            color: Colors.grey[600],
                          ),
                        ),
                      ],
                    ),
                    Text(
                      isLoadingScore
                          ? 'Loading...'
                          : '${carbonScore.toStringAsFixed(2)} kg CO₂ recorded',
                      style: const TextStyle(
                        fontSize: 10,
                        fontStyle: FontStyle.italic,
                        color: textMuted,
                      ),
                    ),
                    const SizedBox(height: 12),
                    const Text(
                      'Top Contributor',
                      style: TextStyle(
                        fontWeight: FontWeight.bold,
                        fontSize: 12,
                      ),
                    ),
                    const SizedBox(height: 4),
                    Row(
                      children: [
                        Container(
                          padding: const EdgeInsets.all(5),
                          decoration: const BoxDecoration(
                            color: badgeGrey,
                            shape: BoxShape.circle,
                          ),
                          child: Icon(currentIcon, size: 16, color: darkGreen),
                        ),
                        const SizedBox(width: 6),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                currentTitle.isEmpty
                                    ? "No data yet"
                                    : currentTitle,
                                style: const TextStyle(
                                  fontSize: 11,
                                  fontWeight: FontWeight.bold,
                                  overflow: TextOverflow.ellipsis,
                                ),
                              ),
                              Text(
                                'Top tracking impact item.',
                                style: TextStyle(
                                  fontSize: 10,
                                  color: textMuted,
                                ),
                                overflow: TextOverflow.ellipsis,
                              ),
                            ],
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
              // Vertical Divider Line
              Container(
                width: 1,
                height: 120, // Adjusted height match
                color: Colors.black12,
                margin: const EdgeInsets.symmetric(horizontal: 6),
              ),
              // Right Content (Simulated Micro Graph)
              Expanded(
                flex: 5,
                child: Column(
                  children: [
                    const Text(
                      'Recent Records',
                      style: TextStyle(
                        fontWeight: FontWeight.bold,
                        fontSize: 12,
                      ),
                    ),
                    const SizedBox(height: 6),
                    SizedBox(
                      height: 75,
                      child: Row(
                        mainAxisAlignment: MainAxisAlignment.spaceEvenly,
                        crossAxisAlignment: CrossAxisAlignment.end,
                        children: [
                          ...last4Weeks.map((record) {
                            double emission;
                            final date = DateTime.parse(record["record_date"]);
                            final label = "${date.month}/${date.day}";

                            switch (_selectedBreakdown) {
                              case "Transportation":
                                emission = (record["transportation"] ?? 0)
                                    .toDouble();
                                break;

                              case "Office Resource":
                                emission = (record["electricity"] ?? 0)
                                    .toDouble();
                                break;

                              case "Food Consumption":
                                emission = (record["food"] ?? 0).toDouble();
                                break;

                              default:
                                emission = (record["total_emission"] ?? 0)
                                    .toDouble();
                            }

                            return _buildGraphBar(
                              emission.toStringAsFixed(1),
                              (emission / maxEmission).clamp(0.1, 1.0),
                              label,
                            );
                          }),
                        ],
                      ),
                    ),
                    const SizedBox(height: 8),
                    Align(
                      alignment: Alignment.centerRight,
                      child: Text(
                        'View Breakdown Details',
                        style: TextStyle(
                          fontSize: 10,
                          fontWeight: FontWeight.bold,
                          color: darkGreen.withValues(alpha: 0.8),
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  // 4. Achievements Section
  Widget _buildAchievementsCard() {
    return _buildSectionCard(
      title: "Achievements",
      child: GridView.count(
        crossAxisCount: 3,
        shrinkWrap: true,
        physics: const NeverScrollableScrollPhysics(),
        mainAxisSpacing: 8,
        crossAxisSpacing: 8,
        childAspectRatio: .9,
        children: [
          _buildAchievementItem(
            "First Footprint",
            "You've recorded your first carbon activity!",
            Icons.eco,
            hasStartedJourney(),
            "Log your first carbon emission.",
          ),

          _buildAchievementItem(
            "Eco Commuter",
            "Transportation emissions kept below 5 kg CO₂.",
            Icons.directions_bike,
            hasEcoCommute(),
            "Keep transportation emissions below 5 kg CO₂.",
          ),

          _buildAchievementItem(
            "Green Streak",
            "You've logged emissions for 7 different days!",
            Icons.local_fire_department,
            hasGreenStreak(),
            "Log emissions for 7 different days.",
          ),

          _buildAchievementItem(
            "Energy Saver",
            "You kept your office resource emissions low!",
            Icons.bolt,
            hasEnergySaver(),
            "Keep your Office Resource emissions below 10 kg CO₂.",
          ),

          _buildAchievementItem(
            "Plant Lover",
            "Excellent food choices this month!",
            Icons.park,
            hasPlantLover(),
            "Keep your Food Consumption emissions below 8 kg CO₂.",
          ),

          _buildAchievementItem(
            "Eco Champion",
            "Outstanding carbon footprint!",
            Icons.workspace_premium,
            hasEcoChampion(),
            "Keep your total carbon emission below 20 kg CO₂.",
          ),
        ],
      ),
    );
  }

  // 5. Department Comparison Section
  Widget _buildDepartmentComparisonCard() {
    return _buildSectionCard(
      title: 'Department Comparison',
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            width: 110,
            padding: const EdgeInsets.all(10),
            decoration: BoxDecoration(
              color: badgeGrey.withValues(alpha: 0.6),
              borderRadius: BorderRadius.circular(8),
            ),
            child: Column(
              children: [
                const Text(
                  "Your Department\nRank",
                  textAlign: TextAlign.center,
                  style: TextStyle(fontSize: 10, fontWeight: FontWeight.w600),
                ),

                const SizedBox(height: 10),

                Text(
                  _departmentRank.isEmpty ? "-" : _departmentRank,
                  style: TextStyle(
                    fontSize: 24,
                    fontWeight: FontWeight.bold,
                    color: darkGreen,
                  ),
                ),

                const SizedBox(height: 8),

                Text(
                  "Out of ${_departmentRankings.length} departments",
                  textAlign: TextAlign.center,
                  style: TextStyle(fontSize: 9, color: textMuted),
                ),
              ],
            ),
          ),

          const SizedBox(width: 12),

          Expanded(
            child: Column(
              children: [
                SizedBox(
                  height: 220,
                  child: Scrollbar(
                    thumbVisibility: true,
                    child: ListView.builder(
                      itemCount: _departmentRankings.length,
                      itemBuilder: (context, index) {
                        final dept = _departmentRankings[index];

                        return _buildDeptBar(
                          "${index + 1}",
                          dept.department,
                          dept.averageEmission,
                          dept.department == _userDepartment
                              ? primaryGreen
                              : Colors.grey.shade400,
                          isUserDept: dept.department == _userDepartment,
                        );
                      },
                    ),
                  ),
                ),

                const SizedBox(height: 10),

                Container(
                  width: double.infinity,
                  padding: const EdgeInsets.all(8),
                  decoration: BoxDecoration(
                    color: badgeGrey.withValues(alpha: 0.4),
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Row(
                    children: [
                      const Icon(Icons.eco, color: primaryGreen, size: 14),

                      const SizedBox(width: 6),

                      Expanded(
                        child: Text(
                          "Your department is currently ranked ${_departmentRank.isEmpty ? "-" : _departmentRank} out of ${_departmentRankings.length} departments.",
                          style: const TextStyle(fontSize: 10),
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  // 6. Split Row: Patterns & Timeline
  Widget _buildPatternsAndTimelineRow() {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        // Left Box: Patterns
        Expanded(
          child: _buildSectionCard(
            title: 'Your Patterns',
            child: Column(
              children: [
                _buildPatternItem(Icons.calendar_month, _weekdayPattern),
                const Divider(height: 12),
                _buildPatternItem(Icons.directions_bus, _highestImpactPattern),
                const Divider(height: 12),
                _buildPatternItem(Icons.insights, _insightPattern),
              ],
            ),
          ),
        ),
        const SizedBox(width: 10),
        // Right Box: Timeline
        Expanded(
          child: _buildSectionCard(
            title: 'Activity Timeline',
            child: Column(
              children: [
                if (isLoadingTimeline)
                  const Center(
                    child: Padding(
                      padding: EdgeInsets.all(12),
                      child: CircularProgressIndicator(),
                    ),
                  )
                else
                  ...recentActivities.map(
                    (activity) => _buildTimelineItem(
                      activity['category'],
                      activity['title'],
                      activity['impact'],
                      activity['time'],
                      activity['icon'],
                      activity['color'],
                    ),
                  ),

                const SizedBox(height: 4),

                Align(
                  alignment: Alignment.center,
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Text(
                        'Full timeline',
                        style: TextStyle(
                          fontSize: 10,
                          color: darkGreen.withValues(alpha: 0.8),
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                      const SizedBox(width: 2),
                      Icon(
                        Icons.arrow_forward,
                        size: 10,
                        color: darkGreen.withValues(alpha: 0.8),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
        ),
      ],
    );
  }

  // --- REUSABLE UI HELPER METHODS ---

  Widget _buildMetricCard({required String title, required Widget child}) {
    return Container(
      padding: const EdgeInsets.all(10),
      height: 85,
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(6),
        border: Border.all(color: Colors.black12, width: 0.5),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(
            title,
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
            style: const TextStyle(
              fontSize: 11,
              fontWeight: FontWeight.bold,
              color: Colors.black87,
            ),
          ),
          child,
        ],
      ),
    );
  }

  Widget _buildSectionCard({
    required String title,
    Widget? trailing,
    required Widget child,
  }) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(10.0),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(6),
        border: Border.all(color: Colors.black12, width: 0.5),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Expanded(
                child: Text(
                  title,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(
                    fontSize: 13,
                    fontWeight: FontWeight.bold,
                    color: Colors.black,
                  ),
                ),
              ),
              ?trailing,
            ],
          ),
          const SizedBox(height: 8),
          child,
        ],
      ),
    );
  }

  Widget _buildSubTab(
    String label,
    bool selected, {
    required VoidCallback onTap,
  }) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
        decoration: BoxDecoration(
          color: selected ? primaryGreen : Colors.grey.shade200,
          borderRadius: BorderRadius.circular(20),
        ),
        child: Text(
          label,
          style: TextStyle(
            color: selected ? Colors.white : Colors.black87,
            fontSize: 10,
            fontWeight: FontWeight.bold,
          ),
        ),
      ),
    );
  }

  Widget _buildGraphBar(String value, double fillPercent, String dateLabel) {
    return Column(
      mainAxisAlignment: MainAxisAlignment.end,
      children: [
        Text(
          value,
          style: const TextStyle(fontSize: 9, fontWeight: FontWeight.w500),
        ),
        const SizedBox(height: 2),
        Container(
          width: 15,
          height: 45 * fillPercent,
          decoration: BoxDecoration(
            color: primaryGreen,
            borderRadius: BorderRadius.circular(2),
          ),
        ),
        const SizedBox(height: 4),
        Text(
          dateLabel,
          textAlign: TextAlign.center,
          style: const TextStyle(fontSize: 8, color: textMuted, height: 1.1),
        ),
      ],
    );
  }

  Widget _buildAchievementItem(
    String title,
    String unlockedDescription,
    IconData icon,
    bool unlocked,
    String unlockRequirement,
  ) {
    return InkWell(
      borderRadius: BorderRadius.circular(12),

      onTap: () {
        showDialog(
          context: context,
          builder: (_) {
            return AlertDialog(
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(18),
              ),

              title: Row(
                children: [
                  Icon(
                    unlocked ? Icons.emoji_events : Icons.lock,
                    color: unlocked ? primaryGreen : Colors.grey,
                  ),

                  const SizedBox(width: 10),

                  Expanded(child: Text(title)),
                ],
              ),

              content: Text(unlocked ? unlockedDescription : unlockRequirement),

              actions: [
                TextButton(
                  onPressed: () {
                    Navigator.pop(context);
                  },
                  child: const Text("OK"),
                ),
              ],
            );
          },
        );
      },

      child: AnimatedOpacity(
        duration: const Duration(milliseconds: 250),
        opacity: unlocked ? 1 : .5,

        child: Container(
          decoration: BoxDecoration(
            color: unlocked ? const Color(0xFFF6FFF8) : Colors.grey.shade100,

            borderRadius: BorderRadius.circular(12),

            border: Border.all(
              color: unlocked
                  ? primaryGreen.withValues(alpha: .3)
                  : Colors.grey.shade300,
            ),
          ),

          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,

            children: [
              Stack(
                alignment: Alignment.bottomRight,
                children: [
                  CircleAvatar(
                    radius: 24,
                    backgroundColor: unlocked
                        ? const Color(0xFFE8F5E9)
                        : Colors.white,

                    child: Icon(
                      icon,
                      color: unlocked ? primaryGreen : Colors.grey,
                    ),
                  ),

                  if (!unlocked)
                    const CircleAvatar(
                      radius: 8,
                      backgroundColor: Colors.white,
                      child: Icon(Icons.lock, size: 10, color: Colors.grey),
                    ),
                ],
              ),

              const SizedBox(height: 8),

              Text(
                title,
                textAlign: TextAlign.center,
                style: TextStyle(
                  fontWeight: FontWeight.bold,
                  fontSize: 11,
                  color: unlocked ? darkGreen : Colors.grey,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildDeptBar(
    String rank,
    String label,
    double score,
    Color color, {
    bool isUserDept = false,
  }) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 5),
      child: Row(
        children: [
          SizedBox(
            width: 18,
            child: Text(
              rank,
              style: const TextStyle(fontSize: 11, fontWeight: FontWeight.bold),
            ),
          ),

          const SizedBox(width: 4),

          Expanded(
            flex: 3,
            child: Text(
              label,
              maxLines: 2,
              overflow: TextOverflow.ellipsis,
              style: TextStyle(
                fontSize: 10,
                fontWeight: isUserDept ? FontWeight.bold : FontWeight.normal,
                color: isUserDept ? primaryGreen : Colors.black87,
              ),
            ),
          ),

          const SizedBox(width: 8),

          Expanded(
            flex: 5,
            child: Stack(
              alignment: Alignment.centerLeft,
              children: [
                Container(
                  height: 7,
                  decoration: BoxDecoration(
                    color: Colors.grey[200],
                    borderRadius: BorderRadius.circular(4),
                  ),
                ),

                FractionallySizedBox(
                  widthFactor: (score / 60).clamp(0.0, 1.0),
                  child: Container(
                    height: 7,
                    decoration: BoxDecoration(
                      color: color,
                      borderRadius: BorderRadius.circular(4),
                    ),
                  ),
                ),
              ],
            ),
          ),

          const SizedBox(width: 8),

          SizedBox(
            width: 58,
            child: Text(
              "${score.toStringAsFixed(2)} kg",
              textAlign: TextAlign.right,
              style: TextStyle(
                fontSize: 10,
                fontWeight: isUserDept ? FontWeight.bold : FontWeight.normal,
                color: isUserDept ? primaryGreen : Colors.black54,
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildPatternItem(IconData icon, String text) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 2.0),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            padding: const EdgeInsets.all(4),
            decoration: const BoxDecoration(
              color: badgeGrey,
              shape: BoxShape.circle,
            ),
            child: Icon(icon, size: 14, color: darkGreen),
          ),
          const SizedBox(width: 6),
          Expanded(
            child: Text(
              text,
              maxLines: 4,
              overflow: TextOverflow.ellipsis,
              style: const TextStyle(
                fontSize: 10,
                height: 1.2,
                fontWeight: FontWeight.w500,
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildTimelineItem(
    String cat,
    String title,
    String impact,
    String time,
    IconData icon,
    Color iconColor,
  ) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Column(
          children: [
            Container(
              padding: const EdgeInsets.all(4),
              decoration: BoxDecoration(
                color: iconColor.withValues(alpha: 0.15),
                shape: BoxShape.circle,
              ),
              child: Icon(icon, size: 12, color: iconColor),
            ),
            Container(
              width: 1,
              height: 26,
              color: Colors.black12,
            ), // Adjusted height spacer
          ],
        ),
        const SizedBox(width: 6),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Expanded(
                    child: Text(
                      cat,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(
                        fontSize: 10,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                  ),
                  Text(
                    impact,
                    style: TextStyle(
                      fontSize: 9.5,
                      color: Colors.grey[600],
                      fontWeight: FontWeight.w500,
                    ),
                  ),
                ],
              ),
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Expanded(
                    child: Text(
                      title,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(fontSize: 9, color: textMuted),
                    ),
                  ),
                  const SizedBox(width: 4),
                  Text(
                    time,
                    style: const TextStyle(fontSize: 8, color: Colors.black26),
                  ),
                ],
              ),
            ],
          ),
        ),
      ],
    );
  }
}
