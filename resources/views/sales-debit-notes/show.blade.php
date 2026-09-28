<x-app-layout>
<div class="container py-4" dir="rtl">
    <div class="d-flex justify-content-between mb-3"><h3>إشعار مدين</h3><a href="{{ route('sales-debit-notes.index') }}" class="btn btn-secondary">رجوع</a></div>
    @include('sales-debit-notes._alerts')
    <div class="card"><div class="card-body">@include('sales-debit-notes._details')</div></div>
    <div class="d-flex gap-2 my-3">
        @can('sales_debit_notes.print')<a href="{{ route('sales-debit-notes.print',$note) }}" target="_blank" class="btn btn-dark">طباعة داخلية</a>@endcan
        @if($note->status === 'draft')
            @can('sales_debit_notes.post')<form method="POST" action="{{ route('sales-debit-notes.post',$note) }}" onsubmit="return confirm('ترحيل الإشعار وتحديث المديونية والمخزون؟')">@csrf<button class="btn btn-primary">ترحيل</button></form>@endcan
        @endif
    </div>
    @if($note->status !== 'cancelled')
        @can('sales_debit_notes.cancel')
            <form method="POST" action="{{ route('sales-debit-notes.cancel',$note) }}" onsubmit="return confirm('هل تريد إلغاء الإشعار؟')">@csrf
                <label for="cancel_reason" class="form-label">سبب الإلغاء</label><textarea id="cancel_reason" class="form-control mb-2" name="cancel_reason" maxlength="2000" required></textarea><button class="btn btn-outline-danger">إلغاء الإشعار</button>
            </form>
        @endcan
    @endif
</div>
</x-app-layout>
