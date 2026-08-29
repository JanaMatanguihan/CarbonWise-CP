import 'package:flutter/material.dart';
import 'package:supabase_flutter/supabase_flutter.dart';
import 'package:carbonwise_app/services/gemini_service.dart';
import 'package:flutter_markdown/flutter_markdown.dart';

class StrategiesScreen extends StatefulWidget {
  const StrategiesScreen({super.key});

  @override
  State<StrategiesScreen> createState() => _StrategiesScreenState();
}

class _StrategiesScreenState extends State<StrategiesScreen> {
  String _aiRecommendation =
      "Loading your personalized sustainability recommendation...";

  String _highestCategory = "";

  static const Color primaryGreen = Color(0xFF3AA76D);

  List<String> _recommendedStrategies = [];

  @override
  void initState() {
    super.initState();
    _loadRecommendation();
    strategyRefreshNotifier.addListener(_refreshRecommendation);
  }

  void _refreshRecommendation() {
    print("Notifier received!");
    _loadRecommendation();
  }

  @override
  void dispose() {
    strategyRefreshNotifier.removeListener(_refreshRecommendation);
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF4F6F4),
      body: SafeArea(
        child: RefreshIndicator(
          color: primaryGreen,
          onRefresh: () async {
            await _loadRecommendation();
          },
          child: ListView(
            physics: const BouncingScrollPhysics(),
            padding: const EdgeInsets.fromLTRB(16, 12, 16, 24),
            children: [
              // =========================
              // PAGE TITLE
              // =========================
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

              // =========================
              // AI COACH CARD
              // =========================
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

  Future<void> _loadRecommendation() async {
    setState(() {
      _aiRecommendation =
          "🤖 Analyzing your emissions and preparing personalized recommendations...";
      _recommendedStrategies = [];
    });

    try {
      final supabase = Supabase.instance.client;
      final user = supabase.auth.currentUser;

      if (user == null) return;

      final latest = await supabase
          .from('carbon_records')
          .select()
          .eq('g_suite', user.email!)
          .order('created_at', ascending: false)
          .limit(1)
          .single();

      final transportation =
          (latest['transportation'] as num?)?.toDouble() ?? 0;

      final electricity = (latest['electricity'] as num?)?.toDouble() ?? 0;

      final food = (latest['food'] as num?)?.toDouble() ?? 0;

      final savedRecommendation = latest['ai_recommendation'];

      if (transportation >= electricity && transportation >= food) {
        _highestCategory = "Transport";
      } else if (electricity >= food) {
        _highestCategory = "Office Resource";
      } else {
        _highestCategory = "Food Consumption";
      }

      if (savedRecommendation != null &&
          savedRecommendation.toString().trim().isNotEmpty) {
        print("Using saved AI recommendation.");

        _parseRecommendation(savedRecommendation.toString());
      } else {
        print("Generating new AI recommendation...");

        final gemini = GeminiService();

        final recommendation = await gemini.generateStrategies(
          transportation: transportation,
          electricity: electricity,
          food: food,
        );

        // Save the COMPLETE AI response to Supabase
        await supabase
            .from('carbon_records')
            .update({'ai_recommendation': recommendation})
            .eq('id', latest['id']);

        // Separate recommendation and strategies
        _parseRecommendation(recommendation);
      }
    } catch (e) {
      print("Recommendation Error:");
      print(e);

      setState(() {
        _aiRecommendation =
            "Unable to generate recommendations. Please record your activities first.";

        _recommendedStrategies = [];
      });
    }
  }

  final ValueNotifier<void> strategyRefreshNotifier = ValueNotifier(null);

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

    setState(() {
      _aiRecommendation = mainRecommendation;
      _recommendedStrategies = strategies;
    });
  }
}
