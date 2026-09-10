import 'dart:convert';
import 'dart:io';

import 'package:http/http.dart' as http;
import 'package:path/path.dart' as path;

import '../utils/api_constants.dart';

class ApiService {
  static const String baseUrl = ApiConstants.baseUrl;

  // LARAVEL AUTHENTICATION TOKEN

  static String? _token;
  static String? _currentUserEmail;

  static String? get token => _token;

  static Future<String?> getCurrentUserEmail() async {
    return _currentUserEmail;
  }

  static void setToken(String token) {
    _token = token;
  }

  static void clearToken() {
    _token = null;
    _currentUserEmail = null;
  }

  // HEADERS

  static Map<String, String> get _headers {
    return {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      if (_token != null) 'Authorization': 'Bearer $_token',
    };
  }

  static Map<String, String> authHeaders(String token) {
    return {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'Authorization': 'Bearer $token',
    };
  }

  // LOGIN

  static Future<Map<String, dynamic>> login(
    String email,
    String password,
  ) async {
    try {
      final response = await http
          .post(
            Uri.parse('$baseUrl/login'),
            headers: {
              'Content-Type': 'application/json',
              'Accept': 'application/json',
            },
            body: jsonEncode({'email': email, 'password': password}),
          )
          .timeout(const Duration(seconds: 15));

      final data = _decodeResponse(response);

      if (response.statusCode == 200) {
        final token = data['token'];

        if (token != null) {
          setToken(token.toString());
        }

        _currentUserEmail = email;

        return data;
      }

      throw Exception(data['message'] ?? 'Login failed.');
    } on SocketException {
      throw Exception(
        'Unable to connect to the server. '
        'Make sure Laravel is running and your phone is connected '
        'to the same Wi-Fi network.',
      );
    } on HttpException {
      throw Exception('Could not communicate with the Laravel server.');
    } catch (e) {
      rethrow;
    }
  }

  // REGISTER

  static Future<Map<String, dynamic>> register({
    required String email,
    required String password,
    required String passwordConfirmation,
    required String role,
    String? srCode,
    required String fullName,
    required String campus,
    int? yearLevel,
    String? department,
    String? facultyType,
    String? office,
  }) async {
    final response = await http.post(
      Uri.parse('$baseUrl/register'),
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: jsonEncode({
        // IMPORTANT:
        // Laravel expects "name", NOT "full_name"
        'name': fullName,

        'email': email,
        'password': password,
        'password_confirmation': passwordConfirmation,

        'role': role,

        'sr_code': srCode,
        'campus': campus,
        'year_level': yearLevel,
        'department': department,
        'faculty_type': facultyType,
        'office': office,
      }),
    );

    // Debug output
    print('REGISTER STATUS: ${response.statusCode}');
    print('REGISTER RESPONSE: ${response.body}');

    final data = _decodeResponse(response);

    if (response.statusCode == 200 || response.statusCode == 201) {
      return data;
    }

    // Laravel validation errors
    if (response.statusCode == 422) {
      final errors = data['errors'];

      if (errors != null) {
        throw Exception(errors.toString());
      }

      throw Exception(
        data['message'] ?? 'Please check the information you entered.',
      );
    }

    throw Exception(
      'Registration failed.\n'
      'Status: ${response.statusCode}\n'
      'Response: ${response.body}',
    );
  }

  // GET: CARBON RECORDS

  Future<List<dynamic>> getCarbonRecords(String email) async {
    final response = await http
        .get(Uri.parse('$baseUrl/carbon-records'), headers: _headers)
        .timeout(const Duration(seconds: 15));

    final data = _decodeResponse(response);

    if (response.statusCode == 200) {
      return data['records'] ?? data['data'] ?? [];
    }

    throw Exception(data['message'] ?? 'Failed to load carbon records.');
  }

  // POST: CARBON RECORD

  Future<void> addCarbonRecord({
    required double transportation,
    required double electricity,
    required double food,
    required String recordDate,
    String? transportItem,
    String? officeItem,
    String? foodItem,
    String? foodMealPeriod,
    String? foodConsumedAt,
  }) async {
    final response = await http
        .post(
          Uri.parse('$baseUrl/carbon-records'),
          headers: _headers,
          body: jsonEncode({
            'transportation': transportation,
            'electricity': electricity,
            'food': food,
            'record_date': recordDate,
            if (transportItem != null) 'transport_item': transportItem,
            if (officeItem != null) 'office_item': officeItem,
            if (foodItem != null) 'food_item': foodItem,
            if (foodMealPeriod != null) 'food_meal_period': foodMealPeriod,
            if (foodConsumedAt != null) 'food_consumed_at': foodConsumedAt,
          }),
        )
        .timeout(const Duration(seconds: 15));

    final data = _decodeResponse(response);

    if (response.statusCode != 201 && response.statusCode != 200) {
      throw Exception(data['message'] ?? 'Failed to add carbon record.');
    }
  }

  // GET: ONE CARBON RECORD

  Future<List<dynamic>> getCarbonRecord(String email) async {
    final response = await http
        .get(Uri.parse('$baseUrl/carbon-records'), headers: _headers)
        .timeout(const Duration(seconds: 30));

    final data = _decodeResponse(response);

    if (response.statusCode == 200) {
      return data['records'] ?? data['data'] ?? [];
    }

    throw Exception(data['message'] ?? 'Failed to load carbon records.');
  }

  // PUT: UPDATE CARBON RECORD

  Future<void> updateCarbonRecord({
    required int id,
    required double transportation,
    required double electricity,
    required double food,
    required String recordDate,
  }) async {
    final response = await http
        .put(
          Uri.parse('$baseUrl/carbon-records/$id'),
          headers: _headers,
          body: jsonEncode({
            'transportation': transportation,
            'electricity': electricity,
            'food': food,
            'record_date': recordDate,
          }),
        )
        .timeout(const Duration(seconds: 15));

    final data = _decodeResponse(response);

    if (response.statusCode != 200) {
      throw Exception(data['message'] ?? 'Failed to update carbon record.');
    }
  }

  // DELETE: CARBON RECORD

  Future<void> deleteCarbonRecord(int id) async {
    final response = await http
        .delete(Uri.parse('$baseUrl/carbon-records/$id'), headers: _headers)
        .timeout(const Duration(seconds: 15));

    final data = _decodeResponse(response);

    if (response.statusCode != 200) {
      throw Exception(data['message'] ?? 'Failed to delete carbon record.');
    }
  }

  // GET: USER INFO

  Future<Map<String, dynamic>> getUserInfo([String? email]) async {
    try {
      final response = await http
          .get(Uri.parse('$baseUrl/profile'), headers: _headers)
          .timeout(const Duration(seconds: 15));

      print('PROFILE STATUS: ${response.statusCode}');
      print('PROFILE RESPONSE: ${response.body}');
      print('PROFILE TOKEN: $_token');

      final data = _decodeResponse(response);

      if (response.statusCode == 200) {
        return data['user'] ?? data['data'] ?? data;
      }

      throw Exception(data['message'] ?? 'Failed to load user information.');
    } catch (e) {
      print('PROFILE API ERROR: $e');
      rethrow;
    }
  }

  // GET: USER CAMPUS

  Future<String?> getUserCampus(String email) async {
    final data = await getUserInfo(email);

    if (data.isEmpty) {
      return null;
    }

    return data['campus']?.toString();
  }

  // GET: LATEST CARBON SCORE
  Future<Map<String, dynamic>?> getLatestCarbonScore(String email) async {
    final records = await getCarbonRecords(email);

    if (records.isEmpty) {
      return null;
    }

    return records.first;
  }

  // GET: RECENT ACTIVITIES
  Future<List<dynamic>> getRecentActivities(String email) async {
    final records = await getCarbonRecords(email);
    return records;
  }

  // GET: EMISSION DATA FOR REPORTS
  Future<List<dynamic>> getEmissionData(String email) async {
    final records = await getCarbonRecords(email);

    return records.map((record) {
      return {
        'total_emission': record['total_emission'],
        'record_date': record['record_date'],
      };
    }).toList();
  }

  // GET: LAST 4 WEEKS / RECORDS
  Future<List<dynamic>> getLast4WeeksRecords(String email) async {
    final records = List<dynamic>.from(await getCarbonRecords(email));

    records.sort(
      (a, b) => (b['record_date']?.toString() ?? '').compareTo(
        a['record_date']?.toString() ?? '',
      ),
    );

    return records.take(4).toList();
  }

  // GET: CARBON PATTERNS
  Future<Map<String, dynamic>> getCarbonPatterns() async {
    final response = await http
        .get(Uri.parse('$baseUrl/carbon-patterns'), headers: _headers)
        .timeout(const Duration(seconds: 15));

    final data = _decodeResponse(response);

    if (response.statusCode == 200) {
      return data;
    }

    throw Exception(data['message'] ?? 'Failed to load carbon patterns.');
  }

  // PUT: UPDATE USER PROFILE
  Future<Map<String, dynamic>> updateUserProfile({
    String? name,
    String? email,
    String? department,
    String? campus,
    String? yearLevel,
  }) async {
    final body = <String, dynamic>{};

    if (name != null) body['name'] = name;
    if (email != null) body['email'] = email;
    if (department != null) body['department'] = department;
    if (campus != null) body['campus'] = campus;
    if (yearLevel != null) body['year_level'] = yearLevel;

    final response = await http
        .put(
          Uri.parse('$baseUrl/profile'),
          headers: _headers,
          body: jsonEncode(body),
        )
        .timeout(const Duration(seconds: 15));

    final data = _decodeResponse(response);

    if (response.statusCode == 200) {
      return data['user'] ?? data['data'] ?? data;
    }

    throw Exception(data['message'] ?? 'Failed to update profile.');
  }

  // POST: UPLOAD PROFILE PICTURE
  Future<String> uploadProfilePicture(File imageFile) async {
    if (_token == null) {
      throw Exception('User not logged in.');
    }

    final request = http.MultipartRequest(
      'POST',
      Uri.parse('$baseUrl/profile-picture'),
    );

    request.headers.addAll({
      'Accept': 'application/json',
      'Authorization': 'Bearer $_token',
    });

    request.files.add(
      await http.MultipartFile.fromPath(
        'profile_picture',
        imageFile.path,
        filename: path.basename(imageFile.path),
      ),
    );

    final streamedResponse = await request.send();

    final response = await http.Response.fromStream(streamedResponse);

    final data = _decodeResponse(response);

    if (response.statusCode == 200 || response.statusCode == 201) {
      return data['profile_picture'] ??
          data['url'] ??
          data['profilePicture'] ??
          '';
    }

    throw Exception(data['message'] ?? 'Failed to upload profile picture.');
  }

  // POST: ADD NOTIFICATION
  Future<void> addNotification({
    required String email,
    required String title,
    required String message,
    required String type,
  }) async {
    final response = await http
        .post(
          Uri.parse('$baseUrl/notifications'),
          headers: _headers,
          body: jsonEncode({'title': title, 'message': message, 'type': type}),
        )
        .timeout(const Duration(seconds: 15));

    final data = _decodeResponse(response);

    if (response.statusCode != 200 && response.statusCode != 201) {
      throw Exception(data['message'] ?? 'Failed to add notification.');
    }
  }

  // TEST LARAVEL CONNECTION
  Future<String> testLaravel() async {
    final response = await http
        .get(
          Uri.parse('$baseUrl/../up'),
          headers: {'Accept': 'application/json'},
        )
        .timeout(const Duration(seconds: 10));

    if (response.statusCode == 200) {
      return response.body;
    }

    throw Exception('Laravel connection failed: ${response.statusCode}');
  }

  // LOGOUT
  static Future<void> logout() async {
    if (_token != null) {
      try {
        await http
            .post(Uri.parse('$baseUrl/logout'), headers: _headers)
            .timeout(const Duration(seconds: 10));
      } catch (_) {
        // Even if the server request fails,
        // clear the local token.
      }
    }

    clearToken();
    _currentUserEmail = null;
  }

  // RESPONSE HELPER
  static Map<String, dynamic> _decodeResponse(http.Response response) {
    if (response.body.isEmpty) {
      return {};
    }

    try {
      final decoded = jsonDecode(response.body);

      if (decoded is Map<String, dynamic>) {
        return decoded;
      }

      return {'data': decoded};
    } catch (_) {
      return {'message': response.body};
    }
  }

  // GET: NOTIFICATIONS
  Future<List<dynamic>> getNotifications() async {
    final response = await http
        .get(Uri.parse('$baseUrl/notifications'), headers: _headers)
        .timeout(const Duration(seconds: 15));

    final data = _decodeResponse(response);

    if (response.statusCode == 200) {
      return data['notifications'] ?? data['data'] ?? [];
    }

    throw Exception(data['message'] ?? 'Failed to load notifications.');
  }

  // PUT: CHANGE PASSWORD
  Future<void> changePassword({
    required String currentPassword,
    required String newPassword,
  }) async {
    final response = await http
        .put(
          Uri.parse('$baseUrl/change-password'),
          headers: _headers,
          body: jsonEncode({
            'current_password': currentPassword,
            'new_password': newPassword,
            'new_password_confirmation': newPassword,
          }),
        )
        .timeout(const Duration(seconds: 15));

    final data = _decodeResponse(response);

    if (response.statusCode == 200) {
      return;
    }

    if (response.statusCode == 422) {
      throw Exception(
        data['message'] ??
            data['errors']?.toString() ??
            'Invalid password information.',
      );
    }

    throw Exception(data['message'] ?? 'Failed to change password.');
  }

  // GET USER PROFILE
  Future<Map<String, dynamic>> getUserProfile() async {
    final response = await http
        .get(Uri.parse('$baseUrl/profile'), headers: _headers)
        .timeout(const Duration(seconds: 15));

    print('PROFILE STATUS: ${response.statusCode}');
    print('PROFILE RESPONSE: ${response.body}');

    final data = _decodeResponse(response);

    if (response.statusCode == 200) {
      return data['user'] ?? data['data'] ?? data;
    }

    throw Exception(data['message'] ?? 'Failed to load profile.');
  }

  // GET DEPARTMENT RANKINGS
  Future<List<dynamic>> getDepartmentRankings({String? month}) async {
    final query = month != null ? '?month=$month' : '';

    final response = await http
        .get(Uri.parse('$baseUrl/department-rankings$query'), headers: _headers)
        .timeout(const Duration(seconds: 15));

    final data = _decodeResponse(response);

    if (response.statusCode == 200) {
      final value = data['rankings'] ?? data['data'] ?? data;
      return value is List ? value : [];
    }

    print('DEPARTMENT STATUS: ${response.statusCode}');
    print('DEPARTMENT BODY: ${response.body}');

    throw Exception(data['message'] ?? 'Failed to load department rankings.');
  }

  // GET CAMPUS RANKINGS
  Future<List<dynamic>> getCampusRankings({String? month}) async {
    final query = month != null ? '?month=$month' : '';

    final response = await http
        .get(Uri.parse('$baseUrl/campus-rankings$query'), headers: _headers)
        .timeout(const Duration(seconds: 15));

    final data = _decodeResponse(response);

    if (response.statusCode == 200) {
      final value = data['rankings'] ?? data['data'] ?? data;
      return value is List ? value : [];
    }

    print('CAMPUS STATUS: ${response.statusCode}');
    print('CAMPUS BODY: ${response.body}');

    throw Exception(data['message'] ?? 'Failed to load campus rankings.');
  }

  // GET: PEER COMPARISON
  Future<Map<String, dynamic>> getMyPeerComparison({
    String period = 'monthly',
  }) async {
    final query = '?period=$period';

    final response = await http
        .get(Uri.parse('$baseUrl/my-peer-comparison$query'), headers: _headers)
        .timeout(const Duration(seconds: 15));

    final data = _decodeResponse(response);

    if (response.statusCode == 200) {
      return Map<String, dynamic>.from(data);
    }

    throw Exception(data['message'] ?? 'Failed to load peer comparison.');
  }

  // GET USER CARBON RECORDS
  Future<List<dynamic>> getUserCarbonRecords(String email) async {
    final response = await http
        .get(
          Uri.parse('$baseUrl/profile/$email/carbon-records'),
          headers: _headers,
        )
        .timeout(const Duration(seconds: 15));

    final data = _decodeResponse(response);

    if (response.statusCode == 200) {
      final value = data['records'] ?? data['data'] ?? data;
      return value is List ? value : [];
    }

    throw Exception(data['message'] ?? 'Failed to load carbon records');
  }

  // GET: DASHBOARD SUMMARY / RANKINGS
  // Laravel should return the authenticated user's dashboard data.
  Future<Map<String, dynamic>> getDashboardSummary() async {
    final response = await http
        .get(Uri.parse('$baseUrl/dashboard'), headers: _headers)
        .timeout(const Duration(seconds: 15));

    final data = _decodeResponse(response);

    if (response.statusCode == 200) {
      final value = data['data'];
      return value is Map<String, dynamic>
          ? Map<String, dynamic>.from(value)
          : data;
    }

    throw Exception(data['message'] ?? 'Failed to load dashboard data.');
  }

  // GET: ALL INDIVIDUAL ACTIVITIES RECORDED TODAY
  Future<List<dynamic>> getTodayActivities() async {
    final response = await http
        .get(Uri.parse('$baseUrl/carbon-records/today'), headers: _headers)
        .timeout(const Duration(seconds: 15));

    final data = _decodeResponse(response);

    if (response.statusCode == 200) {
      final value =
          data['activities'] ?? data['records'] ?? data['data'] ?? data;
      return value is List ? value : [];
    }

    throw Exception(data['message'] ?? 'Failed to load today\'s activities.');
  }

  // GET CURRENT USER
  static Future<Map<String, dynamic>> getCurrentUser() async {
    final response = await http
        .get(Uri.parse('$baseUrl/profile'), headers: _headers)
        .timeout(const Duration(seconds: 15));

    final data = _decodeResponse(response);

    if (response.statusCode == 200) {
      return data['user'] ?? data['data'] ?? data;
    }

    throw Exception(data['message'] ?? 'Failed to load current user.');
  }
}
