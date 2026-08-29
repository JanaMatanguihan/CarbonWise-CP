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

    try {
      final response = await Supabase.instance.client
          .from('user_info')
          .select()
          .eq('g_suite', user.email!)
          .single();

      setState(() {
        userInfo = response;
        isLoadingProfile = false;
      });
    } catch (e) {
      print("Profile Loading Error: $e");

      setState(() {
        isLoadingProfile = false;
      });
    }
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
      last4Weeks = data.reversed
          .map((e) => Map<String, dynamic>.from(e))
          .toList();
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
    final String role = userInfo?['role'] ?? '';

    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 24, 16, 8),
      child: Column(
        children: [
          // PAGE TITLE
          const Align(
            alignment: Alignment.centerLeft,
            child: Text(
              'Profile',
              style: TextStyle(
                fontSize: 26,
                fontWeight: FontWeight.bold,
                color: Color(0xFF1F2933),
              ),
            ),
          ),

          const SizedBox(height: 24),

          // PROFILE CARD
          Container(
            width: double.infinity,
            padding: const EdgeInsets.fromLTRB(20, 24, 20, 20),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(24),
              border: Border.all(color: const Color(0xFFE5EEE8)),
            ),
            child: Column(
              children: [
                // PROFILE PICTURE
                CircleAvatar(
                  radius: 44,
                  backgroundColor: const Color(0xFFE8F5EE),
                  backgroundImage:
                      profilePicture != null && profilePicture.isNotEmpty
                      ? NetworkImage(
                          '$profilePicture?t=${DateTime.now().millisecondsSinceEpoch}',
                        )
                      : null,
                  child: profilePicture == null || profilePicture.isEmpty
                      ? const Icon(
                          Icons.person_rounded,
                          size: 52,
                          color: primaryGreen,
                        )
                      : null,
                ),

                const SizedBox(height: 14),

                // NAME
                Text(
                  isLoadingProfile ? 'Loading...' : fullName,
                  textAlign: TextAlign.center,
                  style: const TextStyle(
                    fontSize: 20,
                    fontWeight: FontWeight.bold,
                    color: Color(0xFF1F2933),
                  ),
                ),

                const SizedBox(height: 5),

                // DEPARTMENT
                if (!isLoadingProfile && (role.isNotEmpty || campus.isNotEmpty))
                  Row(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      if (role.isNotEmpty)
                        Flexible(
                          child: Text(
                            role,
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                            style: const TextStyle(
                              fontSize: 13,
                              color: Colors.black54,
                            ),
                          ),
                        ),

                      if (role.isNotEmpty && campus.isNotEmpty)
                        const Text(
                          '  |  ',
                          style: TextStyle(fontSize: 13, color: Colors.black38),
                        ),

                      if (campus.isNotEmpty)
                        Flexible(
                          child: Text(
                            campus,
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                            style: const TextStyle(
                              fontSize: 13,
                              color: Colors.black54,
                            ),
                          ),
                        ),
                    ],
                  ),

                const SizedBox(height: 3),

                if (!isLoadingProfile && department.isNotEmpty)
                  Text(
                    department,
                    textAlign: TextAlign.center,
                    style: const TextStyle(fontSize: 12, color: Colors.black45),
                  ),

                // EDIT PROFILE BUTTON
                SizedBox(
                  width: double.infinity,
                  child: OutlinedButton.icon(
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
                        await _loadUserInfo();
                        await _loadCarbonScore();
                        await _loadRecentActivities();
                      }
                    },
                    icon: const Icon(Icons.edit_outlined, size: 18),
                    label: const Text(
                      'Edit Profile',
                      style: TextStyle(
                        fontSize: 13,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                    style: OutlinedButton.styleFrom(
                      foregroundColor: primaryGreen,
                      side: const BorderSide(color: primaryGreen),
                      padding: const EdgeInsets.symmetric(vertical: 12),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(14),
                      ),
                    ),
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  // 2. Sustainability Summary
  Widget _buildScoreRow() {
    final sustainabilityColor = getSustainabilityColor();

    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: const Color(0xFFE5EEE8)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text(
            'YOUR SUSTAINABILITY',
            style: TextStyle(
              fontSize: 11,
              fontWeight: FontWeight.bold,
              letterSpacing: 1.1,
              color: textMuted,
            ),
          ),

          const SizedBox(height: 16),

          // CARBON SCORE
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Container(
                width: 46,
                height: 46,
                decoration: const BoxDecoration(
                  color: Color(0xFFE8F5EE),
                  shape: BoxShape.circle,
                ),
                child: const Icon(
                  Icons.eco_rounded,
                  color: primaryGreen,
                  size: 23,
                ),
              ),

              const SizedBox(width: 12),

              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text(
                      'Carbon Score',
                      style: TextStyle(
                        fontSize: 13,
                        fontWeight: FontWeight.w600,
                        color: Color(0xFF1F2933),
                      ),
                    ),

                    const SizedBox(height: 3),

                    Wrap(
                      crossAxisAlignment: WrapCrossAlignment.center,
                      spacing: 4,
                      children: [
                        Text(
                          isLoadingScore
                              ? '--'
                              : carbonScore.toStringAsFixed(1),
                          style: const TextStyle(
                            fontSize: 25,
                            fontWeight: FontWeight.bold,
                            color: darkGreen,
                          ),
                        ),

                        const Text(
                          'kg CO₂ / week',
                          style: TextStyle(fontSize: 11, color: textMuted),
                        ),
                      ],
                    ),

                    const SizedBox(height: 3),

                    const Text(
                      '12% less than last week',
                      style: TextStyle(
                        color: primaryGreen,
                        fontSize: 11,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),

          const Padding(
            padding: EdgeInsets.symmetric(vertical: 16),
            child: Divider(height: 1, color: Color(0xFFE5EEE8)),
          ),

          // SUSTAINABILITY LEVEL
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Stack(
                alignment: Alignment.center,
                children: [
                  SizedBox(
                    width: 46,
                    height: 46,
                    child: CircularProgressIndicator(
                      value: getSustainabilityProgress(),
                      strokeWidth: 4,
                      backgroundColor: const Color(0xFFE8EDE9),
                      valueColor: AlwaysStoppedAnimation<Color>(
                        sustainabilityColor,
                      ),
                    ),
                  ),

                  Icon(
                    getSustainabilityIcon(),
                    color: sustainabilityColor,
                    size: 21,
                  ),
                ],
              ),

              const SizedBox(width: 12),

              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text(
                      'Sustainability Level',
                      style: TextStyle(
                        fontSize: 13,
                        fontWeight: FontWeight.w600,
                        color: Color(0xFF1F2933),
                      ),
                    ),

                    const SizedBox(height: 3),

                    Text(
                      getSustainabilityLevel(),
                      style: TextStyle(
                        fontSize: 17,
                        fontWeight: FontWeight.bold,
                        color: sustainabilityColor,
                      ),
                    ),

                    const SizedBox(height: 2),

                    Text(
                      getSustainabilityMessage(),
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(fontSize: 11, color: textMuted),
                    ),

                    const SizedBox(height: 5),

                    Text(
                      "${DateFormat('MMMM').format(DateTime.now())} Average: "
                      "${getAverageEmission().toStringAsFixed(1)} kg CO₂",
                      style: const TextStyle(
                        fontSize: 10,
                        fontStyle: FontStyle.italic,
                        color: Colors.grey,
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

    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: const Color(0xFFE5EEE8)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text(
            'CARBON BREAKDOWN',
            style: TextStyle(
              fontSize: 11,
              fontWeight: FontWeight.bold,
              letterSpacing: 1.1,
              color: textMuted,
            ),
          ),

          const SizedBox(height: 14),

          // CATEGORY TABS
          SingleChildScrollView(
            scrollDirection: Axis.horizontal,
            child: Row(
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

                const SizedBox(width: 8),

                _buildSubTab(
                  'Office Resource',
                  _selectedBreakdown == "Office Resource",
                  onTap: () {
                    setState(() {
                      _selectedBreakdown = "Office Resource";
                    });
                  },
                ),

                const SizedBox(width: 8),

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
          ),

          const SizedBox(height: 18),

          // THIS WEEK
          Container(
            width: double.infinity,
            padding: const EdgeInsets.all(14),
            decoration: BoxDecoration(
              color: const Color(0xFFF6FAF7),
              borderRadius: BorderRadius.circular(14),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text(
                  'THIS WEEK',
                  style: TextStyle(
                    fontSize: 10,
                    fontWeight: FontWeight.bold,
                    letterSpacing: 0.8,
                    color: textMuted,
                  ),
                ),

                const SizedBox(height: 6),

                Wrap(
                  crossAxisAlignment: WrapCrossAlignment.center,
                  spacing: 4,
                  children: [
                    Text(
                      isLoadingScore
                          ? '--'
                          : currentEmission.toStringAsFixed(2),
                      style: const TextStyle(
                        fontSize: 28,
                        fontWeight: FontWeight.bold,
                        color: primaryGreen,
                      ),
                    ),

                    const Text(
                      'kg CO₂',
                      style: TextStyle(fontSize: 12, color: textMuted),
                    ),
                  ],
                ),

                const SizedBox(height: 3),

                Text(
                  isLoadingScore
                      ? 'Loading your emission data...'
                      : '${carbonScore.toStringAsFixed(2)} kg CO₂ recorded',
                  style: const TextStyle(
                    fontSize: 11,
                    fontStyle: FontStyle.italic,
                    color: textMuted,
                  ),
                ),
              ],
            ),
          ),

          const SizedBox(height: 14),

          // TOP CONTRIBUTOR
          Row(
            children: [
              Container(
                width: 42,
                height: 42,
                decoration: const BoxDecoration(
                  color: Color(0xFFE8F5EE),
                  shape: BoxShape.circle,
                ),
                child: Icon(currentIcon, color: darkGreen, size: 20),
              ),

              const SizedBox(width: 10),

              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text(
                      'Top Contributor',
                      style: TextStyle(
                        fontSize: 11,
                        fontWeight: FontWeight.w600,
                        color: textMuted,
                      ),
                    ),

                    const SizedBox(height: 2),

                    Text(
                      currentTitle.isEmpty ? "No data yet" : currentTitle,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(
                        fontSize: 14,
                        fontWeight: FontWeight.bold,
                        color: Color(0xFF1F2933),
                      ),
                    ),

                    const SizedBox(height: 2),

                    const Text(
                      'Your highest-impact tracked item.',
                      style: TextStyle(fontSize: 10, color: textMuted),
                    ),
                  ],
                ),
              ),
            ],
          ),

          const Padding(
            padding: EdgeInsets.symmetric(vertical: 16),
            child: Divider(height: 1, color: Color(0xFFE5EEE8)),
          ),

          // RECENT RECORDS
          const Text(
            'RECENT RECORDS',
            style: TextStyle(
              fontSize: 10,
              fontWeight: FontWeight.bold,
              letterSpacing: 0.8,
              color: textMuted,
            ),
          ),

          const SizedBox(height: 6),

          SizedBox(
            height: 65,
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceEvenly,
              crossAxisAlignment: CrossAxisAlignment.end,
              children: [
                ...last4Weeks.reversed.take(4).toList().reversed.map((record) {
                  double emission;

                  final date = DateTime.parse(record["record_date"]);
                  final label = "${date.month}/${date.day}";

                  switch (_selectedBreakdown) {
                    case "Transportation":
                      emission = (record["transportation"] ?? 0).toDouble();
                      break;

                    case "Office Resource":
                      emission = (record["electricity"] ?? 0).toDouble();
                      break;

                    case "Food Consumption":
                      emission = (record["food"] ?? 0).toDouble();
                      break;

                    default:
                      emission = (record["total_emission"] ?? 0).toDouble();
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
        ],
      ),
    );
  }

  // 4. Achievements Section
  Widget _buildAchievementsCard() {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.symmetric(vertical: 18),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: const Color(0xFFE5EEE8)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Padding(
            padding: EdgeInsets.symmetric(horizontal: 18),
            child: Text(
              'ACHIEVEMENTS',
              style: TextStyle(
                fontSize: 11,
                fontWeight: FontWeight.bold,
                letterSpacing: 1.1,
                color: textMuted,
              ),
            ),
          ),

          const SizedBox(height: 14),

          SizedBox(
            height: 155,
            child: ListView(
              scrollDirection: Axis.horizontal,
              padding: const EdgeInsets.symmetric(horizontal: 18),
              children: [
                _buildAchievementItem(
                  "First Footprint",
                  "You've recorded your first carbon activity!",
                  Icons.eco_rounded,
                  hasStartedJourney(),
                  "Log your first carbon emission.",
                ),

                const SizedBox(width: 10),

                _buildAchievementItem(
                  "Eco Commuter",
                  "Transportation emissions kept below 5 kg CO₂.",
                  Icons.directions_bike_rounded,
                  hasEcoCommute(),
                  "Keep transportation emissions below 5 kg CO₂.",
                ),

                const SizedBox(width: 10),

                _buildAchievementItem(
                  "Green Streak",
                  "You've logged emissions for 7 different days!",
                  Icons.local_fire_department_rounded,
                  hasGreenStreak(),
                  "Log emissions for 7 different days.",
                ),

                const SizedBox(width: 10),

                _buildAchievementItem(
                  "Energy Saver",
                  "You kept your office resource emissions low!",
                  Icons.bolt_rounded,
                  hasEnergySaver(),
                  "Keep your Office Resource emissions below 10 kg CO₂.",
                ),

                const SizedBox(width: 10),

                _buildAchievementItem(
                  "Plant Lover",
                  "Excellent food choices this month!",
                  Icons.park_rounded,
                  hasPlantLover(),
                  "Keep your Food Consumption emissions below 8 kg CO₂.",
                ),

                const SizedBox(width: 10),

                _buildAchievementItem(
                  "Eco Champion",
                  "Outstanding carbon footprint!",
                  Icons.workspace_premium_rounded,
                  hasEcoChampion(),
                  "Keep your total carbon emission below 20 kg CO₂.",
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  // 5. Department Comparison Section
  Widget _buildDepartmentComparisonCard() {
    final int displayCount = _departmentRankings.length > 5
        ? 5
        : _departmentRankings.length;

    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: const Color(0xFFE5EEE8)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text(
            'DEPARTMENT RANKING',
            style: TextStyle(
              fontSize: 11,
              fontWeight: FontWeight.bold,
              letterSpacing: 1.1,
              color: textMuted,
            ),
          ),

          const SizedBox(height: 18),

          // YOUR RANK SUMMARY
          Center(
            child: Column(
              children: [
                Container(
                  width: 52,
                  height: 52,
                  decoration: const BoxDecoration(
                    color: Color(0xFFE8F5EE),
                    shape: BoxShape.circle,
                  ),
                  child: const Icon(
                    Icons.workspace_premium_rounded,
                    color: primaryGreen,
                    size: 28,
                  ),
                ),

                const SizedBox(height: 8),

                Text(
                  _departmentRank.isEmpty ? '-' : _departmentRank,
                  style: const TextStyle(
                    fontSize: 30,
                    fontWeight: FontWeight.bold,
                    color: darkGreen,
                  ),
                ),

                const SizedBox(height: 2),

                Text(
                  'out of ${_departmentRankings.length} departments',
                  style: const TextStyle(fontSize: 11, color: textMuted),
                ),
              ],
            ),
          ),

          const SizedBox(height: 18),

          const Divider(height: 1, color: Color(0xFFE5EEE8)),

          const SizedBox(height: 16),

          const Text(
            'DEPARTMENT STANDINGS',
            style: TextStyle(
              fontSize: 10,
              fontWeight: FontWeight.bold,
              letterSpacing: 0.8,
              color: textMuted,
            ),
          ),

          const SizedBox(height: 10),

          // TOP DEPARTMENTS
          ..._departmentRankings
              .take(displayCount)
              .toList()
              .asMap()
              .entries
              .map((entry) {
                final index = entry.key;
                final dept = entry.value;

                final isUserDept = dept.department == _userDepartment;

                return _buildDeptBar(
                  '${index + 1}',
                  dept.department,
                  dept.averageEmission,
                  isUserDept ? primaryGreen : Colors.grey.shade400,
                  isUserDept: isUserDept,
                );
              }),

          if (_departmentRankings.length > displayCount) ...[
            const SizedBox(height: 4),

            Center(
              child: TextButton.icon(
                onPressed: () {
                  _showAllDepartmentRankings();
                },
                icon: const Icon(Icons.leaderboard_outlined, size: 16),
                label: const Text('View all departments'),
                style: TextButton.styleFrom(
                  foregroundColor: primaryGreen,
                  textStyle: const TextStyle(
                    fontSize: 12,
                    fontWeight: FontWeight.bold,
                  ),
                ),
              ),
            ),
          ],
        ],
      ),
    );
  }

  // 6. Patterns & Activity Timeline
  Widget _buildPatternsAndTimelineRow() {
    return Column(
      children: [
        // YOUR PATTERNS
        _buildSectionCard(
          title: 'Your Patterns',
          child: Column(
            children: [
              _buildPatternItem(Icons.calendar_month_rounded, _weekdayPattern),

              const Divider(height: 20),

              _buildPatternItem(
                Icons.directions_bus_rounded,
                _highestImpactPattern,
              ),

              const Divider(height: 20),

              _buildPatternItem(Icons.insights_rounded, _insightPattern),
            ],
          ),
        ),

        const SizedBox(height: 14),

        // ACTIVITY TIMELINE
        _buildSectionCard(
          title: 'Recent Activity',
          child: Column(
            children: [
              if (isLoadingTimeline)
                const Padding(
                  padding: EdgeInsets.all(20),
                  child: Center(
                    child: CircularProgressIndicator(color: primaryGreen),
                  ),
                )
              else if (recentActivities.isEmpty)
                const Padding(
                  padding: EdgeInsets.symmetric(vertical: 20),
                  child: Column(
                    children: [
                      Icon(Icons.history_rounded, size: 38, color: textMuted),

                      SizedBox(height: 8),

                      Text(
                        'No recent activity',
                        style: TextStyle(
                          fontSize: 13,
                          fontWeight: FontWeight.bold,
                          color: Color(0xFF1F2933),
                        ),
                      ),

                      SizedBox(height: 4),

                      Text(
                        'Your carbon activities will appear here.',
                        textAlign: TextAlign.center,
                        style: TextStyle(fontSize: 11, color: textMuted),
                      ),
                    ],
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

              if (!isLoadingTimeline && recentActivities.isNotEmpty) ...[
                const SizedBox(height: 8),

                const Divider(),

                const SizedBox(height: 4),

                InkWell(
                  borderRadius: BorderRadius.circular(10),
                  onTap: () {
                    _showFullTimeline();
                  },
                  child: Padding(
                    padding: const EdgeInsets.symmetric(vertical: 8),
                    child: Row(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Text(
                          'View full timeline',
                          style: TextStyle(
                            fontSize: 12,
                            color: darkGreen.withValues(alpha: 0.85),
                            fontWeight: FontWeight.bold,
                          ),
                        ),

                        const SizedBox(width: 5),

                        Icon(
                          Icons.arrow_forward_rounded,
                          size: 15,
                          color: darkGreen.withValues(alpha: 0.85),
                        ),
                      ],
                    ),
                  ),
                ),
              ],
            ],
          ),
        ),
      ],
    );
  }

  // --- REUSABLE UI HELPER METHODS ---

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
      mainAxisSize: MainAxisSize.min,
      mainAxisAlignment: MainAxisAlignment.end,
      children: [
        Text(
          value,
          style: const TextStyle(fontSize: 10, fontWeight: FontWeight.w500),
        ),

        const SizedBox(height: 1),

        Container(
          width: 14,
          height: 32 * fillPercent,
          decoration: BoxDecoration(
            color: primaryGreen,
            borderRadius: BorderRadius.circular(3),
          ),
        ),

        const SizedBox(height: 3),

        Text(
          dateLabel,
          textAlign: TextAlign.center,
          style: const TextStyle(fontSize: 10, color: textMuted, height: 1),
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
    return SizedBox(
      width: 125,
      child: InkWell(
        borderRadius: BorderRadius.circular(16),

        onTap: () {
          showDialog(
            context: context,
            builder: (_) {
              return AlertDialog(
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(20),
                ),

                title: Row(
                  children: [
                    Container(
                      width: 38,
                      height: 38,
                      decoration: BoxDecoration(
                        color: unlocked
                            ? const Color(0xFFE8F5EE)
                            : Colors.grey.shade200,
                        shape: BoxShape.circle,
                      ),
                      child: Icon(
                        unlocked
                            ? Icons.emoji_events_rounded
                            : Icons.lock_outline_rounded,
                        color: unlocked ? primaryGreen : Colors.grey,
                        size: 20,
                      ),
                    ),

                    const SizedBox(width: 10),

                    Expanded(
                      child: Text(
                        title,
                        style: const TextStyle(
                          fontSize: 16,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                    ),
                  ],
                ),

                content: Text(
                  unlocked ? unlockedDescription : unlockRequirement,
                  style: const TextStyle(fontSize: 13, height: 1.4),
                ),

                actions: [
                  TextButton(
                    onPressed: () {
                      Navigator.pop(context);
                    },
                    child: const Text(
                      "Got it",
                      style: TextStyle(
                        color: primaryGreen,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                  ),
                ],
              );
            },
          );
        },

        child: AnimatedOpacity(
          duration: const Duration(milliseconds: 250),
          opacity: unlocked ? 1 : 0.55,

          child: Container(
            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 14),
            decoration: BoxDecoration(
              color: unlocked ? const Color(0xFFF8FCF9) : Colors.grey.shade100,

              borderRadius: BorderRadius.circular(16),

              border: Border.all(
                color: unlocked
                    ? primaryGreen.withValues(alpha: 0.25)
                    : Colors.grey.shade300,
              ),
            ),

            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Stack(
                  alignment: Alignment.bottomRight,
                  children: [
                    Container(
                      width: 52,
                      height: 52,
                      decoration: BoxDecoration(
                        color: unlocked
                            ? const Color(0xFFE8F5EE)
                            : Colors.white,
                        shape: BoxShape.circle,
                      ),
                      child: Icon(
                        icon,
                        color: unlocked ? primaryGreen : Colors.grey,
                        size: 26,
                      ),
                    ),

                    if (!unlocked)
                      Container(
                        width: 22,
                        height: 22,
                        decoration: BoxDecoration(
                          color: Colors.white,
                          shape: BoxShape.circle,
                          border: Border.all(color: Colors.grey.shade300),
                        ),
                        child: const Icon(
                          Icons.lock_rounded,
                          size: 12,
                          color: Colors.grey,
                        ),
                      ),
                  ],
                ),

                const SizedBox(height: 10),

                Text(
                  title,
                  textAlign: TextAlign.center,
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                  style: TextStyle(
                    fontWeight: FontWeight.bold,
                    fontSize: 12,
                    color: unlocked ? darkGreen : Colors.grey,
                  ),
                ),

                const SizedBox(height: 3),

                Text(
                  unlocked ? "Unlocked" : "Locked",
                  style: TextStyle(
                    fontSize: 9,
                    fontWeight: FontWeight.w500,
                    color: unlocked ? primaryGreen : Colors.grey,
                  ),
                ),
              ],
            ),
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
    return Container(
      margin: const EdgeInsets.only(bottom: 8),
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 11),
      decoration: BoxDecoration(
        color: isUserDept ? const Color(0xFFEAF7EF) : const Color(0xFFF8FAF9),
        borderRadius: BorderRadius.circular(14),
        border: Border.all(
          color: isUserDept
              ? primaryGreen.withValues(alpha: 0.35)
              : const Color(0xFFE5EEE8),
        ),
      ),
      child: Row(
        children: [
          // Rank
          SizedBox(
            width: 32,
            child: Center(
              child: Text(
                rank,
                style: TextStyle(
                  fontSize: 13,
                  fontWeight: FontWeight.bold,
                  color: isUserDept ? primaryGreen : textMuted,
                ),
              ),
            ),
          ),

          const SizedBox(width: 8),

          // Department name
          Expanded(
            flex: 4,
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  label,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: TextStyle(
                    fontSize: 13,
                    fontWeight: FontWeight.bold,
                    color: isUserDept ? darkGreen : const Color(0xFF1F2933),
                  ),
                ),

                if (isUserDept)
                  const Padding(
                    padding: EdgeInsets.only(top: 2),
                    child: Text(
                      'Your department',
                      style: TextStyle(
                        fontSize: 10,
                        fontWeight: FontWeight.w600,
                        color: primaryGreen,
                      ),
                    ),
                  ),
              ],
            ),
          ),

          const SizedBox(width: 10),

          // Emission score
          SizedBox(
            width: 70,
            child: Text(
              '${score.toStringAsFixed(2)} kg',
              textAlign: TextAlign.right,
              style: TextStyle(
                fontSize: 11,
                fontWeight: FontWeight.bold,
                color: isUserDept ? primaryGreen : Colors.black54,
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildPatternItem(IconData icon, String text) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Container(
          width: 42,
          height: 42,
          decoration: BoxDecoration(
            color: const Color(0xFFEAF7EF),
            borderRadius: BorderRadius.circular(13),
          ),
          child: Icon(icon, color: primaryGreen, size: 21),
        ),

        const SizedBox(width: 12),

        Expanded(
          child: Padding(
            padding: const EdgeInsets.only(top: 2),
            child: Text(
              text.isEmpty ? 'No pattern data available yet.' : text,
              style: const TextStyle(
                fontSize: 12,
                height: 1.4,
                color: Color(0xFF4B5563),
              ),
            ),
          ),
        ),
      ],
    );
  }

  Widget _buildTimelineItem(
    String category,
    String title,
    String impact,
    String time,
    IconData icon,
    Color color,
  ) {
    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: const Color(0xFFF8FAF9),
        borderRadius: BorderRadius.circular(15),
        border: Border.all(color: const Color(0xFFE5EEE8)),
      ),
      child: Row(
        children: [
          // Activity Icon
          Container(
            width: 42,
            height: 42,
            decoration: BoxDecoration(
              color: color.withValues(alpha: 0.12),
              borderRadius: BorderRadius.circular(13),
            ),
            child: Icon(icon, color: color, size: 21),
          ),

          const SizedBox(width: 12),

          // Activity Information
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  category,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: TextStyle(
                    fontSize: 10,
                    fontWeight: FontWeight.w600,
                    color: color,
                  ),
                ),

                const SizedBox(height: 2),

                Text(
                  title,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(
                    fontSize: 13,
                    fontWeight: FontWeight.bold,
                    color: Color(0xFF1F2933),
                  ),
                ),

                const SizedBox(height: 3),

                Text(
                  time,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(fontSize: 10, color: textMuted),
                ),
              ],
            ),
          ),

          const SizedBox(width: 8),

          // Carbon Impact
          Column(
            crossAxisAlignment: CrossAxisAlignment.end,
            children: [
              Text(
                impact,
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: const TextStyle(
                  fontSize: 11,
                  fontWeight: FontWeight.bold,
                  color: darkGreen,
                ),
              ),

              const SizedBox(height: 3),

              const Text(
                'CO₂ impact',
                style: TextStyle(fontSize: 9, color: textMuted),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildMobileDepartmentRankItem({
    required int rank,
    required String department,
    required double averageEmission,
    required bool isUserDept,
  }) {
    IconData rankIcon;
    Color rankColor;

    switch (rank) {
      case 1:
        rankIcon = Icons.workspace_premium_rounded;
        rankColor = Colors.amber;
        break;

      case 2:
        rankIcon = Icons.workspace_premium_rounded;
        rankColor = Colors.grey;
        break;

      case 3:
        rankIcon = Icons.workspace_premium_rounded;
        rankColor = const Color(0xFFCD7F32);
        break;

      default:
        rankIcon = Icons.eco_rounded;
        rankColor = Colors.grey;
    }

    return Container(
      margin: const EdgeInsets.only(bottom: 8),
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 10),
      decoration: BoxDecoration(
        color: isUserDept ? const Color(0xFFEAF7EF) : const Color(0xFFF8FAF9),
        borderRadius: BorderRadius.circular(14),
        border: Border.all(
          color: isUserDept
              ? primaryGreen.withValues(alpha: 0.35)
              : const Color(0xFFE5EEE8),
        ),
      ),
      child: Row(
        children: [
          SizedBox(
            width: 32,
            child: rank <= 3
                ? Icon(rankIcon, color: rankColor, size: 22)
                : Text(
                    '$rank',
                    textAlign: TextAlign.center,
                    style: const TextStyle(
                      fontSize: 13,
                      fontWeight: FontWeight.bold,
                      color: textMuted,
                    ),
                  ),
          ),

          const SizedBox(width: 8),

          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  department,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: TextStyle(
                    fontSize: 13,
                    fontWeight: FontWeight.bold,
                    color: isUserDept ? darkGreen : const Color(0xFF1F2933),
                  ),
                ),

                if (isUserDept)
                  const Text(
                    'Your department',
                    style: TextStyle(
                      fontSize: 10,
                      color: primaryGreen,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
              ],
            ),
          ),

          const SizedBox(width: 8),

          Text(
            '${averageEmission.toStringAsFixed(2)} kg',
            style: const TextStyle(
              fontSize: 11,
              fontWeight: FontWeight.bold,
              color: darkGreen,
            ),
          ),
        ],
      ),
    );
  }

  void _showAllDepartmentRankings() {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (context) {
        return DraggableScrollableSheet(
          initialChildSize: 0.7,
          minChildSize: 0.5,
          maxChildSize: 0.9,
          builder: (context, scrollController) {
            return Container(
              decoration: const BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
              ),
              child: Column(
                children: [
                  const SizedBox(height: 10),

                  Container(
                    width: 42,
                    height: 4,
                    decoration: BoxDecoration(
                      color: Colors.black12,
                      borderRadius: BorderRadius.circular(10),
                    ),
                  ),

                  const SizedBox(height: 16),

                  const Text(
                    'Department Rankings',
                    style: TextStyle(
                      fontSize: 18,
                      fontWeight: FontWeight.bold,
                      color: Color(0xFF1F2933),
                    ),
                  ),

                  const SizedBox(height: 16),

                  Expanded(
                    child: ListView.builder(
                      controller: scrollController,
                      padding: const EdgeInsets.symmetric(horizontal: 16),
                      itemCount: _departmentRankings.length,
                      itemBuilder: (context, index) {
                        final dept = _departmentRankings[index];

                        return _buildMobileDepartmentRankItem(
                          rank: index + 1,
                          department: dept.department,
                          averageEmission: dept.averageEmission,
                          isUserDept: dept.department == _userDepartment,
                        );
                      },
                    ),
                  ),
                ],
              ),
            );
          },
        );
      },
    );
  }

  void _showFullTimeline() {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (context) {
        return DraggableScrollableSheet(
          initialChildSize: 0.75,
          minChildSize: 0.5,
          maxChildSize: 0.95,
          builder: (context, scrollController) {
            return Container(
              decoration: const BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
              ),
              child: Column(
                children: [
                  const SizedBox(height: 10),

                  // Drag handle
                  Container(
                    width: 42,
                    height: 4,
                    decoration: BoxDecoration(
                      color: Colors.black12,
                      borderRadius: BorderRadius.circular(10),
                    ),
                  ),

                  const SizedBox(height: 16),

                  const Text(
                    'Activity Timeline',
                    style: TextStyle(
                      fontSize: 18,
                      fontWeight: FontWeight.bold,
                      color: Color(0xFF1F2933),
                    ),
                  ),

                  const SizedBox(height: 16),

                  Expanded(
                    child: isLoadingTimeline
                        ? const Center(
                            child: CircularProgressIndicator(
                              color: primaryGreen,
                            ),
                          )
                        : recentActivities.isEmpty
                        ? const Center(
                            child: Column(
                              mainAxisSize: MainAxisSize.min,
                              children: [
                                Icon(
                                  Icons.history_rounded,
                                  size: 42,
                                  color: textMuted,
                                ),
                                SizedBox(height: 10),
                                Text(
                                  'No activities yet',
                                  style: TextStyle(
                                    fontSize: 14,
                                    fontWeight: FontWeight.bold,
                                  ),
                                ),
                                SizedBox(height: 4),
                                Text(
                                  'Your recent carbon activities will appear here.',
                                  textAlign: TextAlign.center,
                                  style: TextStyle(
                                    fontSize: 12,
                                    color: textMuted,
                                  ),
                                ),
                              ],
                            ),
                          )
                        : ListView.builder(
                            controller: scrollController,
                            padding: const EdgeInsets.fromLTRB(16, 0, 16, 24),
                            itemCount: recentActivities.length,
                            itemBuilder: (context, index) {
                              final activity = recentActivities[index];

                              return _buildTimelineItem(
                                activity['category'],
                                activity['title'],
                                activity['impact'],
                                activity['time'],
                                activity['icon'],
                                activity['color'],
                              );
                            },
                          ),
                  ),
                ],
              ),
            );
          },
        );
      },
    );
  }
}
