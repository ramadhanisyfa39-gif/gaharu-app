<x-app-layout>

<div class="container">

    <h3>Input Produksi</h3>

    @if(session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif

    <form action="{{ route('produksi.store') }}"
          method="POST">

        @csrf

        <div class="mb-3">

            <label>Work Order</label>

            <select name="work_order_id"
                    class="form-control"
                    id="work_order_id"
                    required>

                <option value="">
                    -- Pilih WO --
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

@endsection


@section('scripts')

<script>

$('#work_order_id').change(function(){

    let id = $(this).val();

    $.ajax({

        url: '/produksi/get-wo-detail/' + id,

        type: 'GET',

        success: function(data){

            let html = '';

            html += `
                <table class="table table-bordered">

                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th>Qty Rencana</th>
                            <th>Qty Hasil</th>
                        </tr>
                    </thead>

                    <tbody>
            `;

            data.forEach(function(item){

                html += `
                    <tr>

                        <td>

                            ${item.produk.nama_barang}

                            <input type="hidden"
                                   name="produk_id[]"
                                   value="${item.produk_id}">

                        </td>

                        <td>

                            ${item.qty_rencana}

                        </td>

                        <td>

                            <input type="number"
                                   name="qty_hasil[]"
                                   class="form-control"
                                   required>

                        </td>

                    </tr>
                `;
            });

            html += `
                    </tbody>
                </table>
            `;

            $('#detail-wo').html(html);

        }

    });

});

</script>

</x-app-layout>