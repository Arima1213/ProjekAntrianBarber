<?php

namespace App\Http\Controllers;

use App\Models\Queue;
use App\Models\Tenant;
use Barryvdh\DomPDF\Facade\Pdf;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Http\Request;

class nomorAntrian extends Controller
{
    public function index($id)
    {
        $cabang = Tenant::with('lokasi')->findOrFail($id);
        return view('nomorAntrian', compact('cabang'));
    }

    // public function jsonToday($id)
    // {
    //     // Ambil antrian sesuai tanggal, tenant, dan status
    //     $queues = Queue::with(['customer', 'produk'])
    //         ->whereDate('booking_date', today())
    //         ->where('tenant_id', $id)
    //         ->where('status', 'menunggu')
    //         ->orderBy('created_at', 'desc')
    //         ->get();

    //     // Filter produk berdasarkan tenant
    //     if ($id == 1) {
    //         // Cabang Utama: hanya tampilkan potong 20k
    //         $queues = $queues->filter(function ($queue) {
    //             return str_contains($queue->produk->judul, 'Potong 20k');
    //         });
    //     } elseif ($id == 4) {
    //         // Cabang 002: hanya tampilkan potong 20k
    //         $queues = $queues->filter(function ($queue) {
    //             return $queue->produk->judul == 'Potong 20k';
    //         });
    //     }

    //     // Hitung jumlah antrian dengan status 'menunggu'
    //     $menungguCount = $queues->count();


    //     return response()->json([
    //         'queues' => $queues,
    //         'menungguCount' => $menungguCount,
    //     ]);
    // }
    public function jsonToday($id)
    {
        // Ambil antrian sesuai tanggal, tenant, dan status
        $queues = Queue::with(['customer', 'produk'])
            ->whereDate('booking_date', today())
            ->where('tenant_id', $id)
            ->where('status', 'menunggu')
            ->orderBy('created_at', 'desc');

        if ($id == 1) {
            $queues = $queues->whereHas('produk', function ($query) {
                $query->where('judul', 'like', '%Potong 20k%');
            });
        } elseif ($id == 4) {
            $queues = $queues->whereHas('produk', function ($query) {
                $query->where('judul', 'like', '%Potong 20k%');
            });
        }

        $queues = $queues->get();

        $menungguCount = $queues->count();

        return response()->json([
            'queues' => $queues,
            'menungguCount' => $menungguCount,
        ]);
    }



    public function print($id)
    {
        $queue = Queue::with(['produk', 'customer', 'tenant.lokasi'])->findOrFail($id);

        // Enkripsi ID
        $encrypted = Crypt::encryptString($queue->id);

        // Buat URL terenkripsi
        $qrUrl = route('antrian.qr.decrypt', ['encrypted' => $encrypted]);

        // Gunakan API eksternal (misalnya QRServer)
        $qrCode = "https://api.qrserver.com/v1/create-qr-code/?data=" . urlencode($qrUrl) . "&size=150x150";

        // Load view PDF
        $pdf = Pdf::loadView('printAntrian', [
            'queue' => $queue,
            'produk' => $queue->produk,
            'cabang' => $queue->tenant,
            'qrCode' => $qrCode,
        ])->setOptions([
            'chroot' => public_path(), // tetap boleh
            'isRemoteEnabled' => true // <- INI PENTING
        ]);

        $pdf->setPaper([0, 0, 78, 88]);

        return $pdf->stream('antrian-' . $queue->nomor_antrian . '.pdf');
    }
}
