import 'package:flutter/material.dart';

class ActivityInputScreen extends StatefulWidget {
  const ActivityInputScreen({super.key});

  @override
  State<ActivityInputScreen> createState() => _ActivityInputScreenState();
}

class _ActivityInputScreenState extends State<ActivityInputScreen> {
  // Form State Values
  String? _selectedTransportType;
  String? _selectedOfficeResourceType;
  String? _selectedOfficeResourceCategory;
  String? _selectedFoodType;
  String? _selectedFoodCategory;

  // Controllers
  final TextEditingController _distanceController = TextEditingController();

  // 🟢 Lists to store added emissions dynamically
  final List<String> _transportEmissions = [];
  final List<String> _officeEmissions = [];
  final List<String> _foodEmissions = [];

  // Helper method to get conditional food categories based on selected food type
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
          'Soy Milk',
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

  @override
  void dispose() {
    _distanceController.dispose();
    super.dispose();
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
                crossAxisAlignment: CrossAxisAlignment.end,
                children: [
                  Expanded(
                    flex: 5,
                    child: _buildDropdownField(
                      label: 'Transport Type',
                      hint: 'Select your transport type',
                      value: _selectedTransportType,
                      items: [
                        'Traditional Jeepney',
                        'Modern Jeepney',
                        'Car',
                        'Motorcycle',
                        'Bicycle/Walking',
                      ],
                      onChanged: (val) =>
                          setState(() => _selectedTransportType = val),
                    ),
                  ),
                  const SizedBox(width: 10),
                  Expanded(
                    flex: 5,
                    child: _buildTextField(
                      label: 'Distance (in Kilometers)',
                      hint: 'Input distance (ie. 10km)',
                      controller: _distanceController,
                    ),
                  ),
                  const SizedBox(width: 10),
                  _buildAddButton(
                    onPressed: () {
                      if (_selectedTransportType != null &&
                          _distanceController.text.isNotEmpty) {
                        setState(() {
                          _transportEmissions.add(
                            '${_selectedTransportType!} (${_distanceController.text} km)',
                          );
                          _selectedTransportType = null;
                          _distanceController.clear();
                        });
                      }
                    },
                  ),
                ],
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
                    flex: 5,
                    child: _buildDropdownField(
                      label: 'Office Resource Type',
                      hint: 'Select resource type',
                      value: _selectedOfficeResourceType,
                      items: [
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
                    flex: 5,
                    child: _buildDropdownField(
                      label: 'Office Resource Category',
                      hint: _selectedOfficeResourceType == null
                          ? 'Select resource type'
                          : 'Select resource category',
                      value: _selectedOfficeResourceCategory,
                      items: _getOfficeResourceCategories(
                        _selectedOfficeResourceType,
                      ),
                      onChanged: (val) {
                        setState(() {
                          _selectedOfficeResourceCategory = val;
                        });
                      },
                    ),
                  ),
                  const SizedBox(width: 10),
                  _buildAddButton(
                    onPressed: () {
                      if (_selectedOfficeResourceType != null &&
                          _selectedOfficeResourceCategory != null) {
                        setState(() {
                          _officeEmissions.add(
                            '${_selectedOfficeResourceType!} - ${_selectedOfficeResourceCategory!}',
                          );
                          _selectedOfficeResourceType = null;
                          _selectedOfficeResourceCategory = null;
                        });
                      }
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
                      items: [
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
                        setState(() {
                          _foodEmissions.add(
                            '${_selectedFoodType!} (${_selectedFoodCategory!})',
                          );
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
            onTap: () {
              // 1. Guard against empty submissions
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

              // 2. Launch the details summary modal
              showDialog(
                context: context,
                builder: (BuildContext context) {
                  return AlertDialog(
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(12),
                    ),
                    title: const Text(
                      'Your Carbon Emissions Summary',
                      style: TextStyle(
                        color: darkGreen,
                        fontWeight: FontWeight.bold,
                        fontSize: 16,
                      ),
                    ),
                    content: SizedBox(
                      width: double.maxFinite,
                      child: ListView(
                        shrinkWrap: true,
                        children: [
                          if (_transportEmissions.isNotEmpty) ...[
                            const Text(
                              '🚗 Transportation',
                              style: TextStyle(
                                fontWeight: FontWeight.bold,
                                fontSize: 13,
                                color: primaryGreen,
                              ),
                            ),
                            const SizedBox(height: 4),
                            ..._transportEmissions.map(
                              (e) => Padding(
                                padding: const EdgeInsets.only(
                                  left: 8.0,
                                  bottom: 2.0,
                                ),
                                child: Text(
                                  '• $e',
                                  style: const TextStyle(fontSize: 12),
                                ),
                              ),
                            ),
                            const SizedBox(height: 12),
                          ],
                          if (_officeEmissions.isNotEmpty) ...[
                            const Text(
                              '🏢 Office Resources',
                              style: TextStyle(
                                fontWeight: FontWeight.bold,
                                fontSize: 13,
                                color: primaryGreen,
                              ),
                            ),
                            const SizedBox(height: 4),
                            ..._officeEmissions.map(
                              (e) => Padding(
                                padding: const EdgeInsets.only(
                                  left: 8.0,
                                  bottom: 2.0,
                                ),
                                child: Text(
                                  '• $e',
                                  style: const TextStyle(fontSize: 12),
                                ),
                              ),
                            ),
                            const SizedBox(height: 12),
                          ],
                          if (_foodEmissions.isNotEmpty) ...[
                            const Text(
                              '🍲 Food Consumption',
                              style: TextStyle(
                                fontWeight: FontWeight.bold,
                                fontSize: 13,
                                color: primaryGreen,
                              ),
                            ),
                            const SizedBox(height: 4),
                            ..._foodEmissions.map(
                              (e) => Padding(
                                padding: const EdgeInsets.only(
                                  left: 8.0,
                                  bottom: 2.0,
                                ),
                                child: Text(
                                  '• $e',
                                  style: const TextStyle(fontSize: 12),
                                ),
                              ),
                            ),
                          ],
                        ],
                      ),
                    ),
                    actions: [
                      TextButton(
                        onPressed: () => Navigator.pop(context),
                        child: const Text(
                          'Close',
                          style: TextStyle(
                            color: darkGreen,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                      ),
                    ],
                  );
                },
              );
            },
            child: Container(
              width: double.infinity,
              padding: const EdgeInsets.symmetric(vertical: 16.0),
              decoration: BoxDecoration(
                color: primaryGreen,
                borderRadius: BorderRadius.circular(10),
                boxShadow: [
                  BoxShadow(
                    color: Colors.black.withOpacity(0.08),
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
      padding: const EdgeInsets.all(16.0),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.04),
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
            style: const TextStyle(fontSize: 11),
            decoration: InputDecoration(
              hintText: hint,
              hintStyle: const TextStyle(fontSize: 10, color: Colors.black38),
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
                        padding: const EdgeInsets.symmetric(vertical: 2.0),
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
