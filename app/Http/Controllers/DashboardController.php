<?php

namespace App\Http\Controllers;

use App\Models\Pesanan;
use App\Models\Supplier;
use App\Models\MasterBarang;
use App\Models\WorkOrder;
use App\Models\Pembelian;
use App\Models\StokGudang;
use App\Models\StokGudangBatch;
use App\Models\PenjualanPos;
use App\Models\Produksi;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Carbon\CarbonPeriod;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $roleName = $user->role->nama ?? '';

        /*
        |--------------------------------------------------------------------------
        | SUMMARY CARDS (Role-Based Visibility)
        |--------------------------------------------------------------------------
        | B2B and Production are only visible to specific roles:
        | - Kepala Outlet Gaharu
        | - Bagian Produksi
        | - Direktur Keuangan
        | - Super Admin / Administrator
        */
        $hasB2bAccess = in_array($roleName, ['Kepala Outlet Gaharu', 'Direktur Keuangan', 'Super Admin', 'Administrator']);
        $hasProductionAccess = in_array($roleName, ['Kepala Outlet Gaharu', 'Bagian Produksi', 'Direktur Keuangan', 'Super Admin', 'Administrator']);
        $hasPurchaseAccess = in_array($roleName, ['Kepala Outlet Gaharu', 'Kepala Gudang', 'Direktur Keuangan', 'Super Admin', 'Administrator']);

        $totalPesanan = $hasB2bAccess ? Pesanan::count() : 0;
        $totalWO = $hasProductionAccess ? WorkOrder::count() : 0;
        $totalSupplier = $hasPurchaseAccess ? Supplier::count() : 0;
        $totalProduk = MasterBarang::count(); // Global query scope will automatically restrict what items they see!

        /*
        |--------------------------------------------------------------------------
        | NILAI INVENTORY FIFO (Role-Based Filter)
        |--------------------------------------------------------------------------
        */
        $inventoryQuery = StokGudangBatch::query();
        if ($roleName === 'Kepala Outlet Gaharu') {
            $inventoryQuery->where('gudang_id', 2);
        } elseif ($roleName === 'Kepala Outlet Kejingga') {
            $inventoryQuery->where('gudang_id', 4);
        } elseif ($roleName === 'Kepala Gudang') {
            $inventoryQuery->where('gudang_id', 1);
        }
        
        $inventoryValue = $inventoryQuery->select(
            DB::raw('SUM(qty_sisa * harga_per_qty) as total')
        )->value('total') ?? 0;

        /*
        |--------------------------------------------------------------------------
        | PEMBELIAN BULAN INI (Role-Based Filter)
        |--------------------------------------------------------------------------
        */
        $pembelianQuery = Pembelian::whereMonth('tanggal', Carbon::now()->month)
            ->whereYear('tanggal', Carbon::now()->year);

        if ($roleName === 'Kepala Outlet Gaharu') {
            $pembelianQuery->where('gudang_id', 2);
        } elseif ($roleName === 'Kepala Gudang') {
            $pembelianQuery->where('gudang_id', 1);
        } elseif ($roleName === 'Kepala Outlet Kejingga') {
            $pembelianQuery->whereRaw('1 = 0'); // No purchase access
        }

        $pembelianBulanIni = $hasPurchaseAccess ? $pembelianQuery->sum('total') : 0;

        /*
        |--------------------------------------------------------------------------
        | BARANG HAMPIR HABIS (Role-Based Filter)
        |--------------------------------------------------------------------------
        */
        $barangHampirHabisQuery = StokGudang::query()
            ->join('master_barang', 'stok_gudang.barang_id', '=', 'master_barang.id')
            ->where('master_barang.is_active', true)
            ->where('stok_gudang.jumlah', '<=', 10);

        if ($roleName === 'Kepala Outlet Gaharu') {
            $barangHampirHabisQuery->where('stok_gudang.gudang_id', 2);
        } elseif ($roleName === 'Kepala Outlet Kejingga') {
            $barangHampirHabisQuery->where('stok_gudang.gudang_id', 4);
        } elseif ($roleName === 'Kepala Gudang') {
            $barangHampirHabisQuery->where('stok_gudang.gudang_id', 1);
        }

        $barangHampirHabis = $barangHampirHabisQuery
            ->select(
                'master_barang.nama',
                'master_barang.satuan',
                'stok_gudang.jumlah'
            )
            ->orderBy('stok_gudang.jumlah')
            ->limit(5)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | TREN PEMBELIAN 7 HARI TERAKHIR (Role-Based)
        |--------------------------------------------------------------------------
        */
        $labelsPembelian = [];
        $dataPembelian = [];
        if ($hasPurchaseAccess) {
            $chartPembelianQuery = Pembelian::where('tanggal', '>=', now()->subDays(6)->startOfDay());
            if ($roleName === 'Kepala Outlet Gaharu') {
                $chartPembelianQuery->where('gudang_id', 2);
            } elseif ($roleName === 'Kepala Gudang') {
                $chartPembelianQuery->where('gudang_id', 1);
            }
            $chartData = $chartPembelianQuery
                ->selectRaw('DATE(tanggal) as date_label, SUM(total) as daily_total')
                ->groupBy('date_label')
                ->get()
                ->pluck('daily_total', 'date_label');

            $periode = CarbonPeriod::create(now()->subDays(6), now());
            foreach ($periode as $tanggal) {
                $dateStr = $tanggal->format('Y-m-d');
                $labelsPembelian[] = $tanggal->format('d M');
                $dataPembelian[] = (float) ($chartData->get($dateStr) ?? 0);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | TREN PENJUALAN POS 7 HARI TERAKHIR (Role-Based)
        |--------------------------------------------------------------------------
        */
        $labelsPos = [];
        $dataPos = [];
        $chartPosQuery = PenjualanPos::where('tanggal', '>=', now()->subDays(6)->startOfDay())
            ->whereIn('status', ['SUKSES', 'Approved', 'Completed']);

        if ($roleName === 'Kepala Outlet Gaharu') {
            $chartPosQuery->where('gudang_id', 2);
        } elseif ($roleName === 'Kepala Outlet Kejingga') {
            $chartPosQuery->where('gudang_id', 4);
        }

        $chartPosData = $chartPosQuery
            ->selectRaw('DATE(tanggal) as date_label, SUM(total) as daily_total')
            ->groupBy('date_label')
            ->get()
            ->pluck('daily_total', 'date_label');

        $periode = CarbonPeriod::create(now()->subDays(6), now());
        foreach ($periode as $tanggal) {
            $dateStr = $tanggal->format('Y-m-d');
            $labelsPos[] = $tanggal->format('d M');
            $dataPos[] = (float) ($chartPosData->get($dateStr) ?? 0);
        }

        /*
        |--------------------------------------------------------------------------
        | TREN PENJUALAN B2B 7 HARI TERAKHIR (Role-Based)
        |--------------------------------------------------------------------------
        */
        $labelsB2b = [];
        $dataB2b = [];
        if ($hasB2bAccess) {
            $chartB2bData = Pesanan::where('tanggal', '>=', now()->subDays(6)->startOfDay())
                ->selectRaw('DATE(tanggal) as date_label, SUM(total_pesanan) as daily_total')
                ->groupBy('date_label')
                ->get()
                ->pluck('daily_total', 'date_label');

            $periode = CarbonPeriod::create(now()->subDays(6), now());
            foreach ($periode as $tanggal) {
                $dateStr = $tanggal->format('Y-m-d');
                $labelsB2b[] = $tanggal->format('d M');
                $dataB2b[] = (float) ($chartB2bData->get($dateStr) ?? 0);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | PROSENTASE STATUS PRODUKSI (Role-Based)
        |--------------------------------------------------------------------------
        */
        $productionStatus = [];
        if ($hasProductionAccess) {
            $prodStatusData = Produksi::selectRaw('status_produksi, count(*) as total')
                ->groupBy('status_produksi')
                ->pluck('total', 'status_produksi')
                ->toArray();

            $statuses = ['Draft', 'Diproses', 'Selesai', 'Batal'];
            foreach ($statuses as $st) {
                $productionStatus[$st] = $prodStatusData[$st] ?? 0;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | TOP 3 BAHAN SERING DIBELI (Role-Based)
        |--------------------------------------------------------------------------
        */
        $bahanSeringDibeliQuery = DB::table('pembelian_detail')
            ->join('master_barang', 'pembelian_detail.barang_id', '=', 'master_barang.id')
            ->join('pembelian', 'pembelian_detail.pembelian_id', '=', 'pembelian.id')
            ->select('master_barang.nama', 'master_barang.satuan', DB::raw('SUM(pembelian_detail.qty) as total_qty'));

        if ($roleName === 'Kepala Outlet Gaharu') {
            $bahanSeringDibeliQuery->where('pembelian.gudang_id', 2);
        } elseif ($roleName === 'Kepala Gudang') {
            $bahanSeringDibeliQuery->where('pembelian.gudang_id', 1);
        }

        $bahanSeringDibeli = $bahanSeringDibeliQuery
            ->groupBy('master_barang.id', 'master_barang.nama', 'master_barang.satuan')
            ->orderByDesc('total_qty')
            ->limit(3)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | TOP 3 SUPPLIERS (Role-Based)
        |--------------------------------------------------------------------------
        */
        $supplierTeratasQuery = DB::table('pembelian')
            ->join('suppliers', 'pembelian.supplier_id', '=', 'suppliers.id')
            ->select('suppliers.nama', DB::raw('SUM(pembelian.total) as total_nominal'));

        if ($roleName === 'Kepala Outlet Gaharu') {
            $supplierTeratasQuery->where('pembelian.gudang_id', 2);
        } elseif ($roleName === 'Kepala Gudang') {
            $supplierTeratasQuery->where('pembelian.gudang_id', 1);
        }

        $supplierTeratas = $supplierTeratasQuery
            ->groupBy('suppliers.id', 'suppliers.nama')
            ->orderByDesc('total_nominal')
            ->limit(3)
            ->get();

        return view('dashboard', compact(
            'totalPesanan', 
            'totalWO', 
            'totalSupplier', 
            'totalProduk',
            'inventoryValue', 
            'pembelianBulanIni', 
            'barangHampirHabis',
            'labelsPembelian', 
            'dataPembelian',
            'labelsPos',
            'dataPos',
            'labelsB2b',
            'dataB2b',
            'productionStatus',
            'bahanSeringDibeli',
            'supplierTeratas',
            'hasB2bAccess',
            'hasProductionAccess',
            'hasPurchaseAccess'
        ));
    }

    public function keuangan()
    {
        $user = auth()->user();
        $roleName = $user->role->nama ?? '';

        // Only allow Kepala Outlet Gaharu, Direktur Keuangan, Super Admin, Administrator
        if (!in_array($roleName, ['Kepala Outlet Gaharu', 'Direktur Keuangan', 'Super Admin', 'Administrator'])) {
            abort(403, 'Anda tidak memiliki hak akses ke Dashboard Keuangan.');
        }

        // Base helper query for joining journal_items with header tables
        $getBaseJournalItems = function() {
            return DB::table('journal_items')
                ->leftJoin('journals', function ($join) {
                    $join->on('journal_items.journal_id', '=', 'journals.id')
                         ->whereIn('journal_items.journal_type', ['jurnal_umum', 'jurnal', 'closing']);
                })
                ->leftJoin('jurnal_pembelian', function ($join) {
                    $join->on('journal_items.journal_id', '=', 'jurnal_pembelian.id')
                         ->where('journal_items.journal_type', '=', 'jurnal_pembelian');
                })
                ->leftJoin('jurnal_penjualan_pos', function ($join) {
                    $join->on('journal_items.journal_id', '=', 'jurnal_penjualan_pos.id')
                         ->where('journal_items.journal_type', '=', 'jurnal_penjualan_pos');
                })
                ->leftJoin('jurnal_penjualan_b2b', function ($join) {
                    $join->on('journal_items.journal_id', '=', 'jurnal_penjualan_b2b.id')
                         ->where('journal_items.journal_type', '=', 'jurnal_penjualan_b2b');
                })
                ->leftJoin('jurnal_penyesuaian', function ($join) {
                    $join->on('journal_items.journal_id', '=', 'jurnal_penyesuaian.id')
                         ->whereIn('journal_items.journal_type', [\App\Models\JurnalPenyesuaian::class, 'jurnal_penyesuaian']);
                })
                ->join('chart_of_accounts', 'journal_items.account_id', '=', 'chart_of_accounts.id');
        };

        // 1. Profit & Loss trend for the last 6 months
        $months = [];
        $incomeData = [];
        $expenseData = [];

        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $year = $date->year;
            $month = $date->month;
            $months[] = $date->format('M Y');

            // Income: accounts type 'Pendapatan' or 'Pendapatan Lain-lain' or code starting with '4'
            // balance: kredit - debit
            $income = $getBaseJournalItems()
                ->whereRaw('MONTH(COALESCE(journals.tanggal, jurnal_pembelian.tanggal, jurnal_penjualan_pos.tanggal, jurnal_penjualan_b2b.tanggal, jurnal_penyesuaian.tanggal)) = ?', [$month])
                ->whereRaw('YEAR(COALESCE(journals.tanggal, jurnal_pembelian.tanggal, jurnal_penjualan_pos.tanggal, jurnal_penjualan_b2b.tanggal, jurnal_penyesuaian.tanggal)) = ?', [$year])
                ->where(function($q) {
                    $q->whereIn('chart_of_accounts.tipe', ['Pendapatan', 'Pendapatan Lain-lain'])
                      ->orWhere('chart_of_accounts.kode', 'like', '4%');
                })
                ->selectRaw('SUM(journal_items.kredit - journal_items.debit) as total')
                ->value('total') ?? 0;

            // Expenses: accounts type 'Beban' or codes starting with '5', '6', '7', '8'
            // balance: debit - kredit
            $expense = $getBaseJournalItems()
                ->whereRaw('MONTH(COALESCE(journals.tanggal, jurnal_pembelian.tanggal, jurnal_penjualan_pos.tanggal, jurnal_penjualan_b2b.tanggal, jurnal_penyesuaian.tanggal)) = ?', [$month])
                ->whereRaw('YEAR(COALESCE(journals.tanggal, jurnal_pembelian.tanggal, jurnal_penjualan_pos.tanggal, jurnal_penjualan_b2b.tanggal, jurnal_penyesuaian.tanggal)) = ?', [$year])
                ->where(function($q) {
                    $q->whereIn('chart_of_accounts.tipe', ['Beban', 'Beban Administratif', 'Beban Operasional', 'Beban Pajak', 'Harga Pokok Penjualan'])
                      ->orWhere('chart_of_accounts.kode', 'like', '5%')
                      ->orWhere('chart_of_accounts.kode', 'like', '6%')
                      ->orWhere('chart_of_accounts.kode', 'like', '7%')
                      ->orWhere('chart_of_accounts.kode', 'like', '8%');
                })
                ->selectRaw('SUM(journal_items.debit - journal_items.kredit) as total')
                ->value('total') ?? 0;

            $incomeData[] = (float)$income;
            $expenseData[] = (float)$expense;
        }

        // 2. Cash and Bank Balances
        // Fetch accounts of type 'Kas' or 'Bank' or code starts with '11' (usually cash/bank)
        $cashAccounts = DB::table('chart_of_accounts')
            ->whereIn('tipe', ['Kas', 'Bank'])
            ->orWhere('kode', 'like', '11%')
            ->get();

        $balances = [];
        foreach ($cashAccounts as $acc) {
            $balance = $getBaseJournalItems()
                ->where('journal_items.account_id', $acc->id)
                ->selectRaw('SUM(journal_items.debit - journal_items.kredit) as balance')
                ->value('balance') ?? 0;

            $balances[] = [
                'kode' => $acc->kode,
                'nama' => $acc->nama,
                'saldo' => (float)$balance
            ];
        }

        // 3. Assets vs Liabilities/Equity
        // Assets: code starts with '1'
        $totalAssets = $getBaseJournalItems()
            ->where('chart_of_accounts.kode', 'like', '1%')
            ->selectRaw('SUM(journal_items.debit - journal_items.kredit) as total')
            ->value('total') ?? 0;

        // Liabilities: code starts with '2'
        $totalLiabilities = $getBaseJournalItems()
            ->where('chart_of_accounts.kode', 'like', '2%')
            ->selectRaw('SUM(journal_items.kredit - journal_items.debit) as total')
            ->value('total') ?? 0;

        // Equity: code starts with '3'
        $totalEquity = $getBaseJournalItems()
            ->where('chart_of_accounts.kode', 'like', '3%')
            ->selectRaw('SUM(journal_items.kredit - journal_items.debit) as total')
            ->value('total') ?? 0;

        // 4. Recent Adjustments
        $recentAdjustments = DB::table('jurnal_penyesuaian')
            ->orderByDesc('tanggal')
            ->limit(5)
            ->get();

        // 5. Recent Journals (Union across all journal types)
        $qJournals = DB::table('journals')->select('id', 'tanggal', 'no_ref', 'deskripsi', DB::raw("COALESCE(status, 'posted') as status"));
        $qPembelian = DB::table('jurnal_pembelian')->select('id', 'tanggal', 'no_ref', 'deskripsi', DB::raw("'posted' as status"));
        $qPos = DB::table('jurnal_penjualan_pos')->select('id', 'tanggal', 'no_ref', 'deskripsi', DB::raw("'posted' as status"));
        $qB2b = DB::table('jurnal_penjualan_b2b')->select('id', 'tanggal', 'no_ref', 'deskripsi', DB::raw("'posted' as status"));

        $recentJournals = $qJournals
            ->unionAll($qPembelian)
            ->unionAll($qPos)
            ->unionAll($qB2b)
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        return view('dashboard_keuangan', compact(
            'months', 'incomeData', 'expenseData',
            'balances', 'totalAssets', 'totalLiabilities', 'totalEquity',
            'recentAdjustments', 'recentJournals'
        ));
    }
}