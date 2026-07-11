import 'package:flutter/material.dart';
import 'message_model.dart';

class ChatBubble extends StatelessWidget {
  final ChatMessage message;

  const ChatBubble({super.key, required this.message});

  @override
  Widget build(BuildContext context) {
    final bool isUser = message.isUser;

    return Align(
      alignment: isUser ? Alignment.centerRight : Alignment.centerLeft,

      child: Container(
        margin: const EdgeInsets.symmetric(vertical: 6, horizontal: 12),

        constraints: BoxConstraints(
          maxWidth: MediaQuery.of(context).size.width * 0.72,
        ),

        padding: const EdgeInsets.all(14),

        decoration: BoxDecoration(
          color: isUser ? const Color(0xFF3AA76D) : Colors.grey.shade200,

          borderRadius: BorderRadius.only(
            topLeft: const Radius.circular(18),
            topRight: const Radius.circular(18),

            bottomLeft: Radius.circular(isUser ? 18 : 4),

            bottomRight: Radius.circular(isUser ? 4 : 18),
          ),
        ),

        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              message.text,
              style: TextStyle(
                fontSize: 15,
                color: isUser ? Colors.white : Colors.black87,
              ),
            ),

            const SizedBox(height: 5),

            Align(
              alignment: Alignment.bottomRight,
              child: Text(
                "${message.time.hour.toString().padLeft(2, '0')}:${message.time.minute.toString().padLeft(2, '0')}",

                style: TextStyle(
                  fontSize: 10,
                  color: isUser ? Colors.white70 : Colors.grey,
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
