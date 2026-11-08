import 'dart:io';
import 'package:flutter/material.dart';
import 'package:carbonwise_app/services/api_service.dart';
import 'package:screenshot/screenshot.dart';
import 'package:gal/gal.dart';
import 'package:path_provider/path_provider.dart';
import 'dart:typed_data';

class DepartmentRanking {
  final String name;
  final String type;
  final double totalEmission;
  final int totalRecords;

  DepartmentRanking({
    required this.name,
    required this.type,
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
  String _userDepartmentLabel = 'Department';
  String _userName = "Eco Warrior"; // Added for certificate

  bool _isLoading = true;

  String _rankingPeriod = 'weekly';

  List<CampusRanking> _campusRankings = [];

  // Certificate Controllers & State
  final ScreenshotController _screenshotController = ScreenshotController();
  bool _isDownloading = false;

  double _toDouble(dynamic value) =>
      double.tryParse(value?.toString() ?? '0') ?? 0.0;

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
  void initState() {
    super.initState();
    _loadDashboardData();
  }

  @override
  void dispose() {
    _departmentScrollController.dispose();
    super.dispose();
  }

  Future<void> _loadDashboardData() async {
    setState(() => _isLoading = true);

    try {
      Map<String, dynamic> user = {};
      try {
        user = await ApiService.getUserProfile();
        _userName = user['name'] ?? user['full_name'] ?? "Eco Warrior";
      } catch (e) {
        print('Dashboard: profile fetch failed: $e');
      }

      if (!mounted) return;

      await _loadDepartmentRankings(user);
      if (!mounted) return;

      await _loadCampusRankings(user);
      if (!mounted) return;

      await _loadIndividualStatus();
      if (!mounted) return;

      await _loadCurrentRanking();
    } catch (e) {
      print('Dashboard Loading Error: $e');
    } finally {
      if (mounted) {
        setState(() {
          _isLoading = false;
          if (_currentRanking == "Loading...") {
            _currentRanking = "—";
          }
        });
      }
    }
  }

  Future<void> _loadDepartmentRankings(Map<String, dynamic> user) async {
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
              name: map['name']?.toString() ?? '',
              type: map['type']?.toString() ?? 'Department',
              totalEmission: _toDouble(map['total_emission']),
              totalRecords:
                  int.tryParse(map['total_records']?.toString() ?? '0') ?? 0,
            );
          })
          .where((r) => r.name.isNotEmpty)
          .toList();

      rankings.sort((a, b) => a.totalEmission.compareTo(b.totalEmission));

      final role = user['role']?.toString().toLowerCase() ?? '';
      final facultyType = user['faculty_type']?.toString() ?? '';

      String myDepartment;
      String departmentLabel;

      if (role == 'non-teaching staff' ||
          facultyType == 'Administrative Faculty') {
        myDepartment = user['office']?.toString() ?? '';
        departmentLabel = 'Office';
      } else {
        myDepartment = user['department']?.toString() ?? '';
        departmentLabel = 'Department';
      }

      final index = rankings.indexWhere(
        (r) => r.name == myDepartment && r.type == departmentLabel,
      );

      if (!mounted) return;

      setState(() {
        _departmentRankings = rankings;
        _userDepartment = myDepartment;
        _userDepartmentLabel = departmentLabel;
        _departmentRank = index >= 0
            ? '${index + 1}${_getOrdinal(index + 1)}'
            : '-';
      });
    } catch (e) {
      print('Department Ranking Error: $e');
    }
  }

  Future<void> _loadCampusRankings(Map<String, dynamic> user) async {
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

  Future<void> _loadCurrentRanking() async {
    try {
      final data = await _apiService.getMyPeerComparison(
        period: _rankingPeriod,
      );

      if (!mounted) return;

      final topPercentage = data['top_percentage'];
      final role = data['role']?.toString() ?? '';
      final period = data['period']?.toString() ?? _rankingPeriod;

      String description;

      if (topPercentage != null) {
        final percentage = int.tryParse(topPercentage.toString()) ?? 0;
        final roleName = role.isNotEmpty ? role : 'users';
        final periodName = period == 'weekly' ? 'this week' : 'this month';
        description =
            'You are in the top $percentage% of eco-friendly $roleName in your campus $periodName.';
      } else {
        description =
            data['message']?.toString() ??
            'Record your emissions to see your peer comparison.';
      }

      setState(() {
        _currentRanking = topPercentage != null ? 'Top ${topPercentage}%' : '—';
        _currentRankingDescription = description;
      });
    } catch (e) {
      print('Peer Comparison Error: $e');
    }
  }

  Future<void> _loadIndividualStatus() async {
    try {
      final records = await _apiService.getCarbonRecords('');
      final now = DateTime.now();

      double transport = 0;
      double office = 0;
      double food = 0;

      for (final raw in records) {
        final record = Map<String, dynamic>.from(raw as Map);
        final date = DateTime.tryParse(record['record_date']?.toString() ?? '');

        if (date == null ||
            date.year != now.year ||
            date.month != now.month ||
            date.day != now.day) {
          continue;
        }

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

  // ==========================================
  // CERTIFICATE DOWNLOAD LOGIC
  // ==========================================
  Future<void> _downloadCertificate() async {
    if (_isDownloading) return;
    setState(() => _isDownloading = true);

    try {
      // 1. Capture the widget
      final image = await _screenshotController.capture();

      // 2. Check if the image is null
      if (image == null) {
        throw Exception("Could not capture the certificate image.");
      }

      // 3. Save to temporary file
      final directory = await getTemporaryDirectory();
      final imagePath = await File(
        '${directory.path}/carbonwise_certificate.png',
      ).create();

      // 4. Write the bytes. We use 'image.buffer.asUint8List()' to ensure
      // it's a standard List<int> that the file system accepts.
      await imagePath.writeAsBytes(image.buffer.asUint8List());

      // 5. Save to Gallery
      await Gal.putImage(imagePath.path);

      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Certificate saved to your gallery!')),
        );
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(
          context,
        ).showSnackBar(SnackBar(content: Text('Failed to download: $e')));
      }
    } finally {
      if (mounted) setState(() => _isDownloading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final now = DateTime.now();
    final isRecognitionDay = true; // TEMPORARY: forced to true for testing

    return SafeArea(
      child: SingleChildScrollView(
        physics: const BouncingScrollPhysics(),
        padding: const EdgeInsets.symmetric(horizontal: 16.0, vertical: 8.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
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

            _buildFeaturedRankingCard(),
            const SizedBox(height: 12),

            Row(
              children: [
                Expanded(
                  child: _buildRankingCard(
                    title: '$_userDepartmentLabel Ranking',
                    icon: Icons.school_outlined,
                    badgeText: _departmentRank,
                    description: _userDepartment.isEmpty
                        ? 'Loading $_userDepartmentLabel...'
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

            const SizedBox(height: 28),

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
              "Your carbon emissions by category today.",
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

            Text(
              "$_userDepartmentLabel Rankings",
              style: const TextStyle(
                fontSize: 20,
                fontWeight: FontWeight.bold,
                color: Colors.black87,
              ),
            ),
            const SizedBox(height: 4),
            Text(
              "See how ${_userDepartmentLabel.toLowerCase()}s compare in total carbon emissions.",
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
                                  department: dept.name,
                                  emission: dept.totalEmission,
                                  records: dept.totalRecords,
                                ),
                              );
                            }),
                      ],
                    ),
            ),

            const SizedBox(height: 28),

            // ==========================================
            // RECOGNITION / CERTIFICATE SECTION
            // ==========================================
            const Text(
              "Monthly Recognition",
              style: TextStyle(
                fontSize: 20,
                fontWeight: FontWeight.bold,
                color: Colors.black87,
              ),
            ),
            const SizedBox(height: 4),
            Text(
              isRecognitionDay
                  ? "Congratulations! You've earned a certificate for your sustainability efforts this month."
                  : "Your monthly certificate unlocks on the 28th. Keep up the great work!",
              style: TextStyle(fontSize: 13, color: Colors.grey.shade600),
            ),
            const SizedBox(height: 14),

            // SCREENSHOT WRAPPER
            Screenshot(
              controller: _screenshotController,
              child: _buildCertificate(isRecognitionDay),
            ),

            const SizedBox(height: 14),

            // Download Button
            if (isRecognitionDay)
              SizedBox(
                width: double.infinity,
                height: 48,
                child: ElevatedButton.icon(
                  onPressed: _isDownloading ? null : _downloadCertificate,
                  icon: _isDownloading
                      ? const SizedBox(
                          width: 18,
                          height: 18,
                          child: CircularProgressIndicator(
                            color: Colors.white,
                            strokeWidth: 2,
                          ),
                        )
                      : const Icon(Icons.download_rounded),
                  label: Text(
                    _isDownloading ? "Saving..." : "Download Certificate",
                  ),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: const Color(0xFF265D3B),
                    foregroundColor: Colors.white,
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(12),
                    ),
                  ),
                ),
              ),

            const SizedBox(height: 24),
          ],
        ),
      ),
    );
  }

  // ==========================================
  // CERTIFICATE UI
  // ==========================================
  Widget _buildCertificate(bool isUnlocked) {
    // If it's not the 28th, show a blank/locked certificate
    final String displayName = isUnlocked ? _userName : "???";
    final String displayRank = isUnlocked ? _currentRanking : "Pending";

    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(24),
      decoration: BoxDecoration(
        color: isUnlocked ? const Color(0xFFFDFDFD) : const Color(0xFFF0F0F0),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(
          color: isUnlocked
              ? const Color(0xFF3AA76D).withValues(alpha: 0.5)
              : Colors.grey.shade400,
          width: 3,
        ),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.08),
            blurRadius: 15,
            offset: const Offset(0, 5),
          ),
        ],
      ),
      child: Stack(
        alignment: Alignment.center,
        children: [
          Column(
            children: [
              // Logo/Icon
              Icon(
                Icons.workspace_premium_rounded,
                color: isUnlocked
                    ? const Color(0xFF3AA76D)
                    : Colors.grey.shade400,
                size: 48,
              ),
              const SizedBox(height: 12),

              // Title
              Text(
                "CERTIFICATE OF RECOGNITION",
                textAlign: TextAlign.center,
                style: TextStyle(
                  fontSize: 14,
                  fontWeight: FontWeight.bold,
                  letterSpacing: 2.0,
                  color: isUnlocked
                      ? const Color(0xFF265D3B)
                      : Colors.grey.shade500,
                ),
              ),

              const SizedBox(height: 20),
              Text(
                "This certificate is proudly presented to",
                style: TextStyle(fontSize: 12, color: Colors.grey.shade600),
              ),
              const SizedBox(height: 8),

              // User Name
              Text(
                displayName,
                textAlign: TextAlign.center,
                style: TextStyle(
                  fontSize: 24,
                  fontWeight: FontWeight.bold,
                  color: isUnlocked
                      ? const Color(0xFF1F2933)
                      : Colors.grey.shade500,
                ),
              ),

              const SizedBox(height: 20),
              Text(
                "For outstanding dedication to reducing your carbon footprint and achieving a ranking of",
                textAlign: TextAlign.center,
                style: TextStyle(
                  fontSize: 12,
                  color: Colors.grey.shade600,
                  height: 1.4,
                ),
              ),
              const SizedBox(height: 8),

              // Rank
              Container(
                padding: const EdgeInsets.symmetric(
                  horizontal: 16,
                  vertical: 6,
                ),
                decoration: BoxDecoration(
                  color: isUnlocked
                      ? const Color(0xFFEAF6EE)
                      : Colors.grey.shade300,
                  borderRadius: BorderRadius.circular(20),
                ),
                child: Text(
                  displayRank,
                  style: TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.bold,
                    color: isUnlocked
                        ? const Color(0xFF3AA76D)
                        : Colors.grey.shade600,
                  ),
                ),
              ),

              const SizedBox(height: 32),

              // Date & Signature area
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        "Date",
                        style: TextStyle(
                          fontSize: 10,
                          color: Colors.grey.shade500,
                        ),
                      ),
                      const SizedBox(height: 4),
                      Text(
                        isUnlocked
                            ? "${DateTime.now().month}/${DateTime.now().day}/${DateTime.now().year}"
                            : "Available on the 28th",
                        style: TextStyle(
                          fontSize: 12,
                          fontWeight: FontWeight.w600,
                          color: isUnlocked
                              ? Colors.black87
                              : Colors.grey.shade500,
                        ),
                      ),
                    ],
                  ),
                  Column(
                    crossAxisAlignment: CrossAxisAlignment.end,
                    children: [
                      Text(
                        "CarbonWise Team",
                        style: TextStyle(
                          fontSize: 12,
                          fontWeight: FontWeight.bold,
                          color: isUnlocked
                              ? const Color(0xFF265D3B)
                              : Colors.grey.shade500,
                          fontStyle: FontStyle.italic,
                        ),
                      ),
                      const SizedBox(height: 2),
                      Container(
                        width: 100,
                        height: 1,
                        color: Colors.grey.shade400,
                      ),
                    ],
                  ),
                ],
              ),
            ],
          ),

          // Lock Overlay for locked state
          if (!isUnlocked)
            Positioned(
              child: Container(
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  color: Colors.black.withValues(alpha: 0.6),
                  shape: BoxShape.circle,
                ),
                child: const Icon(
                  Icons.lock_rounded,
                  color: Colors.white,
                  size: 32,
                ),
              ),
            ),
        ],
      ),
    );
  }

  // ==========================================
  // HELPER WIDGETS
  // ==========================================
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
          Container(
            width: 42,
            height: 42,
            decoration: const BoxDecoration(
              color: Color(0xFFE8F5EC),
              shape: BoxShape.circle,
            ),
            child: Icon(icon, color: const Color(0xFF3AA76D), size: 22),
          ),
          const SizedBox(height: 10),
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
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
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
                      'Your Eco-Friendly Standing',
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
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 10),
          Text(
            _currentRankingDescription,
            maxLines: 2,
            overflow: TextOverflow.ellipsis,
            style: TextStyle(
              color: Colors.white.withValues(alpha: 0.75),
              fontSize: 11,
              height: 1.3,
            ),
          ),
          const SizedBox(height: 16),
          Container(
            padding: const EdgeInsets.all(4),
            decoration: BoxDecoration(
              color: Colors.white.withValues(alpha: 0.12),
              borderRadius: BorderRadius.circular(12),
            ),
            child: Row(
              children: [
                Expanded(
                  child: GestureDetector(
                    onTap: () {
                      if (_rankingPeriod == 'weekly') return;
                      setState(() => _rankingPeriod = 'weekly');
                      _loadCurrentRanking();
                    },
                    child: Container(
                      padding: const EdgeInsets.symmetric(vertical: 9),
                      decoration: BoxDecoration(
                        color: _rankingPeriod == 'weekly'
                            ? Colors.white
                            : Colors.transparent,
                        borderRadius: BorderRadius.circular(9),
                      ),
                      child: Text(
                        'Weekly',
                        textAlign: TextAlign.center,
                        style: TextStyle(
                          color: _rankingPeriod == 'weekly'
                              ? const Color(0xFF265D3B)
                              : Colors.white70,
                          fontSize: 12,
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                    ),
                  ),
                ),
                const SizedBox(width: 4),
                Expanded(
                  child: GestureDetector(
                    onTap: () {
                      if (_rankingPeriod == 'monthly') return;
                      setState(() => _rankingPeriod = 'monthly');
                      _loadCurrentRanking();
                    },
                    child: Container(
                      padding: const EdgeInsets.symmetric(vertical: 9),
                      decoration: BoxDecoration(
                        color: _rankingPeriod == 'monthly'
                            ? Colors.white
                            : Colors.transparent,
                        borderRadius: BorderRadius.circular(9),
                      ),
                      child: Text(
                        'Monthly',
                        textAlign: TextAlign.center,
                        style: TextStyle(
                          color: _rankingPeriod == 'monthly'
                              ? const Color(0xFF265D3B)
                              : Colors.white70,
                          fontSize: 12,
                          fontWeight: FontWeight.w600,
                        ),
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
}
