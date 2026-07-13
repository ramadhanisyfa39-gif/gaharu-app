<x-app-layout>
    <x-slot name="header">
        Detil Gudang: {{ $gudang->nama }}
    </x-slot>

    <div class="container py-4">
        <div class="mb-3">
            <a href="{{ route('gudangs.index') }}" class="btn btn-secondary">
                &larr; Kembali ke Daftar Gudang
            </a>
        </div>

        <div class="card border-0 shadow-sm" style="border-radius: 12px; overflow: hidden; max-width: 600px;">
            <div class="card-header text-white" style="background-color: #d88656; padding: 16px 20px;">
                <h5 class="mb-0 fw-bold">Informasi Gudang</h5>
            </div>
            <div class="card-body p-4">
                <div class="row">
                    <div class="col-12 mb-3">
                        <label class="fw-bold text-muted small uppercase">Nama Gudang</label>
                        <p class="fs-5 text-dark fw-semibold">{{ $gudang->nama }}</p>
                    </div>

                    <div class="col-12 mb-3">
                        <label class="fw-bold text-muted small uppercase">Kategori</label>
                        <p class="fs-5 text-dark fw-semibold">{{ $gudang->kategori }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
