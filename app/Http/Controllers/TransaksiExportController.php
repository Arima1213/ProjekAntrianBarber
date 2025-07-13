<?php

namespace App\Http\Controllers;

use App\Models\Queue;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Carbon\Carbon;

class TransaksiExportController extends Controller
{
    public function export(Request $request)
    {
        $from = Carbon::parse($request->input('from'))->startOfDay();
        $until = Carbon::parse($request->input('until'))->startOfDay();
        $data = Queue::with(['user', 'produk', 'customer'])
            ->whereBetween('booking_date', [$from->toDateString(), $until->subDay()->toDateString()])
            ->where('is_validated', true)
            ->orderBy('booking_date', 'asc')
            ->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Header
        $headers = ['Tanggal', 'Pelanggan', 'Produk', 'Harga', 'Chapster', 'Status'];
        $sheet->fromArray($headers, null, 'A1');

        // Data Rows
        $row = 2;
        foreach ($data as $item) {
            $sheet->setCellValue("A{$row}", $item->booking_date);
            $sheet->setCellValue("B{$row}", $item->customer->nama ?? '-');
            $sheet->setCellValue("C{$row}", $item->produk->judul ?? '-');
            $sheet->setCellValue("D{$row}", $item->produk->harga ?? 0);
            $sheet->setCellValue("E{$row}", $item->user->name ?? '-');
            $sheet->setCellValue("F{$row}", $item->status);
            $row++;
        }

        // Download response
        $filename = "Laporan_Transaksi_{$from->format('Ymd')}_{$until->subDay()->format('Ymd')}.xlsx";
        $writer = new Xlsx($spreadsheet);

        // Buat response streaming
        $tempFile = tempnam(sys_get_temp_dir(), $filename);
        $writer->save($tempFile);

        return response()->download($tempFile, $filename)->deleteFileAfterSend(true);
    }
}
