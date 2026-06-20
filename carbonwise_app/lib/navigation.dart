import 'package:flutter/material.dart';
import 'package:carbonwise_app/dashboard.dart'; // Make sure you rename your file to match this spelling!

class CustomMainNavigation extends StatefulWidget {
  const CustomMainNavigation({super.key});

  @override
  State<CustomMainNavigation> createState() => _CustomMainNavigationState();
}

class _CustomMainNavigationState extends State<CustomMainNavigation> {
  int _currentIndex = 0;

  // Destination screens corresponding to the navbar tabs
  final List<Widget> _pages = [
    const MainDashboardScreen(),
    const Scaffold(
      body: Center(
        child: Text('Reports Screen', style: TextStyle(fontSize: 20)),
      ),
    ),
    const Scaffold(
      body: Center(
        child: Text('Activity Input Screen', style: TextStyle(fontSize: 20)),
      ),
    ),
    const Scaffold(
      body: Center(
        child: Text('Strategies Screen', style: TextStyle(fontSize: 20)),
      ),
    ),
    const Scaffold(
      body: Center(
        child: Text('Profile Screen', style: TextStyle(fontSize: 20)),
      ),
    ),
  ];

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      // IndexedStack preserves the scrolling state of screens when switching tabs
      body: IndexedStack(index: _currentIndex, children: _pages),
      // SafeArea protects your custom navigation layout from bottom-clipping & overflow errors
      bottomNavigationBar: SafeArea(
        top: false,
        child: Padding(
          padding: const EdgeInsets.fromLTRB(16.0, 0, 16.0, 12.0),
          child: Container(
            height: 70,
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
                  'Activity Input',
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

  Widget _buildNavItem(
    IconData unselectedIcon,
    IconData selectedIcon,
    String label,
    int index,
  ) {
    final bool isSelected = _currentIndex == index;
    final Color itemColor = Colors.white;

    return Expanded(
      child: GestureDetector(
        behavior: HitTestBehavior.opaque,
        onTap: () {
          setState(() {
            _currentIndex = index;
          });
        },
        child: Column(
          mainAxisSize: MainAxisSize.min,
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(
              isSelected ? selectedIcon : unselectedIcon,
              color: itemColor,
              size: isSelected ? 28 : 24,
            ),
            const SizedBox(height: 4),
            Text(
              label,
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: TextStyle(
                color: itemColor,
                fontSize: 11,
                fontWeight: isSelected ? FontWeight.bold : FontWeight.normal,
              ),
            ),
          ],
        ),
      ),
    );
  }
}
