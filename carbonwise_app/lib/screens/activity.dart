import 'package:flutter/material.dart';
import 'dart:async';
import 'package:carbonwise_app/services/api_service.dart';
import 'package:carbonwise_app/utils/dialog_helper.dart';
import 'package:carbonwise_app/services/location_service.dart';
import 'package:carbonwise_app/utils/strategy_notifier.dart';
import 'package:flutter/services.dart';
import 'package:carbonwise_app/utils/carbon_score_refresh_notifier.dart';
import 'package:flutter_map/flutter_map.dart';
import 'package:geolocator/geolocator.dart';
import 'package:latlong2/latlong.dart';
import 'package:carbonwise_app/services/notification_service.dart';

const primaryGreen = Color(0xFF3AA76D);
const darkGreen = Color(0xFF1E5631);

class ActivityInputScreen extends StatefulWidget {
  const ActivityInputScreen({super.key});

  @override
  State<ActivityInputScreen> createState() => _ActivityInputScreenState();
}

class _ActivityInputScreenState extends State<ActivityInputScreen> {
  final ApiService _apiService = ApiService();
  // Form State Values
  String? _selectedTransportType;
  String? _selectedOfficeResourceCategory;
  String? _selectedCampus;
  String? _selectedMealPeriod;
  String? _lastFoodMealPeriod;
  DateTime? _lastFoodConsumedAt;
  final DateTime _selectedFoodDate = DateTime.now();
  TimeOfDay _selectedFoodTime = TimeOfDay.now();
  String? _selectedFoodItem;

  double _transportationTotalEmission = 0.0;
  double _officeResourceTotalEmission = 0.0;
  double _foodTotalEmission = 0.0;

  // Lists to store added emissions dynamically
  final List<String> _transportEmissions = [];
  final List<String> _officeEmissions = [];
  final List<String> _foodEmissions = [];
  final LocationService _locationService = LocationService();
  double? _distanceKm;
  List<LatLng> _routePoints = const [];
  bool _isCalculatingDistance = false;
  bool _isSavingCarbonRecord = false;
  bool _isCheckingCampus = false;
  bool _isOnCampus = false;

  final TextEditingController _homeAddressController = TextEditingController();
  final TextEditingController _officeHoursController = TextEditingController();
  final TextEditingController _servingSizeController = TextEditingController();

  final Map<String, String> campusAddresses = {
    "Lipa Campus": "Batangas State University Lipa Campus",
    "Pablo Borbon Campus": "Batangas State University Pablo Borbon Campus",
    "Alangilan Campus": "Batangas State University Alangilan Campus",
    "Lima Campus": "Batangas State University Lima Campus",
    "ARASOF Nasugbu Campus": "Batangas State University ARASOF Nasugbu Campus",
    "JPLPC Malvar Campus": "Batangas State University JPLPC Malvar Campus",
    "Lemery Campus": "Batangas State University Lemery Campus",
    "Rosario Campus": "Batangas State University Rosario Campus",
    "San Juan Campus": "Batangas State University San Juan Campus",
    "Balayan Campus": "Batangas State University Balayan Campus",
    "Lobo Campus": "Batangas State University Lobo Campus",
    "Mabini Campus": "Batangas State University Mabini Campus",
  };

  final Map<String, double> transportationEmissionFactors = {
    'Motorcycle (0.103 kg CO₂e/km)': 0.103,
    'Tricycle (0.095 kg C₂e/km)': 0.095,
    'Modern Jeepney (0.035 kg C₂e/km)': 0.035,
    'Traditional Jeepney (0.08 kg C₂e/km)': 0.08,
    'Private Car (Gasoline) (0.171 kg C₂e/km)': 0.171,
  };

  final Map<String, List<String>> officeResourceGroups = {
    'Laptops': [
      'Ultra-light / Netbook (40W)',
      'Standard Business Laptop (60W)',
      'Performance Laptop (120W)',
      'Gaming / High-End Workstation Laptop (300W)',
    ],
    'Desktop Computers & Monitors': [
      'Standard Office PC CPU (100W)',
      'Mid-Range Workstation CPU (200W)',
      'High-End / Gaming PC CPU (600W)',
      'Mini PC - NUC/Mac Mini (40W)',
      '18.5" to 20" LED Monitor (20W)',
      '22" to 24" LED Monitor (30W)',
      '27" and larger LED Monitor (50W)',
      'Old CRT Monitor (100W)',
    ],
    'Air Conditioners (Window & Split)': [
      'Window Type AC - 0.5 HP (500W)',
      'Window Type AC - 1.0 HP (1000W)',
      'Window Type AC - 1.5 HP (1500W)',
      'Window Type AC - 2.0 HP (2000W)',
      'Inverter Split Type AC - 1.0 HP (900W)',
      'Inverter Split Type AC - 1.5 HP (1300W)',
      'Inverter Split Type AC - 2.0 HP (1800W)',
      'Inverter Split Type AC - 2.5 HP (2300W)',
      'Floor Standing AC - 3.0 HP (3300W)',
      'Floor Standing AC - 5.0 HP (5300W)',
    ],
    'Smart Displays & Projectors': [
      'Viewboard Smart Screen 55" to 65" (250W)',
      'Viewboard Smart Screen 75" (350W)',
      'Viewboard Smart Screen 86" (500W)',
      'Viewboard Smart Screen 98" and above (800W)',
      'Standard DLP/LCD Projector (300W)',
      'Projector - Eco-Mode (200W)',
      'Large Venue Projector (500W)',
    ],
    'Electric Fans': [
      'AC Motor Fan (65W)',
      'DC Motor Fan (30W)',
      'Ceiling Fan (80W)',
      'Stand Fan (60W)',
      'Wall Fan (55W)',
      'Exhaust Fan (30W)',
      'Tower Fan (50W)',
      'Desk Fan (40W)',
      'Bladeless Fan (55W)',
      'Misting Fan (130W)',
      'Industrial Fan (200W)',
    ],
    'Lights': [
      'Standard LED Bulb (10W)',
      'LED Tube T8/T5 (15W)',
      'LED Downlight/Panel (12W)',
      'High-bay Gym/Halls LED (100W)',
      'CFL Compact Fluorescent (18W)',
    ],
    'Printers & Scanners': [
      'Scanner - Ready/Sleep Mode (100W)',
      'Flatbed Scanner (20W)',
      'High-speed Document Scanner (50W)',
      'Inkjet Printer - Active (30W)',
      'Laser Printer B&W - Active (400W)',
      'Color Laser Printer - Active (500W)',
      'Mid-size Office MFP Copier (1000W)',
      'High-volume Photocopier (2000W)',
    ],
    'Audio Systems': [
      'Desktop/PC Speakers (20W)',
      'Wall-mounted Classroom Speakers (60W)',
      'Large PA System Events/Gym (1000W)',
    ],
  };

  final Map<String, double> officeResourcePowerRatings = {
    'Ultra-light / Netbook (40W)': 40,
    'Standard Business Laptop (60W)': 60,
    'Performance Laptop (120W)': 120,
    'Gaming / High-End Workstation Laptop (300W)': 300,
    'Standard Office PC CPU (100W)': 100,
    'Mid-Range Workstation CPU (200W)': 200,
    'High-End / Gaming PC CPU (600W)': 600,
    'Mini PC - NUC/Mac Mini (40W)': 40,
    '18.5" to 20" LED Monitor (20W)': 20,
    '22" to 24" LED Monitor (30W)': 30,
    '27" and larger LED Monitor (50W)': 50,
    'Old CRT Monitor (100W)': 100,
    'Window Type AC - 0.5 HP (500W)': 500,
    'Window Type AC - 1.0 HP (1000W)': 1000,
    'Window Type AC - 1.5 HP (1500W)': 1500,
    'Window Type AC - 2.0 HP (2000W)': 2000,
    'Inverter Split Type AC - 1.0 HP (900W)': 900,
    'Inverter Split Type AC - 1.5 HP (1300W)': 1300,
    'Inverter Split Type AC - 2.0 HP (1800W)': 1800,
    'Inverter Split Type AC - 2.5 HP (2300W)': 2300,
    'Floor Standing AC - 3.0 HP (3300W)': 3300,
    'Floor Standing AC - 5.0 HP (5300W)': 5300,
    'Viewboard Smart Screen 55" to 65" (250W)': 250,
    'Viewboard Smart Screen 75" (350W)': 350,
    'Viewboard Smart Screen 86" (500W)': 500,
    'Viewboard Smart Screen 98" and above (800W)': 800,
    'Standard DLP/LCD Projector (300W)': 300,
    'Projector - Eco-Mode (200W)': 200,
    'Large Venue Projector (500W)': 500,
    'AC Motor Fan (65W)': 65,
    'DC Motor Fan (30W)': 30,
    'Ceiling Fan (80W)': 80,
    'Stand Fan (60W)': 60,
    'Wall Fan (55W)': 55,
    'Exhaust Fan (30W)': 30,
    'Tower Fan (50W)': 50,
    'Desk Fan (40W)': 40,
    'Bladeless Fan (55W)': 55,
    'Misting Fan (130W)': 130,
    'Industrial Fan (200W)': 200,
    'Standard LED Bulb (10W)': 10,
    'LED Tube T8/T5 (15W)': 15,
    'LED Downlight/Panel (12W)': 12,
    'High-bay Gym/Halls LED (100W)': 100,
    'CFL Compact Fluorescent (18W)': 18,
    'Scanner - Ready/Sleep Mode (100W)': 100,
    'Flatbed Scanner (20W)': 20,
    'High-speed Document Scanner (50W)': 50,
    'Inkjet Printer - Active (30W)': 30,
    'Laser Printer B&W - Active (400W)': 400,
    'Color Laser Printer - Active (500W)': 500,
    'Mid-size Office MFP Copier (1000W)': 1000,
    'High-volume Photocopier (2000W)': 2000,
    'Desktop/PC Speakers (20W)': 20,
    'Wall-mounted Classroom Speakers (60W)': 60,
    'Large PA System Events/Gym (1000W)': 1000,
  };

  final Map<String, double> foodEmissionFactors = {
    'Beef (Beef Herd) (60.0 kg CO2e/kg)': 60.0,
    'Lamb & Mutton (24.5 kg CO2e/kg)': 24.5,
    'Beef (Dairy Herd) (21.1 kg CO2e/kg)': 21.1,
    'Cheese (21.0 kg CO2e/kg)': 21.0,
    'Pork (7.0 kg CO2e/kg)': 7.0,
    'Poultry (Chicken / Turkey) (6.0 kg CO2e/kg)': 6.0,
    'Eggs (4.5 kg CO2e/kg)': 4.5,
    'Fish (Farmed) (5.0 kg CO2e/kg)': 5.0,
    'Rice (Flooded) (4.4 kg CO2e/kg)': 4.4,
    'Tofu (Soy-based) (3.0 kg CO2e/kg)': 3.0,
    'Groundnuts / Peanuts (2.5 kg CO2e/kg)': 2.5,
    'Pulses (Beans / Peas) (1.5 kg CO2e/kg)': 1.5,
    'Wheat & Rye (Bread) (1.4 kg CO2e/kg)': 1.4,
    'Maize (Corn) (1.0 kg CO2e/kg)': 1.0,
    'Potatoes (0.5 kg CO2e/kg)': 0.5,
    'Apples / Bananas (0.4 kg CO2e/kg)': 0.4,
    'Root Vegetables (0.4 kg CO2e/kg)': 0.4,
    'Coffee (22.0 kg CO2e/kg)': 22.0,
    'Dark Chocolate (19.0 kg CO2e/kg)': 19.0,
    'Milk (Bovine) (3.2 kg CO2e/liter)': 3.2,
    'Soy Milk (1.0 kg CO2e/liter)': 1.0,
  };

  final Set<String> _foodLiterItems = {
    'Milk (Bovine) (3.2 kg CO2e/liter)',
    'Soy Milk (1.0 kg CO2e/liter)',
  };

  final Map<String, double> foodGramsPerCup = {
    'Beef (Beef Herd) (60.0 kg CO2e/kg)': 226,
    'Lamb & Mutton (24.5 kg CO2e/kg)': 113,
    'Beef (Dairy Herd) (21.1 kg CO2e/kg)': 226,

    'Cheese (21.0 kg CO2e/kg)': 113,
    'Pork (7.0 kg CO2e/kg)': 135,
    'Poultry (Chicken / Turkey) (6.0 kg CO2e/kg)': 140,
    'Eggs (4.5 kg CO2e/kg)': 136,
    'Fish (Farmed) (5.0 kg CO2e/kg)': 154,

    'Rice (Flooded) (4.4 kg CO2e/kg)': 158,
    'Tofu (Soy-based) (3.0 kg CO2e/kg)': 126,
    'Groundnuts / Peanuts (2.5 kg CO2e/kg)': 146,
    'Pulses (Beans / Peas) (1.5 kg CO2e/kg)': 177,

    'Wheat & Rye (Bread) (1.4 kg CO2e/kg)': 120,
    'Maize (Corn) (1.0 kg CO2e/kg)': 164,
    'Potatoes (0.5 kg CO2e/kg)': 150,
    'Apples / Bananas (0.4 kg CO2e/kg)': 150,
    'Root Vegetables (0.4 kg CO2e/kg)': 150,

    'Coffee (22.0 kg CO2e/kg)': 82,
    'Dark Chocolate (19.0 kg CO2e/kg)': 132,
  };

  final Map<String, double> foodLitersPerCup = {
    'Milk (Bovine) (3.2 kg CO2e/liter)': 0.236,
    'Soy Milk (1.0 kg CO2e/liter)': 0.236,
  };

  @override
  void initState() {
    super.initState();
    _loadSavedCarbonRecords();
    _verifyCampusPresence(showFeedback: false);
  }

  @override
  void dispose() {
    _homeAddressController.dispose();
    _officeHoursController.dispose();
    _servingSizeController.dispose();
    super.dispose();
  }

  double _calculateTransportationEmission(
    String transportType,
    double distance,
  ) {
    final emissionFactor = transportationEmissionFactors[transportType] ?? 0.0;
    return (distance * 2) * emissionFactor;
  }

  double _calculateOfficeResourceEmission(String category, double hours) {
    final power = officeResourcePowerRatings[category] ?? 0;
    return (power * hours / 1000) * 0.7122;
  }

  double _calculateFoodEmission(String foodItem, double servings) {
    final factor = foodEmissionFactors[foodItem] ?? 0.0;

    // Milk and soy milk use liters
    if (_foodLiterItems.contains(foodItem)) {
      final litersPerCup = foodLitersPerCup[foodItem] ?? 0.236;

      final liters = servings * litersPerCup;

      return factor * liters;
    }

    // Other foods use grams -> kg
    final gramsPerCup = foodGramsPerCup[foodItem] ?? 150.0;

    final grams = servings * gramsPerCup;
    final kilograms = grams / 1000.0;

    return factor * kilograms;
  }

  Future<bool> _verifyCampusPresence({required bool showFeedback}) async {
    if (_isCheckingCampus) return _isOnCampus;

    setState(() => _isCheckingCampus = true);

    try {
      if (!await Geolocator.isLocationServiceEnabled()) {
        throw const _CampusCheckException(
          'Turn on location services to add activities.',
        );
      }

      var permission = await Geolocator.checkPermission();

      if (permission == LocationPermission.denied) {
        permission = await Geolocator.requestPermission();
      }

      if (permission == LocationPermission.denied ||
          permission == LocationPermission.deniedForever) {
        throw const _CampusCheckException(
          'Location permission is required to add activities on campus.',
        );
      }

      final campus = await _apiService.getUserCampus('');
      final campusAddress = campus == null ? null : campusAddresses[campus];

      if (campusAddress == null) {
        throw const _CampusCheckException(
          'Your profile campus is not available for location checking.',
        );
      }

      final position = await Geolocator.getCurrentPosition(
        locationSettings: const LocationSettings(
          accuracy: LocationAccuracy.high,
        ),
      );

      final campusPoint = await _locationService.geocodeAddress(campusAddress);

      final metersAway = Geolocator.distanceBetween(
        position.latitude,
        position.longitude,
        campusPoint.latitude,
        campusPoint.longitude,
      );

      final onCampus = metersAway <= 600;

      if (!mounted) return onCampus;

      setState(() => _isOnCampus = onCampus);

      if (!onCampus && showFeedback) {
        DialogHelper.showWarning(
          context: context,
          title: 'Campus location required',
          message:
              'You are about ${metersAway.round()} m from your registered campus. Activities can only be added within 600 m of campus.',
        );
      }

      return onCampus;
    } on _CampusCheckException catch (error) {
      if (mounted && showFeedback) {
        DialogHelper.showWarning(
          context: context,
          title: 'Location required',
          message: error.message,
        );
      }

      return false;
    } catch (_) {
      if (mounted && showFeedback) {
        DialogHelper.showWarning(
          context: context,
          title: 'Location check failed',
          message:
              'We could not verify that you are on campus. Please try again with location services enabled.',
        );
      }

      return false;
    } finally {
      if (mounted) {
        setState(() => _isCheckingCampus = false);
      }
    }
  }

  Future<void> _pickFoodTime() async {
    final time = await showTimePicker(
      context: context,
      initialTime: _selectedFoodTime,
    );
    if (time != null && mounted) setState(() => _selectedFoodTime = time);
  }

  DateTime get _selectedFoodDateTime => DateTime(
    _selectedFoodDate.year,
    _selectedFoodDate.month,
    _selectedFoodDate.day,
    _selectedFoodTime.hour,
    _selectedFoodTime.minute,
  );

  String get _selectedFoodDateLabel =>
      '${_selectedFoodDate.year}-${_selectedFoodDate.month.toString().padLeft(2, '0')}-${_selectedFoodDate.day.toString().padLeft(2, '0')}';

  Future<void> _loadSavedCarbonRecords() async {
    if (ApiService.token == null) return;

    try {
      final records = await _apiService.getCarbonRecords('');
      if (!mounted) return;

      final todayStr = DateTime.now().toIso8601String().split('T').first;

      setState(() {
        _transportEmissions.clear();
        _officeEmissions.clear();
        _foodEmissions.clear();

        for (final record in records) {
          // Requirement: Only show user input for the current day
          final recordDate =
              record['record_date']?.toString() ??
              record['date']?.toString() ??
              record['created_at']?.toString();

          if (recordDate != null && !recordDate.startsWith(todayStr)) {
            continue;
          }

          final transportation = record['transportation'];
          final electricity = record['electricity'];
          final food = record['food'];

          if (transportation != null &&
              (double.tryParse(transportation.toString()) ?? 0) > 0) {
            final transportItem = record["transport_item"] ?? "Transportation";

            _transportEmissions.add(
              "$transportItem\n${transportation.toString()} kg CO₂e",
            );
          }

          if (electricity != null &&
              (double.tryParse(electricity.toString()) ?? 0) > 0) {
            final officeItem = record["office_item"] ?? "Office resource";

            _officeEmissions.add(
              "$officeItem\n${electricity.toString()} kg CO₂e",
            );
          }

          if (food != null && (double.tryParse(food.toString()) ?? 0) > 0) {
            final foodItem = record["food_item"] ?? "Food";

            _foodEmissions.add("$foodItem\n${food.toString()} kg CO₂e");
          }
        }
      });
    } catch (_) {
      // The screen remains usable when history cannot be loaded.
    }
  }

  Future<void> _saveCarbonRecords() async {
    if (_isSavingCarbonRecord) return;
    setState(() => _isSavingCarbonRecord = true);

    if (ApiService.token == null) {
      DialogHelper.showError(
        context: context,
        title: "Sign in required",
        message: "Please sign in again before saving your carbon record.",
      );
      return;
    }

    if (!await _verifyCampusPresence(showFeedback: true)) return;

    setState(() {
      _isSavingCarbonRecord = true;
    });

    showDialog<void>(
      context: context,
      barrierDismissible: false,
      builder: (dialogContext) => PopScope(
        canPop: false,
        child: const AlertDialog(
          content: Row(
            children: [
              CircularProgressIndicator(color: primaryGreen),
              SizedBox(width: 20),
              Expanded(
                child: Text(
                  'Saving your carbon emissions...',
                  style: TextStyle(fontWeight: FontWeight.w600),
                ),
              ),
            ],
          ),
        ),
      ),
    );

    final now = DateTime.now();
    var saved = false;
    try {
      await _apiService.addCarbonRecord(
        transportation: _transportationTotalEmission,
        electricity: _officeResourceTotalEmission,
        food: _foodTotalEmission,
        recordDate: now.toIso8601String().split('T').first,
        transportItem: _transportEmissions.isNotEmpty
            ? _transportEmissions.last
            : null,
        officeItem: _officeEmissions.isNotEmpty ? _officeEmissions.last : null,
        foodItem: _foodEmissions.isNotEmpty ? _foodEmissions.last : null,
        foodMealPeriod: _lastFoodMealPeriod,
        foodConsumedAt: _lastFoodConsumedAt?.toIso8601String(),
      );

      carbonScoreRefreshNotifier.value++;
      strategyRefreshNotifier.value++;
      saved = true;
      _generateSaveNotification();
    } catch (_) {
      saved = false;
    } finally {
      if (!mounted) return;
      Navigator.of(context, rootNavigator: true).pop();
      setState(() {
        _isSavingCarbonRecord = false;
      });
    }

    if (!mounted) return;
    if (saved) {
      DialogHelper.showCalculationSummary(
        context: context,
        transportEmissions: _transportEmissions,
        officeEmissions: _officeEmissions,
        foodEmissions: _foodEmissions,
      );
    } else {
      DialogHelper.showError(
        context: context,
        title: "Unable to Save",
        message: "Could not save your carbon record. Please try again.",
      );
    }
  }

  Future<void> _generateSaveNotification() async {
    try {
      final email = await ApiService.getCurrentUserEmail();
      if (email == null) return;

      final todayTotal =
          _transportationTotalEmission +
          _officeResourceTotalEmission +
          _foodTotalEmission;

      final recent = await _apiService.getCarbonRecords(email);

      await NotificationService.onCarbonRecordSaved(
        email: email,
        todayEmission: todayTotal,
        recentRecords: recent,
      );
    } catch (e) {
      print("Save notification error: $e");
    }
  }

  Future<void> _calculateDistance() async {
    if (_homeAddressController.text.trim().isEmpty || _selectedCampus == null) {
      DialogHelper.showWarning(
        context: context,
        title: "Incomplete Information",
        message:
            "Please enter your starting address and select a destination campus.",
      );
      return;
    }

    if (_distanceKm != null) return;

    setState(() {
      _isCalculatingDistance = true;
    });

    final campusAddress = campusAddresses[_selectedCampus];

    try {
      final route = await _locationService.calculateRoute(
        homeAddress: _homeAddressController.text,
        campusAddress: campusAddress!,
      );

      setState(() {
        _distanceKm = route.distanceKm;
        _routePoints = route.points;
        _isCalculatingDistance = false;
      });
    } catch (e) {
      setState(() {
        _isCalculatingDistance = false;
      });
      DialogHelper.showWarning(
        context: context,
        title: "Invalid Address",
        message: "Could not map the route. Please check your starting address.",
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return SafeArea(
      child: SingleChildScrollView(
        physics: const BouncingScrollPhysics(),
        padding: const EdgeInsets.fromLTRB(16, 16, 16, 100),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // 1. Transport Form Card
            _buildFormCard(
              title: 'Transportation',
              icon: Icons.directions_car_rounded,
              subtitle: 'Record how you traveled today.',
              children: [
                _buildDropdownField(
                  label: 'Transport Type',
                  hint: 'Select your mode of transportation',
                  value: _selectedTransportType,
                  items: const [
                    'Motorcycle (0.103 kg CO₂e/km)',
                    'Tricycle (0.095 kg C₂e/km)',
                    'Modern Jeepney (0.035 kg C₂e/km)',
                    'Traditional Jeepney (0.08 kg C₂e/km)',
                    'Private Car (Gasoline) (0.171 kg C₂e/km)',
                  ],
                  onChanged: (value) {
                    setState(() {
                      _selectedTransportType = value;
                    });
                  },
                ),

                const SizedBox(height: 14),

                _buildTextField(
                  label: "Starting Point",
                  hint: "Enter your starting address",
                  controller: _homeAddressController,
                  onChanged: (_) {
                    setState(() {
                      _distanceKm = null;
                      _routePoints = const [];
                    });
                  },
                ),

                const SizedBox(height: 14),

                _buildCampusField(),

                const SizedBox(height: 16),

                _buildDistancePreview(),

                const SizedBox(height: 12),

                SizedBox(
                  width: double.infinity,
                  child: ElevatedButton.icon(
                    onPressed: _isCalculatingDistance
                        ? null
                        : () async {
                            await _calculateDistance();
                          },
                    icon: _isCalculatingDistance
                        ? const SizedBox(
                            width: 18,
                            height: 18,
                            child: CircularProgressIndicator(
                              strokeWidth: 2,
                              color: Colors.white,
                            ),
                          )
                        : const Icon(Icons.route_rounded),
                    label: Text(
                      _isCalculatingDistance
                          ? "Calculating..."
                          : "Calculate Distance",
                    ),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: darkGreen,
                      foregroundColor: Colors.white,
                      elevation: 0,
                      padding: const EdgeInsets.symmetric(vertical: 15),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(14),
                      ),
                    ),
                  ),
                ),

                const SizedBox(height: 12),

                SizedBox(
                  width: double.infinity,
                  child: _buildAddButton(
                    onPressed: () {
                      if (_selectedTransportType == null ||
                          _distanceKm == null) {
                        DialogHelper.showWarning(
                          context: context,
                          title: "Incomplete Information",
                          message:
                              "Please select a transport type and calculate the distance before adding this activity.",
                        );
                        return;
                      }

                      final emission = _calculateTransportationEmission(
                        _selectedTransportType!,
                        _distanceKm!,
                      );

                      setState(() {
                        _transportationTotalEmission += emission;

                        _transportEmissions.add(
                          "$_selectedTransportType - ${emission.toStringAsFixed(2)} kg CO₂e",
                        );

                        _selectedTransportType = null;
                        _distanceKm = null;
                        _homeAddressController.clear();
                      });
                    },
                  ),
                ),
              ],
            ),

            const SizedBox(height: 16),

            // 2. Office Resource Form Card
            _buildFormCard(
              title: 'Office Resource',
              icon: Icons.devices_other_outlined,
              subtitle: 'Record the resources or appliances you used.',
              children: [
                _buildOfficeResourceDropdown(),

                const SizedBox(height: 14),

                _buildTextField(
                  label: "Hours Used",
                  hint: "Enter number of hours",
                  controller: _officeHoursController,
                  keyboardType: TextInputType.number,
                  numbersOnly: true,
                ),

                const SizedBox(height: 16),

                SizedBox(
                  width: double.infinity,
                  child: _buildAddButton(
                    onPressed: () {
                      if (_selectedOfficeResourceCategory == null) {
                        DialogHelper.showWarning(
                          context: context,
                          title: "Incomplete Information",
                          message:
                              "Please select an office resource or appliance before adding an emission.",
                        );
                        return;
                      }

                      if (_officeHoursController.text.isEmpty) {
                        DialogHelper.showWarning(
                          context: context,
                          title: "Incomplete Information",
                          message: "Please enter the number of hours used.",
                        );
                        return;
                      }

                      final hours = double.tryParse(
                        _officeHoursController.text.trim(),
                      );
                      if (hours == null || hours <= 0) {
                        DialogHelper.showWarning(
                          context: context,
                          title: "Invalid Hours",
                          message: "Enter a number greater than zero.",
                        );
                        return;
                      }

                      final emission = _calculateOfficeResourceEmission(
                        _selectedOfficeResourceCategory!,
                        hours,
                      );

                      setState(() {
                        _officeEmissions.add(
                          '${_selectedOfficeResourceCategory!}\n'
                          '$hours hour(s)\n'
                          '${emission.toStringAsFixed(2)} kg CO₂e',
                        );

                        _officeResourceTotalEmission += emission;

                        _selectedOfficeResourceCategory = null;
                        _officeHoursController.clear();
                      });
                    },
                  ),
                ),
              ],
            ),

            const SizedBox(height: 16),

            // 3. Food Consumption Form Card
            _buildFormCard(
              title: 'Food Consumption',
              icon: Icons.restaurant_outlined,
              subtitle: 'Record what you consumed today.',
              children: [
                _buildDropdownField(
                  label: 'Meal Period',
                  hint: 'Select meal period',
                  value: _selectedMealPeriod,
                  items: const ['Breakfast', 'Lunch', 'Dinner', 'Snack'],
                  onChanged: (value) {
                    setState(() {
                      _selectedMealPeriod = value;
                    });
                  },
                ),

                const SizedBox(height: 14),

                _buildFoodDropdown(),

                const SizedBox(height: 14),

                Row(
                  children: [
                    Expanded(
                      child: Opacity(
                        opacity: 0.7,
                        child: OutlinedButton.icon(
                          onPressed: null,
                          icon: const Icon(Icons.calendar_today_outlined),
                          label: Text(_selectedFoodDateLabel),
                        ),
                      ),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: OutlinedButton.icon(
                        onPressed: _pickFoodTime,
                        icon: const Icon(Icons.access_time_outlined),
                        label: Text(_selectedFoodTime.format(context)),
                      ),
                    ),
                  ],
                ),

                const SizedBox(height: 14),

                _buildTextField(
                  label: 'Amount / Serving (Cups)',
                  hint: 'e.g., 1 cup',
                  controller: _servingSizeController,
                  keyboardType: const TextInputType.numberWithOptions(
                    decimal: true,
                  ),
                  numbersOnly: true,
                ),

                const SizedBox(height: 16),

                SizedBox(
                  width: double.infinity,
                  child: _buildAddButton(
                    onPressed: () {
                      if (_selectedFoodItem == null ||
                          _selectedMealPeriod == null) {
                        DialogHelper.showWarning(
                          context: context,
                          title: 'Incomplete Information',
                          message:
                              'Please select a food item and meal period before adding an emission.',
                        );
                        return;
                      }

                      final amount = double.tryParse(
                        _servingSizeController.text.trim(),
                      );

                      if (amount == null || amount <= 0) {
                        DialogHelper.showWarning(
                          context: context,
                          title: 'Invalid Amount',
                          message: 'Enter a number greater than zero.',
                        );
                        return;
                      }

                      final emission = _calculateFoodEmission(
                        _selectedFoodItem!,
                        amount,
                      );

                      final unit = amount == 1 ? 'cup' : 'cups';

                      setState(() {
                        _foodEmissions.add(
                          '${_selectedFoodItem!}\n'
                          '$_selectedMealPeriod • '
                          '$_selectedFoodDateLabel '
                          '${_selectedFoodTime.format(context)}\n'
                          '$amount $unit\n'
                          '${emission.toStringAsFixed(2)} kg CO₂e',
                        );

                        _foodTotalEmission += emission;

                        _lastFoodMealPeriod = _selectedMealPeriod;
                        _lastFoodConsumedAt = _selectedFoodDateTime;

                        _selectedFoodItem = null;
                        _selectedMealPeriod = null;
                        _servingSizeController.clear();
                      });
                    },
                  ),
                ),
              ],
            ),

            const SizedBox(height: 24),

            // 4. Your Carbon Emissions List Section
            const Align(
              alignment: Alignment.centerLeft,
              child: Text(
                'Your Added Activities',
                style: TextStyle(
                  fontSize: 19,
                  fontWeight: FontWeight.bold,
                  color: darkGreen,
                ),
              ),
            ),

            const SizedBox(height: 4),

            const Text(
              'Review the activities you have recorded for today.',
              style: TextStyle(fontSize: 13, color: Colors.black54),
            ),

            const SizedBox(height: 12),

            _buildCombinedActivityList(),

            const SizedBox(height: 24),

            // POP-UP LOGIC
            GestureDetector(
              onTap: _isSavingCarbonRecord
                  ? null
                  : () async {
                      if (_transportEmissions.isEmpty &&
                          _officeEmissions.isEmpty &&
                          _foodEmissions.isEmpty) {
                        DialogHelper.showWarning(
                          context: context,
                          title: "Incomplete Information",
                          message:
                              "Please add at least one emission to calculate.",
                        );
                        return;
                      }

                      await _saveCarbonRecords();
                    },
              child: Container(
                width: double.infinity,
                padding: const EdgeInsets.symmetric(vertical: 16),
                decoration: BoxDecoration(
                  color: primaryGreen,
                  borderRadius: BorderRadius.circular(10),
                  boxShadow: [
                    BoxShadow(
                      color: Colors.black.withValues(alpha: 0.08),
                      blurRadius: 4,
                      offset: const Offset(0, 2),
                    ),
                  ],
                ),
                child: Center(
                  child: _isSavingCarbonRecord
                      ? const SizedBox(
                          width: 20,
                          height: 20,
                          child: CircularProgressIndicator(
                            color: Colors.white,
                            strokeWidth: 2,
                          ),
                        )
                      : const Text(
                          'Calculate my Carbon Emissions',
                          style: TextStyle(
                            color: Colors.white,
                            fontSize: 15,
                            fontWeight: FontWeight.bold,
                            letterSpacing: 0.3,
                          ),
                        ),
                ),
              ),
            ),

            const SizedBox(height: 16),
          ],
        ),
      ),
    );
  }

  // Component Builders
  Widget _buildFormCard({
    required String title,
    required IconData icon,
    required String subtitle,
    required List<Widget> children,
  }) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: const Color(0xFFE5EEE8)),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.03),
            blurRadius: 10,
            offset: const Offset(0, 3),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                padding: const EdgeInsets.all(10),
                decoration: BoxDecoration(
                  color: primaryGreen.withValues(alpha: 0.12),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Icon(icon, color: primaryGreen, size: 22),
              ),

              const SizedBox(width: 12),

              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      title,
                      style: const TextStyle(
                        fontSize: 18,
                        fontWeight: FontWeight.bold,
                        color: Color(0xFF1F2933),
                      ),
                    ),

                    const SizedBox(height: 2),

                    Text(
                      subtitle,
                      style: const TextStyle(
                        fontSize: 12,
                        color: Colors.black54,
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),

          const SizedBox(height: 20),

          ...children,
        ],
      ),
    );
  }

  Widget _buildOfficeResourceDropdown() {
    final dropdownItems = <DropdownMenuItem<String>>[];

    for (final entry in officeResourceGroups.entries) {
      dropdownItems.add(
        DropdownMenuItem<String>(
          enabled: false,
          value: null,
          child: Padding(
            padding: const EdgeInsets.only(top: 6, bottom: 2),
            child: Text(
              entry.key,
              style: const TextStyle(
                fontSize: 11,
                fontWeight: FontWeight.bold,
                color: darkGreen,
              ),
            ),
          ),
        ),
      );

      for (final item in entry.value) {
        dropdownItems.add(
          DropdownMenuItem<String>(
            value: item,
            child: Padding(
              padding: const EdgeInsets.only(left: 8),
              child: Text(
                item,
                style: const TextStyle(fontSize: 11, color: Colors.black87),
              ),
            ),
          ),
        );
      }
    }

    final safeValue =
        officeResourcePowerRatings.containsKey(_selectedOfficeResourceCategory)
        ? _selectedOfficeResourceCategory
        : null;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text(
          'Office Resource / Appliance',
          style: TextStyle(
            fontSize: 11,
            fontWeight: FontWeight.bold,
            color: primaryGreen,
          ),
          overflow: TextOverflow.ellipsis,
        ),
        const SizedBox(height: 6),
        Container(
          height: 38,
          padding: const EdgeInsets.symmetric(horizontal: 8),
          decoration: BoxDecoration(
            border: Border.all(color: Colors.black38, width: 1),
            borderRadius: BorderRadius.circular(6),
          ),
          child: DropdownButtonHideUnderline(
            child: DropdownButton<String>(
              value: safeValue,
              hint: const Text(
                'Select Appliance/Hardware',
                style: TextStyle(fontSize: 10, color: Colors.black38),
              ),
              isExpanded: true,
              menuMaxHeight: 420,
              icon: const Icon(
                Icons.keyboard_arrow_down,
                color: Colors.black,
                size: 18,
              ),
              style: const TextStyle(fontSize: 11, color: Colors.black87),
              onChanged: (value) {
                if (value == null) return;

                setState(() {
                  _selectedOfficeResourceCategory = value;
                });
              },
              items: dropdownItems,
            ),
          ),
        ),
      ],
    );
  }

  Widget _buildFoodDropdown() {
    final dropdownItems = <DropdownMenuItem<String>>[];

    final foodGroups = <String, List<String>>{
      '1. High-Impact Proteins (Red Meats)': [
        'Beef (Beef Herd) (60.0 kg CO2e/kg)',
        'Lamb & Mutton (24.5 kg CO2e/kg)',
        'Beef (Dairy Herd) (21.1 kg CO2e/kg)',
      ],
      '2. Moderate-Impact Proteins (Dairy & Poultry)': [
        'Cheese (21.0 kg CO2e/kg)',
        'Pork (7.0 kg CO2e/kg)',
        'Poultry (Chicken / Turkey) (6.0 kg CO2e/kg)',
        'Eggs (4.5 kg CO2e/kg)',
        'Fish (Farmed) (5.0 kg CO2e/kg)',
      ],
      '3. Staples and Plant-Based Proteins': [
        'Rice (Flooded) (4.4 kg CO2e/kg)',
        'Tofu (Soy-based) (3.0 kg CO2e/kg)',
        'Groundnuts / Peanuts (2.5 kg CO2e/kg)',
        'Pulses (Beans / Peas) (1.5 kg CO2e/kg)',
      ],
      '4. Grains, Vegetables, and Fruits': [
        'Wheat & Rye (Bread) (1.4 kg CO2e/kg)',
        'Maize (Corn) (1.0 kg CO2e/kg)',
        'Potatoes (0.5 kg CO2e/kg)',
        'Apples / Bananas (0.4 kg CO2e/kg)',
        'Root Vegetables (0.4 kg CO2e/kg)',
      ],
      '5. Beverages and Discretionary Items': [
        'Coffee (22.0 kg CO2e/kg)',
        'Dark Chocolate (19.0 kg CO2e/kg)',
        'Milk (Bovine) (3.2 kg CO2e/liter)',
        'Soy Milk (1.0 kg CO2e/liter)',
      ],
    };

    for (final entry in foodGroups.entries) {
      dropdownItems.add(
        DropdownMenuItem<String>(
          enabled: false,
          value: null,
          child: Padding(
            padding: const EdgeInsets.only(top: 6, bottom: 2),
            child: Text(
              entry.key,
              style: const TextStyle(
                fontSize: 11,
                fontWeight: FontWeight.bold,
                color: darkGreen,
              ),
            ),
          ),
        ),
      );

      for (final item in entry.value) {
        dropdownItems.add(
          DropdownMenuItem<String>(
            value: item,
            child: Padding(
              padding: const EdgeInsets.only(left: 8),
              child: Text(
                item,
                style: const TextStyle(fontSize: 11, color: Colors.black87),
              ),
            ),
          ),
        );
      }
    }

    final safeValue = foodEmissionFactors.containsKey(_selectedFoodItem)
        ? _selectedFoodItem
        : null;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text(
          'Food Type',
          style: TextStyle(
            fontSize: 11,
            fontWeight: FontWeight.bold,
            color: primaryGreen,
          ),
        ),
        const SizedBox(height: 6),
        Container(
          height: 38,
          padding: const EdgeInsets.symmetric(horizontal: 8),
          decoration: BoxDecoration(
            border: Border.all(color: Colors.black38, width: 1),
            borderRadius: BorderRadius.circular(6),
          ),
          child: DropdownButtonHideUnderline(
            child: DropdownButton<String>(
              value: safeValue,
              hint: const Text(
                'Select Food Item',
                style: TextStyle(fontSize: 10, color: Colors.black38),
              ),
              isExpanded: true,
              menuMaxHeight: 420,
              icon: const Icon(
                Icons.keyboard_arrow_down,
                color: Colors.black,
                size: 18,
              ),
              style: const TextStyle(fontSize: 11, color: Colors.black87),
              onChanged: (value) {
                if (value == null) return;

                setState(() {
                  _selectedFoodItem = value;
                });
              },
              items: dropdownItems,
            ),
          ),
        ),
      ],
    );
  }

  Widget _buildDropdownField({
    required String label,
    required String hint,
    required String? value,
    required List<String> items,
    required ValueChanged<String?> onChanged,
  }) {
    final uniqueItems = items.toSet().toList();

    final safeValue = value != null && uniqueItems.contains(value)
        ? value
        : null;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          label,
          style: const TextStyle(
            fontSize: 11,
            fontWeight: FontWeight.bold,
            color: Color(0xFF3AA76D),
          ),
          overflow: TextOverflow.ellipsis,
        ),
        const SizedBox(height: 6),
        Container(
          height: 38,
          padding: const EdgeInsets.symmetric(horizontal: 8),
          decoration: BoxDecoration(
            border: Border.all(color: Colors.black38, width: 1),
            borderRadius: BorderRadius.circular(6),
          ),
          child: DropdownButtonHideUnderline(
            child: DropdownButton<String>(
              value: safeValue,
              hint: Text(
                hint,
                style: const TextStyle(fontSize: 10, color: Colors.black38),
              ),
              isExpanded: true,
              icon: const Icon(
                Icons.keyboard_arrow_down,
                color: Colors.black,
                size: 18,
              ),
              style: const TextStyle(fontSize: 11, color: Colors.black87),
              onChanged: onChanged,
              items: uniqueItems.map<DropdownMenuItem<String>>((String val) {
                return DropdownMenuItem<String>(value: val, child: Text(val));
              }).toList(),
            ),
          ),
        ),
      ],
    );
  }

  Widget _buildTextField({
    required String label,
    required String hint,
    required TextEditingController controller,
    bool enabled = true,
    bool numbersOnly = false,
    TextInputType keyboardType = TextInputType.text,
    ValueChanged<String>? onChanged,
  }) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          label,
          style: const TextStyle(
            fontSize: 11,
            fontWeight: FontWeight.bold,
            color: Color(0xFF3AA76D),
          ),
          overflow: TextOverflow.ellipsis,
        ),
        const SizedBox(height: 6),
        SizedBox(
          height: 52,
          child: TextField(
            controller: controller,
            enabled: enabled,
            onChanged: onChanged,
            keyboardType: keyboardType,
            inputFormatters: numbersOnly
                ? [FilteringTextInputFormatter.allow(RegExp(r'^\d*\.?\d*$'))]
                : [],
            decoration: InputDecoration(
              hintText: hint,
              hintStyle: const TextStyle(fontSize: 13, color: Colors.black38),
              contentPadding: const EdgeInsets.symmetric(
                horizontal: 14,
                vertical: 14,
              ),
              border: OutlineInputBorder(
                borderRadius: BorderRadius.circular(12),
                borderSide: const BorderSide(color: Colors.black38, width: 1),
              ),
              enabledBorder: OutlineInputBorder(
                borderRadius: BorderRadius.circular(12),
                borderSide: const BorderSide(color: Colors.black38, width: 1),
              ),
            ),
          ),
        ),
      ],
    );
  }

  Widget _buildAddButton({required FutureOr<void> Function() onPressed}) {
    return SizedBox(
      width: double.infinity,
      height: 50,
      child: ElevatedButton.icon(
        onPressed: _isCheckingCampus
            ? null
            : () async {
                if (!await _verifyCampusPresence(showFeedback: true)) return;
                await onPressed();
              },
        icon: const Icon(Icons.add_circle_outline_rounded, size: 20),
        label: const Text(
          'Add Activity',
          style: TextStyle(fontSize: 14, fontWeight: FontWeight.bold),
        ),
        style: ElevatedButton.styleFrom(
          backgroundColor: const Color(0xFFE1F2E7),
          foregroundColor: darkGreen,
          elevation: 0,
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(14),
          ),
        ),
      ),
    );
  }

  Widget _buildCampusField() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text(
          "Destination Campus",
          style: TextStyle(
            fontSize: 11,
            fontWeight: FontWeight.bold,
            color: Color(0xFF3AA76D),
          ),
        ),
        const SizedBox(height: 6),
        Container(
          height: 52,
          padding: const EdgeInsets.symmetric(horizontal: 8),
          decoration: BoxDecoration(
            border: Border.all(color: Colors.black38, width: 1),
            borderRadius: BorderRadius.circular(12),
          ),
          child: DropdownButtonHideUnderline(
            child: DropdownButton<String>(
              value: _selectedCampus,
              hint: const Text(
                'Select BatStateU Campus Destination',
                style: TextStyle(fontSize: 13, color: Colors.black38),
              ),
              isExpanded: true,
              icon: const Icon(
                Icons.keyboard_arrow_down,
                color: Colors.black,
                size: 18,
              ),
              style: const TextStyle(fontSize: 14, color: Colors.black87),
              onChanged: (String? newValue) {
                setState(() {
                  _selectedCampus = newValue;
                  _distanceKm = null;
                  _routePoints = const [];
                });
              },
              items: campusAddresses.keys.map<DropdownMenuItem<String>>((
                String campus,
              ) {
                return DropdownMenuItem<String>(
                  value: campus,
                  child: Text(campus),
                );
              }).toList(),
            ),
          ),
        ),
      ],
    );
  }

  Widget _buildDistancePreview() {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: _distanceKm == null
            ? const Color(0xFFF8FBF9)
            : const Color(0xFFE8F6ED),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(
          color: _distanceKm == null
              ? const Color(0xFFD9E8DE)
              : primaryGreen.withValues(alpha: 0.4),
        ),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                padding: const EdgeInsets.all(10),
                decoration: BoxDecoration(
                  color: primaryGreen.withValues(alpha: 0.12),
                  shape: BoxShape.circle,
                ),
                child: const Icon(
                  Icons.map_outlined,
                  color: primaryGreen,
                  size: 20,
                ),
              ),
              const SizedBox(width: 12),
              const Expanded(
                child: Text(
                  "Map Distance Window",
                  style: TextStyle(
                    fontSize: 14,
                    fontWeight: FontWeight.bold,
                    color: darkGreen,
                  ),
                ),
              ),
              if (_distanceKm != null)
                Container(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 8,
                    vertical: 4,
                  ),
                  decoration: BoxDecoration(
                    color: primaryGreen,
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: const Text(
                    "Route Computed",
                    style: TextStyle(
                      color: Colors.white,
                      fontSize: 10,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                ),
            ],
          ),
          if (_routePoints.isNotEmpty) ...[
            const SizedBox(height: 12),
            ClipRRect(
              borderRadius: BorderRadius.circular(12),
              child: SizedBox(
                height: 180,
                child: FlutterMap(
                  options: MapOptions(
                    initialCenter: _routePoints[_routePoints.length ~/ 2],
                    initialZoom: 13,
                  ),
                  children: [
                    TileLayer(
                      urlTemplate:
                          'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
                      userAgentPackageName: 'com.example.carbonwise_app',
                    ),
                    PolylineLayer(
                      polylines: [
                        Polyline(
                          points: _routePoints,
                          strokeWidth: 4,
                          color: primaryGreen,
                        ),
                      ],
                    ),
                    MarkerLayer(
                      markers: [
                        Marker(
                          point: _routePoints.first,
                          width: 36,
                          height: 36,
                          child: const Icon(Icons.home, color: darkGreen),
                        ),
                        Marker(
                          point: _routePoints.last,
                          width: 36,
                          height: 36,
                          child: const Icon(Icons.school, color: primaryGreen),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
            ),
          ],
          const Divider(height: 20),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              const Text(
                "Calculated Road Distance:",
                style: TextStyle(fontSize: 13, color: Colors.black54),
              ),
              Text(
                _distanceKm == null
                    ? "0.00 km"
                    : "${_distanceKm!.toStringAsFixed(2)} km",
                style: const TextStyle(
                  fontSize: 18,
                  fontWeight: FontWeight.bold,
                  color: darkGreen,
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildCombinedActivityList() {
    final List<Widget> activities = [];

    // Transportation
    for (final activity in _transportEmissions) {
      activities.add(
        _buildActivityItem(
          icon: Icons.directions_car_rounded,
          iconColor: const Color(0xFF3AA76D),
          category: 'Transportation',
          activity: activity,
        ),
      );
    }

    // Office Resource
    for (final activity in _officeEmissions) {
      activities.add(
        _buildActivityItem(
          icon: Icons.devices_other_rounded,
          iconColor: const Color(0xFF4F7CAC),
          category: 'Office Resource',
          activity: activity,
        ),
      );
    }

    // Food Consumption
    for (final activity in _foodEmissions) {
      activities.add(
        _buildActivityItem(
          icon: Icons.restaurant_rounded,
          iconColor: const Color(0xFFE38B3D),
          category: 'Food Consumption',
          activity: activity,
        ),
      );
    }

    if (activities.isEmpty) {
      return Container(
        width: double.infinity,
        padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 28),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(18),
          border: Border.all(color: const Color(0xFFE5EEE8)),
        ),
        child: const Column(
          children: [
            Icon(
              Icons.receipt_long_outlined,
              size: 42,
              color: Color(0xFF9CA3AF),
            ),
            SizedBox(height: 10),
            Text(
              'No activities added for today yet',
              style: TextStyle(
                fontSize: 15,
                fontWeight: FontWeight.bold,
                color: Color(0xFF1F2933),
              ),
            ),
            SizedBox(height: 4),
            Text(
              'Your recorded activities for today will appear here.',
              textAlign: TextAlign.center,
              style: TextStyle(fontSize: 13, color: Colors.black54),
            ),
          ],
        ),
      );
    }

    return Column(children: activities);
  }

  Widget _buildActivityItem({
    required IconData icon,
    required Color iconColor,
    required String category,
    required String activity,
  }) {
    final parts = activity.split('\n');

    String title = '';
    String details = '';
    String emission = '';

    if (category == 'Transportation') {
      if (parts.isNotEmpty) {
        final firstLine = parts[0];
        final emissionIndex = firstLine.lastIndexOf(' - ');

        if (emissionIndex != -1) {
          title = firstLine.substring(0, emissionIndex);
          emission = firstLine.substring(emissionIndex + 3);
        } else {
          title = firstLine;
        }
      }

      details = category;
    } else {
      title = parts.isNotEmpty ? parts[0] : '';
      details = parts.length > 1 ? parts[1] : '';
      emission = parts.length > 2 ? parts[2] : '';
    }

    return Container(
      width: double.infinity,
      margin: const EdgeInsets.only(bottom: 10),
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFFE5EEE8)),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.025),
            blurRadius: 8,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Row(
        children: [
          Container(
            width: 44,
            height: 44,
            decoration: BoxDecoration(
              color: iconColor.withValues(alpha: 0.12),
              borderRadius: BorderRadius.circular(13),
            ),
            child: Icon(icon, color: iconColor, size: 23),
          ),

          const SizedBox(width: 12),

          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  title,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(
                    fontSize: 14,
                    fontWeight: FontWeight.bold,
                    color: Color(0xFF1F2933),
                  ),
                ),

                const SizedBox(height: 3),

                Text(
                  details.isEmpty ? category : '$category · $details',
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(fontSize: 12, color: Colors.black54),
                ),
              ],
            ),
          ),

          const SizedBox(width: 8),

          Text(
            emission,
            textAlign: TextAlign.right,
            style: const TextStyle(
              fontSize: 12,
              fontWeight: FontWeight.bold,
              color: darkGreen,
            ),
          ),
        ],
      ),
    );
  }
}

class _CampusCheckException implements Exception {
  const _CampusCheckException(this.message);

  final String message;
}
