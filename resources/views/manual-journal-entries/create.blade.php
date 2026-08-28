<x-app-layout>

@php
    $branchOptions = $branches->map(function ($branch) {
        return [
            'id' => $branch->id,
            'name' => $branch->branch_name_ar
                ?? $branch->branch_name
                ?? $branch->name
                ?? ('فرع رقم ' . $branch->id),
        ];
    })->values();

    $costCenterOptions = $costCenters->map(function ($costCenter) {
        $code = $costCenter->code ?? '';
        $name = $costCenter->name ?? ('مركز تكلفة رقم ' . $costCenter->id);

        return [
            'id' => $costCenter->id,
            'name' => trim($code . ' - ' . $name, ' -'),
        ];
    })->values();

    $accountOptions = $accounts->map(function ($account) {
        $code = $account->account_code ?? $account->code ?? '';
        $name = $account->account_name_ar
            ?? $account->account_name
            ?? $account->name
            ?? ('حساب رقم ' . $account->id);

        return [
            'id' => $account->id,
            'name' => trim($code . ' - ' . $name, ' -'),
        ];
    })->values();

    $customerOptions = $customers->map(function ($customer) {
        return [
            'id' => $customer->id,
            'name' => $customer->customer_name
                ?? $customer->name
                ?? ('عميل رقم ' . $customer->id),
        ];
    })->values();

    $supplierOptions = $suppliers->map(function ($supplier) {
        return [
            'id' => $supplier->id,
            'name' => $supplier->supplier_name
                ?? $supplier->name
                ?? ('مورد رقم ' . $supplier->id),
        ];
    })->values();
@endphp


<div class="container-fluid py-4" dir="rtl">

    {{-- Page Header --}}
    <div class="page-header-card mb-4">
        <div>
            <h3 class="page-title mb-1">إضافة قيد يومية</h3>
            <p class="page-subtitle mb-0">
                إنشاء قيد يومية يدوي كمسودة مع تحديد الحسابات أو العملاء أو الموردين ومراكز التكلفة.
            </p>
        </div>

        <a href="{{ route('manual-journal-entries.index') }}" class="btn btn-secondary">
            رجوع
        </a>
    </div>


    {{-- Alerts --}}
    @if(session('error'))
        <div class="alert alert-danger mb-4">
            {{ session('error') }}
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger mb-4">
            <strong>يوجد أخطاء في الإدخال:</strong>
            <ul class="mb-0 mt-2">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif


    <form method="POST" action="{{ route('manual-journal-entries.store') }}" id="journalForm">
        @csrf

        {{-- Document Info --}}
        <div class="card shadow-sm wazin-card mb-4">
            <div class="card-header wazin-card-header">
                <div>
                    <h5 class="mb-0 fw-bold">بيانات المستند</h5>
                    <small>تحديد تاريخ القيد والفرع الافتراضي وملاحظات المستند</small>
                </div>
            </div>

            <div class="card-body">

                <div class="row g-3">

                    <div class="col-md-3">
                        <label class="form-label">تاريخ القيد <span class="text-danger">*</span></label>
                        <input type="date"
                               name="manual_date"
                               class="form-control"
                               value="{{ old('manual_date', date('Y-m-d')) }}"
                               required>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">الفرع الافتراضي</label>
                        <select name="branch_id" class="form-select">
                            <option value="">بدون فرع</option>

                            @foreach($branches as $branch)
                                @php
                                    $branchName = $branch->branch_name_ar
                                        ?? $branch->branch_name
                                        ?? $branch->name
                                        ?? ('فرع رقم ' . $branch->id);
                                @endphp

                                <option value="{{ $branch->id }}" @selected(old('branch_id') == $branch->id)>
                                    {{ $branchName }}
                                </option>
                            @endforeach
                        </select>

                        <small class="field-hint">
                            يستخدم إذا تركت فرع السطر فارغًا.
                        </small>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">ملاحظات</label>
                        <input type="text"
                               name="notes"
                               class="form-control"
                               value="{{ old('notes') }}"
                               placeholder="مثال: قيد تسوية / تصحيح / نقل رصيد">
                    </div>

                </div>

            </div>
        </div>


        {{-- Lines --}}
        <div class="card shadow-sm wazin-card mb-4">
            <div class="card-header wazin-card-header">
                <div>
                    <h5 class="mb-0 fw-bold">سطور القيد</h5>
                    <small>يجب أن يتساوى إجمالي المدين مع إجمالي الدائن قبل الترحيل</small>
                </div>

                <button type="button" class="btn btn-primary" onclick="addLine()">
                    + إضافة سطر
                </button>
            </div>

            <div class="card-body">

                <div class="journal-note mb-4">
                    <div class="journal-note-icon">⚖️</div>
                    <div>
                        <strong>تنبيه</strong>
                        <p class="mb-0">
                            عند الترحيل يجب أن يساوي إجمالي المدين إجمالي الدائن، ولن يتم إنشاء قيد محاسبي غير متوازن.
                        </p>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover text-center align-middle wazin-table journal-lines-table">
                        <thead>
                            <tr>
                                <th style="width: 160px;">الفرع</th>
                                <th style="width: 180px;">مركز التكلفة</th>
                                <th style="width: 135px;">نوع السطر</th>
                                <th style="min-width: 280px;">الحساب / العميل / المورد</th>
                                <th style="width: 130px;">مدين</th>
                                <th style="width: 130px;">دائن</th>
                                <th style="min-width: 220px;">البيان</th>
                                <th style="width: 70px;">حذف</th>
                            </tr>
                        </thead>

                        <tbody id="linesBody"></tbody>

                        <tfoot>
                            <tr>
                                <td colspan="4">الإجمالي</td>
                                <td id="totalDebit" class="amount-cell">0.00</td>
                                <td id="totalCredit" class="amount-cell">0.00</td>
                                <td colspan="2">
                                    الفرق:
                                    <span id="difference" class="difference-value">0.00</span>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

            </div>
        </div>


        {{-- Actions --}}
        <div class="save-actions mb-5">
            <a href="{{ route('manual-journal-entries.index') }}" class="btn btn-secondary">
                إلغاء
            </a>

            <button type="submit" class="btn btn-primary btn-lg">
                حفظ كمسودة
            </button>
        </div>

    </form>

</div>


<style>
    .page-header-card {
        background: linear-gradient(135deg, #071633, #0A1730);
        color: #fff;
        border-radius: 22px;
        padding: 24px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        box-shadow: 0 16px 40px rgba(7, 22, 51, 0.16);
    }

    .page-title {
        color: #fff;
        font-weight: 900;
    }

    .page-subtitle {
        color: #CFEFF3;
        font-weight: 600;
        line-height: 1.8;
    }

    .wazin-card {
        border: 1px solid #E5E7EB;
        border-radius: 20px;
        overflow: hidden;
    }

    .wazin-card-header {
        background: #fff;
        border-bottom: 1px solid #E5E7EB;
        padding: 18px 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
    }

    .wazin-card-header h5 {
        color: #071633;
    }

    .wazin-card-header small {
        color: #8EA0B8;
        font-weight: 700;
        line-height: 1.8;
    }

    .wazin-card .card-body {
        background: #F8FAFC;
        padding: 22px;
    }

    .journal-note {
        background: #fff;
        border: 1px solid #E5E7EB;
        border-right: 5px solid #2F6BFF;
        border-radius: 18px;
        padding: 16px;
        display: flex;
        align-items: flex-start;
        gap: 14px;
        box-shadow: 0 8px 22px rgba(7, 22, 51, 0.04);
    }

    .journal-note-icon {
        width: 46px;
        height: 46px;
        border-radius: 16px;
        background: rgba(47, 107, 255, 0.10);
        color: #2F6BFF;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 22px;
        flex: 0 0 auto;
    }

    .journal-note strong {
        color: #071633;
        font-weight: 900;
        display: block;
        margin-bottom: 4px;
    }

    .journal-note p {
        color: #64748B;
        font-weight: 700;
        line-height: 1.8;
    }

    .form-label {
        color: #071633;
        font-weight: 900;
        margin-bottom: 7px;
    }

    .form-control,
    .form-select {
        border-radius: 14px;
        border: 1px solid #E5E7EB;
        min-height: 44px;
        font-weight: 600;
        background-color: #fff;
    }

    .form-control:focus,
    .form-select:focus {
        border-color: #2F6BFF;
        box-shadow: 0 0 0 .2rem rgba(47, 107, 255, .12);
    }

    .field-hint {
        display: block;
        color: #64748B !important;
        font-weight: 700;
        margin-top: 7px;
        line-height: 1.7;
    }

    .wazin-table {
        background: #fff;
        border-radius: 16px;
        overflow: hidden;
        margin-bottom: 0;
    }

    .wazin-table thead th {
        background: #071633 !important;
        color: #fff !important;
        border-color: #101F3C !important;
        font-weight: 900;
        white-space: nowrap;
        vertical-align: middle;
    }

    .wazin-table tbody td {
        vertical-align: middle;
        font-weight: 600;
        background: #fff;
    }

    .wazin-table tfoot td {
        background: #F1F5F9 !important;
        color: #071633;
        font-weight: 900;
        border-top: 2px solid #E5E7EB;
        vertical-align: middle;
    }

    .journal-lines-table .form-select,
    .journal-lines-table .form-control {
        min-height: 40px;
        border-radius: 12px;
        font-size: 13px;
        font-weight: 700;
    }

    .journal-lines-table .amount-input {
        direction: ltr;
        text-align: center !important;
        font-weight: 900;
    }

    .amount-cell,
    .difference-value {
        direction: ltr;
        display: inline-block;
        font-weight: 900;
    }

    .difference-balanced {
        color: #16A34A !important;
    }

    .difference-unbalanced {
        color: #E63B4A !important;
    }

    .btn-primary {
        background: #2F6BFF !important;
        border-color: #2F6BFF !important;
        font-weight: 900;
        border-radius: 14px;
        padding: 10px 18px;
    }

    .btn-primary:hover {
        background: #2559D9 !important;
        border-color: #2559D9 !important;
    }

    .btn-secondary {
        font-weight: 900;
        border-radius: 14px;
        padding: 10px 18px;
    }

    .btn-danger {
        background: #E63B4A !important;
        border-color: #E63B4A !important;
        color: #fff !important;
        font-weight: 900;
        border-radius: 12px;
    }

    .btn-danger:hover {
        background: #CC2F3D !important;
        border-color: #CC2F3D !important;
    }

    .save-actions {
        background: #fff;
        border: 1px solid #E5E7EB;
        border-radius: 20px;
        padding: 18px;
        display: flex;
        justify-content: flex-end;
        gap: 12px;
        box-shadow: 0 12px 32px rgba(7, 22, 51, 0.08);
        position: sticky;
        bottom: 18px;
        z-index: 20;
    }

    .save-actions .btn {
        min-width: 150px;
    }

    .alert {
        border-radius: 16px;
        font-weight: 700;
        line-height: 1.8;
    }

    @media (max-width: 767px) {
        .page-header-card,
        .wazin-card-header {
            flex-direction: column;
            align-items: stretch;
        }

        .page-header-card .btn,
        .wazin-card-header .btn {
            width: 100%;
        }

        .journal-note {
            flex-direction: column;
        }

        .save-actions {
            flex-direction: column;
            position: static;
        }

        .save-actions .btn {
            width: 100%;
        }
    }
</style>


@push('scripts')
<script>
    const branchOptions = @json($branchOptions);
    const costCenterOptions = @json($costCenterOptions);
    const accountOptions = @json($accountOptions);
    const customerOptions = @json($customerOptions);
    const supplierOptions = @json($supplierOptions);

    let lineIndex = 0;


    function escapeHtml(value) {
        return String(value ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }


    function optionsHtml(options, selected = '', placeholder = 'اختر') {
        let html = `<option value="">${escapeHtml(placeholder)}</option>`;

        options.forEach(function (item) {
            const isSelected = String(item.id) === String(selected) ? 'selected' : '';
            html += `<option value="${escapeHtml(item.id)}" ${isSelected}>${escapeHtml(item.name)}</option>`;
        });

        return html;
    }


    window.addLine = function (oldLine = null) {
        const index = lineIndex++;

        const lineType = oldLine?.line_type ?? 'account';

        const branchId = oldLine?.branch_id ?? '';
        const costCenterId = oldLine?.cost_center_id ?? '';

        const accountId = oldLine?.account_id ?? '';
        const customerId = oldLine?.customer_id ?? '';
        const supplierId = oldLine?.supplier_id ?? '';

        const debit = oldLine?.debit ?? '';
        const credit = oldLine?.credit ?? '';
        const description = oldLine?.description ?? '';

        const row = document.createElement('tr');

        row.innerHTML = `
            <td>
                <select name="lines[${index}][branch_id]" class="form-select">
                    ${optionsHtml(branchOptions, branchId, 'فرع المستند')}
                </select>
            </td>

            <td>
                <select name="lines[${index}][cost_center_id]" class="form-select">
                    ${optionsHtml(costCenterOptions, costCenterId, 'بدون مركز تكلفة')}
                </select>
            </td>

            <td>
                <select name="lines[${index}][line_type]"
                        class="form-select line-type"
                        onchange="changeLineType(this, true)"
                        required>
                    <option value="account" ${lineType === 'account' ? 'selected' : ''}>حساب</option>
                    <option value="customer" ${lineType === 'customer' ? 'selected' : ''}>عميل</option>
                    <option value="supplier" ${lineType === 'supplier' ? 'selected' : ''}>مورد</option>
                </select>
            </td>

            <td>
                <select name="lines[${index}][account_id]"
                        class="form-select account-select line-target">
                    ${optionsHtml(accountOptions, accountId)}
                </select>

                <select name="lines[${index}][customer_id]"
                        class="form-select customer-select line-target d-none">
                    ${optionsHtml(customerOptions, customerId)}
                </select>

                <select name="lines[${index}][supplier_id]"
                        class="form-select supplier-select line-target d-none">
                    ${optionsHtml(supplierOptions, supplierId)}
                </select>
            </td>

            <td>
                <input type="number"
                       step="0.01"
                       min="0"
                       name="lines[${index}][debit]"
                       class="form-control amount-input debit-input"
                       value="${escapeHtml(debit)}"
                       oninput="calculateTotals()">
            </td>

            <td>
                <input type="number"
                       step="0.01"
                       min="0"
                       name="lines[${index}][credit]"
                       class="form-control amount-input credit-input"
                       value="${escapeHtml(credit)}"
                       oninput="calculateTotals()">
            </td>

            <td>
                <input type="text"
                       name="lines[${index}][description]"
                       class="form-control"
                       value="${escapeHtml(description)}">
            </td>

            <td>
                <button type="button" class="btn btn-sm btn-danger" onclick="removeLine(this)">
                    ×
                </button>
            </td>
        `;

        document.getElementById('linesBody').appendChild(row);

        const typeSelect = row.querySelector('.line-type');

        changeLineType(typeSelect, false);

        calculateTotals();
    };


    window.changeLineType = function (select, clearValues = true) {
        const row = select.closest('tr');
        const type = select.value;

        row.querySelectorAll('.line-target').forEach(function (element) {
            element.classList.add('d-none');

            if (clearValues) {
                element.value = '';
            }
        });

        if (type === 'account') {
            row.querySelector('.account-select').classList.remove('d-none');
        }

        if (type === 'customer') {
            row.querySelector('.customer-select').classList.remove('d-none');
        }

        if (type === 'supplier') {
            row.querySelector('.supplier-select').classList.remove('d-none');
        }
    };


    window.removeLine = function (button) {
        button.closest('tr').remove();
        calculateTotals();
    };


    window.calculateTotals = function () {
        let totalDebit = 0;
        let totalCredit = 0;

        document.querySelectorAll('.debit-input').forEach(function (input) {
            totalDebit += parseFloat(input.value || 0);
        });

        document.querySelectorAll('.credit-input').forEach(function (input) {
            totalCredit += parseFloat(input.value || 0);
        });

        const difference = totalDebit - totalCredit;

        document.getElementById('totalDebit').innerText = totalDebit.toFixed(2);
        document.getElementById('totalCredit').innerText = totalCredit.toFixed(2);

        const differenceElement = document.getElementById('difference');
        differenceElement.innerText = difference.toFixed(2);

        differenceElement.classList.remove('difference-balanced', 'difference-unbalanced');

        if (Math.abs(difference) < 0.01) {
            differenceElement.classList.add('difference-balanced');
        } else {
            differenceElement.classList.add('difference-unbalanced');
        }
    };


    document.addEventListener('DOMContentLoaded', function () {
        const oldLinesRaw = @json(old('lines', []));
        const oldLines = Array.isArray(oldLinesRaw) ? oldLinesRaw : Object.values(oldLinesRaw);

        if (oldLines && oldLines.length > 0) {
            oldLines.forEach(function (line) {
                addLine(line);
            });
        } else {
            addLine();
        }
    });
</script>
@endpush

</x-app-layout>