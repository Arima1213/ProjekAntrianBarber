<?php

namespace App\Filament\Resources\GrafikPemasukanPerbulanResource\Widgets;

use App\Models\Queue;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class GrafikPemasukanPerbulan extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?string $heading = 'Grafik Pemasukan per Bulan';

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $filters = $this->filters ?? [];
        $query = Queue::query()
            ->join('produks', 'queues.produk_id', '=', 'produks.id')
            ->where('queues.status', 'selesai')
            ->where('queues.is_validated', true);

        // Filter tenant jika ada
        if (!empty($filters['tenant_id'])) {
            $query->where('queues.tenant_id', $filters['tenant_id']);
        }

        // Jika ada filter tanggal
        if (!empty($filters['startDate']) && !empty($filters['endDate'])) {
            $startDate = Carbon::parse($filters['startDate']);
            $endDate = Carbon::parse($filters['endDate']);
            $query->whereBetween('queues.booking_date', [$startDate->toDateString(), $endDate->toDateString()]);
        } else {
            // Default: bulan ini, per hari
            $startDate = now()->startOfMonth();
            $endDate = now()->endOfMonth();
            $query->whereBetween('queues.booking_date', [$startDate->toDateString(), $endDate->toDateString()]);
        }

        // Ambil pemasukan per hari
        $pemasukanPerHari = $query
            ->selectRaw('DAY(queues.booking_date) as hari, SUM(produks.harga) as total')
            ->groupBy(DB::raw('DAY(queues.booking_date)'))
            ->orderBy(DB::raw('DAY(queues.booking_date)'))
            ->pluck('total', 'hari')
            ->toArray();

        // Buat data chart per hari
        $days = $startDate->diffInDays($endDate) + 1;
        $dataChart = [];
        $labels = [];
        for ($i = 1; $i <= $days; $i++) {
            $tanggal = $startDate->copy()->addDays($i - 1);
            $hari = (int)$tanggal->format('j');
            $dataChart[] = $pemasukanPerHari[$hari] ?? 0;
            $labels[] = $tanggal->format('d M');
        }

        return [
            'datasets' => [
                [
                    'label' => 'Total Pemasukan (Rp)',
                    'data' => $dataChart,
                    'borderColor' => '#16a34a',
                    'backgroundColor' => 'rgba(22,163,74,0.4)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
            ],
            'labels' => $labels,
        ];
    }
}
