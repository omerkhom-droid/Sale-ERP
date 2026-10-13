<x-app-layout>
<div class="container-fluid py-4" dir="rtl">
    <div class="page-header-card mb-4"><div><h3 class="page-title">{{ $title }}</h3><p class="page-subtitle">الفواتير والإشعارات المدينة والمرتجعات المرحلة حسب تاريخ السند — الربح دون ضريبة القيمة المضافة</p></div></div>
    <div class="d-flex flex-wrap gap-2 mb-3">
        @foreach(\App\Services\SalesAnalysisService::TITLES as $code => $label)
            <a class="btn btn-{{ $kind === $code ? 'primary' : 'outline-primary' }}" href="{{ route('sales-analysis.index', $code) }}">{{ $label }}</a>
        @endforeach
    </div>
    <div class="card shadow-sm mb-4"><div class="card-body">
        <form id="reportFilters" class="row g-3">
            @if($kind === 'monthly')
                <div class="col-md-2"><label class="form-label">السنة</label><input name="year" type="number" min="2000" max="2100" class="form-control" value="{{ $filters['year'] }}" required></div>
            @else
                <div class="col-md-2"><label class="form-label">من تاريخ</label><input name="date_from" type="date" class="form-control" value="{{ $filters['date_from'] }}" required></div>
                <div class="col-md-2"><label class="form-label">إلى تاريخ</label><input name="date_to" type="date" class="form-control" value="{{ $filters['date_to'] }}" required></div>
            @endif
            <div class="col-md-2"><label class="form-label">الفرع</label><select name="branch_id" class="form-select"><option value="">الفروع المسموحة</option>@foreach($branches as $b)<option value="{{ $b->id }}" @selected(($filters['branch_id'] ?? '') == $b->id)>{{ $b->branch_name }}</option>@endforeach</select></div>
            <div class="col-md-2"><label class="form-label">العميل</label><select name="customer_id" class="form-select"><option value="">الجميع</option><option value="cash" @selected(($filters['customer_id'] ?? '') === 'cash')>نقدي غير مسجل</option>@foreach($customers as $c)<option value="{{ $c->id }}" @selected(($filters['customer_id'] ?? '') == $c->id)>{{ $c->customer_name }}</option>@endforeach</select></div>
            @foreach(['user_id' => 'المستخدم المنشئ', 'seller_id' => 'البائع', 'representative_id' => 'المندوب'] as $field => $label)
                <div class="col-md-2"><label class="form-label">{{ $label }}</label><select name="{{ $field }}" class="form-select"><option value="">الجميع</option>@foreach($people as $p)<option value="{{ $p->id }}" @selected(($filters[$field] ?? '') == $p->id)>{{ $p->name }}</option>@endforeach</select></div>
            @endforeach
            <div class="col-md-2"><label class="form-label">الماركة</label><select name="brand_id" class="form-select"><option value="">الجميع</option>@foreach($brands as $b)<option value="{{ $b->id }}" @selected(($filters['brand_id'] ?? '') == $b->id)>{{ $b->brand_name }}</option>@endforeach</select></div>
            <div class="col-md-2"><label class="form-label">نوع السداد</label><select name="payment_type" class="form-select"><option value="">الجميع</option>@foreach(['cash' => 'نقدي', 'credit' => 'آجل', 'partial' => 'جزئي'] as $code => $label)<option value="{{ $code }}" @selected(($filters['payment_type'] ?? '') === $code)>{{ $label }}</option>@endforeach</select></div>
            <div class="col-md-2"><label class="form-label">طريقة الدفع</label><select name="payment_method" class="form-select"><option value="">الجميع</option>@foreach(['cash' => 'نقد', 'card' => 'بطاقة', 'bank_transfer' => 'تحويل', 'other' => 'أخرى'] as $code => $label)<option value="{{ $code }}" @selected(($filters['payment_method'] ?? '') === $code)>{{ $label }}</option>@endforeach</select></div>
            <div class="col-md-3 d-flex gap-3 align-items-end">
                <label><input type="checkbox" name="include_vat" value="1" @checked($filters['include_vat'])> مع الضريبة</label>
                <label><input type="checkbox" name="net_returns" value="1" @checked($filters['net_returns'])> بالصافي (خصم المرتجعات)</label>
            </div>
            <div class="col-12 d-flex gap-2 flex-wrap">
                <button class="btn btn-primary" type="submit">تنفيذ</button><button class="btn btn-outline-dark" id="printReport" type="button">طباعة كل النتائج</button><button class="btn btn-success" id="exportReport" type="button">تصدير Excel</button>
            </div>
        </form>
    </div></div>
    <div id="reportError" class="alert alert-danger d-none" role="alert"></div>
    <div class="row g-3 mb-4">
        @foreach(['net' => 'صافي المبيعات دون الضريبة', 'vat' => 'الضريبة', 'total' => 'الإجمالي مع الضريبة', 'cost' => 'التكلفة', 'profit' => 'الربح', 'percent' => 'نسبة الربح %'] as $key => $label)
            <div class="col-md-2 col-sm-4"><div class="card h-100"><div class="card-body"><small>{{ $label }}</small><h5 class="mt-2 mb-0" data-total="{{ $key }}">—</h5></div></div></div>
        @endforeach
    </div>
    <div class="card shadow-sm"><div class="card-body"><div class="table-responsive">
        <table id="analysisTable" class="table table-bordered table-striped w-100"><thead><tr><th>#</th>@foreach($columns as $label)<th>{{ $label }}</th>@endforeach @if($kind === 'staff')<th>الإجراءات</th>@endif</tr></thead><tbody></tbody></table>
    </div></div></div>
    <p class="text-muted mt-3">بطاقات الإجماليات والطباعة والتصدير تشمل كامل نتائج الفلاتر، ولا تتأثر ببحث الجدول أو الصفحة الحالية. الآجل يشمل السداد الجزئي. نسبة الربح = الربح ÷ صافي المبيعات دون الضريبة × 100. الماركة هي ماركة المنتج الحالية، والمستخدم هو منشئ الفاتورة الأصلية. العملاء النقديون غير المسجلين مجمعون في صف واحد.</p>
</div>
@can('sales_analysis.assign')
<div class="modal fade" id="staffModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog"><div class="modal-content" dir="rtl">
    <div class="modal-header"><h5 class="modal-title">تعيين البائع والمندوب</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button></div>
    <form id="staffForm">@csrf<div class="modal-body"><div id="staffError" class="alert alert-danger d-none"></div>
        @foreach(['seller_id' => 'البائع', 'representative_id' => 'المندوب'] as $field => $label)
        <label class="form-label">{{ $label }}</label><select name="{{ $field }}" class="form-select mb-3"><option value="">غير محدد</option>@foreach($people as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach</select>
        @endforeach
        <small>اختر مستخدماً نشطاً من شركة وفرع الفاتورة. التعيين مخصص للتقارير ولا يغيّر بيانات الفاتورة المالية.</small>
    </div><div class="modal-footer"><button type="submit" class="btn btn-primary">حفظ</button><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button></div></form>
</div></div></div>
@endcan
@push('scripts')
<script>
$(function () {
    const kind = @json($kind);
    const dataUrl = @json(route('sales-analysis.data', $kind));
    const printUrl = @json(route('sales-analysis.print', $kind));
    const exportUrl = @json(route('sales-analysis.export', $kind));
    const attributionUrl = @json(url('/sales-analysis/attributions'));
    const keys = @json(array_keys($columns));
    function filters() {
        const values = {};
        $('#reportFilters').serializeArray().forEach(v => values[v.name] = v.value);
        values.include_vat = $('#reportFilters [name=include_vat]').prop('checked') ? 1 : 0;
        values.net_returns = $('#reportFilters [name=net_returns]').prop('checked') ? 1 : 0;
        return values;
    }
    let applied = filters();
    const textRenderer = $.fn.dataTable.render.text();
    const columns = [{data: 'DT_RowIndex', orderable: false, searchable: false}];
    keys.forEach(key => columns.push({data: key, name: 'r.' + key, render: textRenderer,
        searchable: ['document_no', 'branch_name', 'user_name', 'seller_name', 'customer_label', 'brand_label'].includes(key)}));
    if (kind === 'staff') columns.push({data: 'actions', orderable: false, searchable: false});
    const table = $('#analysisTable').DataTable({
        processing: true, serverSide: true, pageLength: 25, searchDelay: 400, columns: columns,
        order: kind === 'staff' ? [[2, 'desc']] : [[1, 'asc']],
        language: {search:'بحث:', lengthMenu:'عرض _MENU_ صف', info:'عرض _START_ إلى _END_ من _TOTAL_', infoEmpty:'لا توجد نتائج', infoFiltered:'(من _MAX_ سجل)', zeroRecords:'لا توجد نتائج', emptyTable:'لا توجد بيانات', processing:'جاري التحميل...', paginate:{first:'الأول',last:'الأخير',next:'التالي',previous:'السابق'}},
        ajax: {url:dataUrl, data: d => Object.assign(d, applied), error: xhr => {
            $('#reportError').text(xhr.responseJSON?.message || 'تعذر تحميل التقرير. تأكد من تنفيذ Migration والصلاحيات.').removeClass('d-none');
            $('[data-total]').text('—');
            $('#analysisTable_processing').hide();
        }}
    });
    table.on('xhr.dt', function (_, settings, json) {
        if (!json?.totals) return;
        $('#reportError').addClass('d-none');
        Object.entries(json.totals).forEach(([key, value]) => $('[data-total="'+key+'"]').text(Number(value).toLocaleString('en-US', {minimumFractionDigits:2,maximumFractionDigits:2})));
    });
    $('#reportFilters').on('submit', function (event) {
        event.preventDefault(); applied = filters(); $('#reportError').addClass('d-none'); table.search('').ajax.reload();
    });
    $('#printReport').on('click', () => window.open(printUrl + '?' + $.param(applied), '_blank', 'noopener'));
    $('#exportReport').on('click', () => window.location.assign(exportUrl + '?' + $.param(applied)));
    let invoiceId = null;
    const modalElement = document.getElementById('staffModal');
    if (modalElement) {
        const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
        $('#analysisTable').on('click', '.assign-staff', function () {
            invoiceId = $(this).data('id'); $('#staffError').addClass('d-none');
            $.get(attributionUrl + '/' + invoiceId).done(data => {
                ['seller_id','representative_id'].forEach(key => $('#staffForm [name='+key+']').val(data[key] || ''));
                modal.show();
            }).fail(() => $('#reportError').text('تعذر تحميل بيانات البائع والمندوب.').removeClass('d-none'));
        });
        $('#staffForm').on('submit', function (event) {
            event.preventDefault(); const button = $(this).find('[type=submit]').prop('disabled', true);
            $.ajax({url:attributionUrl + '/' + invoiceId, method:'PATCH', data:$(this).serialize(), headers:{Accept:'application/json'}})
                .done(() => {modal.hide(); table.ajax.reload(null, false);})
                .fail(xhr => $('#staffError').text(Object.values(xhr.responseJSON?.errors || {}).flat().join(' ') || xhr.responseJSON?.message || 'تعذر حفظ التعيين.').removeClass('d-none'))
                .always(() => button.prop('disabled', false));
        });
    }
});
</script>
@endpush
</x-app-layout>
