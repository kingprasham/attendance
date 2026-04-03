import 'dart:io';
import 'package:device_info_plus/device_info_plus.dart';

class DeviceService {
  static String? _cachedId;

  Future<String> getDeviceId() async {
    if (_cachedId != null) return _cachedId!;

    final info = DeviceInfoPlugin();
    try {
      if (Platform.isAndroid) {
        final android = await info.androidInfo;
        _cachedId = android.id;
      } else if (Platform.isIOS) {
        final ios = await info.iosInfo;
        _cachedId = ios.identifierForVendor ?? 'ios-unknown';
      } else {
        _cachedId = 'unknown-device';
      }
    } catch (_) {
      _cachedId = 'unknown-device';
    }
    return _cachedId!;
  }
}
