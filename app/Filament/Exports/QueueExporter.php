<?php

namespace App\Filament\Exports;

use App\Models\Queue;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class QueueExporter extends Exporter
{
    protected static ?string $model = Queue::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')->label('ID'),
            ExportColumn::make('customer.nama')->label('Nama Customer'),
            ExportColumn::make('produk.judul')->label('Produk'),
            ExportColumn::make('nomor_antrian')->label('Nomor Antrian'),
            ExportColumn::make('status')->label('Status'),
            ExportColumn::make('is_validated')->label('Tervalidasi'),
            ExportColumn::make('user.name')->label('Chapster'),
            ExportColumn::make('booking_date')->label('Tanggal Booking'),
            ExportColumn::make('created_at')->label('Dibuat Pada'),
            ExportColumn::make('updated_at')->label('Diubah Pada'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Export data antrian selesai. '
            . number_format($export->successful_rows) . ' '
            . str('baris')->plural($export->successful_rows) . ' berhasil diekspor.';

        if ($failed = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failed) . ' gagal.';
        }

        return $body;
    }
}
