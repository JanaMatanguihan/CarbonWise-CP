import 'package:flutter/material.dart';
import '../services/gemini_service.dart';
import 'chat_bubble.dart';
import 'message_model.dart';
import 'typing_indicator.dart';
import '../services/chat_history_service.dart';
import 'date_separator.dart';

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

  final ChatHistoryService _history = ChatHistoryService();

  bool _isLoading = false;

  bool _isSameDay(DateTime a, DateTime b) {
    return a.year == b.year && a.month == b.month && a.day == b.day;
  }

  String _formatDate(DateTime date) {
    final now = DateTime.now();

    final today = DateTime(now.year, now.month, now.day);

    final yesterday = today.subtract(const Duration(days: 1));

    final messageDay = DateTime(date.year, date.month, date.day);

    if (messageDay == today) {
      return "Today";
    }

    if (messageDay == yesterday) {
      return "Yesterday";
    }

    const months = [
      "",
      "Jan",
      "Feb",
      "Mar",
      "Apr",
      "May",
      "Jun",
      "Jul",
      "Aug",
      "Sep",
      "Oct",
      "Nov",
      "Dec",
    ];

    return "${months[date.month]} ${date.day}, ${date.year}";
  }

  String _buildConversationHistory() {
    String history = "";

    for (final message in _messages) {
      if (message.isUser) {
        history += "\nUser: ${message.text}";
      } else {
        history += "\nCarbonWise AI: ${message.text}";
      }
    }

    return history;
  }

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

    _loadMessages();
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

    final userMessage = ChatMessage(text: question, isUser: true);

    setState(() {
      _messages.add(userMessage);
      _isLoading = true;
    });

    // Save user message
    await _history.saveMessage(userMessage);

    _scrollDown();

    try {
      // Build the conversation history from all previous messages
      final history = _buildConversationHistory();

      // Send the conversation to Gemini
      final reply = await _gemini.askGemini(history);

      final botMessage = ChatMessage(text: reply, isUser: false);

      // Save bot message
      await _history.saveMessage(botMessage);

      setState(() {
        _isLoading = false;
        _messages.add(botMessage);
      });
    } catch (e) {
      print(e);

      setState(() {
        _isLoading = false;

        _messages.add(
          ChatMessage(text: "Sorry, something went wrong.", isUser: false),
        );
      });
    }

    _scrollDown();
  }

  Future<void> _loadMessages() async {
    print("Loading chat history...");

    final messages = await _history.loadMessages();

    print("Number of messages: ${messages.length}");

    setState(() {
      _messages.clear();

      if (messages.isEmpty) {
        print("No previous messages.");

        _messages.add(
          ChatMessage(
            text:
                "Hello! 👋\n\nI'm CarbonWise AI.\n\nAsk me anything about sustainability.",
            isUser: false,
          ),
        );
      } else {
        print("Loaded previous messages.");

        _messages.addAll(messages);
      }
    });

    _scrollDown();
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
                    child: ListView.builder(
                      controller: _scrollController,
                      padding: const EdgeInsets.symmetric(vertical: 10),
                      itemCount: _messages.length + (_isLoading ? 1 : 0),
                      itemBuilder: (context, index) {
                        if (_isLoading && index == _messages.length) {
                          return const Padding(
                            padding: EdgeInsets.only(left: 12, bottom: 10),
                            child: TypingIndicator(),
                          );
                        }

                        final message = _messages[index];

                        Widget bubble = ChatBubble(message: message);

                        // Show a date separator if this is the first message or the day changed.
                        if (index == 0 ||
                            !_isSameDay(
                              message.time,
                              _messages[index - 1].time,
                            )) {
                          return Column(
                            children: [
                              DateSeparator(text: _formatDate(message.time)),
                              bubble,
                            ],
                          );
                        }

                        return bubble;
                      },
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
