import 'package:flutter/material.dart';
import 'package:supabase_flutter/supabase_flutter.dart';

class ProfileScreen extends StatefulWidget {
  const ProfileScreen({super.key});

  @override
  State<ProfileScreen> createState() => _ProfileScreenState();
}

class _ProfileScreenState extends State<ProfileScreen> {
  Map<String, dynamic>? userInfo;
  bool isLoadingProfile = true;

  List<Map<String, dynamic>> recentActivities = [];
  bool isLoadingTimeline = true;

  double carbonScore = 0.0;
  bool isLoadingScore = true;

  @override
  void initState() {
    super.initState();
    _loadUserInfo();
    _loadCarbonScore();
    _loadRecentActivities();
  }

  Future<void> _loadRecentActivities() async {
    try {
      final user = Supabase.instance.client.auth.currentUser;

      if (user == null || user.email == null) return;

      final records = await Supabase.instance.client
          .from('carbon_records')
          .select()
          .eq('g_suite', user.email!)
          .order('created_at', ascending: false);

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

    final record = await Supabase.instance.client
        .from('carbon_records')
        .select('total_emission')
        .eq('g_suite', user.email!)
        .order('created_at', ascending: false)
        .limit(1)
        .maybeSingle();

    print("Database record: $record");

    setState(() {
      carbonScore = (record?['total_emission'] ?? 0).toDouble();
      isLoadingScore = false;
    });

    print("Carbon Score: $carbonScore");
  }

  Future<void> _loadUserInfo() async {
    final user = Supabase.instance.client.auth.currentUser;

    if (user == null || user.email == null) {
      setState(() {
        isLoadingProfile = false;
      });
      return;
    }

    final response = await Supabase.instance.client
        .from('user_info')
        .select('sr_code, g_suite, full_name, campus, department')
        .eq('g_suite', user.email!)
        .maybeSingle();

    setState(() {
      userInfo = response;
      isLoadingProfile = false;
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
                  const SizedBox(height: 12),
                  _buildAccountSettingsCard(),
                  const SizedBox(height: 24), // Bottom breathing room
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
        children: [
          const CircleAvatar(
            radius: 36,
            backgroundColor: Colors.white,
            child: Icon(Icons.person, size: 44, color: primaryGreen),
          ),
          const SizedBox(width: 16),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              mainAxisAlignment: MainAxisAlignment.center,
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
                    color: Colors.white.withOpacity(0.9),
                    fontSize: 12,
                    fontStyle: FontStyle.italic,
                    height: 1.3,
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
                          color: Colors.black.withOpacity(0.7),
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
                      width: 38,
                      height: 38,
                      child: CircularProgressIndicator(
                        value: 0.75,
                        strokeWidth: 3.5,
                        backgroundColor: Colors.grey[200],
                        valueColor: const AlwaysStoppedAnimation<Color>(
                          primaryGreen,
                        ),
                      ),
                    ),
                    const Icon(Icons.eco, size: 18, color: primaryGreen),
                  ],
                ),
                const SizedBox(width: 8),
                const Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Text(
                        'Conscious User',
                        overflow: TextOverflow.ellipsis,
                        style: TextStyle(
                          fontSize: 12,
                          fontWeight: FontWeight.bold,
                          color: darkGreen,
                        ),
                      ),
                      Text(
                        'Keep it up! You are on track.',
                        overflow: TextOverflow.ellipsis,
                        maxLines: 2,
                        style: TextStyle(fontSize: 10, color: textMuted),
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
    return _buildSectionCard(
      title: 'Your Carbon Emission Breakdown',
      child: Column(
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceAround,
            children: [
              _buildSubTab('Transportation', true),
              _buildSubTab('Office Resource', false),
              _buildSubTab('Food Consumption', false),
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
                              : carbonScore.toStringAsFixed(2),
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
                          child: const Icon(
                            Icons.directions_bus,
                            size: 16,
                            color: darkGreen,
                          ),
                        ),
                        const SizedBox(width: 6),
                        const Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                'Commuting',
                                style: TextStyle(
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
                      'Last 4 Weeks',
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
                          _buildGraphBar('22.1', 0.9, 'Apr 28-\nMay 4'),
                          _buildGraphBar('20.3', 0.8, 'May 5-\nMay 11'),
                          _buildGraphBar('19.6', 0.75, 'May 12-\nMay 18'),
                          _buildGraphBar('18.4', 0.68, 'May 19-\nMay 25'),
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
                          color: darkGreen.withOpacity(0.8),
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
      title: 'Achievements',
      trailing: Text(
        'View All',
        style: TextStyle(
          fontSize: 12,
          color: darkGreen.withOpacity(0.8),
          fontWeight: FontWeight.bold,
        ),
      ),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          _buildAchievementItem(
            'Going Green',
            'Start your journey.',
            Icons.eco,
            true,
          ),
          _buildAchievementItem(
            'Eco Commute',
            'Greener travels.',
            Icons.directions_bike,
            true,
          ),
          _buildAchievementItem(
            'Green Streak',
            '3/7 days completed',
            Colors.grey,
            false,
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
        children: [
          Container(
            width: 105,
            padding: const EdgeInsets.symmetric(vertical: 12, horizontal: 6),
            decoration: BoxDecoration(
              color: badgeGrey.withOpacity(0.6),
              borderRadius: BorderRadius.circular(4),
            ),
            child: const Column(
              mainAxisAlignment: MainAxisAlignment.spaceEvenly,
              children: [
                Text(
                  'Your Department\nRank',
                  textAlign: TextAlign.center,
                  style: TextStyle(fontSize: 10, fontWeight: FontWeight.w500),
                ),
                SizedBox(height: 4),
                Text(
                  '3rd',
                  style: TextStyle(
                    fontSize: 22,
                    fontWeight: FontWeight.bold,
                    color: darkGreen,
                  ),
                ),
                SizedBox(height: 4),
                Text(
                  'Out of 5 departments',
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
                _buildDeptBar('1', 'CET', 56.2, Colors.grey[400]!),
                _buildDeptBar('2', 'CAS', 42.8, Colors.grey[400]!),
                _buildDeptBar(
                  '3',
                  'CICS',
                  36.7,
                  primaryGreen,
                  isUserDept: true,
                ),
                _buildDeptBar('4', 'CABE', 28.9, Colors.grey[400]!),
                _buildDeptBar('5', 'CTE', 24.1, Colors.grey[400]!),
                const SizedBox(height: 6),
                Container(
                  width: double.infinity,
                  padding: const EdgeInsets.symmetric(
                    vertical: 4,
                    horizontal: 6,
                  ),
                  decoration: BoxDecoration(
                    color: badgeGrey.withOpacity(0.4),
                    borderRadius: BorderRadius.circular(4),
                  ),
                  child: Row(
                    children: [
                      const Icon(Icons.eco, size: 12, color: primaryGreen),
                      const SizedBox(width: 4),
                      Expanded(
                        child: RichText(
                          overflow: TextOverflow.ellipsis,
                          text: const TextSpan(
                            style: TextStyle(
                              fontSize: 10,
                              color: Colors.black87,
                            ),
                            children: [
                              TextSpan(text: 'You contribute '),
                              TextSpan(
                                text: '0.8% ',
                                style: TextStyle(fontWeight: FontWeight.bold),
                              ),
                              TextSpan(text: 'of '),
                              TextSpan(
                                text: 'CICS ',
                                style: TextStyle(fontWeight: FontWeight.bold),
                              ),
                              TextSpan(text: 'total emissions'),
                            ],
                          ),
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
                _buildPatternItem(
                  Icons.calendar_month,
                  'You emit more than 23% of CO2 on weekdays than weekends.',
                ),
                const Divider(height: 12),
                _buildPatternItem(
                  Icons.directions_bus,
                  'Your highest impact activity is commuting.',
                ),
                const Divider(height: 12),
                _buildPatternItem(
                  Icons.directions_walk,
                  'You reduced paper usage by 18%.',
                ),
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
                          color: darkGreen.withOpacity(0.8),
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                      const SizedBox(width: 2),
                      Icon(
                        Icons.arrow_forward,
                        size: 10,
                        color: darkGreen.withOpacity(0.8),
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

  // 7. Account Settings Box
  Widget _buildAccountSettingsCard() {
    return Container(
      padding: const EdgeInsets.all(12.0),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(6),
        border: Border.all(color: Colors.black12, width: 0.5),
      ),
      child: Row(
        children: [
          const Icon(Icons.settings, size: 26, color: Colors.black87),
          const SizedBox(width: 10),
          const Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'Account Settings',
                  style: TextStyle(fontSize: 13, fontWeight: FontWeight.bold),
                ),
                Text(
                  'Manage configurations and user info preferences.',
                  style: TextStyle(fontSize: 11, color: textMuted),
                ),
              ],
            ),
          ),
          const SizedBox(width: 6),
          OutlinedButton(
            onPressed: () {},
            style: OutlinedButton.styleFrom(
              side: const BorderSide(color: primaryGreen, width: 0.8),
              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 0),
              minimumSize: const Size(64, 28),
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(4),
              ),
            ),
            child: const Text(
              'Edit Profile',
              style: TextStyle(
                fontSize: 10.5,
                color: primaryGreen,
                fontWeight: FontWeight.bold,
              ),
            ),
          ),
        ],
      ),
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
              if (trailing != null) trailing,
            ],
          ),
          const SizedBox(height: 8),
          child,
        ],
      ),
    );
  }

  Widget _buildSubTab(String label, bool isActive) {
    return Column(
      mainAxisSize: MainAxisSize.min,
      children: [
        Text(
          label,
          style: TextStyle(
            fontSize: 11,
            fontWeight: isActive ? FontWeight.bold : FontWeight.normal,
            color: isActive ? primaryGreen : Colors.black54,
          ),
        ),
        if (isActive)
          Container(
            margin: const EdgeInsets.only(top: 3),
            width: 45,
            height: 2,
            color: primaryGreen,
          ),
      ],
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
    String desc,
    dynamic iconData,
    bool isUnlocked,
  ) {
    return Expanded(
      child: Container(
        margin: const EdgeInsets.symmetric(horizontal: 3),
        padding: const EdgeInsets.symmetric(vertical: 10, horizontal: 4),
        height: 100, // Explicit bounded constraint for equal grid look
        decoration: BoxDecoration(
          border: Border.all(color: Colors.black12, width: 0.5),
          borderRadius: BorderRadius.circular(4),
        ),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Container(
              padding: const EdgeInsets.all(4),
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                color: isUnlocked ? Colors.white : Colors.grey[100],
                border: Border.all(
                  color: isUnlocked ? primaryGreen : Colors.grey,
                  width: 1,
                ),
              ),
              child: Icon(
                iconData is IconData ? iconData : Icons.workspace_premium,
                size: 20,
                color: isUnlocked ? primaryGreen : Colors.grey[400],
              ),
            ),
            const SizedBox(height: 4),
            Text(
              title,
              textAlign: TextAlign.center,
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: TextStyle(
                fontSize: 10.5,
                fontWeight: FontWeight.bold,
                color: isUnlocked ? primaryGreen : Colors.grey,
              ),
            ),
            const SizedBox(height: 2),
            Expanded(
              child: Text(
                desc,
                textAlign: TextAlign.center,
                maxLines: 2,
                overflow: TextOverflow.ellipsis,
                style: const TextStyle(fontSize: 9, color: textMuted),
              ),
            ),
          ],
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
      padding: const EdgeInsets.symmetric(vertical: 3.0),
      child: Row(
        children: [
          SizedBox(
            width: 12,
            child: Text(
              rank,
              style: const TextStyle(fontSize: 10, fontWeight: FontWeight.bold),
            ),
          ),
          SizedBox(
            width: 32,
            child: Text(
              label,
              style: TextStyle(
                fontSize: 10,
                fontWeight: isUserDept ? FontWeight.bold : FontWeight.normal,
                color: isUserDept ? primaryGreen : Colors.black87,
              ),
            ),
          ),
          Expanded(
            child: Stack(
              alignment: Alignment.centerLeft,
              children: [
                Container(
                  height: 5,
                  width: double.infinity,
                  decoration: BoxDecoration(
                    color: Colors.grey[200],
                    borderRadius: BorderRadius.circular(2),
                  ),
                ),
                FractionallySizedBox(
                  widthFactor: score / 60.0,
                  child: Container(
                    height: 5,
                    decoration: BoxDecoration(
                      color: color,
                      borderRadius: BorderRadius.circular(2),
                    ),
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(width: 8),
          SizedBox(
            width: 55,
            child: Text(
              '$score kg',
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
                color: iconColor.withOpacity(0.15),
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
