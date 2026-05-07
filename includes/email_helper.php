<?php
/**
 * EthioEvent Hub - Advanced Email Notification System
 * This module handles automated communication using premium HTML templates.
 * In a local environment, these are logged to logs/mail_sandbox.log for verification.
 */

require_once __DIR__ . '/functions.php';

/**
 * Sends a stylized HTML booking confirmation.
 */
function sendBookingConfirmationEmail($user_email, $user_name, $booking, $event) {
    $subject = "🎫 Booking Confirmation - " . h($event['title']);
    
    $message = "
    <html>
    <head>
        <style>
            body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #333; }
            .wrapper { max-width: 600px; margin: 0 auto; border: 1px solid #eee; border-radius: 15px; overflow: hidden; }
            .header { background: linear-gradient(135deg, #6366f1, #9333ea); padding: 40px 20px; color: white; text-align: center; }
            .content { padding: 30px; }
            .ticket { border: 2px dashed #6366f1; padding: 25px; margin: 25px 0; border-radius: 15px; background: #f8fafc; }
            .ticket-title { color: #1e1b4b; margin-top: 0; font-size: 22px; }
            .footer { background: #f1f5f9; padding: 20px; text-align: center; font-size: 12px; color: #64748b; }
            .btn { display: inline-block; padding: 12px 25px; background: #6366f1; color: white; text-decoration: none; border-radius: 50px; font-weight: bold; margin-top: 20px; }
        </style>
    </head>
    <body>
        <div class='wrapper'>
            <div class='header'>
                <h1 style='margin:0;'>EthioEvent Hub</h1>
                <p style='margin:5px 0 0;'>Your Premium Ticket is Confirmed!</p>
            </div>
            <div class='content'>
                <h3>Hello {$user_name},</h3>
                <p>Congratulations! Your booking for the upcoming event has been successfully confirmed. We're excited to have you join us.</p>
                
                <div class='ticket'>
                    <h3 class='ticket-title'>" . h($event['title']) . "</h3>
                    <p style='margin:5px 0;'><strong>📅 Date:</strong> " . date('M d, Y', strtotime($event['event_date'])) . "</p>
                    <p style='margin:5px 0;'><strong>📍 Venue:</strong> " . h($event['venue']) . "</p>
                    <p style='margin:5px 0;'><strong>🎟️ Quantity:</strong> {$booking['quantity']} Tickets</p>
                    <p style='margin:5px 0;'><strong>💰 Total:</strong> ETB " . number_format($booking['total_price'], 2) . "</p>
                    <p style='margin:15px 0 0; color:#6366f1; font-family:monospace; font-size:18px;'><strong>REF: {$booking['booking_reference']}</strong></p>
                </div>
                
                <p>Please present the reference code or your digital ticket at the entrance.</p>
                <a href='#' class='btn'>View in Dashboard</a>
                <p style='margin-top:25px;'>Enjoy the experience! 🎉</p>
            </div>
            <div class='footer'>
                <p>EthioEvent Hub - Experience Ethiopia Like Never Before</p>
                <p>&copy; 2026 EthioEvent Hub Team</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    // In production, use real mail() or PHPMailer
    // mail($user_email, $subject, $message, $headers);
    
    return sendSimulatedEmail($user_email, $subject, $message);
}

/**
 * Sends a welcome email after registration.
 */
function sendWelcomeEmail($user_email, $user_name) {
    $subject = "✨ Welcome to EthioEvent Hub!";
    $message = "
    <html>
    <body style='font-family: Arial, sans-serif;'>
        <div style='padding: 20px; border: 1px solid #eee; border-radius: 10px;'>
            <h2 style='color: #6366f1;'>Welcome, {$user_name}!</h2>
            <p>Thank you for joining EthioEvent Hub, Ethiopia's premier event platform.</p>
            <p>You can now browse events, follow your favorite organizers, and book tickets instantly.</p>
            <p>Ready to explore?</p>
            <a href='#' style='padding: 10px 20px; background: #6366f1; color: white; text-decoration: none; border-radius: 5px;'>Start Browsing</a>
        </div>
    </body>
    </html>
    ";
    return sendSimulatedEmail($user_email, $subject, $message);
}

/**
 * Sends a password reset link.
 */
function sendPasswordResetEmail($user_email, $user_name, $reset_link) {
    $subject = "🔑 Password Reset Request";
    $message = "
    <html>
    <body style='font-family: Arial, sans-serif;'>
        <div style='padding: 20px; border: 1px solid #eee; border-radius: 10px;'>
            <h2 style='color: #ef4444;'>Security Alert</h2>
            <p>Hello {$user_name},</p>
            <p>We received a request to reset your password. If you didn't make this request, you can safely ignore this email.</p>
            <p>To reset your password, click the button below:</p>
            <a href='{$reset_link}' style='padding: 10px 20px; background: #ef4444; color: white; text-decoration: none; border-radius: 5px;'>Reset Password</a>
            <p style='margin-top:20px; font-size: 12px; color: #666;'>This link will expire in 1 hour.</p>
        </div>
    </body>
    </html>
    ";
    return sendSimulatedEmail($user_email, $subject, $message);
}
?>
