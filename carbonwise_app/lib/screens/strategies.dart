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
  // Filter States
  double _transportation = 0;
  double _electricity = 0;
  double _food = 0;

  String _aiRecommendation =
      "Loading your personalized sustainability recommendation...";

  String _highestCategory = "";

  static const Color primaryGreen = Color(0xFF3AA76D);
  static const Color darkGreen = Color(0xFF1E5631);
  static const Color badgeGrey = Color(0xFFCCEAD8);

  @override
  void initState() {
    super.initState();
    _loadRecommendation();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Color(0xFFF4F6F4),
      body: Column(
        children: [
          // Main Content Area Container Box
          Expanded(
            child: SafeArea(
              child: Container(
                margin: const EdgeInsets.all(16.0),
                padding: const EdgeInsets.all(12.0),
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(4),
                ),
                child: ListView(
                  children: [
                    Container(
                      width: double.infinity,
                      padding: const EdgeInsets.all(16),
                      decoration: BoxDecoration(
                        color: const Color(0xFFEFFAF2),
                        borderRadius: BorderRadius.circular(16),
                        border: Border.all(color: Colors.green.shade300),
                      ),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Row(
                            children: [
                              Icon(Icons.auto_awesome, color: primaryGreen),
                              SizedBox(width: 8),
                              Text(
                                "AI Sustainability Coach",
                                style: TextStyle(
                                  fontWeight: FontWeight.bold,
                                  fontSize: 17,
                                ),
                              ),
                            ],
                          ),

                          const SizedBox(height: 12),

                          MarkdownBody(
                            data: _aiRecommendation,
                            selectable: true,
                          ),
                        ],
                      ),
                    ),

                    const SizedBox(height: 20),

                    if (_highestCategory.isNotEmpty)
                      Padding(
                        padding: const EdgeInsets.only(bottom: 10),
                        child: Row(
                          children: [
                            const Icon(Icons.star, color: Colors.amber),
                            const SizedBox(width: 6),
                            Text(
                              "Recommended for you: $_highestCategory",
                              style: const TextStyle(
                                fontWeight: FontWeight.bold,
                                color: Colors.green,
                              ),
                            ),
                          ],
                        ),
                      ),
                  ],
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Future<void> _loadRecommendation() async {
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

        setState(() {
          _aiRecommendation = savedRecommendation;
        });
      } else {
        print("Generating new AI recommendation...");

        final gemini = GeminiService();

        final recommendation = await gemini.generateStrategies(
          transportation: transportation,
          electricity: electricity,
          food: food,
        );

        // Save it to Supabase
        await supabase
            .from('carbon_records')
            .update({'ai_recommendation': recommendation})
            .eq('id', latest['id']);

        setState(() {
          _aiRecommendation = recommendation;
        });
      }
    } catch (e) {
      print("Recommendation Error:");
      print(e);

      setState(() {
        _aiRecommendation =
            "Unable to generate recommendations. Please record your activities first.";
      });
    }

    const SizedBox(height: 20);

    Align(
      alignment: Alignment.centerLeft,
      child: Text(
        "⭐ Recommended Strategies",
        style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
      ),
    );

    const SizedBox(height: 10);

    // --- UI Helpers ---
    Widget _buildFilterDropdown({
      required String value,
      required List<String> items,
      required ValueChanged<String?> onChanged,
    }) {
      return Container(
        height: 36,
        padding: const EdgeInsets.symmetric(horizontal: 4),
        decoration: BoxDecoration(
          border: Border.all(color: Colors.black45, width: 0.8),
          borderRadius: BorderRadius.circular(4),
        ),
        child: DropdownButtonHideUnderline(
          child: DropdownButton<String>(
            value: value,
            isExpanded: true,
            icon: const Icon(
              Icons.arrow_drop_down,
              size: 18,
              color: Colors.black87,
            ),
            style: const TextStyle(fontSize: 10, color: Colors.black87),
            items: items.map((String val) {
              return DropdownMenuItem<String>(
                value: val,
                child: Text(val, overflow: TextOverflow.ellipsis),
              );
            }).toList(),
            onChanged: onChanged,
          ),
        ),
      );
    }

    Widget _buildPaginationButton(IconData icon, VoidCallback onPressed) {
      return IconButton(
        icon: Icon(icon, size: 18),
        onPressed: onPressed,
        visualDensity: VisualDensity.compact,
      );
    }

    Widget _buildPaginationPageNum(String pageNum, bool isActive) {
      return Padding(
        padding: const EdgeInsets.symmetric(horizontal: 4.0),
        child: Text(
          pageNum,
          style: TextStyle(
            fontSize: 11,
            fontWeight: isActive ? FontWeight.bold : FontWeight.normal,
            color: isActive ? darkGreen : Colors.black54,
          ),
        ),
      );
    }
  }
}
