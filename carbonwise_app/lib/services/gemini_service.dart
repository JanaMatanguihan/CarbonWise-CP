import 'dart:convert';
import 'package:http/http.dart' as http;
import '../utils/api_constants.dart';

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

Keep answers friendly, simple, and under 100 words.

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
}
