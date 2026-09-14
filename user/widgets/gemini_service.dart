import 'dart:convert';
import 'package:http/http.dart' as http;
import '../utils/api_constants.dart';

class GeminiService {
  final String _baseUrl =
      "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent";

  /// Generates structured sustainability mitigation strategies based on logged emission data.
  Future<String> generateStrategies({
    required double transportation,
    required double electricity,
    required double food,
  }) async {
    final url = Uri.parse("$_baseUrl?key=${ApiConstants.geminiApiKey}");
    final double total = transportation + electricity + food;

    final prompt = """
You are an expert environmental coach for CarbonWise.
Analyze these user carbon footprint metrics:
- Transportation: $transportation kg CO2
- Electricity: $electricity kg CO2
- Food: $food kg CO2
- Combined Total: $total kg CO2

Provide customized, encouraging, and actionable recommendations focusing on their highest emission category. 
Keep the answer under 150 words using bullet points and bold text for key actions.
""";

    try {
      final response = await http.post(
        url,
        headers: {"Content-Type": "application/json"},
        body: jsonEncode({
          "contents": [
            {
              "parts": [
                {"text": prompt}
              ]
            }
          ]
        }),
      );

      if (response.statusCode == 200) {
        final json = jsonDecode(response.body);
        return json["candidates"][0]["content"]["parts"][0]["text"];
      } else {
        return "Failed to load recommendation. (Error ${response.statusCode})";
      }
    } catch (e) {
      return "Unable to connect to the recommendation engine.";
    }
  }

  /// Answers basic conversational sustainability queries.
  Future<String> askGemini(String question) async {
    final url = Uri.parse("$_baseUrl?key=${ApiConstants.geminiApiKey}");

    try {
      final response = await http.post(
        url,
        headers: {
          "Content-Type": "application/json",
        },
        body: jsonEncode({
          "contents": [
            {
              "parts": [
                {
                  "text": """
You are CarbonWise AI.

Answer only questions about:
- Sustainability
- Carbon emissions
- Transportation
- Electricity
- Food
- Recycling

Keep answers friendly and under 100 words.

Use Markdown formatting when helpful:
- **bold** for important tips
- numbered lists for steps
- bullet points when appropriate

Do not use tables.

Use emojis naturally to make answers easier to read.
Don't overuse them.

If the question is unrelated, reply exactly with:

"I'm CarbonWise AI. I only answer sustainability-related questions."

Conversation so far:

$question

Continue the conversation naturally.
""",
                },
              ],
            },
          ],
        }),
      );

      if (response.statusCode == 200) {
        final json = jsonDecode(response.body);
        return json["candidates"][0]["content"]["parts"][0]["text"];
      }

      return "Error ${response.statusCode}\n${response.body}";
    } catch (e) {
      return "Unable to connect to CarbonWise AI.";
    }
  }

  /// Answers conversational sustainability queries with user footprint context.
  Future<String> askGeminiWithContext(
    String question, {
    double? transportation,
    double? electricity,
    double? food,
    double? totalEmission,
  }) async {
    final url = Uri.parse("$_baseUrl?key=${ApiConstants.geminiApiKey}");

    String carbonContext = "";
    if (totalEmission != null) {
      carbonContext = """
User's Current Footprint Context:
- Grand Total: $totalEmission kg CO₂
- Transportation: ${transportation ?? 0} kg CO₂
- Electricity: ${electricity ?? 0} kg CO₂
- Food: ${food ?? 0} kg CO₂
Use this real data if they ask about their footprint, progress, or how they are doing. Keep advice supportive!
""";
    }

    try {
      final response = await http.post(
        url,
        headers: {
          "Content-Type": "application/json",
        },
        body: jsonEncode({
          "contents": [
            {
              "parts": [
                {
                  "text": """
You are CarbonWise AI, a helpful, friendly, and expert sustainability coach.

Answer questions about:
- Sustainability and eco-friendly tips
- Carbon emissions
- Transportation, Electricity, Food, Recycling

$carbonContext

Keep answers conversational, positive, and under 100 words.

Formatting rules:
- **bold** for key actions
- bullet points or numbered lists for steps
- No markdown tables
- Use emojis naturally but sparingly

If the user's prompt is completely unrelated to sustainability, reply with:
"I'm CarbonWise AI. I only answer sustainability-related questions."

Conversation history:
$question

Provide your next response naturally:
""",
                },
              ],
            },
          ],
        }),
      );

      if (response.statusCode == 200) {
        final json = jsonDecode(response.body);
        return json["candidates"][0]["content"]["parts"][0]["text"];
      }

      return "Error ${response.statusCode}\n${response.body}";
    } catch (e) {
      return "Unable to connect to CarbonWise AI.";
    }
  }
}