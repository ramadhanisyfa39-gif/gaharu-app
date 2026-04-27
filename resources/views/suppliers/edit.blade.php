@extends('layouts.app')

@section('content')

<div class="row justify-content-center">
    <div class="col-lg-7 col-md-9">

        <div class="card shadow-sm border-0">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0 fw-bold">Edit Supplier</h5>
                    <small class="text-muted">Perbarui data supplier</small>
                </div>

                <a href="{{ route('suppliers.index') }}" class="btn btn-outline-secondary btn-sm">
                    Kembali
                </a>
            </div>

            <div class="card-body">
                <form action="{{ route('suppliers.update', $supplier->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label for="nama" class="form-label fw-semibold">
                            Nama Supplier <span class="text-danger">*</span>
                        </label>
                        <input 
                            type="text" 
                            name="nama" 
                            id="nama"
                            class="form-control @error('nama') is-invalid @enderror" 
                            value="{{ old('nama', $supplier->nama) }}"
                            placeholder="Masukkan nama supplier"
                        >

                        @error('nama')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="no_hp" class="form-label fw-semibold">
                            No HP
                        </label>
                        <input 
                            type="text" 
                            name="no_hp" 
                            id="no_hp"
                            class="form-control @error('no_hp') is-invalid @enderror" 
                            value="{{ old('no_hp', $supplier->no_hp) }}"
                            placeholder="Contoh: 08123456789"
                        >

                        @error('no_hp')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="alamat" class="form-label fw-semibold">
                            Alamat
                        </label>
                        <textarea 
                            name="alamat" 
                            id="alamat"
                            rows="3"
                            class="form-control @error('alamat') is-invalid @enderror"
                            placeholder="Masukkan alamat supplier"
                        >{{ old('alamat', $supplier->alamat) }}</textarea>

                        @error('alamat')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('suppliers.index') }}" class="btn btn-light border">
                            Batal
                        </a>

                        <button type="submit" class="btn btn-primary">
                            Update Supplier
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</div>

@endsection