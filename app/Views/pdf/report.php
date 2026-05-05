<?php
$totalData = count($rows);
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
    body {
        font-family: DejaVu Sans, sans-serif;
        font-size: 11px;
        color: #333;
        padding: 20px;
    }

    .header {
        background: #1a1a2e;
        color: #fff;
        padding: 16px 20px;
        border-radius: 8px;
        margin-bottom: 20px;
    }

    .header h2 {
        margin: 0;
        font-size: 18px;
    }

    .meta {
        margin-top: 5px;
        font-size: 10px;
        color: #ccc;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 10px;
    }

    th {
        background: #0f3460;
        color: #fff;
        padding: 8px;
        font-size: 10px;
        text-align: center;
    }

    td {
        padding: 7px;
        border-bottom: 1px solid #eaeaea;
    }

    tr:nth-child(even) td {
        background: #f7f9fc;
    }

    .right { text-align: right; }
    .center { text-align: center; }

    .status-pending { color: #856404; font-weight: bold; }
    .status-approved { color: #198754; font-weight: bold; }
    .status-rejected { color: #dc3545; font-weight: bold; }

    .footer {
        margin-top: 20px;
        font-size: 10px;
        text-align: center;
        color: #999;
        border-top: 1px solid #eee;
        padding-top: 10px;
    }
</style>
</head>

<body>

<div class="header">
    <h2>📊 Laporan Booking Kamar Kos</h2>
    <div class="meta">
        Dicetak: <?= date('d F Y H:i') ?> | Total Data: <?= $totalData ?>
    </div>
</div>

<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Penyewa</th>
            <th>Kamar</th>
            <th>Check-in</th>
            <th>Durasi</th>
            <th>Total</th>
            <th>Status</th>
        </tr>
    </thead>

    <tbody>
        <?php foreach ($rows as $i => $row): 
            $statusClass = match (strtolower($row['status'])) {
                'approved' => 'status-approved',
                'rejected' => 'status-rejected',
                default    => 'status-pending',
            };
        ?>
        <tr>
            <td class="center"><?= $i + 1 ?></td>
            <td><?= esc($row['username']) ?></td>
            <td><?= esc($row['room_name']) ?></td>
            <td class="center"><?= date('d/m/Y', strtotime($row['checkin_date'])) ?></td>
            <td class="center"><?= $row['duration'] ?> bln</td>
            <td class="right">Rp <?= number_format($row['total_price'], 0, ',', '.') ?></td>
            <td class="center <?= $statusClass ?>">
                <?= strtoupper($row['status']) ?>
            </td>
        </tr>
        <?php endforeach ?>
    </tbody>
</table>

<div class="footer">
    Sistem Manajemen Kos • Generated otomatis oleh sistem
</div>

</body>
</html>