<!DOCTYPE html>
<html>
<head>
    <title>Flight Booking</title>
</head>
<body>
    <h1>Hello {{ $bookingDetails['name'] }}</h1>
    <p>Your flight booking has been confirmed.</p>
    <p><strong>PNR:</strong> {{ $bookingDetails['pnr'] }}</p>
    <p><strong>Flight Number:</strong> {{ $bookingDetails['flight_number'] }}</p>
    <p>Thank you for booking with us!</p>
</body>
</html>