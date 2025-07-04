<?php

namespace App\Filament\Resources\TransaksiResource\Pages;

use App\Models\Queue;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Carbon;
use Livewire\WithPagination;
use App\Models\Tenant; // Menambahkan model Tenant

class TransaksiReport extends Page
{
    use WithPagination;

    protected static string $resource = \App\Filament\Resources\TransaksiResource::class;
    protected static string $view = 'filament.resources.transaksi-resource.pages.transaksi-report';

    public $from;
    public $until;
    public $tenant_id;  // Tambahkan properti tenant_id
    public $data = [];
    public $tenants;  // Menambahkan properti tenants

    public function mount(): void
    {
        $this->from = now()->startOfMonth()->toDateString();
        $this->until = now()->endOfMonth()->toDateString();
        $this->tenants = Tenant::all();  // Ambil semua tenant untuk dipilih
        $this->data = $this->getData();
    }

    public function getData()
    {
        $from = Carbon::parse($this->from)->startOfDay();
        $until = Carbon::parse($this->until)->startOfDay()->addDay(); // agar inklusif

        $query = Queue::with(['user', 'produk', 'customer']);

        // Memfilter berdasarkan tenant jika ada tenant_id
        if ($this->tenant_id) {
            $query->where('tenant_id', $this->tenant_id);
        }

        return $query
            ->whereBetween('booking_date', [$from->toDateString(), $until->subDay()->toDateString()])
            ->orderBy('booking_date', 'asc')
            ->get();
    }

    public function updatedFrom()
    {
        $this->data = $this->getData();
    }

    public function updatedUntil()
    {
        $this->data = $this->getData();
    }

    public function updatedTenantId()
    {
        // Perbarui data ketika tenant_id diperbarui
        $this->data = $this->getData();
    }
}
