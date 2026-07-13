<x-app-layout>
    <x-slot name="header">
        Detil Kategori: {{ $kategori->nama }}
    </x-slot>

    <div class="container py-4">
        <div class="mb-3">
            <a href="{{ route('kategori.index') }}" class="btn btn-secondary">
                &larr; Kembali ke Daftar Kategori
            </a>
        </div>

        <div class="card border-0 shadow-sm" style="border-radius: 12px; overflow: hidden; max-width: 600px;">
            <div class="card-header text-white" style="background-color: #d88656; padding: 16px 20px;">
                <h5 class="mb-0 fw-bold">Informasi Kategori</h5>
            </div>
            <div class="card-body p-4">
                <div class="row">
                    <div class="col-12 mb-3">
                        <label class="fw-bold text-muted small uppercase">Nama Kategori</label>
                        <p class="fs-5 text-dark fw-semibold">{{ $kategori->nama }}</p>
                    </div>

                    <div class="col-12 mb-3">
                        <label class="fw-bold text-muted small uppercase">Prefix Kode Barang</label>
                        <p class="fs-5 text-dark fw-semibold font-monospace">{{ $kategori->prefix ?? '—' }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
