<?php
declare(strict_types=1);

namespace App\Services;

/**
 * EmailTemplate
 *
 * Centralized email template builder for member setup invitations,
 * password resets, and booking confirmations.
 */
class EmailTemplate
{
    public static function invitation(string $name, string $url): string
    {
        return '
        <div style="font-family:sans-serif;max-width:600px;margin:0 auto;padding:24px;background:#ffffff;border:1px solid #e5e7eb;border-radius:12px;">
            <h2 style="color:#1E5631;">Welcome to Pine Valley Golf Club</h2>
            <p>Dear ' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . ',</p>
            <p>Your member account has been registered. Please click below to activate your account and choose your password:</p>
            <p style="margin:24px 0;"><a href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" style="background:#1E5631;color:#ffffff;padding:12px 24px;border-radius:8px;text-decoration:none;font-weight:600;">Set Your Password</a></p>
            <p style="color:#6b7280;font-size:12px;">This link expires in 7 days.</p>
        </div>';
    }

    public static function passwordReset(string $name, string $url): string
    {
        return '
        <div style="font-family:sans-serif;max-width:600px;margin:0 auto;padding:24px;background:#ffffff;border:1px solid #e5e7eb;border-radius:12px;">
            <h2 style="color:#1E5631;">Password Reset Request</h2>
            <p>Dear ' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . ',</p>
            <p>We received a request to reset your password. Click below to proceed:</p>
            <p style="margin:24px 0;"><a href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" style="background:#1E5631;color:#ffffff;padding:12px 24px;border-radius:8px;text-decoration:none;font-weight:600;">Reset Password</a></p>
            <p style="color:#6b7280;font-size:12px;">This link expires in 1 hour.</p>
        </div>';
    }

    public static function bookingConfirmation(string $name, array $booking): string
    {
        return '
        <div style="font-family:sans-serif;max-width:600px;margin:0 auto;padding:24px;background:#ffffff;border:1px solid #e5e7eb;border-radius:12px;">
            <h2 style="color:#1E5631;">Tee Time Confirmed</h2>
            <p>Dear ' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . ',</p>
            <p>Your tee time at Pine Valley Golf Club is confirmed for ' . htmlspecialchars($booking['date'] ?? '', ENT_QUOTES, 'UTF-8') . ' at ' . htmlspecialchars($booking['time'] ?? '', ENT_QUOTES, 'UTF-8') . '.</p>
        </div>';
    }
}
