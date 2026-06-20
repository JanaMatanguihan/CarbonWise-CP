import 'package:flutter/material.dart';

void main() {
  runApp(const CarbonWiseApp());
}

class CarbonWiseApp extends StatelessWidget {
  const CarbonWiseApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      debugShowCheckedModeBanner: false,
      title: 'CarbonWise',
      theme: ThemeData(
        scaffoldBackgroundColor: const Color(
          0xFFF5F7F5,
        ), // Off-white canvas tone
        primarySwatch: Colors.green,
      ),
      home: const LandingPageScreen(),
    );
  }
}

// ==========================================
// 1. LANDING PAGE SCREEN
// ==========================================
class LandingPageScreen extends StatelessWidget {
  const LandingPageScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: SafeArea(
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 24.0),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              const Spacer(),
              Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  const Text(
                    'Welcome to ',
                    style: TextStyle(
                      fontSize: 26,
                      fontWeight: FontWeight.bold,
                      color: Colors.black,
                    ),
                  ),
                  Text(
                    'CarbonWise',
                    style: TextStyle(
                      fontSize: 26,
                      fontWeight: FontWeight.bold,
                      color: Colors.green[800],
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 40),
              Center(
                child: Image.asset(
                  'assets/carbonwise-logo.png',
                  height: 200,
                  fit: BoxFit.contain,
                ),
              ),
              const SizedBox(height: 50),
              const Text(
                'Track your daily footprint and level up your impact on campus sustainability!',
                textAlign: TextAlign.center,
                style: TextStyle(
                  fontSize: 18,
                  fontWeight: FontWeight.w600,
                  color: Colors.black87,
                  height: 1.4,
                ),
              ),
              const Spacer(),
              SizedBox(
                width: double.infinity,
                height: 48,
                child: ElevatedButton(
                  onPressed: () {
                    Navigator.push(
                      context,
                      MaterialPageRoute(
                        builder: (context) => const LoginScreen(),
                      ),
                    );
                  },
                  style: ElevatedButton.styleFrom(
                    backgroundColor: const Color(0xFF3B9E66),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(8),
                    ),
                  ),
                  child: const Text(
                    'Get started with CarbonWise',
                    style: TextStyle(
                      fontSize: 15,
                      color: Colors.white,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                ),
              ),
              const SizedBox(height: 30),
            ],
          ),
        ),
      ),
    );
  }
}

// ==========================================
// 2. LOG-IN SCREEN (FIXED BOTTOM PINNING)
// ==========================================
class LoginScreen extends StatelessWidget {
  const LoginScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: LayoutBuilder(
        builder: (context, constraints) {
          return SingleChildScrollView(
            physics: const ClampingScrollPhysics(),
            child: ConstrainedBox(
              constraints: BoxConstraints(minHeight: constraints.maxHeight),
              child: IntrinsicHeight(
                child: Column(
                  children: [
                    const SizedBox(height: 60),
                    Center(
                      child: Image.asset(
                        'assets/carbonwise-logo.png',
                        height: 70,
                        width: 70,
                        fit: BoxFit.contain,
                      ),
                    ),
                    const SizedBox(height: 8),
                    Text(
                      'CarbonWise',
                      style: TextStyle(
                        fontSize: 22,
                        fontWeight: FontWeight.bold,
                        color: Colors.green[800],
                      ),
                    ),
                    const SizedBox(height: 30),

                    // Expanded card fills all remaining bottom viewport room
                    Expanded(
                      child: Container(
                        width: double.infinity,
                        padding: const EdgeInsets.only(
                          left: 24.0,
                          right: 24.0,
                          top: 32.0,
                          bottom: 24.0,
                        ),
                        decoration: const BoxDecoration(
                          color: Color(
                            0xFF1E5631,
                          ), // Deep dark green mockup tone
                          borderRadius: BorderRadius.only(
                            topLeft: Radius.circular(32),
                            topRight: Radius.circular(32),
                          ),
                        ),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const Center(
                              child: Text(
                                'Welcome to CarbonWise',
                                style: TextStyle(
                                  fontSize: 22,
                                  fontWeight: FontWeight.bold,
                                  color: Colors.white,
                                ),
                              ),
                            ),
                            const SizedBox(height: 8),
                            const Center(
                              child: Text(
                                'Log in with your SR-Code and password to start your sustainability journey.',
                                textAlign: TextAlign.center,
                                style: TextStyle(
                                  fontSize: 13,
                                  color: Colors.white70,
                                ),
                              ),
                            ),
                            const SizedBox(height: 30),

                            const Text(
                              'SR-Code',
                              style: TextStyle(
                                color: Colors.white,
                                fontWeight: FontWeight.w500,
                              ),
                            ),
                            const SizedBox(height: 6),
                            TextField(
                              decoration: InputDecoration(
                                hintText: '2x-xxxxx',
                                hintStyle: const TextStyle(
                                  color: Colors.grey,
                                  fontSize: 13,
                                ),
                                fillColor: const Color(0xFFECECEC),
                                filled: true,
                                border: OutlineInputBorder(
                                  borderRadius: BorderRadius.circular(8),
                                  borderSide: BorderSide.none,
                                ),
                                contentPadding: const EdgeInsets.symmetric(
                                  horizontal: 16,
                                  vertical: 12,
                                ),
                              ),
                            ),
                            const SizedBox(height: 20),

                            const Text(
                              'Password',
                              style: TextStyle(
                                color: Colors.white,
                                fontWeight: FontWeight.w500,
                              ),
                            ),
                            const SizedBox(height: 6),
                            TextField(
                              obscureText: true,
                              decoration: InputDecoration(
                                hintText: 'Enter Password',
                                hintStyle: const TextStyle(
                                  color: Colors.grey,
                                  fontSize: 13,
                                ),
                                fillColor: const Color(0xFFECECEC),
                                filled: true,
                                border: OutlineInputBorder(
                                  borderRadius: BorderRadius.circular(8),
                                  borderSide: BorderSide.none,
                                ),
                                contentPadding: const EdgeInsets.symmetric(
                                  horizontal: 16,
                                  vertical: 12,
                                ),
                              ),
                            ),
                            const SizedBox(height: 35),

                            SizedBox(
                              width: double.infinity,
                              height: 48,
                              child: ElevatedButton(
                                onPressed: () {},
                                style: ElevatedButton.styleFrom(
                                  backgroundColor: const Color(0xFF3B9E66),
                                  shape: RoundedRectangleBorder(
                                    borderRadius: BorderRadius.circular(8),
                                  ),
                                ),
                                child: const Text(
                                  'Log in',
                                  style: TextStyle(
                                    color: Colors.white,
                                    fontSize: 16,
                                    fontWeight: FontWeight.bold,
                                  ),
                                ),
                              ),
                            ),

                            // This spacer forces the following items down to the bottom
                            const Spacer(),

                            const Divider(color: Colors.white30),
                            const SizedBox(height: 15),

                            Center(
                              child: GestureDetector(
                                onTap: () {
                                  Navigator.push(
                                    context,
                                    MaterialPageRoute(
                                      builder: (context) =>
                                          const SignUpScreen(),
                                    ),
                                  );
                                },
                                child: RichText(
                                  textAlign: TextAlign.center,
                                  text: const TextSpan(
                                    style: TextStyle(
                                      color: Colors.white70,
                                      fontSize: 13,
                                    ),
                                    children: [
                                      TextSpan(
                                        text: "Don't have an account yet? ",
                                      ),
                                      TextSpan(
                                        text: "Click here to Sign up.",
                                        style: TextStyle(
                                          fontWeight: FontWeight.bold,
                                          color: Colors.white,
                                        ),
                                      ),
                                    ],
                                  ),
                                ),
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            ),
          );
        },
      ),
    );
  }
}

// ==========================================
// 3. SIGN UP SCREEN
// ==========================================
class SignUpScreen extends StatefulWidget {
  const SignUpScreen({super.key});

  @override
  State<SignUpScreen> createState() => _SignUpScreenState();
}

class _SignUpScreenState extends State<SignUpScreen> {
  String? selectedCampus;
  String? selectedYearLevel;
  String? selectedDepartment;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: LayoutBuilder(
        builder: (context, constraints) {
          return SingleChildScrollView(
            physics: const ClampingScrollPhysics(),
            child: ConstrainedBox(
              constraints: BoxConstraints(minHeight: constraints.maxHeight),
              child: IntrinsicHeight(
                child: Column(
                  children: [
                    const SizedBox(height: 50),
                    Center(
                      child: Image.asset(
                        'assets/carbonwise-logo.png',
                        height: 60,
                        width: 60,
                        fit: BoxFit.contain,
                      ),
                    ),
                    const SizedBox(height: 8),
                    Text(
                      'CarbonWise',
                      style: TextStyle(
                        fontSize: 20,
                        fontWeight: FontWeight.bold,
                        color: Colors.green[800],
                      ),
                    ),
                    const SizedBox(height: 20),

                    Expanded(
                      child: Container(
                        width: double.infinity,
                        padding: const EdgeInsets.symmetric(
                          horizontal: 24.0,
                          vertical: 32.0,
                        ),
                        decoration: const BoxDecoration(
                          color: Color(0xFF1E5631),
                          borderRadius: BorderRadius.only(
                            topLeft: Radius.circular(32),
                            topRight: Radius.circular(32),
                          ),
                        ),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const Center(
                              child: Text(
                                'Get Started with CarbonWise',
                                style: TextStyle(
                                  fontSize: 22,
                                  fontWeight: FontWeight.bold,
                                  color: Colors.white,
                                ),
                              ),
                            ),
                            const SizedBox(height: 6),
                            const Center(
                              child: Text(
                                'Create your account and start your sustainability journey today!',
                                textAlign: TextAlign.center,
                                style: TextStyle(
                                  fontSize: 12,
                                  color: Colors.white70,
                                ),
                              ),
                            ),
                            const SizedBox(height: 25),

                            _buildInputField(
                              label: 'SR-Code',
                              hint: '2x-xxxxx',
                            ),
                            _buildInputField(
                              label: 'Name',
                              hint: 'Enter your name',
                            ),
                            _buildInputField(
                              label: 'G-Suite Email',
                              hint: '@g.batstate-u.edu.ph',
                            ),
                            _buildInputField(
                              label: 'Password',
                              hint: 'Enter your password',
                              isObscured: true,
                            ),
                            _buildInputField(
                              label: 'Password Confirmation',
                              hint: 'Enter your password',
                              isObscured: true,
                            ),

                            Row(
                              children: [
                                Expanded(
                                  child: Column(
                                    crossAxisAlignment:
                                        CrossAxisAlignment.start,
                                    children: [
                                      const Text(
                                        'Campus',
                                        style: TextStyle(
                                          color: Colors.white,
                                          fontWeight: FontWeight.w500,
                                        ),
                                      ),
                                      const SizedBox(height: 6),
                                      _buildDropdownField(
                                        hint: 'Click to choose...',
                                        items: [
                                          'Lipa Campus',
                                          'Pablo Borbon Campus',
                                          'Alangilan Campus',
                                          'Lima Campus',
                                          'ARASOF Nasugbu Campus',
                                          'JPLPC Malvar Campus',
                                          'Lemery Campus',
                                          'Rosario Campus',
                                          'San Juan Campus',
                                          'Balayan Campus',
                                          'Lobo Campus',
                                          'Mabini',
                                        ],
                                        value: selectedCampus,
                                        onChanged: (val) => setState(
                                          () => selectedCampus = val,
                                        ),
                                      ),
                                    ],
                                  ),
                                ),
                                const SizedBox(width: 12),
                                Expanded(
                                  child: Column(
                                    crossAxisAlignment:
                                        CrossAxisAlignment.start,
                                    children: [
                                      const Text(
                                        'Year Level',
                                        style: TextStyle(
                                          color: Colors.white,
                                          fontWeight: FontWeight.w500,
                                        ),
                                      ),
                                      const SizedBox(height: 6),
                                      _buildDropdownField(
                                        hint: 'Click to choose...',
                                        items: [
                                          '1st Year',
                                          '2nd Year',
                                          '3rd Year',
                                          '4th Year',
                                        ],
                                        value: selectedYearLevel,
                                        onChanged: (val) => setState(
                                          () => selectedYearLevel = val,
                                        ),
                                      ),
                                    ],
                                  ),
                                ),
                              ],
                            ),
                            const SizedBox(height: 16),

                            const Text(
                              'Department',
                              style: TextStyle(
                                color: Colors.white,
                                fontWeight: FontWeight.w500,
                              ),
                            ),
                            const SizedBox(height: 6),
                            _buildDropdownField(
                              hint: 'Click to choose your department',
                              items: ['CABE', 'CAS', 'CET', 'CICS', 'CTE'],
                              value: selectedDepartment,
                              onChanged: (val) =>
                                  setState(() => selectedDepartment = val),
                            ),
                            const SizedBox(height: 35),

                            SizedBox(
                              width: double.infinity,
                              height: 48,
                              child: ElevatedButton(
                                onPressed: () {},
                                style: ElevatedButton.styleFrom(
                                  backgroundColor: const Color(0xFF3B9E66),
                                  shape: RoundedRectangleBorder(
                                    borderRadius: BorderRadius.circular(8),
                                  ),
                                ),
                                child: const Text(
                                  'Sign Up',
                                  style: TextStyle(
                                    color: Colors.white,
                                    fontSize: 16,
                                    fontWeight: FontWeight.bold,
                                  ),
                                ),
                              ),
                            ),
                            const SizedBox(height: 24),
                          ],
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            ),
          );
        },
      ),
    );
  }

  Widget _buildInputField({
    required String label,
    required String hint,
    bool isObscured = false,
  }) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          label,
          style: const TextStyle(
            color: Colors.white,
            fontWeight: FontWeight.w500,
          ),
        ),
        const SizedBox(height: 6),
        TextField(
          obscureText: isObscured,
          decoration: InputDecoration(
            hintText: hint,
            hintStyle: const TextStyle(color: Colors.grey, fontSize: 13),
            fillColor: const Color(0xFFECECEC),
            filled: true,
            border: OutlineInputBorder(
              borderRadius: BorderRadius.circular(8),
              borderSide: BorderSide.none,
            ),
            contentPadding: const EdgeInsets.symmetric(
              horizontal: 14,
              vertical: 12,
            ),
          ),
        ),
        const SizedBox(height: 16),
      ],
    );
  }

  Widget _buildDropdownField({
    required String hint,
    required List<String> items,
    required String? value,
    required ValueChanged<String?> onChanged,
  }) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12),
      decoration: BoxDecoration(
        color: const Color(0xFFECECEC),
        borderRadius: BorderRadius.circular(8),
      ),
      child: DropdownButtonHideUnderline(
        child: DropdownButton<String>(
          value: value,
          hint: Text(
            hint,
            style: const TextStyle(color: Colors.grey, fontSize: 13),
          ),
          isExpanded: true,
          icon: const Icon(Icons.keyboard_arrow_down, color: Colors.black87),
          items: items.map((String item) {
            return DropdownMenuItem<String>(
              value: item,
              child: Text(
                item,
                style: const TextStyle(fontSize: 14, color: Colors.black87),
              ),
            );
          }).toList(),
          onChanged: onChanged,
        ),
      ),
    );
  }
}
