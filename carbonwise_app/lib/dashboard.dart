import 'package:flutter/material.dart';

class MainDashboardScreen extends StatelessWidget {
  const MainDashboardScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(
        0xFFEFEFEA,
      ), // Canvas off-white tone matching background
      body: SafeArea(
        child: SingleChildScrollView(
          physics: const BouncingScrollPhysics(),
          child: Padding(
            padding: const EdgeInsets.all(16.0),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // ==========================================
                // HEADER BANNER
                // ==========================================
                Container(
                  width: double.infinity,
                  padding: const EdgeInsets.symmetric(
                    horizontal: 16,
                    vertical: 14,
                  ),
                  decoration: BoxDecoration(
                    color: const Color(0xFF3AA76D),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: const [
                          Text(
                            'Good morning, user!',
                            style: TextStyle(
                              color: Colors.white,
                              fontSize: 22,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                          SizedBox(height: 4),
                          Text(
                            'Track your impact today.',
                            style: TextStyle(
                              color: Colors.white70,
                              fontSize: 14,
                            ),
                          ),
                        ],
                      ),
                      Row(
                        children: [
                          _buildHeaderIcon(Icons.notifications_none),
                          const SizedBox(width: 10),
                          _buildHeaderIcon(Icons.person_outline),
                        ],
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 20),

                // ==========================================
                // GREEN POINTS PROGRESS
                // ==========================================
                ClipRRect(
                  borderRadius: BorderRadius.circular(10),
                  child: const LinearProgressIndicator(
                    value: 0.45,
                    minHeight: 14,
                    backgroundColor: Color(0xFFCBEAD7),
                    valueColor: AlwaysStoppedAnimation<Color>(
                      Color(0xFF265D3B),
                    ),
                  ),
                ),
                const SizedBox(height: 6),
                const Center(
                  child: Text(
                    'Green Points',
                    style: TextStyle(
                      color: Color(0xFF265D3B),
                      fontSize: 15,
                      fontWeight: FontWeight.bold,
                      fontStyle: FontStyle.italic,
                    ),
                  ),
                ),
                const SizedBox(height: 20),

                // ==========================================
                // TOP RANKING CARDS (All 3 Fit Here Perfectly)
                // ==========================================
                Row(
                  children: [
                    Expanded(
                      child: _buildRankingCard(
                        title: 'Your Current\nRanking',
                        icon: Icons.recycling_outlined,
                        badgeText: 'Top 20%',
                        description: 'Lowest carbon emissions this week.',
                      ),
                    ),
                    const SizedBox(width: 8),
                    Expanded(
                      child: _buildRankingCard(
                        title: 'Department\nRanking',
                        icon: Icons.school_outlined,
                        badgeText: '2nd',
                        description: 'Among all campus departments.',
                      ),
                    ),
                    const SizedBox(width: 8),
                    Expanded(
                      child: _buildRankingCard(
                        title: 'Campus\nRanking',
                        icon: Icons.business_outlined,
                        badgeText: '4th',
                        description:
                            'Out of 12 Batangas State Universitycampuses.',
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 20),
                const Divider(color: Colors.black26, thickness: 1),
                const SizedBox(height: 12),

                // ==========================================
                // CHART SECTIONS (Fixed Right Overflow)
                // ==========================================
                Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Expanded(
                      child: _buildStatusCard(
                        title: 'Individual Status',
                        child: Column(
                          children: [
                            Container(
                              height: 110,
                              color: Colors
                                  .black12, // Placeholder for your Bar Chart graphic
                              child: const Center(
                                child: Text(
                                  'Bar Chart Placeholder',
                                  style: TextStyle(fontSize: 10),
                                ),
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                    const SizedBox(width: 10),
                    Expanded(
                      child: _buildStatusCard(
                        title: 'Department Ranking',
                        child: Column(
                          children: [
                            Container(
                              height: 110,
                              color: Colors
                                  .black12, // Placeholder for your Department Bar Chart graphic
                              child: const Center(
                                child: Text(
                                  'Chart Placeholder',
                                  style: TextStyle(fontSize: 10),
                                ),
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 20),
                const Divider(color: Colors.black26, thickness: 1),
                const SizedBox(height: 16),

                // ==========================================
                // GOING GREEN INITIATIVES
                // ==========================================
                Container(
                  width: double.infinity,
                  padding: const EdgeInsets.all(16),
                  decoration: BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.circular(12),
                    boxShadow: [
                      BoxShadow(
                        color: Colors.black.withOpacity(0.05),
                        blurRadius: 6,
                        offset: const Offset(0, 2),
                      ),
                    ],
                  ),
                  child: Column(
                    children: [
                      Row(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: const [
                          Icon(Icons.campaign, color: Color(0xFF265D3B)),
                          SizedBox(width: 8),
                          Text(
                            'Going Green Initiatives',
                            style: TextStyle(
                              color: Color(0xFF265D3B),
                              fontWeight: FontWeight.bold,
                              fontSize: 16,
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 10),
                      const Text(
                        'What: The Great Green Clean-Up Drive\n'
                        'When: Friday, June 19, 2026 | 8:00 AM - 12:00 PM\n'
                        'Where: Assembly point at the Campus Facade\n'
                        'What to bring: A reusable water bottle!',
                        textAlign: TextAlign.center,
                        style: TextStyle(
                          fontSize: 13,
                          fontWeight: FontWeight.w600,
                          color: Colors.black87,
                          height: 1.4,
                        ),
                      ),
                    ],
                  ),
                ),
                const SizedBox(
                  height: 80,
                ), // Creates clear scroll room above the floating tab bar
              ],
            ),
          ),
        ),
      ),
    );
  }

  // Helper builder for header top circular widgets
  Widget _buildHeaderIcon(IconData icon) {
    return Container(
      padding: const EdgeInsets.all(6),
      decoration: const BoxDecoration(
        color: Colors.white24,
        shape: BoxShape.circle,
      ),
      child: Icon(icon, color: Colors.white, size: 22),
    );
  }

  // Builder for the 3 clean top row ranking cards
  Widget _buildRankingCard({
    required String title,
    required IconData icon,
    required String badgeText,
    required String description,
  }) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 12),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(10),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.05),
            blurRadius: 4,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Column(
        children: [
          Text(
            title,
            textAlign: TextAlign.center,
            style: const TextStyle(
              fontSize: 11,
              fontWeight: FontWeight.bold,
              color: Colors.black87,
            ),
          ),
          const SizedBox(height: 8),
          Icon(icon, color: const Color(0xFF3AA76D), size: 28),
          const SizedBox(height: 6),
          Text(
            badgeText,
            style: const TextStyle(
              fontSize: 15,
              fontWeight: FontWeight.bold,
              color: Color(0xFF265D3B),
            ),
          ),
          const SizedBox(height: 4),
          Text(
            description,
            textAlign: TextAlign.center,
            maxLines: 2,
            overflow: TextOverflow.ellipsis,
            style: const TextStyle(fontSize: 8, color: Colors.black54),
          ),
        ],
      ),
    );
  }

  // Builder for structural chart wrapper blocks
  Widget _buildStatusCard({required String title, required Widget child}) {
    return Container(
      padding: const EdgeInsets.all(10),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(10),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.05),
            blurRadius: 4,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            title,
            style: const TextStyle(
              fontSize: 13,
              fontWeight: FontWeight.bold,
              color: Colors.black87,
            ),
          ),
          const SizedBox(height: 10),
          child,
        ],
      ),
    );
  }
}
