import 'dart:convert';
import 'package:http/http.dart' as http;
import '../utils/api_constants.dart';

class LocationService {
  Future<double> calculateDistance({
    required String homeAddress,
    required String campusAddress,
  }) async {
    final home = await _geocode(homeAddress);
    final campus = await _geocode(campusAddress);

    final url = Uri.parse(
      "https://api.openrouteservice.org/v2/directions/driving-car",
    );

    final response = await http.post(
      url,
      headers: {
        "Authorization": ApiConstants.orsApiKey,
        "Content-Type": "application/json",
      },
      body: jsonEncode({
        "coordinates": [
          [home[0], home[1]],
          [campus[0], campus[1]],
        ],
      }),
    );

    if (response.statusCode != 200) {
      throw Exception(response.body);
    }

    final data = jsonDecode(response.body);

    final distanceMeters =
        data["features"][0]["properties"]["segments"][0]["distance"];

    return distanceMeters / 1000;
  }

  Future<List<double>> _geocode(String address) async {
    final url = Uri.parse(
      "https://api.openrouteservice.org/geocode/search"
      "?api_key=${ApiConstants.orsApiKey}"
      "&text=${Uri.encodeComponent(address)}",
    );

    final response = await http.get(url);

    if (response.statusCode != 200) {
      throw Exception(response.body);
    }

    final data = jsonDecode(response.body);

    final coords = data["features"][0]["geometry"]["coordinates"];

    return [
      (coords[0] as num).toDouble(), // longitude
      (coords[1] as num).toDouble(), // latitude
    ];
  }
}
