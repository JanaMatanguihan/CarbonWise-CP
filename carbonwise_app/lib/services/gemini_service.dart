import 'dart:convert';
import 'package:http/http.dart' as http;
import '../utils/api_constants.dart';
import '../utils/smart_suggestion.dart';

class GeminiService {
  Future<String> askGemini(String question) async {
    final url = Uri.parse(
      "https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-latest:generateContent",
    );

    print("Sending request to Gemini...");

    final response = await http.post(
      url,
      headers: {
        "Content-Type": "application/json",
        "X-goog-api-key": ApiConstants.geminiApiKey,
      },
      body: jsonEncode({
        "contents": [
          {
            "parts": [
              {
                "text":
                    """
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

    print("Response received!");
    print(response.statusCode);
    print(response.body);

    if (response.statusCode == 200) {
      final json = jsonDecode(response.body);

      return json["candidates"][0]["content"]["parts"][0]["text"];
    }

    return "Error ${response.statusCode}\n${response.body}";
  }

  Future<String> generateStrategies({
    required double transportation,
    required double electricity,
    required double food,
  }) async {
    final url = Uri.parse(
      "https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-latest:generateContent",
    );

    final prompt =
        """
You are CarbonWise AI, a personalized sustainability assistant.

The user's latest carbon emissions are:

Transportation: $transportation kg CO₂
Electricity: $electricity kg CO₂
Food: $food kg CO₂

Analyze the user's carbon emissions and provide personalized advice.

Respond using EXACTLY this format:

RECOMMENDATION:
Write a short, friendly summary of the user's overall carbon emission pattern.
Identify the highest emission category and explain why it should be the main focus.
Keep this section concise.

STRATEGIES:
- Provide one practical and personalized strategy based on the user's highest emission category.
- Provide another practical strategy.
- Provide a third practical strategy.

Requirements:
- The strategies must be based on the user's actual emission data.
- Make the advice realistic for a student.
- Keep the tone encouraging and friendly.
- Keep the entire response under 180 words.
- Do not use Markdown headings.
- Do not add any text before RECOMMENDATION.
- Do not add any text after the last strategy.
""";

    final response = await http.post(
      url,
      headers: {
        "Content-Type": "application/json",
        "X-goog-api-key": ApiConstants.geminiApiKey,
      },
      body: jsonEncode({
        "contents": [
          {
            "parts": [
              {"text": prompt},
            ],
          },
        ],
      }),
    );

    if (response.statusCode == 200) {
      final json = jsonDecode(response.body);

      return json["candidates"][0]["content"]["parts"][0]["text"];
    }

    print("Status Code: ${response.statusCode}");
    print("Response Body:");
    print(response.body);

    return "Error ${response.statusCode}\n${response.body}";
  }

  Future<List<SmartSuggestion>> generateSmartSuggestions({
    required double transportation,
    required double electricity,
    required double food,
  }) async {
    final url = Uri.parse(
      "https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-latest:generateContent",
    );

    final prompt =
        """
You are CarbonWise AI.

A student's carbon emissions are:

Transportation: $transportation kg CO₂
Electricity: $electricity kg CO₂
Food: $food kg CO₂

For EACH category provide:

Suggestion: one sentence (maximum 15 words)

Impact: one sentence (maximum 8 words)

Return ONLY this format:

Transportation
Suggestion: ...
Impact: ...

Electricity
Suggestion: ...
Impact: ...

Food
Suggestion: ...
Impact: ...

Do not use markdown.
Do not use bullet points.
Do not add explanations.
""";

    final response = await http.post(
      url,
      headers: {
        "Content-Type": "application/json",
        "X-goog-api-key": ApiConstants.geminiApiKey,
      },
      body: jsonEncode({
        "contents": [
          {
            "parts": [
              {"text": prompt},
            ],
          },
        ],
      }),
    );

    if (response.statusCode == 200) {
      final responseData = jsonDecode(response.body);

      final text =
          responseData["candidates"][0]["content"]["parts"][0]["text"]
              as String;

      final lines = text
          .split('\n')
          .where((String line) => line.trim().isNotEmpty)
          .toList();

      List<SmartSuggestion> suggestions = [];

      String suggestion = "";
      String impact = "";

      for (final line in lines) {
        if (line.startsWith("Suggestion:")) {
          suggestion = line.replaceFirst("Suggestion:", "").trim();
        }

        if (line.startsWith("Impact:")) {
          impact = line.replaceFirst("Impact:", "").trim();

          suggestions.add(
            SmartSuggestion(suggestion: suggestion, impact: impact),
          );
        }
      }

      return suggestions;
    }
    throw Exception(
      "Failed to generate smart suggestions. Status: ${response.statusCode}\n${response.body}",
    );
  }
}
