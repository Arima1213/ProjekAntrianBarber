<?php

namespace App\Filament\Customer\Resources\UserQueueResource\Pages;

use App\Filament\Customer\Resources\UserQueueResource;
use App\Models\Customer;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Illuminate\Support\Facades\Auth;
use App\Models\Queue;
use App\Models\Tenant;
use App\Models\Produk;
use Filament\Notifications\Notification;

class ListUserQueues extends ListRecords
{
    protected static string $resource = UserQueueResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('Daftar Antrian')
                ->label('Daftar Antrian')
                ->form([
                    Select::make('tenant_id')
                        ->label('Pilih Tenant')
                        ->options(fn() => Tenant::pluck('name', 'id'))
                        ->reactive()
                        ->required(),

                    Select::make('produk_id')
                        ->label('Pilih Produk')
                        ->options(function (callable $get) {
                            $tenantId = $get('tenant_id');
                            return Produk::where('tenant_id', $tenantId)->pluck('judul', 'id');
                        })
                        ->required()
                        ->reactive(),

                    DatePicker::make('booking_date')
                        ->label('Tanggal Booking')
                        ->required()
                        ->minDate(now()->startOfDay())
                        ->default(now()->startOfDay()),
                ])
                ->action(function (array $data) {
                    $user = Auth::user();

                    $customer = Customer::where('user_id', $user->id)->first();

                    if (!$customer) {
                        $customer = Customer::create([
                            'user_id' => $user->id,
                            'nama' => $user->name,
                        ]);
                    }

                    $tenantId = $data['tenant_id'];
                    $produkId = $data['produk_id'];
                    $bookingDate = $data['booking_date'];
                    // // Nomor antrian dihitung per tenant (cabang) dan tanggal booking
                    // $lastQueue = Queue::where('tenant_id', $tenantId)
                    //     ->whereDate('booking_date', $bookingDate)
                    //     ->orderByDesc('nomor_antrian')
                    //     ->first();
                    // $nextNomorAntrian = $lastQueue ? $lastQueue->nomor_antrian + 1 : 1;

                    if ($tenantId == 4) {

                        $lastQueue = Queue::where('tenant_id', $tenantId)
                            ->where('produk_id', $produkId)
                            ->whereDate('booking_date', $bookingDate)
                            ->orderByDesc('nomor_antrian')
                            ->first();

                        $nextNomorAntrian = $lastQueue ? $lastQueue->nomor_antrian + 1 : 1;
                    } else {

                        $lastQueue = Queue::where('tenant_id', $tenantId)
                            ->where('produk_id', $produkId)
                            ->whereDate('booking_date', $bookingDate)
                            ->orderByDesc('nomor_antrian')
                            ->first();

                        $nextNomorAntrian = $lastQueue ? $lastQueue->nomor_antrian + 1 : 1;
                    }

                    Queue::create([
                        'customer_id' => $customer->id,
                        'tenant_id' => $tenantId,
                        'produk_id' => $data['produk_id'],
                        'booking_date' => $bookingDate,
                        'nomor_antrian' => $nextNomorAntrian,
                        'status' => 'menunggu',
                        'is_validated' => false,
                    ]);

                    Notification::make()
                        ->title('Berhasil Mendaftar')
                        ->body('Berhasil mendaftar dengan nomor antrian: ' . $nextNomorAntrian)
                        ->success()
                        ->send();
                })
        ];
    }
}
