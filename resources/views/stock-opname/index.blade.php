<x-app-layout>

<x-slot name="header">
    Stock Opname
</x-slot>

<div class="container-fluid">

    {{-- PAGE HEADER --}}

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h3 class="fw-bold mb-1 text-dark">
                Stock Opname
            </h3>

            <p class="text-muted mb-0">
                Penyesuaian persediaan berdasarkan hasil perhitungan fisik gudang.
            </p>

        </div>

        <button
            class="btn btn-primary px-4"
            data-bs-toggle="modal"
            data-bs-target="#createOpnameModal">

            <i class="bi bi-plus-circle me-2"></i>
            Buat Stock Opname

        </button>

    </div>

    {{-- SUMMARY CARD --}}

    <div class="row mb-4">

        <div class="col-md-4">

            <div class="card border-0 shadow-sm rounded-4">

                <div class="card-body">

                    <div class="text-muted mb-2">
                        Total Stock Opname
                    </div>

                    <h2 class="fw-bold mb-0">
                        {{ $stockOpname->total() }}
                    </h2>

                </div>

            </div>

        </div>

        <div class="col-md-4">

            <div class="card border-0 shadow-sm rounded-4">

                <div class="card-body">

                    <div class="text-muted mb-2">
                        Draft
                    </div>

                    <h2 class="fw-bold text-warning mb-0">

                        {{ $stockOpname->where('status','draft')->count() }}

                    </h2>

                </div>

            </div>

        </div>

        <div class="col-md-4">

            <div class="card border-0 shadow-sm rounded-4">

                <div class="card-body">

                    <div class="text-muted mb-2">
                        Approved
                    </div>

                    <h2 class="fw-bold text-success mb-0">

                        {{ $stockOpname->where('status','approved')->count() }}

                    </h2>

                </div>

            </div>

        </div>

    </div>

    {{-- TABLE CARD --}}

    <div class="card border-0 shadow-sm rounded-4">

        <div class="card-header bg-white border-0 pt-4 px-4">

            <h5 class="fw-bold mb-0">
                Daftar Stock Opname
            </h5>

        </div>

        <div class="card-body p-0">

            <div class="table-responsive">

                <table class="table align-middle mb-0">

                    <thead style="background:#7A4517;color:white;">

                        <tr>

                            <th>Kode</th>

                            <th>Tanggal</th>

                            <th>Gudang</th>

                            <th>Status</th>

                            <th width="180">
                                Aksi
                            </th>

                        </tr>

                    </thead>

                    <tbody>

                        @forelse($stockOpname as $row)

                            <tr>

                                <td class="fw-semibold">

                                    {{ $row->kode_opname }}

                                </td>

                                <td>

                                    {{ \Carbon\Carbon::parse($row->tanggal)->format('d M Y') }}

                                </td>

                                <td>

                                    {{ $row->gudang->nama ?? '-' }}

                                </td>

                                <td>

                                    @if($row->status == 'approved')

                                        <span class="badge bg-success">

                                            Approved

                                        </span>

                                    @else

                                        <span class="badge bg-warning text-dark">

                                            Draft

                                        </span>

                                    @endif

                                </td>

                                <td>

    <a href="{{ route('stock-opname.show',$row->id) }}"
       class="btn btn-sm btn-outline-primary">
        Detail
    </a>

    @if($row->status == 'draft')

        <a href="{{ route('stock-opname.approve',$row->id) }}"
           class="btn btn-sm btn-success"
           onclick="return confirm('Approve stock opname ini?')">

            Approve

        </a>

    @else

        <span class="badge bg-secondary">

            Sudah Approve

        </span>

    @endif

</td>

                            </tr>

                        @empty

                            <tr>

                                <td
                                    colspan="5"
                                    class="text-center py-5 text-muted">

                                    Belum ada data Stock Opname

                                </td>

                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

        </div>

        <div class="card-footer bg-white">

            {{ $stockOpname->links() }}

        </div>

    </div>

</div>

{{-- MODAL CREATE --}}

<div
    class="modal fade"
    id="createOpnameModal"
    tabindex="-1"
    aria-hidden="true">

<div class="modal-dialog modal-lg">

    <div class="modal-content border-0 shadow">

        <div
            class="modal-header text-white"
            style="background:#A55A1A;">

            <h5 class="modal-title">
                Buat Stock Opname
            </h5>

            <button
                type="button"
                class="btn-close btn-close-white"
                data-bs-dismiss="modal">
            </button>

        </div>

        <form
            method="GET"
            action="{{ route('stock-opname.create') }}">

            <div class="modal-body">

                <div class="alert alert-light border">

                    Pilih gudang yang akan dilakukan stock opname.

                </div>

                <div class="mb-3">

                    <label class="form-label fw-semibold">
                        Gudang
                    </label>

                    <select
                        name="gudang_id"
                        class="form-select"
                        required>

                        <option value="">
                            Pilih Gudang
                        </option>

                        @foreach($gudangs as $gudang)

                            <option value="{{ $gudang->id }}">
                                {{ $gudang->nama }}
                            </option>

                        @endforeach

                    </select>

                </div>

            </div>

            <div class="modal-footer">

                <button
                    type="button"
                    class="btn btn-light"
                    data-bs-dismiss="modal">

                    Batal

                </button>

                <button
                    type="submit"
                    class="btn btn-primary">

                    Mulai Opname

                </button>

            </div>

        </form>

    </div>

</div>

</div>


</x-app-layout>