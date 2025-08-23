{{-- resources/views/emails/2fa_verification.blade.php --}}
<!DOCTYPE html>
<html>
<head>
    <title>Login Verification</title>
</head>
<body>
    <h1>Login Verification Required</h1>
    <p>Please click the link below to complete your login. This link will expire in 15 minutes.</p>
    
    {{-- IMPORTANT: This URL should point to your FRONTEND application --}}
    <a href="{{ env('FRONTEND_URL') }}/verify-login?token={{ $token }}">Verify Login</a>
    
    <p>If you did not attempt to log in, please secure your account immediately.</p>
</body>
</html>