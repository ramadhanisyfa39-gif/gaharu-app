<x-app-layout>

<div class="row justify-content-center">
    <div class="col-lg-7 col-md-9">

        <div class="card shadow-sm border-0">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0 fw-bold">Edit Gudang</h5>
                    <small class="text-muted">Perbarui data gudang</small>
                </div>

                <a href="{{ route('gudangs.index') }}" class="btn btn-outline-secondary btn-sm">
                    Kembali
                </a>
            </div>

            <div class="card-body">
                <form action="{{ route('gudangs.update', $gudang->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label for="nama" class="form-label fw-semibold">
                            Nama Gudang <span class="text-danger">*</span>
                        </label>
                        <input
                            type="text"
                            name="nama"
                            id="nama"
                            class="form-control @error('nama') is-invalid @enderror"
                            value="{{ old('nama', $gudang->nama) }}"
                            placeholder="Contoh: Gudang Bahan Baku"
                        >

                        @error('nama')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="kategori" class="form-label fw-semibold">
                            Kategori <span class="text-danger">*</span>
                        </label>

                        <select
                            name="kategori"
                            id="kategori"
                            class="form-select @error('kategori') is-invalid @enderror"
                        >
                            <option value="">-- Pilih Kategori --</option>

                            <option value="Bahan Baku" {{ old('kategori', $gudang->kategori) == 'Bahan Baku' ? 'selected' : '' }}>
                                Bahan Baku
                            </option>

                            <option value="Barang Jadi" {{ old('kategori', $gudang->kategori) == 'Barang Jadi' ? 'selected' : '' }}>
                                Barang Jadi
                            </option>

                            <option value="Operasional" {{ old('kategori', $gudang->kategori) == 'Operasional' ? 'selected' : '' }}>
                                Operasional
                            </option>

                            <option value="Outlet" {{ old('kategori', $gudang->kategori) == 'Outlet' ? 'selected' : '' }}>
                                Outlet
                            </option>
                        </select>

                        @error('kategori')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('gudangs.index') }}" class="btn btn-light border">
                            Batal
                        </a>

                        <button type="submit" class="btn btn-primary">
                            Update Gudang
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</div>

</x-app-layout>