<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Queue;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Tables\Filters\Filter;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\GlobalSearch\Actions\Action;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use App\Filament\Resources\QueueResource\Pages;
use App\Filament\Resources\QueueResource\Pages\EditQueue;
use App\Filament\Resources\QueueResource\Pages\ListQueues;
use App\Filament\Resources\QueueResource\Pages\CreateQueue;
use App\Models\Customer;
use App\Models\User;
use Filament\Actions\Exports\ExportColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Get;
use Filament\Tables\Actions\ExportBulkAction;

class QueueResource extends Resource
{
    protected static ?string $model = Queue::class;

    protected static ?string $navigationIcon = 'heroicon-o-queue-list';
    protected static ?string $label = 'Antrian';
    protected static ?string $pluralLabel = 'Antrian';
    protected static ?string $slug = 'antrian';
    protected static ?string $navigationLabel = 'Antrian';
    protected static ?string $recordTitleAttribute = 'nomor_antrian';
    protected static ?string $modelLabel = 'Antrian';
    protected static ?string $pluralModelLabel = 'Antrian';
    protected static null|string $tenantOwnershipRelationshipName = 'tenant';


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Toggle::make('is_new_customer')
                    ->label('Pelanggan Baru?')
                    ->default(false)
                    ->reactive(),

                Select::make('customer_id')
                    ->label('Pilih Pelanggan')
                    ->searchable()
                    ->options(fn() => Customer::pluck('nama', 'id'))
                    ->visible(fn(Get $get) => !$get('is_new_customer'))
                    ->required(fn(Get $get) => !$get('is_new_customer')),

                TextInput::make('nama')
                    ->label('Nama Pelanggan')
                    ->required(fn(Get $get) => $get('is_new_customer'))
                    ->visible(fn(Get $get) => $get('is_new_customer')),

                TextInput::make('nomor')
                    ->label('Nomor Pelanggan')
                    ->numeric()
                    ->required(fn(Get $get) => $get('is_new_customer'))
                    ->visible(fn(Get $get) => $get('is_new_customer')),

                Forms\Components\Select::make('produk_id')
                    ->label('Produk')
                    ->options(function () {
                        $tenantId = Auth::user()?->teams->first()?->id;
                        return \App\Models\Produk::where('tenant_id', $tenantId)->pluck('judul', 'id');
                    })
                    ->required(),

                Forms\Components\TextInput::make('status')
                    ->label('Status Antrian')
                    ->readOnly()
                    ->default('menunggu')
                    ->required(),

                Forms\Components\Hidden::make('is_validated')
                    ->default(true),

                Forms\Components\Select::make('user_id')
                    ->label('Chapster yang Melayani')
                    ->options(fn() => \App\Models\User::pluck('name', 'id'))
                    ->searchable()
                    ->required()
                    ->disabled(),  // Membuat field ini tidak bisa diedit

                DatePicker::make('booking_date')
                    ->label('Tanggal Booking')
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set) {
                        $lastQueue = \App\Models\Queue::whereDate('booking_date', $state)
                            ->orderByDesc('nomor_antrian')
                            ->first();

                        $nextNumber = $lastQueue ? $lastQueue->nomor_antrian + 1 : 1;

                        $set('nomor_antrian', $nextNumber);
                    }),

                TextInput::make('nomor_antrian')
                    ->label('Nomor Antrian')
                    ->required()
                    ->numeric()
                    ->readOnly()
                    ->hint('Akan otomatis terisi jika tanggal booking sudah terisi'),

                Forms\Components\Hidden::make('tenant_id')
                    ->default(fn() => Auth::user()?->teams->first()?->id)
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('customer.nama')
                    ->label('Nama Customer')
                    ->searchable()
                    ->getStateUsing(function ($record) {
                        return $record->customer ? $record->customer->nama : '-';
                    }),

                Tables\Columns\TextColumn::make('produk.judul')
                    ->label('Produk')
                    ->sortable(),
                Tables\Columns\TextColumn::make('nomor_antrian')
                    ->label('Nomor Antrian')
                    ->numeric()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn(string $state) => match ($state) {
                        'menunggu' => 'warning',
                        'selesai' => 'success',
                        'batal' => 'danger',
                        default => 'secondary',
                    }),
                Tables\Columns\IconColumn::make('is_validated')
                    ->label('Tervalidasi')
                    ->sortable()
                    ->boolean(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Chapster')
                    ->sortable(),
                Tables\Columns\TextColumn::make('booking_date')
                    ->label('Tanggal Booking')
                    ->date('l, d F Y')
                    ->sortable(),
            ])
            ->filters([
                Filter::make('today')
                    ->label('Antrian Hari Ini')
                    ->default(true)
                    ->query(function (Builder $query) {
                        return $query->whereDate('booking_date', now()->toDateString());
                    }),
                //filter chapster
                Tables\Filters\SelectFilter::make('requested_chapster_id')
                    ->label('Chapster')
                    ->options([
                        'umum' => 'Umum',
                        'dani' => 'Dani',
                    ]),
                // Filter untuk status antrian
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'menunggu' => 'Menunggu',
                        'selesai' => 'Selesai',
                        'batal' => 'Batal',
                    ]),
                // Filter tanggal booking
                Filter::make('booking_date')
                    ->form([
                        DatePicker::make('date'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (!empty($data['date'])) {
                            return $query->whereDate('booking_date', $data['date']);
                        }
                        return $query;
                    }),

                // Filter untuk menampilkan semua data (tidak difilter tanggal)
                Filter::make('Semua Antrian')
                    ->label('Tampilkan Semua Antrian')
                    ->query(fn(Builder $query) => $query),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),

                // action untuk print struk
                Tables\Actions\Action::make('print_struk')
                    ->label('Print Struk')
                    ->action(function (Queue $record) {
                        return redirect()->route('antrian.print', ['queue' => $record->id]);
                    })
                    ->icon('heroicon-o-printer')
                    ->openUrlInNewTab()
                    ->color('primary')
                    ->disabled(fn(Queue $record) => $record->status === 'selesai'),

                // action to validate the queue
                Tables\Actions\Action::make('validate')
                    ->label('Validate')
                    ->action(function (Queue $record) {
                        $record->update(['is_validated' => true]);
                    })
                    ->requiresConfirmation()
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->disabled(fn(Queue $record) => $record->is_validated || $record->status === 'batal'),

                // action selesai with conditions
                Tables\Actions\Action::make('selesai')
                    ->label('Selesai')
                    ->action(function (Queue $record) {
                        $record->update([
                            'status' => 'selesai',
                            'user_id' => Auth::user()?->id,
                        ]);
                    })
                    ->requiresConfirmation()
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->disabled(
                        fn(Queue $record) =>
                        !$record->is_validated ||
                            $record->status === 'selesai' ||
                            $record->status === 'batal' ||
                            Auth::user()?->hasRole('super_admin') ||
                            Auth::user()?->teams->first()?->id === 1 // Check if the user is from 'cabang 001'
                    ),

                // action batalkan
                Tables\Actions\Action::make('batalkan')
                    ->label('Cancel')
                    ->action(function (Queue $record) {
                        $record->update(['status' => 'batal']);
                    })
                    ->requiresConfirmation()
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->disabled(
                        fn(Queue $record) =>
                        !$record->is_validated ||
                            $record->status === 'batal' ||
                            $record->status === 'selesai'
                    ),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    ExportBulkAction::make()
                        ->label('Export Antrian Terpilih')
                        ->exportColumns([
                            ExportColumn::make('id')->label('ID'),
                            ExportColumn::make('customer.nama')->label('Nama Customer'),
                            ExportColumn::make('produk.judul')->label('Produk'),
                            ExportColumn::make('nomor_antrian')->label('Nomor Antrian'),
                            ExportColumn::make('status')->label('Status'),
                            ExportColumn::make('is_validated')->label('Tervalidasi'),
                            ExportColumn::make('user.name')->label('Chapster'),
                            ExportColumn::make('booking_date')->label('Tanggal Booking'),
                            ExportColumn::make('created_at')->label('Dibuat Pada'),
                        ])
                        ->fileName(fn() => 'export-antrian-' . now()->format('Y-m-d-H-i-s') . '.xlsx'),

                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListQueues::route('/'),
            'create' => Pages\CreateQueue::route('/create'),
            'edit' => Pages\EditQueue::route('/{record}/edit'),
        ];
    }
}