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
You are CarbonWise AI.

The user's latest carbon emissions are:

Transportation: $transportation kg CO₂
Electricity: $electricity kg CO₂
Food: $food kg CO₂

Analyze all three categories.

Requirements:

1. Begin with a short overall summary.

2. Identify the highest emission category and explain why it should be the priority.

3. Create these Markdown headings exactly:

## 🚗 Transportation
## 💡 Electricity
## 🥗 Food
## 🌱 Positive Progress

4. Under each category, provide 2–3 practical recommendations.

5. If a category already has relatively low emissions, acknowledge that and encourage the user to maintain those habits.

6. Keep the tone encouraging and friendly.

7. Keep the response under 220 words.

Do not use tables.
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
      final json = jsonDecode(response.body);

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
