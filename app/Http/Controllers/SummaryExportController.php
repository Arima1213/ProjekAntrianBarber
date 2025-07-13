<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use App\Models\Queue;
use Symfony\Component\HttpFoundation\StreamedResponse;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class SummaryExportController extends Controller
{
    public function export(Request $request)
    {
        $from = Carbon::parse($request->input('from'))->startOfDay();
        $until = Carbon::parse($request->input('until'))->endOfDay();

        // Daftar tanggal
        $dates = [];
        $period = new \DatePeriod($from, new \DateInterval('P1D'), $until->copy()->addDay());
        foreach ($period as $date) {
            $dates[] = $date->format('Y-m-d');
        }

        // Query antrian
        $query = Queue::with(['user', 'produk'])
            ->whereBetween('booking_date', [$from->format('Y-m-d'), $until->format('Y-m-d')]);

        if (!Auth::user()->hasRole('super_admin')) {
            $query->where('user_id', Auth::id());
        }

        $query->where('is_validated', true);

        $queues = $query->get();

        // Ringkasan
        $summary = [];
        foreach ($queues as $queue) {
            $name = $queue->user->name ?? '-';
            $layanan = $queue->produk->judul ?? '-';
            $date = $queue->booking_date;

            if (!isset($summary[$name])) {
                $summary[$name] = [];
            }

            if (!isset($summary[$name][$layanan])) {
                $summary[$name][$layanan] = array_fill_keys($dates, 0);
            }

            $summary[$name][$layanan][$date]++;
        }

        foreach ($summary as $name => $layanans) {
            foreach ($layanans as $layanan => $tanggal) {
                $summary[$name][$layanan]['total'] = array_sum($tanggal);
            }
        }

        // Generate Excel
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Header
        $header = ['Nama Karyawan', 'Jenis Layanan'];
        $header = array_merge($header, $dates);
        $header[] = 'Total';
        $sheet->fromArray($header, null, 'A1');

        // Data
        $row = 2;
        foreach ($summary as $karyawan => $layanans) {
            foreach ($layanans as $layanan => $items) {
                $dataRow = [$karyawan, $layanan];
                foreach ($dates as $date) {
                    $dataRow[] = $items[$date] ?? 0;
                }
                $dataRow[] = $items['total'] ?? 0;
                $sheet->fromArray($dataRow, null, 'A' . $row);
                $row++;
            }
        }

        // Simpan dan unduh
        $filename = 'Laporan_Ringkasan_' . $from->format('Ymd') . '_' . $until->format('Ymd') . '.xlsx';

        return new StreamedResponse(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment;filename=\"{$filename}\"",
            'Cache-Control' => 'max-age=0',
        ]);
    }
}
