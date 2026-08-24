<?php

declare(strict_types=1);

use App\Core\Config;

return [
    'provider' => Config::get('SMS_PROVIDER', 'netgsm'),
    'enabled' => Config::bool('SMS_ENABLED', false),
    'test_mode' => Config::bool('SMS_TEST_MODE', true),
    'test_phone' => Config::get('SMS_TEST_PHONE', ''),
    'force_to' => Config::get('SMS_FORCE_TO', ''),
    'max_recipients_per_request' => (int) Config::get('SMS_MAX_RECIPIENTS_PER_REQUEST', '1000'),
    'max_retry_count' => (int) Config::get('SMS_MAX_RETRY_COUNT', '3'),
    'retry_delay_minutes' => (int) Config::get('SMS_RETRY_DELAY_MINUTES', '10'),
    'appointment_reminder_enabled' => Config::bool('SMS_APPOINTMENT_REMINDER_ENABLED', true),
    'appointment_reminder_hours' => (int) Config::get('SMS_APPOINTMENT_REMINDER_HOURS', '24'),
    'appointment_reminder_days_before' => (int) Config::get('SMS_APPOINTMENT_REMINDER_DAYS_BEFORE', '1'),
    'appointment_reminder_time' => Config::get('SMS_APPOINTMENT_REMINDER_TIME', '14:00'),
    'birthday_message_enabled' => Config::bool('SMS_BIRTHDAY_MESSAGE_ENABLED', true),
    'birthday_message_time' => Config::get('SMS_BIRTHDAY_MESSAGE_TIME', '09:00'),
    'payment_promise_reminder_enabled' => Config::bool('SMS_PAYMENT_PROMISE_REMINDER_ENABLED', true),
    'payment_promise_reminder_hours' => (int) Config::get('SMS_PAYMENT_PROMISE_REMINDER_HOURS', '24'),
    'netgsm' => [
        'usercode' => Config::get('NETGSM_USERCODE', ''),
        'password' => Config::get('NETGSM_PASSWORD', ''),
        'header' => Config::get('NETGSM_HEADER', ''),
        'encoding' => Config::get('NETGSM_ENCODING', 'TR'),
        'filter' => Config::get('NETGSM_FILTER', '0'),
        'base_url' => rtrim((string) Config::get('NETGSM_API_BASE_URL', 'https://api.netgsm.com.tr'), '/'),
        'send_path' => Config::get('NETGSM_SEND_PATH', '/sms/rest/v2/send'),
        'report_path' => Config::get('NETGSM_REPORT_PATH', '/sms/report'),
        'connect_timeout' => (int) Config::get('NETGSM_CONNECT_TIMEOUT', '10'),
        'timeout' => (int) Config::get('NETGSM_TIMEOUT', '30'),
    ],
];
