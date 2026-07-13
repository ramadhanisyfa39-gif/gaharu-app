<!DOCTYPE html>
<html>
<head>
    <title>Laporan Status Work Order</title>
    <style>
        body { font-family: sans-serif; font-size: 11px; color: #333; margin: 20px; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h2 { margin: 0; font-size: 18px; color: #1e3a8a; }
        .header p { margin: 5px 0 0 0; font-size: 12px; color: #666; }
        .info-table { width: 100%; margin-bottom: 20px; border-collapse: collapse; }
        .info-table td { padding: 4px 0; font-size: 11px; }
        .main-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .main-table th { background-color: #1e3a8a; color: #ffffff; text-align: left; padding: 8px; font-weight: bold; font-size: 11px; }
        .main-table td { padding: 8px; border-bottom: 1px solid #ddd; font-size: 10px; }
        .main-table tr:nth-child(even) { background-color: #fcfcfc; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .fw-bold { font-weight: bold; }
    </style>
</head>
<body>

    <div class="header">
        <h2>LAPORAN STATUS WORK ORDER</h2>
        <p>CV Gaharu App</p>
    </div>

    <table class="info-table">
        <tr>
            <td width="15%"><strong>Kategori Laporan</strong></td>
            <td width="3%">:</td>
            <td>Status & Progress Work Order (Terbaru)</td>
            <td width="15%" class="text-right"><strong>Tanggal Cetak</strong></td>
            <td width="3%">:</td>
            <td width="20%">{{ date('d-m-Y H:i') }}</td>
        </tr>
    </table>

    <table class="main-table">
        <thead>
            <tr>
                <th width="30" class="text-center">No</th>
                <th>Kode WO</th>
                <th>Tanggal WO</th>
                <th>Pembuat</th>
                <th class="text-center">Total Rencana Qty</th>
                <th class="text-center">Total Realisasi Qty</th>
                <th class="text-center">Progress %</th>
                <th class="text-center">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($workOrderStatus as $index => $row)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td class="fw-bold">{{ $row->kode_wo }}</td>
                <td>{{ date('d-m-Y', strtotime($row->tanggal_wo)) }}</td>
                <td>{{ $row->pembuat->nama ?? 'Sistem' }}</td>
                <td class="text-center">{{ number_format($row->total_rencana, 0) }}</td>
                <td class="text-center">{{ number_format($row->total_realisasi, 0) }}</td>
                <td class="text-center fw-bold">{{ number_format($row->persentase, 0) }}%</td>
                <td class="text-center">{{ $row->status_wo }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="text-center text-muted">Tidak ada data Work Order ditemukan.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

</body>
</html>
