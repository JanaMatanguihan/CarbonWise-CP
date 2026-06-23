import 'package:flutter/material.dart';

class MainDashboardScreen extends StatelessWidget {
  const MainDashboardScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return SingleChildScrollView(
      physics: const BouncingScrollPhysics(),
      padding: const EdgeInsets.symmetric(horizontal: 16.0, vertical: 8.0),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // TOP RANKING CARDS
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
                  description: 'Out of 12 Batangas State University campuses.',
                ),
              ),
            ],
          ),
          const SizedBox(height: 20),
          const Divider(color: Colors.black26, thickness: 1),
          const SizedBox(height: 12),

          // CHART SECTIONS
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
                        color:
                            Colors.black12, // Placeholder for Bar Chart graphic
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
                            .black12, // Placeholder for Department Bar Chart graphic
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

          // GOING GREEN INITIATIVES
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
          const SizedBox(height: 16),
        ],
      ),
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
