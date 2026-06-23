import 'package:flutter/material.dart';

class ReportsScreen extends StatefulWidget {
  const ReportsScreen({super.key});

  @override
  State<ReportsScreen> createState() => _ReportsScreenState();
}

class _ReportsScreenState extends State<ReportsScreen> {
  String _timeframeOverTime = 'This Week';
  String _timeframeBySource = 'This Week';

  final List<String> _dropdownOptions = [
    'This Week',
    'This Month',
    'Last Month',
  ];

  @override
  Widget build(BuildContext context) {
    const primaryGreen = Color(0xFF3AA76D);
    const darkGreen = Color(0xFF1E5631);

    return SingleChildScrollView(
      physics: const BouncingScrollPhysics(),
      child: Padding(
        padding: const EdgeInsets.all(16.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // 3. Total CO2 Emissions Card
            Container(
              width: double.infinity,
              padding: const EdgeInsets.all(16.0),
              decoration: _cardDecoration(),
              child: Column(
                children: [
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      const Text(
                        'Total CO2 Emissions',
                        style: TextStyle(
                          fontSize: 16,
                          fontWeight: FontWeight.bold,
                          color: Colors.black87,
                        ),
                      ),
                      Text(
                        '+5% since last month',
                        style: TextStyle(
                          fontSize: 13,
                          color: Colors.red[400],
                          fontWeight: FontWeight.w600,
                          fontStyle: FontStyle.italic,
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 12),
                  const Row(
                    mainAxisAlignment: MainAxisAlignment.center,
                    crossAxisAlignment: CrossAxisAlignment.baseline,
                    textBaseline: TextBaseline.alphabetic,
                    children: [
                      Text(
                        '125',
                        style: TextStyle(
                          fontSize: 52,
                          fontWeight: FontWeight.w800,
                          color: Colors.black,
                        ),
                      ),
                      SizedBox(width: 8),
                      Text(
                        'kg CO2',
                        style: TextStyle(
                          fontSize: 20,
                          fontWeight: FontWeight.bold,
                          color: Colors.black87,
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),
            const SizedBox(height: 16),

            // 4. Row of Three Stat Cards
            Row(
              children: [
                Expanded(child: _buildStatCard('This Week', '18', 'kg CO2')),
                const SizedBox(width: 12),
                Expanded(child: _buildStatCard('This Month', '125', 'kg CO2')),
                const SizedBox(width: 12),
                Expanded(
                  child: _buildStatCard(
                    'Your emissions\nlast month',
                    '-12% ↓',
                    'emissions',
                    valueColor: primaryGreen,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 16),

            // 5. Charts Placeholder Row
            Row(
              children: [
                Expanded(
                  child: _buildChartPlaceholder(
                    'Emissions over time',
                    Icons.show_chart,
                    primaryGreen,
                    currentValue: _timeframeOverTime,
                    onChanged: (newValue) {
                      setState(() {
                        _timeframeOverTime = newValue;
                      });
                    },
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: _buildChartPlaceholder(
                    'Emissions by source',
                    Icons.pie_chart_outline,
                    primaryGreen,
                    currentValue: _timeframeBySource,
                    onChanged: (newValue) {
                      setState(() {
                        _timeframeBySource = newValue;
                      });
                    },
                  ),
                ),
              ],
            ),
            const SizedBox(height: 20),

            // 6. Smart Suggestions Section
            const Text(
              'Smart Suggestions',
              style: TextStyle(
                fontSize: 16,
                fontWeight: FontWeight.bold,
                color: Colors.black87,
              ),
            ),
            const SizedBox(height: 12),
            Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // FIXED: Removed the redundant Expanded wrappers around the helper calls
                _buildSuggestionCard(
                  Icons.directions_bus_outlined,
                  'Use public transport 2x this week.',
                  'You can save up to 4.2 kg CO2.',
                ),
                const SizedBox(width: 8),
                _buildSuggestionCard(
                  Icons.lightbulb_outline,
                  'Turn off lights and electric fans when not in use.',
                  'You can save up to ~0.5 kg CO2/day.',
                ),
                const SizedBox(width: 8),
                _buildSuggestionCard(
                  Icons.restaurant_outlined,
                  'Choose more plant-based meals this week.',
                  '',
                ),
              ],
            ),
            const SizedBox(height: 20),

            // 7. Carbon Reduction Journey
            Container(
              padding: const EdgeInsets.all(16.0),
              decoration: _cardDecoration(),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text(
                    'Carbon Reduction Journey',
                    style: TextStyle(
                      fontSize: 15,
                      fontWeight: FontWeight.bold,
                      color: Colors.black87,
                    ),
                  ),
                  const SizedBox(height: 16),
                  Row(
                    children: [
                      // Circular percentage indicator
                      Container(
                        width: 60,
                        height: 60,
                        decoration: BoxDecoration(
                          shape: BoxShape.circle,
                          border: Border.all(color: primaryGreen, width: 4),
                        ),
                        child: const Center(
                          child: Text(
                            '65%',
                            style: TextStyle(
                              fontSize: 16,
                              fontWeight: FontWeight.bold,
                              color: primaryGreen,
                            ),
                          ),
                        ),
                      ),
                      const SizedBox(width: 16),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const Text(
                              'Great job! You’re on track to reduce emissions.',
                              style: TextStyle(
                                fontWeight: FontWeight.bold,
                                fontSize: 13,
                              ),
                            ),
                            const SizedBox(height: 4),
                            Text(
                              'Your Goal: Reduce 200 kg CO2 this month.',
                              style: TextStyle(
                                color: Colors.grey[600],
                                fontSize: 11,
                              ),
                            ),
                            const SizedBox(height: 8),
                            ClipRRect(
                              borderRadius: BorderRadius.circular(10),
                              child: const LinearProgressIndicator(
                                value: 0.65,
                                backgroundColor: Color(0xFFE0E0E0),
                                valueColor: AlwaysStoppedAnimation<Color>(
                                  darkGreen,
                                ),
                                minHeight: 8,
                              ),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  // Helper Elements & Shared Styles
  BoxDecoration _cardDecoration() {
    return BoxDecoration(
      color: Colors.white,
      borderRadius: BorderRadius.circular(12),
      boxShadow: [
        BoxShadow(
          color: Colors.black.withOpacity(0.04),
          blurRadius: 6,
          offset: const Offset(0, 3),
        ),
      ],
    );
  }

  Widget _buildStatCard(
    String title,
    String value,
    String unit, {
    Color valueColor = Colors.black,
  }) {
    return Container(
      padding: const EdgeInsets.symmetric(vertical: 16.0, horizontal: 8.0),
      decoration: _cardDecoration(),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Text(
            title,
            textAlign: TextAlign.center,
            style: const TextStyle(
              fontSize: 12,
              fontWeight: FontWeight.bold,
              color: Colors.black54,
            ),
          ),
          const SizedBox(height: 8),
          Text(
            value,
            style: TextStyle(
              fontSize: 28,
              fontWeight: FontWeight.bold,
              color: valueColor,
            ),
          ),
          const SizedBox(height: 2),
          Text(
            unit,
            style: const TextStyle(
              fontSize: 11,
              color: Colors.black87,
              fontWeight: FontWeight.w500,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildChartPlaceholder(
    String title,
    IconData icon,
    Color color, {
    required String currentValue,
    required ValueChanged<String> onChanged,
  }) {
    return Container(
      padding: const EdgeInsets.all(12),
      height: 140,
      decoration: _cardDecoration(),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Expanded(
                child: Text(
                  title,
                  style: const TextStyle(
                    fontSize: 12,
                    fontWeight: FontWeight.bold,
                  ),
                  overflow: TextOverflow.ellipsis,
                ),
              ),
              PopupMenuButton<String>(
                onSelected: onChanged,
                itemBuilder: (BuildContext context) {
                  return _dropdownOptions.map((String choice) {
                    return PopupMenuItem<String>(
                      value: choice,
                      height: 36,
                      child: Text(
                        choice,
                        style: const TextStyle(
                          fontSize: 12,
                          color: Colors.black87,
                        ),
                      ),
                    );
                  }).toList();
                },
                child: Container(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 6,
                    vertical: 4,
                  ),
                  decoration: BoxDecoration(
                    color: Colors.grey[200],
                    borderRadius: BorderRadius.circular(4),
                  ),
                  child: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Text(
                        currentValue,
                        style: const TextStyle(
                          fontSize: 9,
                          fontWeight: FontWeight.w500,
                          color: Colors.black87,
                        ),
                      ),
                      const SizedBox(width: 2),
                      const Icon(
                        Icons.arrow_drop_down,
                        size: 12,
                        color: Colors.black87,
                      ),
                    ],
                  ),
                ),
              ),
            ],
          ),
          Expanded(
            child: Center(
              child: Icon(icon, size: 50, color: color.withOpacity(0.4)),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildSuggestionCard(IconData icon, String mainText, String subText) {
    return Expanded(
      child: Container(
        padding: const EdgeInsets.all(8.0),
        height: 150,
        decoration: BoxDecoration(
          color: Colors.white,
          border: Border.all(color: const Color(0xFF3AA76D), width: 1),
          borderRadius: BorderRadius.circular(8),
        ),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            CircleAvatar(
              backgroundColor: const Color(0xFFE8F5E9),
              radius: 18,
              child: Icon(icon, color: const Color(0xFF3AA76D), size: 20),
            ),
            const SizedBox(height: 8),
            Text(
              mainText,
              textAlign: TextAlign.center,
              style: const TextStyle(
                fontSize: 10,
                fontWeight: FontWeight.bold,
                color: Colors.black87,
              ),
            ),
            if (subText.isNotEmpty) ...[
              const SizedBox(height: 4),
              Text(
                subText,
                textAlign: TextAlign.center,
                style: TextStyle(fontSize: 8, color: Colors.grey[500]),
              ),
            ],
          ],
        ),
      ),
    );
  }
}
