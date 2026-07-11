import 'package:flutter/material.dart';

class TypingIndicator extends StatelessWidget {
  const TypingIndicator({super.key});

  @override
  Widget build(BuildContext context) {
    return const Padding(
      padding: EdgeInsets.all(15),
      child: Row(
        children: [
          SizedBox(
            width: 18,
            height: 18,
            child: CircularProgressIndicator(strokeWidth: 2),
          ),

          SizedBox(width: 10),

          Text(
            "CarbonWise AI is thinking...",
            style: TextStyle(color: Colors.grey),
          ),
        ],
      ),
    );
  }
}
