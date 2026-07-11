import 'package:flutter/material.dart';
import 'chatbot_overlay.dart';

class ChatbotButton extends StatelessWidget {
  const ChatbotButton({super.key});

  @override
  Widget build(BuildContext context) {
    return FloatingActionButton.extended(
      heroTag: "carbonwise_ai",
      backgroundColor: const Color(0xFF3AA76D),
      foregroundColor: Colors.white,

      icon: const Icon(Icons.eco),

      label: const Text("AI", style: TextStyle(fontWeight: FontWeight.bold)),

      onPressed: () {
        showGeneralDialog(
          context: context,
          barrierDismissible: true,
          barrierLabel: "AI",

          barrierColor: Colors.black45,

          transitionDuration: const Duration(milliseconds: 250),

          pageBuilder: (_, __, ___) {
            return const SafeArea(child: ChatbotOverlay());
          },

          transitionBuilder: (_, animation, __, child) {
            return FadeTransition(opacity: animation, child: child);
          },
        );
      },
    );
  }
}
