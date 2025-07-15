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
			padding: 10px;
			text-align: center;
			display: flex;
			flex-direction: column;
			justify-content: center;
			height: 100vh;
		}

		.judul {
			font-size: clamp(24px, 6vw, 56px);
			/*font-weight: 600;*/
			/*padding: 10px*/
		}

		.nomor-antrian {
			/* font-size: clamp(48px, 22vw, 180px); */
			font-weight: bold;
			/* margin: 3px 0; */
		}

		.tanggal {
			font-size: clamp(16px, 3vw, 32px);
			/* margin-top: 1vw; */
		}

		.alamat {
			font-size: clamp(12px, 2vw, 22px);
			/* margin-top: 1vw; */
			color: #ccc;
		}

		#sedang-dilayani-wrapper {
			position: absolute;
			top: 45%;
			right: 10%;
			transform: translateY(-50%);
			text-align: right;
			font-size: 22px;
			color: #fff;
			z-index: 10;
		}

		#sedang-dilayani-wrapper .label {
			font-size: 24px;
			font-weight: normal;
			color: #ccc;
		}

		#sedang-dilayani-wrapper .value {
			font-size: 48px;
			font-weight: bold;
			color: #fff;
		}
	</style>
</head>

<body>
	<div class="center-container">
		<!-- Nomor Antrian Sedang Dilayani (absolute) -->
		<div id="sedang-dilayani-wrapper">
			<div class="label">Sedang Dilayani</div>
			<div id="sedang-dilayani" class="value">-</div>
		</div>

		<div class="judul">Nomor Antrian {{ $cabang->name }}</div>
		<div class="text-center" style="font-size: 130px;">
			<h1 class="fw-bold">Jumlah Antrian Menunggu</h1>
		</div>
		<div id="jumlah-antrian-menunggu" class="nomor-antrian" style="font-size: 700px; margin-top: -10rem">-</div>
		<div style="margin-top: -12rem;">
			<div class="tanggal" id="tanggal" style="font-size: 40px"></div>
			<div class="alamat" style="font-size: 40px">
				{{ $cabang->lokasi->first()->alamat ?? '-' }}
			</div>
		</div>
		{{-- <div class="tanggal" id="tanggal"></div>
        <div class="alamat">
            {{ $cabang->lokasi->first()->alamat ?? '-' }}
        </div> --}}
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
					// Menampilkan jumlah menunggu
					if (data.menungguCount !== undefined) {
						$('#jumlah-antrian-menunggu').text(data.menungguCount);
					} else {
						$('#jumlah-antrian-menunggu').text('Error');
					}

					// Menampilkan nomor antrian sedang dilayani
					if (data.sedangDilayani !== null) {
						$('#sedang-dilayani').text(data.sedangDilayani.nomor_antrian);
					} else {
						$('#sedang-dilayani').text('-');
					}

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
