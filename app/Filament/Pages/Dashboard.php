<?php

namespace App\Filament\Pages;

use App\Filament\Resources\StatsHarianResource\Widgets\StatsHarian;
use Filament\Pages\Dashboard as PagesDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Get;
use Filament\Forms\Form;
use App\Models\Tenant;
use Illuminate\Support\Facades\Auth;

class Dashboard extends PagesDashboard
{
    use HasFiltersForm;


    public function filtersForm(Form $form): Form
    {
        // ambil url dan ambil value setelah base/admin/
        $url = request()->url();
        // kemudian ambil value setelah base/admin/
        $url = str_replace(url('admin/'), '', $url);
        // hapus garis miring (/) di depan jika ada
        $url = ltrim($url, '/');
        // kemudian cari di table teams apakah ada yang sesuai dengan value tersebut
        $tenant = Tenant::where('slug', $url)->first();
        // jika ada, set default tenant_id ke id tenant tersebut
        if ($tenant) {
            $defaultTenantId = $tenant->id;
        } else {
            $defaultTenantId = Auth::user()?->teams->first()?->id ?? 1;
        }

        return $form
            ->schema([
                Section::make()
                    ->schema([
                        Select::make('tenant_id')
                            ->label('Tenant')
                            ->options(Tenant::all()->pluck('name', 'id'))
                            ->searchable()
                            ->placeholder('Pilih Tenant')
                            ->hint('Pilih tenant untuk menampilkan data sesuai cabang yang diinginkan.')
                            ->afterStateHydrated(function (callable $set, $state) {
                                $url = request()->url();
                                $slug = str_replace(url('admin/'), '', $url);
                                $slug = ltrim($slug, '/');
                                $tenant = \App\Models\Tenant::where('slug', $slug)->first();
                                $set('tenant_id', $tenant?->id ?? Auth::user()?->teams->first()?->id ?? 1);
                            }),

                        DatePicker::make('startDate')
                            ->label('Tanggal Awal')
                            ->default(now())
                            ->hint('Pilih tanggal mulai periode data yang ingin ditampilkan.'),

                        DatePicker::make('endDate')
                            ->label('Tanggal Akhir')
                            ->default(now())
                            ->hint('Pilih tanggal akhir periode data yang ingin ditampilkan.'),
                    ])
                    ->columns(3),
            ]);
    }
}
