import 'dart:io';
import 'package:flutter/material.dart';
import 'package:carbonwise_app/services/api_service.dart';
import 'package:carbonwise_app/screens/edit_profile.dart';
import 'package:carbonwise_app/utils/profile_refresh_notifier.dart';
import 'package:intl/intl.dart';
import 'package:cached_network_image/cached_network_image.dart';
import 'package:screenshot/screenshot.dart';
import 'package:gal/gal.dart';
import 'package:path_provider/path_provider.dart';
import 'package:carbonwise_app/utils/dialog_helper.dart';

class ProfileScreen extends StatefulWidget {
  const ProfileScreen({super.key});

  @override
  State<ProfileScreen> createState() => _ProfileScreenState();
}

class _ProfileScreenState extends State<ProfileScreen> {
  final ApiService _apiService = ApiService();

  // Certificate Controllers & State
  final ScreenshotController _screenshotController = ScreenshotController();
  bool _isDownloading = false;

  double _toDouble(dynamic value) =>
      double.tryParse(value?.toString() ?? '0') ?? 0.0;

  String _formatActivityName(String value) {
    return value
        .trim()
        .toLowerCase()
        .split(' ')
        .map(
          (word) => word.isEmpty
              ? word
              : '${word[0].toUpperCase()}${word.substring(1)}',
        )
        .join(' ');
  }

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
      total += _toDouble(record["total_emission"] ?? 0);
    }
    return total / monthlyRecords.length;
  }

  String getSustainabilityLevel() {
    final avg = getAverageEmission();
    if (avg <= 5) return "Eco Champion";
    if (avg <= 10) return "Green Advocate";
    if (avg <= 20) return "Conscious User";
    return "Sustainability Builder";
  }

  String getSustainabilityMessage() {
    final avg = getAverageEmission();
    if (avg <= 5) return "Excellent! Your emissions are consistently very low.";
    if (avg <= 10) return "Great job! You're maintaining sustainable habits.";
    if (avg <= 20) return "You're making progress. Every small action counts.";
    return "You're improving. Try lowering this week's emissions.";
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
    if (avg <= 5) return Icons.emoji_events;
    if (avg <= 10) return Icons.eco;
    if (avg <= 20) return Icons.energy_savings_leaf;
    return Icons.trending_up;
  }

  String? getFullImageUrl(String? path) {
    if (path == null || path.isEmpty) return null;
    if (path.startsWith('http://') || path.startsWith('https://')) return path;

    final rootUri = Uri.parse(ApiService.baseUrl);
    final origin = '${rootUri.scheme}://${rootUri.authority}';
    return '$origin$path';
  }

  Map<String, dynamic>? userInfo;
  bool isLoadingProfile = true;

  List<Map<String, dynamic>> recentActivities = [];
  bool isLoadingTimeline = true;

  double carbonScore = 0.0;
  String carbonTrendText = "Starting your green track!";
  bool isLoadingScore = true;

  double transportationEmission = 0;
  double officeEmission = 0;
  double foodEmission = 0;

  String _selectedBreakdown = "Transportation";

  String _transportItem = "";
  String _officeItem = "";
  String _foodItem = "";

  int _profilePictureVersion = 0;

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

  List<Map<String, dynamic>> last4Weeks = [];

  bool hasStartedJourney() => carbonScore > 0;
  bool hasEcoCommute() =>
      transportationEmission > 0 && transportationEmission <= 5;
  bool hasGreenStreak() {
    final uniqueDates = <String>{};
    for (final activity in recentActivities) {
      uniqueDates.add(activity["time"]);
    }
    return uniqueDates.length >= 7;
  }

  bool hasEnergySaver() => officeEmission > 0 && officeEmission <= 10;
  bool hasPlantLover() => foodEmission > 0 && foodEmission <= 8;
  bool hasEcoChampion() => carbonScore > 0 && carbonScore <= 20;

  @override
  void initState() {
    super.initState();
    _loadAllProfileData();
    profileRefreshNotifier.addListener(_refreshProfileData);
  }

  @override
  void dispose() {
    profileRefreshNotifier.removeListener(_refreshProfileData);
    super.dispose();
  }

  void _refreshProfileData() {
    if (mounted) _loadUserInfo();
  }

  Future<void> _loadAllProfileData() async {
    await _loadUserInfo();
    if (!mounted) return;
    await _loadCarbonScore();
    if (!mounted) return;
    await _loadRecentActivities();
    if (!mounted) return;
    await _loadLast4Weeks();
  }

  Future<void> _loadRecentActivities() async {
    try {
      final email = await ApiService.getCurrentUserEmail();
      if (email == null) return;

      final records = await _apiService.getRecentActivities(email);
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
      setState(() => isLoadingTimeline = false);
    }
  }

  Future<void> _loadCarbonScore() async {
    try {
      final email = await ApiService.getCurrentUserEmail();
      if (email == null) {
        if (!mounted) return;
        setState(() => isLoadingScore = false);
        return;
      }

      final records = await _apiService.getCarbonRecords(email);
      final now = DateTime.now();

      final currentWeekStart = now.subtract(const Duration(days: 7));
      final lastWeekStart = now.subtract(const Duration(days: 14));
      final lastWeekEnd = currentWeekStart;

      double currentWeek = 0.0;
      double lastWeek = 0.0;
      double grandTotal = 0.0;

      double totalTransportation = 0.0;
      double totalOfficeResource = 0.0;
      double totalFoodConsumption = 0.0;

      String latestTransportItem = "";
      String latestOfficeItem = "";
      String latestFoodItem = "";

      int latestTransportRecordId = -1;
      int latestOfficeRecordId = -1;
      int latestFoodRecordId = -1;

      for (final record in records) {
        final transportation = _toDouble(record['transportation']);
        final electricity = _toDouble(record['electricity']);
        final food = _toDouble(record['food']);

        totalTransportation += transportation;
        totalOfficeResource += electricity;
        totalFoodConsumption += food;

        final recordId = int.tryParse(record['id']?.toString() ?? '') ?? 0;

        if (transportation > 0 && recordId >= latestTransportRecordId) {
          final item = record['transport_item']?.toString() ?? '';
          if (item.trim().isNotEmpty) {
            latestTransportItem = _formatActivityName(item);
            latestTransportRecordId = recordId;
          }
        }

        if (electricity > 0 && recordId >= latestOfficeRecordId) {
          final item = record['office_item']?.toString() ?? '';
          if (item.trim().isNotEmpty) {
            latestOfficeItem = _formatActivityName(item);
            latestOfficeRecordId = recordId;
          }
        }

        if (food > 0 && recordId >= latestFoodRecordId) {
          final item = record['food_item']?.toString() ?? '';
          if (item.trim().isNotEmpty) {
            latestFoodItem = _formatActivityName(item);
            latestFoodRecordId = recordId;
          }
        }

        final totalEmission = _toDouble(record['total_emission']);
        grandTotal += totalEmission;

        final recordDateString =
            record['created_at']?.toString() ??
            record['record_date']?.toString();
        if (recordDateString == null || recordDateString.isEmpty) continue;

        DateTime? recordDate;
        try {
          recordDate = DateTime.parse(recordDateString);
        } catch (_) {
          continue;
        }

        if (!recordDate.isBefore(currentWeekStart)) {
          currentWeek += totalEmission;
        } else if (!recordDate.isBefore(lastWeekStart) &&
            recordDate.isBefore(lastWeekEnd)) {
          lastWeek += totalEmission;
        }
      }

      final calculatedScore = currentWeek > 0 ? currentWeek : grandTotal;

      String trendText = "Starting your green track!";
      if (calculatedScore > 0) {
        if (lastWeek > 0) {
          if (calculatedScore < lastWeek) {
            final percentage = ((lastWeek - calculatedScore) / lastWeek) * 100;
            trendText = "${percentage.round()}% less than last week";
          } else if (calculatedScore > lastWeek) {
            final percentage = ((calculatedScore - lastWeek) / lastWeek) * 100;
            trendText = "${percentage.round()}% more than last week";
          } else {
            trendText = "Same emissions as last week";
          }
        } else {
          trendText = "Tracking active emissions metrics!";
        }
      }

      if (!mounted) return;

      setState(() {
        carbonScore = double.parse(calculatedScore.toStringAsFixed(1));
        transportationEmission = totalTransportation;
        officeEmission = totalOfficeResource;
        foodEmission = totalFoodConsumption;
        _transportItem = latestTransportItem;
        _officeItem = latestOfficeItem;
        _foodItem = latestFoodItem;
        carbonTrendText = trendText;
        isLoadingScore = false;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() => isLoadingScore = false);
    }
  }

  Future<void> _loadUserInfo() async {
    try {
      final email = await ApiService.getCurrentUserEmail();
      if (email == null) {
        if (mounted) {
          setState(() {
            userInfo = {'name': 'Profile Unavailable'};
            isLoadingProfile = false;
          });
        }
        return;
      }

      final response = await _apiService.getUserInfo(email);

      if (mounted) {
        setState(() {
          userInfo = response;
          isLoadingProfile = false;
        });
      }
    } catch (e) {
      print("Profile Loading Error: $e");
      if (mounted) {
        setState(() {
          userInfo = {'name': 'Profile Unavailable'};
          isLoadingProfile = false;
        });
      }
    }
  }

  Future<void> _loadLast4Weeks() async {
    try {
      final email = await ApiService.getCurrentUserEmail();
      if (email == null) return;

      final data = await _apiService.getLast4WeeksRecords(email);

      if (mounted) {
        setState(() {
          last4Weeks = data.reversed
              .map((e) => Map<String, dynamic>.from(e))
              .toList();
        });
      }
    } catch (e) {
      print("Last 4 Weeks Error: $e");
    }
  }

  Future<void> _downloadCertificate() async {
    if (_isDownloading) return;
    setState(() => _isDownloading = true);

    try {
      final image = await _screenshotController.capture();
      if (image == null) {
        throw Exception("Could not capture the certificate image.");
      }

      final directory = await getTemporaryDirectory();
      final imagePath = await File(
        '${directory.path}/carbonwise_certificate.png',
      ).create();
      await imagePath.writeAsBytes(image.buffer.asUint8List());

      await Gal.putImage(imagePath.path);

      if (mounted) {
        DialogHelper.showSuccess(
          context: context,
          title: "Certificate Saved",
          message:
              "Your certificate has been successfully downloaded to your gallery.",
        );
      }
    } catch (e) {
      if (mounted) {
        DialogHelper.showError(
          context: context,
          title: "Download Failed",
          message: "Could not save the certificate. $e",
        );
      }
    } finally {
      if (mounted) setState(() => _isDownloading = false);
    }
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

                  _buildCertificateSection(),
                  const SizedBox(height: 12),

                  _buildAchievementsCard(),
                  const SizedBox(height: 12),
                  _buildPatternsAndTimelineRow(),
                  const SizedBox(height: 12),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  // CERTIFICATE SECTION UI
  Widget _buildCertificateSection() {
    String displayRank = "Top 100%";
    if (carbonScore > 0) {
      if (carbonScore <= 10)
        displayRank = "Top 5%";
      else if (carbonScore <= 20)
        displayRank = "Top 15%";
      else if (carbonScore <= 40)
        displayRank = "Top 30%";
      else
        displayRank = "Top 50%";
    }

    String displayName =
        userInfo?['name'] ?? userInfo?['full_name'] ?? "Eco Warrior";

    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(20),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.04),
            blurRadius: 10,
            offset: const Offset(0, 4),
          ),
        ],
        border: Border.all(color: const Color(0xFFE5EEE8)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              const Icon(Icons.stars_rounded, color: primaryGreen, size: 18),
              const SizedBox(width: 6),
              const Text(
                'MONTHLY RECOGNITION',
                style: TextStyle(
                  fontSize: 11,
                  fontWeight: FontWeight.bold,
                  letterSpacing: 1.2,
                  color: textMuted,
                ),
              ),
            ],
          ),
          const SizedBox(height: 4),
          const Text(
            "Here is your official certificate for your sustainability efforts!",
            style: TextStyle(fontSize: 12, color: Colors.black54),
          ),
          const SizedBox(height: 16),

          // Certificate UI wrapped in Screenshot
          Screenshot(
            controller: _screenshotController,
            child: Container(
              width: double.infinity,
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: const Color(0xFFFAFDFB),
                borderRadius: BorderRadius.circular(16),
                border: Border.all(
                  color: primaryGreen.withValues(alpha: 0.4),
                  width: 2,
                ),
                boxShadow: [
                  BoxShadow(
                    color: primaryGreen.withValues(alpha: 0.08),
                    blurRadius: 20,
                    offset: const Offset(0, 8),
                  ),
                ],
              ),
              child: Container(
                padding: const EdgeInsets.symmetric(
                  horizontal: 20,
                  vertical: 24,
                ),
                decoration: BoxDecoration(
                  borderRadius: BorderRadius.circular(10),
                  border: Border.all(
                    color: primaryGreen.withValues(alpha: 0.15),
                    width: 1,
                  ),
                ),
                child: Column(
                  children: [
                    // Top Award Badge Icon
                    Container(
                      padding: const EdgeInsets.all(10),
                      decoration: BoxDecoration(
                        color: primaryGreen.withValues(alpha: 0.1),
                        shape: BoxShape.circle,
                      ),
                      child: const Icon(
                        Icons.workspace_premium_rounded,
                        color: primaryGreen,
                        size: 40,
                      ),
                    ),
                    const SizedBox(height: 12),
                    const Text(
                      "CERTIFICATE OF RECOGNITION",
                      textAlign: TextAlign.center,
                      style: TextStyle(
                        fontSize: 13,
                        fontWeight: FontWeight.w800,
                        letterSpacing: 2.2,
                        color: darkGreen,
                      ),
                    ),
                    const SizedBox(height: 4),
                    Container(
                      width: 40,
                      height: 2,
                      color: primaryGreen.withValues(alpha: 0.5),
                    ),
                    const SizedBox(height: 18),
                    Text(
                      "This certificate is proudly presented to",
                      style: TextStyle(
                        fontSize: 11,
                        fontStyle: FontStyle.italic,
                        color: Colors.grey.shade600,
                      ),
                    ),
                    const SizedBox(height: 8),

                    // Display Name using standard fallback serif style
                    Text(
                      displayName,
                      textAlign: TextAlign.center,
                      style: const TextStyle(
                        fontSize: 24,
                        fontWeight: FontWeight.bold,
                        fontFamily: 'serif',
                        color: Color(0xFF162A1E),
                      ),
                    ),
                    const SizedBox(height: 12),
                    Padding(
                      padding: const EdgeInsets.symmetric(horizontal: 16),
                      child: Text(
                        "For outstanding dedication to reducing carbon footprint and reaching the top tier of sustainability performance.",
                        textAlign: TextAlign.center,
                        style: TextStyle(
                          fontSize: 11,
                          color: Colors.grey.shade700,
                          height: 1.4,
                        ),
                      ),
                    ),
                    const SizedBox(height: 16),

                    // Rank Badge Pill
                    Container(
                      padding: const EdgeInsets.symmetric(
                        horizontal: 18,
                        vertical: 6,
                      ),
                      decoration: BoxDecoration(
                        gradient: LinearGradient(
                          colors: [
                            const Color(0xFFEAF6EE),
                            primaryGreen.withValues(alpha: 0.15),
                          ],
                          begin: Alignment.topLeft,
                          end: Alignment.bottomRight,
                        ),
                        borderRadius: BorderRadius.circular(30),
                        border: Border.all(
                          color: primaryGreen.withValues(alpha: 0.3),
                        ),
                      ),
                      child: Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          const Icon(
                            Icons.eco_rounded,
                            size: 16,
                            color: primaryGreen,
                          ),
                          const SizedBox(width: 6),
                          Text(
                            displayRank,
                            style: const TextStyle(
                              fontSize: 15,
                              fontWeight: FontWeight.bold,
                              color: darkGreen,
                            ),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 28),

                    // Footer Section (Date & Signature)
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      crossAxisAlignment: CrossAxisAlignment.end,
                      children: [
                        Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              "${DateTime.now().month}/${DateTime.now().day}/${DateTime.now().year}",
                              style: const TextStyle(
                                fontSize: 11,
                                fontWeight: FontWeight.w600,
                                color: Colors.black87,
                              ),
                            ),
                            const SizedBox(height: 2),
                            Container(
                              width: 80,
                              height: 1,
                              color: Colors.grey.shade300,
                            ),
                            const SizedBox(height: 2),
                            Text(
                              "DATE ISSUED",
                              style: TextStyle(
                                fontSize: 9,
                                fontWeight: FontWeight.bold,
                                letterSpacing: 0.5,
                                color: Colors.grey.shade500,
                              ),
                            ),
                          ],
                        ),
                        Column(
                          crossAxisAlignment: CrossAxisAlignment.end,
                          children: [
                            const Text(
                              "SDO CarbonWise",
                              style: TextStyle(
                                fontSize: 12,
                                fontWeight: FontWeight.bold,
                                color: darkGreen,
                                fontStyle: FontStyle.italic,
                              ),
                            ),
                            const SizedBox(height: 2),
                            Container(
                              width: 100,
                              height: 1,
                              color: Colors.grey.shade300,
                            ),
                            const SizedBox(height: 2),
                            Text(
                              "OFFICIAL ISSUER",
                              style: TextStyle(
                                fontSize: 9,
                                fontWeight: FontWeight.bold,
                                letterSpacing: 0.5,
                                color: Colors.grey.shade500,
                              ),
                            ),
                          ],
                        ),
                      ],
                    ),
                  ],
                ),
              ),
            ),
          ),

          const SizedBox(height: 16),

          // Action Button
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
                  : const Icon(Icons.file_download_outlined),
              label: Text(
                _isDownloading
                    ? "Saving Certificate..."
                    : "Download Certificate",
                style: const TextStyle(fontWeight: FontWeight.bold),
              ),
              style: ElevatedButton.styleFrom(
                backgroundColor: primaryGreen,
                foregroundColor: Colors.white,
                elevation: 0,
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(12),
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }

  // 1. Profile Header
  Widget _buildProfileHeader() {
    final String fullName = isLoadingProfile
        ? 'Loading...'
        : (userInfo?['name'] ?? userInfo?['full_name'] ?? 'User Profile')
              .toString();

    final String rawRole = userInfo?['role'] ?? '';
    final String facultyType = userInfo?['faculty_type'] ?? '';

    String role = rawRole;
    if (rawRole.toLowerCase() == 'faculty' && facultyType.isNotEmpty) {
      role = facultyType;
    } else if (rawRole.toLowerCase() == 'student') {
      role = 'Student';
    } else if (rawRole.toLowerCase() == 'staff' ||
        rawRole.toLowerCase() == 'non-teaching staff') {
      role = 'Non-Teaching Staff';
    } else if (rawRole.isNotEmpty) {
      role = rawRole[0].toUpperCase() + rawRole.substring(1);
    }

    final String department =
        userInfo?['department'] ?? userInfo?['office'] ?? '';
    final String campus = userInfo?['campus'] ?? '';
    final String? rawProfilePic = userInfo?['profile_picture'];
    final String? profilePicture = getFullImageUrl(rawProfilePic);

    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 20, 16, 8),
      child: Column(
        children: [
          const Align(
            alignment: Alignment.centerLeft,
            child: Text(
              'Profile',
              style: TextStyle(
                fontSize: 24,
                fontWeight: FontWeight.bold,
                color: Color(0xFF1F2933),
              ),
            ),
          ),
          const SizedBox(height: 16),
          Container(
            width: double.infinity,
            padding: const EdgeInsets.all(24),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(24),
              border: Border.all(color: Colors.grey.shade200),
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
                Container(
                  width: 112,
                  height: 112,
                  padding: const EdgeInsets.all(3),
                  decoration: BoxDecoration(
                    shape: BoxShape.circle,
                    color: Colors.white,
                    border: Border.all(
                      color: primaryGreen.withValues(alpha: 0.5),
                      width: 2.5,
                    ),
                    boxShadow: [
                      BoxShadow(
                        color: Colors.black.withValues(alpha: 0.08),
                        blurRadius: 10,
                        offset: const Offset(0, 4),
                      ),
                    ],
                  ),
                  child: ClipOval(
                    child: Container(
                      color: Colors.grey.shade100,
                      child: profilePicture != null && profilePicture.isNotEmpty
                          ? CachedNetworkImage(
                              imageUrl: _profilePictureVersion == 0
                                  ? profilePicture
                                  : '$profilePicture?v=$_profilePictureVersion',
                              width: 104,
                              height: 104,
                              fit: BoxFit.cover,
                              placeholder: (context, url) => const Center(
                                child: SizedBox(
                                  width: 26,
                                  height: 26,
                                  child: CircularProgressIndicator(
                                    strokeWidth: 2.5,
                                    color: primaryGreen,
                                  ),
                                ),
                              ),
                              errorWidget: (context, url, error) =>
                                  const Center(
                                    child: Icon(
                                      Icons.person_rounded,
                                      size: 48,
                                      color: primaryGreen,
                                    ),
                                  ),
                              fadeInDuration: const Duration(milliseconds: 200),
                            )
                          : const Center(
                              child: Icon(
                                Icons.person_rounded,
                                size: 48,
                                color: primaryGreen,
                              ),
                            ),
                    ),
                  ),
                ),
                const SizedBox(height: 16),
                Text(
                  isLoadingProfile ? 'Loading...' : fullName,
                  textAlign: TextAlign.center,
                  style: const TextStyle(
                    fontSize: 20,
                    fontWeight: FontWeight.bold,
                    color: Color(0xFF1F2933),
                  ),
                ),
                const SizedBox(height: 6),
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
                              fontWeight: FontWeight.w500,
                            ),
                          ),
                        ),
                      if (role.isNotEmpty && campus.isNotEmpty)
                        const Text(
                          '  •  ',
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
                              fontWeight: FontWeight.w500,
                            ),
                          ),
                        ),
                    ],
                  ),
                const SizedBox(height: 4),
                if (!isLoadingProfile && department.isNotEmpty)
                  Text(
                    department,
                    textAlign: TextAlign.center,
                    style: const TextStyle(fontSize: 12, color: Colors.grey),
                  ),
                const SizedBox(height: 20),
                SizedBox(
                  width: double.infinity,
                  child: OutlinedButton.icon(
                    onPressed: () async {
                      if (userInfo == null) return;
                      final updated = await showDialog<bool>(
                        context: context,
                        builder: (_) => EditProfileDialog(
                          fullName:
                              (userInfo!['name'] ??
                                      userInfo!['full_name'] ??
                                      '')
                                  .toString(),
                          studentNumber: userInfo!['sr_code'] ?? '',
                          email: userInfo!['g_suite'] ?? '',
                          department:
                              userInfo!['department'] ??
                              userInfo!['office'] ??
                              '',
                          campus: userInfo!['campus'] ?? '',
                          profilePicture: userInfo!['profile_picture'],
                        ),
                      );
                      if (updated == true) {
                        setState(() => _profilePictureVersion++);
                        await _loadUserInfo();
                        await _loadCarbonScore();
                        await _loadRecentActivities();
                      }
                    },
                    icon: const Icon(Icons.edit_outlined, size: 16),
                    label: const Text(
                      'Edit Profile',
                      style: TextStyle(
                        fontSize: 13,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                    style: OutlinedButton.styleFrom(
                      foregroundColor: primaryGreen,
                      side: const BorderSide(color: primaryGreen, width: 1.5),
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
                    Text(
                      isLoadingScore
                          ? 'Calculating your weekly trend...'
                          : carbonTrendText,
                      style: TextStyle(
                        color: carbonTrendText.contains('more')
                            ? Colors.redAccent
                            : primaryGreen,
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
                      "${DateFormat('MMMM').format(DateTime.now())} Average: ${getAverageEmission().toStringAsFixed(1)} kg CO₂",
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
          SingleChildScrollView(
            scrollDirection: Axis.horizontal,
            child: Row(
              children: [
                _buildSubTab(
                  'Transportation',
                  _selectedBreakdown == "Transportation",
                  onTap: () =>
                      setState(() => _selectedBreakdown = "Transportation"),
                ),
                const SizedBox(width: 8),
                _buildSubTab(
                  'Office Resource',
                  _selectedBreakdown == "Office Resource",
                  onTap: () =>
                      setState(() => _selectedBreakdown = "Office Resource"),
                ),
                const SizedBox(width: 8),
                _buildSubTab(
                  'Food Consumption',
                  _selectedBreakdown == "Food Consumption",
                  onTap: () =>
                      setState(() => _selectedBreakdown = "Food Consumption"),
                ),
              ],
            ),
          ),
          const SizedBox(height: 18),
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

  // 6. Activity Timeline
  Widget _buildPatternsAndTimelineRow() {
    return Column(
      children: [
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
                  onTap: () => _showFullTimeline(),
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
                    onPressed: () => Navigator.pop(context),
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
