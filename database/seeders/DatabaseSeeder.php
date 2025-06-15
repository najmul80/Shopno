<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // It's crucial to run RolesAndPermissionsSeeder first
        // as other seeders might rely on users with specific roles/stores.
        $this->call([
            RolesAndPermissionsSeeder::class, // This creates super-admin, store-admin, roles, permissions
            // Do not call UserFactory directly here if RolesAndPermissionsSeeder creates users.
            // Or, ensure UserFactory assigns roles if it creates users.
        ]);

        // Get the created users and stores to associate with other data
        $superAdmin = \App\Models\User::where('email', 'superadmin@example.com')->first();
        $storeAdmin = \App\Models\User::where('email', 'storeadmin@example.com')->first();
        $salesPerson = \App\Models\User::where('email', 'salesperson@example.com')->first();
        $demoStore = \App\Models\Store::where('name', 'Demo Test Store')->first();

        if (!$demoStore) {
            $this->command->error("Demo Test Store not found. Please ensure RolesAndPermissionsSeeder runs successfully and creates it.");
            return;
        }

        // Seed Stores (if RolesAndPermissionsSeeder doesn't create enough)
        // \App\Models\Store::factory(2)->create(); // Example: create 2 more stores

        // Seed Categories - associated with the demoStore
        \App\Models\Category::factory(5)->for($demoStore)->create()->each(function ($category) use ($demoStore) {
            // Create sub-categories for some parent categories
            if (rand(0, 1)) {
                \App\Models\Category::factory(rand(1, 3))->for($demoStore)->create(['parent_id' => $category->id]);
            }
        });
        $this->command->info('Categories and sub-categories seeded for Demo Test Store.');


        // Seed Customers - associated with the demoStore
        \App\Models\Customer::factory(20)->for($demoStore)->create();
        $this->command->info('Customers seeded for Demo Test Store.');

        // Seed Products - associated with the demoStore and its categories
        $demoStoreCategories = $demoStore->categories()->whereNull('parent_id')->get(); // Get top-level categories of the store
        if ($demoStoreCategories->isNotEmpty()) {
            for ($i = 0; $i < 30; $i++) { // Create 30 products
                \App\Models\Product::factory()
                    ->for($demoStore)
                    ->for($demoStoreCategories->random()) // Assign a random category from the store
                    ->has(\App\Models\ProductImage::factory()->count(rand(1, 3)), 'images') // Create 1-3 images for each product
                    ->create();
            }
            $this->command->info('Products with images seeded for Demo Test Store.');
        } else {
            $this->command->warn('No categories found for Demo Test Store. Products were not seeded with categories.');
        }


        // Seed Sales - using products from demoStore and staff from demoStore
        $demoStoreProducts = $demoStore->products;
        $staffForSales = collect([$storeAdmin, $salesPerson])->filter(); // Filter out null users

        if ($demoStoreProducts->isNotEmpty() && $staffForSales->isNotEmpty()) {
            for ($i = 0; $i < 50; $i++) { // Create 50 sales transactions
                $sale = \App\Models\Sale::factory()
                    ->for($demoStore)
                    ->for($staffForSales->random()) // Random staff (store admin or salesperson)
                    ->for($demoStore->customers->isNotEmpty() ? $demoStore->customers->random() : \App\Models\Customer::factory()->for($demoStore)->create()) // Random existing customer or create new
                    ->create([ // Override factory generated totals with more realistic ones based on items
                        'created_at' => now()->subDays(rand(0, 90))->subHours(rand(0,23))->subMinutes(rand(0,59)), // Random date in last 90 days
                    ]);

                $numberOfItems = rand(1, 5);
                $saleSubTotal = 0;
                for ($j = 0; $j < $numberOfItems; $j++) {
                    $product = $demoStoreProducts->where('stock_quantity', '>', 0)->random(); // Pick a product with stock
                    if ($product) {
                        $quantity = rand(1, min(3, $product->stock_quantity)); // Max 3 items or available stock
                        $itemSubTotal = $product->sale_price * $quantity;
                        $saleSubTotal += $itemSubTotal;

                        \App\Models\SaleItem::factory()->create([
                            'sale_id' => $sale->id,
                            'product_id' => $product->id,
                            'quantity' => $quantity,
                            'unit_price' => $product->sale_price,
                            'item_sub_total' => $itemSubTotal,
                        ]);
                        // Decrement stock (Important: SaleFactory might need to handle this or do it here)
                        $product->decrement('stock_quantity', $quantity);
                    }
                }
                // Update sale totals based on items
                $discount = $saleSubTotal > 100 ? round($saleSubTotal * (rand(0,10)/100), 2) : 0; // 0-10% discount if subtotal > 100
                $taxRate = 5.00; // Example 5% tax
                $taxableAmount = $saleSubTotal - $discount;
                $tax = round($taxableAmount * ($taxRate/100), 2);
                $shipping = rand(0,1) ? rand(50, 200) : 0;
                $grandTotal = $taxableAmount + $tax + $shipping;

                $sale->update([
                    'sub_total' => $saleSubTotal,
                    'discount_amount' => $discount,
                    'tax_percentage' => $taxRate,
                    'tax_amount' => $tax,
                    'shipping_charge' => $shipping,
                    'grand_total' => $grandTotal,
                    'amount_paid' => $grandTotal, // Assuming fully paid for simplicity
                    'payment_status' => 'paid',
                ]);
            }
            $this->command->info('Sales with items seeded for Demo Test Store.');
        } else {
             $this->command->warn('No products or staff found for Demo Test Store. Sales were not seeded.');
        }

        // Note: ActivityLog and Notification seeders are not typically done,
        // as these are generated by application activity.
    }
}