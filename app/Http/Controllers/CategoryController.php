<?php

namespace App\Http\Controllers;
use App\Support\BranchScope;
use App\Models\Category;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::with('childrenRecursive')
            ->whereNull('parent_id')
            ->orderBy('category_name')
            ->get();

        return view('categories.index', compact('categories'));
    }

    public function store(StoreCategoryRequest $request)
    {
        $data = $request->validated();

        if (!empty($data['parent_id'])) {
            $parent = Category::findOrFail($data['parent_id']);
            $data['level'] = $parent->level + 1;
        } else {
            $data['level'] = 1;
        }

        Category::create($data);

        return response()->json([
            'status' => true,
            'message' => 'تم إضافة التصنيف بنجاح'
        ]);
    }

    public function edit(Category $category)
    {
        return response()->json($category);
    }

    public function update(UpdateCategoryRequest $request, Category $category)
    {
        $data = $request->validated();

        if (!empty($data['parent_id'])) {
            $parent = Category::findOrFail($data['parent_id']);
            $data['level'] = $parent->level + 1;
        } else {
            $data['level'] = 1;
        }

        $category->update($data);

        return response()->json([
            'status' => true,
            'message' => 'تم تعديل التصنيف بنجاح'
        ]);
    }

    public function destroy(Category $category)
    {
        if ($category->children()->exists()) {
            return response()->json([
                'status' => false,
                'message' => 'لا يمكن حذف تصنيف يحتوي على تصنيفات فرعية'
            ], 422);
        }

        $category->delete();

        return response()->json([
            'status' => true,
            'message' => 'تم حذف التصنيف بنجاح'
        ]);
    }
}