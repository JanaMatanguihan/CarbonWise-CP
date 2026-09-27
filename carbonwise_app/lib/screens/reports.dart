import 'package:flutter/material.dart';
import 'package:carbonwise_app/services/api_service.dart';
import 'package:fl_chart/fl_chart.dart';
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

  String? tftForecastStatus;
  int tftRecordsAvailable = 0;
  int tftRecordsRequired = 90;

  @override
  void initState() {
    super.initState();
    _loadAllReportsData();
    strategyRefreshNotifier.addListener(_refreshReports);
  }

  Future<void> _loadAllReportsData() async {
    await _loadEmissionData();
    if (!mounted) return;

    await _loadChartData(_timeframeOverTime);
    if (!mounted) return;

    await _loadWeeklyComparison();
    if (!mounted) return;

    await _loadTftForecast();
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

  Future<List<dynamic>>? _recordsFuture;

  Future<List<dynamic>> _records() {
    _recordsFuture ??= _apiService.getCarbonRecords('');
    return _recordsFuture!;
  }

  Future<void> _loadTftForecast() async {
    try {
      print('TFT: requesting forecast...');

      final data = await _apiService.getTft30DayForecast();

      final status = data['status']?.toString();

      final recordsAvailable =
          int.tryParse(data['records_available']?.toString() ?? '') ?? 0;

      final recordsRequired =
          int.tryParse(data['records_required']?.toString() ?? '') ?? 90;

      print('TFT STATUS: $status');
      print('TFT RECORDS: $recordsAvailable / $recordsRequired');

      // NO DATA / NEW USER
      if (status == 'no_data') {
        if (!mounted) return;

        setState(() {
          tftForecastSpots = [];
          tftForecastLabels = [];

          isTftLoading = false;
          tftForecastError = null;

          tftForecastStatus = 'no_data';
          tftRecordsAvailable = recordsAvailable;
          tftRecordsRequired = recordsRequired;
        });

        return;
      }

      // NOT ENOUGH HISTORY
      if (status == 'insufficient_data') {
        if (!mounted) return;

        setState(() {
          tftForecastSpots = [];
          tftForecastLabels = [];

          isTftLoading = false;
          tftForecastError = null;

          tftForecastStatus = 'insufficient_data';
          tftRecordsAvailable = recordsAvailable;
          tftRecordsRequired = recordsRequired;
        });

        return;
      }

      // SERVER ERROR
      if (status == 'error') {
        throw Exception(
          data['message']?.toString() ??
              'The forecasting service is currently unavailable.',
        );
      }

      // SUCCESS
      final forecast = data['forecast'];

      if (forecast is! List || forecast.isEmpty) {
        throw Exception('No 30-day forecast was returned.');
      }

      final spots = <FlSpot>[];
      final dateLabels = <String>[];

      for (int i = 0; i < forecast.length && i < 30; i++) {
        final item = Map<String, dynamic>.from(forecast[i] as Map);

        final targetDate = DateTime.tryParse(
          item['record_date']?.toString() ?? '',
        );

        final prediction = double.tryParse(item['forecast']?.toString() ?? '');

        if (targetDate == null || prediction == null) {
          continue;
        }

        spots.add(FlSpot(spots.length.toDouble(), prediction));

        dateLabels.add('${targetDate.month}/${targetDate.day}');
      }

      if (spots.length != 30) {
        throw Exception(
          'Expected 30 forecast entries, but received ${spots.length}.',
        );
      }

      if (!mounted) return;

      setState(() {
        tftForecastSpots = spots;
        tftForecastLabels = dateLabels;

        isTftLoading = false;
        tftForecastError = null;

        tftForecastStatus = 'success';
        tftRecordsAvailable = recordsAvailable;
        tftRecordsRequired = recordsRequired;
      });
    } catch (e) {
      print('TFT FORECAST ERROR: $e');

      if (!mounted) return;

      setState(() {
        tftForecastSpots = [];
        tftForecastLabels = [];

        isTftLoading = false;
        tftForecastError = e.toString();

        tftForecastStatus = 'error';
      });
    }
  }

  Future<void> _loadEmissionData() async {
    try {
      final records = await _records();
      print('CARBON RECORD COUNT: ${records.length}');
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
      print('>>> CHART: _loadChartData STARTED');

      final records = await _records();

      print('>>> CHART: _records() FINISHED');
      print('>>> CHART: RECORD COUNT = ${records.length}');

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

      print('========== CHART DEBUG ==========');
      print('TIMEFRAME: $timeframe');
      print('TOTAL RECORDS: ${records.length}');
      print('FILTERED RECORDS: ${filtered.length}');
      print('START: $start');
      print('END: $end');
      print('=================================');

      filtered.sort(
        (a, b) => (a['record_date'].toString()).compareTo(
          b['record_date'].toString(),
        ),
      );

      print('>>> CHART: SORT FINISHED');

      emissionOverTime = [];
      labels = [];
      transportTotal = 0;
      officeTotal = 0;
      foodTotal = 0;

      print('>>> CHART: VARIABLES RESET');

      for (var i = 0; i < filtered.length; i++) {
        final row = filtered[i];
        final date = _recordDate(row['record_date'])!;
        emissionOverTime.add(
          FlSpot(i.toDouble(), _toDouble(row['total_emission'])),
        );

        print('>>> CHART: PROCESSING RECORD $i');

        labels.add('${date.month}/${date.day}');
        transportTotal += _toDouble(row['transportation']);
        officeTotal += _toDouble(row['electricity']);
        foodTotal += _toDouble(row['food']);
      }

      print('>>> CHART: LOOP FINISHED');
      print('>>> CHART: EMISSION POINTS = ${emissionOverTime.length}');
      print('>>> CHART: LABELS = ${labels.length}');
      print('>>> CHART: TRANSPORT = $transportTotal');
      print('>>> CHART: OFFICE = $officeTotal');
      print('>>> CHART: FOOD = $foodTotal');

      if (mounted) {
        print('>>> CHART: CALLING SETSTATE');
        setState(() {});
        print('>>> CHART: SETSTATE FINISHED');
      }
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

  Future<void> _refreshReports() async {
    await _loadChartData(_timeframeOverTime);
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
        await _loadTftForecast();
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
                padding: const EdgeInsets.all(18),
                decoration: _cardDecoration(),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Row(
                          children: [
                            Container(
                              padding: const EdgeInsets.all(8),
                              decoration: BoxDecoration(
                                color: const Color(0xFFEAF6EE),
                                borderRadius: BorderRadius.circular(10),
                              ),
                              child: const Icon(
                                Icons.insights_rounded,
                                color: primaryGreen,
                                size: 20,
                              ),
                            ),
                            const SizedBox(width: 10),
                            const Text(
                              "30-Day Emissions Forecast",
                              style: TextStyle(
                                fontSize: 16,
                                fontWeight: FontWeight.bold,
                                color: Colors.black87,
                              ),
                            ),
                          ],
                        ),
                        Container(
                          padding: const EdgeInsets.symmetric(
                            horizontal: 8,
                            vertical: 4,
                          ),
                          decoration: BoxDecoration(
                            color: const Color(0xFFEAF6EE),
                            borderRadius: BorderRadius.circular(12),
                          ),
                          child: const Text(
                            "TFT Model",
                            style: TextStyle(
                              fontSize: 10,
                              fontWeight: FontWeight.w600,
                              color: darkGreen,
                            ),
                          ),
                        ),
                      ],
                    ),

                    const SizedBox(height: 6),

                    const Text(
                      "Projected carbon footprint trends for the upcoming month (Swipe to scroll)",
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
                          : tftForecastStatus == 'no_data'
                          ? _buildTftEmptyState()
                          : tftForecastStatus == 'insufficient_data'
                          ? _buildTftInsufficientDataState()
                          : tftForecastStatus == 'error'
                          ? _buildTftErrorState()
                          : tftForecastSpots.isEmpty
                          ? const Center(
                              child: Text(
                                'No forecast available.',
                                style: TextStyle(
                                  color: Colors.black54,
                                  fontSize: 13,
                                ),
                              ),
                            )
                          : SingleChildScrollView(
                              scrollDirection: Axis.horizontal,
                              physics: const BouncingScrollPhysics(),
                              child: SizedBox(
                                width: tftForecastSpots.length * 45.0,
                                child: LineChart(
                                  LineChartData(
                                    minX: 0,
                                    maxX: tftForecastSpots.length > 1
                                        ? (tftForecastSpots.length - 1)
                                              .toDouble()
                                        : 1,
                                    minY: 0,

                                    gridData: FlGridData(
                                      show: true,
                                      drawVerticalLine: false,
                                      horizontalInterval: 5,
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
                                        sideTitles: SideTitles(
                                          showTitles: false,
                                        ),
                                      ),

                                      rightTitles: const AxisTitles(
                                        sideTitles: SideTitles(
                                          showTitles: false,
                                        ),
                                      ),

                                      leftTitles: AxisTitles(
                                        sideTitles: SideTitles(
                                          showTitles: true,
                                          reservedSize: 40,
                                          interval: 5,
                                          getTitlesWidget: (value, meta) {
                                            if (value == 0) {
                                              return const SizedBox();
                                            }

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
                                          interval:
                                              tftForecastLabels.length > 10
                                              ? 5
                                              : 1,
                                          getTitlesWidget: (value, meta) {
                                            final index = value.toInt();

                                            if (index < 0 ||
                                                index >=
                                                    tftForecastLabels.length) {
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

                                    lineTouchData: LineTouchData(
                                      enabled: true,
                                      touchTooltipData: LineTouchTooltipData(
                                        getTooltipItems: (touchedSpots) {
                                          return touchedSpots.map((spot) {
                                            final index = spot.x.toInt();

                                            final date =
                                                index >= 0 &&
                                                    index <
                                                        tftForecastLabels.length
                                                ? tftForecastLabels[index]
                                                : '';

                                            return LineTooltipItem(
                                              '$date\n'
                                              '${spot.y.toStringAsFixed(2)} kg CO₂e',
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
                                        curveSmoothness: 0.25,
                                        color: primaryGreen,
                                        barWidth: 3,
                                        isStrokeCapRound: true,

                                        dotData: FlDotData(
                                          show: true,
                                          getDotPainter:
                                              (spot, percent, barData, index) {
                                                return FlDotCirclePainter(
                                                  radius: 3,
                                                  color: Colors.white,
                                                  strokeWidth: 2,
                                                  strokeColor: primaryGreen,
                                                );
                                              },
                                        ),

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
                            ),
                    ),
                  ],
                ),
              ),

              const SizedBox(height: 20),
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

  Widget _buildTftEmptyState() {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 24),
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Container(
            width: 64,
            height: 64,
            decoration: BoxDecoration(
              color: primaryGreen.withValues(alpha: 0.10),
              shape: BoxShape.circle,
            ),
            child: const Icon(
              Icons.insights_outlined,
              color: primaryGreen,
              size: 30,
            ),
          ),

          const SizedBox(height: 14),

          const Text(
            'Your forecast will appear here',
            textAlign: TextAlign.center,
            style: TextStyle(
              fontSize: 15,
              fontWeight: FontWeight.bold,
              color: Color(0xFF1F2933),
            ),
          ),

          const SizedBox(height: 7),

          const Text(
            'Start recording your daily activities to build your '
            'carbon emission history and generate a personalized '
            '30-day forecast.',
            textAlign: TextAlign.center,
            style: TextStyle(fontSize: 12, height: 1.5, color: Colors.black54),
          ),
        ],
      ),
    );
  }

  Widget _buildTftInsufficientDataState() {
    final progress = tftRecordsRequired > 0
        ? (tftRecordsAvailable / tftRecordsRequired).clamp(0.0, 1.0)
        : 0.0;

    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 20),
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Container(
            width: 58,
            height: 58,
            decoration: BoxDecoration(
              color: primaryGreen.withValues(alpha: 0.10),
              shape: BoxShape.circle,
            ),
            child: const Icon(
              Icons.trending_up_rounded,
              color: primaryGreen,
              size: 28,
            ),
          ),

          const SizedBox(height: 12),

          const Text(
            'Building your emission history',
            textAlign: TextAlign.center,
            style: TextStyle(
              fontSize: 15,
              fontWeight: FontWeight.bold,
              color: Color(0xFF1F2933),
            ),
          ),

          const SizedBox(height: 6),

          Text(
            'You have recorded $tftRecordsAvailable '
            'of $tftRecordsRequired required history entries.',
            textAlign: TextAlign.center,
            style: const TextStyle(fontSize: 12, color: Colors.black54),
          ),

          const SizedBox(height: 14),

          ClipRRect(
            borderRadius: BorderRadius.circular(10),
            child: LinearProgressIndicator(
              value: progress,
              minHeight: 7,
              backgroundColor: const Color(0xFFE5EEE8),
              valueColor: const AlwaysStoppedAnimation<Color>(primaryGreen),
            ),
          ),

          const SizedBox(height: 8),

          Text(
            '${(progress * 100).round()}% of required history',
            style: const TextStyle(fontSize: 11, color: Colors.black45),
          ),
        ],
      ),
    );
  }

  Widget _buildTftErrorState() {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 24),
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          const Icon(Icons.cloud_off_outlined, color: Colors.black38, size: 38),

          const SizedBox(height: 10),

          const Text(
            'Forecast temporarily unavailable',
            textAlign: TextAlign.center,
            style: TextStyle(
              fontSize: 14,
              fontWeight: FontWeight.bold,
              color: Color(0xFF1F2933),
            ),
          ),

          const SizedBox(height: 5),

          const Text(
            'We could not generate your forecast right now. '
            'Please try again later.',
            textAlign: TextAlign.center,
            style: TextStyle(fontSize: 12, color: Colors.black54),
          ),
        ],
      ),
    );
  }
}
