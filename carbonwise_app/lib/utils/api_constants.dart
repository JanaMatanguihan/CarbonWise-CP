import 'secrets.dart';

class ApiConstants {
  static const String baseUrl =
      'https://carbonwise-cp-production.up.railway.app/api';

  // Base URL and API Key for Open Route Service
  static const String orsApiKey =
      "eyJvcmciOiI1YjNjZTM1OTc4NTExMTAwMDFjZjYyNDgiLCJpZCI6IjQwNWU0ZGIwMDBhYjRkYzA4OWZjYzAxOTNiZTMyZDM1IiwiaCI6Im11cm11cjY0In0=";

  // API Key for Gemini (Google Generative AI)
  static const geminiApiKey = Secrets.geminiApiKey;
}
