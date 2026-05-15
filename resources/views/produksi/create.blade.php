<x-app-layout>

@if ($errors->any())
    <div class="alert alert-danger">
        <ul>@foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach</ul>
    </div>
@endif
<div class="container">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">Input Produksi</h3>
    </div>

    <div class="card shadow-sm">

        <div class="card-header bg-primary text-white">
            Form Produksi
        </div>

        <div class="card-body">

            <form action="{{ route('produksi.store') }}"
                  method="POST">

                @csrf

                <div class="mb-3">

                    <label class="form-label">
                        Work Order
                    </label>

                    <select name="work_order_id"
                            class="form-control"
                            id="work_order_id"
                            required>

                        <option value="">
                            -- Pilih Work Order --
                        </option>

                        @foreach($workOrders as $wo)

                            <option value="{{ $wo->id }}">
                                {{ $wo->kode_wo }}
                            </option>

                        @endforeach

                    </select>

                </div>

                <div id="detail-wo">

                </div>

                <button type="submit"
                        class="btn btn-primary">

                    Simpan Produksi

                </button>

            </form>

        </div>

    </div>

</div>


<script>

// Bagian Script di create.blade.php
document.getElementById('work_order_id').addEventListener('change', function(){
    let id = this.value;
    let detailContainer = document.getElementById('detail-wo');
    
    if(!id) {
        detailContainer.innerHTML = '';
        return;
    }

    fetch('/produksi/get-wo-detail/' + id)
    .then(response => {
        if (!response.ok) throw new Error('Network response was not ok');
        return response.json();
    })
    .then(data => {
        if(data.length === 0) {
            detailContainer.innerHTML = '<div class="alert alert-warning">Tidak ada detail untuk WO ini.</div>';
            return;
        }

        let html = `
            <table class="table table-bordered mt-3">
                <thead class="table-light">
                    <tr>
                        <th>Produk</th>
                        <th>Qty Rencana</th>
                        <th width="200">Qty Hasil</th>
                    </tr>
                </thead>
                <tbody>`;

        data.forEach((item, index) => {
            html += `
                <tr>
                    <td>
                        ${item.produk.nama} <input type="hidden" name="produk_id[]" value="${item.produk_id}">
                    </td>
                    <td>${item.qty_rencana}</td>
                    <td>
                        <input type="number" name="qty_hasil[]" class="form-control" 
                               value="${item.qty_rencana}" min="1" required>
                    </td>
                </tr>`;
        });

        html += `</tbody></table>`;
        detailContainer.innerHTML = html;
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Gagal mengambil data detail WO');
    });
});

</script>

</x-app-layout>