import 'package:flutter/material.dart';
import 'package:supabase_flutter/supabase_flutter.dart';
import 'package:carbonwise_app/services/api_service.dart';
import 'package:carbonwise_app/utils/dialog_helper.dart';
import 'package:carbonwise_app/services/location_service.dart';
import 'package:carbonwise_app/utils/strategy_notifier.dart';
import 'package:flutter/services.dart';

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
  String? _selectedOfficeResourceType;
  String? _selectedOfficeResourceCategory;
  String? _selectedFoodType;
  String? _selectedFoodCategory;
  String _userCampus = "";

  double _transportationTotalEmission = 0.0;
  double _officeResourceTotalEmission = 0.0;
  double _foodTotalEmission = 0.0;

  // 🟢 Lists to store added emissions dynamically
  final List<String> _transportEmissions = [];
  final List<String> _officeEmissions = [];
  final List<String> _foodEmissions = [];
  final LocationService _locationService = LocationService();
  double? _distanceKm;
  bool _isCalculatingDistance = false;

  final TextEditingController _homeAddressController = TextEditingController();
  final TextEditingController _officeUsageController = TextEditingController();
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
    'Traditional Jeepney': 0.18,
    'Modern Jeepney': 0.09,
    'Car': 0.21,
    'Motorcycle': 0.10,
    'Bicycle/Walking': 0.0,
  };

  final Map<String, double> officeResourcePowerRatings = {
    'Window Type': 1500,
    'Split-Type (Wall-Mounted)': 1800,
    'Ceiling Cassette / Ceiling Suspended': 3000,
    'Floor Standing (Tower)': 5300,
    'AC Motor Fan': 65,
    'DC Motor Fan': 30,
    'Ceiling Fan': 80,
    'Stand Fan': 60,
    'Wall Fan': 55,
    'Exhaust Fan': 30,
    'Tower Fan': 50,
    'Desk Fan': 40,
    'Bladeless Fan': 55,
    'Misting Fan': 130,
    'Industrial Fan': 200,
    'LED (Light Emitting Diode)': 15,
    'Fluorescent': 40,
    'Incandescent': 60,
    'Standard DLP/LDC Projector': 300,
    'Eco Mode': 200,
    'Large Venue Projector (Auditoriums)': 700,
    'Standby': 5,
    'Inkjet Printer (Desktop)': 30,
    'Laser Printer (B&W)': 400,
    'Color Laser Printer': 500,
    'Mid-size Office MFP': 800,
    'High-volume Photocopier': 1500,
    'Ultra-light/Notebook': 45,
    'Standard Business Laptop': 60,
    'Performance Laptop': 120,
    'Gaming/High-End Workstation': 200,
    'Standard Office PC': 200,
    'Mid-range Workstation': 350,
    'High-end/Gaming PC': 500,
    'Mini PC (NUC/MAC Mini)': 50,
    '18.5" to 20" LED Monitor': 20,
    '22" to 24" LED Monitor': 30,
    '27" and Larger': 50,
    'OLD CRTS Monitor (Big Box Style)': 100,
    '55" to 65"': 120,
    '75"': 180,
    '86"': 250,
    '98" and above': 400,
    'Desktop/PC Speakers': 20,
    'Wall-mounted Classroom Speakers': 60,
    'Large PA System (Events/Gyms)': 1000,
  };

  final Map<String, double> officeResourceEmissionFactors = {
    'Window Type': 0.80,
    'Split-Type (Wall-Mounted)': 0.95,
    'Ceiling Cassette / Ceiling Suspended': 1.50,
    'Floor Standing (Tower)': 2.50,

    'AC Motor Fan': 0.03,
    'DC Motor Fan': 0.02,
    'Ceiling Fan': 0.04,
    'Stand Fan': 0.03,
    'Wall Fan': 0.03,
    'Exhaust Fan': 0.02,
    'Tower Fan': 0.03,
    'Desk Fan': 0.02,
    'Bladeless Fan': 0.03,
    'Misting Fan': 0.06,
    'Industrial Fan': 0.10,

    'LED (Light Emitting Diode)': 0.01,
    'Fluorescent': 0.02,
    'Incandescent': 0.03,

    'Standard DLP/LDC Projector': 0.15,
    'Eco Mode': 0.10,
    'Large Venue Projector (Auditoriums)': 0.35,
    'Standby': 0.001,

    'Inkjet Printer (Desktop)': 0.02,
    'Laser Printer (B&W)': 0.15,
    'Color Laser Printer': 0.18,
    'Mid-size Office MFP': 0.25,
    'High-volume Photocopier': 0.50,

    'Ultra-light/Notebook': 0.02,
    'Standard Business Laptop': 0.03,
    'Performance Laptop': 0.06,
    'Gaming/High-End Workstation': 0.10,

    'Standard Office PC': 0.10,
    'Mid-range Workstation': 0.18,
    'High-end/Gaming PC': 0.25,
    'Mini PC (NUC/MAC Mini)': 0.02,

    '18.5" to 20" LED Monitor': 0.01,
    '22" to 24" LED Monitor': 0.02,
    '27" and Larger': 0.03,
    'OLD CRTS Monitor (Big Box Style)': 0.05,

    '55" to 65"': 0.06,
    '75"': 0.09,
    '86"': 0.12,
    '98" and above': 0.18,

    'Desktop/PC Speakers': 0.01,
    'Wall-mounted Classroom Speakers': 0.03,
    'Large PA System (Events/Gyms)': 0.50,
  };

  final Map<String, double> foodEmissionFactors = {
    'Beef (Beef Herd)': 60.0,
    'Lamb & Mutton': 24.5,
    'Beef (Dairy Herd)': 21.1,
    'Cheese': 21.0,
    'Pork': 7.0,
    'Poultry (Chicken/Turkey)': 6.0,
    'Eggs': 4.5,
    'Fish (Farmed)': 5.0,
    'Rice (Flooded)': 4.5,
    'Tofu (Soy-based)': 3.0,
    'Groundnuts/Peanuts': 2.5,
    'Pulses (Beans/Pease)': 2.0,
    'Wheat & Rye (Bread)': 1.4,
    'Maize (Corn)': 1.0,
    'Potatoes': 0.5,
    'Apples/Bananas': 0.4,
    'Root Vegetables': 0.4,
    'Other Fruits & Vegetables': 0.2,
    'Coffee': 28.0,
    'Dark Chocolate': 19.0,
    'Milk (Bovine)': 3.2,
    'Soy Milk': 1.0,
  };

  @override
  void initState() {
    super.initState();
    _loadSavedCarbonRecords();
    _loadUserCampus();
  }

  @override
  void dispose() {
    _homeAddressController.dispose();
    _officeUsageController.dispose();
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
    return (power * hours / 1000) * 0.527;
  }

  double _calculateFoodEmission(String foodCategory) {
    return foodEmissionFactors[foodCategory] ?? 0.0;
  }

  List<String> _getFoodCategories(String? foodType) {
    switch (foodType) {
      case 'Red Meat':
        return ['Beef (Beef Herd)', 'Lamb & Mutton', 'Beef (Dairy Herd)'];
      case 'Dairy & Poultry':
        return [
          'Cheese',
          'Pork',
          'Poultry (Chicken/Turkey)',
          'Eggs',
          'Fish (Farmed)',
        ];
      case 'Staples & Plant-based Proteins':
        return [
          'Rice (Flooded)',
          'Tofu (Soy-based)',
          'Groundnuts/Peanuts',
          'Pulses (Beans/Pease)',
        ];
      case 'Grains, Vegetables, and Fruits':
        return [
          'Wheat & Rye (Bread)',
          'Maize (Corn)',
          'Potatoes',
          'Apples/Bananas',
          'Root Vegetables',
          'Other Fruits & Vegetables',
        ];
      case 'Beverages and Discretionary Items':
        return ['Coffee', 'Dark Chocolate', 'Milk (Bovine)', 'Soy Milk'];
      default:
        return [];
    }
  }

  List<String> _getOfficeResourceCategories(String? officeResourceType) {
    switch (officeResourceType) {
      case 'Air Conditioner':
        return [
          'Window Type',
          'Split-Type (Wall-Mounted)',
          'Ceiling Cassette / Ceiling Suspended',
          'Floor Standing (Tower)',
        ];
      case 'Electric Fan':
        return [
          'AC Motor Fan',
          'DC Motor Fan',
          'Ceiling Fan',
          'Stand Fan',
          'Wall Fan',
          'Exhaust Fan',
          'Tower Fan',
          'Desk Fan',
          'Bladeless Fan',
          'Misting Fan',
          'Industrial Fan',
        ];
      case 'Lights':
        return ['LED (Light Emitting Diode)', 'Fluorescent', 'Incandescent'];
      case 'Projector':
        return [
          'Standard DLP/LDC Projector',
          'Eco Mode',
          'Large Venue Projector (Auditoriums)',
          'Standby',
        ];
      case 'Printer (Laser)':
        return [
          'Inkjet Printer (Desktop)',
          'Laser Printer (B&W)',
          'Color Laser Printer',
        ];
      case 'Photocopier / Multifunction Printer':
        return ['Mid-size Office MFP', 'High-volume Photocopier'];
      case 'Laptop':
        return [
          'Ultra-light/Notebook',
          'Standard Business Laptop',
          'Performance Laptop',
          'Gaming/High-End Workstation',
        ];
      case 'Desktop Computer (CPU + Monitor)':
      case 'Scanner':
        return [
          'Standard Office PC',
          'Mid-range Workstation',
          'High-end/Gaming PC',
          'Mini PC (NUC/MAC Mini)',
          '18.5" to 20" LED Monitor',
          '22" to 24" LED Monitor',
          '27" and Larger',
          'OLD CRTS Monitor (Big Box Style)',
        ];
      case 'Viewboard / Smart Screen':
        return ['55" to 65"', '75"', '86"', '98" and above'];
      case 'Sound Speaker':
        return [
          'Desktop/PC Speakers',
          'Wall-mounted Classroom Speakers',
          'Large PA System (Events/Gyms)',
        ];
      default:
        return [];
    }
  }

  Future<void> _loadSavedCarbonRecords() async {
    final user = Supabase.instance.client.auth.currentUser;

    if (user == null || user.email == null) return;

    final records = await _apiService.getCarbonRecords(user.email!);

    setState(() {
      _transportEmissions.clear();
      _officeEmissions.clear();
      _foodEmissions.clear();

      for (final record in records) {
        final transportation = record['transportation'];
        final electricity = record['electricity'];
        final food = record['food'];

        if (transportation != null &&
            transportation.toString() != "0" &&
            transportation.toString() != "0.0") {
          final transportItem = record["transport_item"] ?? "";

          _transportEmissions.add(
            "$transportItem\n${transportation.toString()} kg CO₂e",
          );
        }

        if (electricity != null &&
            electricity.toString() != "0" &&
            electricity.toString() != "0.0") {
          final officeItem = record["office_item"] ?? "";

          _officeEmissions.add(
            "$officeItem\n${electricity.toString()} kg CO₂e",
          );
        }

        if (food != null &&
            food.toString() != "0" &&
            food.toString() != "0.0") {
          final foodItem = record["food_item"] ?? "";

          _foodEmissions.add("$foodItem\n${food.toString()} kg CO₂e");
        }
      }
    });
  }

  Future<void> _saveCarbonRecords() async {
    final user = Supabase.instance.client.auth.currentUser;

    if (user == null) return;

    final now = DateTime.now();

    final totalEmission =
        _transportationTotalEmission +
        _officeResourceTotalEmission +
        _foodTotalEmission;

    final transportItem = _transportEmissions.isNotEmpty
        ? _transportEmissions.last.split(" - ").first
        : "";

    final officeItem = _officeEmissions.isNotEmpty
        ? _officeEmissions.last.split("\n").first
        : "";

    final foodItem = _foodEmissions.isNotEmpty
        ? _foodEmissions.last.split("(").first.trim()
        : "";

    try {
      await _apiService.addCarbonRecord(
        email: user.email!,
        transportation: _transportationTotalEmission,
        electricity: _officeResourceTotalEmission,
        food: _foodTotalEmission,
        totalEmission: totalEmission,
        recordDate: now.toIso8601String().split('T').first,
        createdAt: now.toIso8601String(),
        transportItem: transportItem,
        officeItem: officeItem,
        foodItem: foodItem,
      );

      await _apiService.addNotification(
        email: user.email!,
        title: "Carbon Record Saved",
        message: "Your carbon emission record has been saved successfully.",
        type: "success",
      );

      // Refresh AI Sustainability Coach immediately
      strategyRefreshNotifier.value++;
      print("Notifier sent!");

      DialogHelper.showSuccess(
        context: context,
        title: "Calculation Complete",
        message: "Your carbon emission record has been saved successfully.",
        onOk: () {
          DialogHelper.showCalculationSummary(
            context: context,
            transportEmissions: _transportEmissions,
            officeEmissions: _officeEmissions,
            foodEmissions: _foodEmissions,
          );
        },
      );
    } catch (e) {
      DialogHelper.showError(
        context: context,
        title: "Unable to Save",
        message:
            "Something went wrong while saving your carbon emission record. Please try again.",
      );
    }
  }

  Future<void> _calculateDistance() async {
    if (_homeAddressController.text.trim().isEmpty) {
      DialogHelper.showWarning(
        context: context,
        title: "Missing Address",
        message: "Please enter your starting address first.",
      );
      return;
    }

    // If we've already calculated the distance, don't call the API again.
    if (_distanceKm != null) {
      return;
    }

    setState(() {
      _isCalculatingDistance = true;
    });

    final user = Supabase.instance.client.auth.currentUser;

    if (user == null) return;

    final campus = await _apiService.getUserCampus(user.email!);

    if (campus == null) {
      DialogHelper.showError(
        context: context,
        title: "Campus not found",
        message: "Unable to retrieve your campus.",
      );
      return;
    }

    setState(() {
      _userCampus = campus;
    });

    final campusAddress = campusAddresses[campus];

    if (campusAddress == null) {
      DialogHelper.showError(
        context: context,
        title: "Unknown campus",
        message: "Campus address is not available.",
      );
      return;
    }

    try {
      final distanceKm = await _locationService.calculateDistance(
        homeAddress: _homeAddressController.text,
        campusAddress: campusAddress,
      );

      setState(() {
        _distanceKm = distanceKm;
        _isCalculatingDistance = false;
      });
    } catch (e) {
      setState(() {
        _isCalculatingDistance = false;
      });

      DialogHelper.showWarning(
        context: context,
        title: "Invalid Address",
        message:
            "Please enter a valid starting address. We couldn't locate the address you entered.",
      );

      return;
    }

    setState(() {
      _distanceKm = _distanceKm;
      _isCalculatingDistance = false;
    });
  }

  Future<void> _loadUserCampus() async {
    final user = Supabase.instance.client.auth.currentUser;

    if (user == null) return;

    final campus = await _apiService.getUserCampus(user.email!);

    if (campus != null) {
      setState(() {
        _userCampus = campus;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return SingleChildScrollView(
      physics: const BouncingScrollPhysics(),
      padding: const EdgeInsets.symmetric(horizontal: 16.0, vertical: 8.0),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // 1. Transport Form Card
          _buildFormCard(
            title: 'Transport',
            children: [
              Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  /// LEFT SIDE
                  Expanded(
                    flex: 4,
                    child: Column(
                      children: [
                        _buildDropdownField(
                          label: 'Transport Type',
                          hint: 'Select your transport type',
                          value: _selectedTransportType,
                          items: const [
                            'Traditional Jeepney',
                            'Modern Jeepney',
                            'Car',
                            'Motorcycle',
                            'Bicycle/Walking',
                          ],
                          onChanged: (value) {
                            setState(() {
                              _selectedTransportType = value;
                            });
                          },
                        ),

                        const SizedBox(height: 12),

                        _buildTextField(
                          label: "Starting Point",
                          hint: "Input Address Here",
                          controller: _homeAddressController,
                          onChanged: (_) {
                            _distanceKm = null;
                          },
                        ),

                        const SizedBox(height: 12),

                        Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const Text(
                              "Ending Point",
                              style: TextStyle(
                                fontSize: 11,
                                fontWeight: FontWeight.bold,
                                color: Color(0xFF3AA76D),
                              ),
                            ),
                            const SizedBox(height: 6),
                            Container(
                              height: 38,
                              width: double.infinity,
                              alignment: Alignment.centerLeft,
                              padding: const EdgeInsets.symmetric(
                                horizontal: 10,
                              ),
                              decoration: BoxDecoration(
                                border: Border.all(color: Colors.black38),
                                borderRadius: BorderRadius.circular(6),
                                color: Colors.grey.shade100,
                              ),
                              child: Text(
                                _userCampus.isEmpty
                                    ? "Loading campus..."
                                    : _userCampus,
                                style: const TextStyle(fontSize: 11),
                              ),
                            ),
                          ],
                        ),
                      ],
                    ),
                  ),

                  const SizedBox(width: 14),

                  /// RIGHT SIDE
                  Expanded(
                    flex: 6,
                    child: Container(
                      height: 180,
                      decoration: BoxDecoration(
                        border: Border.all(color: Colors.grey.shade400),
                        borderRadius: BorderRadius.circular(8),
                      ),
                      child: Center(
                        child: Column(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            const Icon(
                              Icons.map,
                              size: 45,
                              color: Color(0xFF3AA76D),
                            ),

                            const SizedBox(height: 9),

                            Text(
                              _distanceKm == null
                                  ? "Map Preview"
                                  : "${_distanceKm!.toStringAsFixed(2)} km",
                              style: const TextStyle(
                                fontWeight: FontWeight.bold,
                                fontSize: 18,
                                color: Color(0xFF3AA76D),
                              ),
                            ),

                            const SizedBox(height: 10),

                            ElevatedButton(
                              style: ElevatedButton.styleFrom(
                                backgroundColor: const Color(0xFF3AA76D),
                                foregroundColor: Colors.white,
                              ),
                              onPressed: () async {
                                await _calculateDistance();
                              },
                              child: _isCalculatingDistance
                                  ? const Row(
                                      mainAxisSize: MainAxisSize.min,
                                      children: [
                                        SizedBox(
                                          width: 18,
                                          height: 18,
                                          child: CircularProgressIndicator(
                                            strokeWidth: 2,
                                            color: Colors.white,
                                          ),
                                        ),
                                        SizedBox(width: 10),
                                        Text("Calculating..."),
                                      ],
                                    )
                                  : const Text("Calculate Distance"),
                            ),
                          ],
                        ),
                      ),
                    ),
                  ),
                ],
              ),

              const SizedBox(height: 15),

              SizedBox(
                width: double.infinity,
                child: ElevatedButton(
                  style: ElevatedButton.styleFrom(
                    backgroundColor: const Color(0xFFC7D8CE),
                    foregroundColor: const Color(0xFF265D3B),
                    elevation: 0,
                    padding: const EdgeInsets.symmetric(vertical: 15),
                  ),
                  onPressed: () {
                    if (_selectedTransportType == null || _distanceKm == null) {
                      DialogHelper.showWarning(
                        context: context,
                        title: "Incomplete Information",
                        message:
                            "Please select a transport type and input your address to calculate the distance before adding an emission.",
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
                    });
                  },
                  child: const Text(
                    "+ Add Emission",
                    style: TextStyle(fontWeight: FontWeight.bold),
                  ),
                ),
              ),
            ],
          ),

          const SizedBox(height: 16),

          // 2. Office Resource Form Card
          _buildFormCard(
            title: 'Office Resource',
            children: [
              // First Row
              Row(
                crossAxisAlignment: CrossAxisAlignment.end,
                children: [
                  Expanded(
                    flex: 4,
                    child: _buildDropdownField(
                      label: 'Office Resource Type',
                      hint: 'Select resource type',
                      value: _selectedOfficeResourceType,
                      items: const [
                        'Air Conditioner',
                        'Desktop Computer (CPU + Monitor)',
                        'Electric Fan',
                        'Lights',
                        'Laptop',
                        'Viewboard / Smart Screen',
                        'Projector',
                        'Printer (Laser)',
                        'Photocopier / Multifunction Printer',
                        'Scanner',
                        'Sound Speaker',
                      ],
                      onChanged: (val) {
                        setState(() {
                          _selectedOfficeResourceType = val;
                          _selectedOfficeResourceCategory = null;
                        });
                      },
                    ),
                  ),

                  const SizedBox(width: 10),

                  Expanded(
                    flex: 4,
                    child: _buildDropdownField(
                      label: 'Resource Category',
                      hint: _selectedOfficeResourceType == null
                          ? 'Select resource type'
                          : 'Select resource category',
                      value: _selectedOfficeResourceCategory,
                      items: _getOfficeResourceCategories(
                        _selectedOfficeResourceType,
                      ),
                      onChanged: (val) =>
                          setState(() => _selectedOfficeResourceCategory = val),
                    ),
                  ),

                  const SizedBox(width: 10),

                  SizedBox(
                    width: 120,
                    child: _buildTextField(
                      label: "Hours",
                      hint: "Enter hours",
                      controller: _officeHoursController,
                      keyboardType: TextInputType.number,
                      numbersOnly: true,
                    ),
                  ),
                ],
              ),

              const SizedBox(height: 12),

              // Second Row (Button)
              SizedBox(
                width: double.infinity,
                child: _buildAddButton(
                  onPressed: () {
                    if (_selectedOfficeResourceType == null ||
                        _selectedOfficeResourceCategory == null) {
                      DialogHelper.showWarning(
                        context: context,
                        title: "Incomplete Information",
                        message:
                            "Please select an office resource type and category before adding an emission.",
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

                    final hours = double.parse(_officeHoursController.text);

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

                      _selectedOfficeResourceType = null;
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
            children: [
              // First Row
              Row(
                crossAxisAlignment: CrossAxisAlignment.end,
                children: [
                  Expanded(
                    flex: 4,
                    child: _buildDropdownField(
                      label: 'Food Type',
                      hint: 'Select food type',
                      value: _selectedFoodType,
                      items: const [
                        'Red Meat',
                        'Dairy & Poultry',
                        'Staples & Plant-based Proteins',
                        'Grains, Vegetables, and Fruits',
                        'Beverages and Discretionary Items',
                      ],
                      onChanged: (val) {
                        setState(() {
                          _selectedFoodType = val;
                          _selectedFoodCategory = null;
                        });
                      },
                    ),
                  ),

                  const SizedBox(width: 10),

                  Expanded(
                    flex: 4,
                    child: _buildDropdownField(
                      label: 'Food Category',
                      hint: _selectedFoodType == null
                          ? 'Select food type'
                          : 'Select food category',
                      value: _selectedFoodCategory,
                      items: _getFoodCategories(_selectedFoodType),
                      onChanged: _selectedFoodType == null
                          ? (_) {}
                          : (val) =>
                                setState(() => _selectedFoodCategory = val),
                    ),
                  ),

                  const SizedBox(width: 10),

                  SizedBox(
                    width: 120,
                    child: _buildTextField(
                      label: "Servings",
                      hint: "Enter servings",
                      controller: _servingSizeController,
                      keyboardType: TextInputType.number,
                      numbersOnly: true,
                    ),
                  ),
                ],
              ),

              const SizedBox(height: 12),

              // Second Row
              SizedBox(
                width: double.infinity,
                child: _buildAddButton(
                  onPressed: () {
                    if (_selectedFoodType == null ||
                        _selectedFoodCategory == null) {
                      DialogHelper.showWarning(
                        context: context,
                        title: "Incomplete Information",
                        message:
                            "Please select a food type and food category before adding an emission.",
                      );
                      return;
                    }

                    if (_servingSizeController.text.isEmpty) {
                      DialogHelper.showWarning(
                        context: context,
                        title: "Incomplete Information",
                        message: "Please enter the serving size.",
                      );
                      return;
                    }

                    final serving = double.parse(_servingSizeController.text);

                    // Emission factors are in kg CO₂e per kilogram of food.
                    // Approximate 1 serving = 100 g (0.1 kg).

                    const servingWeight = 0.1;

                    final emission =
                        _calculateFoodEmission(_selectedFoodCategory!) *
                        serving *
                        servingWeight;

                    setState(() {
                      _foodEmissions.add(
                        '${_selectedFoodCategory!}\n'
                        '$serving serving(s)\n'
                        '${emission.toStringAsFixed(2)} kg CO₂e',
                      );

                      _foodTotalEmission += emission;

                      _selectedFoodType = null;
                      _selectedFoodCategory = null;
                      _servingSizeController.clear();
                    });
                  },
                ),
              ),
            ],
          ),

          const SizedBox(height: 24),

          // 4. Your Carbon Emissions List Section
          const Center(
            child: Text(
              'Your Carbon Emissions List',
              style: TextStyle(
                fontSize: 18,
                fontWeight: FontWeight.bold,
                color: darkGreen,
              ),
            ),
          ),
          const SizedBox(height: 16),

          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Expanded(
                child: _buildDynamicListCard(
                  'Transportation',
                  _transportEmissions,
                ),
              ),
              const SizedBox(width: 12),

              Expanded(
                child: _buildDynamicListCard(
                  'Office Resource',
                  _officeEmissions,
                ),
              ),
              const SizedBox(width: 12),

              Expanded(
                child: _buildDynamicListCard(
                  'Food Consumption',
                  _foodEmissions,
                ),
              ),
            ],
          ),

          const SizedBox(height: 24),

          // 🟢 POP-UP LOGIC ADDED BELOW
          GestureDetector(
            onTap: () async {
              if (_transportEmissions.isEmpty &&
                  _officeEmissions.isEmpty &&
                  _foodEmissions.isEmpty) {
                DialogHelper.showWarning(
                  context: context,
                  title: "Incomplete Information",
                  message: "Please add at least one emission to calculate.",
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
              child: const Center(
                child: Text(
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
    );
  }

  // Component Builders
  Widget _buildFormCard({
    required String title,
    required List<Widget> children,
  }) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.04),
            blurRadius: 6,
            offset: const Offset(0, 3),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            title,
            style: const TextStyle(
              fontSize: 18,
              fontWeight: FontWeight.bold,
              color: Color(0xFF1E5631),
            ),
          ),
          const SizedBox(height: 12),
          ...children,
        ],
      ),
    );
  }

  Widget _buildDropdownField({
    required String label,
    required String hint,
    required String? value,
    required List<String> items,
    required ValueChanged<String?> onChanged,
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
        Container(
          height: 38,
          padding: const EdgeInsets.symmetric(horizontal: 8),
          decoration: BoxDecoration(
            border: Border.all(color: Colors.black38, width: 1),
            borderRadius: BorderRadius.circular(6),
          ),
          child: DropdownButtonHideUnderline(
            child: DropdownButton<String>(
              value: value,
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
              items: items.map<DropdownMenuItem<String>>((String val) {
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
          height: 38,
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
              hintStyle: const TextStyle(fontSize: 8, color: Colors.black38),
              contentPadding: const EdgeInsets.symmetric(
                horizontal: 8,
                vertical: 8,
              ),
              border: OutlineInputBorder(
                borderRadius: BorderRadius.circular(6),
                borderSide: const BorderSide(color: Colors.black38, width: 1),
              ),
              enabledBorder: OutlineInputBorder(
                borderRadius: BorderRadius.circular(6),
                borderSide: const BorderSide(color: Colors.black38, width: 1),
              ),
            ),
          ),
        ),
      ],
    );
  }

  Widget _buildAddButton({required VoidCallback onPressed}) {
    return Container(
      height: 38,
      margin: const EdgeInsets.only(bottom: 1),
      child: ElevatedButton(
        onPressed: onPressed,
        style: ElevatedButton.styleFrom(
          backgroundColor: const Color(0xFFCCEAD8),
          foregroundColor: const Color(0xFF1E5631),
          elevation: 0,
          padding: const EdgeInsets.symmetric(horizontal: 10),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(6)),
        ),
        child: const Text(
          '+ Add Emission',
          style: TextStyle(fontSize: 10, fontWeight: FontWeight.bold),
        ),
      ),
    );
  }

  Widget _buildDynamicListCard(String title, List<String> items) {
    return Container(
      height: 160,
      padding: const EdgeInsets.all(6),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(8),
        border: Border.all(color: const Color(0xFF3AA76D), width: 1),
      ),
      child: Column(
        children: [
          Text(
            title,
            textAlign: TextAlign.center,
            style: const TextStyle(
              fontSize: 11,
              fontWeight: FontWeight.bold,
              color: Color(0xFF3AA76D),
            ),
          ),

          const Divider(height: 8),

          Expanded(
            child: items.isEmpty
                ? const Center(
                    child: Text(
                      "No entries yet",
                      style: TextStyle(fontSize: 9, color: Colors.black38),
                    ),
                  )
                : ListView.builder(
                    itemCount: items.length,
                    itemBuilder: (context, index) {
                      final parts = items[index].split("\n");

                      String itemTitle = "";
                      String emission = "";

                      if (parts.length >= 2) {
                        itemTitle = parts[0];
                        emission = parts[1];
                      } else {
                        emission = parts[0];
                      }

                      IconData icon = Icons.eco;

                      if (itemTitle.toLowerCase().contains("jeep") ||
                          itemTitle.toLowerCase().contains("car") ||
                          itemTitle.toLowerCase().contains("bus") ||
                          itemTitle.toLowerCase().contains("walk")) {
                        icon = Icons.directions_bus;
                      } else if (itemTitle.toLowerCase().contains("laptop") ||
                          itemTitle.toLowerCase().contains("ac") ||
                          itemTitle.toLowerCase().contains("fan") ||
                          itemTitle.toLowerCase().contains("computer") ||
                          itemTitle.toLowerCase().contains("led")) {
                        icon = Icons.computer;
                      } else if (itemTitle.toLowerCase().contains("beef") ||
                          itemTitle.toLowerCase().contains("chicken") ||
                          itemTitle.toLowerCase().contains("rice") ||
                          itemTitle.toLowerCase().contains("fish") ||
                          itemTitle.toLowerCase().contains("pork")) {
                        icon = Icons.restaurant;
                      }

                      return Padding(
                        padding: const EdgeInsets.symmetric(vertical: 6),
                        child: Row(
                          children: [
                            Icon(
                              icon,
                              size: 16,
                              color: const Color(0xFF3AA76D),
                            ),
                            const SizedBox(width: 8),
                            Expanded(
                              child: Text(
                                "$itemTitle - $emission",
                                style: const TextStyle(fontSize: 10),
                              ),
                            ),
                          ],
                        ),
                      );
                    },
                  ),
          ),
        ],
      ),
    );
  }
}
