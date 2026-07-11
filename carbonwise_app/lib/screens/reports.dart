import 'package:flutter/material.dart';
import 'package:supabase_flutter/supabase_flutter.dart';
import 'package:carbonwise_app/services/api_service.dart';
import 'package:fl_chart/fl_chart.dart';

enum ReportTimeframe { thisWeek, thisMonth, lastMonth }

class ReportsScreen extends StatefulWidget {
  const ReportsScreen({super.key});

  @override
  State<ReportsScreen> createState() => _ReportsScreenState();
}

class _Legend extends StatelessWidget {
  final Color color;
  final String text;

  const _Legend({required this.color, required this.text});

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Container(
          width: 14,
          height: 14,
          decoration: BoxDecoration(color: color, shape: BoxShape.circle),
        ),
        const SizedBox(width: 6),
        Text(text, style: const TextStyle(fontSize: 12)),
      ],
    );
  }
}

class _ReportsScreenState extends State<ReportsScreen> {
  final ApiService _apiService = ApiService();
  ReportTimeframe _timeframeOverTime = ReportTimeframe.thisWeek;
  List<FlSpot> emissionOverTime = [];

  double transportTotal = 0;
  double officeTotal = 0;
  double foodTotal = 0;

  List<FlSpot> emissionSpots = [];

  List<String> labels = [];

  bool isLoading = true;
  double totalEmission = 0;
  double weekEmission = 0;
  double monthEmission = 0;

  @override
  void initState() {
    super.initState();
    _loadEmissionData();
    _loadChartData(_timeframeOverTime);
  }

  Future<void> _loadEmissionData() async {
    try {
      final user = Supabase.instance.client.auth.currentUser;

      print("Current user: ${user?.email}");

      if (user == null || user.email == null) {
        print("No logged in user.");
        return;
      }

      final records = await _apiService.getEmissionData(user.email!);

      print("Records: $records");

      double total = 0;
      double week = 0;
      double month = 0;

      final now = DateTime.now();

      for (final record in records) {
        final emission =
            double.tryParse(record['total_emission'].toString()) ?? 0.0;

        final date = DateTime.parse(record['record_date'].toString());

        print("Emission: $emission");
        print("Date: $date");

        total += emission;

        if (date.year == now.year && date.month == now.month) {
          month += emission;
        }

        final difference = now.difference(date).inDays;

        if (difference >= 0 && difference < 7) {
          week += emission;
        }
      }

      print("Total: $total");
      print("Week: $week");
      print("Month: $month");

      setState(() {
        totalEmission = total;
        weekEmission = week;
        monthEmission = month;
        isLoading = false;
      });
    } catch (e) {
      print("Reports Error: $e");

      setState(() {
        isLoading = false;
      });
    }
  }

  Future<void> _loadChartData(ReportTimeframe timeframe) async {
    final user = Supabase.instance.client.auth.currentUser;

    if (user == null) return;

    DateTime now = DateTime.now();

    DateTime start;
    DateTime end;

    switch (timeframe) {
      case ReportTimeframe.thisWeek:
        start = now.subtract(Duration(days: now.weekday - 1));
        end = now;
        break;

      case ReportTimeframe.thisMonth:
        start = DateTime(now.year, now.month, 1);
        end = now;
        break;

      case ReportTimeframe.lastMonth:
        start = DateTime(now.year, now.month - 1, 1);
        end = DateTime(now.year, now.month, 0);
        break;
    }

    final records = await Supabase.instance.client
        .from('carbon_records')
        .select()
        .eq('g_suite', user.email!)
        .gte('record_date', start.toIso8601String().split('T')[0])
        .lte('record_date', end.toIso8601String().split('T')[0])
        .order('record_date');

    emissionOverTime.clear();

    transportTotal = 0;
    officeTotal = 0;
    foodTotal = 0;

    int x = 0;

    for (final row in records) {
      emissionOverTime.add(
        FlSpot(x.toDouble(), (row['total_emission'] as num).toDouble()),
      );

      transportTotal += (row['transportation'] as num?)?.toDouble() ?? 0;

      officeTotal += (row['electricity'] as num?)?.toDouble() ?? 0;

      foodTotal += (row['food'] as num?)?.toDouble() ?? 0;

      x++;
    }

    setState(() {});
  }

  @override
  Widget build(BuildContext context) {
    const primaryGreen = Color(0xFF3AA76D);
    const darkGreen = Color(0xFF1E5631);

    return RefreshIndicator(
      onRefresh: () async {
        await _loadEmissionData();
        await _loadChartData(_timeframeOverTime);
      },
      child: SingleChildScrollView(
        physics: const AlwaysScrollableScrollPhysics(),
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
                    Row(
                      mainAxisAlignment: MainAxisAlignment.center,
                      crossAxisAlignment: CrossAxisAlignment.baseline,
                      textBaseline: TextBaseline.alphabetic,
                      children: [
                        Text(
                          isLoading ? '--' : totalEmission.toStringAsFixed(2),
                          style: const TextStyle(
                            fontSize: 36,
                            fontWeight: FontWeight.bold,
                            color: primaryGreen,
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
                  Expanded(
                    child: _buildStatCard(
                      'This Week',
                      isLoading ? '--' : weekEmission.toStringAsFixed(2),
                      'kg CO₂',
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: _buildStatCard(
                      'This Month',
                      isLoading ? '--' : monthEmission.toStringAsFixed(2),
                      'kg CO₂',
                    ),
                  ),
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

              // 5. Charts
              Container(
                padding: const EdgeInsets.all(16),
                decoration: _cardDecoration(),
                child: Column(
                  children: [
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        const Text(
                          "Emissions Over Time",
                          style: TextStyle(fontWeight: FontWeight.bold),
                        ),

                        DropdownButton<ReportTimeframe>(
                          value: _timeframeOverTime,
                          underline: const SizedBox(),
                          items: const [
                            DropdownMenuItem(
                              value: ReportTimeframe.thisWeek,
                              child: Text("This Week"),
                            ),

                            DropdownMenuItem(
                              value: ReportTimeframe.thisMonth,
                              child: Text("This Month"),
                            ),

                            DropdownMenuItem(
                              value: ReportTimeframe.lastMonth,
                              child: Text("Last Month"),
                            ),
                          ],
                          onChanged: (value) {
                            if (value == null) return;

                            setState(() {
                              _timeframeOverTime = value;
                            });

                            _loadChartData(value);
                          },
                        ),
                      ],
                    ),

                    const SizedBox(height: 20),

                    SizedBox(
                      height: 200,
                      child: LineChart(
                        LineChartData(
                          lineBarsData: [
                            LineChartBarData(
                              spots: emissionOverTime,
                              isCurved: true,
                              barWidth: 4,
                              dotData: const FlDotData(show: true),
                            ),
                          ],
                        ),
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 20),
              // Emissions by Source Pie Chart
              Container(
                padding: const EdgeInsets.all(16),
                decoration: _cardDecoration(),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        const Text(
                          "Emissions by Source",
                          style: TextStyle(
                            fontSize: 16,
                            fontWeight: FontWeight.bold,
                          ),
                        ),

                        DropdownButton<ReportTimeframe>(
                          value: _timeframeOverTime,
                          underline: const SizedBox(),
                          items: const [
                            DropdownMenuItem(
                              value: ReportTimeframe.thisWeek,
                              child: Text("This Week"),
                            ),
                            DropdownMenuItem(
                              value: ReportTimeframe.thisMonth,
                              child: Text("This Month"),
                            ),
                            DropdownMenuItem(
                              value: ReportTimeframe.lastMonth,
                              child: Text("Last Month"),
                            ),
                          ],
                          onChanged: (value) {
                            if (value == null) return;

                            setState(() {
                              _timeframeOverTime = value;
                            });

                            _loadChartData(value);
                          },
                        ),
                      ],
                    ),

                    const SizedBox(height: 20),

                    SizedBox(
                      height: 220,
                      child: PieChart(
                        PieChartData(
                          centerSpaceRadius: 45,
                          sectionsSpace: 3,
                          borderData: FlBorderData(show: false),

                          sections: [
                            PieChartSectionData(
                              value: transportTotal,
                              color: Colors.green,
                              title: "${transportTotal.toStringAsFixed(1)} kg",
                              radius: 65,
                              titleStyle: const TextStyle(
                                color: Colors.white,
                                fontWeight: FontWeight.bold,
                                fontSize: 11,
                              ),
                            ),

                            PieChartSectionData(
                              value: officeTotal,
                              color: Colors.orange,
                              title: "${officeTotal.toStringAsFixed(1)} kg",
                              radius: 65,
                              titleStyle: const TextStyle(
                                color: Colors.white,
                                fontWeight: FontWeight.bold,
                                fontSize: 11,
                              ),
                            ),

                            PieChartSectionData(
                              value: foodTotal,
                              color: Colors.blue,
                              title: "${foodTotal.toStringAsFixed(1)} kg",
                              radius: 65,
                              titleStyle: const TextStyle(
                                color: Colors.white,
                                fontWeight: FontWeight.bold,
                                fontSize: 11,
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),

                    const SizedBox(height: 20),

                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceEvenly,
                      children: const [
                        _Legend(color: Colors.green, text: "Transportation"),
                        _Legend(color: Colors.orange, text: "Office"),
                        _Legend(color: Colors.blue, text: "Food"),
                      ],
                    ),
                  ],
                ),
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
          color: Colors.black.withValues(alpha: 0.04),
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
