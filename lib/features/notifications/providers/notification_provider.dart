import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/network/api_client.dart';
import '../../../core/constants/api_endpoints.dart';

class NotificationState {
  final bool isLoading;
  final List<Map<String, dynamic>> notifications;

  const NotificationState({
    this.isLoading = false,
    this.notifications = const [],
  });

  NotificationState copyWith({
    bool? isLoading,
    List<Map<String, dynamic>>? notifications,
  }) {
    return NotificationState(
      isLoading: isLoading ?? this.isLoading,
      notifications: notifications ?? this.notifications,
    );
  }
}

class NotificationNotifier extends StateNotifier<NotificationState> {
  final ApiClient _api;

  NotificationNotifier(this._api) : super(const NotificationState());

  Future<void> loadNotifications() async {
    state = state.copyWith(isLoading: true);
    try {
      final response = await _api.get(ApiEndpoints.notificationsList);
      state = state.copyWith(
        isLoading: false,
        notifications: List<Map<String, dynamic>>.from(response['data'] ?? []),
      );
    } catch (_) {
      state = state.copyWith(isLoading: false);
    }
  }

  Future<void> markRead(int notificationId) async {
    try {
      await _api.post(
        ApiEndpoints.notificationsMarkRead,
        data: {'notification_id': notificationId},
      );
      state = state.copyWith(
        notifications: state.notifications.map((n) {
          if (n['id'] == notificationId) {
            return {...n, 'is_read': 1};
          }
          return n;
        }).toList(),
      );
    } catch (_) {}
  }
}

final notificationProvider =
    StateNotifierProvider<NotificationNotifier, NotificationState>((ref) {
  return NotificationNotifier(ApiClient.instance);
});
