import 'package:flutter/material.dart';
import 'package:supabase_flutter/supabase_flutter.dart';
import 'package:carbonwise_app/services/api_service.dart';
import 'package:carbonwise_app/utils/dialog_helper.dart';
import 'package:carbonwise_app/services/location_service.dart';

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
  final TextEditingController _homeAddressController = TextEditingController();
  double? _distanceKm;
  final TextEditingController _officeUsageController = TextEditingController();

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
    final powerRating = officeResourcePowerRatings[category] ?? 0.0;
    return (powerRating * hours) /
        1000 *
        0.527; // Convert to kWh and multiply by emission factor
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
          _transportEmissions.add("${transportation.toString()} kg CO₂");
        }

        if (electricity != null &&
            electricity.toString() != "0" &&
            electricity.toString() != "0.0") {
          _officeEmissions.add("${electricity.toString()} kg CO₂");
        }

        if (food != null &&
            food.toString() != "0" &&
            food.toString() != "0.0") {
          _foodEmissions.add("${food.toString()} kg CO₂");
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

    try {
      await _apiService.addCarbonRecord(
        email: user.email!,
        transportation: _transportationTotalEmission,
        electricity: _officeResourceTotalEmission,
        food: _foodTotalEmission,
        totalEmission: totalEmission,
        recordDate: now.toIso8601String().split('T').first,
        createdAt: now.toIso8601String(),
      );

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
      print("SAVE ERROR: $e");

      DialogHelper.showError(
        context: context,
        title: "Unable to Save",
        message:
            "Something went wrong while saving your carbon emission record. Please try again.",
      );
    }
  }

  Future<void> _calculateDistance() async {
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

    final distance = await _locationService.calculateDistance(
      homeAddress: _homeAddressController.text,
      campusAddress: campusAddress,
    );

    setState(() {
      _distanceKm = distance;
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
    const primaryGreen = Color(0xFF3AA76D);
    const darkGreen = Color(0xFF1E5631);

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
                              child: const Text("Calculate"),
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
                      DialogHelper.showInfo(
                        context: context,
                        title: "Incomplete Information",
                        message:
                            "Please select a transport type and calculate the distance first.",
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
                        "$_selectedTransportType\n"
                        "${_distanceKm!.toStringAsFixed(2)} km\n"
                        "${emission.toStringAsFixed(2)} kg CO₂e",
                      );

                      _selectedTransportType = null;
                      _homeAddressController.clear();
                      _distanceKm = null;
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
                      label: 'Office Resource Category',
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

                  Expanded(
                    flex: 3,
                    child: TextField(
                      controller: _officeUsageController,
                      keyboardType: const TextInputType.numberWithOptions(
                        decimal: true,
                      ),
                      decoration: const InputDecoration(
                        labelText: "Usage",
                        hintText: "Hours",
                        border: OutlineInputBorder(),
                      ),
                    ),
                  ),

                  const SizedBox(width: 10),

                  _buildAddButton(
                    onPressed: () {
                      if (_selectedOfficeResourceType == null ||
                          _selectedOfficeResourceCategory == null ||
                          _officeUsageController.text.isEmpty) {
                        return;
                      }

                      final hours =
                          double.tryParse(_officeUsageController.text) ?? 0;

                      final emission = _calculateOfficeResourceEmission(
                        _selectedOfficeResourceCategory!,
                        hours,
                      );

                      setState(() {
                        _officeEmissions.add(
                          '${_selectedOfficeResourceType!} - '
                          '${_selectedOfficeResourceCategory!}\n'
                          '${hours.toStringAsFixed(1)} hrs\n'
                          '${emission.toStringAsFixed(2)} kg CO₂e',
                        );

                        _officeResourceTotalEmission += emission;

                        _selectedOfficeResourceType = null;
                        _selectedOfficeResourceCategory = null;
                        _officeUsageController.clear();
                      });
                    },
                  ),
                ],
              ),
            ],
          ),

          const SizedBox(height: 16),

          // 3. Food Consumption Form Card
          _buildFormCard(
            title: 'Food Consumption',
            children: [
              Row(
                crossAxisAlignment: CrossAxisAlignment.end,
                children: [
                  Expanded(
                    flex: 5,
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
                    flex: 5,
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
                  _buildAddButton(
                    onPressed: () {
                      if (_selectedFoodType != null &&
                          _selectedFoodCategory != null) {
                        final emission = _calculateFoodEmission(
                          _selectedFoodCategory!,
                        );

                        setState(() {
                          _foodEmissions.add(
                            '${_selectedFoodType!} (${_selectedFoodCategory!}) '
                            '(${emission.toStringAsFixed(2)} kg CO2e)',
                          );

                          _foodTotalEmission += emission;

                          _selectedFoodType = null;
                          _selectedFoodCategory = null;
                        });
                      }
                    },
                  ),
                ],
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
                ScaffoldMessenger.of(context).showSnackBar(
                  const SnackBar(
                    content: Text(
                      'Please add at least one emission to calculate.',
                    ),
                  ),
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
          const Divider(height: 8, color: Color(0xFF3AA76D)),
          Expanded(
            child: items.isEmpty
                ? const Center(
                    child: Text(
                      'No entries yet',
                      style: TextStyle(fontSize: 9, color: Colors.black38),
                    ),
                  )
                : ListView.builder(
                    itemCount: items.length,
                    itemBuilder: (context, index) {
                      return Padding(
                        padding: const EdgeInsets.symmetric(vertical: 2),
                        child: Row(
                          children: [
                            Expanded(
                              child: Text(
                                items[index],
                                style: const TextStyle(
                                  fontSize: 9,
                                  color: Colors.black87,
                                ),
                                maxLines: 2,
                                overflow: TextOverflow.ellipsis,
                              ),
                            ),
                            GestureDetector(
                              onTap: () {
                                setState(() {
                                  items.removeAt(index);
                                });
                              },
                              child: const Icon(
                                Icons.cancel_outlined,
                                color: Colors.redAccent,
                                size: 12,
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
