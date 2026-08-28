<div class="table-responsive">

    <table class="table table-bordered table-hover align-middle" id="unitsTable">

        <thead class="table-light">
            <tr class="text-center">
                <th width="220">الوحدة</th>
                <th width="90">افتراضي</th>
                <th width="100">المعامل</th>
                <th width="120">شراء</th>
                <th width="120">بيع</th>
                <th width="130">أقل بيع</th>
                <th width="180">الباركود</th>
                <th width="70">
                    <button type="button"
                            id="addUnit"
                            class="btn btn-success btn-sm">
                        +
                    </button>
                </th>
            </tr>
        </thead>

        <tbody>

        @if(isset($product) && $product->exists)

            @foreach($product->units as $i => $row)

                <tr>

                    <td>
                        <input type="hidden"
                               name="units[{{ $i }}][id]"
                               value="{{ $row->id }}">

                        <select class="form-select unit-select"
                                name="units[{{ $i }}][unit_id]">

                            <option value="">اختر</option>

                            @foreach($units as $unit)
                                <option value="{{ $unit->id }}"
                                        @selected((int) $row->unit_id === (int) $unit->id)>
                                    {{ $unit->unit_name }}
                                </option>
                            @endforeach

                        </select>
                    </td>

                    <td class="text-center">
                        <input type="radio"
                               class="form-check-input defaultUnit"
                               {{ $row->is_default ? 'checked' : '' }}>

                        <input type="hidden"
                               class="defaultValue"
                               name="units[{{ $i }}][is_default]"
                               value="{{ $row->is_default ? 1 : 0 }}">
                    </td>

                    <td>
                        <input type="number"
                               class="form-control"
                               step="0.001"
                               name="units[{{ $i }}][factor]"
                               value="{{ $row->factor }}">
                    </td>

                    <td>
                        <input type="number"
                               class="form-control"
                               step="0.01"
                               name="units[{{ $i }}][purchase_price]"
                               value="{{ $row->purchase_price }}">
                    </td>

                    <td>
                        <input type="number"
                               class="form-control"
                               step="0.01"
                               name="units[{{ $i }}][sale_price]"
                               value="{{ $row->sale_price }}">
                    </td>

                    <td>
                        <input type="number"
                               class="form-control"
                               step="0.01"
                               name="units[{{ $i }}][minimum_sale_price]"
                               value="{{ $row->minimum_sale_price }}">
                    </td>

                    <td>
                        <input type="text"
                               class="form-control"
                               name="units[{{ $i }}][barcode]"
                               value="{{ optional($row->barcode)->barcode }}">
                    </td>

                    <td class="text-center">
                        <button type="button"
                                class="btn btn-danger btn-sm removeRow">
                            ×
                        </button>
                    </td>

                </tr>

            @endforeach

        @endif

        </tbody>

    </table>

</div>


<template id="unitRowTemplate">

    <tr>

        <td>
            <input type="hidden"
                   name="units[{index}][id]"
                   value="">

            <select class="form-select unit-select"
                    name="units[{index}][unit_id]">

                <option value="">اختر</option>

                @foreach($units as $unit)
                    <option value="{{ $unit->id }}">
                        {{ $unit->unit_name }}
                    </option>
                @endforeach

            </select>
        </td>

        <td class="text-center">
            <input type="radio"
                   class="form-check-input defaultUnit">

            <input type="hidden"
                   class="defaultValue"
                   name="units[{index}][is_default]"
                   value="0">
        </td>

        <td>
            <input type="number"
                   step="0.001"
                   value="1"
                   class="form-control"
                   name="units[{index}][factor]">
        </td>

        <td>
            <input type="number"
                   step="0.01"
                   value="0"
                   class="form-control"
                   name="units[{index}][purchase_price]">
        </td>

        <td>
            <input type="number"
                   step="0.01"
                   value="0"
                   class="form-control"
                   name="units[{index}][sale_price]">
        </td>

        <td>
            <input type="number"
                   step="0.01"
                   value="0"
                   class="form-control"
                   name="units[{index}][minimum_sale_price]">
        </td>

        <td>
            <input type="text"
                   class="form-control"
                   name="units[{index}][barcode]">
        </td>

        <td class="text-center">
            <button type="button"
                    class="btn btn-danger btn-sm removeRow">
                ×
            </button>
        </td>

    </tr>

</template>