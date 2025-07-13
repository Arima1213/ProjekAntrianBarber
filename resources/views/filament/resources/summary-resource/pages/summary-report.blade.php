<x-filament::page>
	<h2 class="mb-4 text-lg font-bold text-gray-900 dark:text-gray-100">Laporan Ringkasan</h2>

	<form wire:submit.prevent="getSummaryData" class="mb-6 space-y-4">
		<div class="grid grid-cols-1 gap-4 md:grid-cols-3">
			<x-filament::input type="date" wire:model.defer="from" label="Tanggal Awal" />
			<x-filament::input type="date" wire:model.defer="until" label="Tanggal Akhir" />

			@if (Auth::user()->hasRole('super_admin'))
				{{-- <label for="tenant_id" class="block text-sm font-medium text-gray-700 whitespace-normal">Pilih
                    Tenant</label> --}}
				<select wire:model="tenant_id" wire:change="getSummaryData" id="tenant_id"
					class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 sm:text-sm">
					<option value="">Filter Cabang</option>
					@foreach ($tenants as $tenant)
						<option value="{{ $tenant->id }}">{{ $tenant->name }}</option>
					@endforeach
				</select>
			@endif

			<div class="flex items-end gap-3">
				<x-filament::button type="submit" class="w-full md:w-auto">
					Tampilkan
				</x-filament::button>
				<x-filament::button tag="a"
					href="{{ route('summary.export.excel', ['from' => $from, 'until' => $until, 'tenant_id' => $tenant_id ?? null]) }}" target="_blank"
					color="gray" class="w-full md:w-auto">
					EXCEl
				</x-filament::button>
			</div>
		</div>
	</form>

	@php
		$from = $this->from;
		$until = $this->until;
		$today = \Carbon\Carbon::today();
	@endphp
	<div class="overflow-x-auto">
		<table class="min-w-full table-auto border-collapse border border-gray-300 dark:border-gray-700">
			<thead>
				<tr class="bg-gray-100 dark:bg-gray-800">
					<th class="border px-4 py-2 text-gray-900 dark:border-gray-700 dark:text-gray-100">Nama Karyawan
					</th>
					<th class="border px-4 py-2 text-gray-900 dark:border-gray-700 dark:text-gray-100">Jenis Layanan
					</th>
					@foreach ($data['dates'] as $date)
						<th class="border px-4 py-2 text-gray-900 dark:border-gray-700 dark:text-gray-100">
							{{ $date }}</th>
					@endforeach
					<th class="border px-4 py-2 text-gray-900 dark:border-gray-700 dark:text-gray-100">Total</th>
				</tr>
			</thead>
			<tbody>
				@foreach ($data['summary'] as $karyawan => $layanans)
					@foreach ($layanans as $layanan => $items)
						<tr class="bg-white dark:bg-gray-900">
							<td class="border px-4 py-2 text-gray-800 dark:border-gray-700 dark:text-gray-200">
								{{ $karyawan }}</td>
							<td class="border px-4 py-2 text-gray-800 dark:border-gray-700 dark:text-gray-200">
								{{ $layanan }}</td>
							@foreach ($data['dates'] as $date)
								@php
									$value = $items[$date] ?? 0;
									$isPast = \Carbon\Carbon::parse($date)->lessThanOrEqualTo($today);
								@endphp
								<td
									class="{{ $value == 0 && $isPast ? 'bg-red-100 text-red-700 font-bold' : 'text-gray-800 dark:text-gray-200' }} border px-4 py-2 text-center dark:border-gray-700">
									@if ($value == 0 && $isPast)
										-
									@else
										{{ $value }}
									@endif
								</td>
							@endforeach
							<td class="border px-4 py-2 text-center font-semibold text-gray-900 dark:border-gray-700 dark:text-gray-100">
								{{ $items['total'] }}</td>
						</tr>
					@endforeach
				@endforeach
			</tbody>
		</table>
	</div>
</x-filament::page>
