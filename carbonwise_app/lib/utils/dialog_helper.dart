import 'package:awesome_dialog/awesome_dialog.dart';
import 'package:flutter/material.dart';

class DialogHelper {
  static const Color primaryGreen = Color(0xFF265D3B);
  static const Color background = Color(0xFFEFEFEA);

  // Success Dialog

  static void showSuccess({
    required BuildContext context,
    required String title,
    required String message,
    VoidCallback? onOk,
  }) {
    AwesomeDialog(
      context: context,
      dialogType: DialogType.success,
      animType: AnimType.scale,
      dialogBackgroundColor: background,

      title: title,
      desc: message,

      titleTextStyle: const TextStyle(
        fontSize: 22,
        fontWeight: FontWeight.bold,
        color: primaryGreen,
      ),

      descTextStyle: const TextStyle(fontSize: 16),

      btnOkColor: primaryGreen,
      btnOkText: "OK",

      btnOkOnPress: onOk ?? () {},
    ).show();
  }

  // Error Dialog

  static void showError({
    required BuildContext context,
    required String title,
    required String message,
  }) {
    AwesomeDialog(
      context: context,
      dialogType: DialogType.error,
      animType: AnimType.scale,
      dialogBackgroundColor: background,

      title: title,
      desc: message,

      titleTextStyle: const TextStyle(
        fontSize: 22,
        fontWeight: FontWeight.bold,
        color: primaryGreen,
      ),

      btnOkColor: primaryGreen,
      btnOkText: "OK",

      btnOkOnPress: () {},
    ).show();
  }

  // Confirm Dialog

  static void showConfirm({
    required BuildContext context,
    required String title,
    required String message,
    required VoidCallback onConfirm,
  }) {
    AwesomeDialog(
      context: context,
      dialogType: DialogType.question,
      animType: AnimType.scale,
      dialogBackgroundColor: background,

      title: title,
      desc: message,

      titleTextStyle: const TextStyle(
        fontSize: 22,
        fontWeight: FontWeight.bold,
        color: primaryGreen,
      ),

      btnCancelColor: Colors.grey,
      btnOkColor: primaryGreen,

      btnCancelText: "Cancel",
      btnOkText: "Yes",

      btnCancelOnPress: () {},

      btnOkOnPress: onConfirm,
    ).show();
  }
}
