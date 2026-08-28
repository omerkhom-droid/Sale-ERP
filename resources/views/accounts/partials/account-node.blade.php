<div class="account-node mb-2" style="margin-right: {{ ($account->level - 1) * 28 }}px;">

    <div class="d-flex align-items-center justify-content-between border rounded p-2 bg-white">

        <div>
            <span class="fw-bold">
                {{ $account->is_group ? '📁' : '📄' }}
                {{ $account->account_code }}
                -
                {{ $account->account_name_ar }}
            </span>

            <span class="badge bg-secondary ms-2">
                {{ $account->account_type }}
            </span>

            <span class="badge {{ $account->is_active ? 'bg-success' : 'bg-danger' }}">
                {{ $account->is_active ? 'نشط' : 'غير نشط' }}
            </span>
        </div>

        <div class="d-flex gap-1">

            @if($account->is_group)
                <button type="button"
                        class="btn btn-sm btn-outline-success addChildAccount"
                        data-id="{{ $account->id }}"
                        data-type="{{ $account->account_type }}"
                        data-balance="{{ $account->normal_balance }}">
                    + فرعي
                </button>
            @endif

            <button type="button"
                    class="btn btn-sm btn-outline-primary editAccount"
                    data-id="{{ $account->id }}">
                تعديل
            </button>

            <button type="button"
                    class="btn btn-sm btn-outline-danger deleteAccount"
                    data-id="{{ $account->id }}">
                حذف
            </button>

        </div>

    </div>

    @if($account->childrenRecursive && $account->childrenRecursive->count())
        @foreach($account->childrenRecursive as $child)
            @include('accounts.partials.account-node', ['account' => $child])
        @endforeach
    @endif

</div>