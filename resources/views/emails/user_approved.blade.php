<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Account Has Been Approved</title>
</head>
<body>
    <h1>Hello {{ $user->name }},</h1>
    <p>We are pleased to inform you that your account has been approved. You can now log in and start using the platform.</p>
    
    <p><a href="{{ url('/login') }}">Click here to log in</a></p>

    <p>Thank you for being a part of our community!</p>

    <p>Best regards,</p>
    <p>The Team</p>
</body>
</html>
