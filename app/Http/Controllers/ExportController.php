<?php

namespace App\Http\Controllers;

use App\Models\Queue;

class ExportController extends Controller
{
    public function export()
    {
        $fileName = 'transaksi.xlsx';

        $query = Queue::query()->with(['customer', 'produk', 'user']);

        // FILTER: berdasarkan user_id (Chapster)
        if (request()->has('user_id')) {
            $query->where('user_id', request('user_id'));
        }

        // FILTER: berdasarkan produk_id
        if (request()->has('produk_id')) {
            $query->where('produk_id', request('produk_id'));
        }

        // FILTER: berdasarkan booking_date
        if (request()->has('booking_date')) {
            $query->whereDate('booking_date', request('booking_date'));
        }

        // SORTING
        if (request()->has('sortColumn') && request()->has('sortDirection')) {
            $query->orderBy(request('sortColumn'), request('sortDirection'));
        } else {
            $query->latest();
        }

        $queues = $query->get();

        // Gunakan PhpSpreadsheet untuk membuat file Excel
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Header Excel
        $sheet->fromArray([
            ['ID', 'Nama Pelanggan', 'Produk', 'Chapster', 'Harga Produk', 'Tanggal Booking']
        ], null, 'A1');

        // Isi Data
        $row = 2;
        foreach ($queues as $item) {
            $sheet->fromArray([
                $item->id,
                $item->customer->nama ?? '-',
                $item->produk->judul ?? '-',
                $item->user->name ?? '-',
                'Rp ' . number_format($item->produk->harga ?? 0, 0, ',', '.'),
                \Carbon\Carbon::parse($item->booking_date)->format('d-m-Y'),
            ], null, 'A' . $row);
            $row++;
        }

        // Output Excel ke response
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
