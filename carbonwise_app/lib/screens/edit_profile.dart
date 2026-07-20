import 'package:flutter/material.dart';
import 'package:carbonwise_app/services/api_service.dart';
import 'package:supabase_flutter/supabase_flutter.dart';
import 'dart:io';
import 'package:image_picker/image_picker.dart';
import 'package:carbonwise_app/utils/profile_refresh_notifier.dart';
import 'package:carbonwise_app/utils/dialog_helper.dart';

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
  String? _profilePicture;

  bool isSaving = false;

  File? _selectedImage;

  final ImagePicker _picker = ImagePicker();

  @override
  void initState() {
    super.initState();

    _nameController = TextEditingController(text: widget.fullName);

    _profilePicture = widget.profilePicture;
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
        controller: TextEditingController(text: value),
        decoration: InputDecoration(
          isDense: true,
          contentPadding: const EdgeInsets.symmetric(
            horizontal: 14,
            vertical: 12,
          ),
          labelText: label,
          prefixIcon: Icon(icon, color: const Color(0xFF2E7D32)),
        ),
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
        width: 700,
        decoration: BoxDecoration(
          color: const Color(0xFFF7FBF8),
          borderRadius: BorderRadius.circular(22),
        ),
        child: SingleChildScrollView(
          child: Padding(
            padding: const EdgeInsets.all(18),
            child: Column(
              children: [
                /// HEADER
                Row(
                  children: [
                    const Icon(
                      Icons.edit_rounded,
                      color: primaryGreen,
                      size: 24,
                    ),

                    const SizedBox(width: 10),

                    const Expanded(
                      child: Text(
                        "Edit Profile",
                        style: TextStyle(
                          fontSize: 20,
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

                const SizedBox(height: 12),

                /// PROFILE PHOTO
                Stack(
                  alignment: Alignment.bottomRight,
                  children: [
                    Container(
                      decoration: BoxDecoration(
                        shape: BoxShape.circle,
                        boxShadow: const [
                          BoxShadow(
                            color: Colors.black12,
                            blurRadius: 18,
                            offset: Offset(0, 6),
                          ),
                        ],
                      ),
                      child: CircleAvatar(
                        radius: 60,
                        backgroundColor: const Color(0xFFDDEBDD),
                        backgroundImage: _selectedImage != null
                            ? FileImage(_selectedImage!)
                            : (_profilePicture != null &&
                                  _profilePicture!.isNotEmpty)
                            ? NetworkImage(_profilePicture!)
                            : null,
                        child:
                            (_selectedImage == null &&
                                (_profilePicture == null ||
                                    _profilePicture!.isEmpty))
                            ? const Icon(
                                Icons.person,
                                size: 48,
                                color: primaryGreen,
                              )
                            : null,
                      ),
                    ),

                    Container(
                      decoration: BoxDecoration(
                        color: primaryGreen,
                        shape: BoxShape.circle,
                        border: Border.all(color: Colors.white, width: 3),
                        boxShadow: const [
                          BoxShadow(color: Colors.black26, blurRadius: 8),
                        ],
                      ),
                      child: IconButton(
                        splashRadius: 16,
                        icon: const Icon(
                          Icons.camera_alt,
                          color: Colors.white,
                          size: 16,
                        ),
                        onPressed: _pickImage,
                      ),
                    ),
                  ],
                ),

                const SizedBox(height: 6),

                TextButton(
                  onPressed: _pickImage,
                  child: const Text(
                    "Change Profile Picture",
                    style: TextStyle(color: darkGreen, fontSize: 13),
                  ),
                ),

                const SizedBox(height: 18),

                const SizedBox(height: 12),

                Row(
                  children: [
                    const Expanded(child: Divider()),

                    Padding(
                      padding: const EdgeInsets.symmetric(horizontal: 10),
                      child: Text(
                        "ACCOUNT INFORMATION",
                        style: TextStyle(
                          color: Colors.grey.shade600,
                          fontWeight: FontWeight.bold,
                          letterSpacing: 1,
                          fontSize: 11,
                        ),
                      ),
                    ),

                    const Expanded(child: Divider()),
                  ],
                ),

                const SizedBox(height: 18),

                /// NAME
                TextField(
                  controller: _nameController,
                  decoration: InputDecoration(
                    isDense: true,
                    contentPadding: const EdgeInsets.symmetric(
                      horizontal: 14,
                      vertical: 12,
                    ),
                    labelText: "Full Name",
                    prefixIcon: const Icon(Icons.person, color: primaryGreen),
                    filled: true,
                    fillColor: const Color(0xFFF8FBF9),
                    enabledBorder: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(15),
                      borderSide: const BorderSide(color: Color(0xFFDCE8DF)),
                    ),

                    focusedBorder: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(15),
                      borderSide: const BorderSide(
                        color: primaryGreen,
                        width: 2,
                      ),
                    ),

                    border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(15),
                    ),
                  ),
                ),

                const SizedBox(height: 18),

                readOnlyField("SR Code", widget.studentNumber, Icons.badge),

                readOnlyField("G-Suite", widget.email, Icons.email),

                readOnlyField("Department", widget.department, Icons.school),

                readOnlyField("Campus", widget.campus, Icons.location_city),

                const SizedBox(height: 12),

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
                              profileUrl = await _apiService
                                  .uploadProfilePicture(_selectedImage!);

                              setState(() {
                                _profilePicture = profileUrl;
                              });
                            }

                            await _apiService.updateUserProfile(
                              email: user.email!,
                              fullName: _nameController.text.trim(),
                              profilePicture: profileUrl,
                            );

                            await _apiService.addNotification(
                              // notification
                              email: user.email!,
                              title: "Profile Updated",
                              message:
                                  "Your profile information has been updated.",
                              type: "info",
                            );

                            profileRefreshNotifier.value++;

                            if (!mounted) return;

                            DialogHelper.showSuccess(
                              context: context,
                              title: "Profile Updated!",
                              message:
                                  "Your profile has been updated successfully.",
                              onOk: () {
                                Navigator.pop(context, true);
                              },
                            );
                          } catch (e) {
                            ScaffoldMessenger.of(context).showSnackBar(
                              SnackBar(content: Text(e.toString())),
                            );
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
      ),
    );
  }
}
