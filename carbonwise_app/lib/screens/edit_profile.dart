import 'package:flutter/material.dart';
import 'package:carbonwise_app/services/api_service.dart';
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
  static const Color primaryGreen = Color(0xFF3AA76D);
  static const Color darkGreen = Color(0xFF265D3B);
  static const Color lightBackground = Color(0xFFF7FBF8);
  static const Color fieldBackground = Color(0xFFF8FBF9);

  late TextEditingController _nameController;

  final ApiService _apiService = ApiService();
  final ImagePicker _picker = ImagePicker();

  String? _profilePicture;
  File? _selectedImage;

  bool isSaving = false;
  bool isLoadingImage = false;

  @override
  void initState() {
    super.initState();

    // Full Name is automatically filled with the user's current name.
    _nameController = TextEditingController(text: widget.fullName.trim());

    // Keep the user's existing profile picture.
    _profilePicture = widget.profilePicture;
  }

  @override
  void dispose() {
    _nameController.dispose();
    super.dispose();
  }

  // ============================================================
  // PICK PROFILE PICTURE
  // ============================================================

  Future<void> _pickImage() async {
    try {
      final picked = await _picker.pickImage(
        source: ImageSource.gallery,
        imageQuality: 75,
        maxWidth: 1200,
        maxHeight: 1200,
      );

      if (picked == null || !mounted) return;

      setState(() {
        _selectedImage = File(picked.path);
      });
    } catch (e) {
      if (!mounted) return;

      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Could not select the image: $e'),
          behavior: SnackBarBehavior.floating,
        ),
      );
    }
  }

  // ============================================================
  // READ-ONLY FIELD
  // ============================================================

  Widget readOnlyField(String label, String value, IconData icon) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: TextField(
        readOnly: true,
        controller: TextEditingController(text: value),
        style: const TextStyle(
          fontSize: 13,
          color: Color(0xFF4B5563),
          fontWeight: FontWeight.w500,
        ),
        decoration: InputDecoration(
          isDense: true,
          labelText: label,
          labelStyle: const TextStyle(fontSize: 12, color: Colors.black54),
          prefixIcon: Icon(icon, color: Colors.black45, size: 19),
          suffixIcon: const Icon(
            Icons.lock_outline_rounded,
            size: 17,
            color: Colors.black26,
          ),
          filled: true,
          fillColor: Colors.white,
          contentPadding: const EdgeInsets.symmetric(
            horizontal: 14,
            vertical: 14,
          ),
          enabledBorder: OutlineInputBorder(
            borderRadius: BorderRadius.circular(14),
            borderSide: const BorderSide(color: Color(0xFFE0E8E3)),
          ),
          focusedBorder: OutlineInputBorder(
            borderRadius: BorderRadius.circular(14),
            borderSide: const BorderSide(color: Color(0xFFD1DDD6)),
          ),
        ),
      ),
    );
  }

  // ============================================================
  // PROFILE PHOTO
  // ============================================================

  Widget _buildProfilePhoto() {
    Widget imageWidget;

    // If user just selected a new picture,
    // show that picture immediately.
    if (_selectedImage != null) {
      imageWidget = Image.file(
        _selectedImage!,
        width: 116,
        height: 116,
        fit: BoxFit.cover,
      );
    }
    // Otherwise show the existing profile picture.
    else if (_profilePicture != null && _profilePicture!.trim().isNotEmpty) {
      imageWidget = Image.network(
        _profilePicture!,
        width: 116,
        height: 116,
        fit: BoxFit.cover,

        loadingBuilder:
            (
              BuildContext context,
              Widget child,
              ImageChunkEvent? loadingProgress,
            ) {
              if (loadingProgress == null) {
                return child;
              }

              return Container(
                width: 116,
                height: 116,
                color: const Color(0xFFE8F5EE),
                child: Center(
                  child: SizedBox(
                    width: 30,
                    height: 30,
                    child: CircularProgressIndicator(
                      strokeWidth: 3,
                      color: primaryGreen,
                      value: loadingProgress.expectedTotalBytes != null
                          ? loadingProgress.cumulativeBytesLoaded /
                                loadingProgress.expectedTotalBytes!
                          : null,
                    ),
                  ),
                ),
              );
            },

        errorBuilder: (context, error, stackTrace) {
          return Container(
            width: 116,
            height: 116,
            color: const Color(0xFFE8F5EE),
            child: const Icon(
              Icons.person_rounded,
              size: 52,
              color: primaryGreen,
            ),
          );
        },
      );
    }
    // No picture.
    else {
      imageWidget = Container(
        width: 116,
        height: 116,
        color: const Color(0xFFE8F5EE),
        child: const Icon(Icons.person_rounded, size: 52, color: primaryGreen),
      );
    }

    return Container(
      width: 128,
      height: 128,
      padding: const EdgeInsets.all(4),
      decoration: BoxDecoration(
        shape: BoxShape.circle,
        color: Colors.white,

        // Little green border
        border: Border.all(
          color: primaryGreen.withValues(alpha: 0.45),
          width: 2,
        ),

        // Soft shadow
        boxShadow: const [
          BoxShadow(
            color: Color(0x22000000),
            blurRadius: 16,
            offset: Offset(0, 7),
          ),
        ],
      ),
      child: ClipOval(child: imageWidget),
    );
  }

  // ============================================================
  // PHOTO SECTION
  // ============================================================

  Widget _buildPhotoSection() {
    return Column(
      children: [
        Stack(
          alignment: Alignment.bottomRight,
          children: [
            _buildProfilePhoto(),

            // Camera button
            Container(
              width: 40,
              height: 40,
              decoration: BoxDecoration(
                color: primaryGreen,
                shape: BoxShape.circle,
                border: Border.all(color: Colors.white, width: 3),
                boxShadow: const [
                  BoxShadow(
                    color: Color(0x25000000),
                    blurRadius: 8,
                    offset: Offset(0, 3),
                  ),
                ],
              ),
              child: IconButton(
                padding: EdgeInsets.zero,
                tooltip: 'Change profile picture',
                onPressed: isSaving ? null : _pickImage,
                icon: const Icon(
                  Icons.camera_alt_rounded,
                  color: Colors.white,
                  size: 18,
                ),
              ),
            ),
          ],
        ),

        const SizedBox(height: 10),

        TextButton.icon(
          onPressed: isSaving ? null : _pickImage,
          icon: const Icon(Icons.photo_library_outlined, size: 17),
          label: Text(
            _selectedImage == null
                ? 'Change Profile Picture'
                : 'Choose Another Picture',
          ),
          style: TextButton.styleFrom(foregroundColor: darkGreen),
        ),

        if (_selectedImage != null)
          const Text(
            'New picture selected',
            style: TextStyle(
              fontSize: 11,
              color: primaryGreen,
              fontWeight: FontWeight.w600,
            ),
          ),
      ],
    );
  }

  // ============================================================
  // SECTION TITLE
  // ============================================================

  Widget _sectionLabel(String title) {
    return Row(
      children: [
        Expanded(child: Divider(color: Colors.grey.shade300)),
        Padding(
          padding: const EdgeInsets.symmetric(horizontal: 10),
          child: Text(
            title,
            style: TextStyle(
              color: Colors.grey.shade600,
              fontWeight: FontWeight.w800,
              letterSpacing: 1,
              fontSize: 10,
            ),
          ),
        ),
        Expanded(child: Divider(color: Colors.grey.shade300)),
      ],
    );
  }

  // ============================================================
  // SAVE PROFILE
  // ============================================================

  Future<void> _saveProfile() async {
    final newName = _nameController.text.trim();

    if (newName.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Full Name cannot be empty.'),
          behavior: SnackBarBehavior.floating,
        ),
      );
      return;
    }

    if (isSaving) return;

    setState(() {
      isSaving = true;
    });

    try {
      // Update the user's name.
      //
      // IMPORTANT:
      // We do NOT send the G-Suite email here.
      // The email is read-only.
      await _apiService.updateUserProfile(name: newName);

      // Upload the new profile picture separately.
      if (_selectedImage != null) {
        final profileUrl = await _apiService.uploadProfilePicture(
          _selectedImage!,
        );

        if (mounted) {
          setState(() {
            _profilePicture = profileUrl;
          });
        }
      }

      // Tell the rest of the app that the profile changed.
      profileRefreshNotifier.value++;

      if (!mounted) return;

      DialogHelper.showSuccess(
        context: context,
        title: 'Profile Updated!',
        message: 'Your profile has been updated successfully.',
        onOk: () {
          Navigator.pop(context, true);
        },
      );
    } catch (e) {
      if (!mounted) return;

      setState(() {
        isSaving = false;
      });

      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(e.toString()),
          behavior: SnackBarBehavior.floating,
        ),
      );
    }
  }

  // ============================================================
  // BUILD
  // ============================================================

  @override
  Widget build(BuildContext context) {
    final screenWidth = MediaQuery.of(context).size.width;
    final screenHeight = MediaQuery.of(context).size.height;

    final dialogWidth = screenWidth > 560 ? 500.0 : screenWidth - 28;

    return Dialog(
      backgroundColor: Colors.transparent,
      insetPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 24),
      child: ConstrainedBox(
        constraints: BoxConstraints(
          maxWidth: dialogWidth,
          maxHeight: screenHeight * 0.90,
        ),
        child: Container(
          decoration: BoxDecoration(
            color: lightBackground,
            borderRadius: BorderRadius.circular(26),
            border: Border.all(color: const Color(0xFFDCE8DF)),
            boxShadow: const [
              BoxShadow(
                color: Color(0x30000000),
                blurRadius: 28,
                offset: Offset(0, 12),
              ),
            ],
          ),
          child: SingleChildScrollView(
            padding: const EdgeInsets.fromLTRB(20, 18, 20, 20),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // ==================================================
                // HEADER
                // ==================================================
                Row(
                  children: [
                    Container(
                      width: 42,
                      height: 42,
                      decoration: BoxDecoration(
                        color: const Color(0xFFE8F5EE),
                        borderRadius: BorderRadius.circular(13),
                      ),
                      child: const Icon(
                        Icons.edit_rounded,
                        color: primaryGreen,
                        size: 21,
                      ),
                    ),

                    const SizedBox(width: 11),

                    const Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            'Edit Profile',
                            style: TextStyle(
                              fontSize: 20,
                              fontWeight: FontWeight.w800,
                              color: darkGreen,
                            ),
                          ),
                          SizedBox(height: 2),
                          Text(
                            'Update your profile information',
                            style: TextStyle(
                              fontSize: 11,
                              color: Colors.black54,
                            ),
                          ),
                        ],
                      ),
                    ),

                    IconButton(
                      tooltip: 'Close',
                      onPressed: isSaving ? null : () => Navigator.pop(context),
                      icon: const Icon(Icons.close_rounded),
                    ),
                  ],
                ),

                const SizedBox(height: 20),

                // ==================================================
                // PROFILE PICTURE
                // ==================================================
                Center(child: _buildPhotoSection()),

                const SizedBox(height: 18),

                // ==================================================
                // ACCOUNT INFORMATION
                // ==================================================
                _sectionLabel('ACCOUNT INFORMATION'),

                const SizedBox(height: 16),

                // FULL NAME - EDITABLE
                TextField(
                  controller: _nameController,
                  enabled: !isSaving,
                  textCapitalization: TextCapitalization.words,
                  textInputAction: TextInputAction.done,
                  style: const TextStyle(
                    fontSize: 14,
                    fontWeight: FontWeight.w600,
                    color: Color(0xFF1F2933),
                  ),
                  decoration: InputDecoration(
                    isDense: true,
                    labelText: 'Full Name',
                    hintText: 'Enter your full name',
                    prefixIcon: const Icon(
                      Icons.person_outline_rounded,
                      color: primaryGreen,
                      size: 20,
                    ),
                    filled: true,
                    fillColor: fieldBackground,
                    contentPadding: const EdgeInsets.symmetric(
                      horizontal: 14,
                      vertical: 15,
                    ),
                    enabledBorder: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(14),
                      borderSide: const BorderSide(color: Color(0xFFDCE8DF)),
                    ),
                    focusedBorder: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(14),
                      borderSide: const BorderSide(
                        color: primaryGreen,
                        width: 2,
                      ),
                    ),
                  ),
                ),

                const SizedBox(height: 14),

                // SR CODE - READ ONLY
                readOnlyField(
                  'SR Code',
                  widget.studentNumber,
                  Icons.badge_outlined,
                ),

                // G-SUITE EMAIL - READ ONLY
                readOnlyField(
                  'G-Suite Email',
                  widget.email,
                  Icons.email_outlined,
                ),

                // DEPARTMENT - READ ONLY
                readOnlyField(
                  'Department',
                  widget.department,
                  Icons.school_outlined,
                ),

                // CAMPUS - READ ONLY
                readOnlyField(
                  'Campus',
                  widget.campus,
                  Icons.location_on_outlined,
                ),

                const SizedBox(height: 8),

                // ==================================================
                // BUTTONS
                // ==================================================
                Row(
                  children: [
                    Expanded(
                      child: OutlinedButton(
                        onPressed: isSaving
                            ? null
                            : () => Navigator.pop(context),
                        style: OutlinedButton.styleFrom(
                          foregroundColor: darkGreen,
                          disabledForegroundColor: Colors.black26,
                          minimumSize: const Size(double.infinity, 50),
                          side: const BorderSide(color: Color(0xFFD0DDD4)),
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(14),
                          ),
                        ),
                        child: const Text(
                          'Cancel',
                          style: TextStyle(fontWeight: FontWeight.w700),
                        ),
                      ),
                    ),

                    const SizedBox(width: 10),

                    Expanded(
                      flex: 2,
                      child: ElevatedButton.icon(
                        onPressed: isSaving ? null : _saveProfile,
                        style: ElevatedButton.styleFrom(
                          backgroundColor: primaryGreen,
                          foregroundColor: Colors.white,
                          disabledBackgroundColor: primaryGreen.withValues(
                            alpha: 0.45,
                          ),
                          minimumSize: const Size(double.infinity, 50),
                          elevation: 0,
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(14),
                          ),
                        ),
                        icon: isSaving
                            ? const SizedBox(
                                width: 19,
                                height: 19,
                                child: CircularProgressIndicator(
                                  strokeWidth: 2.2,
                                  color: Colors.white,
                                ),
                              )
                            : const Icon(Icons.check_rounded, size: 19),
                        label: Text(
                          isSaving ? 'Saving...' : 'Save Changes',
                          style: const TextStyle(fontWeight: FontWeight.w800),
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
