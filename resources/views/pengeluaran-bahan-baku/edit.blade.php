<x-app-layout>

<div class="container">

    <h3 class="mb-3">
        Edit Pengeluaran Bahan Baku
    </h3>

    <div class="card">
        <div class="card-body">

            <form
                action="{{ route('pengeluaran-bahan-baku.update', $pengeluaran->id) }}"
                method="POST">

                @csrf
                @method('PUT')

                <div class="mb-3">

                    <label>Gudang</label>

                    <select
                        name="gudang_id"
                        class="form-control"
                        required>

                        @foreach($gudang as $g)

                            <option
                                value="{{ $g->id }}"
                                {{ $pengeluaran->gudang_id == $g->id ? 'selected' : '' }}>

                                {{ $g->nama }}

                            </option>

                        @endforeach

                    </select>

                </div>

                <hr>

                <h5>Detail Barang</h5>

                <table class="table table-bordered">

                    <thead>
                        <tr>
                            <th>Barang</th>
                            <th width="150">Qty</th>
                        </tr>
                    </thead>

                    <tbody>

                        @foreach($pengeluaran->details as $detail)

                            <tr>

                                <td>

                                    <select
                                        name="barang_id[]"
                                        class="form-control"
                                        required>

                                        @foreach($barang as $b)

                                            <option
                                                value="{{ $b->id }}"
                                                {{ $detail->barang_id == $b->id ? 'selected' : '' }}>

                                                {{ $b->nama }}

                                            </option>

                                        @endforeach

                                    </select>

                                </td>

                                <td>

                                    <input
                                        type="number"
                                        step="0.01"
                                        name="qty[]"
                                        value="{{ $detail->qty }}"
                                        class="form-control"
                                        required>

                                </td>

                            </tr>

                        @endforeach

                    </tbody>

                </table>

                <div class="mb-3">

                    <label>Keterangan</label>

                    <textarea
                        name="keterangan"
                        class="form-control"
                        rows="3">{{ $pengeluaran->keterangan }}</textarea>

                </div>

                <button class="btn btn-primary">
                    Update
                </button>

                <a
                    href="{{ route('pengeluaran-bahan-baku.index') }}"
                    class="btn btn-secondary">

                    Kembali

                </a>

            </form>

        </div>
    </div>

</div>

</x-app-layout>