import 'package:flutter/material.dart';
import 'package:carbonwise_app/services/api_service.dart';
import 'package:fl_chart/fl_chart.dart';
import 'package:carbonwise_app/services/gemini_service.dart';
import 'package:carbonwise_app/utils/smart_suggestion.dart';
import 'package:carbonwise_app/utils/strategy_notifier.dart';

const primaryGreen = Color(0xFF3AA76D);
const darkGreen = Color(0xFF1E5631);

enum ReportTimeframe { thisWeek, thisMonth, lastMonth }

class ReportsScreen extends StatefulWidget {
  const ReportsScreen({super.key});

  @override
  State<ReportsScreen> createState() => _ReportsScreenState();
}

class _NoEmissionRecords extends StatelessWidget {
  const _NoEmissionRecords();

  @override
  Widget build(BuildContext context) {
    return const Center(
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(Icons.bar_chart_outlined, size: 42, color: Colors.black26),
          SizedBox(height: 8),
          Text(
            "No emission records yet",
            style: TextStyle(color: Colors.black54, fontSize: 13),
          ),
        ],
      ),
    );
  }
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

  double _getHorizontalInterval() {
    if (emissionOverTime.isEmpty) return 1;

    final maxValue = emissionOverTime
        .map((spot) => spot.y)
        .reduce((a, b) => a > b ? a : b);

    if (maxValue <= 5) return 1;
    if (maxValue <= 20) return 5;
    if (maxValue <= 50) return 10;
    if (maxValue <= 100) return 20;

    return (maxValue / 5).ceilToDouble();
  }

  double _getXAxisInterval() {
    if (labels.length <= 4) {
      return 1;
    }

    if (labels.length <= 8) {
      return 2;
    }

    return (labels.length / 4).ceilToDouble();
  }

  List<FlSpot> tftForecastSpots = [];
  List<String> tftForecastLabels = [];
  bool isTftLoading = true;
  String? tftForecastError;

  @override
  void initState() {
    super.initState();
    _loadEmissionData();
    _loadChartData(_timeframeOverTime);
    _loadWeeklyComparison();
    _loadCarbonReductionJourney();
    strategyRefreshNotifier.addListener(_refreshReports);
    _loadTftForecast();
  }

  @override
  void dispose() {
    strategyRefreshNotifier.removeListener(_refreshReports);
    super.dispose();
  }

  double _toDouble(dynamic value) =>
      double.tryParse(value?.toString() ?? '0') ?? 0.0;

  DateTime? _recordDate(dynamic value) =>
      DateTime.tryParse(value?.toString() ?? '');

  bool _between(DateTime date, DateTime start, DateTime end) {
    final d = DateTime(date.year, date.month, date.day);
    final s = DateTime(start.year, start.month, start.day);
    final e = DateTime(end.year, end.month, end.day);
    return !d.isBefore(s) && !d.isAfter(e);
  }

  Future<List<dynamic>> _records() => _apiService.getCarbonRecords('');

  Future<void> _loadTftForecast() async {
    try {
      print('TFT: requesting forecast...');

      final data = await _apiService.getTft30DayForecast();

      print('TFT: response received');
      print('TFT DATA: $data');

      final forecast = data['forecast'];

      if (forecast is! List) {
        throw Exception('Invalid forecast data: $data');
      }

      final spots = <FlSpot>[];
      final dateLabels = <String>[];

      for (int i = 0; i < forecast.length; i++) {
        final item = Map<String, dynamic>.from(forecast[i] as Map);

        final date = DateTime.tryParse(item['record_date']?.toString() ?? '');

        final prediction = double.tryParse(
          item['predicted_total_emission']?.toString() ?? '',
        );

        if (date == null || prediction == null) continue;

        spots.add(FlSpot(i.toDouble(), prediction));

        dateLabels.add('${date.month}/${date.day}');
      }

      if (!mounted) return;

      setState(() {
        tftForecastSpots = spots;
        tftForecastLabels = dateLabels;
        isTftLoading = false;
        tftForecastError = null;
      });
    } catch (e) {
      print('TFT FORECAST ERROR: $e');

      if (!mounted) return;

      setState(() {
        isTftLoading = false;
        tftForecastError = e.toString();
      });
    }
  }

  Future<void> _loadEmissionData() async {
    try {
      final records = await _records();
      final now = DateTime.now();
      final startOfWeek = DateTime(
        now.year,
        now.month,
        now.day,
      ).subtract(Duration(days: now.weekday - 1));
      final startOfMonth = DateTime(now.year, now.month, 1);

      double total = 0;
      double week = 0;
      double month = 0;

      for (final raw in records) {
        final record = Map<String, dynamic>.from(raw as Map);
        final date = _recordDate(record['record_date']);
        if (date == null) continue;
        final emission = _toDouble(record['total_emission']);
        total += emission;
        if (_between(date, startOfWeek, now)) week += emission;
        if (_between(date, startOfMonth, now)) month += emission;
      }

      if (!mounted) return;
      setState(() {
        totalEmission = total;
        weekEmission = week;
        monthEmission = month;
        isLoading = false;
      });
    } catch (e) {
      print('Reports Error: $e');
      if (mounted) setState(() => isLoading = false);
    }
  }

  Future<void> _loadChartData(ReportTimeframe timeframe) async {
    try {
      final records = await _records();
      final now = DateTime.now();
      late DateTime start;
      late DateTime end;

      switch (timeframe) {
        case ReportTimeframe.thisWeek:
          start = DateTime(
            now.year,
            now.month,
            now.day,
          ).subtract(Duration(days: now.weekday - 1));
          end = DateTime(now.year, now.month, now.day);
          break;
        case ReportTimeframe.thisMonth:
          start = DateTime(now.year, now.month, 1);
          end = DateTime(now.year, now.month, now.day);
          break;
        case ReportTimeframe.lastMonth:
          start = DateTime(now.year, now.month - 1, 1);
          end = DateTime(now.year, now.month, 0);
          break;
      }

      final filtered = records
          .map((e) => Map<String, dynamic>.from(e as Map))
          .where((row) {
            final date = _recordDate(row['record_date']);
            return date != null && _between(date, start, end);
          })
          .toList();

      filtered.sort(
        (a, b) => (a['record_date'].toString()).compareTo(
          b['record_date'].toString(),
        ),
      );

      emissionOverTime = [];
      labels = [];
      transportTotal = 0;
      officeTotal = 0;
      foodTotal = 0;

      for (var i = 0; i < filtered.length; i++) {
        final row = filtered[i];
        final date = _recordDate(row['record_date'])!;
        emissionOverTime.add(
          FlSpot(i.toDouble(), _toDouble(row['total_emission'])),
        );
        labels.add('${date.month}/${date.day}');
        transportTotal += _toDouble(row['transportation']);
        officeTotal += _toDouble(row['electricity']);
        foodTotal += _toDouble(row['food']);
      }

      if (mounted) setState(() {});
      await _loadSmartSuggestions();
    } catch (e) {
      print('Chart Data Error: $e');
    }
  }

  Future<void> _loadWeeklyComparison() async {
    try {
      final records = await _records();
      final now = DateTime.now();
      final startOfThisWeek = DateTime(
        now.year,
        now.month,
        now.day,
      ).subtract(Duration(days: now.weekday - 1));
      final startOfLastWeek = startOfThisWeek.subtract(const Duration(days: 7));
      final endOfLastWeek = startOfThisWeek.subtract(const Duration(days: 1));
      final endOfThisWeek = startOfThisWeek.add(const Duration(days: 6));

      double thisWeekTotal = 0;
      double lastWeekTotal = 0;
      for (final raw in records) {
        final row = Map<String, dynamic>.from(raw as Map);
        final date = _recordDate(row['record_date']);
        if (date == null) continue;
        final emission = _toDouble(row['total_emission']);
        if (_between(date, startOfThisWeek, endOfThisWeek))
          thisWeekTotal += emission;
        if (_between(date, startOfLastWeek, endOfLastWeek))
          lastWeekTotal += emission;
      }

      if (!mounted) return;
      setState(() {
        _weeklyChange = lastWeekTotal == 0
            ? null
            : ((thisWeekTotal - lastWeekTotal) / lastWeekTotal) * 100;
      });
    } catch (e) {
      print('Weekly comparison error: $e');
    }
  }

  Future<void> _loadCarbonReductionJourney() async {
    try {
      final records = await _records();
      final now = DateTime.now();
      final thisMonthStart = DateTime(now.year, now.month, 1);
      final lastMonthStart = DateTime(now.year, now.month - 1, 1);
      final lastMonthEnd = thisMonthStart.subtract(const Duration(days: 1));

      double thisMonthTotal = 0;
      double lastMonthTotal = 0;
      for (final raw in records) {
        final row = Map<String, dynamic>.from(raw as Map);
        final date = _recordDate(row['record_date']);
        if (date == null) continue;
        final emission = _toDouble(row['total_emission']);
        if (_between(date, thisMonthStart, now)) thisMonthTotal += emission;
        if (_between(date, lastMonthStart, lastMonthEnd))
          lastMonthTotal += emission;
      }

      double score;
      String message;
      String subtitle;
      if (lastMonthTotal == 0) {
        score = 0;
        message = 'Start your sustainability journey!';
        subtitle = 'Record more activities to compare your monthly progress.';
      } else {
        final change =
            ((thisMonthTotal - lastMonthTotal) / lastMonthTotal) * 100;
        score = (100 - change).clamp(0, 100).toDouble();
        if (change <= -20) {
          message = 'Amazing work! 🎉';
          subtitle =
              'You reduced your emissions by ${change.abs().toStringAsFixed(1)}% this month.';
        } else if (change < 0) {
          message = 'Nice progress! 💚';
          subtitle =
              'You\'re emitting ${change.abs().toStringAsFixed(1)}% less than last month.';
        } else if (change == 0) {
          message = 'Steady progress 👍';
          subtitle = 'Your emissions stayed consistent this month.';
        } else {
          message = 'Let\'s improve next month! 🌍';
          subtitle =
              'Your emissions increased by ${change.toStringAsFixed(1)}%.';
        }
      }

      if (!mounted) return;
      setState(() {
        _journeyPercent = score;
        _journeyProgress = score / 100;
        _journeyMessage = message;
        _journeySubtitle = subtitle;
      });
    } catch (e) {
      print('Carbon Journey Error: $e');
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
                padding: const EdgeInsets.all(20),
                decoration: BoxDecoration(
                  color: const Color(0xFF3AA76D),
                  borderRadius: BorderRadius.circular(20),
                  boxShadow: [
                    BoxShadow(
                      color: const Color(0xFF3AA76D).withValues(alpha: 0.18),
                      blurRadius: 12,
                      offset: const Offset(0, 5),
                    ),
                  ],
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        Container(
                          padding: const EdgeInsets.all(8),
                          decoration: BoxDecoration(
                            color: Colors.white.withValues(alpha: 0.18),
                            shape: BoxShape.circle,
                          ),
                          child: const Icon(
                            Icons.eco_outlined,
                            color: Colors.white,
                            size: 22,
                          ),
                        ),

                        const SizedBox(width: 10),

                        const Expanded(
                          child: Text(
                            'Your Total Carbon Footprint',
                            style: TextStyle(
                              fontSize: 15,
                              fontWeight: FontWeight.w600,
                              color: Colors.white,
                            ),
                          ),
                        ),
                      ],
                    ),

                    const SizedBox(height: 22),

                    Row(
                      crossAxisAlignment: CrossAxisAlignment.end,
                      children: [
                        Text(
                          isLoading ? '--' : totalEmission.toStringAsFixed(2),
                          style: const TextStyle(
                            fontSize: 36,
                            fontWeight: FontWeight.bold,
                            color: Colors.white,
                          ),
                        ),

                        const SizedBox(width: 6),

                        const Padding(
                          padding: EdgeInsets.only(bottom: 7),
                          child: Text(
                            'kg CO₂e',
                            style: TextStyle(
                              fontSize: 15,
                              fontWeight: FontWeight.w500,
                              color: Colors.white70,
                            ),
                          ),
                        ),
                      ],
                    ),

                    const SizedBox(height: 6),

                    const Text(
                      'Your recorded carbon emissions so far.',
                      style: TextStyle(fontSize: 12, color: Colors.white70),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 20),

              const Text(
                'Quick Overview',
                style: TextStyle(
                  fontSize: 17,
                  fontWeight: FontWeight.bold,
                  color: Colors.black87,
                ),
              ),

              const SizedBox(height: 10),

              SizedBox(
                height: 135,
                child: ListView(
                  scrollDirection: Axis.horizontal,
                  physics: const BouncingScrollPhysics(),
                  children: [
                    SizedBox(
                      width: 150,
                      child: _buildStatCard(
                        'This Week',
                        isLoading ? '--' : weekEmission.toStringAsFixed(2),
                        'kg CO₂e',
                        icon: Icons.calendar_today_outlined,
                      ),
                    ),

                    const SizedBox(width: 10),

                    SizedBox(
                      width: 150,
                      child: _buildStatCard(
                        'This Month',
                        isLoading ? '--' : monthEmission.toStringAsFixed(2),
                        'kg CO₂e',
                        icon: Icons.calendar_month_outlined,
                      ),
                    ),

                    const SizedBox(width: 10),

                    SizedBox(
                      width: 150,
                      child: _buildStatCard(
                        "Weekly Change",
                        _weeklyChange == null
                            ? "--"
                            : "${_weeklyChange!.abs().toStringAsFixed(1)}%"
                                  "${_weeklyChange! < 0 ? " ↓" : " ↑"}",
                        "vs last week",
                        icon: _weeklyChange != null && _weeklyChange! < 0
                            ? Icons.trending_down
                            : Icons.trending_up,
                        valueColor: _weeklyChange != null && _weeklyChange! < 0
                            ? primaryGreen
                            : Colors.red,
                      ),
                    ),

                    const SizedBox(width: 4),
                  ],
                ),
              ),

              const SizedBox(height: 20),
              const SizedBox(height: 16),

              // 5. Charts
              Container(
                padding: const EdgeInsets.all(16),
                decoration: _cardDecoration(),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text(
                      "Emissions Over Time",
                      style: TextStyle(
                        fontSize: 17,
                        fontWeight: FontWeight.bold,
                        color: Colors.black87,
                      ),
                    ),

                    const SizedBox(height: 12),

                    _buildTimeframeSelector(),

                    const SizedBox(height: 18),

                    SizedBox(
                      height: 230,
                      child: emissionOverTime.isEmpty
                          ? const _NoEmissionRecords()
                          : LineChart(
                              LineChartData(
                                minX: 0,
                                maxX: emissionOverTime.length > 1
                                    ? (emissionOverTime.length - 1).toDouble()
                                    : 1,
                                minY: 0,

                                gridData: FlGridData(
                                  show: true,
                                  drawVerticalLine: false,
                                  horizontalInterval: _getHorizontalInterval(),
                                  getDrawingHorizontalLine: (value) {
                                    return FlLine(
                                      color: Colors.black.withValues(
                                        alpha: 0.07,
                                      ),
                                      strokeWidth: 1,
                                    );
                                  },
                                ),

                                borderData: FlBorderData(show: false),

                                titlesData: FlTitlesData(
                                  topTitles: const AxisTitles(
                                    sideTitles: SideTitles(showTitles: false),
                                  ),

                                  rightTitles: const AxisTitles(
                                    sideTitles: SideTitles(showTitles: false),
                                  ),

                                  leftTitles: AxisTitles(
                                    sideTitles: SideTitles(
                                      showTitles: true,
                                      reservedSize: 38,
                                      interval: _getHorizontalInterval(),
                                      getTitlesWidget: (value, meta) {
                                        if (value == 0) {
                                          return const SizedBox();
                                        }

                                        return Padding(
                                          padding: const EdgeInsets.only(
                                            right: 6,
                                          ),
                                          child: Text(
                                            value.toStringAsFixed(0),
                                            style: const TextStyle(
                                              fontSize: 9,
                                              color: Colors.black45,
                                            ),
                                          ),
                                        );
                                      },
                                    ),
                                  ),

                                  bottomTitles: AxisTitles(
                                    sideTitles: SideTitles(
                                      showTitles: true,
                                      reservedSize: 32,
                                      interval: _getXAxisInterval(),
                                      getTitlesWidget: (value, meta) {
                                        final index = value.toInt();

                                        if (index < 0 ||
                                            index >= labels.length) {
                                          return const SizedBox();
                                        }

                                        return Padding(
                                          padding: const EdgeInsets.only(
                                            top: 8,
                                          ),
                                          child: Text(
                                            labels[index],
                                            style: const TextStyle(
                                              fontSize: 9,
                                              color: Colors.black45,
                                            ),
                                          ),
                                        );
                                      },
                                    ),
                                  ),
                                ),

                                lineTouchData: LineTouchData(
                                  enabled: true,
                                  touchTooltipData: LineTouchTooltipData(
                                    getTooltipItems: (touchedSpots) {
                                      return touchedSpots.map((spot) {
                                        final index = spot.x.toInt();

                                        final date =
                                            index >= 0 && index < labels.length
                                            ? labels[index]
                                            : "";

                                        return LineTooltipItem(
                                          "$date\n${spot.y.toStringAsFixed(2)} kg CO₂e",
                                          const TextStyle(
                                            color: Colors.white,
                                            fontSize: 11,
                                            fontWeight: FontWeight.bold,
                                          ),
                                        );
                                      }).toList();
                                    },
                                  ),
                                ),

                                lineBarsData: [
                                  LineChartBarData(
                                    spots: emissionOverTime,
                                    isCurved: true,
                                    curveSmoothness: 0.3,
                                    color: const Color(0xFF3AA76D),
                                    barWidth: 3,
                                    isStrokeCapRound: true,

                                    dotData: FlDotData(
                                      show: true,
                                      getDotPainter:
                                          (spot, percent, barData, index) {
                                            return FlDotCirclePainter(
                                              radius: 4,
                                              color: Colors.white,
                                              strokeWidth: 2,
                                              strokeColor: const Color(
                                                0xFF3AA76D,
                                              ),
                                            );
                                          },
                                    ),

                                    belowBarData: BarAreaData(
                                      show: true,
                                      color: const Color(
                                        0xFF3AA76D,
                                      ).withValues(alpha: 0.10),
                                    ),
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
                    const Text(
                      "Emissions by Source",
                      style: TextStyle(
                        fontSize: 17,
                        fontWeight: FontWeight.bold,
                        color: Colors.black87,
                      ),
                    ),

                    const SizedBox(height: 12),

                    _buildTimeframeSelector(),

                    const SizedBox(height: 20),

                    SizedBox(
                      height: 230,
                      child:
                          (transportTotal == 0 &&
                              officeTotal == 0 &&
                              foodTotal == 0)
                          ? const _NoEmissionRecords()
                          : PieChart(
                              PieChartData(
                                centerSpaceRadius: 45,
                                sectionsSpace: 3,
                                borderData: FlBorderData(show: false),

                                sections: [
                                  PieChartSectionData(
                                    value: transportTotal,
                                    color: Colors.green,
                                    title:
                                        "${transportTotal.toStringAsFixed(1)} kg",
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
                                    title:
                                        "${officeTotal.toStringAsFixed(1)} kg",
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

                    Wrap(
                      alignment: WrapAlignment.center,
                      spacing: 16,
                      runSpacing: 10,
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

              const SizedBox(height: 20),

              // 6. 30-Day TFT Forecast
              Container(
                padding: const EdgeInsets.all(16),
                decoration: _cardDecoration(),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text(
                      "30-Day Emissions Forecast",
                      style: TextStyle(
                        fontSize: 17,
                        fontWeight: FontWeight.bold,
                        color: Colors.black87,
                      ),
                    ),

                    const SizedBox(height: 6),

                    const Text(
                      "Predicted carbon emissions for the next 30 days",
                      style: TextStyle(fontSize: 12, color: Colors.black54),
                    ),

                    const SizedBox(height: 18),

                    SizedBox(
                      height: 250,
                      child: isTftLoading
                          ? const Center(
                              child: CircularProgressIndicator(
                                color: primaryGreen,
                              ),
                            )
                          : tftForecastError != null
                          ? Center(
                              child: Text(
                                tftForecastError ?? "Unable to load forecast.",
                                textAlign: TextAlign.center,
                                style: const TextStyle(
                                  color: Colors.black54,
                                  fontSize: 13,
                                ),
                              ),
                            )
                          : tftForecastSpots.isEmpty
                          ? const Center(
                              child: Text(
                                "No forecast available.",
                                style: TextStyle(
                                  color: Colors.black54,
                                  fontSize: 13,
                                ),
                              ),
                            )
                          : LineChart(
                              LineChartData(
                                minX: 0,
                                maxX: tftForecastSpots.length > 1
                                    ? (tftForecastSpots.length - 1).toDouble()
                                    : 1,
                                minY: 0,

                                gridData: FlGridData(
                                  show: true,
                                  drawVerticalLine: false,
                                  horizontalInterval: 100,
                                ),

                                titlesData: FlTitlesData(
                                  topTitles: const AxisTitles(
                                    sideTitles: SideTitles(showTitles: false),
                                  ),

                                  rightTitles: const AxisTitles(
                                    sideTitles: SideTitles(showTitles: false),
                                  ),

                                  leftTitles: AxisTitles(
                                    sideTitles: SideTitles(
                                      showTitles: true,
                                      reservedSize: 48,
                                      interval: 100,
                                      getTitlesWidget: (value, meta) {
                                        return Text(
                                          value.toStringAsFixed(0),
                                          style: const TextStyle(
                                            fontSize: 9,
                                            color: Colors.black45,
                                          ),
                                        );
                                      },
                                    ),
                                  ),

                                  bottomTitles: AxisTitles(
                                    sideTitles: SideTitles(
                                      showTitles: true,
                                      reservedSize: 32,
                                      interval: 5,
                                      getTitlesWidget: (value, meta) {
                                        final index = value.toInt();

                                        if (index < 0 ||
                                            index >= tftForecastLabels.length) {
                                          return const SizedBox();
                                        }

                                        return Padding(
                                          padding: const EdgeInsets.only(
                                            top: 8,
                                          ),
                                          child: Text(
                                            tftForecastLabels[index],
                                            style: const TextStyle(
                                              fontSize: 9,
                                              color: Colors.black45,
                                            ),
                                          ),
                                        );
                                      },
                                    ),
                                  ),
                                ),

                                borderData: FlBorderData(show: false),

                                lineTouchData: LineTouchData(
                                  enabled: true,
                                  touchTooltipData: LineTouchTooltipData(
                                    getTooltipItems: (touchedSpots) {
                                      return touchedSpots.map((spot) {
                                        final index = spot.x.toInt();

                                        final date =
                                            index >= 0 &&
                                                index < tftForecastLabels.length
                                            ? tftForecastLabels[index]
                                            : "";

                                        return LineTooltipItem(
                                          "$date\n"
                                          "${spot.y.toStringAsFixed(2)} kg CO₂e",
                                          const TextStyle(
                                            color: Colors.white,
                                            fontSize: 11,
                                            fontWeight: FontWeight.bold,
                                          ),
                                        );
                                      }).toList();
                                    },
                                  ),
                                ),

                                lineBarsData: [
                                  LineChartBarData(
                                    spots: tftForecastSpots,
                                    isCurved: true,
                                    curveSmoothness: 0.3,
                                    color: primaryGreen,
                                    barWidth: 3,
                                    isStrokeCapRound: true,

                                    dotData: FlDotData(show: false),

                                    belowBarData: BarAreaData(
                                      show: true,
                                      color: primaryGreen.withValues(
                                        alpha: 0.10,
                                      ),
                                    ),
                                  ),
                                ],
                              ),
                            ),
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
              Column(
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

                  const SizedBox(height: 10),

                  _buildSuggestionCard(
                    Icons.lightbulb_outline,
                    _smartSuggestions.length > 1
                        ? _smartSuggestions[1].suggestion
                        : "",
                    _smartSuggestions.length > 1
                        ? _smartSuggestions[1].impact
                        : "",
                  ),

                  const SizedBox(height: 10),

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
    required IconData icon,
    Color valueColor = const Color(0xFF265D3B),
  }) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: const Color(0xFFE7F0EA)),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.035),
            blurRadius: 8,
            offset: const Offset(0, 3),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            padding: const EdgeInsets.all(7),
            decoration: const BoxDecoration(
              color: Color(0xFFEAF6EE),
              shape: BoxShape.circle,
            ),
            child: Icon(icon, size: 18, color: const Color(0xFF3AA76D)),
          ),

          const Spacer(),

          Text(
            title,
            style: const TextStyle(
              fontSize: 11,
              fontWeight: FontWeight.w500,
              color: Colors.black54,
            ),
          ),

          const SizedBox(height: 3),

          Text(
            value,
            style: TextStyle(
              fontSize: 21,
              fontWeight: FontWeight.bold,
              color: valueColor,
            ),
          ),

          const SizedBox(height: 2),

          Text(
            unit,
            style: const TextStyle(fontSize: 10, color: Colors.black45),
          ),
        ],
      ),
    );
  }

  Widget _buildSuggestionCard(IconData icon, String mainText, String subText) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFFE3F0E7)),
      ),
      child: Row(
        children: [
          Container(
            width: 44,
            height: 44,
            decoration: const BoxDecoration(
              color: Color(0xFFEAF6EE),
              shape: BoxShape.circle,
            ),
            child: Icon(icon, color: const Color(0xFF3AA76D), size: 22),
          ),

          const SizedBox(width: 12),

          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  mainText.isEmpty ? "Loading suggestion..." : mainText,
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(
                    fontSize: 13,
                    fontWeight: FontWeight.bold,
                    color: Colors.black87,
                  ),
                ),

                if (subText.isNotEmpty) ...[
                  const SizedBox(height: 4),

                  Text(
                    subText,
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(fontSize: 11, color: Colors.black54),
                  ),
                ],
              ],
            ),
          ),

          const SizedBox(width: 6),

          const Icon(Icons.chevron_right, color: Colors.black38),
        ],
      ),
    );
  }

  Widget _buildTimeframeSelector() {
    return SingleChildScrollView(
      scrollDirection: Axis.horizontal,
      physics: const BouncingScrollPhysics(),
      child: Row(
        children: [
          _buildTimeframeChip(
            label: "This Week",
            timeframe: ReportTimeframe.thisWeek,
          ),

          const SizedBox(width: 8),

          _buildTimeframeChip(
            label: "This Month",
            timeframe: ReportTimeframe.thisMonth,
          ),

          const SizedBox(width: 8),

          _buildTimeframeChip(
            label: "Last Month",
            timeframe: ReportTimeframe.lastMonth,
          ),
        ],
      ),
    );
  }

  Widget _buildTimeframeChip({
    required String label,
    required ReportTimeframe timeframe,
  }) {
    final isSelected = _timeframeOverTime == timeframe;

    return InkWell(
      onTap: () {
        if (isSelected) return;

        setState(() {
          _timeframeOverTime = timeframe;
        });

        _loadChartData(timeframe);
      },
      borderRadius: BorderRadius.circular(20),
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 200),
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 9),
        decoration: BoxDecoration(
          color: isSelected ? primaryGreen : const Color(0xFFEAF6EE),
          borderRadius: BorderRadius.circular(20),
          border: Border.all(
            color: isSelected ? primaryGreen : const Color(0xFFD7EBDD),
          ),
        ),
        child: Text(
          label,
          style: TextStyle(
            fontSize: 12,
            fontWeight: FontWeight.w600,
            color: isSelected ? Colors.white : const Color(0xFF265D3B),
          ),
        ),
      ),
    );
  }
}
