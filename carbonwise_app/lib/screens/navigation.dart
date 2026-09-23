import 'package:flutter/material.dart';

import 'package:carbonwise_app/screens/activity.dart';
import 'package:carbonwise_app/screens/dashboard.dart';
import 'package:carbonwise_app/screens/profile.dart';
import 'package:carbonwise_app/screens/reports.dart';
import 'package:carbonwise_app/screens/strategies.dart';

import 'package:carbonwise_app/main.dart';
import 'package:carbonwise_app/utils/profile_refresh_notifier.dart';
import 'package:carbonwise_app/utils/carbon_score_refresh_notifier.dart';
import 'package:carbonwise_app/utils/dialog_helper.dart';
import 'package:carbonwise_app/services/notification_service.dart';

import '../services/api_service.dart';

class CustomMainNavigation extends StatefulWidget {
  const CustomMainNavigation({super.key});

  @override
  State<CustomMainNavigation> createState() => _CustomMainNavigationState();
}

class _CustomMainNavigationState extends State<CustomMainNavigation> {
  int _currentIndex = 0;

  String userName = 'User';
  double _carbonScore = 100;

  List<Map<String, dynamic>> notifications = [];

  final ApiService _apiService = ApiService();

  double _toDouble(dynamic value) =>
      double.tryParse(value?.toString() ?? '0') ?? 0.0;

  // ============================================================
  // PAGE TITLES
  // ============================================================

  final List<String> _pageTitles = [
    '',
    'View your Reports',
    'Activity Input',
    'Mitigation Strategies',
    'User Profile Summary',
  ];

  final Set<int> _visitedIndices = {0};

  // ============================================================
  // GREETING
  // ============================================================

  String getGreeting() {
    final hour = DateTime.now().hour;

    if (hour < 12) {
      return "🌅 Good morning";
    } else if (hour < 18) {
      return "☀️ Good afternoon";
    } else {
      return "🌙 Good evening";
    }
  }

  // ============================================================
  // INITIALIZATION
  // ============================================================

  @override
  void initState() {
    super.initState();

    // Run these sequentially, not concurrently
    _initializeNavigation();

    profileRefreshNotifier.addListener(_refreshProfile);
    carbonScoreRefreshNotifier.addListener(_loadCarbonScore);
    carbonScoreRefreshNotifier.addListener(_refreshNotificationsFromNotifier);
  }

  @override
  void dispose() {
    profileRefreshNotifier.removeListener(_refreshProfile);
    carbonScoreRefreshNotifier.removeListener(_loadCarbonScore);
    carbonScoreRefreshNotifier.removeListener(
      _refreshNotificationsFromNotifier,
    );
    super.dispose();
  }

  Future<void> _refreshNotificationsFromNotifier() async {
    await Future.delayed(const Duration(milliseconds: 800));
    if (!mounted) return;
    await loadNotifications();
  }

  Future<void> _initializeNavigation() async {
    await loadUserName();
    if (!mounted) return;
    await loadNotifications();
    if (!mounted) return;
    await _loadCarbonScore();
  }

  // REFRESH PROFILE

  Future<void> _refreshProfile() async {
    await loadUserName();
  }

  // LOAD USER NAME
  Future<void> loadUserName() async {
    try {
      final user = await _apiService.getUserInfo();

      if (!mounted) return;

      setState(() {
        userName = user['full_name'] ?? user['name'] ?? 'User';
      });

      print("Loaded name: $userName");
    } catch (e) {
      print("ERROR loading user name: $e");
    }
  }

  // LOAD NOTIFICATIONS
  Future<void> loadNotifications() async {
    try {
      final data = await _apiService.getNotifications();

      if (!mounted) return;

      setState(() {
        notifications = List<Map<String, dynamic>>.from(data);
      });
    } catch (e) {
      print("Error loading notifications: $e");
    }
  }

  // LOAD CARBON SCORE
  Future<void> _loadCarbonScore() async {
    try {
      print('🚨 NAVIGATION: calling getCarbonRecords');
      final records = await _apiService.getCarbonRecords('');

      if (records.isEmpty) {
        if (!mounted) return;

        setState(() {
          _carbonScore = 100;
        });

        return;
      }

      final today = DateTime.now();

      final todayString =
          "${today.year.toString().padLeft(4, '0')}-"
          "${today.month.toString().padLeft(2, '0')}-"
          "${today.day.toString().padLeft(2, '0')}";

      double transportation = 0;
      double electricity = 0;
      double food = 0;

      for (final record in records) {
        final recordDate = record['record_date']?.toString();

        if (recordDate == todayString) {
          transportation += _toDouble(record['transportation']);
          electricity += _toDouble(record['electricity']);
          food += _toDouble(record['food']);
        }
      }

      final total = transportation + electricity + food;

      double score = 100 - total;

      if (score < 0) {
        score = 0;
      }

      if (score > 100) {
        score = 100;
      }

      if (!mounted) return;

      setState(() {
        _carbonScore = score;
      });

      print("Today's Total Emission: $total");
      print("Today's CarbonWise Score: $score");
    } catch (e) {
      print("Carbon Score Error:");
      print(e);
    }
  }

  // BUILD
  @override
  Widget build(BuildContext context) {
    const primaryGreen = Color(0xFF3AA76D);
    const backgroundGray = Color(0xFFF4F6F4);
    const darkGreen = Color(0xFF1E5631);

    final bool isProfilePage = _currentIndex == 4;

    return Scaffold(
      backgroundColor: backgroundGray,

      // APP BAR
      appBar: isProfilePage
          ? null
          : AppBar(
              automaticallyImplyLeading: false,
              backgroundColor: backgroundGray,
              elevation: 0,
              scrolledUnderElevation: 0,
              toolbarHeight: 155,
              titleSpacing: 0,

              title: Padding(
                padding: const EdgeInsets.fromLTRB(16, 20, 16, 10),

                child: Column(
                  mainAxisSize: MainAxisSize.min,

                  children: [
                    // ==================================================
                    // GREETING + ACTIONS
                    // ==================================================
                    Row(
                      children: [
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,

                            children: [
                              Text(
                                _currentIndex == 0
                                    ? '${getGreeting()}, $userName!'
                                    : _pageTitles[_currentIndex],

                                maxLines: 1,
                                overflow: TextOverflow.ellipsis,

                                style: const TextStyle(
                                  color: Colors.black87,
                                  fontSize: 22,
                                  fontWeight: FontWeight.bold,
                                ),
                              ),

                              const SizedBox(height: 5),

                              Text(
                                _currentIndex == 0
                                    ? 'Track your impact today.'
                                    : 'Manage your carbon footprint.',

                                style: const TextStyle(
                                  color: Colors.black54,
                                  fontSize: 14,
                                ),
                              ),
                            ],
                          ),
                        ),

                        const SizedBox(width: 10),

                        _buildNotificationButton(),

                        const SizedBox(width: 6),

                        _buildProfileMenuButton(),
                      ],
                    ),

                    const SizedBox(height: 16),

                    // ==================================================
                    // CARBON SCORE
                    // ==================================================
                    Container(
                      width: double.infinity,

                      padding: const EdgeInsets.symmetric(
                        horizontal: 16,
                        vertical: 13,
                      ),

                      decoration: BoxDecoration(
                        color: const Color(0xFFEAF6EE),
                        borderRadius: BorderRadius.circular(16),
                      ),

                      child: Column(
                        children: [
                          Row(
                            children: [
                              const Icon(
                                Icons.eco_outlined,
                                color: primaryGreen,
                                size: 22,
                              ),

                              const SizedBox(width: 8),

                              const Expanded(
                                child: Text(
                                  "CarbonWise Score",

                                  style: TextStyle(
                                    color: Colors.black87,
                                    fontSize: 15,
                                    fontWeight: FontWeight.w600,
                                  ),
                                ),
                              ),

                              Text(
                                "${_carbonScore.toStringAsFixed(0)}/100",

                                style: const TextStyle(
                                  color: darkGreen,
                                  fontWeight: FontWeight.bold,
                                  fontSize: 16,
                                ),
                              ),
                            ],
                          ),

                          const SizedBox(height: 9),

                          ClipRRect(
                            borderRadius: BorderRadius.circular(10),

                            child: LinearProgressIndicator(
                              value: (_carbonScore / 100).clamp(0.0, 1.0),

                              backgroundColor: const Color(0xFFCCEAD8),

                              valueColor: const AlwaysStoppedAnimation<Color>(
                                primaryGreen,
                              ),

                              minHeight: 9,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
            ),

      // ==========================================================
      // BODY
      // ==========================================================
      body: SafeArea(
        top: isProfilePage,

        child: IndexedStack(
          index: _currentIndex,
          children: List.generate(5, (i) {
            if (!_visitedIndices.contains(i)) {
              return const SizedBox.shrink();
            }
            switch (i) {
              case 0:
                return const DashboardScreen();
              case 1:
                return const ReportsScreen();
              case 2:
                return const ActivityInputScreen();
              case 3:
                return const StrategiesScreen();
              case 4:
                return const ProfileScreen();
              default:
                return const SizedBox.shrink();
            }
          }),
        ),
      ),

      // ==========================================================
      // BOTTOM NAVIGATION
      // ==========================================================
      bottomNavigationBar: Container(
        height: 78,

        decoration: BoxDecoration(
          color: Colors.white,

          boxShadow: [
            BoxShadow(
              color: Colors.black.withValues(alpha: 0.08),
              blurRadius: 12,
              offset: const Offset(0, -2),
            ),
          ],
        ),

        child: Stack(
          clipBehavior: Clip.none,
          alignment: Alignment.center,

          children: [
            Row(
              children: [
                Expanded(
                  child: _buildNavItem(
                    index: 0,
                    icon: Icons.home_outlined,
                    activeIcon: Icons.home,
                    label: "Home",
                  ),
                ),

                Expanded(
                  child: _buildNavItem(
                    index: 1,
                    icon: Icons.bar_chart_outlined,
                    activeIcon: Icons.bar_chart,
                    label: "Reports",
                  ),
                ),

                const SizedBox(width: 70),

                Expanded(
                  child: _buildNavItem(
                    index: 3,
                    icon: Icons.lightbulb_outline,
                    activeIcon: Icons.lightbulb,
                    label: "Strategies",
                  ),
                ),

                Expanded(
                  child: _buildNavItem(
                    index: 4,
                    icon: Icons.person_outline,
                    activeIcon: Icons.person,
                    label: "Profile",
                  ),
                ),
              ],
            ),

            // ======================================================
            // CENTER INPUT BUTTON
            // ======================================================
            Positioned(
              top: -24,

              child: GestureDetector(
                onTap: () {
                  setState(() {
                    _currentIndex = 2;
                    _visitedIndices.add(2);
                  });
                },

                child: Column(
                  mainAxisSize: MainAxisSize.min,

                  children: [
                    Container(
                      width: 58,
                      height: 58,

                      decoration: BoxDecoration(
                        color: const Color(0xFF3AA76D),

                        shape: BoxShape.circle,

                        border: Border.all(color: Colors.white, width: 5),

                        boxShadow: [
                          BoxShadow(
                            color: const Color(
                              0xFF3AA76D,
                            ).withValues(alpha: 0.3),

                            blurRadius: 10,

                            offset: const Offset(0, 3),
                          ),
                        ],
                      ),

                      child: const Icon(
                        Icons.add,
                        color: Colors.white,
                        size: 28,
                      ),
                    ),

                    const SizedBox(height: 2),

                    Text(
                      "Input",

                      style: TextStyle(
                        fontSize: 9,

                        fontWeight: _currentIndex == 2
                            ? FontWeight.bold
                            : FontWeight.w500,

                        color: _currentIndex == 2
                            ? const Color(0xFF265D3B)
                            : Colors.black45,
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  // NAVIGATION ITEM
  Widget _buildNavItem({
    required int index,
    required IconData icon,
    required IconData activeIcon,
    required String label,
  }) {
    final bool isSelected = _currentIndex == index;

    return InkWell(
      onTap: () {
        setState(() {
          // If leaving activity screen or clicking home/reports, refresh score to capture recent inputs
          _currentIndex = index;
          _visitedIndices.add(index);
        });
      },

      borderRadius: BorderRadius.circular(18),

      child: SizedBox(
        height: 72,

        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,

          children: [
            Icon(
              isSelected ? activeIcon : icon,

              size: 23,

              color: isSelected ? const Color(0xFF3AA76D) : Colors.black45,
            ),

            const SizedBox(height: 3),

            Text(
              label,

              style: TextStyle(
                fontSize: 9,

                fontWeight: isSelected ? FontWeight.bold : FontWeight.w500,

                color: isSelected ? const Color(0xFF265D3B) : Colors.black45,
              ),
            ),
          ],
        ),
      ),
    );
  }

  // NOTIFICATION BUTTON
  Widget _buildNotificationButton() {
    return PopupMenuButton<String>(
      offset: const Offset(0, 48),

      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),

      color: Colors.white,

      onSelected: (value) {
        if (value == 'all') {
          _showAllNotifications();
        }
      },

      child: _circleIcon(Icons.notifications_none),

      itemBuilder: (context) {
        if (notifications.isEmpty) {
          return [
            const PopupMenuItem<String>(
              enabled: false,

              child: SizedBox(
                width: 340,

                child: Padding(
                  padding: EdgeInsets.symmetric(vertical: 8),

                  child: Center(
                    child: Text(
                      "No notifications yet.",
                      style: TextStyle(color: Colors.black54),
                    ),
                  ),
                ),
              ),
            ),
          ];
        }

        return [
          const PopupMenuItem<String>(
            enabled: false,

            child: SizedBox(
              width: 340,

              child: Text(
                "Notifications",

                style: TextStyle(
                  fontWeight: FontWeight.bold,
                  fontSize: 16,
                  color: Color(0xFF1E5631),
                ),
              ),
            ),
          ),

          ...notifications.take(3).map((notification) {
            IconData icon = Icons.notifications;

            switch (notification['type']) {
              case 'success':
                icon = Icons.check_circle;
                break;

              case 'info':
                icon = Icons.info;
                break;

              case 'warning':
                icon = Icons.warning_amber_rounded;
                break;

              case 'achievement':
                icon = Icons.emoji_events;
                break;
            }

            return PopupMenuItem<String>(
              enabled: false,

              child: SizedBox(
                width: 340,

                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,

                  children: [
                    Row(
                      crossAxisAlignment: CrossAxisAlignment.start,

                      children: [
                        Icon(icon, size: 18, color: const Color(0xFF3AA76D)),

                        const SizedBox(width: 8),

                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,

                            children: [
                              Text(
                                notification['title'] ?? '',

                                style: const TextStyle(
                                  fontWeight: FontWeight.bold,
                                  color: Color(0xFF1E5631),
                                ),
                              ),

                              const SizedBox(height: 4),

                              Text(
                                notification['message'] ?? '',

                                style: const TextStyle(fontSize: 13),
                              ),
                            ],
                          ),
                        ),
                      ],
                    ),

                    const SizedBox(height: 8),

                    const Divider(thickness: 0.8),
                  ],
                ),
              ),
            );
          }),

          const PopupMenuDivider(),

          const PopupMenuItem<String>(
            value: 'all',

            child: Center(
              child: Text(
                "Show all notifications",

                style: TextStyle(
                  color: Color(0xFF3AA76D),
                  fontWeight: FontWeight.bold,
                ),
              ),
            ),
          ),
        ];
      },
    );
  }

  // PROFILE MENU
  Widget _buildProfileMenuButton() {
    return PopupMenuButton<String>(
      offset: const Offset(0, 48),

      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),

      color: Colors.white,

      child: _circleIcon(Icons.person_outline),

      onSelected: (value) {
        if (value == 'manage_profile') {
          setState(() {
            _currentIndex = 4;
          });
        }

        if (value == 'change_password') {
          _changePassword();
        }

        if (value == 'logout') {
          DialogHelper.showConfirm(
            context: context,

            title: "Log Out",

            message: "Are you sure you want to log out of CarbonWise?",

            onConfirm: () async {
              await _logout();
            },
          );
        }
      },

      itemBuilder: (context) => const <PopupMenuEntry<String>>[
        PopupMenuItem<String>(
          value: 'manage_profile',

          child: Row(
            children: [
              Icon(Icons.manage_accounts_outlined, color: Color(0xFF1E5631)),

              SizedBox(width: 10),

              Text('Manage Profile'),
            ],
          ),
        ),

        PopupMenuItem<String>(
          value: 'change_password',

          child: Row(
            children: [
              Icon(Icons.lock_outline, color: Color(0xFF1E5631)),

              SizedBox(width: 10),

              Text('Change Password'),
            ],
          ),
        ),

        PopupMenuDivider(),

        PopupMenuItem<String>(
          value: 'logout',

          child: Row(
            children: [
              Icon(Icons.logout, color: Colors.red),

              SizedBox(width: 10),

              Text('Log Out', style: TextStyle(color: Colors.red)),
            ],
          ),
        ),
      ],
    );
  }

  // CIRCLE ICON
  Widget _circleIcon(IconData icon) {
    return Container(
      width: 40,
      height: 40,

      decoration: const BoxDecoration(
        color: Colors.white,
        shape: BoxShape.circle,
      ),

      child: Icon(icon, color: const Color(0xFF3AA76D), size: 24),
    );
  }

  // LOGOUT
  Future<void> _logout() async {
    try {
      await ApiService.logout();
      NotificationService.reset();

      // Reset local navigation states
      if (mounted) {
        setState(() {
          userName = 'User';
          _carbonScore = 100;
          notifications = [];
          _currentIndex = 0;
        });
      }

      if (!mounted) return;

      DialogHelper.showSuccess(
        context: context,
        title: "Logged Out",
        message: "You have been logged out successfully.",
        onOk: () {
          Navigator.pushAndRemoveUntil(
            context,
            MaterialPageRoute(builder: (_) => const LoginScreen()),
            (route) => false,
          );
        },
      );
    } catch (e) {
      if (!mounted) return;
      DialogHelper.showError(
        context: context,
        title: "Logout Failed",
        message: "Unable to log out. Please try again.",
      );
    }
  }

  // CHANGE PASSWORD
  Future<void> _changePassword() async {
    final currentPasswordController = TextEditingController();
    final passwordController = TextEditingController();
    final confirmPasswordController = TextEditingController();

    const primaryGreen = Color(0xFF3AA76D);
    const darkGreen = Color(0xFF1E5631);

    try {
      final bool? success = await showDialog<bool>(
        context: context,
        barrierDismissible: false,
        builder: (dialogContext) {
          bool isLoading = false;
          bool obscureCurrent = true;
          bool obscureNew = true;
          bool obscureConfirm = true;
          String? errorMessage;

          return StatefulBuilder(
            builder: (context, setDialogState) {
              // Live password strength calculation
              final newPass = passwordController.text;
              final strength = _passwordStrength(newPass);
              final strengthColor = _passwordStrengthColor(strength);
              final strengthLabel = _passwordStrengthLabel(strength);
              final hasMinLength = newPass.length >= 8;
              final hasNumber = newPass.contains(RegExp(r'[0-9]'));
              final hasLetter = newPass.contains(RegExp(r'[a-zA-Z]'));

              return Dialog(
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(20),
                ),
                insetPadding: const EdgeInsets.symmetric(
                  horizontal: 20,
                  vertical: 24,
                ),
                child: ConstrainedBox(
                  constraints: const BoxConstraints(maxWidth: 420),
                  child: SingleChildScrollView(
                    child: Padding(
                      padding: const EdgeInsets.all(22),
                      child: Column(
                        mainAxisSize: MainAxisSize.min,
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          // ============================
                          // HEADER
                          // ============================
                          Row(
                            children: [
                              Container(
                                width: 44,
                                height: 44,
                                decoration: const BoxDecoration(
                                  color: Color(0xFFE8F5EE),
                                  shape: BoxShape.circle,
                                ),
                                child: const Icon(
                                  Icons.lock_reset_rounded,
                                  color: primaryGreen,
                                  size: 22,
                                ),
                              ),
                              const SizedBox(width: 12),
                              const Expanded(
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Text(
                                      'Change Password',
                                      style: TextStyle(
                                        color: darkGreen,
                                        fontWeight: FontWeight.bold,
                                        fontSize: 18,
                                      ),
                                    ),
                                    SizedBox(height: 2),
                                    Text(
                                      'Keep your account secure',
                                      style: TextStyle(
                                        fontSize: 12,
                                        color: Colors.black54,
                                      ),
                                    ),
                                  ],
                                ),
                              ),
                              IconButton(
                                onPressed: isLoading
                                    ? null
                                    : () => Navigator.of(
                                        dialogContext,
                                      ).pop(false),
                                icon: const Icon(
                                  Icons.close_rounded,
                                  color: Colors.black45,
                                ),
                              ),
                            ],
                          ),

                          const SizedBox(height: 20),

                          // ============================
                          // CURRENT PASSWORD
                          // ============================
                          _passwordField(
                            controller: currentPasswordController,
                            label: 'Current Password',
                            hint: 'Enter your current password',
                            obscure: obscureCurrent,
                            enabled: !isLoading,
                            onToggle: () => setDialogState(
                              () => obscureCurrent = !obscureCurrent,
                            ),
                          ),

                          const SizedBox(height: 16),

                          // ============================
                          // NEW PASSWORD
                          // ============================
                          _passwordField(
                            controller: passwordController,
                            label: 'New Password',
                            hint: 'At least 8 characters',
                            obscure: obscureNew,
                            enabled: !isLoading,
                            onChanged: (_) => setDialogState(() {}),
                            onToggle: () =>
                                setDialogState(() => obscureNew = !obscureNew),
                          ),

                          const SizedBox(height: 10),

                          // Strength bar
                          if (newPass.isNotEmpty) ...[
                            Row(
                              children: [
                                Expanded(
                                  child: ClipRRect(
                                    borderRadius: BorderRadius.circular(4),
                                    child: LinearProgressIndicator(
                                      value: strength,
                                      minHeight: 6,
                                      backgroundColor: const Color(0xFFE5E7EB),
                                      valueColor: AlwaysStoppedAnimation<Color>(
                                        strengthColor,
                                      ),
                                    ),
                                  ),
                                ),
                                const SizedBox(width: 10),
                                Text(
                                  strengthLabel,
                                  style: TextStyle(
                                    fontSize: 11,
                                    fontWeight: FontWeight.bold,
                                    color: strengthColor,
                                  ),
                                ),
                              ],
                            ),
                            const SizedBox(height: 10),
                          ],

                          // Requirements checklist
                          if (newPass.isNotEmpty)
                            Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                _requirement(
                                  'At least 8 characters',
                                  hasMinLength,
                                ),
                                const SizedBox(height: 4),
                                _requirement('Contains a letter', hasLetter),
                                const SizedBox(height: 4),
                                _requirement('Contains a number', hasNumber),
                              ],
                            ),

                          const SizedBox(height: 16),

                          // ============================
                          // CONFIRM PASSWORD
                          // ============================
                          _passwordField(
                            controller: confirmPasswordController,
                            label: 'Confirm New Password',
                            hint: 'Re-enter your new password',
                            obscure: obscureConfirm,
                            enabled: !isLoading,
                            onChanged: (_) => setDialogState(() {}),
                            onToggle: () => setDialogState(
                              () => obscureConfirm = !obscureConfirm,
                            ),
                          ),

                          // Live mismatch indicator
                          if (confirmPasswordController.text.isNotEmpty &&
                              passwordController.text.isNotEmpty &&
                              confirmPasswordController.text !=
                                  passwordController.text) ...[
                            const SizedBox(height: 6),
                            const Row(
                              children: [
                                Icon(
                                  Icons.error_outline_rounded,
                                  size: 14,
                                  color: Colors.redAccent,
                                ),
                                SizedBox(width: 4),
                                Text(
                                  'Passwords do not match',
                                  style: TextStyle(
                                    fontSize: 11,
                                    color: Colors.redAccent,
                                    fontWeight: FontWeight.w500,
                                  ),
                                ),
                              ],
                            ),
                          ],

                          // Server error
                          if (errorMessage != null) ...[
                            const SizedBox(height: 12),
                            Container(
                              width: double.infinity,
                              padding: const EdgeInsets.all(10),
                              decoration: BoxDecoration(
                                color: const Color(0xFFFDECEC),
                                borderRadius: BorderRadius.circular(10),
                              ),
                              child: Row(
                                children: [
                                  const Icon(
                                    Icons.error_outline_rounded,
                                    color: Colors.redAccent,
                                    size: 18,
                                  ),
                                  const SizedBox(width: 8),
                                  Expanded(
                                    child: Text(
                                      errorMessage!,
                                      style: const TextStyle(
                                        fontSize: 12,
                                        color: Colors.redAccent,
                                      ),
                                    ),
                                  ),
                                ],
                              ),
                            ),
                          ],

                          const SizedBox(height: 22),

                          // ============================
                          // ACTIONS
                          // ============================
                          Row(
                            children: [
                              Expanded(
                                child: OutlinedButton(
                                  onPressed: isLoading
                                      ? null
                                      : () => Navigator.of(
                                          dialogContext,
                                        ).pop(false),
                                  style: OutlinedButton.styleFrom(
                                    foregroundColor: darkGreen,
                                    side: BorderSide(
                                      color: Colors.grey.shade300,
                                    ),
                                    padding: const EdgeInsets.symmetric(
                                      vertical: 14,
                                    ),
                                    shape: RoundedRectangleBorder(
                                      borderRadius: BorderRadius.circular(12),
                                    ),
                                  ),
                                  child: const Text(
                                    'Cancel',
                                    style: TextStyle(
                                      fontWeight: FontWeight.bold,
                                    ),
                                  ),
                                ),
                              ),
                              const SizedBox(width: 12),
                              Expanded(
                                flex: 2,
                                child: ElevatedButton(
                                  onPressed: isLoading
                                      ? null
                                      : () async {
                                          final currentPass =
                                              currentPasswordController.text
                                                  .trim();
                                          final newPass = passwordController
                                              .text
                                              .trim();
                                          final confirmPass =
                                              confirmPasswordController.text
                                                  .trim();

                                          // Clear old error
                                          setDialogState(
                                            () => errorMessage = null,
                                          );

                                          // Validate
                                          if (currentPass.isEmpty ||
                                              newPass.isEmpty ||
                                              confirmPass.isEmpty) {
                                            setDialogState(() {
                                              errorMessage =
                                                  'Please fill in all password fields.';
                                            });
                                            return;
                                          }

                                          if (newPass.length < 8) {
                                            setDialogState(() {
                                              errorMessage =
                                                  'New password must be at least 8 characters.';
                                            });
                                            return;
                                          }

                                          if (newPass != confirmPass) {
                                            setDialogState(() {
                                              errorMessage =
                                                  'New passwords do not match.';
                                            });
                                            return;
                                          }

                                          if (newPass == currentPass) {
                                            setDialogState(() {
                                              errorMessage =
                                                  'New password must be different from the current one.';
                                            });
                                            return;
                                          }

                                          setDialogState(
                                            () => isLoading = true,
                                          );

                                          try {
                                            await _apiService.changePassword(
                                              currentPassword: currentPass,
                                              newPassword: newPass,
                                            );

                                            if (!dialogContext.mounted) return;
                                            Navigator.of(
                                              dialogContext,
                                            ).pop(true);
                                          } catch (error) {
                                            if (!dialogContext.mounted) return;
                                            setDialogState(() {
                                              isLoading = false;
                                              errorMessage = error
                                                  .toString()
                                                  .replaceFirst(
                                                    'Exception: ',
                                                    '',
                                                  );
                                            });
                                          }
                                        },
                                  style: ElevatedButton.styleFrom(
                                    backgroundColor: primaryGreen,
                                    foregroundColor: Colors.white,
                                    padding: const EdgeInsets.symmetric(
                                      vertical: 14,
                                    ),
                                    shape: RoundedRectangleBorder(
                                      borderRadius: BorderRadius.circular(12),
                                    ),
                                    elevation: 0,
                                  ),
                                  child: isLoading
                                      ? const SizedBox(
                                          width: 20,
                                          height: 20,
                                          child: CircularProgressIndicator(
                                            color: Colors.white,
                                            strokeWidth: 2,
                                          ),
                                        )
                                      : const Text(
                                          'Update Password',
                                          style: TextStyle(
                                            fontWeight: FontWeight.bold,
                                            fontSize: 14,
                                          ),
                                        ),
                                ),
                              ),
                            ],
                          ),
                        ],
                      ),
                    ),
                  ),
                ),
              );
            },
          );
        },
      );

      if (success == true) {
        if (!mounted) return;

        DialogHelper.showSuccess(
          context: context,
          title: "Password Updated",
          message:
              "Your password has been changed successfully. Please log in again with your new password.",
          onOk: () async {
            await ApiService.logout();
            if (!mounted) return;
            Navigator.pushAndRemoveUntil(
              context,
              MaterialPageRoute(builder: (_) => const LoginScreen()),
              (route) => false,
            );
          },
        );
      }
    } catch (error) {
      if (!mounted) return;
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(SnackBar(content: Text('Error: $error')));
    }
  }

  // Password strength: 0.0 to 1.0
  double _passwordStrength(String password) {
    if (password.isEmpty) return 0;
    double score = 0;
    if (password.length >= 8) score += 0.25;
    if (password.length >= 12) score += 0.15;
    if (password.contains(RegExp(r'[a-z]'))) score += 0.15;
    if (password.contains(RegExp(r'[A-Z]'))) score += 0.15;
    if (password.contains(RegExp(r'[0-9]'))) score += 0.15;
    if (password.contains(RegExp(r'[^a-zA-Z0-9]'))) score += 0.15;
    return score.clamp(0.0, 1.0);
  }

  Color _passwordStrengthColor(double strength) {
    if (strength < 0.4) return Colors.redAccent;
    if (strength < 0.7) return Colors.orange;
    return const Color(0xFF3AA76D);
  }

  String _passwordStrengthLabel(double strength) {
    if (strength == 0) return '';
    if (strength < 0.4) return 'Weak';
    if (strength < 0.7) return 'Fair';
    if (strength < 0.9) return 'Good';
    return 'Strong';
  }

  // Reusable password field
  Widget _passwordField({
    required TextEditingController controller,
    required String label,
    required String hint,
    required bool obscure,
    required bool enabled,
    required VoidCallback onToggle,
    ValueChanged<String>? onChanged,
  }) {
    const primaryGreen = Color(0xFF3AA76D);

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          label,
          style: const TextStyle(
            fontSize: 12,
            fontWeight: FontWeight.bold,
            color: Color(0xFF1F2933),
          ),
        ),
        const SizedBox(height: 6),
        TextField(
          controller: controller,
          obscureText: obscure,
          enabled: enabled,
          onChanged: onChanged,
          style: const TextStyle(fontSize: 14),
          decoration: InputDecoration(
            hintText: hint,
            hintStyle: const TextStyle(fontSize: 13, color: Color(0xFFB0B0B0)),
            prefixIcon: const Icon(
              Icons.lock_outline_rounded,
              color: primaryGreen,
              size: 20,
            ),
            suffixIcon: IconButton(
              splashRadius: 20,
              icon: Icon(
                obscure
                    ? Icons.visibility_off_rounded
                    : Icons.visibility_rounded,
                color: Colors.grey,
                size: 20,
              ),
              onPressed: enabled ? onToggle : null,
            ),
            filled: true,
            fillColor: const Color(0xFFF7F9F8),
            contentPadding: const EdgeInsets.symmetric(
              horizontal: 14,
              vertical: 14,
            ),
            border: OutlineInputBorder(
              borderRadius: BorderRadius.circular(12),
              borderSide: BorderSide.none,
            ),
            enabledBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(12),
              borderSide: BorderSide.none,
            ),
            focusedBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(12),
              borderSide: const BorderSide(color: primaryGreen, width: 1.5),
            ),
            disabledBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(12),
              borderSide: BorderSide.none,
            ),
          ),
        ),
      ],
    );
  }

  // Requirement checklist row
  Widget _requirement(String text, bool met) {
    return Row(
      children: [
        Icon(
          met
              ? Icons.check_circle_rounded
              : Icons.radio_button_unchecked_rounded,
          size: 14,
          color: met ? const Color(0xFF3AA76D) : Colors.grey.shade400,
        ),
        const SizedBox(width: 6),
        Text(
          text,
          style: TextStyle(
            fontSize: 11,
            color: met ? const Color(0xFF3AA76D) : Colors.black54,
            fontWeight: met ? FontWeight.w600 : FontWeight.normal,
          ),
        ),
      ],
    );
  }

  // ALL NOTIFICATIONS
  void _showAllNotifications() {
    showDialog(
      context: context,

      builder: (_) {
        return Dialog(
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(18),
          ),

          child: Container(
            width: 420,

            constraints: const BoxConstraints(maxHeight: 550),

            padding: const EdgeInsets.all(18),

            child: Column(
              children: [
                const Text(
                  "All Notifications",

                  style: TextStyle(
                    fontSize: 20,
                    fontWeight: FontWeight.bold,
                    color: Color(0xFF1E5631),
                  ),
                ),

                const SizedBox(height: 15),

                Expanded(
                  child: notifications.isEmpty
                      ? const Center(
                          child: Text(
                            "No notifications yet.",
                            style: TextStyle(color: Colors.black54),
                          ),
                        )
                      : ListView.separated(
                          itemCount: notifications.length,

                          separatorBuilder: (_, __) =>
                              const Divider(thickness: 0.8),

                          itemBuilder: (context, index) {
                            final notification = notifications[index];

                            IconData icon = Icons.notifications;

                            switch (notification['type']) {
                              case 'success':
                                icon = Icons.check_circle;
                                break;

                              case 'warning':
                                icon = Icons.warning_amber_rounded;
                                break;

                              case 'achievement':
                                icon = Icons.emoji_events;
                                break;

                              case 'info':
                                icon = Icons.info;
                                break;
                            }

                            return ListTile(
                              leading: CircleAvatar(
                                backgroundColor: const Color(0xFFCCEAD8),

                                child: Icon(
                                  icon,

                                  color: const Color(0xFF3AA76D),
                                ),
                              ),

                              title: Text(
                                notification['title'] ?? '',

                                style: const TextStyle(
                                  fontWeight: FontWeight.bold,
                                ),
                              ),

                              subtitle: Padding(
                                padding: const EdgeInsets.only(top: 4),

                                child: Text(notification['message'] ?? ''),
                              ),
                            );
                          },
                        ),
                ),

                const SizedBox(height: 15),

                SizedBox(
                  width: double.infinity,

                  child: ElevatedButton(
                    style: ElevatedButton.styleFrom(
                      backgroundColor: const Color(0xFF3AA76D),

                      foregroundColor: Colors.white,
                    ),

                    onPressed: () => Navigator.pop(context),

                    child: const Text("Close"),
                  ),
                ),
              ],
            ),
          ),
        );
      },
    );
  }
}
