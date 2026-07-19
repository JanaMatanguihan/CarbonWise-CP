import 'package:flutter/material.dart';
import 'package:carbonwise_app/services/api_service.dart';
import 'package:supabase_flutter/supabase_flutter.dart';
import 'dart:io';
import 'package:image_picker/image_picker.dart';
import 'package:carbonwise_app/utils/profile_refresh_notifier.dart';

class EditProfileDialog extends StatefulWidget {
  final String fullName;
  final String studentNumber;
  final String email;
  final String department;
  final String campus;
  final String? profilePicture;

  const EditProfileDialog({
    super.key,
    required this.fullName,
    required this.studentNumber,
    required this.email,
    required this.department,
    required this.campus,
    this.profilePicture,
  });

  @override
  State<EditProfileDialog> createState() => _EditProfileDialogState();
}

class _EditProfileDialogState extends State<EditProfileDialog> {
  late TextEditingController _nameController;
  final ApiService _apiService = ApiService();

  bool isSaving = false;

  File? _selectedImage;

  final ImagePicker _picker = ImagePicker();

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

  Future<void> _pickImage() async {
    final picked = await _picker.pickImage(
      source: ImageSource.gallery,
      imageQuality: 70,
    );

    if (picked == null) return;

    setState(() {
      _selectedImage = File(picked.path);
    });
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
    const primaryGreen = Color(0xFF3AA76D);
    const darkGreen = Color(0xFF265D3B);

    return Dialog(
      backgroundColor: Colors.transparent,
      child: Container(
        width: 520,
        padding: const EdgeInsets.all(24),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(18),
        ),
        child: SingleChildScrollView(
          child: Column(
            children: [
              /// HEADER
              Row(
                children: [
                  const Expanded(
                    child: Text(
                      "Edit Profile",
                      style: TextStyle(
                        fontSize: 22,
                        fontWeight: FontWeight.bold,
                        color: darkGreen,
                      ),
                    ),
                  ),

                  IconButton(
                    splashRadius: 20,
                    onPressed: () => Navigator.pop(context),
                    icon: const Icon(Icons.close),
                  ),
                ],
              ),

              const SizedBox(height: 20),

              /// PROFILE PHOTO
              Stack(
                alignment: Alignment.bottomRight,
                children: [
                  CircleAvatar(
                    radius: 55,
                    backgroundColor: const Color(0xFFDDEBDD),
                    backgroundImage: _selectedImage != null
                        ? FileImage(_selectedImage!)
                        : (widget.profilePicture != null &&
                              widget.profilePicture!.isNotEmpty)
                        ? NetworkImage(widget.profilePicture!)
                        : null,
                    child:
                        (_selectedImage == null &&
                            (widget.profilePicture == null ||
                                widget.profilePicture!.isEmpty))
                        ? const Icon(Icons.person, size: 60)
                        : null,
                  ),

                  Container(
                    decoration: const BoxDecoration(
                      color: primaryGreen,
                      shape: BoxShape.circle,
                    ),
                    child: IconButton(
                      splashRadius: 18,
                      icon: const Icon(
                        Icons.camera_alt,
                        color: Colors.white,
                        size: 18,
                      ),
                      onPressed: () {},
                    ),
                  ),
                ],
              ),

              const SizedBox(height: 10),

              TextButton(
                onPressed: _pickImage,
                child: const Text(
                  "Change Profile Picture",
                  style: TextStyle(color: darkGreen),
                ),
              ),

              const SizedBox(height: 18),

              /// NAME
              TextField(
                controller: _nameController,
                decoration: InputDecoration(
                  labelText: "Full Name",
                  prefixIcon: const Icon(Icons.person, color: primaryGreen),
                  filled: true,
                  fillColor: const Color(0xFFF8FBF9),
                  border: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(12),
                  ),
                ),
              ),

              const SizedBox(height: 18),

              readOnlyField(
                "Student Number",
                widget.studentNumber,
                Icons.badge,
              ),

              readOnlyField("G-Suite", widget.email, Icons.email),

              readOnlyField("Department", widget.department, Icons.school),

              readOnlyField("Campus", widget.campus, Icons.location_city),

              const SizedBox(height: 20),

              Row(
                children: [
                  Expanded(
                    child: OutlinedButton(
                      onPressed: () => Navigator.pop(context),

                      style: OutlinedButton.styleFrom(
                        foregroundColor: primaryGreen,
                        side: const BorderSide(color: primaryGreen),
                        minimumSize: const Size(double.infinity, 50),
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(12),
                        ),
                      ),

                      child: const Text("Cancel"),
                    ),
                  ),

                  const SizedBox(width: 12),

                  Expanded(
                    child: ElevatedButton(
                      style: ElevatedButton.styleFrom(
                        backgroundColor: primaryGreen,
                        foregroundColor: Colors.white,
                        minimumSize: const Size(double.infinity, 50),
                        elevation: 0,
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(12),
                        ),
                      ),

                      onPressed: () async {
                        if (_nameController.text.trim().isEmpty) {
                          ScaffoldMessenger.of(context).showSnackBar(
                            const SnackBar(
                              content: Text("Name cannot be empty."),
                            ),
                          );
                          return;
                        }

                        setState(() {
                          isSaving = true;
                        });

                        try {
                          final user =
                              Supabase.instance.client.auth.currentUser;

                          if (user == null) return;

                          String? profileUrl = widget.profilePicture;

                          if (_selectedImage != null) {
                            profileUrl = await _apiService.uploadProfilePicture(
                              _selectedImage!,
                            );
                          }

                          await _apiService.updateUserProfile(
                            email: user.email!,
                            fullName: _nameController.text.trim(),
                            profilePicture: profileUrl,
                          );

                          profileRefreshNotifier.value++;

                          if (!mounted) return;

                          Navigator.pop(context, true);
                        } catch (e) {
                          ScaffoldMessenger.of(
                            context,
                          ).showSnackBar(SnackBar(content: Text(e.toString())));
                        } finally {
                          if (mounted) {
                            setState(() {
                              isSaving = false;
                            });
                          }
                        }
                      },

                      child: isSaving
                          ? const SizedBox(
                              width: 22,
                              height: 22,
                              child: CircularProgressIndicator(
                                color: Colors.white,
                              ),
                            )
                          : const Text(
                              "Save Changes",
                              style: TextStyle(fontWeight: FontWeight.bold),
                            ),
                    ),
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }
}
