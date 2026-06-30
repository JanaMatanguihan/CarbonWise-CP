import 'dart:convert';
import 'package:http/http.dart' as http;
import '../utils/api_constants.dart';

class ApiService {
  static const String baseUrl = ApiConstants.baseUrl;
  static const String apiKey = ApiConstants.apiKey;

  // GET: Carbon Records

  Future<List<dynamic>> getCarbonRecords(String email) async {
    final response = await http.get(
      Uri.parse("$baseUrl/carbon_records?g_suite=eq.$email&select=*"),
      headers: {"apikey": apiKey, "Authorization": "Bearer $apiKey"},
    );

    if (response.statusCode == 200) {
      return jsonDecode(response.body);
    }

    throw Exception("Failed to load records");
  }

  // POST: Carbon Record

  Future<void> addCarbonRecord({
    required String email,
    required double transportation,
    required double electricity,
    required double food,
    required double totalEmission,
    required String recordDate,
    required String createdAt,
  }) async {
    final response = await http.post(
      Uri.parse("$baseUrl/carbon_records"),
      headers: {
        "apikey": apiKey,
        "Authorization": "Bearer $apiKey",
        "Content-Type": "application/json",
        "Prefer": "return=representation",
      },
      body: jsonEncode({
        "g_suite": email,
        "transportation": transportation,
        "electricity": electricity,
        "food": food,
        "total_emission": totalEmission,
        "record_date": recordDate,
        "created_at": createdAt,
      }),
    );

    if (response.statusCode != 201 && response.statusCode != 200) {
      throw Exception(response.body);
    }
  }

  // GET: User Info

  Future<Map<String, dynamic>> getUserInfo(String email) async {
    final response = await http.get(
      Uri.parse(
        "${ApiConstants.baseUrl}/user_info?g_suite=eq.$email&select=sr_code,g_suite,full_name,campus,department",
      ),
      headers: {
        "apikey": ApiConstants.apiKey,
        "Authorization": "Bearer ${ApiConstants.apiKey}",
      },
    );

    if (response.statusCode == 200) {
      final List data = jsonDecode(response.body);
      return data.isNotEmpty ? data.first : {};
    }

    throw Exception("Failed to load user info");
  }

  // GET: User Carbon Score

  Future<Map<String, dynamic>?> getLatestCarbonScore(String email) async {
    final response = await http.get(
      Uri.parse(
        "${ApiConstants.baseUrl}/carbon_records?g_suite=eq.$email&select=total_emission&order=created_at.desc&limit=1",
      ),
      headers: {
        "apikey": ApiConstants.apiKey,
        "Authorization": "Bearer ${ApiConstants.apiKey}",
      },
    );

    if (response.statusCode == 200) {
      final List data = jsonDecode(response.body);

      if (data.isEmpty) return null;

      return data.first;
    }

    throw Exception("Failed to load carbon score");
  }

  // GET: User Recent Activities

  Future<List<dynamic>> getRecentActivities(String email) async {
    final response = await http.get(
      Uri.parse(
        "${ApiConstants.baseUrl}/carbon_records?g_suite=eq.$email&select=*&order=created_at.desc",
      ),
      headers: {
        "apikey": ApiConstants.apiKey,
        "Authorization": "Bearer ${ApiConstants.apiKey}",
      },
    );

    if (response.statusCode == 200) {
      return jsonDecode(response.body);
    }

    throw Exception("Failed to load activities");
  }

  // GET: Report Data

  Future<List<dynamic>> getEmissionData(String email) async {
    final response = await http.get(
      Uri.parse(
        "${ApiConstants.baseUrl}/carbon_records?g_suite=eq.$email&select=total_emission,record_date",
      ),
      headers: {
        "apikey": ApiConstants.apiKey,
        "Authorization": "Bearer ${ApiConstants.apiKey}",
      },
    );

    if (response.statusCode == 200) {
      return jsonDecode(response.body);
    }

    throw Exception("Failed to load emission data");
  }

  // PATCH: Update User Info

  Future<void> updateUserProfile({
    required String email,
    required String fullName,
    String? profilePicture,
  }) async {
    final response = await http.patch(
      Uri.parse("${ApiConstants.baseUrl}/user_info?g_suite=eq.$email"),
      headers: {
        "apikey": ApiConstants.apiKey,
        "Authorization": "Bearer ${ApiConstants.apiKey}",
        "Content-Type": "application/json",
        "Prefer": "return=representation",
      },
      body: jsonEncode({
        "full_name": fullName,
        "profile_picture": profilePicture,
      }),
    );

    if (response.statusCode != 200 && response.statusCode != 204) {
      throw Exception(response.body);
    }
  }
}
