<div class="mb-2"
     style="margin-right: {{ ($category->level - 1) * 30 }}px;">

    <div class="border rounded p-2 bg-white d-flex justify-content-between align-items-center">

        <div>

            {{ $category->children()->count() ? '📁' : '📄' }}

            {{ $category->category_name }}

            @if(!$category->is_active)
                <span class="badge bg-danger">
                    غير نشط
                </span>
            @endif

        </div>

        <div>
            @can('categories.create')
            <button
                class="btn btn-success btn-sm addChildCategory"
                data-id="{{ $category->id }}">

                + فرعي

            </button>
            @endcan

            @can('categories.edit')
            <button
                class="btn btn-primary btn-sm editCategory"
                data-id="{{ $category->id }}">

                تعديل

            </button>
            @endcan

            @can('categories.delete')
            <button
                class="btn btn-danger btn-sm deleteCategory"
                data-id="{{ $category->id }}">

                حذف

            </button>
            @endcan

        </div>

    </div>

    @foreach($category->childrenRecursive as $child)

        @include('categories.partials.category-node', [
            'category' => $child
        ])

    @endforeach

</div>