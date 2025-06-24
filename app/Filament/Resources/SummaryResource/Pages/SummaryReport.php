<?php

namespace App\Filament\Resources\SummaryResource\Pages;

use Illuminate\Support\Carbon;
use App\Filament\Resources\SummaryResource;
use App\Models\Queue;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Livewire\WithPagination;

class SummaryReport extends Page
{
    protected static string $resource = SummaryResource::class;
    protected static string $view = 'filament.resources.summary-resource.pages.summary-report';
    use WithPagination;

    public $data = [];
    public $from;
    public $until;

    public function mount(): void
    {
        $this->from = now()->startOfMonth()->toDateString();
        $this->until = now()->endOfMonth()->toDateString();

        $this->data = $this->getSummaryData();
    }

    public function getSummaryData()
    {
        $from = Carbon::parse($this->from)->startOfDay();
        $until = Carbon::parse($this->until)->startOfDay()->addDay();

        $dates = [];
        $period = new \DatePeriod($from, new \DateInterval('P1D'), $until);
        foreach ($period as $date) {
            $dates[] = $date->format('Y-m-d');
        }

        $query = Queue::with(['user', 'produk']);

        // Filter berdasarkan user login jika bukan super admin
        if (!Auth::user()->hasRole('super_admin')) {
            $query->where('user_id', Auth::id());
        }

        $queues = $query->whereBetween('booking_date', [$from->format('Y-m-d'), $until->format('Y-m-d')])->get();

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

        return [
            'dates' => $dates,
            'summary' => $summary,
        ];
    }

    public function updatedFrom()
    {
        $this->data = $this->getSummaryData();
    }

    public function updatedUntil()
    {
        $this->data = $this->getSummaryData();
    }
}
