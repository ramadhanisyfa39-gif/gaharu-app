<x-app-layout>

<div class="container">

<h3 class="mb-3">Edit Barang</h3>

<div class="card">
<div class="card-body">

<form action="{{ route('barang.update',$data->id) }}" method="POST">

@csrf
@method('PUT')

<div class="mb-3">

    <label>Kategori</label>

    <select name="kategori_id" class="form-control">

        @foreach($kategori as $k)

        <option value="{{ $k->id }}"
            {{ $data->kategori_id==$k->id?'selected':'' }}>

            {{ $k->nama }}

        </option>

        @endforeach

    </select>

</div>

<div class="row">

    <div class="col-md-6 mb-3">

        <label>Kode Barang</label>

        <input type="text"
               name="kode_barang"
               value="{{ $data->kode_barang }}"
               class="form-control"
               required>

    </div>

    <div class="col-md-6 mb-3">

        <label>Nama Barang</label>

        <input type="text"
               name="nama"
               value="{{ $data->nama }}"
               class="form-control"
               required>

    </div>

    <div class="col-md-6 mb-3">

        <label>Satuan</label>

        <input type="text"
               name="satuan"
               value="{{ $data->satuan }}"
               class="form-control">

    </div>

    <div class="col-md-6 mb-3">

        <label>Jenis Barang</label>

        <select name="jenis_utama"
                id="jenis"
                class="form-control"
                required>

            <option value="BAHAN_BAKU"
                {{ $data->jenis_utama=='BAHAN_BAKU'?'selected':'' }}>
                Bahan Baku
            </option>

            <option value="BARANG_JADI"
                {{ $data->jenis_utama=='BARANG_JADI'?'selected':'' }}>
                Barang Jadi
            </option>

            <option value="OPERATIONAL"
                {{ $data->jenis_utama=='OPERATIONAL'?'selected':'' }}>
                Operational
            </option>

        </select>

    </div>

    <div class="col-md-6 mb-3" id="group-min-stock">

        <label>Minimum Stock (Batas Kritis)</label>

        <input type="number"
               name="minimum_stock"
               id="minimum_stock"
               value="{{ $data->minimum_stock }}"
               class="form-control"
               min="0">

    </div>

</div>

<div class="mt-3">

    <button class="btn btn-primary">
        Update
    </button>

    <a href="{{ route('barang.index') }}"
       class="btn btn-secondary">
        Kembali
    </a>

</div>

</form>

</div>
</div>

</div>

<script>

document.addEventListener("DOMContentLoaded",function(){

    const jenis=document.getElementById("jenis");
    const group=document.getElementById("group-min-stock");
    const minimum=document.getElementById("minimum_stock");

    function toggle(){

        if(jenis.value==="BAHAN_BAKU"){

            group.style.display="block";

        }else{

            group.style.display="none";
            minimum.value="";

        }

    }

    jenis.addEventListener("change",toggle);

    toggle();

});

</script>

</x-app-layout>
