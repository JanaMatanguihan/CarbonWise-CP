import 'dart:convert';

import 'package:carbonwise_app/screens/activity.dart';
import 'package:carbonwise_app/screens/dashboard.dart';
import 'package:carbonwise_app/screens/profile.dart';
import 'package:carbonwise_app/screens/reports.dart';
import 'package:carbonwise_app/screens/strategies.dart';
import 'package:crypto/crypto.dart';
import 'package:flutter/material.dart';
import 'package:supabase_flutter/supabase_flutter.dart';
import '../utils/dialog_helper.dart';
import 'package:carbonwise_app/main.dart';
import 'package:carbonwise_app/widgets/chatbot_button.dart';
import 'package:carbonwise_app/utils/profile_refresh_notifier.dart';
import 'package:carbonwise_app/utils/carbon_score_refresh_notifier.dart';

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

  final List<String> _pageTitles = [
    '',
    'View your Reports',
    'Activity Input',
    'Mitigation Strategies',
    'User Profile Summary',
  ];

  final List<Widget> _pages = [
    DashboardScreen(),
    ReportsScreen(),
    ActivityInputScreen(),
    StrategiesScreen(),
    ProfileScreen(),
  ];

  Future<void> loadNotifications() async {
    final user = Supabase.instance.client.auth.currentUser;

    if (user == null) return;

    try {
      final data = await Supabase.instance.client
          .from('notifications')
          .select()
          .eq('g_suite', user.email!)
          .order('created_at', ascending: false);

      if (!mounted) return;

      setState(() {
        notifications = List<Map<String, dynamic>>.from(data);
      });
    } catch (e) {
      print("Error loading notifications: $e");
    }
  }

  @override
  void initState() {
    super.initState();
    loadUserName();
    _loadCarbonScore();
    profileRefreshNotifier.addListener(_refreshProfile);
    loadNotifications();
    carbonScoreRefreshNotifier.addListener(_loadCarbonScore);
  }

  @override
  void dispose() {
    carbonScoreRefreshNotifier.removeListener(_loadCarbonScore);
    super.dispose();
  }

  Future<void> _refreshProfile() async {
    await loadUserName();
  }

  Future<void> loadUserName() async {
    final user = Supabase.instance.client.auth.currentUser;

    if (user == null) {
      print("No logged in user");
      return;
    }

    print("Logged in email: ${user.email}");

    try {
      final response = await Supabase.instance.client
          .from('user_info')
          .select('full_name')
          .eq('g_suite', user.email!)
          .single();

      print("Database response: $response");

      if (!mounted) return;

      setState(() {
        userName = response['full_name'] ?? 'User';
      });

      print("Loaded name: $userName");
    } catch (e) {
      print("ERROR: $e");
    }
  }

  Future<void> _loadCarbonScore() async {
    try {
      final supabase = Supabase.instance.client;
      final user = supabase.auth.currentUser;

      if (user == null) return;

      final today = DateTime.now();

      final todayString =
          "${today.year.toString().padLeft(4, '0')}-"
          "${today.month.toString().padLeft(2, '0')}-"
          "${today.day.toString().padLeft(2, '0')}";

      final records = await supabase
          .from('carbon_records')
          .select()
          .eq('g_suite', user.email!)
          .eq('record_date', todayString);

      double transportation = 0;
      double electricity = 0;
      double food = 0;

      for (final record in records) {
        transportation += (record['transportation'] as num?)?.toDouble() ?? 0;

        electricity += (record['electricity'] as num?)?.toDouble() ?? 0;

        food += (record['food'] as num?)?.toDouble() ?? 0;
      }

      final total = transportation + electricity + food;

      double score = 100 - total;

      if (score < 0) score = 0;
      if (score > 100) score = 100;

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

  @override
  Widget build(BuildContext context) {
    const primaryGreen = Color(0xFF3AA76D);
    const backgroundGray = Color(0xFFF4F6F4);
    const darkGreen = Color(0xFF1E5631);

    final bool isProfilePage = _currentIndex == 4;

    return Scaffold(
      backgroundColor: backgroundGray,
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
                padding: const EdgeInsets.fromLTRB(
                  16,
                  20, // more space at the top
                  16,
                  10,
                ),
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    // =========================
                    // GREETING + ACTIONS
                    // =========================
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

                    // =========================
                    // CARBON SCORE
                    // =========================
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
      body: SafeArea(
        top: isProfilePage,
        child: IndexedStack(index: _currentIndex, children: _pages),
      ),
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

                // Space for center button
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

            // CENTER INPUT BUTTON
            Positioned(
              top: -24,
              child: GestureDetector(
                onTap: () {
                  setState(() {
                    _currentIndex = 2;
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
      floatingActionButton: const ChatbotButton(),
    );
  }

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
          _currentIndex = index;
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
          }).toList(),

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

  Widget _buildProfileMenuButton() {
    return PopupMenuButton<String>(
      offset: const Offset(0, 48),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      color: Colors.white,
      child: _circleIcon(Icons.person_outline),
      onSelected: (value) {
        if (value == 'manage_profile') {
          setState(() => _currentIndex = 4);
        } else if (value == 'change_password') {
          _changePassword();
        } else if (value == 'logout') {
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

  Widget _circleIcon(IconData icon) {
    return Container(
      width: 40,
      height: 40,
      decoration: const BoxDecoration(
        color: Colors.white,
        shape: BoxShape.circle,
      ),
      child: Icon(icon, color: Color(0xFF3AA76D), size: 24),
    );
  }

  Future<void> _logout() async {
    try {
      await Supabase.instance.client.auth.signOut();

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

  Future<void> _changePassword() async {
    final currentPasswordController = TextEditingController();
    final passwordController = TextEditingController();
    final confirmPasswordController = TextEditingController();

    try {
      final newPassword = await showDialog<String>(
        context: context,
        builder: (dialogContext) {
          return AlertDialog(
            title: const Text('Change Password'),
            content: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                TextField(
                  controller: currentPasswordController,
                  obscureText: true,
                  decoration: const InputDecoration(
                    labelText: 'Current Password',
                    border: OutlineInputBorder(),
                  ),
                ),
                const SizedBox(height: 12),
                TextField(
                  controller: passwordController,
                  obscureText: true,
                  decoration: const InputDecoration(
                    labelText: 'New Password',
                    border: OutlineInputBorder(),
                  ),
                ),
                const SizedBox(height: 12),
                TextField(
                  controller: confirmPasswordController,
                  obscureText: true,
                  decoration: const InputDecoration(
                    labelText: 'Confirm Password',
                    border: OutlineInputBorder(),
                  ),
                ),
              ],
            ),
            actions: [
              TextButton(
                onPressed: () => Navigator.of(dialogContext).pop(),
                child: const Text('Cancel'),
              ),
              ElevatedButton(
                onPressed: () {
                  final password = passwordController.text.trim();
                  final confirmPassword = confirmPasswordController.text.trim();

                  if (password.isEmpty || confirmPassword.isEmpty) {
                    ScaffoldMessenger.of(context).showSnackBar(
                      const SnackBar(
                        content: Text('Please fill in both fields.'),
                      ),
                    );
                    return;
                  }

                  if (password.length < 6) {
                    ScaffoldMessenger.of(context).showSnackBar(
                      const SnackBar(
                        content: Text(
                          'Password must be at least 6 characters.',
                        ),
                      ),
                    );
                    return;
                  }

                  if (password != confirmPassword) {
                    ScaffoldMessenger.of(context).showSnackBar(
                      const SnackBar(content: Text('Passwords do not match.')),
                    );
                    return;
                  }

                  Navigator.of(dialogContext).pop(password);
                },
                child: const Text('Save'),
              ),
            ],
          );
        },
      );

      if (newPassword == null) return;

      // Password change logic
      await Supabase.instance.client.auth.updateUser(
        UserAttributes(password: newPassword),
      );

      final hashedPassword = sha256
          .convert(utf8.encode(newPassword))
          .toString();
      final userEmail = Supabase.instance.client.auth.currentUser?.email;

      if (userEmail != null) {
        await Supabase.instance.client
            .from('user_info')
            .update({
              'password_hash': hashedPassword,
              'password_updated_at': DateTime.now().toIso8601String(),
            })
            .eq('g_suite', userEmail);
      }

      await Supabase.instance.client.auth.signOut();

      if (!mounted) return;

      Navigator.of(
        context,
        rootNavigator: true,
      ).pushNamedAndRemoveUntil('/landing', (route) => false);
    } catch (error) {
      if (!mounted) return;
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(SnackBar(content: Text('Error: $error')));
    } finally {
      // Controllers are safely disposed after the dialog and navigation actions
      passwordController.dispose();
      confirmPasswordController.dispose();
    }
  }

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
