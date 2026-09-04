<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Models moved from App\Models\X into App\Models\<Module>\X. Polymorphic
 * columns store the class name as a literal string, so existing rows must be
 * rewritten or role checks, token auth and notifications stop resolving.
 */
return new class extends Migration
{
    private const MODULES = [
        'Administration' => ['User', 'Menu', 'StoreSetting'],
        'Customers'      => ['Customer', 'CustomerMeasurement'],
        'Tailoring'      => ['Order', 'OrderItem', 'OrderItemMaterial', 'OrderItemMeasurement',
                             'OrderPayment', 'Tailor', 'TailorAssignment', 'MeasurementType',
                             'MeasurementField', 'GarmentPrice', 'AlterationOrder',
                             'AlterationOrderPayment', 'AlterationGarment',
                             'AlterationGarmentMeasurement', 'AlterationGarmentPhoto',
                             'AlterationStatusHistory', 'AlterationTask',
                             'AlterationTaskAssignment', 'AlterationType'],
        'Inventory'      => ['Product', 'ProductMedia', 'ProductSeo', 'ProductSpecification',
                             'ProductVariant', 'Category', 'Brand', 'Tag', 'Attribute',
                             'AttributeValue', 'Offer', 'StockMovement', 'StockAdjustment',
                             'Supplier', 'SupplierContact', 'SupplierLedgerEntry',
                             'SupplierReturn', 'SupplierReturnItem', 'Purchase', 'PurchaseItem',
                             'PurchasePayment'],
        'Sales'          => ['Sale', 'SaleItem', 'SaleReturn', 'SaleReturnItem', 'Cart'],
        'Accounting'     => ['Account', 'JournalEntry', 'JournalEntryLine', 'Expense'],
        'Messages'       => ['ContactMessage'],
    ];

    private const COLUMNS = [
        'model_has_roles'        => 'model_type',
        'model_has_permissions'  => 'model_type',
        'personal_access_tokens' => 'tokenable_type',
        'notifications'          => 'notifiable_type',
    ];

    public function up(): void
    {
        $this->remap(true);
    }

    public function down(): void
    {
        $this->remap(false);
    }

    private function remap(bool $toModules): void
    {
        $root = 'App\\Models\\';

        foreach (self::COLUMNS as $table => $column) {
            if (!Schema::hasTable($table) || !Schema::hasColumn($table, $column)) {
                continue;
            }

            foreach (self::MODULES as $module => $models) {
                foreach ($models as $model) {
                    $flat      = $root . $model;
                    $namespaced = $root . $module . '\\' . $model;

                    [$from, $to] = $toModules ? [$flat, $namespaced] : [$namespaced, $flat];

                    DB::table($table)->where($column, $from)->update([$column => $to]);
                }
            }
        }
    }
};
