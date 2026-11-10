import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:carbonwise_app/services/api_service.dart';
import 'package:carbonwise_app/utils/strategy_notifier.dart';
import 'package:carbonwise_app/services/gemini_service.dart';
import 'package:carbonwise_app/utils/dialog_helper.dart'; // 👈 Added
import 'package:flutter_markdown/flutter_markdown.dart';

class StrategiesScreen extends StatefulWidget {
  const StrategiesScreen({super.key});

  @override
  State<StrategiesScreen> createState() => _StrategiesScreenState();
}

class _StrategiesScreenState extends State<StrategiesScreen> {
  final ApiService _apiService = ApiService();

  String _aiRecommendation =
      "Loading your personalized sustainability recommendation...";
  String _highestCategory = "";
  static const Color primaryGreen = Color(0xFF3AA76D);

  List<Map<String, dynamic>> _recommendedStrategies = [];

  // CACHE
  static String? _cachedRecommendation;
  static String? _cachedCategory;
  static List<Map<String, dynamic>>? _cachedStrategies;
  static DateTime? _cachedAt;
  static const Duration _cacheDuration = Duration(minutes: 15);
  static DateTime? _lastForceRefreshAt;

  bool _isLoadingRecommendation = false;

  @override
  void initState() {
    super.initState();
    _loadRecommendation();
    strategyRefreshNotifier.addListener(_forceRefreshRecommendation);
  }

  void _forceRefreshRecommendation() {
    _loadRecommendation(forceRefresh: true);
  }

  @override
  void dispose() {
    strategyRefreshNotifier.removeListener(_forceRefreshRecommendation);
    super.dispose();
  }

  // CACHE HELPERS
  bool get _cacheIsValid {
    if (_cachedRecommendation == null || _cachedAt == null) return false;
    return DateTime.now().difference(_cachedAt!) < _cacheDuration;
  }

  void _writeCache(
    String recommendation,
    String category,
    List<Map<String, dynamic>> strategies,
  ) {
    _cachedRecommendation = recommendation;
    _cachedCategory = category;
    _cachedStrategies = strategies;
    _cachedAt = DateTime.now();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF4F6F4),
      body: SafeArea(
        child: RefreshIndicator(
          color: primaryGreen,
          onRefresh: () async {
            await _loadRecommendation(forceRefresh: true);
          },
          child: ListView(
            physics: const BouncingScrollPhysics(),
            padding: const EdgeInsets.fromLTRB(16, 12, 16, 24),
            children: [
              const Text(
                "Your Strategies",
                style: TextStyle(
                  fontSize: 24,
                  fontWeight: FontWeight.bold,
                  color: Color(0xFF1F2933),
                ),
              ),
              const SizedBox(height: 4),
              const Text(
                "Personalized tips to help reduce your carbon footprint.",
                style: TextStyle(fontSize: 13, color: Colors.black54),
              ),
              const SizedBox(height: 20),

              // AI COACH CARD
              Container(
                width: double.infinity,
                padding: const EdgeInsets.all(18),
                decoration: BoxDecoration(
                  color: const Color(0xFFEFFAF2),
                  borderRadius: BorderRadius.circular(20),
                  border: Border.all(color: const Color(0xFFCFE9D8)),
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        Container(
                          width: 42,
                          height: 42,
                          decoration: const BoxDecoration(
                            color: primaryGreen,
                            shape: BoxShape.circle,
                          ),
                          child: const Icon(
                            Icons.auto_awesome,
                            color: Colors.white,
                            size: 21,
                          ),
                        ),
                        const SizedBox(width: 12),
                        const Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                "AI Sustainability Coach",
                                style: TextStyle(
                                  fontSize: 16,
                                  fontWeight: FontWeight.bold,
                                  color: Color(0xFF1F2933),
                                ),
                              ),
                              SizedBox(height: 2),
                              Text(
                                "Personalized insights based on your latest activity",
                                style: TextStyle(
                                  fontSize: 11,
                                  color: Colors.black54,
                                ),
                              ),
                            ],
                          ),
                        ),
                        if (!_isLoadingRecommendation)
                          IconButton(
                            splashRadius: 20,
                            tooltip: 'Refresh recommendation',
                            icon: const Icon(
                              Icons.refresh_rounded,
                              color: primaryGreen,
                              size: 20,
                            ),
                            onPressed: () =>
                                _loadRecommendation(forceRefresh: true),
                          ),
                      ],
                    ),
                    const SizedBox(height: 18),

                    if (_highestCategory.isNotEmpty)
                      Container(
                        width: double.infinity,
                        padding: const EdgeInsets.all(12),
                        decoration: BoxDecoration(
                          color: Colors.white.withValues(alpha: 0.75),
                          borderRadius: BorderRadius.circular(14),
                        ),
                        child: Row(
                          children: [
                            const Icon(
                              Icons.track_changes_outlined,
                              color: primaryGreen,
                              size: 20,
                            ),
                            const SizedBox(width: 10),
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  const Text(
                                    "Recommended Focus",
                                    style: TextStyle(
                                      fontSize: 10,
                                      color: Colors.black54,
                                    ),
                                  ),
                                  const SizedBox(height: 2),
                                  Text(
                                    _highestCategory,
                                    style: const TextStyle(
                                      fontSize: 14,
                                      fontWeight: FontWeight.bold,
                                      color: Color(0xFF265D3B),
                                    ),
                                  ),
                                ],
                              ),
                            ),
                          ],
                        ),
                      ),
                    if (_highestCategory.isNotEmpty) const SizedBox(height: 20),

                    const Text(
                      "Your Personalized Recommendation",
                      style: TextStyle(
                        fontSize: 14,
                        fontWeight: FontWeight.bold,
                        color: Color(0xFF1F2933),
                      ),
                    ),
                    const SizedBox(height: 10),

                    if (_isLoadingRecommendation)
                      const Padding(
                        padding: EdgeInsets.symmetric(vertical: 24),
                        child: Center(
                          child: SizedBox(
                            width: 26,
                            height: 26,
                            child: CircularProgressIndicator(
                              color: primaryGreen,
                              strokeWidth: 2.5,
                            ),
                          ),
                        ),
                      )
                    else
                      MarkdownBody(
                        data: _aiRecommendation,
                        selectable: true,
                        styleSheet: MarkdownStyleSheet(
                          p: const TextStyle(
                            fontSize: 13,
                            height: 1.5,
                            color: Colors.black87,
                          ),
                          strong: const TextStyle(
                            fontWeight: FontWeight.bold,
                            color: Color(0xFF265D3B),
                          ),
                          listBullet: const TextStyle(
                            color: primaryGreen,
                            fontSize: 13,
                          ),
                        ),
                      ),
                  ],
                ),
              ),

              const SizedBox(height: 20),

              if (_recommendedStrategies.isNotEmpty) ...[
                const Text(
                  "Recommended Strategies",
                  style: TextStyle(
                    fontSize: 17,
                    fontWeight: FontWeight.bold,
                    color: Color(0xFF1F2933),
                  ),
                ),
                const SizedBox(height: 10),
                ..._recommendedStrategies.map(
                  (strategy) => Padding(
                    padding: const EdgeInsets.only(bottom: 10),
                    child: _buildStrategyCard(strategy),
                  ),
                ),
              ],
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildStrategyCard(Map<String, dynamic> strategy) {
    final isPriority = strategy['priority'] == true;
    final iconStr = strategy['icon']?.toString() ?? '⚡';
    final category = strategy['category']?.toString() ?? '';
    final freq = strategy['frequency']?.toString() ?? '';
    final impact = strategy['impact']?.toString() ?? '';
    final unit = strategy['unit']?.toString() ?? '';

    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: isPriority
            ? Border.all(color: primaryGreen.withValues(alpha: 0.5), width: 1.5)
            : null,
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.04),
            blurRadius: 8,
            offset: const Offset(0, 3),
          ),
        ],
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            width: 38,
            height: 38,
            decoration: BoxDecoration(
              color: const Color(0xFFEAF6EE),
              borderRadius: BorderRadius.circular(12),
            ),
            child: Center(
              child: Text(iconStr, style: const TextStyle(fontSize: 20)),
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Expanded(
                      child: Text(
                        strategy['title']?.toString() ?? '',
                        style: const TextStyle(
                          fontSize: 14,
                          fontWeight: FontWeight.bold,
                          color: Color(0xFF1F2933),
                        ),
                      ),
                    ),
                    if (isPriority)
                      Container(
                        padding: const EdgeInsets.symmetric(
                          horizontal: 6,
                          vertical: 2,
                        ),
                        decoration: BoxDecoration(
                          color: Colors.redAccent.withValues(alpha: 0.1),
                          borderRadius: BorderRadius.circular(4),
                        ),
                        child: const Text(
                          "Priority",
                          style: TextStyle(
                            fontSize: 9,
                            color: Colors.redAccent,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                      ),
                  ],
                ),
                const SizedBox(height: 4),
                Text(
                  strategy['description']?.toString() ?? '',
                  style: const TextStyle(
                    fontSize: 12,
                    height: 1.4,
                    color: Colors.black87,
                  ),
                ),
                const SizedBox(height: 10),
                Wrap(
                  spacing: 6,
                  runSpacing: 6,
                  children: [
                    if (category.isNotEmpty)
                      _buildPill(
                        category,
                        const Color(0xFFF0FDF4),
                        const Color(0xFF166534),
                      ),
                    if (freq.isNotEmpty)
                      _buildPill(
                        freq,
                        const Color(0xFFEFF6FF),
                        const Color(0xFF1E3A8A),
                      ),
                    if (impact.isNotEmpty)
                      _buildPill(
                        "$impact $unit",
                        const Color(0xFFFFF7ED),
                        const Color(0xFF9A3412),
                      ),
                  ],
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildPill(String text, Color bgColor, Color textColor) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
      decoration: BoxDecoration(
        color: bgColor,
        borderRadius: BorderRadius.circular(20),
      ),
      child: Text(
        text,
        style: TextStyle(
          fontSize: 10,
          fontWeight: FontWeight.w600,
          color: textColor,
        ),
      ),
    );
  }

  // RECOMMENDATION LOADING
  Future<void> _loadRecommendation({bool forceRefresh = false}) async {
    if (!forceRefresh && _cacheIsValid) {
      if (mounted) {
        setState(() {
          _aiRecommendation = _cachedRecommendation!;
          _highestCategory = _cachedCategory ?? '';
          _recommendedStrategies = _cachedStrategies ?? [];
          _isLoadingRecommendation = false;
        });
      }
      return;
    }

    if (forceRefresh &&
        _lastForceRefreshAt != null &&
        DateTime.now().difference(_lastForceRefreshAt!) <
            const Duration(seconds: 20)) {
      if (_cachedRecommendation != null && mounted) {
        setState(() {
          _aiRecommendation = _cachedRecommendation!;
          _highestCategory = _cachedCategory ?? '';
          _recommendedStrategies = _cachedStrategies ?? [];
          _isLoadingRecommendation = false;
        });

        DialogHelper.showWarning(
          context: context,
          title: "Please Wait",
          message: "Please wait a moment before refreshing again.",
        );
        return;
      }
    }

    if (forceRefresh) _lastForceRefreshAt = DateTime.now();

    if (mounted) setState(() => _isLoadingRecommendation = true);

    try {
      final email = await ApiService.getCurrentUserEmail();

      if (email == null || email.isEmpty) {
        _setMessage(
          "Please log in and record an activity first so your AI coach can personalize your recommendations.",
        );
        return;
      }

      final records = await _apiService.getCarbonRecords(email);

      if (records.isEmpty) {
        _setMessage(
          "Record an activity first and I'll give you personalized sustainability advice based on your emissions.",
        );
        return;
      }

      double transportation = 0;
      double electricity = 0;
      double food = 0;

      for (final record in records) {
        transportation +=
            double.tryParse(record['transportation']?.toString() ?? '0') ?? 0.0;
        electricity +=
            double.tryParse(record['electricity']?.toString() ?? '0') ?? 0.0;
        food += double.tryParse(record['food']?.toString() ?? '0') ?? 0.0;
      }

      String category;
      if (transportation >= electricity && transportation >= food) {
        category = "Transport";
      } else if (electricity >= food) {
        category = "Office Resource";
      } else {
        category = "Food Consumption";
      }

      final gemini = GeminiService();
      String? aiRecommendation;

      try {
        aiRecommendation = await _generateWithRetry(
          call: () => gemini.generateStrategies(
            transportation: transportation,
            electricity: electricity,
            food: food,
          ),
        );
      } catch (e) {
        if (!mounted) return;
        _showError(e);
        return;
      }

      if (!mounted) return;

      _highestCategory = category;

      try {
        final cleanJson = aiRecommendation
            .replaceAll(RegExp(r'```json\s*|\s*```'), '')
            .trim();
        final Map<String, dynamic> json = _parseJson(cleanJson);

        final insight =
            json['insight_summary']?.toString() ??
            "Here are your recommendations.";
        final rawStrategies = json['strategies'] as List? ?? [];

        final List<Map<String, dynamic>> parsedStrategies = [];
        for (var s in rawStrategies) {
          if (s is Map) parsedStrategies.add(Map<String, dynamic>.from(s));
        }

        _writeCache(insight, category, parsedStrategies);
        setState(() {
          _aiRecommendation = insight;
          _recommendedStrategies = parsedStrategies;
        });

        if (forceRefresh && mounted) {
          DialogHelper.showSuccess(
            context: context,
            title: "Strategies Updated",
            message:
                "Your personalized AI strategies have been refreshed successfully.",
          );
        }
      } catch (parseError) {
        _showError("The AI returned an unexpected format. Please try again.");
      }
    } catch (e) {
      if (!mounted) return;
      _showError(e);
    } finally {
      if (mounted) setState(() => _isLoadingRecommendation = false);
    }
  }

  Map<String, dynamic> _parseJson(String text) {
    final start = text.indexOf('{');
    final end = text.lastIndexOf('}');
    if (start != -1 && end != -1) {
      return jsonDecode(text.substring(start, end + 1));
    }
    return jsonDecode(text);
  }

  Future<String> _generateWithRetry({
    required Future<String> Function() call,
    int maxAttempts = 3,
  }) async {
    Duration delay = const Duration(seconds: 2);
    for (int attempt = 1; attempt <= maxAttempts; attempt++) {
      try {
        return await call();
      } catch (e) {
        final message = e.toString().toLowerCase();
        final isTransient =
            message.contains('503') ||
            message.contains('unavailable') ||
            message.contains('high demand') ||
            message.contains('overloaded') ||
            message.contains('429') ||
            message.contains('resource_exhausted');
        if (!isTransient || attempt == maxAttempts) rethrow;
        await Future.delayed(delay);
        delay *= 2;
      }
    }
    throw Exception("AI service unavailable after $maxAttempts attempts.");
  }

  void _showError(Object e) {
    if (!mounted) return;

    String userMessage;
    final message = e.toString().toLowerCase();

    if (message.contains('503') || message.contains('unavailable')) {
      userMessage =
          "Our AI coach is currently experiencing high demand. Please try again later.";
    } else if (message.contains('429')) {
      userMessage =
          "You've reached today's AI request limit. Please try again later.";
    } else {
      userMessage =
          "Unable to generate recommendations right now. Please try again.";
    }

    if (_cachedRecommendation != null && _cachedRecommendation!.isNotEmpty) {
      setState(() {
        _aiRecommendation =
            "Showing your last recommendation — we couldn't reach the AI coach just now.\n\n$_cachedRecommendation";
        _highestCategory = _cachedCategory ?? '';
        _recommendedStrategies = _cachedStrategies ?? [];
      });

      DialogHelper.showWarning(
        context: context,
        title: "AI Service Busy",
        message: userMessage,
      );
      return;
    }

    setState(() {
      _aiRecommendation = userMessage;
      _recommendedStrategies = [];
      _highestCategory = '';
    });

    DialogHelper.showError(
      context: context,
      title: "Recommendation Failed",
      message: userMessage,
    );
  }

  void _setMessage(String message) {
    if (!mounted) return;
    setState(() {
      _aiRecommendation = message;
      _recommendedStrategies = [];
      _highestCategory = '';
      _isLoadingRecommendation = false;
    });
  }
}
