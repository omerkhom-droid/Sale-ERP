<?php

namespace App\Http\Controllers;

use App\Models\CostCenter;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Yajra\DataTables\Facades\DataTables;

class CostCenterController extends Controller
{
    private function actorIsSuperAdmin(): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        return (bool) ($user->is_super_admin ?? false)
            || (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin())
            || $user->hasRole('Super Admin');
    }

    private function applyCompanyScope($query)
    {
        $user = auth()->user();

        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        if (
            ! $this->actorIsSuperAdmin()
            && Schema::hasColumn('cost_centers', 'company_id')
            && ! empty($user->company_id)
        ) {
            $query->where('company_id', $user->company_id);
        }

        return $query;
    }

    private function assertCostCenterAccess(CostCenter $costCenter): void
    {
        $user = auth()->user();

        if (! $user) {
            abort(403);
        }

        if ($this->actorIsSuperAdmin()) {
            return;
        }

        if (
            Schema::hasColumn('cost_centers', 'company_id')
            && ! empty($user->company_id)
            && (int) $costCenter->company_id !== (int) $user->company_id
        ) {
            abort(403, 'لا تملك صلاحية الوصول إلى مركز تكلفة من شركة أخرى.');
        }
    }

    private function assertParentAllowed(?int $parentId, ?int $currentId = null): void
    {
        if (! $parentId) {
            return;
        }

        if ($currentId && (int) $parentId === (int) $currentId) {
            abort(422, 'لا يمكن أن يكون مركز التكلفة تابعًا لنفسه.');
        }

        $query = CostCenter::whereKey($parentId);

        $this->applyCompanyScope($query);

        $parent = $query->first();

        abort_unless($parent, 403, 'لا تملك صلاحية استخدام مركز التكلفة الأب.');

        if ($currentId) {
            $this->assertNotCircularParent($parent, $currentId);
        }
    }

    private function assertNotCircularParent(CostCenter $parent, int $currentId): void
    {
        $currentParent = $parent;

        while ($currentParent) {
            if ((int) $currentParent->id === (int) $currentId) {
                abort(422, 'لا يمكن اختيار مركز تكلفة فرعي كأب لهذا المركز.');
            }

            $currentParent = $currentParent->parent;
        }
    }

    public function index()
    {
        abort_unless(auth()->user()?->can('cost_centers.view'), 403);

        $parents = $this->applyCompanyScope(
                CostCenter::query()
            )
            ->orderBy('code')
            ->orderBy('name')
            ->get();

        return view('cost-centers.index', compact('parents'));
    }

    public function fetch(Request $request)
    {
        abort_unless(auth()->user()?->can('cost_centers.view'), 403);

        if (! $request->ajax()) {
            abort(404);
        }

        $user = auth()->user();

        $costCenters = $this->applyCompanyScope(
                CostCenter::query()->with('parent')
            )
            ->latest();

        return DataTables::of($costCenters)
            ->addIndexColumn()

            ->addColumn('parent_name', function ($row) {
                return e($row->parent?->name ?? '-');
            })

            ->addColumn('status', function ($row) {
                return $row->is_active
                    ? '<span class="badge bg-success">نشط</span>'
                    : '<span class="badge bg-danger">غير نشط</span>';
            })

            ->addColumn('actions', function ($row) use ($user) {
                $buttons = '<div class="d-flex justify-content-center gap-1 flex-wrap">';

                if ($user && $user->can('cost_centers.edit')) {
                    $buttons .= '
                        <button type="button"
                                class="btn btn-sm btn-warning edit-btn"
                                data-id="' . $row->id . '"
                                data-code="' . e($row->code) . '"
                                data-name="' . e($row->name) . '"
                                data-parent-id="' . e($row->parent_id) . '"
                                data-is-active="' . (int) $row->is_active . '"
                                data-notes="' . e($row->notes) . '">
                            تعديل
                        </button>
                    ';
                }

                if ($user && $user->can('cost_centers.delete')) {
                    $buttons .= '
                        <button type="button"
                                class="btn btn-sm btn-danger delete-btn"
                                data-id="' . $row->id . '">
                            حذف
                        </button>
                    ';
                }

                $buttons .= '</div>';

                return $buttons === '<div class="d-flex justify-content-center gap-1 flex-wrap"></div>'
                    ? '<span class="text-muted">-</span>'
                    : $buttons;
            })

            ->rawColumns(['status', 'actions'])
            ->make(true);
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()?->can('cost_centers.create'), 403);

        $data = $request->validate([
            'code' => ['nullable', 'string', 'max:50', 'unique:cost_centers,code'],
            'name' => ['required', 'string', 'max:255'],
            'parent_id' => ['nullable', 'integer'],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ]);

        $this->assertParentAllowed((int) ($data['parent_id'] ?? 0));

        $data['is_active'] = $request->boolean('is_active');

        if (
            Schema::hasColumn('cost_centers', 'company_id')
            && ! empty(auth()->user()?->company_id)
        ) {
            $data['company_id'] = auth()->user()->company_id;
        }

        CostCenter::create($data);

        return response()->json([
            'status' => true,
            'message' => 'تم إضافة مركز التكلفة بنجاح.',
        ]);
    }

    public function update(Request $request, CostCenter $costCenter)
    {
        abort_unless(auth()->user()?->can('cost_centers.edit'), 403);

        $this->assertCostCenterAccess($costCenter);

        $data = $request->validate([
            'code' => ['nullable', 'string', 'max:50', 'unique:cost_centers,code,' . $costCenter->id],
            'name' => ['required', 'string', 'max:255'],
            'parent_id' => ['nullable', 'integer'],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ]);

        $this->assertParentAllowed(
            (int) ($data['parent_id'] ?? 0),
            (int) $costCenter->id
        );

        $data['is_active'] = $request->boolean('is_active');

        unset($data['company_id']);

        $costCenter->update($data);

        return response()->json([
            'status' => true,
            'message' => 'تم تعديل مركز التكلفة بنجاح.',
        ]);
    }

    public function destroy(CostCenter $costCenter)
    {
        abort_unless(auth()->user()?->can('cost_centers.delete'), 403);

        $this->assertCostCenterAccess($costCenter);

        try {
            if ($costCenter->children()->exists()) {
                return response()->json([
                    'status' => false,
                    'message' => 'لا يمكن حذف مركز تكلفة لديه مراكز فرعية.',
                ], 422);
            }

            $costCenter->delete();

            return response()->json([
                'status' => true,
                'message' => 'تم حذف مركز التكلفة بنجاح.',
            ]);

        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'لا يمكن حذف مركز التكلفة لأنه مرتبط بعمليات أخرى.',
            ], 422);
        }
    }
}