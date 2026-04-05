<?php
// api/config/constants.php

// JWT
define('JWT_SECRET', 'Kal1naEng!n33r1ng@Att3nd@nc3$JWT#S3cr3tK3y2026!xZ9pQmRvLwKjHnBt');
define('JWT_ACCESS_EXPIRY', 1800);      // 30 minutes
define('JWT_REFRESH_EXPIRY', 2592000);  // 30 days
define('JWT_ALGORITHM', 'HS256');

// AES Encryption (for PAN, Aadhar)
define('AES_KEY', 'KaL1n@En9!AES256K3y#2026$Pr@sh@m');
define('AES_METHOD', 'aes-256-cbc');

// Timezone
define('APP_TIMEZONE', 'Asia/Kolkata');
define('UTC_OFFSET_IST', '+05:30');

// Rate Limiting
define('RATE_LIMIT_LOGIN', 5);           // 5 attempts
define('RATE_LIMIT_LOGIN_WINDOW', 900);  // per 15 minutes
define('RATE_LIMIT_ATTENDANCE', 10);     // 10 requests
define('RATE_LIMIT_ATTENDANCE_WINDOW', 60); // per 1 minute
define('RATE_LIMIT_GENERAL', 100);       // 100 requests
define('RATE_LIMIT_GENERAL_WINDOW', 60); // per 1 minute

// Late Threshold (default 9:30 AM IST)
define('DEFAULT_LATE_HOUR', 9);
define('DEFAULT_LATE_MINUTE', 30);

// Employee Code Prefix
define('EMPLOYEE_CODE_PREFIX', 'KE');
