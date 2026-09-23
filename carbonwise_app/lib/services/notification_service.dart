import 'package:carbonwise_app/services/api_service.dart';

/// Generates notifications from user actions and pushes them to the
/// Laravel backend so they appear in the notification bell.
class NotificationService {
  static final ApiService _api = ApiService();

  // Tracks achievements already notified in this app session
  // so we don't spam the user with duplicate unlocks.
  static final Set<String> _notifiedAchievements = {};

  /// Call this after a carbon record is successfully saved.
  ///
  /// Compares today's total emission to the user's recent average and
  /// produces a friendly notification for notable changes.
  static Future<void> onCarbonRecordSaved({
    required String email,
    required double todayEmission,
    required List<dynamic> recentRecords,
  }) async {
    try {
      // First ever record → welcome notification
      if (recentRecords.length <= 1) {
        await _safeAdd(
          email: email,
          title: "Welcome to CarbonWise! 🌱",
          message:
              "You logged your first activity. Keep tracking daily to build your eco profile.",
          type: "success",
        );
        return;
      }

      // Average of the *other* records (skip the one we just saved)
      final priorRecords = recentRecords.skip(1).toList();
      if (priorRecords.isEmpty) return;

      double total = 0;
      int count = 0;
      for (final record in priorRecords) {
        final map = Map<String, dynamic>.from(record as Map);
        total += double.tryParse(map['total_emission']?.toString() ?? '0') ?? 0;
        count++;
      }
      if (count == 0) return;

      final average = total / count;
      if (average <= 0) return;

      final changePercent = ((todayEmission - average) / average) * 100;

      // 10%+ reduction → positive notification
      if (changePercent <= -10) {
        await _safeAdd(
          email: email,
          title: "Great progress! 💚",
          message:
              "You emitted ${changePercent.abs().toStringAsFixed(0)}% less than your recent average. Keep it up!",
          type: "success",
        );
        return;
      }

      // 20%+ increase → warning notification
      if (changePercent >= 20) {
        await _safeAdd(
          email: email,
          title: "Emissions increased ⚠️",
          message:
              "Today's emission is ${changePercent.toStringAsFixed(0)}% higher than your average. Try to reduce tomorrow.",
          type: "warning",
        );
        return;
      }

      // Otherwise: no notification — don't spam for small changes.
    } catch (e) {
      // Notifications are a nice-to-have. Never let them break the app.
      print("NotificationService.onCarbonRecordSaved error: $e");
    }
  }

  /// Call this from the profile screen after loading user stats.
  /// Fires a notification for any newly-unlocked achievement.
  static Future<void> onAchievementsChecked({
    required String email,
    required Map<String, bool> unlockedAchievements,
  }) async {
    try {
      for (final entry in unlockedAchievements.entries) {
        final name = entry.key;
        final unlocked = entry.value;

        if (!unlocked) continue;
        if (_notifiedAchievements.contains(name)) continue;

        // Mark as notified even if the API call fails,
        // so we don't retry every time the profile screen loads.
        _notifiedAchievements.add(name);

        await _safeAdd(
          email: email,
          title: "Achievement unlocked! 🎉",
          message:
              "You earned the \"$name\" badge. Tap your profile to see it.",
          type: "achievement",
        );
      }
    } catch (e) {
      print("NotificationService.onAchievementsChecked error: $e");
    }
  }

  /// Call this on logout so the next user doesn't see cached achievement state.
  static void reset() {
    _notifiedAchievements.clear();
  }

  /// Wrapper that swallows API errors so callers don't have to try/catch.
  static Future<void> _safeAdd({
    required String email,
    required String title,
    required String message,
    required String type,
  }) async {
    try {
      await _api.addNotification(
        email: email,
        title: title,
        message: message,
        type: type,
      );
    } catch (e) {
      print("NotificationService._safeAdd failed: $e");
    }
  }
}
