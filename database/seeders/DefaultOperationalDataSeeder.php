<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DefaultOperationalDataSeeder extends Seeder
{
    public function run(): void
    {
        // $branchId = $this->seedBranch();
        $branchId = 1;
        $this->seedWarehouse($branchId);
        $this->seedCustomer();
        $this->seedSupplier();
        $this->seedCostCenter();

        $unitIds = $this->seedUnits();
        $this->seedProducts($unitIds);
    }


    // private function seedBranch(): ?int
    // {
    //     if (!Schema::hasTable('branches')) {
    //         return null;
    //     }

    //     $data = $this->filterColumns('branches', [
    //         'branch_name' => 'الفرع الرئيسي',
    //         'branch_name_ar' => 'الفرع الرئيسي',
    //         'branch_name_en' => 'Main Branch',
    //         'code' => 'BR-001',
    //         'address' => 'الرياض',
    //         'phone' => '0500000000',
    //         'is_active' => 1,
    //         'is_main' => 1,
    //         'created_at' => now(),
    //         'updated_at' => now(),
    //     ]);

    //     $branch = DB::table('branches')
    //         ->where(function ($query) {
    //             if (Schema::hasColumn('branches', 'code')) {
    //                 $query->where('code', 'BR-001');
    //             } elseif (Schema::hasColumn('branches', 'branch_name')) {
    //                 $query->where('branch_name', 'الفرع الرئيسي');
    //             } elseif (Schema::hasColumn('branches', 'branch_name_ar')) {
    //                 $query->where('branch_name_ar', 'الفرع الرئيسي');
    //             }
    //         })
    //         ->first();

    //     if ($branch) {
    //         DB::table('branches')->where('id', $branch->id)->update($data);
    //         return $branch->id;
    //     }

    //     return DB::table('branches')->insertGetId($data);
    // }


    private function seedWarehouse(?int $branchId): ?int
    {
        if (!Schema::hasTable('warehouses')) {
            return null;
        }

        $data = $this->filterColumns('warehouses', [
            'warehouse_name' => 'المستودع الرئيسي',
            'warehouse_name_ar' => 'المستودع الرئيسي',
            'warehouse_name_en' => 'Main Warehouse',
            'name' => 'المستودع الرئيسي',
            'warehouse_code' => 'WH-001',
            'branch_id' => $branchId,
            'address' => 'الرياض',
            'phone' => '0500000000',
            'is_active' => 1,
            'is_main' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $warehouse = DB::table('warehouses')
            ->where(function ($query) {
                if (Schema::hasColumn('warehouses', 'warehouse_code')) {
                    $query->where('warehouse_code', 'WH-001');
                } elseif (Schema::hasColumn('warehouses', 'warehouse_name')) {
                    $query->where('warehouse_name', 'المستودع الرئيسي');
                } elseif (Schema::hasColumn('warehouses', 'name')) {
                    $query->where('name', 'المستودع الرئيسي');
                }
            })
            ->first();

        if ($warehouse) {
            DB::table('warehouses')->where('id', $warehouse->id)->update($data);
            return $warehouse->id;
        }

        return DB::table('warehouses')->insertGetId($data);
    }


    private function seedCustomer(): ?int
    {
        if (!Schema::hasTable('customers')) {
            return null;
        }

        $data = $this->filterColumns('customers', [
            'customer_code' => 'CN01',
            'customer_name' => 'عميل افتراضي',
            'name' => 'عميل افتراضي',
            'mobile' => '0501111111',
            'phone' => '0501111111',
            'customer_mobile' => '0501111111',
            'tax_number' => '300000000000003',
            'vat_number' => '300000000000003',
            'tax_registration_number' => '300000000000003',
            'address' => 'الرياض',
            'customer_address' => 'الرياض',
            'opening_balance' => 0,
            'credit_limit' => 0,
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $customer = DB::table('customers')
            ->where(function ($query) {
                if (Schema::hasColumn('customers', 'mobile')) {
                    $query->where('mobile', '0501111111');
                } elseif (Schema::hasColumn('customers', 'customer_mobile')) {
                    $query->where('customer_mobile', '0501111111');
                } elseif (Schema::hasColumn('customers', 'customer_name')) {
                    $query->where('customer_name', 'عميل افتراضي');
                } elseif (Schema::hasColumn('customers', 'name')) {
                    $query->where('name', 'عميل افتراضي');
                }
            })
            ->first();

        if ($customer) {
            DB::table('customers')->where('id', $customer->id)->update($data);
            return $customer->id;
        }

        return DB::table('customers')->insertGetId($data);
    }


    private function seedSupplier(): ?int
    {
        if (!Schema::hasTable('suppliers')) {
            return null;
        }

        $data = $this->filterColumns('suppliers', [
            'supplier_code' => 'SP01',
            'supplier_name' => 'مورد افتراضي',
            'name' => 'مورد افتراضي',
            'mobile' => '0502222222',
            'phone' => '0502222222',
            'supplier_mobile' => '0502222222',
            'tax_number' => '300000000000004',
            'vat_number' => '300000000000004',
            'tax_registration_number' => '300000000000004',
            'address' => 'الرياض',
            'supplier_address' => 'الرياض',
            'opening_balance' => 0,
            'credit_limit' => 0,
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $supplier = DB::table('suppliers')
            ->where(function ($query) {
                if (Schema::hasColumn('suppliers', 'mobile')) {
                    $query->where('mobile', '0502222222');
                } elseif (Schema::hasColumn('suppliers', 'supplier_mobile')) {
                    $query->where('supplier_mobile', '0502222222');
                } elseif (Schema::hasColumn('suppliers', 'supplier_name')) {
                    $query->where('supplier_name', 'مورد افتراضي');
                } elseif (Schema::hasColumn('suppliers', 'name')) {
                    $query->where('name', 'مورد افتراضي');
                }
            })
            ->first();

        if ($supplier) {
            DB::table('suppliers')->where('id', $supplier->id)->update($data);
            return $supplier->id;
        }

        return DB::table('suppliers')->insertGetId($data);
    }


    private function seedCostCenter(): ?int
    {
        if (!Schema::hasTable('cost_centers')) {
            return null;
        }

        $data = $this->filterColumns('cost_centers', [
            'code' => 'CC-001',
            'name' => 'مركز تكلفة عام',
            'parent_id' => null,
            'is_active' => 1,
            'notes' => 'مركز تكلفة افتراضي',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $costCenter = DB::table('cost_centers')
            ->where(function ($query) {
                if (Schema::hasColumn('cost_centers', 'code')) {
                    $query->where('code', 'CC-001');
                } else {
                    $query->where('name', 'مركز تكلفة عام');
                }
            })
            ->first();

        if ($costCenter) {
            DB::table('cost_centers')->where('id', $costCenter->id)->update($data);
            return $costCenter->id;
        }

        return DB::table('cost_centers')->insertGetId($data);
    }

    private function seedUnits(): array
    {
        if (!Schema::hasTable('units')) {
            return [];
        }

        $pieceId = $this->seedUnit([
            'unit_code' => 'PCS',
            'code' => 'PCS',
            'unit_name' => 'حبة',
            'unit_name_ar' => 'حبة',
            'unit_name_en' => 'Piece',
            'name' => 'حبة',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $cartonId = $this->seedUnit([
            'unit_code' => 'CTN',
            'code' => 'CTN',
            'unit_name' => 'كرتون',
            'unit_name_ar' => 'كرتون',
            'unit_name_en' => 'Carton',
            'name' => 'كرتون',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [
            'piece' => $pieceId,
            'carton' => $cartonId,
        ];
    }


    private function seedUnit(array $rawData): ?int
    {
        $data = $this->filterColumns('units', $rawData);

        $unit = null;

        if (Schema::hasColumn('units', 'unit_code')) {
            $unit = DB::table('units')->where('unit_code', $rawData['unit_code'])->first();
        } elseif (Schema::hasColumn('units', 'code')) {
            $unit = DB::table('units')->where('code', $rawData['code'])->first();
        } elseif (Schema::hasColumn('units', 'unit_name')) {
            $unit = DB::table('units')->where('unit_name', $rawData['unit_name'])->first();
        } elseif (Schema::hasColumn('units', 'name')) {
            $unit = DB::table('units')->where('name', $rawData['name'])->first();
        }

        if ($unit) {
            DB::table('units')->where('id', $unit->id)->update($data);
            return $unit->id;
        }

        return DB::table('units')->insertGetId($data);
    }


    private function seedProducts(array $unitIds): void
    {
        if (!Schema::hasTable('products')) {
            return;
        }

        $pieceId = $unitIds['piece'] ?? null;
        $cartonId = $unitIds['carton'] ?? null;

        $productOneId = $this->seedProduct([
            'category_id' => '1',
            'sku' => 'PRD-001',
            'product_code' => 'PRD-001',
            'barcode' => '6280000000011',

            'product_name_ar' => 'منتج افتراضي 1',
            'product_name_en' => 'Default Product 1',
            'product_name' => 'منتج افتراضي 1',
            'name' => 'منتج افتراضي 1',

            'short_name' => 'منتج 1',
            'keywords' => 'منتج افتراضي تجربة بيع شراء',
            'product_type' => 'inventory',
            'type' => 'inventory',

            'minimum_quantity' => 5,
            'min_quantity' => 5,
            'track_inventory' => 1,
            'is_active' => 1,
            'description' => 'منتج افتراضي للاختبار',

            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $productTwoId = $this->seedProduct([
            'category_id' => '1',
            'sku' => 'PRD-002',
            'product_code' => 'PRD-002',
            'barcode' => '6280000000028',

            'product_name_ar' => 'منتج افتراضي 2',
            'product_name_en' => 'Default Product 2',
            'product_name' => 'منتج افتراضي 2',
            'name' => 'منتج افتراضي 2',

            'short_name' => 'منتج 2',
            'keywords' => 'منتج افتراضي تجربة مخزون',
            'product_type' => 'inventory',
            'type' => 'inventory',

            'minimum_quantity' => 10,
            'min_quantity' => 10,
            'track_inventory' => 1,
            'is_active' => 1,
            'description' => 'منتج افتراضي للاختبار',

            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($productOneId && $pieceId) {
            $this->seedProductUnit($productOneId, $pieceId, [
                'conversion_factor' => 1,
                'factor' => 1,
                'purchase_price' => 80,
                'sale_price' => 130,
                'minimum_sale_price' => 120,
                'is_default' => 1,
                'is_base' => 1,
            ]);
        }

        if ($productOneId && $cartonId) {
            $this->seedProductUnit($productOneId, $cartonId, [
                'conversion_factor' => 12,
                'factor' => 12,
                'purchase_price' => 900,
                'sale_price' => 1500,
                'minimum_sale_price' => 1400,
                'is_default' => 0,
                'is_base' => 0,
            ]);
        }

        if ($productTwoId && $pieceId) {
            $this->seedProductUnit($productTwoId, $pieceId, [
                'conversion_factor' => 1,
                'factor' => 1,
                'purchase_price' => 50,
                'sale_price' => 90,
                'minimum_sale_price' => 80,
                'is_default' => 1,
                'is_base' => 1,
            ]);
        }
    }


    private function seedProduct(array $rawData): ?int
    {
        $data = $this->filterColumns('products', $rawData);

        $product = null;

        if (Schema::hasColumn('products', 'sku')) {
            $product = DB::table('products')->where('sku', $rawData['sku'])->first();
        } elseif (Schema::hasColumn('products', 'product_code')) {
            $product = DB::table('products')->where('product_code', $rawData['product_code'])->first();
        } elseif (Schema::hasColumn('products', 'barcode')) {
            $product = DB::table('products')->where('barcode', $rawData['barcode'])->first();
        } elseif (Schema::hasColumn('products', 'product_name_ar')) {
            $product = DB::table('products')->where('product_name_ar', $rawData['product_name_ar'])->first();
        } elseif (Schema::hasColumn('products', 'name')) {
            $product = DB::table('products')->where('name', $rawData['name'])->first();
        }

        if ($product) {
            DB::table('products')->where('id', $product->id)->update($data);
            return $product->id;
        }

        return DB::table('products')->insertGetId($data);
    }


    private function seedProductUnit(int $productId, int $unitId, array $rawData): ?int
    {
        if (!Schema::hasTable('product_units')) {
            return null;
        }

        $data = $this->filterColumns('product_units', array_merge([
            'product_id' => $productId,
            'unit_id' => $unitId,
            'product_unit_id' => $unitId,
            'created_at' => now(),
            'updated_at' => now(),
        ], $rawData));

        $productUnit = DB::table('product_units')
            ->where('product_id', $productId)
            ->where(function ($query) use ($unitId) {
                if (Schema::hasColumn('product_units', 'unit_id')) {
                    $query->where('unit_id', $unitId);
                } elseif (Schema::hasColumn('product_units', 'product_unit_id')) {
                    $query->where('product_unit_id', $unitId);
                }
            })
            ->first();

        if ($productUnit) {
            DB::table('product_units')->where('id', $productUnit->id)->update($data);
            return $productUnit->id;
        }

        return DB::table('product_units')->insertGetId($data);
    }
    private function filterColumns(string $table, array $data): array
    {
        return collect($data)
            ->filter(function ($value, $column) use ($table) {
                return Schema::hasColumn($table, $column);
            })
            ->toArray();
    }
}