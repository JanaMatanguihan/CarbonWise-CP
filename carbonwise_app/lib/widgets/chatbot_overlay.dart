import 'package:flutter/material.dart';
import '../services/gemini_service.dart';
import 'chat_bubble.dart';
import 'message_model.dart';
import 'typing_indicator.dart';

class ChatbotOverlay extends StatefulWidget {
  const ChatbotOverlay({super.key});

  @override
  State<ChatbotOverlay> createState() => _ChatbotOverlayState();
}

class _ChatbotOverlayState extends State<ChatbotOverlay>
    with SingleTickerProviderStateMixin {
  final GeminiService _gemini = GeminiService();

  final TextEditingController _controller = TextEditingController();

  final ScrollController _scrollController = ScrollController();

  final List<ChatMessage> _messages = [];

  String _conversationHistory = "";

  bool _isLoading = false;

  late AnimationController _animationController;
  late Animation<double> _scaleAnimation;

  @override
  void initState() {
    super.initState();

    _animationController = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 250),
    );

    _scaleAnimation = CurvedAnimation(
      parent: _animationController,
      curve: Curves.easeOutBack,
    );

    _animationController.forward();

    _messages.add(
      ChatMessage(
        text:
            "Hello! 👋\n\nI'm CarbonWise AI.\n\nAsk me anything about sustainability, transportation, electricity, food, or carbon emissions.",
        isUser: false,
      ),
    );
  }

  @override
  void dispose() {
    _controller.dispose();
    _scrollController.dispose();
    _animationController.dispose();
    super.dispose();
  }

  void _scrollDown() {
    Future.delayed(const Duration(milliseconds: 200), () {
      if (_scrollController.hasClients) {
        _scrollController.animateTo(
          _scrollController.position.maxScrollExtent,
          duration: const Duration(milliseconds: 250),
          curve: Curves.easeOut,
        );
      }
    });
  }

  Future<void> _sendMessage() async {
    if (_controller.text.trim().isEmpty) return;

    final question = _controller.text.trim();

    _controller.clear();

    setState(() {
      // Add user's message
      _messages.add(ChatMessage(text: question, isUser: true));

      // Show typing indicator
      _isLoading = true;
    });

    _scrollDown();

    // Save user's message to conversation history
    _conversationHistory += "\nUser: $question";

    try {
      // Send the whole conversation to Gemini
      final reply = await _gemini.askGemini(_conversationHistory);

      // Save Gemini's reply to conversation history
      _conversationHistory += "\nCarbonWise AI: $reply";

      setState(() {
        // Hide typing indicator
        _isLoading = false;

        // Display Gemini's reply
        _messages.add(ChatMessage(text: reply, isUser: false));
      });

      _scrollDown();
    } catch (e) {
      print("Gemini Error:");
      print(e);

      setState(() {
        _isLoading = false;

        _messages.add(
          ChatMessage(text: "Sorry, something went wrong.", isUser: false),
        );
      });

      _scrollDown();
    }
  }

  @override
  Widget build(BuildContext context) {
    final keyboardHeight = MediaQuery.of(context).viewInsets.bottom;
    final screen = MediaQuery.of(context).size;

    return AnimatedPadding(
      duration: const Duration(milliseconds: 200),
      curve: Curves.easeOut,
      padding: EdgeInsets.only(bottom: keyboardHeight),
      child: Center(
        child: ScaleTransition(
          scale: _scaleAnimation,
          child: Material(
            color: Colors.transparent,
            child: Container(
              width: screen.width * 0.92,
              height: screen.height * 0.75,
              constraints: const BoxConstraints(maxWidth: 420, maxHeight: 700),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(24),
                boxShadow: const [
                  BoxShadow(
                    blurRadius: 18,
                    color: Colors.black26,
                    offset: Offset(0, 6),
                  ),
                ],
              ),
              child: Column(
                children: [
                  Container(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 18,
                      vertical: 16,
                    ),
                    decoration: const BoxDecoration(
                      color: Color(0xFF3AA76D),
                      borderRadius: BorderRadius.vertical(
                        top: Radius.circular(24),
                      ),
                    ),
                    child: Row(
                      children: [
                        const CircleAvatar(
                          backgroundColor: Colors.white,
                          child: Icon(Icons.eco, color: Color(0xFF3AA76D)),
                        ),

                        const SizedBox(width: 12),

                        const Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                "CarbonWise AI",
                                style: TextStyle(
                                  color: Colors.white,
                                  fontSize: 18,
                                  fontWeight: FontWeight.bold,
                                ),
                              ),

                              SizedBox(height: 2),

                              Text(
                                "Your sustainability assistant",
                                style: TextStyle(
                                  color: Colors.white70,
                                  fontSize: 12,
                                ),
                              ),
                            ],
                          ),
                        ),

                        IconButton(
                          onPressed: () {
                            Navigator.pop(context);
                          },
                          icon: const Icon(Icons.close, color: Colors.white),
                        ),
                      ],
                    ),
                  ),
                  Expanded(
                    child: ListView(
                      controller: _scrollController,
                      padding: const EdgeInsets.symmetric(vertical: 10),
                      children: [
                        ..._messages.map(
                          (message) => ChatBubble(message: message),
                        ),

                        if (_isLoading)
                          const Padding(
                            padding: EdgeInsets.only(
                              left: 12,
                              bottom: 10,
                              top: 4,
                            ),
                            child: TypingIndicator(),
                          ),
                      ],
                    ),
                  ),
                  SafeArea(
                    top: false,
                    child: Padding(
                      padding: const EdgeInsets.fromLTRB(12, 8, 12, 12),
                      child: Row(
                        children: [
                          Expanded(
                            child: TextField(
                              controller: _controller,
                              minLines: 1,
                              maxLines: 4,
                              textInputAction: TextInputAction.send,
                              onSubmitted: (_) => _sendMessage(),

                              decoration: InputDecoration(
                                hintText: "Ask anything...",
                                filled: true,
                                fillColor: Colors.grey.shade100,
                                contentPadding: const EdgeInsets.symmetric(
                                  horizontal: 18,
                                  vertical: 14,
                                ),
                                border: OutlineInputBorder(
                                  borderRadius: BorderRadius.circular(30),
                                  borderSide: BorderSide.none,
                                ),
                              ),
                            ),
                          ),

                          const SizedBox(width: 10),

                          CircleAvatar(
                            radius: 25,
                            backgroundColor: const Color(0xFF3AA76D),
                            child: IconButton(
                              onPressed: _sendMessage,
                              icon: const Icon(Icons.send, color: Colors.white),
                            ),
                          ),
                        ],
                      ),
                    ),
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
