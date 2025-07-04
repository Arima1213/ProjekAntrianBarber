<?php

namespace App\Filament\Resources\QueueResource\Pages;

use App\Filament\Resources\QueueResource;
use App\Models\Customer;
use App\Models\Produk;
use App\Models\Queue;
use Filament\Actions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Get;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

class ListQueues extends ListRecords
{
    protected static string $resource = QueueResource::class;

    // protected function getHeaderActions(): array
    // {
    //     return [
    //         Actions\Action::make('Daftar Antrian')
    //             ->label('Daftar Antrian')
    //             ->form([
    //                 // Toggle::make('is_new_customer')
    //                 //     ->label('Pelanggan Baru?')
    //                 //     ->default(false)
    //                 //     ->reactive(),

    //                 // Select::make('customer_id')
    //                 //     ->label('Pilih Pelanggan')
    //                 //     ->searchable()
    //                 //     ->options(fn() => Customer::pluck('nama', 'id'))
    //                 //     ->visible(fn(Get $get) => !$get('is_new_customer')),

    //                 // TextInput::make('nama')
    //                 //     ->label('Nama Pelanggan')
    //                 //     ->required(fn(Get $get) => $get('is_new_customer'))
    //                 //     ->visible(fn(Get $get) => $get('is_new_customer')),

    //                 // TextInput::make('nomor')
    //                 //     ->label('Nomor Pelanggan')
    //                 //     ->numeric()
    //                 //     ->visible(fn(Get $get) => $get('is_new_customer')),

    //                 // TextInput::make('user_email')
    //                 //     ->label('Email User')
    //                 //     ->email()
    //                 //     ->required(fn(Get $get) => $get('is_new_customer'))
    //                 //     ->visible(fn(Get $get) => $get('is_new_customer')),

    //                 // TextInput::make('user_password')
    //                 //     ->label('Password User')
    //                 //     ->password()
    //                 //     ->required(fn(Get $get) => $get('is_new_customer'))
    //                 //     ->visible(fn(Get $get) => $get('is_new_customer')),

    //                 Select::make('produk_id')
    //                     ->label('Pilih Produk')
    //                     ->options(function (callable $get) {
    //                         // Ambil tenant_id dari user yang sedang login
    //                         $tenantId = Auth::user()?->teams->first()?->id;
    //                         return Produk::where('tenant_id', $tenantId)->pluck('judul', 'id');
    //                     })
    //                     ->required()
    //                     ->reactive(),

    //                 DatePicker::make('booking_date')
    //                     ->label('Tanggal Booking')
    //                     ->required()
    //                     ->minDate(now()->startOfDay())
    //                     ->default(now()->startOfDay()),
    //             ])
    //             ->action(function (array $data) {

    //                 $user = Auth::user();

    //                 // Ambil customer dan user id sesuai kondisi pelanggan baru atau tidak
    //                 // if (!empty($data['is_new_customer']) && $data['is_new_customer']) {
    //                 //     // Buat user baru
    //                 //     // $newUser = \App\Models\User::create([
    //                 //     //     'name' => $data['nama'],
    //                 //     //     'email' => $data['user_email'],
    //                 //     //     'password' => bcrypt($data['user_password']),
    //                 //     // ]);
    //                 //     // Buat customer baru
    //                 //     $customer = Customer::create([
    //                 //         'nama' => $data['nama'],
    //                 //         'nomor' => $data['nomor'],
    //                 //     ]);
    //                 //     $data = $this->mutateFormDataBeforeCreate($data);
    //                 // } else {
    //                 //     $customer = Customer::find($data['customer_id']);
    //                 //     $data = $this->mutateFormDataBeforeCreate($data);
    //                 // }

    //                 // if (!$customer) {
    //                 //     Notification::make()
    //                 //         ->title('Gagal Mendaftar')
    //                 //         ->body('Pelanggan tidak ditemukan.')
    //                 //         ->danger()
    //                 //         ->send();
    //                 //     return;
    //                 // }

    //                 $bookingDate = $data['booking_date'];
    //                 $tenantId = Auth::user()?->teams->first()?->id;

    //                 $lastQueue = Queue::where('tenant_id', $tenantId)
    //                     ->whereDate('booking_date', $bookingDate)
    //                     ->orderByDesc('nomor_antrian')
    //                     ->first();
    //                 $nextNomorAntrian = $lastQueue ? $lastQueue->nomor_antrian + 1 : 1;

    //                 $queue = Queue::create([
    //                     'tenant_id' => Auth::user()?->teams->first()?->id,
    //                     'produk_id' => $data['produk_id'],
    //                     'booking_date' => $bookingDate,
    //                     'nomor_antrian' => $nextNomorAntrian,
    //                     'status' => 'menunggu',
    //                     'is_validated' => true,
    //                 ]);

    //                 Notification::make()
    //                     ->title('Berhasil Mendaftar')
    //                     ->body("Berhasil mendaftar dengan nomor antrian: {$nextNomorAntrian}")
    //                     ->success()
    //                     ->actions([
    //                         Action::make('printNow')
    //                             ->label('Print Sekarang')
    //                             ->url(route('antrian.print', ['queue' => $queue->id]))
    //                             ->openUrlInNewTab(),
    //                     ])

    //                     ->send()
    //                     ->sendToDatabase(Auth::user());
    //             })
    //     ];
    // }
    public function getHeaderActions(): array
    {
        return [
            Actions\Action::make('Daftar Antrian')
                ->label('Daftar Antrian')
                ->form([
                    Select::make('produk_id')
                        ->label('Pilih Produk')
                        ->options(function (callable $get) {
                            $tenantId = Auth::user()?->teams->first()?->id;
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

                    $tenantId = Auth::user()?->teams->first()?->id;
                    $produkId = $data['produk_id'];
                    $bookingDate = $data['booking_date'];

                    if ($tenantId == 4) {

                        $lastQueue = Queue::where('tenant_id', $tenantId)
                            ->where('produk_id', $produkId)
                            ->whereDate('booking_date', $bookingDate)
                            ->orderByDesc('nomor_antrian')
                            ->first();

                        $nextNomorAntrian = $lastQueue ? $lastQueue->nomor_antrian + 1 : 1;
                    } else {
                        $lastQueue = Queue::where('tenant_id', $tenantId)
                            ->whereDate('booking_date', $bookingDate)
                            ->orderByDesc('nomor_antrian')
                            ->first();

                        $nextNomorAntrian = $lastQueue ? $lastQueue->nomor_antrian + 1 : 1;
                    }

                    $queue = Queue::create([
                        'tenant_id' => $tenantId,
                        'produk_id' => $produkId,
                        'booking_date' => $bookingDate,
                        'nomor_antrian' => $nextNomorAntrian,
                        'status' => 'menunggu',
                        'is_validated' => true,
                    ]);

                    Notification::make()
                        ->title('Berhasil Mendaftar')
                        ->body("Berhasil mendaftar dengan nomor antrian: {$nextNomorAntrian}")
                        ->success()
                        ->actions([
                            Action::make('printNow')
                                ->label('Print Sekarang')
                                ->url(route('antrian.print', ['queue' => $queue->id]))
                                ->openUrlInNewTab(),
                        ])
                        ->send()
                        ->sendToDatabase(Auth::user());
                })
        ];
    }


    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Tidak perlu buat customer di sini, sudah di-handle di action
        unset($data['nama'], $data['nomor'], $data['is_new_customer'], $data['user_email'], $data['user_password']);
        return $data;
    }
}
