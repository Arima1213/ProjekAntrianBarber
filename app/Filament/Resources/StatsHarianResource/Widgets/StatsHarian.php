<?php

namespace App\Filament\Resources\StatsHarianResource\Widgets;

use App\Models\Queue;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class StatsHarian extends BaseWidget
{
    use InteractsWithPageFilters;

    protected function getStats(): array
    {
        $filters = $this->filters ?? [];

        $query = Queue::query();

        if (!empty($filters['tenant_id'])) {
            $query->where('queues.tenant_id', $filters['tenant_id']);
        }

        if (!empty($filters['startDate']) && !empty($filters['endDate'])) {
            $query->whereBetween('queues.booking_date', [$filters['startDate'], $filters['endDate']]);
        } elseif (!empty($filters['startDate'])) {
            $query->whereDate('queues.booking_date', '>=', $filters['startDate']);
        } elseif (!empty($filters['endDate'])) {
            $query->whereDate('queues.booking_date', '<=', $filters['endDate']);
        }

        $jumlahAntrian = (clone $query)->count();
        $jumlahSelesai = (clone $query)->where('status', 'selesai')->count();

        $pendapatan = (clone $query)
            ->join('produks', 'queues.produk_id', '=', 'produks.id')
            ->sum('produks.harga');

        // dd($jumlahAntrian);

        return [
            Stat::make('Total Antrian', $jumlahAntrian)
                ->description('Seluruh antrian'),
            Stat::make('Antrian Selesai', $jumlahSelesai)
                ->description('Status selesai'),
            Stat::make('Pendapatan', 'Rp ' . number_format($pendapatan, 0, ',', '.'))
                ->description('Dari antrian selesai/filter'),
        ];
    }
}
