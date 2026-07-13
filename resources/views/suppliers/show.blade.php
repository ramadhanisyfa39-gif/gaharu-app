<x-app-layout>
    <x-slot name="header">
        Detil Supplier: {{ $supplier->nama }}
    </x-slot>

    <div class="container py-4">
        <div class="mb-3">
            <a href="{{ route('suppliers.index') }}" class="btn btn-secondary">
                &larr; Kembali ke Daftar Supplier
            </a>
        </div>

        <div class="card border-0 shadow-sm" style="border-radius: 12px; overflow: hidden; max-width: 600px;">
            <div class="card-header text-white" style="background-color: #d88656; padding: 16px 20px;">
                <h5 class="mb-0 fw-bold">Informasi Supplier</h5>
            </div>
            <div class="card-body p-4">
                <div class="row">
                    <div class="col-12 mb-3">
                        <label class="fw-bold text-muted small uppercase">Nama Supplier</label>
                        <p class="fs-5 text-dark fw-semibold">{{ $supplier->nama }}</p>
                    </div>

                    <div class="col-12 mb-3">
                        <label class="fw-bold text-muted small uppercase">Nomor HP</label>
                        <p class="fs-6 text-dark">{{ $supplier->no_hp ?? '—' }}</p>
                    </div>

                    <div class="col-12 mb-3">
                        <label class="fw-bold text-muted small uppercase">Alamat</label>
                        <p class="fs-6 text-dark">{{ $supplier->alamat ?? '—' }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
