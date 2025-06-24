<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Login Notification</title>
</head>
<body>
    <h2>Hello,</h2>
    <p>A login to your Tutors account was detected:</p>

    <ul>
        <li><strong>Email:</strong> {{ $email }}</li>
        <li><strong>Device:</strong> {{ ucfirst($deviceType) }}</li>
        <li><strong>Login Time:</strong> {{ $loginTime }} (UTC)</li>
        <li><strong>IP Address:</strong> {{ $ip }}</li>
        <li><strong>IP Address:</strong> {{ $location }}</li>
    </ul>

    <p>If this wasn't you, please reset your password immediately.</p>
    <p>Thank you,<br>Tutors Team</p>
</body>
</html>
