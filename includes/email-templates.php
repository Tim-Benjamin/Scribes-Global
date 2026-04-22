<?php

/**
 * Email Template Functions
 * Uses the Mailer class from config/mailer.php
 */

// Newsletter Confirmation Email
function getNewsletterConfirmationEmail($name, $confirmLink) {
    // Make sure the link is properly formatted
    $confirmLink = htmlspecialchars($confirmLink, ENT_QUOTES, 'UTF-8');
    
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: 'Outfit', Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; background: #f9f9f9; }
            .header { background: linear-gradient(135deg, #092573 0%, #1A3FA8 100%); color: white; padding: 2rem; text-align: center; }
            .content { padding: 2rem; background: white; }
            .cta-btn { background: linear-gradient(135deg, #092573 0%, #1A3FA8 100%); color: white; padding: 12px 30px; text-decoration: none; border-radius: 25px; display: inline-block; margin: 1.5rem 0; }
            .footer { background: #f0f0f0; padding: 1rem; text-align: center; font-size: 0.875rem; color: #666; }
            code { background: #f0f0f0; padding: 0.5rem; display: block; word-break: break-all; margin: 1rem 0; font-size: 0.85rem; }
            hr { border: none; border-top: 1px solid #ddd; margin: 2rem 0; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>Confirm Your Subscription</h1>
                <p>Welcome to Scribes Global Newsletter</p>
            </div>
            
            <div class='content'>
                <h2>Hi " . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . ",</h2>
                
                <p>Thank you for signing up to receive updates from Scribes Global! We're excited to share inspiring stories, events, and creative content with you.</p>
                
                <p>To complete your subscription, please confirm your email address by clicking the button below:</p>
                
                <div style='text-align: center;'>
                    <a href='" . $confirmLink . "' class='cta-btn' style='display: inline-block;'>Confirm My Subscription</a>
                </div>
                
                <p style='font-size: 0.875rem; color: #666; margin-top: 2rem;'>
                    <strong>Or copy and paste this link in your browser:</strong><br>
                    <code>" . $confirmLink . "</code>
                </p>
                
                <p style='color: #999; font-size: 0.875rem;'>This link will expire in 48 hours.</p>
                
                <hr>
                
                <p style='font-size: 0.875rem; color: #666;'>
                    If you did not sign up for this newsletter, please ignore this email or contact us immediately.
                </p>
            </div>
            
            <div class='footer'>
                <p>&copy; 2024 Scribes Global. All rights reserved.</p>
                <p>Celebrating creative arts and worship</p>
            </div>
        </div>
    </body>
    </html>
    ";
}

// Event Notification Email for Newsletter Subscribers
function getEventNotificationEmail($name, $eventData) {
    $startDate = date('F j, Y', strtotime($eventData['start_date']));
    $startTime = date('g:i A', strtotime($eventData['start_date']));
    $eventLink = SITE_URL . '/pages/events/details?id=' . $eventData['id'];
    
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: 'Outfit', Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; background: #f9f9f9; }
            .header { background: linear-gradient(135deg, #092573 0%, #1A3FA8 100%); color: white; padding: 2rem; text-align: center; }
            .content { padding: 2rem; background: white; }
            .event-card { border-left: 4px solid #092573; padding: 1.5rem; background: #f9f9f9; margin: 1.5rem 0; border-radius: 8px; }
            .event-card h3 { margin: 0 0 1rem 0; color: #092573; }
            .event-detail { display: flex; align-items: center; gap: 0.75rem; margin: 0.75rem 0; font-size: 0.95rem; }
            .event-detail strong { color: #092573; }
            .cta-btn { background: linear-gradient(135deg, #092573 0%, #1A3FA8 100%); color: white; padding: 12px 30px; text-decoration: none; border-radius: 25px; display: inline-block; margin: 1.5rem 0; }
            .footer { background: #f0f0f0; padding: 1rem; text-align: center; font-size: 0.875rem; color: #666; }
            hr { border: none; border-top: 1px solid #ddd; margin: 2rem 0; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>🎉 New Event Alert!</h1>
                <p>An exciting event you might be interested in</p>
            </div>
            
            <div class='content'>
                <h2>Hi " . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . ",</h2>
                
                <p>A new event has been announced that we think you'll love!</p>
                
                <div class='event-card'>
                    <h3>" . htmlspecialchars($eventData['title'], ENT_QUOTES, 'UTF-8') . "</h3>
                    
                    <div class='event-detail'>
                        <strong>📅 Date:</strong> " . $startDate . "
                    </div>
                    
                    <div class='event-detail'>
                        <strong>🕐 Time:</strong> " . $startTime . "
                    </div>
                    
                    <div class='event-detail'>
                        <strong>📍 Location:</strong> " . htmlspecialchars($eventData['location'], ENT_QUOTES, 'UTF-8') . "
                    </div>
                    
                    <p style='margin-top: 1rem; color: #666; font-size: 0.95rem;'>" . htmlspecialchars(substr($eventData['description'], 0, 250), ENT_QUOTES, 'UTF-8') . "...</p>
                    
                    <div style='text-align: center; margin-top: 1.5rem;'>
                        <a href='" . htmlspecialchars($eventLink, ENT_QUOTES, 'UTF-8') . "' class='cta-btn'>View Full Event Details</a>
                    </div>
                </div>
                
                <hr>
                
                <p style='font-size: 0.875rem; color: #666;'>
                    You received this email because you're subscribed to our newsletter. To manage your preferences, visit our website.
                </p>
            </div>
            
            <div class='footer'>
                <p>&copy; 2024 Scribes Global. All rights reserved.</p>
                <p>Celebrating creative arts and worship</p>
            </div>
        </div>
    </body>
    </html>
    ";
}

?>