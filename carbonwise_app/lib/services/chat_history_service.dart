import 'package:supabase_flutter/supabase_flutter.dart';
import '../widgets/message_model.dart';

class ChatHistoryService {
  final supabase = Supabase.instance.client;

  Future<void> saveMessage(ChatMessage message) async {
    try {
      final user = supabase.auth.currentUser;

      print("Current user: ${user?.email}");

      if (user == null) return;

      await supabase.from('chatbot_messages').insert({
        'g_suite': user.email,
        'message': message.text,
        'is_user': message.isUser,
      });

      print("Message saved successfully!");
    } catch (e) {
      print("SAVE MESSAGE ERROR:");
      print(e);
    }
  }

  Future<List<ChatMessage>> loadMessages() async {
    try {
      final user = supabase.auth.currentUser;

      print("Current user: ${user?.email}");

      if (user == null) {
        print("No user logged in.");
        return [];
      }

      final response = await supabase
          .from('chatbot_messages')
          .select()
          .eq('g_suite', user.email!)
          .order('created_at');

      print("Messages loaded:");
      print(response);

      return response.map<ChatMessage>((item) {
        return ChatMessage(text: item['message'], isUser: item['is_user']);
      }).toList();
    } catch (e) {
      print("LOAD ERROR:");
      print(e);

      return [];
    }
  }
}
