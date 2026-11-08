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

  int _chartTab = 0;
  int? _selectedForecastIndex;
  bool _forecastExpanded = false;

  List<FlSpot> emissionOverTime = [];

  double transportTotal = 0;
  double officeTotal = 0;
  double foodTotal = 0;
  double? _weeklyChange;

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
    if (labels.length <= 4) return 1;
    if (labels.length <= 8) return 2;
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
      final data = await _apiService.getTft30DayForecast();

      final status = data['status']?.toString();

      final recordsAvailable =
          int.tryParse(data['records_available']?.toString() ?? '') ?? 0;

      final recordsRequired =
          int.tryParse(data['records_required']?.toString() ?? '') ?? 90;

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

        if (targetDate == null || prediction == null) continue;

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
        _selectedForecastIndex = null;
      });
    } catch (e) {
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
    } catch (_) {
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
    } catch (_) {
      // Keep screen usable if charts cannot be loaded.
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
    } catch (_) {
      // Weekly comparison is a nice-to-have.
    }
  }

  Future<void> _refreshReports() async {
    await _loadChartData(_timeframeOverTime);
  }

  @override
  Widget build(BuildContext context) {
    return RefreshIndicator(
      color: primaryGreen,
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
              _buildTotalCard(),
              const SizedBox(height: 16),
              _buildQuickOverviewCard(),
              const SizedBox(height: 16),
              _buildMergedChartCard(),
              const SizedBox(height: 16),
              _buildForecastSection(),
              const SizedBox(height: 20),
            ],
          ),
        ),
      ),
    );
  }

  // 1. TOTAL EMISSION CARD
  Widget _buildTotalCard() {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: primaryGreen,
        borderRadius: BorderRadius.circular(20),
        boxShadow: [
          BoxShadow(
            color: primaryGreen.withValues(alpha: 0.18),
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
            'The total CO₂ you\'ve emitted from all your recorded activities so far.',
            style: TextStyle(fontSize: 12, color: Colors.white70, height: 1.4),
          ),
        ],
      ),
    );
  }

  // 2. QUICK OVERVIEW CARD (single row of 3 stats)
  Widget _buildQuickOverviewCard() {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 14),
      decoration: _cardDecoration(),
      child: Row(
        children: [
          Expanded(
            child: _buildMiniStat(
              title: 'This Week',
              value: isLoading ? '--' : weekEmission.toStringAsFixed(2),
              unit: 'kg CO₂e',
              icon: Icons.calendar_today_outlined,
            ),
          ),
          _statDivider(),
          Expanded(
            child: _buildMiniStat(
              title: 'This Month',
              value: isLoading ? '--' : monthEmission.toStringAsFixed(2),
              unit: 'kg CO₂e',
              icon: Icons.calendar_month_outlined,
            ),
          ),
          _statDivider(),
          Expanded(
            child: _buildMiniStat(
              title: 'Weekly Δ',
              value: _weeklyChange == null
                  ? '--'
                  : '${_weeklyChange!.abs().toStringAsFixed(1)}%',
              unit: _weeklyChange == null
                  ? 'no data'
                  : (_weeklyChange! < 0
                        ? 'lower than last'
                        : 'higher than last'),
              icon: _weeklyChange != null && _weeklyChange! < 0
                  ? Icons.trending_down
                  : Icons.trending_up,
              valueColor: _weeklyChange != null && _weeklyChange! < 0
                  ? primaryGreen
                  : Colors.redAccent,
            ),
          ),
        ],
      ),
    );
  }

  Widget _statDivider() {
    return Container(width: 1, height: 56, color: const Color(0xFFE5EEE8));
  }

  Widget _buildMiniStat({
    required String title,
    required String value,
    required String unit,
    required IconData icon,
    Color valueColor = darkGreen,
  }) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 4),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.center,
        children: [
          Icon(icon, size: 16, color: primaryGreen),
          const SizedBox(height: 8),
          Text(
            title,
            textAlign: TextAlign.center,
            style: const TextStyle(
              fontSize: 11,
              color: Colors.black54,
              fontWeight: FontWeight.w500,
            ),
          ),
          const SizedBox(height: 4),
          Text(
            value,
            textAlign: TextAlign.center,
            style: TextStyle(
              fontSize: 18,
              fontWeight: FontWeight.bold,
              color: valueColor,
            ),
          ),
          const SizedBox(height: 2),
          Text(
            unit,
            textAlign: TextAlign.center,
            maxLines: 2,
            overflow: TextOverflow.ellipsis,
            style: const TextStyle(fontSize: 9, color: Colors.black45),
          ),
        ],
      ),
    );
  }

  // 3. MERGED CHART CARD (Trend | Breakdown)
  Widget _buildMergedChartCard() {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: _cardDecoration(),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Expanded(child: _buildChartTab(label: 'Trend', index: 0)),
              const SizedBox(width: 8),
              Expanded(child: _buildChartTab(label: 'Breakdown', index: 1)),
            ],
          ),
          const SizedBox(height: 14),
          _buildTimeframeSelector(),
          const SizedBox(height: 18),
          SizedBox(
            height: 240,
            child: _chartTab == 0 ? _buildTrendChart() : _buildBreakdownChart(),
          ),
          if (_chartTab == 1) ...[
            const SizedBox(height: 16),
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
        ],
      ),
    );
  }

  Widget _buildChartTab({required String label, required int index}) {
    final isSelected = _chartTab == index;
    return InkWell(
      onTap: () {
        if (isSelected) return;
        setState(() => _chartTab = index);
      },
      borderRadius: BorderRadius.circular(12),
      child: Container(
        padding: const EdgeInsets.symmetric(vertical: 10),
        decoration: BoxDecoration(
          color: isSelected ? primaryGreen : const Color(0xFFEAF6EE),
          borderRadius: BorderRadius.circular(12),
        ),
        child: Center(
          child: Text(
            label,
            style: TextStyle(
              fontSize: 13,
              fontWeight: FontWeight.w600,
              color: isSelected ? Colors.white : darkGreen,
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildTrendChart() {
    if (emissionOverTime.isEmpty) {
      return const _NoEmissionRecords();
    }

    return LineChart(
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
              color: Colors.black.withValues(alpha: 0.07),
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
                if (value == 0) return const SizedBox();
                return Padding(
                  padding: const EdgeInsets.only(right: 6),
                  child: Text(
                    value.toStringAsFixed(0),
                    style: const TextStyle(fontSize: 9, color: Colors.black45),
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
                if (index < 0 || index >= labels.length) {
                  return const SizedBox();
                }
                return Padding(
                  padding: const EdgeInsets.only(top: 8),
                  child: Text(
                    labels[index],
                    style: const TextStyle(fontSize: 9, color: Colors.black45),
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
                final date = index >= 0 && index < labels.length
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
            color: primaryGreen,
            barWidth: 3,
            isStrokeCapRound: true,
            dotData: FlDotData(
              show: true,
              getDotPainter: (spot, percent, barData, index) {
                return FlDotCirclePainter(
                  radius: 4,
                  color: Colors.white,
                  strokeWidth: 2,
                  strokeColor: primaryGreen,
                );
              },
            ),
            belowBarData: BarAreaData(
              show: true,
              color: primaryGreen.withValues(alpha: 0.10),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildBreakdownChart() {
    final noData = transportTotal == 0 && officeTotal == 0 && foodTotal == 0;

    if (noData) return const _NoEmissionRecords();

    return PieChart(
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
    );
  }

  Widget _buildTimeframeSelector() {
    return Row(
      children: [
        Expanded(
          child: _buildTimeframeChip(
            label: "This Week",
            timeframe: ReportTimeframe.thisWeek,
          ),
        ),
        const SizedBox(width: 8),
        Expanded(
          child: _buildTimeframeChip(
            label: "This Month",
            timeframe: ReportTimeframe.thisMonth,
          ),
        ),
        const SizedBox(width: 8),
        Expanded(
          child: _buildTimeframeChip(
            label: "Last Month",
            timeframe: ReportTimeframe.lastMonth,
          ),
        ),
      ],
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
        padding: const EdgeInsets.symmetric(vertical: 8),
        decoration: BoxDecoration(
          color: isSelected ? primaryGreen : const Color(0xFFEAF6EE),
          borderRadius: BorderRadius.circular(20),
          border: Border.all(
            color: isSelected ? primaryGreen : const Color(0xFFD7EBDD),
          ),
        ),
        child: Center(
          child: Text(
            label,
            style: TextStyle(
              fontSize: 11,
              fontWeight: FontWeight.w600,
              color: isSelected ? Colors.white : darkGreen,
            ),
          ),
        ),
      ),
    );
  }

  // 4. COLLAPSIBLE FORECAST SECTION
  Widget _buildForecastSection() {
    return Container(
      decoration: _cardDecoration(),
      child: Column(
        children: [
          InkWell(
            borderRadius: BorderRadius.circular(16),
            onTap: () {
              setState(() => _forecastExpanded = !_forecastExpanded);
            },
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Row(
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
                  const Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          '30-Day Emissions Forecast',
                          style: TextStyle(
                            fontSize: 15,
                            fontWeight: FontWeight.bold,
                            color: Color(0xFF1F2933),
                          ),
                        ),
                        SizedBox(height: 2),
                        Text(
                          'Tap to see your projected carbon footprint.',
                          style: TextStyle(fontSize: 11, color: Colors.black54),
                        ),
                      ],
                    ),
                  ),
                  AnimatedRotation(
                    duration: const Duration(milliseconds: 180),
                    turns: _forecastExpanded ? 0.5 : 0,
                    child: const Icon(
                      Icons.keyboard_arrow_down_rounded,
                      color: Colors.black54,
                    ),
                  ),
                ],
              ),
            ),
          ),
          AnimatedCrossFade(
            firstChild: const SizedBox(width: double.infinity),
            secondChild: Padding(
              padding: const EdgeInsets.fromLTRB(16, 0, 16, 20),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text(
                    'Projected carbon footprint for the upcoming month. '
                    'Swipe horizontally to see all 30 days.',
                    style: TextStyle(
                      fontSize: 12,
                      color: Colors.black54,
                      height: 1.4,
                    ),
                  ),
                  const SizedBox(height: 20),
                  SizedBox(height: 420, child: _buildForecastContent()),
                ],
              ),
            ),
            crossFadeState: _forecastExpanded
                ? CrossFadeState.showSecond
                : CrossFadeState.showFirst,
            duration: const Duration(milliseconds: 220),
          ),
        ],
      ),
    );
  }

  // HELPERS
  BoxDecoration _cardDecoration() {
    return BoxDecoration(
      color: Colors.white,
      borderRadius: BorderRadius.circular(16),
      boxShadow: [
        BoxShadow(
          color: Colors.black.withValues(alpha: 0.04),
          blurRadius: 6,
          offset: const Offset(0, 3),
        ),
      ],
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

  Widget _buildForecastSummary() {
    if (tftForecastSpots.isEmpty) return const SizedBox.shrink();

    double peak = tftForecastSpots.first.y;
    int peakIndex = 0;
    double total = 0;

    for (var i = 0; i < tftForecastSpots.length; i++) {
      final y = tftForecastSpots[i].y;
      total += y;
      if (y > peak) {
        peak = y;
        peakIndex = i;
      }
    }

    final average = total / tftForecastSpots.length;
    final peakDate = peakIndex < tftForecastLabels.length
        ? tftForecastLabels[peakIndex]
        : '';

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
      decoration: BoxDecoration(
        color: const Color(0xFFF5F9F6),
        borderRadius: BorderRadius.circular(10),
      ),
      child: Row(
        children: [
          Expanded(
            child: _buildForecastMiniStat(
              label: 'Daily Average',
              value: '${average.toStringAsFixed(2)} kg',
              icon: Icons.show_chart_rounded,
            ),
          ),
          Container(width: 1, height: 32, color: const Color(0xFFE5EEE8)),
          Expanded(
            child: _buildForecastMiniStat(
              label: 'Peak Day ($peakDate)',
              value: '${peak.toStringAsFixed(2)} kg',
              icon: Icons.trending_up_rounded,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildForecastMiniStat({
    required String label,
    required String value,
    required IconData icon,
  }) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 8),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Icon(icon, size: 13, color: primaryGreen),
              const SizedBox(width: 4),
              Text(
                label,
                style: const TextStyle(
                  fontSize: 10,
                  color: Colors.black54,
                  fontWeight: FontWeight.w500,
                ),
              ),
            ],
          ),
          const SizedBox(height: 4),
          Text(
            value,
            style: const TextStyle(
              fontSize: 14,
              fontWeight: FontWeight.bold,
              color: darkGreen,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildForecastContent() {
    if (isTftLoading) {
      return const Center(
        child: CircularProgressIndicator(color: primaryGreen),
      );
    }
    if (tftForecastStatus == 'no_data') return _buildTftEmptyState();
    if (tftForecastStatus == 'insufficient_data') {
      return _buildTftInsufficientDataState();
    }
    if (tftForecastStatus == 'error') return _buildTftErrorState();
    if (tftForecastSpots.isEmpty) {
      return const Center(
        child: Text(
          'No forecast available.',
          style: TextStyle(color: Colors.black54, fontSize: 13),
        ),
      );
    }

    double maxY = 0;
    double minY = double.infinity;
    for (final spot in tftForecastSpots) {
      if (spot.y > maxY) maxY = spot.y;
      if (spot.y < minY) minY = spot.y;
    }
    if (minY == double.infinity) minY = 0;
    if (minY > 0) minY = 0;

    maxY = maxY <= 0 ? 5 : (maxY * 1.35).ceilToDouble();

    final yInterval = maxY <= 5
        ? 1.0
        : maxY <= 10
        ? 2.0
        : maxY <= 25
        ? 5.0
        : maxY <= 50
        ? 10.0
        : (maxY / 5).ceilToDouble();

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Expanded(
          child: SingleChildScrollView(
            scrollDirection: Axis.horizontal,
            physics: const BouncingScrollPhysics(),
            padding: const EdgeInsets.only(top: 8, bottom: 4),
            child: SizedBox(
              width: tftForecastSpots.length * 55.0,
              child: LineChart(
                LineChartData(
                  minX: 0,
                  maxX: tftForecastSpots.length > 1
                      ? (tftForecastSpots.length - 1).toDouble()
                      : 1,
                  minY: minY,
                  maxY: maxY,

                  clipData: const FlClipData.all(),

                  gridData: FlGridData(
                    show: true,
                    drawVerticalLine: false,
                    horizontalInterval: yInterval,
                    getDrawingHorizontalLine: (value) {
                      return FlLine(
                        color: Colors.black.withValues(alpha: 0.06),
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
                        reservedSize: 42,
                        interval: yInterval,
                        getTitlesWidget: (value, meta) {
                          if (value == 0) return const SizedBox();
                          return Padding(
                            padding: const EdgeInsets.only(right: 6),
                            child: Text(
                              value.toStringAsFixed(value < 10 ? 1 : 0),
                              style: const TextStyle(
                                fontSize: 10,
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
                        reservedSize: 40,
                        interval: 2,
                        getTitlesWidget: (value, meta) {
                          final index = value.toInt();
                          if (index < 0 || index >= tftForecastLabels.length) {
                            return const SizedBox();
                          }
                          return Padding(
                            padding: const EdgeInsets.only(top: 10),
                            child: Text(
                              tftForecastLabels[index],
                              style: const TextStyle(
                                fontSize: 10,
                                color: Colors.black54,
                                fontWeight: FontWeight.w500,
                              ),
                            ),
                          );
                        },
                      ),
                    ),
                  ),

                  // TOOLTIP — kept inside chart bounds
                  lineTouchData: LineTouchData(
                    enabled: true,
                    handleBuiltInTouches: true,
                    touchCallback: (event, response) {
                      // When the user taps or drags on the chart:
                      if (response?.lineBarSpots != null &&
                          response!.lineBarSpots!.isNotEmpty) {
                        final index = response.lineBarSpots!.first.spotIndex;
                        if (_selectedForecastIndex != index) {
                          setState(() => _selectedForecastIndex = index);
                        }
                      } else if (event is FlTapUpEvent) {
                        // Tap outside any dot clears selection
                        setState(() => _selectedForecastIndex = null);
                      }
                    },
                    getTouchedSpotIndicator: (barData, spotIndexes) {
                      return spotIndexes.map((index) {
                        return TouchedSpotIndicatorData(
                          FlLine(
                            color: primaryGreen.withValues(alpha: 0.4),
                            strokeWidth: 1.5,
                            dashArray: [4, 4],
                          ),
                          FlDotData(
                            getDotPainter: (spot, _, __, ___) =>
                                FlDotCirclePainter(
                                  radius: 6,
                                  color: primaryGreen,
                                  strokeWidth: 2,
                                  strokeColor: Colors.white,
                                ),
                          ),
                        );
                      }).toList();
                    },
                    touchTooltipData: LineTouchTooltipData(
                      getTooltipColor: (_) => Colors.transparent,
                      getTooltipItems: (touchedSpots) {
                        return touchedSpots.map((_) {
                          return LineTooltipItem(
                            '',
                            const TextStyle(fontSize: 0),
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
                        getDotPainter: (spot, percent, barData, index) {
                          final isSelected = index == _selectedForecastIndex;
                          return FlDotCirclePainter(
                            radius: isSelected ? 6 : 4,
                            color: isSelected ? primaryGreen : Colors.white,
                            strokeWidth: 2,
                            strokeColor: primaryGreen,
                          );
                        },
                      ),
                      belowBarData: BarAreaData(
                        show: true,
                        gradient: LinearGradient(
                          begin: Alignment.topCenter,
                          end: Alignment.bottomCenter,
                          colors: [
                            primaryGreen.withValues(alpha: 0.22),
                            primaryGreen.withValues(alpha: 0.02),
                          ],
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ),
        ),

        if (_selectedForecastIndex != null &&
            _selectedForecastIndex! >= 0 &&
            _selectedForecastIndex! < tftForecastSpots.length) ...[
          const SizedBox(height: 12),
          _buildSelectedDayBanner(),
        ],

        const SizedBox(height: 14),
        _buildForecastSummary(),
      ],
    );
  }

  Widget _buildSelectedDayBanner() {
    final i = _selectedForecastIndex!;
    final spot = tftForecastSpots[i];
    final date = i < tftForecastLabels.length ? tftForecastLabels[i] : '';

    return Container(
      width: double.infinity,
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
      decoration: BoxDecoration(
        color: const Color(0xFFEAF6EE),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: primaryGreen.withValues(alpha: 0.35)),
      ),
      child: Row(
        children: [
          Container(
            padding: const EdgeInsets.all(8),
            decoration: const BoxDecoration(
              color: Colors.white,
              shape: BoxShape.circle,
            ),
            child: const Icon(
              Icons.calendar_today_rounded,
              size: 16,
              color: primaryGreen,
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  date,
                  style: const TextStyle(
                    fontSize: 12,
                    color: Colors.black54,
                    fontWeight: FontWeight.w500,
                  ),
                ),
                const SizedBox(height: 2),
                Text(
                  '${spot.y.toStringAsFixed(2)} kg CO₂e predicted',
                  style: const TextStyle(
                    fontSize: 15,
                    fontWeight: FontWeight.bold,
                    color: darkGreen,
                  ),
                ),
              ],
            ),
          ),
          IconButton(
            splashRadius: 18,
            icon: const Icon(
              Icons.close_rounded,
              size: 18,
              color: Colors.black45,
            ),
            onPressed: () {
              setState(() => _selectedForecastIndex = null);
            },
          ),
        ],
      ),
    );
  }
}
