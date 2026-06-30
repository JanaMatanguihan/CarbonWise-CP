import 'package:flutter/material.dart';

class StrategiesScreen extends StatefulWidget {
  const StrategiesScreen({super.key});

  @override
  State<StrategiesScreen> createState() => _StrategiesScreenState();
}

class _StrategiesScreenState extends State<StrategiesScreen> {
  // Filter States
  String _searchQuery = '';
  String _selectedCategory = 'All Categories';
  String _selectedFrequency = 'All Frequencies';

  final List<Map<String, String>> _allStrategies = [
    {
      'title': 'Promote Plant-Rich Meals',
      'description':
          'Encourage plant-based meal options in campus cafeterias and events.',
      'category': 'Food Consumption',
      'frequency': 'Daily',
      'icon': 'flat_ware',
    },
    {
      'title': 'Print Less, Think Twice',
      'description':
          'Reduce unnecessary printing and encourage digital document sharing.',
      'category': 'Office Resource',
      'frequency': 'Weekly',
      'icon': 'print',
    },
    {
      'title': 'Bike to Campus',
      'description': 'Encourage cycling by improving bike racks.',
      'category': 'Transport',
      'frequency': 'Daily',
      'icon': 'directions_bike',
    },
    {
      'title': 'Switch Off, Save More',
      'description': 'Turn off lights, AC, and electronics when not in use.',
      'category': 'Office Resource',
      'frequency': 'Daily',
      'icon': 'lightbulb',
    },
    {
      'title': 'Use Public Transport',
      'description':
          'Encourage the use of public transport through commuter benefits and awareness.',
      'category': 'Transport',
      'frequency': 'Daily',
      'icon': 'directions_bus',
    },
    {
      'title': 'Walk Today',
      'description': 'Walk to nearby places, do not use public transport.',
      'category': 'Transport',
      'frequency': 'Daily',
      'icon': 'footprints',
    },
    {
      'title': 'Carpool',
      'description': 'Use public transport whenever you can.',
      'category': 'Transport',
      'frequency': 'Daily',
      'icon': 'directions_car',
    },
  ];

  static const Color primaryGreen = Color(0xFF3AA76D);
  static const Color darkGreen = Color(0xFF1E5631);
  static const Color badgeGrey = Color(0xFFCCEAD8);

  @override
  Widget build(BuildContext context) {
    // Filter logic based on choices
    final filteredStrategies = _allStrategies.where((strategy) {
      final matchesSearch =
          strategy['title']!.toLowerCase().contains(
            _searchQuery.toLowerCase(),
          ) ||
          strategy['description']!.toLowerCase().contains(
            _searchQuery.toLowerCase(),
          );
      final matchesCategory =
          _selectedCategory == 'All Categories' ||
          strategy['category'] == _selectedCategory;
      final matchesFrequency =
          _selectedFrequency == 'All Frequencies' ||
          strategy['frequency'] == _selectedFrequency;
      return matchesSearch && matchesCategory && matchesFrequency;
    }).toList();

    return Scaffold(
      backgroundColor: Color(0xFFF4F6F4),
      body: Column(
        children: [
          // Main Content Area Container Box
          Expanded(
            child: SafeArea(
              child: Container(
                margin: const EdgeInsets.all(16.0),
                padding: const EdgeInsets.all(12.0),
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(4),
                ),
                child: Column(
                  children: [
                    // Search and Filters Bar Row
                    Row(
                      children: [
                        Expanded(
                          flex: 4,
                          child: Container(
                            height: 36,
                            padding: const EdgeInsets.symmetric(horizontal: 6),
                            decoration: BoxDecoration(
                              border: Border.all(
                                color: Colors.black45,
                                width: 0.8,
                              ),
                              borderRadius: BorderRadius.circular(4),
                            ),
                            child: Row(
                              crossAxisAlignment: CrossAxisAlignment.center,
                              children: [
                                const Icon(
                                  Icons.search,
                                  size: 16,
                                  color: Colors.black87,
                                ),
                                const SizedBox(width: 4),
                                Expanded(
                                  child: TextField(
                                    onChanged: (val) =>
                                        setState(() => _searchQuery = val),
                                    style: const TextStyle(fontSize: 11),
                                    decoration: const InputDecoration(
                                      hintText: 'Search...',
                                      hintStyle: TextStyle(
                                        color: Colors.black38,
                                        fontSize: 11,
                                      ),
                                      border: InputBorder.none,
                                      isDense: true,
                                      contentPadding: EdgeInsets.symmetric(
                                        vertical: 8,
                                      ),
                                    ),
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ),
                        const SizedBox(width: 6),
                        Expanded(
                          flex: 3,
                          child: _buildFilterDropdown(
                            value: _selectedCategory,
                            items: [
                              'All Categories',
                              'Transport',
                              'Office Resource',
                              'Food Consumption',
                            ],
                            onChanged: (val) =>
                                setState(() => _selectedCategory = val!),
                          ),
                        ),
                        const SizedBox(width: 6),
                        Expanded(
                          flex: 3,
                          child: _buildFilterDropdown(
                            value: _selectedFrequency,
                            items: ['All Frequencies', 'Daily', 'Weekly'],
                            onChanged: (val) =>
                                setState(() => _selectedFrequency = val!),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 14),

                    // Table Label Header
                    const Padding(
                      padding: EdgeInsets.symmetric(
                        horizontal: 4.0,
                        vertical: 2.0,
                      ),
                      child: Row(
                        children: [
                          Expanded(
                            flex: 5,
                            child: Text(
                              'Strategy',
                              style: TextStyle(
                                fontSize: 11,
                                color: Colors.black54,
                              ),
                            ),
                          ),
                          Expanded(
                            flex: 3,
                            child: Center(
                              child: Text(
                                'Category',
                                style: TextStyle(
                                  fontSize: 11,
                                  color: Colors.black54,
                                ),
                              ),
                            ),
                          ),
                          Expanded(
                            flex: 2,
                            child: Center(
                              child: Text(
                                'Frequency',
                                style: TextStyle(
                                  fontSize: 11,
                                  color: Colors.black54,
                                ),
                              ),
                            ),
                          ),
                        ],
                      ),
                    ),
                    const Divider(color: Colors.black12, thickness: 1),

                    // Strategies Table List
                    Expanded(
                      child: filteredStrategies.isEmpty
                          ? const Center(
                              child: Text(
                                'No strategies found.',
                                style: TextStyle(
                                  fontSize: 12,
                                  color: Colors.grey,
                                ),
                              ),
                            )
                          : ListView.separated(
                              itemCount: filteredStrategies.length,
                              separatorBuilder: (context, index) =>
                                  const Divider(
                                    color: Colors.black12,
                                    height: 1,
                                  ),
                              itemBuilder: (context, index) {
                                final item = filteredStrategies[index];
                                return Padding(
                                  padding: const EdgeInsets.symmetric(
                                    vertical: 8.0,
                                  ),
                                  child: Row(
                                    crossAxisAlignment:
                                        CrossAxisAlignment.center,
                                    children: [
                                      Expanded(
                                        flex: 5,
                                        child: Row(
                                          crossAxisAlignment:
                                              CrossAxisAlignment.start,
                                          children: [
                                            Container(
                                              padding: const EdgeInsets.all(6),
                                              decoration: BoxDecoration(
                                                shape: BoxShape.circle,
                                                border: Border.all(
                                                  color: primaryGreen
                                                      .withOpacity(0.3),
                                                  width: 1,
                                                ),
                                              ),
                                              child: Icon(
                                                _getStrategyIcon(item['icon']),
                                                color: primaryGreen,
                                                size: 20,
                                              ),
                                            ),
                                            const SizedBox(width: 8),
                                            Expanded(
                                              child: Column(
                                                crossAxisAlignment:
                                                    CrossAxisAlignment.start,
                                                children: [
                                                  Text(
                                                    item['title']!,
                                                    style: const TextStyle(
                                                      fontWeight:
                                                          FontWeight.bold,
                                                      fontSize: 12,
                                                      color: Colors.black,
                                                    ),
                                                  ),
                                                  const SizedBox(height: 2),
                                                  Text(
                                                    item['description']!,
                                                    style: const TextStyle(
                                                      fontSize: 9,
                                                      color: Colors.black54,
                                                      height: 1.2,
                                                    ),
                                                  ),
                                                ],
                                              ),
                                            ),
                                          ],
                                        ),
                                      ),
                                      Expanded(
                                        flex: 3,
                                        child: Center(
                                          child: Container(
                                            padding: const EdgeInsets.symmetric(
                                              horizontal: 6,
                                              vertical: 4,
                                            ),
                                            decoration: BoxDecoration(
                                              color: badgeGrey,
                                              borderRadius:
                                                  BorderRadius.circular(4),
                                            ),
                                            child: Text(
                                              item['category']!,
                                              textAlign: TextAlign.center,
                                              style: const TextStyle(
                                                color: darkGreen,
                                                fontSize: 9,
                                                fontWeight: FontWeight.bold,
                                              ),
                                            ),
                                          ),
                                        ),
                                      ),
                                      Expanded(
                                        flex: 2,
                                        child: Center(
                                          child: Container(
                                            padding: const EdgeInsets.symmetric(
                                              horizontal: 8,
                                              vertical: 4,
                                            ),
                                            decoration: BoxDecoration(
                                              color: badgeGrey,
                                              borderRadius:
                                                  BorderRadius.circular(4),
                                            ),
                                            child: Text(
                                              item['frequency']!,
                                              style: const TextStyle(
                                                color: darkGreen,
                                                fontSize: 9,
                                                fontWeight: FontWeight.bold,
                                              ),
                                            ),
                                          ),
                                        ),
                                      ),
                                    ],
                                  ),
                                );
                              },
                            ),
                    ),

                    // Pagination Footer
                    Row(
                      mainAxisAlignment: MainAxisAlignment.end,
                      children: [
                        _buildPaginationButton(
                          Icons.keyboard_arrow_left,
                          () {},
                        ),
                        _buildPaginationPageNum('1', true),
                        _buildPaginationPageNum('2', false),
                        _buildPaginationPageNum('3', false),
                        _buildPaginationButton(
                          Icons.keyboard_arrow_right,
                          () {},
                        ),
                      ],
                    ),
                  ],
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }

  // --- UI Helpers ---
  Widget _buildFilterDropdown({
    required String value,
    required List<String> items,
    required ValueChanged<String?> onChanged,
  }) {
    return Container(
      height: 36,
      padding: const EdgeInsets.symmetric(horizontal: 4),
      decoration: BoxDecoration(
        border: Border.all(color: Colors.black45, width: 0.8),
        borderRadius: BorderRadius.circular(4),
      ),
      child: DropdownButtonHideUnderline(
        child: DropdownButton<String>(
          value: value,
          isExpanded: true,
          icon: const Icon(
            Icons.arrow_drop_down,
            size: 18,
            color: Colors.black87,
          ),
          style: const TextStyle(fontSize: 10, color: Colors.black87),
          items: items.map((String val) {
            return DropdownMenuItem<String>(
              value: val,
              child: Text(val, overflow: TextOverflow.ellipsis),
            );
          }).toList(),
          onChanged: onChanged,
        ),
      ),
    );
  }

  Widget _buildPaginationButton(IconData icon, VoidCallback onPressed) {
    return IconButton(
      icon: Icon(icon, size: 18),
      onPressed: onPressed,
      visualDensity: VisualDensity.compact,
    );
  }

  Widget _buildPaginationPageNum(String pageNum, bool isActive) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 4.0),
      child: Text(
        pageNum,
        style: TextStyle(
          fontSize: 11,
          fontWeight: isActive ? FontWeight.bold : FontWeight.normal,
          color: isActive ? darkGreen : Colors.black54,
        ),
      ),
    );
  }

  IconData _getStrategyIcon(String? iconKey) {
    switch (iconKey) {
      case 'flat_ware':
        return Icons.flatware;
      case 'print':
        return Icons.print;
      case 'directions_bike':
        return Icons.directions_bike;
      case 'lightbulb':
        return Icons.lightbulb;
      case 'directions_bus':
        return Icons.directions_bus;
      case 'footprints':
        return Icons.flutter_dash;
      case 'directions_car':
        return Icons.directions_car;
      default:
        return Icons.eco;
    }
  }
}
