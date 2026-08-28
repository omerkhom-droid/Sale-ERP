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

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">إضافة أرصدة افتتاحية</h4>

        <a href="{{ route('opening-balances.index') }}" class="btn btn-secondary">
            رجوع
        </a>
    </div>

    @if(session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <strong>يوجد أخطاء في الإدخال:</strong>
            <ul class="mb-0 mt-2">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('opening-balances.store') }}">
        @csrf

        <div class="card shadow-sm mb-3">
            <div class="card-header bg-light">
                <strong>بيانات المستند</strong>
            </div>

            <div class="card-body">
                <div class="row g-3">

                    <div class="col-md-3">
                        <label class="form-label">تاريخ الرصيد الافتتاحي</label>
                        <input type="date"
                               name="opening_date"
                               class="form-control"
                               value="{{ old('opening_date', date('Y-m-d')) }}"
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

                        <small class="text-muted">
                            يستخدم إذا تركت فرع السطر فارغًا.
                        </small>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">ملاحظات</label>
                        <input type="text"
                               name="notes"
                               class="form-control"
                               value="{{ old('notes') }}"
                               placeholder="مثال: أرصدة افتتاحية عند بداية استخدام النظام">
                    </div>

                </div>
            </div>
        </div>


        <div class="card shadow-sm">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <strong>السطور</strong>

                <button type="button" class="btn btn-sm btn-primary" onclick="addLine()">
                    + إضافة سطر
                </button>
            </div>

            <div class="card-body">

                <div class="table-responsive">
                    <table class="table table-bordered text-center align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th style="width: 160px;">الفرع</th>
                                <th style="width: 180px;">مركز التكلفة</th>
                                <th style="width: 135px;">نوع السطر</th>
                                <th style="min-width: 260px;">الحساب / العميل / المورد</th>
                                <th style="width: 130px;">مدين</th>
                                <th style="width: 130px;">دائن</th>
                                <th style="min-width: 200px;">البيان</th>
                                <th style="width: 70px;">حذف</th>
                            </tr>
                        </thead>

                        <tbody id="linesBody"></tbody>

                        <tfoot class="table-light fw-bold">
                            <tr>
                                <td colspan="4">الإجمالي</td>
                                <td id="totalDebit">0.00</td>
                                <td id="totalCredit">0.00</td>
                                <td colspan="2">
                                    الفرق:
                                    <span id="difference">0.00</span>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="alert alert-info mb-0">
                    <strong>ملاحظة:</strong>
                    إذا كان إجمالي المدين لا يساوي إجمالي الدائن، سيقوم النظام تلقائيًا بإضافة الفرق على حساب
                    <strong>أرصدة افتتاحية</strong>
                    عند الترحيل.
                </div>

            </div>
        </div>


        <div class="mt-3 d-flex gap-2">
            <button type="submit" class="btn btn-success">
                حفظ كمسودة
            </button>

            <a href="{{ route('opening-balances.index') }}" class="btn btn-secondary">
                إلغاء
            </a>
        </div>

    </form>

</div>


<script>
    const branchOptions = @json($branchOptions);
    const costCenterOptions = @json($costCenterOptions);
    const accountOptions = @json($accountOptions);
    const customerOptions = @json($customerOptions);
    const supplierOptions = @json($supplierOptions);

    let lineIndex = 0;

    function optionsHtml(options, selected = '', placeholder = 'اختر') {
        let html = `<option value="">${placeholder}</option>`;

        options.forEach(function (item) {
            const isSelected = String(item.id) === String(selected) ? 'selected' : '';
            html += `<option value="${item.id}" ${isSelected}>${item.name}</option>`;
        });

        return html;
    }

    function addLine(oldLine = null) {
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
                        onchange="changeLineType(this)"
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
                       class="form-control text-end debit-input"
                       value="${debit}"
                       oninput="calculateTotals()">
            </td>

            <td>
                <input type="number"
                       step="0.01"
                       min="0"
                       name="lines[${index}][credit]"
                       class="form-control text-end credit-input"
                       value="${credit}"
                       oninput="calculateTotals()">
            </td>

            <td>
                <input type="text"
                       name="lines[${index}][description]"
                       class="form-control"
                       value="${description}">
            </td>

            <td>
                <button type="button" class="btn btn-sm btn-danger" onclick="removeLine(this)">
                    ×
                </button>
            </td>
        `;

        document.getElementById('linesBody').appendChild(row);

        const typeSelect = row.querySelector('.line-type');
        changeLineType(typeSelect);

        calculateTotals();
    }

    function changeLineType(select) {
        const row = select.closest('tr');
        const type = select.value;

        row.querySelectorAll('.line-target').forEach(function (element) {
            element.classList.add('d-none');
            element.value = '';
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
    }

    function removeLine(button) {
        button.closest('tr').remove();
        calculateTotals();
    }

    function calculateTotals() {
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
        document.getElementById('difference').innerText = difference.toFixed(2);
    }

    const oldLines = @json(old('lines', []));

    if (oldLines && oldLines.length > 0) {
        oldLines.forEach(function (line) {
            addLine(line);
        });
    } else {
        addLine();
    }
</script>

</x-app-layout>