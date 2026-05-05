<?php
$checkin  = date('d F Y', strtotime($booking['checkin_date']));
$checkout = date('d F Y', strtotime($booking['checkin_date'] . ' +' . $booking['duration'] . ' months'));
$monthly  = number_format($booking['price'], 0, ',', '.');
$total    = number_format($booking['total_price'], 0, ',', '.');
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
body { font-family: DejaVu Sans; font-size: 12px; color: #333; }
.header { background: #1a1a2e; color: white; padding: 20px; }
.box { background: #f8f9fa; padding: 14px; margin-top: 10px; }
table { width: 100%; border-collapse: collapse; margin-top: 10px; }
th { background: #0f3460; color: white; padding: 8px; }
td { padding: 7px; border-bottom: 1px solid #eee; }
</style>
</head>
<body>

<div class="header">
    <h2>INVOICE #<?= $booking['id'] ?></h2>
</div>

<div class="box">
    <b><?= $booking['username'] ?></b><br>
    <?= $booking['email'] ?><br><br>

    Kamar: <b><?= $booking['room_name'] ?></b><br>
    Check-in: <?= $checkin ?><br>
    Checkout: <?= $checkout ?><br>
    Durasi: <?= $booking['duration'] ?> bulan<br><br>

    <b>Total: Rp <?= $total ?></b>
</div>

</body>
</html>