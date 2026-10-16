<?php
/**
 * Mail Sender Utility
 * Sends emails via Gmail SMTP using PHPMailer
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/email_config.php';

class MailSender {
    
    /**
     * Send verification code email
     */
    public static function sendVerificationCode($toEmail, $code) {
        $mail = new PHPMailer(true);
        
        try {
            // Server settings
            $mail->SMTPDebug = 0; // Disable debug output to prevent interference
            $mail->Debugoutput = function($str, $level) {
                error_log("PHPMailer: $str");
            };
            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = GMAIL_USERNAME;
            $mail->Password = GMAIL_PASSWORD;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = SMTP_PORT;
            $mail->Timeout = 30;
            
            // Recipients
            $mail->setFrom(GMAIL_FROM_EMAIL, GMAIL_FROM_NAME);
            $mail->addAddress($toEmail);
            
            // Content
            $mail->isHTML(true);
            $mail->CharSet = 'UTF-8';
            $currentYear = date('Y');
            $mail->Subject = 'Email Verification Code - Gujarat Memory Championship' . ' ' . $currentYear;
            $mail->Body = self::getEmailHtmlBody($code);
            $mail->AltBody = "Your verification code is: " . $code . "\n\nThis code will expire in 10 minutes.";
            
            $result = $mail->send();
            return $result;
            
        } catch (Exception $e) {
            // Log detailed error
            $errorMsg = "Email Error: " . $mail->ErrorInfo . " | Exception: " . $e->getMessage();
            error_log($errorMsg);
            
            // Store error in session for display
            $_SESSION['email_error_detail'] = $errorMsg;
            
            return false;
        }
    }
    
    /**
     * Send congratulations email for passing the test
     */
    public static function sendCongratulationsEmail($toEmail, $studentName) {
        $mail = new PHPMailer(true);
        
        try {
            // Server settings
            $mail->SMTPDebug = 0; // Disable debug output for congratulations emails
            $mail->Debugoutput = function($str, $level) {
                error_log("PHPMailer: $str");
            };
            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = GMAIL_USERNAME;
            $mail->Password = GMAIL_PASSWORD;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = SMTP_PORT;
            $mail->Timeout = 30;
            
            // Recipients
            $mail->setFrom(GMAIL_FROM_EMAIL, GMAIL_FROM_NAME);
            $mail->addAddress($toEmail);

      // Content
      $currentYear = date('Y');
            $mail->isHTML(true);
            $mail->CharSet = 'UTF-8';
            $mail->Subject = 'Congratulations! You Passed the Gujarat Memory Championship ' . $currentYear;
            $mail->Body = self::getCongratulationsHtmlBody($studentName);
            $mail->AltBody = "Congratulations " . $studentName . "!\n\nYou have passed the Gujarat Memory Championship " . $currentYear . ". For certificate, we will contact you soon.\nFor help, call " . CONTACT_NUMBER . ".\n\nThank you,\nTeam MSFG";
            
            $result = $mail->send();
            return $result;
            
        } catch (Exception $e) {
            // Log detailed error
            $errorMsg = "Email Error: " . $mail->ErrorInfo . " | Exception: " . $e->getMessage();
            error_log($errorMsg);
            
            return false;
        }
    }
    
    /**
     * Get email HTML body template
     */
    private static function getEmailHtmlBody($code) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
        </head>
        <body>
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                <div style='background: #0171B9; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0;'>
                    <h1>Gujarat memory championship 2026 selection round</h1>
                    <p>Email Verification</p>
                </div>
                <div style='background: #f9f9f9; padding: 30px; border-radius: 0 0 8px 8px;'>
                    <p>Hello,</p>
                    <p>Your email verification code is:</p>
                    <div style='background: white; border: 2px solid #0171B9; padding: 20px; text-align: center; margin: 20px 0; border-radius: 8px;'>
                        <h2 style='color: #0171B9; margin: 0; letter-spacing: 5px; font-size: 32px;'>" . htmlspecialchars($code) . "</h2>
                    </div>
                    <p><strong>This code will expire in 10 minutes.</strong></p>
                    <p>If you didn't request this verification, please ignore this email.</p>
                    <hr style='border: none; border-top: 1px solid #ddd; margin: 20px 0;'>
                    <p style='color: #666; font-size: 12px;'>
                        Thank you,<br>
                        <strong>Team MSFG</strong>
                    </p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    /**
     * Get congratulations email HTML body template
     */
    private static function getCongratulationsHtmlBody($studentName) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
        </head>
        <body>
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                <div style='background: linear-gradient(135deg, #0171B9 0%, #02a0f7 100%); color: white; padding: 40px 20px; text-align: center; border-radius: 8px 8px 0 0;'>
                    <h1 style='margin: 0 0 10px 0; font-size: 36px;'>🎉 Congratulations 🎉</h1>
                    <p style='font-size: 18px; margin: 0;'>You are now selected for Gujarat Memory Championship 2026 Selection Round</p>
                </div>
                <div style='background: #f9f9f9; padding: 30px; border-radius: 0 0 8px 8px;'>
                    <p style='font-size: 16px;'>Dear <strong>" . htmlspecialchars($studentName) . "</strong>,</p>
                    <div style='background: white; border-left: 4px solid #0171B9; padding: 20px; margin: 20px 0; border-radius: 4px;'>
                        <p style='margin: 0; font-size: 16px;'>We are thrilled to inform you that you have <strong style='color: #0171B9;'>successfully passed</strong> the Gujarat Memory Championship " . date('Y') . " selection round</p>
                    </div>
                    <p>Your dedication and effort have paid off. This is a significant achievement, and we are proud of your accomplishment.</p>
                    <div style='background: #fff8e1; border: 1px solid #ffb300; padding: 15px; margin: 20px 0; border-radius: 4px;'>
                        <p style='margin: 0; font-size: 14px;'><strong>📜 Certificate Information:</strong><br>
                        We will contact you soon regarding your certificate of completion.</p>
                    </div>
                    <p>Keep up the excellent work and continue to challenge yourself!</p>
                    <div style='background: #e8f3ff; border: 1px solid #0171B9; padding: 14px; margin: 20px 0; border-radius: 6px;'>
                        <p style='margin: 0; font-size: 14px; color: #044a7a;'>
                            <strong>📞 Need Assistance?</strong><br>
                            For any questions or support, please contact us at: <a href='tel:" . CONTACT_NUMBER . "' style='color: #0171B9; text-decoration: none; font-weight: 600;'>" . CONTACT_NUMBER . "</a>
                        </p>
                    </div>
                    <hr style='border: none; border-top: 1px solid #ddd; margin: 20px 0;'>
                    <p style='color: #666; font-size: 12px;'>
                        Best regards,<br>
                        <strong>Team MSFG</strong>
                    </p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
}
?>
