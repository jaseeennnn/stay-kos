<?php

namespace App\Controllers;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\{Fill, Alignment, Border};
use Dompdf\Dompdf;
use Dompdf\Options;

class Export extends BaseController
{
    public function bookingsExcel()
    {
        $db   = \Config\Database::connect();
        $role = session()->get('role');

        $builder = $db->table('bookings b')
            ->select('b.id, u.username, r.room_name, b.checkin_date, b.duration, b.total_price, b.status, b.created_at')
            ->join('users u', 'u.id = b.user_id')
            ->join('rooms r', 'r.id = b.room_id')
            ->where('b.deleted_at', null)
            ->orderBy('b.created_at', 'desc');

        if ($role !== 'admin') {
            $builder->where('b.user_id', session()->get('userId'));
        }

        $rows        = $builder->get()->getResultArray();
        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Bookings');

        $sheet->mergeCells('A1:H1');
        $sheet->setCellValue('A1', 'Laporan Booking Kamar Kos — ' . date('d F Y'));
        $sheet->getStyle('A1')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1a1a2e']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $headers = ['#', 'Penyewa', 'Kamar', 'Check-in', 'Durasi (bln)', 'Total (Rp)', 'Status', 'Tgl Booking'];
        $col = 'A';
        foreach ($headers as $h) {
            $sheet->setCellValue($col . '2', $h);
            $sheet->getStyle($col . '2')->applyFromArray([
                'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0f3460']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);
            $col++;
        }

        foreach ($rows as $i => $row) {
            $r = $i + 3;
            $sheet->setCellValue('A' . $r, $i + 1);
            $sheet->setCellValue('B' . $r, $row['username']);
            $sheet->setCellValue('C' . $r, $row['room_name']);
            $sheet->setCellValue('D' . $r, date('d/m/Y', strtotime($row['checkin_date'])));
            $sheet->setCellValue('E' . $r, (int)$row['duration']);
            $sheet->setCellValue('F' . $r, (float)$row['total_price']);
            $sheet->setCellValue('G' . $r, strtoupper($row['status']));
            $sheet->setCellValue('H' . $r, date('d/m/Y H:i', strtotime($row['created_at'])));

            $sheet->getStyle('F' . $r)->getNumberFormat()->setFormatCode('#,##0');

            if ($r % 2 === 0) {
                $sheet->getStyle("A{$r}:H{$r}")->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F0F4FF']],
                ]);
            }
        }

        foreach (range('A', 'H') as $c) {
            $sheet->getColumnDimension($c)->setAutoSize(true);
        }

        $filename = 'laporan-booking-' . date('Ymd-His') . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        (new Xlsx($spreadsheet))->save('php://output');
        exit;
    }

    public function roomsExcel()
    {
        $rows        = \Config\Database::connect()->table('rooms')->where('deleted_at', null)->orderBy('room_name')->get()->getResultArray();
        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Daftar Kamar');

        $sheet->mergeCells('A1:E1');
        $sheet->setCellValue('A1', 'Daftar Kamar Kos — ' . date('d F Y'));
        $sheet->getStyle('A1')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 13, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1a1a2e']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $headers = ['#', 'Nama Kamar', 'Harga/Bulan (Rp)', 'Fasilitas', 'Status'];
        $col = 'A';
        foreach ($headers as $h) {
            $sheet->setCellValue($col . '2', $h);
            $sheet->getStyle($col . '2')->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0f3460']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);
            $col++;
        }

        foreach ($rows as $i => $row) {
            $r = $i + 3;
            $sheet->setCellValue('A' . $r, $i + 1);
            $sheet->setCellValue('B' . $r, $row['room_name']);
            $sheet->setCellValue('C' . $r, (float)$row['price']);
            $sheet->setCellValue('D' . $r, $row['facilities']);
            $sheet->setCellValue('E' . $r, strtoupper($row['status']));
            $sheet->getStyle('C' . $r)->getNumberFormat()->setFormatCode('#,##0');
        }

        foreach (range('A', 'E') as $c) {
            $sheet->getColumnDimension($c)->setAutoSize(true);
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="daftar-kamar-' . date('Ymd') . '.xlsx"');
        header('Cache-Control: max-age=0');
        (new Xlsx($spreadsheet))->save('php://output');
        exit;
    }

    public function bookingPdf(int $bookingId)
    {
        $db   = \Config\Database::connect();
        $role = session()->get('role');

        $builder = $db->table('bookings b')
            ->select('b.*, u.username, u.email, r.room_name, r.price, r.facilities')
            ->join('users u', 'u.id = b.user_id')
            ->join('rooms r', 'r.id = b.room_id')
            ->where('b.id', $bookingId)
            ->where('b.deleted_at', null);

        if ($role !== 'admin') {
            $builder->where('b.user_id', session()->get('userId'));
        }

        $booking = $builder->get()->getRowArray();
        if (! $booking) {
            return $this->response->setStatusCode(404)->setBody('Booking tidak ditemukan.');
        }

        $schedule = \App\Models\InstallmentModel::getScheduleForBooking($bookingId);

        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', false);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($this->buildInvoiceHtml($booking, $schedule));
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $dompdf->stream('invoice-booking-' . $bookingId . '-' . date('Ymd') . '.pdf', ['Attachment' => true]);
        exit;
    }

    public function bookingsReportPdf()
    {
        if (session()->get('role') !== 'admin') {
            return $this->response->setStatusCode(403)->setBody('Akses ditolak.');
        }

        $rows = \Config\Database::connect()->table('bookings b')
            ->select('b.id, u.username, r.room_name, b.checkin_date, b.duration, b.total_price, b.status')
            ->join('users u', 'u.id = b.user_id')
            ->join('rooms r', 'r.id = b.room_id')
            ->where('b.deleted_at', null)
            ->orderBy('b.created_at', 'desc')
            ->get()->getResultArray();

        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($this->buildReportHtml($rows));
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        $dompdf->stream('laporan-booking-' . date('Ymd') . '.pdf', ['Attachment' => true]);
        exit;
    }

    public function importRooms()
    {
        if (session()->get('role') !== 'admin') {
            return redirect()->back()->with('error', 'Akses ditolak.');
        }

        $file = $this->request->getFile('import_file');
        if (! $file || ! $file->isValid()) {
            return redirect()->back()->with('error', 'File tidak valid.');
        }

        try {
            $reader      = \PhpOffice\PhpSpreadsheet\IOFactory::createAutoReader($file->getTempName());
            $spreadsheet = $reader->load($file->getTempName());
            $rows        = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);

            $db = \Config\Database::connect();
            $success = 0;
            $errors  = [];

            foreach ($rows as $i => $row) {
                if ($i < 3) continue;

                $roomName   = trim($row['A'] ?? '');
                $price      = (float)str_replace([',', '.'], ['', '.'], $row['B'] ?? 0);
                $facilities = trim($row['C'] ?? '');
                $status     = strtolower(trim($row['D'] ?? 'available'));

                if (empty($roomName) || $price <= 0) {
                    $errors[] = "Baris {$i}: Nama kamar dan harga wajib diisi.";
                    continue;
                }

                if (! in_array($status, ['available', 'booked', 'maintenance'])) {
                    $status = 'available';
                }

                $db->table('rooms')->insert([
                    'room_name'  => $roomName,
                    'price'      => $price,
                    'facilities' => $facilities,
                    'status'     => $status,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
                $success++;
            }

            $msg = "{$success} kamar berhasil diimpor.";
            if (! empty($errors)) {
                $msg .= ' Terdapat ' . count($errors) . ' baris dengan error.';
            }
            return redirect()->to('/admin/rooms')->with('success', $msg);

        } catch (\Exception $e) {
            log_message('error', 'Import error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal memproses file: ' . $e->getMessage());
        }
    }

    public function importTemplate()
    {
        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Template Import Kamar');

        $sheet->mergeCells('A1:D1');
        $sheet->setCellValue('A1', 'Template Import Kamar — Jangan ubah format kolom!');
        $sheet->getStyle('A1')->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'dc3545']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $sheet->mergeCells('A2:D2');
        $sheet->setCellValue('A2', 'Isi data mulai dari baris ke-3. Status: available | booked | maintenance');
        $sheet->getStyle('A2')->getFont()->setItalic(true);

        $headers = ['Nama Kamar*', 'Harga/Bulan (angka)*', 'Fasilitas', 'Status'];
        $col = 'A';
        foreach ($headers as $h) {
            $sheet->setCellValue($col . '3', $h);
            $sheet->getStyle($col . '3')->applyFromArray([
                'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0f3460']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);
            $col++;
        }

        $samples = [
            ['Kamar A1', 1500000, 'AC, WiFi, Kamar Mandi Dalam', 'available'],
            ['Kamar B2', 1200000, 'WiFi, Kipas Angin', 'available'],
        ];
        foreach ($samples as $r => $sample) {
            $row = $r + 4;
            $sheet->setCellValue('A' . $row, $sample[0]);
            $sheet->setCellValue('B' . $row, $sample[1]);
            $sheet->setCellValue('C' . $row, $sample[2]);
            $sheet->setCellValue('D' . $row, $sample[3]);
        }

        foreach (range('A', 'D') as $c) {
            $sheet->getColumnDimension($c)->setAutoSize(true);
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="template-import-kamar.xlsx"');
        header('Cache-Control: max-age=0');
        (new Xlsx($spreadsheet))->save('php://output');
        exit;
    }

    private function buildInvoiceHtml(array $booking, array $schedule): string
    {
        $checkin    = date('d F Y', strtotime($booking['checkin_date']));
        $checkout   = date('d F Y', strtotime($booking['checkin_date'] . ' +' . $booking['duration'] . ' months'));
        $monthly    = number_format($booking['price'], 0, ',', '.');
        $total      = number_format($booking['total_price'], 0, ',', '.');

        $scheduleRows = '';
        foreach ($schedule as $s) {
            $color = $s['status'] === 'paid' ? '#198754' : ($s['status'] === 'overdue' ? '#dc3545' : '#6c757d');
            $scheduleRows .= "<tr>
                <td style='text-align:center;'>Bulan {$s['month_number']}</td>
                <td style='text-align:center;'>" . date('d/m/Y', strtotime($s['due_date'])) . "</td>
                <td style='text-align:right;'>Rp " . number_format($s['amount'], 0, ',', '.') . "</td>
                <td style='text-align:center; color:{$color}; font-weight:bold;'>" . strtoupper($s['status']) . "</td>
            </tr>";
        }

        return "<!DOCTYPE html><html><head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: 'DejaVu Sans', sans-serif; font-size: 12px; color: #333; padding: 20px; }
            .header { background: #1a1a2e; color: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
            .header h1 { margin: 0; font-size: 22px; }
            .box { background: #f8f9fa; border-radius: 6px; padding: 14px; border-left: 4px solid #0f3460; margin-bottom: 16px; }
            table { width: 100%; border-collapse: collapse; margin-top: 8px; }
            th { background: #0f3460; color: white; padding: 8px 10px; font-size: 11px; }
            td { padding: 7px 10px; border-bottom: 1px solid #eee; }
            .footer { margin-top: 30px; text-align: center; color: #aaa; font-size: 10px; border-top: 1px solid #eee; padding-top: 10px; }
        </style></head><body>
        <div class='header'><h1>🏠 StayKos — INVOICE #{$booking['id']}</h1></div>
        <div class='box'>
            <strong>{$booking['username']}</strong> | {$booking['email']}<br>
            Kamar: <strong>{$booking['room_name']}</strong> @ Rp {$monthly}/bln<br>
            Check-in: {$checkin} | Checkout: {$checkout} | Durasi: {$booking['duration']} bulan<br>
            <strong style='color:#0f3460; font-size:14px;'>TOTAL: Rp {$total}</strong>
        </div>
        " . (! empty($schedule) ? "
        <h4>Jadwal Cicilan Bulanan</h4>
        <table>
            <thead><tr><th>Bulan</th><th>Jatuh Tempo</th><th>Nominal</th><th>Status</th></tr></thead>
            <tbody>{$scheduleRows}</tbody>
        </table>" : '') . "
        <div class='footer'>Dicetak " . date('d F Y H:i') . " • StayKos System</div>
        </body></html>";
    }

    private function buildReportHtml(array $rows): string
    {
        $tableRows = '';
        foreach ($rows as $i => $row) {
            $color = match (strtolower($row['status'])) {
                'approved' => '#198754', 'rejected' => '#dc3545', default => '#856404'
            };
            $tableRows .= "<tr>
                <td>" . ($i + 1) . "</td>
                <td>{$row['username']}</td>
                <td>{$row['room_name']}</td>
                <td>" . date('d/m/Y', strtotime($row['checkin_date'])) . "</td>
                <td style='text-align:center;'>{$row['duration']} bln</td>
                <td style='text-align:right;'>Rp " . number_format($row['total_price'], 0, ',', '.') . "</td>
                <td style='text-align:center; color:{$color}; font-weight:bold;'>" . strtoupper($row['status']) . "</td>
            </tr>";
        }

        return "<!DOCTYPE html><html><head><meta charset='UTF-8'>
        <style>
            body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; }
            h2 { color: #1a1a2e; }
            table { width:100%; border-collapse:collapse; }
            th { background:#1a1a2e; color:#fff; padding:7px 8px; }
            td { padding:6px 8px; border-bottom:1px solid #ddd; }
            tr:nth-child(even) td { background:#f5f5f5; }
        </style></head><body>
        <h2>Laporan Booking Kamar Kos</h2>
        <p style='color:#666;'>Dicetak: " . date('d F Y H:i') . " — Total: " . count($rows) . " data</p>
        <table>
            <thead><tr><th>#</th><th>Penyewa</th><th>Kamar</th><th>Check-in</th>
            <th>Durasi</th><th>Total</th><th>Status</th></tr></thead>
            <tbody>{$tableRows}</tbody>
        </table>
        </body></html>";
    }
}