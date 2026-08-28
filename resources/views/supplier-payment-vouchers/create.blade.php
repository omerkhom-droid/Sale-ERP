<x-app-layout>

<div class="container-fluid py-4" dir="rtl">

    <form id="supplier_payment_voucher_form">
        @csrf

        <input type="hidden" name="save_action" id="save_action" value="draft">

        <div class="card shadow-sm mb-3">

            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">

                <h5 class="mb-0">سند صرف مورد جديد</h5>

                <a href="{{ route('supplier-payment-vouchers.index') }}" class="btn btn-light btn-sm">
                    رجوع
                </a>

            </div>

            <div class="card-body">

                <div id="form_errors"></div>

                <div class="row g-3">

                    <div class="col-md-3">
                        <label class="form-label">رقم السند</label>
                        <input type="text"
                               name="voucher_no"
                               id="voucher_no"
                               class="form-control"
                               placeholder="اتركه فارغ للتوليد التلقائي">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">تاريخ السند <span class="text-danger">*</span></label>
                        <input type="date"
                               name="voucher_date"
                               id="voucher_date"
                               class="form-control"
                               value="{{ date('Y-m-d') }}"
                               required>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">المورد <span class="text-danger">*</span></label>
                        <select name="supplier_id" id="supplier_id" class="form-select" required>
                            <option value="">اختر المورد</option>
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id }}">
                                    {{ $supplier->supplier_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">مركز التكلفة</label>
                        <select name="cost_center_id" class="form-select">
                            <option value="">بدون مركز تكلفة</option>

                            @foreach($costCenters as $costCenter)
                                <option value="{{ $costCenter->id }}" @selected(old('cost_center_id') == $costCenter->id)>
                                    {{ $costCenter->code ? $costCenter->code . ' - ' : '' }}{{ $costCenter->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">حساب الدفع <span class="text-danger">*</span></label>
                        <select name="payment_account_id" id="payment_account_id" class="form-select" required>
                            <option value="">اختر حساب الدفع</option>
                            @foreach($paymentAccounts as $account)
                                <option value="{{ $account->id }}">
                                    {{ $account->account_code }} - {{ $account->account_name_ar }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">مبلغ السند <span class="text-danger">*</span></label>
                        <input type="number"
                               step="0.01"
                               min="0.01"
                               name="amount"
                               id="amount"
                               class="form-control"
                               value="0"
                               required>
                    </div>

                    <div class="col-md-7">
                        <label class="form-label">البيان / الملاحظات</label>
                        <input type="text"
                               name="notes"
                               id="notes"
                               class="form-control"
                               placeholder="مثال: سداد فواتير مشتريات للمورد">
                    </div>

                </div>

            </div>

        </div>


        <div class="card shadow-sm mb-3">

            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold">الفواتير المفتوحة للمورد</h6>

                <button type="button" id="autoDistributeBtn" class="btn btn-primary btn-sm">
                    توزيع تلقائي
                </button>
            </div>

            <div class="card-body">

                <div class="alert alert-info mb-3">
                    اختر المورد أولاً، ثم ستظهر الفواتير المرحلة التي عليها مبالغ متبقية.
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-striped text-center align-middle" id="openInvoicesTable">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>رقم الفاتورة</th>
                                <th>تاريخ الفاتورة</th>
                                <th>إجمالي الفاتورة</th>
                                <th>المدفوع</th>
                                <th>المتبقي</th>
                                <th>مبلغ السداد</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr>
                                <td colspan="7" class="text-muted">
                                    لم يتم اختيار مورد
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

            </div>

        </div>


        <div class="row g-3">

            <div class="col-md-8">
                <div class="alert alert-warning mb-0">
                    عند الترحيل سيتم إنشاء قيد محاسبي وتحديث حالة دفع الفواتير المرتبطة.
                </div>
            </div>

            <div class="col-md-4">

                <div class="card shadow-sm">
                    <div class="card-body">

                        <div class="d-flex justify-content-between mb-2">
                            <span>مبلغ السند</span>
                            <strong id="amountPreview">0.00</strong>
                        </div>

                        <div class="d-flex justify-content-between mb-2">
                            <span>إجمالي التوزيع</span>
                            <strong id="allocatedPreview">0.00</strong>
                        </div>

                        <hr>

                        <div class="d-flex justify-content-between fs-5">
                            <span>الفرق</span>
                            <strong id="differencePreview" class="text-danger">0.00</strong>
                        </div>

                    </div>
                </div>

                <button type="submit" id="saveDraftBtn" class="btn btn-secondary w-100 mt-3">
                    حفظ كمسودة
                </button>

                <button type="submit" id="savePostBtn" class="btn btn-success w-100 mt-2">
                    حفظ وترحيل
                </button>

            </div>

        </div>

    </form>

</div>


@push('scripts')
<script>
$(function () {

    let invoices = [];

    function formatNumber(value) {
        return parseFloat(value || 0).toFixed(2);
    }

    function renderInvoices() {

        let tbody = $('#openInvoicesTable tbody');
        tbody.html('');

        if (invoices.length === 0) {
            tbody.html(`
                <tr>
                    <td colspan="7" class="text-muted">
                        لا توجد فواتير مفتوحة لهذا المورد
                    </td>
                </tr>
            `);
            calculateTotals();
            return;
        }

        invoices.forEach(function (invoice, index) {

            tbody.append(`
                <tr>
                    <td>${index + 1}</td>

                    <td>
                        ${invoice.invoice_no}
                        <input type="hidden"
                               name="allocations[${index}][purchase_invoice_id]"
                               value="${invoice.id}">
                    </td>

                    <td>${invoice.invoice_date}</td>

                    <td>${formatNumber(invoice.total_amount)}</td>

                    <td>${formatNumber(invoice.paid_amount)}</td>

                    <td>
                        <span class="remainingAmount" data-remaining="${invoice.remaining_amount}">
                            ${formatNumber(invoice.remaining_amount)}
                        </span>
                    </td>

                    <td>
                        <input type="number"
                               step="0.01"
                               min="0"
                               max="${invoice.remaining_amount}"
                               name="allocations[${index}][amount]"
                               class="form-control allocationInput text-center"
                               value="0">
                    </td>
                </tr>
            `);

        });

        calculateTotals();
    }

    function calculateTotals() {

        let amount = parseFloat($('#amount').val()) || 0;
        let allocated = 0;

        $('.allocationInput').each(function () {
            let input = $(this);
            let row = input.closest('tr');

            let value = parseFloat(input.val()) || 0;
            let remaining = parseFloat(row.find('.remainingAmount').data('remaining')) || 0;

            if (value > remaining) {
                value = remaining;
                input.val(formatNumber(value));
            }

            allocated += value;
        });

        let difference = amount - allocated;

        $('#amountPreview').text(formatNumber(amount));
        $('#allocatedPreview').text(formatNumber(allocated));
        $('#differencePreview').text(formatNumber(difference));

        if (difference == 0) {
            $('#differencePreview')
                .removeClass('text-danger')
                .addClass('text-success');
        } else {
            $('#differencePreview')
                .removeClass('text-success')
                .addClass('text-danger');
        }
    }

    $('#supplier_id').on('change', function () {

        let supplierId = $(this).val();

        invoices = [];
        $('#openInvoicesTable tbody').html(`
            <tr>
                <td colspan="7" class="text-muted">
                    جاري تحميل الفواتير...
                </td>
            </tr>
        `);

        if (!supplierId) {
            invoices = [];
            renderInvoices();
            return;
        }

        $.get("{{ url('/supplier-payment-vouchers/open-invoices') }}/" + supplierId, function (data) {
            invoices = data;
            renderInvoices();
        });

    });

    $('#autoDistributeBtn').on('click', function () {

        let amount = parseFloat($('#amount').val()) || 0;

        if (amount <= 0) {
            Swal.fire({
                icon: 'warning',
                title: 'تنبيه',
                text: 'أدخل مبلغ السند أولاً'
            });
            return;
        }

        let remainingToAllocate = amount;

        $('.allocationInput').each(function () {

            let input = $(this);
            let row = input.closest('tr');
            let invoiceRemaining = parseFloat(row.find('.remainingAmount').data('remaining')) || 0;

            let value = 0;

            if (remainingToAllocate > 0) {
                value = Math.min(remainingToAllocate, invoiceRemaining);
            }

            input.val(formatNumber(value));
            remainingToAllocate -= value;

        });

        calculateTotals();
    });

    $(document).on('input', '#amount, .allocationInput', function () {
        calculateTotals();
    });

    $('#saveDraftBtn').on('click', function () {
        $('#save_action').val('draft');
    });

    $('#savePostBtn').on('click', function () {
        $('#save_action').val('post');
    });

    $('#supplier_payment_voucher_form').on('submit', function (e) {
        e.preventDefault();

        $('#saveDraftBtn, #savePostBtn').prop('disabled', true);
        $('#form_errors').html('');

        $.ajax({
            url: "{{ route('supplier-payment-vouchers.store') }}",
            type: "POST",
            data: $(this).serialize(),

            success: function (response) {
                Swal.fire({
                    icon: 'success',
                    title: 'تم',
                    text: response.message,
                    timer: 1200,
                    showConfirmButton: false
                });

                setTimeout(function () {
                    window.location.href = "{{ url('/supplier-payment-vouchers') }}/" + response.voucher_id;
                }, 900);
            },

            error: function (xhr) {
                $('#saveDraftBtn, #savePostBtn').prop('disabled', false);

                if (xhr.status === 422) {
                    let errors = xhr.responseJSON.errors;
                    let html = '<div class="alert alert-danger"><ul class="mb-0">';

                    $.each(errors, function (key, value) {
                        html += `<li>${value[0]}</li>`;
                    });

                    html += '</ul></div>';
                    $('#form_errors').html(html);

                    window.scrollTo({
                        top: 0,
                        behavior: 'smooth'
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'خطأ',
                        text: 'حدث خطأ غير متوقع'
                    });
                }
            }
        });
    });

});
</script>
@endpush

</x-app-layout>