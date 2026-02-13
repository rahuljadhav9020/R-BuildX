<?php
function sendOrderConfirmation($orderDetails) {
    try {
        $to = $orderDetails['email'];
        $subject = 'Order Confirmation - R-BuildX #' . $orderDetails['order_id'];
        
        // Email body in HTML
        $message = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; }
                .container { max-width: 600px; margin: 0 auto; }
                .header { color: #512da8; }
                .details { background: #f5f5f5; padding: 15px; margin: 20px 0; }
                .footer { font-size: 12px; color: #666; margin-top: 20px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <h2 class='header'>Thank you for your order!</h2>
                <p>Dear {$orderDetails['customer_name']},</p>
                <p>Your order has been received and is being reviewed. Here are your order details:</p>
                
                <div class='details'>
                    <p><strong>Order ID:</strong> #{$orderDetails['order_id']}</p>
                    <p><strong>Vehicle:</strong> {$orderDetails['vehicle_type']}</p>
                    <p><strong>Location:</strong> {$orderDetails['location']}</p>
                    <p><strong>Purpose:</strong> {$orderDetails['purpose']}</p>
                    <p><strong>Amount:</strong> ₹{$orderDetails['rate']}/-</p>
                </div>

                <p>We will review your order and notify you once it's approved.</p>
                <p>For any queries, please contact us at support@rbuildx.com</p>
                
                <div class='footer'>
                    <p>This is an automated email, please do not reply.</p>
                </div>
            </div>
        </body>
        </html>
        ";

        // Headers for HTML email
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: R-BuildX <noreply@rbuildx.com>" . "\r\n";

        // Send email
        mail($to, $subject, $message, $headers);
        return true;
    } catch (Exception $e) {
        error_log("Mail Error: " . $e->getMessage());
        return false;
    }
} 