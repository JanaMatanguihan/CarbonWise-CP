import 'package:flutter/material.dart';
import 'package:carbonwise_app/services/api_service.dart';
import 'package:carbonwise_app/utils/strategy_notifier.dart';
import 'package:carbonwise_app/services/gemini_service.dart';
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

  List<String> _recommendedStrategies = [];

  // CACHE
  static String? _cachedRecommendation;
  static String? _cachedCategory;
  static List<String>? _cachedStrategies;
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
    List<String> strategies,
  ) {
    _cachedRecommendation = recommendation;
    _cachedCategory = category;
    _cachedStrategies = strategies;
    _cachedAt = DateTime.now();
  }

  // static void clearCache() {
  //   _cachedRecommendation = null;
  //   _cachedCategory = null;
  //   _cachedStrategies = null;
  //   _cachedAt = null;
  //   _lastForceRefreshAt = null;
  // }

  // BUILD
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
              // PAGE TITLE
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
                    // Header
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

                        // Manual refresh button
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

                    // Recommended category
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

              // =========================
              // QUICK TIP SECTION
              // =========================
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

  Widget _buildStrategyCard(String strategy) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
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
            child: const Icon(
              Icons.eco_outlined,
              color: primaryGreen,
              size: 20,
            ),
          ),

          const SizedBox(width: 12),

          Expanded(
            child: Text(
              strategy,
              style: const TextStyle(
                fontSize: 12,
                height: 1.4,
                color: Color(0xFF1F2933),
              ),
            ),
          ),
        ],
      ),
    );
  }

  // RECOMMENDATION LOADING
  Future<void> _loadRecommendation({bool forceRefresh = false}) async {
    // 1. Serve from cache if valid and not forced.
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

    // 2. Guard against spamming Gemini after a 503
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
        _showSnack("Please wait a moment before refreshing again.");
        return;
      }
    }

    if (forceRefresh) {
      _lastForceRefreshAt = DateTime.now();
    }

    // 3. Show loading state.
    if (mounted) {
      setState(() {
        _isLoadingRecommendation = true;
      });
    }

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

      // Aggregate the user's recorded activity.
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

      // Determine highest category.
      String category;
      if (transportation >= electricity && transportation >= food) {
        category = "Transport";
      } else if (electricity >= food) {
        category = "Office Resource";
      } else {
        category = "Food Consumption";
      }

      // Call Gemini with retry + backoff.
      final gemini = GeminiService();
      final recommendation = await _generateWithRetry(
        call: () => gemini.generateStrategies(
          transportation: transportation,
          electricity: electricity,
          food: food,
        ),
      );

      if (!mounted) return;

      _highestCategory = category;
      _parseRecommendation(recommendation);
    } catch (e) {
      if (!mounted) return;
      _showError(e);
    } finally {
      if (mounted) {
        setState(() => _isLoadingRecommendation = false);
      }
    }
  }

  // RETRY WITH BACKOFF (handles 503 / 429 from Gemini)
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

  // ERROR + FALLBACK HANDLING
  void _showError(Object e) {
    final message = e.toString();

    String userMessage;
    if (message.contains('503') || message.contains('UNAVAILABLE')) {
      userMessage =
          "Our AI coach is briefly overloaded. Please pull down to refresh in a moment.";
    } else if (message.contains('429') ||
        message.contains('RESOURCE_EXHAUSTED')) {
      userMessage =
          "You've reached today's AI request limit. Please try again later.";
    } else if (message.contains('401') ||
        message.contains('API key') ||
        message.contains('permission')) {
      userMessage =
          "The AI service is temporarily unavailable. Please try again later.";
    } else if (message.contains('SocketException') ||
        message.contains('TimeoutException') ||
        message.contains('Failed host lookup')) {
      userMessage =
          "Couldn't reach the AI service. Please check your internet and try again.";
    } else {
      userMessage =
          "Unable to generate recommendations right now. Please try again.";
    }

    if (_cachedRecommendation != null && _cachedRecommendation!.isNotEmpty) {
      setState(() {
        _aiRecommendation =
            "Showing your last recommendation — we couldn't reach the AI coach just now.\n\n"
            "$_cachedRecommendation";
        _highestCategory = _cachedCategory ?? '';
        _recommendedStrategies = _cachedStrategies ?? [];
      });
      _showSnack(userMessage);
      return;
    }

    setState(() {
      _aiRecommendation = userMessage;
      _recommendedStrategies = [];
      _highestCategory = '';
    });
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

  void _showSnack(String message) {
    if (!mounted) return;
    ScaffoldMessenger.of(context)
      ..hideCurrentSnackBar()
      ..showSnackBar(
        SnackBar(
          content: Text(message),
          behavior: SnackBarBehavior.floating,
          duration: const Duration(seconds: 3),
          backgroundColor: const Color(0xFF1F2933),
        ),
      );
  }

  // PARSING
  void _parseRecommendation(String recommendation) {
    final parts = recommendation.split("STRATEGIES:");

    String mainRecommendation = recommendation;
    List<String> strategies = [];

    if (parts.length > 1) {
      mainRecommendation = parts[0].replaceFirst("RECOMMENDATION:", "").trim();

      strategies = parts[1]
          .split("\n")
          .map((item) => item.trim())
          .where((item) => item.isNotEmpty)
          .map((item) => item.replaceFirst(RegExp(r'^[-•\d.]+\s*'), '').trim())
          .where((item) => item.isNotEmpty)
          .toList();
    }

    _writeCache(mainRecommendation, _highestCategory, strategies);

    setState(() {
      _aiRecommendation = mainRecommendation;
      _recommendedStrategies = strategies;
    });
  }
}
