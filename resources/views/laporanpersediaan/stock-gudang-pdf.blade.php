<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<style>
    * { margin:0; padding:0; box-sizing:border-box; }
    body { font-family:'DejaVu Sans',sans-serif; font-size:10px; color:#222; }
    .header { border-bottom:3px solid #5a3416; padding-bottom:10px; margin-bottom:14px; }
    .header h1 { font-size:17px; font-weight:700; color:#5a3416; }
    .header .meta { font-size:10px; color:#888; margin-top:3px; }
    table { width:100%; border-collapse:collapse; }
    thead th { background:#5a3416; color:#fff; padding:6px 8px; font-size:9px; text-transform:uppercase; }
    tbody tr:nth-child(even) { background:#fdf9f6; }
    tbody tr.habis td { background:#fde8ea; }
    tbody td { padding:5px 8px; border-bottom:1px solid #f0e8e0; }
    .text-right  { text-align:right; }
    .text-center { text-align:center; }
    .plus  { color:#198754; font-weight:700; }
    .minus { color:#dc3545; font-weight:700; }
    .footer { margin-top:16px; border-top:1px solid #eee; padding-top:6px; font-size:9px; color:#aaa; text-align:right; }
</style>
</head>
<body>
    <div class="header">
        <h1>Laporan Posisi Stok Gudang</h1>
        <div class="meta">Per tanggal: {{ now()->format('d M Y, H:i') }}</div>
    </div>
    <table>
        <thead>
            <tr>
                <th>Kode Barang</th>
                <th>Nama Barang</th>
                <th>Gudang</th>
                <th>Satuan</th>
                <th class="text-right">Stok</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data as $row)
                <tr class="{{ $row->jumlah == 0 ? 'habis' : '' }}">
                    <td>{{ $row->kode_barang }}</td>
                    <td>{{ $row->nama_barang }}</td>
                    <td>{{ $row->nama_gudang }}</td>
                    <td>{{ $row->satuan }}</td>
                    <td class="text-right {{ $row->jumlah == 0 ? 'minus' : 'plus' }}">
                        {{ number_format($row->jumlah, 2) }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <div class="footer">Sistem ERP Gaharu · {{ auth()->user()->nama ?? '' }} · {{ now()->format('d/m/Y H:i') }}</div>
</body>
</html>