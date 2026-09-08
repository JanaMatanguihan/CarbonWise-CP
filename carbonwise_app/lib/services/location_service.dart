import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:latlong2/latlong.dart';
import '../utils/api_constants.dart';

class LocationService {
  Future<RouteResult> calculateRoute({
    required String homeAddress,
    required String campusAddress,
  }) async {
    final home = await _geocode(homeAddress);
    final campus = await _geocode(campusAddress);

    final url = Uri.parse(
      "https://api.openrouteservice.org/v2/directions/driving-car/geojson",
    );

    final response = await http
        .post(
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
        )
        .timeout(const Duration(seconds: 15));

    if (response.statusCode != 200) {
      throw Exception(response.body);
    }

    final data = jsonDecode(response.body);

    final features = data['features'];
    if (features is! List || features.isEmpty) {
      throw Exception('No driving route was found.');
    }

    final route = features[0];
    final distanceMeters =
        (route['properties']?['summary']?['distance'] as num?)?.toDouble();
    if (distanceMeters == null) {
      throw Exception('The route response did not include a distance.');
    }

    final geometry = route['geometry']?['coordinates'];
    if (geometry is! List || geometry.isEmpty) {
      throw Exception('The route response did not include map coordinates.');
    }

    final points = geometry
        .whereType<List>()
        .where((coordinates) => coordinates.length >= 2)
        .map(
          (coordinates) => LatLng(
            (coordinates[1] as num).toDouble(),
            (coordinates[0] as num).toDouble(),
          ),
        )
        .toList();
    if (points.isEmpty) {
      throw Exception('The route coordinates are invalid.');
    }

    return RouteResult(distanceKm: distanceMeters / 1000, points: points);
  }

  Future<LatLng> geocodeAddress(String address) async {
    final coordinates = await _geocode(address);
    return LatLng(coordinates[1], coordinates[0]);
  }

  Future<List<double>> _geocode(String address) async {
    final url = Uri.parse(
      "https://api.openrouteservice.org/geocode/search"
      "?api_key=${ApiConstants.orsApiKey}"
      "&text=${Uri.encodeComponent(address)}",
    );

    final response = await http.get(url).timeout(const Duration(seconds: 15));

    if (response.statusCode != 200) {
      throw Exception(response.body);
    }

    final data = jsonDecode(response.body);

    final features = data['features'];
    if (features is! List || features.isEmpty) {
      throw Exception('Address not found.');
    }

    final coords = features[0]['geometry']?['coordinates'];
    if (coords is! List || coords.length < 2) {
      throw Exception('Address coordinates are unavailable.');
    }

    return [
      (coords[0] as num).toDouble(), // longitude
      (coords[1] as num).toDouble(), // latitude
    ];
  }
}

class RouteResult {
  const RouteResult({required this.distanceKm, required this.points});

  final double distanceKm;
  final List<LatLng> points;
}
