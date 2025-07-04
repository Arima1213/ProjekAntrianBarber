<!DOCTYPE html>
<html lang="id">

<head>
	<meta charset="UTF-8">
	<title>Nomor Antrian {{ $cabang->name }}</title>
	<meta name="csrf-token" content="{{ csrf_token() }}">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
	<style>
		/* Sama seperti sebelumnya */
		html,
		body {
			height: 100%;
			margin: 0;
			padding: 0;
			background: #000;
			color: #fff;
			font-family: Arial;
		}

		.center-container {
			width: 100%;
			max-width: 700px;
			margin: auto;
			text-align: center;
			display: flex;
			flex-direction: column;
			justify-content: center;
			height: 100vh;
		}

		.judul {
			font-size: clamp(24px, 6vw, 56px);
			font-weight: 600;
		}

		.nomor-antrian {
			font-size: clamp(48px, 22vw, 180px);
			font-weight: bold;
			margin: 3vw 0;
		}

		.tanggal {
			font-size: clamp(16px, 3vw, 32px);
			margin-top: 1vw;
		}

		.alamat {
			font-size: clamp(12px, 2vw, 22px);
			margin-top: 1vw;
			color: #ccc;
		}
	</style>
</head>

<body>
	<div class="center-container">
		<div class="judul">Nomor Antrian {{ $cabang->name }}</div>
		<div class="text-center">
			<h1 class="fw-bold">Jumlah Antrian Menunggu</h1>
		</div>
		<div id="jumlah-antrian-menunggu" class="nomor-antrian">-</div>
		<div class="tanggal" id="tanggal"></div>
		<div class="alamat">
			{{ $cabang->lokasi->first()->alamat ?? '-' }}
		</div>
		{{-- <div class="jumlah-antrian">
            Jumlah Antrian Menunggu: <span id="jumlah-antrian-menunggu">-</span>
        </div> --}}

	</div>

	<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
	<script>
		function tampilkanTanggal() {
			const hari = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
			const bulan = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September',
				'Oktober', 'November', 'Desember'
			];
			const t = new Date();
			const teksTanggal = `${hari[t.getDay()]}, ${t.getDate()} ${bulan[t.getMonth()]} ${t.getFullYear()}`;
			$('#tanggal').text(teksTanggal);
		}

		function ambilNomorAntrian() {
			$.ajax({
				url: "{{ route('antrian.today.json', ['id' => $cabang->id]) }}",
				method: "GET",
				success: function(data) {
					// console.log(data); // Debug log untuk melihat data yang diterima
					if (data.queues.length > 0) {
						const terakhir = data.queues[data.queues.length - 1];
						$('#nomor-antrian').text(terakhir.nomor_antrian);
					} else {
						$('#nomor-antrian').text('-');
					}

					// Pastikan menungguCount ada dan tampilkan di HTML
					if (data.menungguCount !== undefined) {
						$('#jumlah-antrian-menunggu').text(data.menungguCount);
					} else {
						$('#jumlah-antrian-menunggu').text('Error');
					}
				},
				error: function() {
					$('#nomor-antrian').text('Error');
					$('#jumlah-antrian-menunggu').text('Error');
				}
			});
		}

		$(document).ready(function() {
			tampilkanTanggal();
			ambilNomorAntrian();
			setInterval(ambilNomorAntrian, 5000);
		});
	</script>
</body>

</html>
