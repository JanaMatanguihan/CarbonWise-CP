import 'package:flutter/material.dart';
import 'package:carbonwise_app/screens/navigation.dart';
import 'package:carbonwise_app/services/api_service.dart';
import 'package:carbonwise_app/utils/dialog_helper.dart';
import 'package:flutter/services.dart';
import 'package:carbonwise_app/widgets/terms_conditions_dialog.dart';

void main() {
  WidgetsFlutterBinding.ensureInitialized();
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
        useMaterial3: true,

        scaffoldBackgroundColor: const Color(0xFFEFEFEA),

        colorScheme: ColorScheme.fromSeed(
          seedColor: const Color(0xFF2E7D32),
          primary: const Color(0xFF2E7D32),
        ),

        appBarTheme: const AppBarTheme(
          backgroundColor: Color(0xFF2E7D32),
          foregroundColor: Colors.white,
          elevation: 0,
        ),

        elevatedButtonTheme: ElevatedButtonThemeData(
          style: ElevatedButton.styleFrom(
            backgroundColor: const Color(0xFF2E7D32),
            foregroundColor: Colors.white,
            shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(14),
            ),
          ),
        ),

        inputDecorationTheme: InputDecorationTheme(
          filled: true,
          fillColor: Colors.white,

          border: OutlineInputBorder(borderRadius: BorderRadius.circular(14)),

          enabledBorder: OutlineInputBorder(
            borderRadius: BorderRadius.circular(14),
            borderSide: BorderSide(color: Colors.grey.shade300),
          ),

          focusedBorder: const OutlineInputBorder(
            borderRadius: BorderRadius.all(Radius.circular(14)),
            borderSide: BorderSide(color: Color(0xFF2E7D32), width: 2),
          ),
        ),
      ),
      initialRoute: '/landing',
      routes: {
        '/landing': (context) => const LandingPageScreen(),
        '/login': (context) => const LoginScreen(),
        '/home': (context) => const CustomMainNavigation(),
      },
    );
  }
}

// 1. LANDING PAGE SCREEN
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
                      color: const Color(0xFF265D3B),
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
                    backgroundColor: const Color(0xFF3AA76D),
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

// 2. LOG-IN SCREEN
class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _emailController = TextEditingController();
  final _passwordController = TextEditingController();
  bool _isLoading = false;

  @override
  void dispose() {
    _emailController.dispose();
    _passwordController.dispose();
    super.dispose();
  }

  Future<void> _handleLogin() async {
    final email = _emailController.text.trim();
    final password = _passwordController.text.trim();

    if (email.isEmpty || password.isEmpty) {
      DialogHelper.showError(
        context: context,
        title: "Missing Information",
        message: "Please fill in all fields before logging in.",
      );
      return;
    }

    setState(() => _isLoading = true);

    try {
      final data = await ApiService.login(email, password);

      final user = data['user'];

      if (user['email_verified_at'] == null) {
        if (!mounted) return;

        DialogHelper.showError(
          context: context,
          title: "Email Not Verified",
          message:
              "Please verify your BatStateU email before logging into CarbonWise.",
        );

        return;
      }

      if (mounted) {
        DialogHelper.showSuccess(
          context: context,
          title: "Welcome!",
          message: "Login successful. Welcome back to CarbonWise!",
          onOk: () {
            Navigator.pushAndRemoveUntil(
              context,
              MaterialPageRoute(
                builder: (context) => const CustomMainNavigation(),
              ),
              (route) => false,
            );
          },
        );
      }
    } catch (error) {
      if (!mounted) return;

      DialogHelper.showError(
        context: context,
        title: "Login Failed",
        message: error.toString(),
      );
    } finally {
      if (mounted) {
        setState(() => _isLoading = false);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.white,
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
                        height: 140,
                        width: 140,
                        fit: BoxFit.contain,
                        errorBuilder: (context, error, stackTrace) =>
                            const Icon(
                              Icons.eco,
                              size: 100,
                              color: Color(0xFF265D3B),
                            ),
                      ),
                    ),
                    const SizedBox(height: 12),
                    const Text(
                      'CarbonWise',
                      style: TextStyle(
                        fontSize: 26,
                        fontWeight: FontWeight.bold,
                        color: Color(0xFF265D3B),
                      ),
                    ),
                    const SizedBox(height: 35),
                    Expanded(
                      child: Container(
                        width: double.infinity,
                        padding: const EdgeInsets.only(
                          left: 24.0,
                          right: 24.0,
                          top: 36.0,
                          bottom: 32.0,
                        ),
                        decoration: const BoxDecoration(
                          color: Color(0xFF2B6B46),
                          borderRadius: BorderRadius.only(
                            topLeft: Radius.circular(28),
                            topRight: Radius.circular(28),
                          ),
                        ),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const Center(
                              child: Text(
                                'Welcome to CarbonWise',
                                style: TextStyle(
                                  fontSize: 24,
                                  fontWeight: FontWeight.bold,
                                  color: Colors.white,
                                ),
                              ),
                            ),
                            const SizedBox(height: 10),
                            const Center(
                              child: Padding(
                                padding: EdgeInsets.symmetric(horizontal: 16.0),
                                child: Text(
                                  'Log in with your G-Suite email and password to start your sustainability journey.',
                                  textAlign: TextAlign.center,
                                  style: TextStyle(
                                    fontSize: 13,
                                    color: Colors.white70,
                                    height: 1.3,
                                  ),
                                ),
                              ),
                            ),
                            const SizedBox(height: 35),
                            const Text(
                              'G-Suite Email',
                              style: TextStyle(
                                color: Colors.white,
                                fontSize: 15,
                                fontWeight: FontWeight.w600,
                              ),
                            ),
                            const SizedBox(height: 6),
                            TextField(
                              controller: _emailController,
                              style: const TextStyle(color: Colors.black87),
                              keyboardType: TextInputType.emailAddress,
                              decoration: InputDecoration(
                                hintText: 'example@g.batstate-u.edu.ph',
                                hintStyle: const TextStyle(
                                  color: Color(0xFFB0B0B0),
                                  fontSize: 14,
                                ),
                                fillColor: const Color(0xFFF5F5F5),
                                filled: true,
                                border: OutlineInputBorder(
                                  borderRadius: BorderRadius.circular(6),
                                  borderSide: BorderSide.none,
                                ),
                                contentPadding: const EdgeInsets.symmetric(
                                  horizontal: 16,
                                  vertical: 14,
                                ),
                              ),
                            ),
                            const SizedBox(height: 20),
                            const Text(
                              'Password',
                              style: TextStyle(
                                color: Colors.white,
                                fontSize: 15,
                                fontWeight: FontWeight.w600,
                              ),
                            ),
                            const SizedBox(height: 6),
                            TextFormField(
                              controller: _passwordController,
                              obscureText: true,
                              style: const TextStyle(color: Colors.black87),
                              decoration: InputDecoration(
                                hintText: 'Enter Password',
                                hintStyle: const TextStyle(
                                  color: Color(0xFFB0B0B0),
                                  fontSize: 14,
                                ),
                                fillColor: const Color(0xFFF5F5F5),
                                filled: true,
                                border: OutlineInputBorder(
                                  borderRadius: BorderRadius.circular(6),
                                  borderSide: BorderSide.none,
                                ),
                                contentPadding: const EdgeInsets.symmetric(
                                  horizontal: 16,
                                  vertical: 14,
                                ),
                              ),
                            ),
                            const SizedBox(height: 30),
                            SizedBox(
                              width: double.infinity,
                              height: 48,
                              child: ElevatedButton(
                                onPressed: _isLoading ? null : _handleLogin,
                                style: ElevatedButton.styleFrom(
                                  backgroundColor: const Color(0xFF3AA76D),
                                  elevation: 0,
                                  shape: RoundedRectangleBorder(
                                    borderRadius: BorderRadius.circular(8),
                                  ),
                                ),
                                child: _isLoading
                                    ? const SizedBox(
                                        height: 20,
                                        width: 20,
                                        child: CircularProgressIndicator(
                                          color: Colors.white,
                                          strokeWidth: 2,
                                        ),
                                      )
                                    : const Text(
                                        'Log in',
                                        style: TextStyle(
                                          color: Colors.white,
                                          fontSize: 18,
                                          fontWeight: FontWeight.bold,
                                        ),
                                      ),
                              ),
                            ),
                            const SizedBox(height: 30),
                            const Divider(color: Colors.white38, thickness: 1),
                            const SizedBox(height: 20),
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
                                      fontSize: 14,
                                    ),
                                    children: [
                                      TextSpan(
                                        text: "Don’t have an account yet? ",
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
  final _srCodeController = TextEditingController();
  final _nameController = TextEditingController();
  final _emailController = TextEditingController();
  final _passwordController = TextEditingController();
  final _confirmPasswordController = TextEditingController();
  bool agreedToTerms = false;
  final GlobalKey<FormState> _formKey = GlobalKey<FormState>();

  String? selectedRole;

  String? selectedCampus;
  String? selectedYearLevel;
  String? selectedDepartment;
  String? selectedFacultyType;
  String? selectedOffice;
  bool _isLoading = false;

  final roles = ["Student", "Faculty", "Non-Teaching Staff"];

  final facultyTypes = ["Teaching Faculty", "Administrative Faculty"];

  final campuses = [
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
    'Mabini Campus',
  ];

  final colleges = [
    'College of Arts and Sciences',
    'College of Accountancy, Business and Economics',
    'College of Informatics and Computing Sciences',
    'College of Engineering Technology',
    'College of Teacher Education',
    'College of Engineering',
  ];

  final administrativeOffices = [
    'Office of the Chancellor',
    'Internal Audit',
    'Quality Assurance Management',
    'Sustainable Development',
    'Planning and Development',
    'External Affairs',
    'Resource Generation',
    'ICT Services',
    'Testing and Admission',
    'Registration Services',
    'Scholarship and Financial Assistance',
    'Guidance and Counseling',
    'Library Services',
    'Student Organization and Activities',
    'Student Discipline',
    'Sports and Development',
    'OJT',
    'National Service Training Program',
    'Human Resource Management',
    'Records Management',
    'Procurement',
    'Budget',
    'Cashiering/Disbursing',
    'Accounting',
    'Project Facilities and Management',
    'Environment Management Unit',
    'Property and Supply Management',
    'General Services',
    'Extension',
    'Research',
  ];

  final staffOffices = [
    'Office of the Chancellor',
    'Internal Audit',
    'Quality Assurance Management',
    'Sustainable Development',
    'Planning and Development',
    'External Affairs',
    'Resource Generation',
    'ICT Services',
    'Testing and Admission',
    'Registration Services',
    'Scholarship and Financial Assistance',
    'Guidance and Counseling',
    'Library Services',
    'Student Organization and Activities',
    'Student Discipline',
    'Sports and Development',
    'OJT',
    'National Service Training Program',
    'Human Resource Management',
    'Records Management',
    'Procurement',
    'Budget',
    'Cashiering/Disbursing',
    'Accounting',
    'Project Facilities and Management',
    'Environment Management Unit',
    'Property and Supply Management',
    'General Services',
    'Extension',
    'Research',
  ];

  @override
  void initState() {
    super.initState();

    _srCodeController.addListener(() {
      String text = _srCodeController.text;

      // Remove existing dash
      text = text.replaceAll('-', '');

      // Limit to 7 digits
      if (text.length > 7) {
        text = text.substring(0, 7);
      }

      // Insert dash after first 2 digits
      if (text.length > 2) {
        text = '${text.substring(0, 2)}-${text.substring(2)}';
      }

      if (_srCodeController.text != text) {
        _srCodeController.value = TextEditingValue(
          text: text,
          selection: TextSelection.collapsed(offset: text.length),
        );
      }

      // Automatically generate G-Suite email
      _emailController.text = text.isEmpty ? '' : '$text@g.batstate-u.edu.ph';
    });
  }

  @override
  void dispose() {
    _srCodeController.dispose();
    _nameController.dispose();
    _emailController.dispose();
    _passwordController.dispose();
    _confirmPasswordController.dispose();
    super.dispose();
  }

  Future<void> _handleSignUp() async {
    // Make sure a role is selected
    if (selectedRole == null) {
      DialogHelper.showError(
        context: context,
        title: "Missing Information",
        message: "Please select your role.",
      );
      return;
    }

    // Validate text fields if the Form exists
    final form = _formKey.currentState;

    if (form != null && !form.validate()) {
      return;
    }

    // Make sure campus is selected
    if (selectedCampus == null) {
      DialogHelper.showError(
        context: context,
        title: "Missing Information",
        message: "Please select your campus.",
      );
      return;
    }

    // Student-specific validation
    if (selectedRole == "Student") {
      if (_srCodeController.text.trim().isEmpty) {
        DialogHelper.showError(
          context: context,
          title: "Missing Information",
          message: "Please enter your SR-Code.",
        );
        return;
      }

      if (_nameController.text.trim().isEmpty) {
        DialogHelper.showError(
          context: context,
          title: "Missing Information",
          message: "Please enter your name.",
        );
        return;
      }

      if (selectedYearLevel == null) {
        DialogHelper.showError(
          context: context,
          title: "Missing Information",
          message: "Please select your year level.",
        );
        return;
      }

      if (selectedDepartment == null) {
        DialogHelper.showError(
          context: context,
          title: "Missing Information",
          message: "Please select your college/department.",
        );
        return;
      }
    }

    // Faculty-specific validation
    if (selectedRole == "Faculty") {
      if (selectedFacultyType == null) {
        DialogHelper.showError(
          context: context,
          title: "Missing Information",
          message: "Please select your faculty type.",
        );
        return;
      }

      if (selectedDepartment == null) {
        DialogHelper.showError(
          context: context,
          title: "Missing Information",
          message: selectedFacultyType == "Teaching Faculty"
              ? "Please select your college."
              : "Please select your office.",
        );
        return;
      }
    }

    // Non-teaching staff validation
    if (selectedRole == "Non-Teaching Staff") {
      if (selectedDepartment == null) {
        DialogHelper.showError(
          context: context,
          title: "Missing Information",
          message: "Please select your office.",
        );
        return;
      }
    }

    final email = _emailController.text.trim();
    final password = _passwordController.text;
    final confirmPassword = _confirmPasswordController.text;
    final srCode = _srCodeController.text.trim();
    final name = _nameController.text.trim();

    // Extra password check
    if (password.isEmpty) {
      DialogHelper.showError(
        context: context,
        title: "Missing Information",
        message: "Please enter a password.",
      );
      return;
    }

    if (password.length < 8) {
      DialogHelper.showError(
        context: context,
        title: "Invalid Password",
        message: "Password must be at least 6 characters.",
      );
      return;
    }

    if (password != confirmPassword) {
      DialogHelper.showError(
        context: context,
        title: "Password Mismatch",
        message: "Passwords do not match.",
      );
      return;
    }

    // Student email is generated from SR-Code
    if (selectedRole == "Student" && email.isEmpty) {
      DialogHelper.showError(
        context: context,
        title: "Invalid SR-Code",
        message: "Please enter a valid SR-Code.",
      );
      return;
    }

    setState(() {
      _isLoading = true;
    });

    try {
      int? yearLevel;

      if (selectedRole == "Student" && selectedYearLevel != null) {
        yearLevel = int.tryParse(
          selectedYearLevel!.replaceAll(RegExp(r'[^0-9]'), ''),
        );
      }

      await ApiService.register(
        email: email,
        password: password,
        passwordConfirmation: confirmPassword,
        role: selectedRole!,
        srCode: selectedRole == "Student" ? srCode : null,
        fullName: name,
        campus: selectedCampus!,
        yearLevel: yearLevel,
        department: selectedRole == "Student"
            ? selectedDepartment
            : selectedRole == "Faculty" &&
                  selectedFacultyType == "Teaching Faculty"
            ? selectedDepartment
            : null,
        facultyType: selectedRole == "Faculty" ? selectedFacultyType : null,
        office:
            selectedRole == "Faculty" &&
                selectedFacultyType == "Administrative Faculty"
            ? selectedDepartment
            : selectedRole == "Non-Teaching Staff"
            ? selectedDepartment
            : null,
      );

      if (!mounted) return;

      DialogHelper.showSuccess(
        context: context,
        title: "Account Created",
        message:
            "Your account has been created successfully.\n\n"
            "A verification email has been sent to your email address.\n\n"
            "Please verify your email before logging in.",
        onOk: () {
          Navigator.pushNamedAndRemoveUntil(
            context,
            '/login',
            (route) => false,
          );
        },
      );
    } catch (error) {
      if (!mounted) return;

      String message = error.toString();

      // Remove "Exception:" from the beginning if present
      if (message.startsWith("Exception: ")) {
        message = message.substring(11);
      }

      DialogHelper.showError(
        context: context,
        title: "Registration Failed",
        message: message,
      );
    } finally {
      if (mounted) {
        setState(() {
          _isLoading = false;
        });
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.white,
      body: LayoutBuilder(
        builder: (context, constraints) {
          return SingleChildScrollView(
            physics: const ClampingScrollPhysics(),
            child: ConstrainedBox(
              constraints: BoxConstraints(minHeight: constraints.maxHeight),
              child: IntrinsicHeight(
                child: Form(
                  key: _formKey,
                  child: Column(
                    children: [
                      const SizedBox(height: 50),

                      Center(
                        child: Image.asset(
                          'assets/carbonwise-logo.png',
                          height: 60,
                          width: 60,
                          fit: BoxFit.contain,
                          errorBuilder: (context, error, stackTrace) =>
                              const Icon(
                                Icons.eco,
                                size: 40,
                                color: Color(0xFF265D3B),
                              ),
                        ),
                      ),

                      const SizedBox(height: 8),

                      const Text(
                        'CarbonWise',
                        style: TextStyle(
                          fontSize: 20,
                          fontWeight: FontWeight.bold,
                          color: Color(0xFF265D3B),
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
                            color: Color(0xFF2B6B46),
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
                                  'Create your account and start your journey today!',
                                  textAlign: TextAlign.center,
                                  style: TextStyle(
                                    fontSize: 12,
                                    color: Colors.white70,
                                  ),
                                ),
                              ),

                              const SizedBox(height: 20),

                              const Text(
                                "Role",
                                style: TextStyle(
                                  color: Colors.white,
                                  fontWeight: FontWeight.w500,
                                ),
                              ),

                              const SizedBox(height: 6),

                              _buildDropdownField(
                                label: "Role",
                                hint: "Select your role",
                                items: roles,
                                value: selectedRole,
                                onChanged: (value) {
                                  setState(() {
                                    selectedRole = value;

                                    selectedFacultyType = null;
                                    selectedCampus = null;
                                    selectedDepartment = null;
                                    selectedYearLevel = null;

                                    _srCodeController.clear();
                                    _nameController.clear();
                                    _emailController.clear();
                                    _passwordController.clear();
                                    _confirmPasswordController.clear();
                                  });
                                },
                              ),

                              const SizedBox(height: 25),

                              // ==================================================
                              // STUDENT
                              // ==================================================
                              if (selectedRole == "Student") ...[
                                _buildInputField(
                                  label: "SR-Code",
                                  hint: "xx-xxxxx",
                                  controller: _srCodeController,
                                  keyboardType: TextInputType.number,
                                  isSrCode: true,
                                ),

                                _buildInputField(
                                  label: "Name",
                                  hint: "Enter your name",
                                  controller: _nameController,
                                ),

                                _buildInputField(
                                  label: "G-Suite Email",
                                  hint: "Automatically generated",
                                  controller: _emailController,
                                  readOnly: true,
                                ),

                                _buildInputField(
                                  label: "Password",
                                  hint: "Enter password",
                                  controller: _passwordController,
                                  isObscured: true,
                                ),

                                _buildInputField(
                                  label: "Confirm Password",
                                  hint: "Confirm password",
                                  controller: _confirmPasswordController,
                                  isObscured: true,
                                ),

                                _buildDropdownField(
                                  label: "Campus",
                                  hint: "Choose Campus",
                                  items: campuses,
                                  value: selectedCampus,
                                  onChanged: (v) {
                                    setState(() {
                                      selectedCampus = v;
                                    });
                                  },
                                ),

                                _buildDropdownField(
                                  label: "Year Level",
                                  hint: "Choose Year Level",
                                  items: [
                                    "1st Year",
                                    "2nd Year",
                                    "3rd Year",
                                    "4th Year",
                                  ],
                                  value: selectedYearLevel,
                                  onChanged: (v) {
                                    setState(() {
                                      selectedYearLevel = v;
                                    });
                                  },
                                ),

                                _buildDropdownField(
                                  label: "Department",
                                  hint: "Choose Department",
                                  items: colleges,
                                  value: selectedDepartment,
                                  onChanged: (v) {
                                    setState(() {
                                      selectedDepartment = v;
                                    });
                                  },
                                ),
                              ],

                              // ==================================================
                              // FACULTY
                              // ==================================================
                              if (selectedRole == "Faculty") ...[
                                _buildInputField(
                                  label: "Name",
                                  hint: "Enter your full name",
                                  controller: _nameController,
                                ),

                                _buildInputField(
                                  label: "G-Suite Email",
                                  hint: "example@g.batstate-u.edu.ph",
                                  controller: _emailController,
                                ),

                                _buildInputField(
                                  label: "Password",
                                  hint: "Password",
                                  controller: _passwordController,
                                  isObscured: true,
                                ),

                                _buildInputField(
                                  label: "Confirm Password",
                                  hint: "Confirm Password",
                                  controller: _confirmPasswordController,
                                  isObscured: true,
                                ),

                                _buildDropdownField(
                                  label: "Campus",
                                  hint: "Choose Campus",
                                  items: campuses,
                                  value: selectedCampus,
                                  onChanged: (v) {
                                    setState(() {
                                      selectedCampus = v;
                                    });
                                  },
                                ),

                                _buildDropdownField(
                                  label: "Faculty Type",
                                  hint: "Faculty Type",
                                  items: facultyTypes,
                                  value: selectedFacultyType,
                                  onChanged: (v) {
                                    setState(() {
                                      selectedFacultyType = v;
                                      selectedDepartment = null;
                                    });
                                  },
                                ),

                                if (selectedFacultyType == "Teaching Faculty")
                                  _buildDropdownField(
                                    label: "College",
                                    hint: "Choose College",
                                    items: colleges,
                                    value: selectedDepartment,
                                    onChanged: (v) {
                                      setState(() {
                                        selectedDepartment = v;
                                      });
                                    },
                                  ),

                                if (selectedFacultyType ==
                                    "Administrative Faculty")
                                  _buildDropdownField(
                                    label: "Office",
                                    hint: "Choose Office",
                                    items: administrativeOffices,
                                    value: selectedDepartment,
                                    onChanged: (v) {
                                      setState(() {
                                        selectedDepartment = v;
                                      });
                                    },
                                  ),
                              ],

                              // ==================================================
                              // NON-TEACHING STAFF
                              // ==================================================
                              if (selectedRole == "Non-Teaching Staff") ...[
                                _buildInputField(
                                  label: "Name",
                                  hint: "Enter your full name",
                                  controller: _nameController,
                                ),

                                _buildInputField(
                                  label: "Email",
                                  hint: "Enter your email",
                                  controller: _emailController,
                                ),

                                _buildInputField(
                                  label: "Password",
                                  hint: "Password",
                                  controller: _passwordController,
                                  isObscured: true,
                                ),

                                _buildInputField(
                                  label: "Confirm Password",
                                  hint: "Confirm Password",
                                  controller: _confirmPasswordController,
                                  isObscured: true,
                                ),

                                _buildDropdownField(
                                  label: "Campus",
                                  hint: "Choose Campus",
                                  items: campuses,
                                  value: selectedCampus,
                                  onChanged: (v) {
                                    setState(() {
                                      selectedCampus = v;
                                    });
                                  },
                                ),

                                _buildDropdownField(
                                  label: "Office",
                                  hint: "Choose Office",
                                  items: staffOffices,
                                  value: selectedDepartment,
                                  onChanged: (v) {
                                    setState(() {
                                      selectedDepartment = v;
                                    });
                                  },
                                ),
                              ],

                              const SizedBox(height: 18),

                              const SizedBox(height: 16),

                              // ==================================================
                              // SIGN UP BUTTON
                              // ==================================================
                              SizedBox(
                                width: double.infinity,
                                height: 48,
                                child: ElevatedButton(
                                  onPressed: _isLoading ? null : _handleSignUp,
                                  style: ElevatedButton.styleFrom(
                                    backgroundColor: const Color(0xFF3AA76D),
                                    shape: RoundedRectangleBorder(
                                      borderRadius: BorderRadius.circular(8),
                                    ),
                                  ),
                                  child: _isLoading
                                      ? const SizedBox(
                                          height: 20,
                                          width: 20,
                                          child: CircularProgressIndicator(
                                            color: Colors.white,
                                            strokeWidth: 2,
                                          ),
                                        )
                                      : const Text(
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

                              // ==================================================
                              // LOGIN LINK
                              // ==================================================
                              Center(
                                child: GestureDetector(
                                  onTap: () {
                                    Navigator.pop(context);
                                  },
                                  child: RichText(
                                    textAlign: TextAlign.center,
                                    text: const TextSpan(
                                      style: TextStyle(
                                        color: Colors.white70,
                                        fontSize: 14,
                                      ),
                                      children: [
                                        TextSpan(
                                          text: "Already have an account? ",
                                        ),
                                        TextSpan(
                                          text: "Log In.",
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

                              const SizedBox(height: 12),
                            ],
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            ),
          );
        },
      ),
    );
  }

  // Helper widget stubs so the layout code runs smoothly
  Widget _buildInputField({
    required String label,
    required String hint,
    required TextEditingController controller,
    bool isObscured = false,
    bool readOnly = false,
    bool isSrCode = false,
    TextInputType keyboardType = TextInputType.text,
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

        TextFormField(
          controller: controller,
          obscureText: isObscured,
          keyboardType: keyboardType,
          readOnly: readOnly,

          inputFormatters: isSrCode
              ? [
                  FilteringTextInputFormatter.digitsOnly,
                  LengthLimitingTextInputFormatter(7),
                ]
              : null,

          validator: (value) {
            final text = value?.trim() ?? '';

            if (text.isEmpty) {
              return '$label is required';
            }

            if (isSrCode && text.length != 8) {
              return 'Please enter a valid SR-Code';
            }

            if (label == "Password" && text.length < 8) {
              return 'Password must be at least 8 characters';
            }

            if (label == "Confirm Password" &&
                text != _passwordController.text) {
              return 'Passwords do not match';
            }

            return null;
          },

          decoration: InputDecoration(
            hintText: hint,
            hintStyle: const TextStyle(color: Color(0xFFB0B0B0), fontSize: 14),
            fillColor: const Color(0xFFF5F5F5),
            filled: true,

            border: OutlineInputBorder(
              borderRadius: BorderRadius.circular(6),
              borderSide: BorderSide.none,
            ),

            enabledBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(6),
              borderSide: BorderSide.none,
            ),

            focusedBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(6),
              borderSide: const BorderSide(color: Color(0xFF3AA76D), width: 2),
            ),

            errorBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(6),
              borderSide: const BorderSide(color: Colors.redAccent, width: 1),
            ),

            focusedErrorBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(6),
              borderSide: const BorderSide(color: Colors.redAccent, width: 2),
            ),

            contentPadding: const EdgeInsets.symmetric(
              horizontal: 16,
              vertical: 14,
            ),
          ),
        ),

        const SizedBox(height: 16),
      ],
    );
  }

  Widget _buildDropdownField({
    required String label,
    required String hint,
    required List<String> items,
    required String? value,
    required ValueChanged<String?> onChanged,
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

        DropdownButtonFormField<String>(
          value: value,
          isExpanded: true,

          validator: (value) {
            if (value == null || value.isEmpty) {
              return 'Please select $label';
            }
            return null;
          },

          decoration: InputDecoration(
            hintText: hint,
            hintStyle: const TextStyle(color: Color(0xFFB0B0B0), fontSize: 14),
            fillColor: const Color(0xFFF5F5F5),
            filled: true,

            border: OutlineInputBorder(
              borderRadius: BorderRadius.circular(6),
              borderSide: BorderSide.none,
            ),

            enabledBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(6),
              borderSide: BorderSide.none,
            ),

            focusedBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(6),
              borderSide: const BorderSide(color: Color(0xFF3AA76D), width: 2),
            ),

            errorBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(6),
              borderSide: const BorderSide(color: Colors.redAccent, width: 1),
            ),

            contentPadding: const EdgeInsets.symmetric(
              horizontal: 16,
              vertical: 14,
            ),
          ),

          items: items.map((item) {
            return DropdownMenuItem<String>(value: item, child: Text(item));
          }).toList(),

          onChanged: onChanged,
        ),

        const SizedBox(height: 16),
      ],
    );
  }
}
