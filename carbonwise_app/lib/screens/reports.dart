import 'package:flutter/material.dart';
import 'package:supabase_flutter/supabase_flutter.dart';
import 'package:carbonwise_app/services/api_service.dart';
import 'package:fl_chart/fl_chart.dart';
import 'package:carbonwise_app/services/gemini_service.dart';
import 'package:carbonwise_app/utils/smart_suggestion.dart';
import 'package:carbonwise_app/utils/strategy_notifier.dart';

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
  double? _weeklyChange;

  List<FlSpot> emissionSpots = [];

  List<String> labels = [];

  bool isLoading = true;
  double totalEmission = 0;
  double weekEmission = 0;
  double monthEmission = 0;

  double _journeyPercent = 0;
  double _journeyProgress = 0;

  String _journeyMessage = "";
  String _journeySubtitle = "";

  List<SmartSuggestion> _smartSuggestions = [];

  @override
  void initState() {
    super.initState();
    _loadEmissionData();
    _loadChartData(_timeframeOverTime);
    _loadWeeklyComparison();
    _loadCarbonReductionJourney();
    strategyRefreshNotifier.addListener(_refreshReports);
  }

  @override
  void dispose() {
    strategyRefreshNotifier.removeListener(_refreshReports);
    super.dispose();
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

    await _loadSmartSuggestions();
  }

  Future<void> _loadWeeklyComparison() async {
    try {
      final supabase = Supabase.instance.client;
      final user = supabase.auth.currentUser;

      if (user == null) return;

      final now = DateTime.now();

      // Monday of this week
      final startOfThisWeek = DateTime(
        now.year,
        now.month,
        now.day,
      ).subtract(Duration(days: now.weekday - 1));

      // Monday of last week
      final startOfLastWeek = startOfThisWeek.subtract(const Duration(days: 7));

      // Monday of next week
      final startOfNextWeek = startOfThisWeek.add(const Duration(days: 7));

      final thisWeekRecords = await supabase
          .from('carbon_records')
          .select('total_emission')
          .eq('g_suite', user.email!)
          .gte('created_at', startOfThisWeek.toIso8601String())
          .lt('created_at', startOfNextWeek.toIso8601String());

      final lastWeekRecords = await supabase
          .from('carbon_records')
          .select('total_emission')
          .eq('g_suite', user.email!)
          .gte('created_at', startOfLastWeek.toIso8601String())
          .lt('created_at', startOfThisWeek.toIso8601String());

      double thisWeekTotal = 0;
      double lastWeekTotal = 0;

      for (final row in thisWeekRecords) {
        thisWeekTotal += (row['total_emission'] as num?)?.toDouble() ?? 0;
      }

      for (final row in lastWeekRecords) {
        lastWeekTotal += (row['total_emission'] as num?)?.toDouble() ?? 0;
      }

      if (lastWeekTotal == 0) {
        setState(() {
          _weeklyChange = null;
        });
        return;
      }

      final change = ((thisWeekTotal - lastWeekTotal) / lastWeekTotal) * 100;

      setState(() {
        _weeklyChange = change;
      });
    } catch (e) {
      print("Weekly comparison error: $e");
    }
  }

  Future<void> _loadCarbonReductionJourney() async {
    try {
      final supabase = Supabase.instance.client;
      final user = supabase.auth.currentUser;

      if (user == null) return;

      final now = DateTime.now();

      // First day of this month
      final thisMonthStart = DateTime(now.year, now.month, 1);

      // First day of next month
      final nextMonthStart = DateTime(now.year, now.month + 1, 1);

      // First day of last month
      final lastMonthStart = DateTime(now.year, now.month - 1, 1);

      // ---------- THIS MONTH ----------
      final thisMonthRecords = await supabase
          .from('carbon_records')
          .select('total_emission')
          .eq('g_suite', user.email!)
          .gte('record_date', thisMonthStart.toIso8601String().split('T').first)
          .lt('record_date', nextMonthStart.toIso8601String().split('T').first);

      // ---------- LAST MONTH ----------
      final lastMonthRecords = await supabase
          .from('carbon_records')
          .select('total_emission')
          .eq('g_suite', user.email!)
          .gte('record_date', lastMonthStart.toIso8601String().split('T').first)
          .lt('record_date', thisMonthStart.toIso8601String().split('T').first);

      double thisMonthTotal = 0;
      double lastMonthTotal = 0;

      for (final record in thisMonthRecords) {
        thisMonthTotal += (record['total_emission'] as num?)?.toDouble() ?? 0;
      }

      for (final record in lastMonthRecords) {
        lastMonthTotal += (record['total_emission'] as num?)?.toDouble() ?? 0;
      }

      double percent = 0;
      String message;
      String subtitle;

      if (lastMonthTotal == 0) {
        message = "Start your sustainability journey!";
        subtitle = "Record more activities to compare your monthly progress.";
      } else {
        double score;
        double change = 0;

        if (lastMonthTotal == 0) {
          score = 100;

          message = "Welcome to your sustainability journey! 🌱";
          subtitle =
              "Keep logging your activities to start tracking your progress.";
        } else {
          change = ((thisMonthTotal - lastMonthTotal) / lastMonthTotal) * 100;

          if (change <= -20) {
            score = 100;

            message = "Amazing work! 🎉";
            subtitle =
                "You reduced your emissions by ${change.abs().toStringAsFixed(1)}% this month.";
          } else if (change < 0) {
            score = 90;

            message = "Nice progress! 💚";
            subtitle =
                "You're emitting ${change.abs().toStringAsFixed(1)}% less than last month.";
          } else if (change == 0) {
            score = 80;

            message = "Steady progress 👍";
            subtitle = "Your emissions stayed consistent this month.";
          } else {
            score = (100 - change).clamp(0, 100);

            message = "Let's improve next month! 🌍";
            subtitle =
                "Your emissions increased by ${change.toStringAsFixed(1)}%.";
          }
        }

        setState(() {
          _journeyPercent = score;
          _journeyProgress = score / 100;
          _journeyMessage = message;
          _journeySubtitle = subtitle;
        });

        print("===== Carbon Reduction Journey =====");
        print("This Month: $thisMonthTotal");
        print("Last Month: $lastMonthTotal");
        print("Percent: $percent");
        print("Message: $message");
        print("Subtitle: $subtitle");

        setState(() {
          _journeyPercent = percent;
          _journeyProgress = percent / 100;
          _journeyMessage = message;
          _journeySubtitle = subtitle;
        });
      }
    } catch (e) {
      print("Carbon Journey Error:");
      print(e);
    }
  }

  Future<void> _loadSmartSuggestions() async {
    try {
      final suggestions = await GeminiService().generateSmartSuggestions(
        transportation: transportTotal,
        electricity: officeTotal,
        food: foodTotal,
      );

      setState(() {
        _smartSuggestions = suggestions;
      });
    } catch (e) {
      print("Gemini Error: $e");

      setState(() {
        _smartSuggestions = [
          SmartSuggestion(
            suggestion: "Unable to load suggestions right now.",
            impact: "Please try again later.",
          ),
          SmartSuggestion(
            suggestion: "Unable to load suggestions right now.",
            impact: "Please try again later.",
          ),
          SmartSuggestion(
            suggestion: "Unable to load suggestions right now.",
            impact: "Please try again later.",
          ),
        ];
      });
    }
  }

  Future<void> _refreshReports() async {
    await _loadChartData(_timeframeOverTime);
    await _loadSmartSuggestions();
  }

  @override
  Widget build(BuildContext context) {
    const primaryGreen = Color(0xFF3AA76D);
    const darkGreen = Color(0xFF1E5631);

    return RefreshIndicator(
      onRefresh: () async {
        await _loadEmissionData();
        await _loadChartData(_timeframeOverTime);
        await _loadWeeklyComparison();
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
                      "This week's\nemissions",
                      _weeklyChange == null
                          ? "--"
                          : "${_weeklyChange!.abs().toStringAsFixed(1)}%"
                                "${_weeklyChange! < 0 ? " ↓" : " ↑"}",
                      "vs last week",
                      valueColor: _weeklyChange != null && _weeklyChange! < 0
                          ? primaryGreen
                          : Colors.red,
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
                    _smartSuggestions.isNotEmpty
                        ? _smartSuggestions[0].suggestion
                        : "",
                    _smartSuggestions.isNotEmpty
                        ? _smartSuggestions[0].impact
                        : "",
                  ),
                  const SizedBox(width: 8),
                  _buildSuggestionCard(
                    Icons.lightbulb_outline,
                    _smartSuggestions.length > 1
                        ? _smartSuggestions[1].suggestion
                        : "",
                    _smartSuggestions.length > 1
                        ? _smartSuggestions[1].impact
                        : "",
                  ),
                  const SizedBox(width: 8),
                  _buildSuggestionCard(
                    Icons.restaurant_outlined,
                    _smartSuggestions.length > 2
                        ? _smartSuggestions[2].suggestion
                        : "",
                    _smartSuggestions.length > 2
                        ? _smartSuggestions[2].impact
                        : "",
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
                        SizedBox(
                          width: 80,
                          height: 80,
                          child: Stack(
                            alignment: Alignment.center,
                            children: [
                              SizedBox(
                                width: 80,
                                height: 80,
                                child: CircularProgressIndicator(
                                  value: _journeyProgress,
                                  strokeWidth: 8,
                                  backgroundColor: Colors.grey.shade300,
                                  valueColor: const AlwaysStoppedAnimation(
                                    primaryGreen,
                                  ),
                                ),
                              ),

                              Column(
                                mainAxisAlignment: MainAxisAlignment.center,
                                children: [
                                  Text(
                                    "${_journeyPercent.toStringAsFixed(0)}%",
                                    style: const TextStyle(
                                      fontSize: 20,
                                      fontWeight: FontWeight.bold,
                                      color: primaryGreen,
                                    ),
                                  ),

                                  const Text(
                                    "Goal",
                                    style: TextStyle(
                                      fontSize: 9,
                                      color: Colors.grey,
                                    ),
                                  ),
                                ],
                              ),
                            ],
                          ),
                        ),
                        const SizedBox(width: 16),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              AnimatedSwitcher(
                                duration: const Duration(milliseconds: 400),
                                child: Text(
                                  _journeyMessage,
                                  key: ValueKey(_journeyMessage),
                                  style: const TextStyle(
                                    fontWeight: FontWeight.bold,
                                    fontSize: 14,
                                  ),
                                ),
                              ),
                              const SizedBox(height: 4),
                              AnimatedSwitcher(
                                duration: const Duration(milliseconds: 400),
                                child: Text(
                                  _journeySubtitle,
                                  key: ValueKey(_journeySubtitle),
                                  style: TextStyle(
                                    fontSize: 12,
                                    color: Colors.grey[600],
                                  ),
                                ),
                              ),
                              const SizedBox(height: 8),
                              ClipRRect(
                                borderRadius: BorderRadius.circular(10),
                                child: TweenAnimationBuilder<double>(
                                  tween: Tween(begin: 0, end: _journeyProgress),
                                  duration: const Duration(milliseconds: 800),
                                  builder: (context, value, _) {
                                    return LinearProgressIndicator(
                                      value: value,
                                      minHeight: 8,
                                      backgroundColor: const Color(0xFFE0E0E0),
                                      valueColor: const AlwaysStoppedAnimation(
                                        darkGreen,
                                      ),
                                    );
                                  },
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
