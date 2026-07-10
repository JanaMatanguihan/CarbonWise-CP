import 'dart:convert';
import 'package:http/http.dart' as http;
import '../utils/api_constants.dart';

class RouteResult {
  final double distanceKm;
  final List<List<double>> coordinates;

  RouteResult({required this.distanceKm, required this.coordinates});
}

class LocationService {
  Future<RouteResult> calculateRoute({
    required String homeAddress,
    required String campusAddress,
  }) async {
    // 1. Convert home address into coordinates
    final home = await _geocode(homeAddress);

    // 2. Convert campus into coordinates
    final campus = await _geocode(campusAddress);

    // 3. Request route from OpenRouteService
    final response = await http.get(
      Uri.parse(
        'https://api.openrouteservice.org/v2/directions/driving-car'
        '?start=${home[0]},${home[1]}'
        '&end=${campus[0]},${campus[1]}',
      ),
      headers: {'Authorization': ApiConstants.orsApiKey},
    );

    if (response.statusCode != 200) {
      throw Exception("Unable to calculate route.");
    }

    final data = jsonDecode(response.body);

    final feature = data["features"][0];

    final distance =
        (feature["properties"]["summary"]["distance"] as num) / 1000;

    final coordinates = (feature["geometry"]["coordinates"] as List)
        .map<List<double>>(
          (e) => [(e[0] as num).toDouble(), (e[1] as num).toDouble()],
        )
        .toList();

    return RouteResult(distanceKm: distance, coordinates: coordinates);
  }

  Future<List<double>> _geocode(String address) async {
    final response = await http.get(
      Uri.parse(
        "https://api.openrouteservice.org/geocode/search"
        "?api_key=${ApiConstants.orsApiKey}"
        "&text=${Uri.encodeComponent(address)}",
      ),
    );

    if (response.statusCode != 200) {
      throw Exception("Geocoding failed.");
    }

    final data = jsonDecode(response.body);

    if (data["features"].isEmpty) {
      throw Exception("Address not found.");
    }

    final coords = data["features"][0]["geometry"]["coordinates"];

    return [(coords[0] as num).toDouble(), (coords[1] as num).toDouble()];
  }
}
