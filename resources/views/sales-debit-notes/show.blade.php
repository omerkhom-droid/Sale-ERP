<x-app-layout>
@include('sales-debit-notes._styles')
<div class="container-fluid py-4 wazin-note" dir="rtl">
    <div class="wn-hero">
        <div><h3>عرض إشعار مدين</h3><p>مراجعة بيانات الإشعار والأصناف وحالة الترحيل.</p></div>
        <div class="wn-actions">
            @can('sales_debit_notes.print')<a href="{{ route('sales-debit-notes.print',$note) }}" target="_blank" rel="noopener" class="btn btn-primary">طباعة داخلية</a>@endcan
            <a href="{{ route('sales-debit-notes.index') }}" class="btn wn-back">رجوع للقائمة</a>
        </div>
    </div>
    @include('sales-debit-notes._alerts')
    @include('sales-debit-notes._details')
    @if($note->status === 'draft')
        @can('sales_debit_notes.post')
            <div class="wn-card"><div class="wn-body wn-footer">
                <div><strong>الإشعار محفوظ كمسودة</strong><div class="wn-muted">الترحيل يحدّث المديونية والقيود، والمخزون عند زيادة كمية صنف مخزني.</div></div>
                <form method="POST" action="{{ route('sales-debit-notes.post',$note) }}" onsubmit="return confirm('هل تريد ترحيل الإشعار؟');">@csrf<button class="btn btn-primary" type="submit">ترحيل الإشعار</button></form>
            </div></div>
        @endcan
    @endif
    @if($note->status !== 'cancelled')
        @can('sales_debit_notes.cancel')
            <section class="wn-card wn-cancel-box">
                <div class="wn-heading"><div><h5>إلغاء الإشعار</h5><small>يُعكس أثر الإشعار المرحّل عند السماح بالإلغاء.</small></div></div>
                <form class="wn-body" method="POST" action="{{ route('sales-debit-notes.cancel',$note) }}" onsubmit="return confirm('هل أنت متأكد من إلغاء الإشعار؟');">
                    @csrf
                    <label class="form-label" for="cancel-reason">سبب الإلغاء <span class="text-danger">*</span></label>
                    <textarea class="form-control mb-3" id="cancel-reason" name="cancel_reason" rows="3" maxlength="2000" required>{{ old('cancel_reason') }}</textarea>
                    <button type="submit" class="btn btn-danger">إلغاء الإشعار</button>
                </form>
            </section>
        @endcan
    @endif
</div>
</x-app-layout>
