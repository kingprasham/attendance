<?php
// api/helpers/geofence.php

/**
 * Calculate distance between two GPS coordinates using Haversine formula.
 * Returns distance in meters.
 */
function haversine_distance($lat1, $lng1, $lat2, $lng2) {
    $earth_radius = 6371000; // meters

    $lat1_rad = deg2rad($lat1);
    $lat2_rad = deg2rad($lat2);
    $delta_lat = deg2rad($lat2 - $lat1);
    $delta_lng = deg2rad($lng2 - $lng1);

    $a = sin($delta_lat / 2) * sin($delta_lat / 2) +
         cos($lat1_rad) * cos($lat2_rad) *
         sin($delta_lng / 2) * sin($delta_lng / 2);

    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

    return $earth_radius * $c;
}

/**
 * Check if employee coordinates are within branch geofence.
 * Returns true if within radius, false otherwise.
 */
function is_within_geofence($emp_lat, $emp_lng, $branch_lat, $branch_lng, $radius_meters) {
    $distance = haversine_distance($emp_lat, $emp_lng, $branch_lat, $branch_lng);
    return $distance <= $radius_meters;
}
