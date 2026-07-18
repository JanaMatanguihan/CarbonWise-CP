import 'dart:convert';
import 'package:http/http.dart' as http;
import '../utils/api_constants.dart';
import 'package:supabase_flutter/supabase_flutter.dart';

class ApiService {
  static const String baseUrl = ApiConstants.baseUrl;
  static const String apiKey = ApiConstants.apiKey;

  // GET: Carbon Records

  Future<List<dynamic>> getCarbonRecords(String email) async {
    final today = DateTime.now().toIso8601String().split('T')[0];

    final response = await Supabase.instance.client
        .from('carbon_records')
        .select()
        .eq('g_suite', email)
        .eq('record_date', today);

    return response;
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
    required String transportItem,
    required String officeItem,
    required String foodItem,
  }) async {
    // STEP 1: Check if today's record already exists
    final checkResponse = await http.get(
      Uri.parse(
        "$baseUrl/carbon_records?g_suite=eq.$email&record_date=eq.$recordDate",
      ),
      headers: {"apikey": apiKey, "Authorization": "Bearer $apiKey"},
    );

    if (checkResponse.statusCode != 200) {
      throw Exception(checkResponse.body);
    }

    final List records = jsonDecode(checkResponse.body);

    // STEP 2: If a record already exists today, UPDATE it
    if (records.isNotEmpty) {
      final existing = records.first;

      final updatedTransportation =
          (existing["transportation"] ?? 0).toDouble() + transportation;

      final updatedElectricity =
          (existing["electricity"] ?? 0).toDouble() + electricity;

      final updatedFood = (existing["food"] ?? 0).toDouble() + food;

      final updatedTotal =
          (existing["total_emission"] ?? 0).toDouble() + totalEmission;

      final updateResponse = await http.patch(
        Uri.parse(
          "$baseUrl/carbon_records?g_suite=eq.$email&record_date=eq.$recordDate",
        ),
        headers: {
          "apikey": apiKey,
          "Authorization": "Bearer $apiKey",
          "Content-Type": "application/json",
        },
        body: jsonEncode({
          "transportation": updatedTransportation,
          "electricity": updatedElectricity,
          "food": updatedFood,
          "total_emission": updatedTotal,
          "transport_item": transportItem,
          "office_item": officeItem,
          "food_item": foodItem,
        }),
      );

      if (updateResponse.statusCode != 204 &&
          updateResponse.statusCode != 200) {
        throw Exception(updateResponse.body);
      }
    }
    // STEP 3: Otherwise create a new row
    else {
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
        "${ApiConstants.baseUrl}/carbon_records"
        "?g_suite=eq.$email"
        "&select=total_emission,transportation,electricity,food,transport_item,office_item,food_item"
        "&order=created_at.desc"
        "&limit=1",
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

  // Get User Campus
  Future<String?> getUserCampus(String email) async {
    final data = await getUserInfo(email);

    if (data.isEmpty) return null;

    return data["campus"];
  }

  // Get last 4 weeks record
  Future<List<Map<String, dynamic>>> getLast4WeeksRecords(String email) async {
    final now = DateTime.now();
    final fourWeeksAgo = now.subtract(const Duration(days: 28));

    final response = await Supabase.instance.client
        .from('carbon_records')
        .select()
        .eq('g_suite', email)
        .gte('record_date', fourWeeksAgo.toIso8601String().split('T').first)
        .order('record_date', ascending: true);

    return List<Map<String, dynamic>>.from(response);
  }
}
