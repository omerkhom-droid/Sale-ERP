<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Http\Requests\StoreAccountRequest;
use App\Http\Requests\UpdateAccountRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AccountController extends Controller
{
    public function index()
    {
        $accounts = Account::with('childrenRecursive')
            ->whereNull('parent_id')
            ->orderBy('account_code')
            ->get();

        return view('accounts.index', compact('accounts'));
    }


    public function generateCode(Request $request)
    {
        $request->validate([
            'parent_id' => ['nullable', 'exists:accounts,id'],
            'account_type' => ['required_without:parent_id', 'nullable', Rule::in([
                'asset',
                'liability',
                'equity',
                'revenue',
                'expense',
            ])],
        ]);

        $code = $this->generateAccountCode(
            $request->filled('parent_id') ? (int) $request->parent_id : null,
            $request->account_type
        );

        return response()->json([
            'status' => true,
            'account_code' => $code,
        ]);
    }


    public function store(StoreAccountRequest $request)
    {
        $data = $request->validated();

        if (!empty($data['parent_id'])) {
            $parent = Account::findOrFail($data['parent_id']);

            $data['level'] = $parent->level + 1;
            $data['account_type'] = $parent->account_type;
            $data['normal_balance'] = $parent->normal_balance;
        } else {
            $data['level'] = 1;
        }

        if (empty($data['account_code'])) {
            $data['account_code'] = $this->generateAccountCode(
                !empty($data['parent_id']) ? (int) $data['parent_id'] : null,
                $data['account_type'] ?? null
            );
        }

        Account::create($data);

        return response()->json([
            'status' => true,
            'message' => 'تم إضافة الحساب بنجاح',
        ]);
    }


    public function edit(Account $account)
    {
        return response()->json($account);
    }


    public function update(UpdateAccountRequest $request, Account $account)
    {
        $data = $request->validated();

        if (!empty($data['parent_id'])) {
            $parent = Account::findOrFail($data['parent_id']);

            $data['level'] = $parent->level + 1;
            $data['account_type'] = $parent->account_type;
            $data['normal_balance'] = $parent->normal_balance;
        } else {
            $data['level'] = 1;
        }

        if (empty($data['account_code'])) {
            $data['account_code'] = $account->account_code;
        }

        $account->update($data);

        return response()->json([
            'status' => true,
            'message' => 'تم تعديل الحساب بنجاح',
        ]);
    }


    public function destroy(Account $account)
    {
        if ($account->children()->exists()) {
            return response()->json([
                'status' => false,
                'message' => 'لا يمكن حذف حساب يحتوي على حسابات فرعية',
            ], 422);
        }

        $account->delete();

        return response()->json([
            'status' => true,
            'message' => 'تم حذف الحساب بنجاح',
        ]);
    }


    private function generateAccountCode(?int $parentId = null, ?string $accountType = null): string
    {
        if ($parentId) {
            $parent = Account::findOrFail($parentId);

            $lastChild = Account::where('parent_id', $parent->id)
                ->orderByRaw('CAST(account_code AS UNSIGNED) DESC')
                ->first();

            if ($lastChild) {
                return (string) ((int) $lastChild->account_code + 1);
            }

            return $parent->account_code . '01';
        }

        $prefix = match ($accountType) {
            'asset' => '1',
            'liability' => '2',
            'equity' => '3',
            'revenue' => '4',
            'expense' => '5',
            default => '9',
        };

        $lastRoot = Account::whereNull('parent_id')
            ->where('account_type', $accountType)
            ->orderByRaw('CAST(account_code AS UNSIGNED) DESC')
            ->first();

        if ($lastRoot) {
            return (string) ((int) $lastRoot->account_code + 1000);
        }

        return $prefix . '000';
    }
}