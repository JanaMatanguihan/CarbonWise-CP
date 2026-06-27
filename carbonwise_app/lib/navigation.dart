import 'package:flutter/material.dart';
import 'package:carbonwise_app/strategies.dart';
import 'package:carbonwise_app/dashboard.dart';
import 'package:carbonwise_app/reports.dart';
import 'package:carbonwise_app/activity.dart';
import 'package:carbonwise_app/profile.dart';
import 'package:supabase_flutter/supabase_flutter.dart';

class CustomMainNavigation extends StatefulWidget {
  const CustomMainNavigation({super.key});

  @override
  State<CustomMainNavigation> createState() => _CustomMainNavigationState();
}

class _CustomMainNavigationState extends State<CustomMainNavigation> {
  int _currentIndex = 0;

  String userName = 'User';

  Future<void> loadUserName() async {
    final user = Supabase.instance.client.auth.currentUser;

    if (user == null) return;

    setState(() {
      userName = user.userMetadata?['full_name'] ?? 'User';
    });

    print('Logged in as: $userName');
  }

  @override
  void initState() {
    super.initState();
    loadUserName();
  }

  final List<String> _pageTitles = [
    '',
    'View your Reports',
    'Activity Input',
    'Mitigation Strategies',
    'User Profile Summary',
  ];

  final List<Widget> _pages = [
    const DashboardScreen(),
    const ReportsScreen(),
    const ActivityInputScreen(),
    const StrategiesScreen(),
    const ProfileScreen(),
  ];

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
              titleSpacing: 14,
              title: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Container(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 14.0,
                      vertical: 14.0,
                    ),
                    decoration: BoxDecoration(
                      color: primaryGreen,
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Flexible(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              Text(
                                _currentIndex == 0
                                    ? 'Good morning, $userName!'
                                    : _pageTitles[_currentIndex],
                                style: const TextStyle(
                                  color: Colors.white,
                                  fontSize: 22,
                                  fontWeight: FontWeight.bold,
                                  height: 1.2,
                                ),
                              ),
                              const SizedBox(height: 4),
                              const Text(
                                'Track your impact today.',
                                style: TextStyle(
                                  color: Colors.white70,
                                  fontSize: 15,
                                ),
                              ),
                            ],
                          ),
                        ),
                        const SizedBox(width: 8),
                        Row(
                          children: [
                            _buildNotificationButton(),
                            const SizedBox(width: 6),
                            _buildProfileMenuButton(context),
                          ],
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 12),

                  Column(
                    children: [
                      ClipRRect(
                        borderRadius: BorderRadius.circular(10),
                        child: const LinearProgressIndicator(
                          value: 0.45,
                          backgroundColor: Color(0xFFCCEAD8),
                          valueColor: AlwaysStoppedAnimation<Color>(darkGreen),
                          minHeight: 12,
                        ),
                      ),
                      const SizedBox(height: 4),
                      const Text(
                        'Green Points',
                        style: TextStyle(
                          color: darkGreen,
                          fontWeight: FontWeight.bold,
                          fontStyle: FontStyle.italic,
                          fontSize: 15,
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),

      body: SafeArea(
        top: isProfilePage,
        child: IndexedStack(index: _currentIndex, children: _pages),
      ),

      bottomNavigationBar: SafeArea(
        top: false,
        child: Padding(
          padding: const EdgeInsets.fromLTRB(12.0, 0, 12.0, 12.0),
          child: Container(
            height: 72,
            decoration: BoxDecoration(
              color: const Color(0xFF3AA76D),
              borderRadius: BorderRadius.circular(30),
              boxShadow: [
                BoxShadow(
                  color: Colors.black.withOpacity(0.15),
                  blurRadius: 10,
                  offset: const Offset(0, -2),
                ),
              ],
            ),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceAround,
              children: [
                _buildNavItem(Icons.home_outlined, Icons.home, 'Home', 0),
                _buildNavItem(
                  Icons.analytics_outlined,
                  Icons.analytics,
                  'Reports',
                  1,
                ),
                _buildNavItem(
                  Icons.edit_note_outlined,
                  Icons.edit_note,
                  'Input',
                  2,
                ),
                _buildNavItem(
                  Icons.lightbulb_outline,
                  Icons.lightbulb,
                  'Strategies',
                  3,
                ),
                _buildNavItem(Icons.person_outline, Icons.person, 'Profile', 4),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildCircleIcon(IconData icon) {
    return Container(
      padding: const EdgeInsets.all(7),
      decoration: BoxDecoration(
        color: Colors.white.withOpacity(0.2),
        shape: BoxShape.circle,
      ),
      child: Icon(icon, color: Colors.white, size: 22),
    );
  }

  Widget _buildNavItem(
    IconData unselectedIcon,
    IconData selectedIcon,
    String label,
    int index,
  ) {
    final bool isSelected = _currentIndex == index;

    return Expanded(
      child: GestureDetector(
        onTap: () => setState(() => _currentIndex = index),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(
              isSelected ? selectedIcon : unselectedIcon,
              color: Colors.white,
              size: isSelected ? 28 : 24,
            ),
            const SizedBox(height: 3),
            Text(
              label,
              style: TextStyle(
                color: Colors.white,
                fontSize: 12,
                fontWeight: isSelected ? FontWeight.bold : FontWeight.normal,
              ),
            ),
          ],
        ),
      ),
    );
  }
}

Widget _buildNotificationButton() {
  return PopupMenuButton<String>(
    offset: const Offset(0, 48),
    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
    color: Colors.white,
    child: _circleIcon(Icons.notifications_none),
    itemBuilder: (context) => const <PopupMenuEntry<String>>[
      PopupMenuItem<String>(
        enabled: false,
        child: SizedBox(
          width: 260,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                'Notifications',
                style: TextStyle(
                  fontWeight: FontWeight.bold,
                  color: Color(0xFF1E5631),
                ),
              ),
              SizedBox(height: 8),
              Text(
                'Reminder: Do not forget to complete your green activity today.',
                style: TextStyle(color: Colors.black87, fontSize: 13),
              ),
            ],
          ),
        ),
      ),
    ],
  );
}

Widget _buildProfileMenuButton(BuildContext context) {
  return PopupMenuButton<String>(
    offset: const Offset(0, 48),
    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
    color: Colors.white,
    child: _circleIcon(Icons.person_outline),
    onSelected: (value) {
      if (value == 'manage_profile') {
        Navigator.pushNamed(context, '/manage-profile');
      } else if (value == 'change_password') {
        Navigator.pushNamed(context, '/change-password');
      } else if (value == 'logout') {
        _logout(context);
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

Future<void> _logout(BuildContext context) async {
  final navigator = Navigator.of(context);

  await Supabase.instance.client.auth.signOut();

  navigator.pushNamedAndRemoveUntil('/', (route) => false);
}
