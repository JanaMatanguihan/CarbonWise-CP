import 'package:flutter/material.dart';
import 'package:carbonwise_app/services/api_service.dart';
import 'package:supabase_flutter/supabase_flutter.dart';

class EditProfileScreen extends StatefulWidget {
  final String fullName;
  final String studentNumber;
  final String email;
  final String department;
  final String campus;
  final String? profilePicture;

  const EditProfileScreen({
    super.key,
    required this.fullName,
    required this.studentNumber,
    required this.email,
    required this.department,
    required this.campus,
    this.profilePicture,
  });

  @override
  State<EditProfileScreen> createState() => _EditProfileScreenState();
}

class _EditProfileScreenState extends State<EditProfileScreen> {
  late TextEditingController _nameController;
  final ApiService _apiService = ApiService();

  bool isSaving = false;

  @override
  void initState() {
    super.initState();

    _nameController = TextEditingController(text: widget.fullName);
  }

  @override
  void dispose() {
    _nameController.dispose();
    super.dispose();
  }

  Widget readOnlyField(String label, String value, IconData icon) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 18),
      child: TextField(
        readOnly: true,
        decoration: InputDecoration(
          labelText: "Full Name",
          prefixIcon: const Icon(Icons.person, color: Color(0xFF2E7D32)),
          filled: true,
          fillColor: Colors.white,

          enabledBorder: OutlineInputBorder(
            borderRadius: BorderRadius.circular(14),
            borderSide: BorderSide(color: Colors.grey.shade300),
          ),

          focusedBorder: OutlineInputBorder(
            borderRadius: BorderRadius.circular(14),
            borderSide: const BorderSide(color: Color(0xFF2E7D32), width: 2),
          ),
        ),
        controller: TextEditingController(text: value),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    const green = Color(0xFF2E7D32);

    return Scaffold(
      backgroundColor: const Color(0xFFEFEFEA),

      appBar: AppBar(
        backgroundColor: const Color(0xFF265D3B),
        foregroundColor: Colors.white,
        elevation: 0,
        centerTitle: true,
        title: const Text(
          "Edit Profile",
          style: TextStyle(fontWeight: FontWeight.bold, fontSize: 20),
        ),
      ),

      body: SingleChildScrollView(
        padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 20),

        child: Column(
          children: [
            const SizedBox(height: 10),

            Stack(
              alignment: Alignment.bottomRight,
              children: [
                CircleAvatar(
                  radius: 55,
                  backgroundColor: const Color(0xFFDDEBDD),
                  child: const Icon(Icons.person, size: 60, color: green),
                ),

                FloatingActionButton.small(
                  backgroundColor: const Color(0xFF265D3B),
                  onPressed: () {
                    // We'll add Image Picker later
                  },
                  child: const Icon(Icons.camera_alt),
                ),
              ],
            ),

            const SizedBox(height: 15),

            TextButton(
              style: TextButton.styleFrom(
                foregroundColor: const Color(0xFF265D3B),
              ),
              onPressed: () {},
              child: const Text("Change Profile Picture"),
            ),

            const SizedBox(height: 20),

            TextField(
              controller: _nameController,

              decoration: InputDecoration(
                labelText: "Full Name",

                labelStyle: const TextStyle(color: Color(0xFF265D3B)),

                prefixIcon: const Icon(Icons.person, color: Color(0xFF265D3B)),

                filled: true,
                fillColor: Colors.white,

                contentPadding: const EdgeInsets.symmetric(
                  horizontal: 18,
                  vertical: 16,
                ),

                enabledBorder: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(14),
                  borderSide: BorderSide(color: Colors.grey.shade300),
                ),

                focusedBorder: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(14),
                  borderSide: const BorderSide(
                    color: Color(0xFF265D3B),
                    width: 2,
                  ),
                ),
              ),
            ),

            const SizedBox(height: 20),

            readOnlyField("Student Number", widget.studentNumber, Icons.badge),

            readOnlyField("G-Suite", widget.email, Icons.email),

            readOnlyField("Department", widget.department, Icons.school),

            readOnlyField("Campus", widget.campus, Icons.location_city),

            const SizedBox(height: 10),

            SizedBox(
              width: double.infinity,

              height: 55,

              child: ElevatedButton(
                style: ElevatedButton.styleFrom(
                  backgroundColor: const Color(0xFF2E7D32),
                  foregroundColor: Colors.white,
                  elevation: 2,
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(14),
                  ),
                ),

                onPressed: () async {
                  if (_nameController.text.trim().isEmpty) {
                    ScaffoldMessenger.of(context).showSnackBar(
                      const SnackBar(content: Text("Name cannot be empty.")),
                    );

                    return;
                  }

                  setState(() {
                    isSaving = true;
                  });

                  try {
                    final user = Supabase.instance.client.auth.currentUser;

                    if (user == null) return;

                    await _apiService.updateUserProfile(
                      email: user.email!,
                      fullName: _nameController.text.trim(),
                      profilePicture: widget.profilePicture,
                    );

                    if (!mounted) return;

                    ScaffoldMessenger.of(context).showSnackBar(
                      const SnackBar(
                        content: Text("Profile updated successfully!"),
                        backgroundColor: const Color(0xFF265D3B),
                      ),
                    );

                    Navigator.pop(context);
                  } catch (e) {
                    if (!mounted) return;

                    ScaffoldMessenger.of(context).showSnackBar(
                      SnackBar(
                        content: Text(e.toString()),
                        backgroundColor: Colors.red,
                      ),
                    );
                  } finally {
                    if (mounted) {
                      setState(() {
                        isSaving = false;
                      });
                    }
                  }
                },

                // PATCH goes here
                child: isSaving
                    ? const SizedBox(
                        width: 24,
                        height: 24,
                        child: CircularProgressIndicator(color: Colors.white),
                      )
                    : const Text(
                        "Save Changes",
                        style: TextStyle(
                          fontSize: 18,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
