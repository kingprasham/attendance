import 'package:geolocator/geolocator.dart';
import 'package:safe_device/safe_device.dart';

class LocationResult {
  final double latitude;
  final double longitude;
  const LocationResult(this.latitude, this.longitude);
}

class LocationException implements Exception {
  final String message;
  const LocationException(this.message);
  @override
  String toString() => message;
}

class LocationService {
  Future<LocationResult> getCurrentLocation() async {
    // Check for fake GPS / rooted device
    final isMockLocation = await SafeDevice.isMockLocation;
    if (isMockLocation) {
      throw const LocationException(
        'Mock location detected. Disable fake GPS apps and try again.',
      );
    }

    // Check permission
    LocationPermission permission = await Geolocator.checkPermission();
    if (permission == LocationPermission.denied) {
      permission = await Geolocator.requestPermission();
      if (permission == LocationPermission.denied) {
        throw const LocationException('Location permission denied.');
      }
    }
    if (permission == LocationPermission.deniedForever) {
      throw const LocationException(
        'Location permission permanently denied. Enable it in Settings.',
      );
    }

    // Check if GPS is on
    final serviceEnabled = await Geolocator.isLocationServiceEnabled();
    if (!serviceEnabled) {
      throw const LocationException('GPS is disabled. Please enable location services.');
    }

    try {
      final position = await Geolocator.getCurrentPosition(
        locationSettings: const LocationSettings(
          accuracy: LocationAccuracy.high,
          timeLimit: Duration(seconds: 15),
        ),
      );
      return LocationResult(position.latitude, position.longitude);
    } catch (e) {
      throw LocationException('Could not get location: ${e.toString()}');
    }
  }
}
