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

  // Info Dialog

  static void showInfo({
    required BuildContext context,
    required String title,
    required String message,
    VoidCallback? onOk,
  }) {
    AwesomeDialog(
      context: context,
      dialogType: DialogType.info,
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

      btnOkOnPress: onOk ?? () {},
    ).show();
  }

  // Calculation Summary Dialog

  static void showCalculationSummary({
    required BuildContext context,
    required List<String> transportEmissions,
    required List<String> officeEmissions,
    required List<String> foodEmissions,
  }) {
    AwesomeDialog(
      context: context,
      dialogType: DialogType.info,
      animType: AnimType.scale,
      dialogBackgroundColor: background,

      body: Padding(
        padding: const EdgeInsets.all(20),
        child: SingleChildScrollView(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const Center(
                child: Icon(Icons.eco, color: primaryGreen, size: 50),
              ),

              const SizedBox(height: 15),

              const Center(
                child: Text(
                  "Carbon Emissions Summary",
                  textAlign: TextAlign.center,
                  style: TextStyle(
                    fontSize: 22,
                    fontWeight: FontWeight.bold,
                    color: primaryGreen,
                  ),
                ),
              ),

              const SizedBox(height: 8),

              const Center(
                child: Text(
                  "Here is your carbon emission summary for today!",
                  textAlign: TextAlign.center,
                  style: TextStyle(fontSize: 14, color: Colors.black54),
                ),
              ),

              const SizedBox(height: 20),

              if (transportEmissions.isNotEmpty) ...[
                const Text(
                  "🚗 Transportation",
                  style: TextStyle(
                    fontWeight: FontWeight.bold,
                    color: primaryGreen,
                    fontSize: 16,
                  ),
                ),
                const SizedBox(height: 5),
                ...transportEmissions.map(
                  (e) => Padding(
                    padding: const EdgeInsets.only(left: 10, bottom: 3),
                    child: Text("• $e"),
                  ),
                ),
                const SizedBox(height: 15),
              ],

              if (officeEmissions.isNotEmpty) ...[
                const Text(
                  "💻 Office Resources",
                  style: TextStyle(
                    fontWeight: FontWeight.bold,
                    color: primaryGreen,
                    fontSize: 16,
                  ),
                ),
                const SizedBox(height: 5),
                ...officeEmissions.map(
                  (e) => Padding(
                    padding: const EdgeInsets.only(left: 10, bottom: 3),
                    child: Text("• $e"),
                  ),
                ),
                const SizedBox(height: 15),
              ],

              if (foodEmissions.isNotEmpty) ...[
                const Text(
                  "🍽 Food Consumption",
                  style: TextStyle(
                    fontWeight: FontWeight.bold,
                    color: primaryGreen,
                    fontSize: 16,
                  ),
                ),
                const SizedBox(height: 5),
                ...foodEmissions.map(
                  (e) => Padding(
                    padding: const EdgeInsets.only(left: 10, bottom: 3),
                    child: Text("• $e"),
                  ),
                ),
              ],
            ],
          ),
        ),
      ),

      btnOkColor: primaryGreen,
      btnOkText: "Done",
      btnOkOnPress: () {},
    ).show();
  }
}
