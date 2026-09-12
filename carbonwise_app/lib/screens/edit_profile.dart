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
  static const Color primaryGreen = Color(0xFF2E7D32);
  static const Color fieldBackground = Color(0xFFF5F5F5);

  late TextEditingController _nameController;

  final ApiService _apiService = ApiService();
  final ImagePicker _picker = ImagePicker();

  String? _profilePicture;
  File? _selectedImage;

  bool isSaving = false;

  @override
  void initState() {
    super.initState();
    _nameController = TextEditingController(text: widget.fullName.trim());
    _profilePicture = widget.profilePicture;
  }

  @override
  void dispose() {
    _nameController.dispose();
    super.dispose();
  }

  Future<void> _pickImage() async {
    try {
      final picked = await _picker.pickImage(
        source: ImageSource.gallery,
        imageQuality: 80,
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

  Widget readOnlyField(String label, String value, IconData icon) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 14),
      child: TextField(
        readOnly: true,
        controller: TextEditingController(text: value),
        style: const TextStyle(
          fontSize: 13,
          color: Color(0xFF616161),
          fontWeight: FontWeight.w500,
        ),
        decoration: InputDecoration(
          isDense: true,
          labelText: label,
          labelStyle: const TextStyle(fontSize: 12, color: Colors.grey),
          prefixIcon: Icon(icon, color: Colors.grey, size: 20),
          suffixIcon: const Icon(
            Icons.lock_outline_rounded,
            size: 16,
            color: Colors.grey,
          ),
          filled: true,
          fillColor: const Color(0xFFFAFAFA),
          contentPadding: const EdgeInsets.symmetric(
            horizontal: 16,
            vertical: 16,
          ),
          enabledBorder: OutlineInputBorder(
            borderRadius: BorderRadius.circular(16),
            borderSide: BorderSide(color: Colors.grey.shade300),
          ),
          focusedBorder: OutlineInputBorder(
            borderRadius: BorderRadius.circular(16),
            borderSide: BorderSide(color: Colors.grey.shade400),
          ),
        ),
      ),
    );
  }

  // Remade profile photo with better border, shadow, and loading state
  Widget _buildProfilePhoto() {
    Widget imageWidget;

    if (_selectedImage != null) {
      imageWidget = Image.file(
        _selectedImage!,
        width: 120,
        height: 120,
        fit: BoxFit.cover,
      );
    } else if (_profilePicture != null && _profilePicture!.trim().isNotEmpty) {
      imageWidget = Image.network(
        _profilePicture!,
        width: 120,
        height: 120,
        fit: BoxFit.cover,
        loadingBuilder: (context, child, loadingProgress) {
          if (loadingProgress == null) return child;
          return Container(
            width: 120,
            height: 120,
            color: Colors.grey.shade100,
            child: Center(
              child: SizedBox(
                width: 26,
                height: 26,
                child: CircularProgressIndicator(
                  strokeWidth: 2.5,
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
            width: 120,
            height: 120,
            color: Colors.grey.shade100,
            child: const Icon(
              Icons.person_rounded,
              size: 50,
              color: primaryGreen,
            ),
          );
        },
      );
    } else {
      imageWidget = Container(
        width: 120,
        height: 120,
        color: Colors.grey.shade100,
        child: const Icon(Icons.person_rounded, size: 50, color: primaryGreen),
      );
    }

    return Container(
      padding: const EdgeInsets.all(3),
      decoration: BoxDecoration(
        shape: BoxShape.circle,
        color: Colors.white,
        border: Border.all(
          color: primaryGreen.withValues(alpha: 0.6),
          width: 2.5,
        ),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.08),
            blurRadius: 12,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: ClipOval(child: imageWidget),
    );
  }

  Widget _buildPhotoSection() {
    return Column(
      children: [
        Stack(
          alignment: Alignment.bottomRight,
          children: [
            _buildProfilePhoto(),
            Container(
              decoration: BoxDecoration(
                color: primaryGreen,
                shape: BoxShape.circle,
                border: Border.all(color: Colors.white, width: 2.5),
                boxShadow: [
                  BoxShadow(
                    color: Colors.black.withValues(alpha: 0.15),
                    blurRadius: 6,
                    offset: const Offset(0, 2),
                  ),
                ],
              ),
              child: IconButton(
                constraints: const BoxConstraints(minWidth: 36, minHeight: 36),
                padding: EdgeInsets.zero,
                tooltip: 'Change profile picture',
                onPressed: isSaving ? null : _pickImage,
                icon: const Icon(
                  Icons.camera_alt_rounded,
                  color: Colors.white,
                  size: 16,
                ),
              ),
            ),
          ],
        ),
        const SizedBox(height: 12),
        TextButton.icon(
          onPressed: isSaving ? null : _pickImage,
          icon: const Icon(Icons.photo_library_outlined, size: 16),
          label: Text(
            _selectedImage == null
                ? 'Change Profile Picture'
                : 'Choose Another Picture',
            style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 13),
          ),
          style: TextButton.styleFrom(foregroundColor: primaryGreen),
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

  Widget _sectionLabel(String title) {
    return Row(
      children: [
        Expanded(child: Divider(color: Colors.grey.shade300)),
        Padding(
          padding: const EdgeInsets.symmetric(horizontal: 12),
          child: Text(
            title,
            style: TextStyle(
              color: Colors.grey.shade600,
              fontWeight: FontWeight.w700,
              letterSpacing: 0.8,
              fontSize: 11,
            ),
          ),
        ),
        Expanded(child: Divider(color: Colors.grey.shade300)),
      ],
    );
  }

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
      await _apiService.updateUserProfile(name: newName);

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

  @override
  Widget build(BuildContext context) {
    final screenWidth = MediaQuery.of(context).size.width;
    final dialogWidth = screenWidth > 520 ? 460.0 : screenWidth - 32;

    return Dialog(
      backgroundColor: Colors.transparent,
      insetPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 24),
      child: ConstrainedBox(
        constraints: BoxConstraints(
          maxWidth: dialogWidth,
          maxHeight: MediaQuery.of(context).size.height * 0.88,
        ),
        child: Container(
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(24),
            boxShadow: [
              BoxShadow(
                color: Colors.black.withValues(alpha: 0.12),
                blurRadius: 24,
                offset: const Offset(0, 8),
              ),
            ],
          ),
          child: ClipRRect(
            borderRadius: BorderRadius.circular(24),
            child: SingleChildScrollView(
              padding: const EdgeInsets.fromLTRB(24, 20, 24, 24),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Header
                  Row(
                    children: [
                      Container(
                        padding: const EdgeInsets.all(10),
                        decoration: BoxDecoration(
                          color: primaryGreen.withValues(alpha: 0.1),
                          borderRadius: BorderRadius.circular(12),
                        ),
                        child: const Icon(
                          Icons.edit_rounded,
                          color: primaryGreen,
                          size: 20,
                        ),
                      ),
                      const SizedBox(width: 12),
                      const Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              'Edit Profile',
                              style: TextStyle(
                                fontSize: 18,
                                fontWeight: FontWeight.bold,
                                color: Color(0xFF212121),
                              ),
                            ),
                            SizedBox(height: 2),
                            Text(
                              'Update your personal info',
                              style: TextStyle(
                                fontSize: 12,
                                color: Colors.grey,
                              ),
                            ),
                          ],
                        ),
                      ),
                      IconButton(
                        tooltip: 'Close',
                        onPressed: isSaving
                            ? null
                            : () => Navigator.pop(context),
                        icon: const Icon(
                          Icons.close_rounded,
                          color: Colors.grey,
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 20),

                  // Profile Picture Widget Section
                  Center(child: _buildPhotoSection()),
                  const SizedBox(height: 20),

                  _sectionLabel('ACCOUNT INFORMATION'),
                  const SizedBox(height: 16),

                  // Editable Full Name Field
                  TextField(
                    controller: _nameController,
                    enabled: !isSaving,
                    textCapitalization: TextCapitalization.words,
                    style: const TextStyle(
                      fontSize: 14,
                      fontWeight: FontWeight.w600,
                      color: Color(0xFF212121),
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
                        horizontal: 16,
                        vertical: 16,
                      ),
                      enabledBorder: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(16),
                        borderSide: BorderSide(color: Colors.grey.shade300),
                      ),
                      focusedBorder: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(16),
                        borderSide: const BorderSide(
                          color: primaryGreen,
                          width: 2,
                        ),
                      ),
                    ),
                  ),
                  const SizedBox(height: 12),

                  readOnlyField(
                    'SR Code',
                    widget.studentNumber,
                    Icons.badge_outlined,
                  ),
                  readOnlyField(
                    'G-Suite Email',
                    widget.email,
                    Icons.email_outlined,
                  ),
                  readOnlyField(
                    'Department',
                    widget.department,
                    Icons.school_outlined,
                  ),
                  readOnlyField(
                    'Campus',
                    widget.campus,
                    Icons.location_on_outlined,
                  ),
                  const SizedBox(height: 16),

                  // Action Buttons
                  Row(
                    children: [
                      Expanded(
                        child: OutlinedButton(
                          onPressed: isSaving
                              ? null
                              : () => Navigator.pop(context),
                          style: OutlinedButton.styleFrom(
                            foregroundColor: Colors.grey.shade700,
                            minimumSize: const Size(double.infinity, 48),
                            side: BorderSide(color: Colors.grey.shade300),
                            shape: RoundedRectangleBorder(
                              borderRadius: BorderRadius.circular(14),
                            ),
                          ),
                          child: const Text(
                            'Cancel',
                            style: TextStyle(fontWeight: FontWeight.w600),
                          ),
                        ),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        flex: 2,
                        child: ElevatedButton.icon(
                          onPressed: isSaving ? null : _saveProfile,
                          style: ElevatedButton.styleFrom(
                            backgroundColor: primaryGreen,
                            foregroundColor: Colors.white,
                            minimumSize: const Size(double.infinity, 48),
                            elevation: 0,
                            shape: RoundedRectangleBorder(
                              borderRadius: BorderRadius.circular(14),
                            ),
                          ),
                          icon: isSaving
                              ? const SizedBox(
                                  width: 18,
                                  height: 18,
                                  child: CircularProgressIndicator(
                                    strokeWidth: 2,
                                    color: Colors.white,
                                  ),
                                )
                              : const Icon(Icons.check_rounded, size: 18),
                          label: Text(
                            isSaving ? 'Saving...' : 'Save Changes',
                            style: const TextStyle(fontWeight: FontWeight.bold),
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
      ),
    );
  }
}
