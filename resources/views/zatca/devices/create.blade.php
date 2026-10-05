<x-app-layout>


<div class="container">

    <h4 class="mb-4">
        إضافة وحدة فوترة ZATCA
    </h4>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form
        method="POST"
        action="{{ route('zatca.devices.store') }}"
    >
        @csrf

        <div class="row">

            <div class="col-md-6 mb-3">
                <label class="form-label">
                    اسم الجهاز
                </label>

                <input
                    type="text"
                    name="device_name"
                    class="form-control"
                    value="{{ old('device_name') }}"
                    required
                >
            </div>

            <div class="col-md-6 mb-3">
                <label class="form-label">
                    Common Name
                </label>

                <input
                    type="text"
                    name="common_name"
                    class="form-control"
                    value="{{ old('common_name') }}"
                    required
                >
            </div>

            <div class="col-md-12 mb-3">
                <label class="form-label">
                    EGS Serial Number
                </label>

                <input
                    type="text"
                    name="egs_serial_number"
                    class="form-control"
                    value="{{ old(
                        'egs_serial_number',
                        '1-MyERP|2-1.0|3-' . strtoupper(uniqid())
                    ) }}"
                    required
                >

                <small class="text-muted">
                    مثال:
                    1-MyERP|2-1.0|3-DEVICE001
                </small>
            </div>

            <div class="col-md-6 mb-3">
                <label class="form-label">
                    الرقم الضريبي
                </label>

                <input
                    type="text"
                    name="organization_identifier"
                    class="form-control"
                    maxlength="15"
                    value="{{ old('organization_identifier') }}"
                    required
                >
            </div>

            <div class="col-md-6 mb-3">
                <label class="form-label">
                    اسم المنشأة
                </label>

                <input
                    type="text"
                    name="organization_name"
                    class="form-control"
                    value="{{ old('organization_name') }}"
                    required
                >
            </div>

            <div class="col-md-6 mb-3">
                <label class="form-label">
                    الفرع / Organization Unit
                </label>

                <input
                    type="text"
                    name="organization_unit_name"
                    class="form-control"
                    value="{{ old('organization_unit_name') }}"
                >
            </div>

            <div class="col-md-6 mb-3">

                <label class="form-label">
                    نوع الفواتير
                </label>

                <select
                    name="invoice_type"
                    class="form-select"
                    required
                >
                    <option value="1100">
                        قياسية + مبسطة
                    </option>

                    <option value="1000">
                        فواتير قياسية فقط
                    </option>

                    <option value="0100">
                        فواتير مبسطة فقط
                    </option>
                </select>

            </div>

            <div class="col-md-6 mb-3">
                <label class="form-label">
                    عنوان الفرع
                </label>

                <input
                    type="text"
                    name="registered_address"
                    class="form-control"
                    value="{{ old('registered_address') }}"
                    required
                >
            </div>

            <div class="col-md-6 mb-3">
                <label class="form-label">
                    النشاط التجاري
                </label>

                <input
                    type="text"
                    name="business_category"
                    class="form-control"
                    value="{{ old('business_category') }}"
                    required
                >
            </div>

        </div>

        <button
            type="submit"
            class="btn btn-primary"
        >
            حفظ وحدة ZATCA
        </button>

    </form>

</div>

</x-app-layout>