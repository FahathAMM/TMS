<?php

namespace Database\Seeders;

use App\Models\Tailor;
use Illuminate\Database\Seeder;

class TailorSeeder extends Seeder
{
    public function run(): void
    {
        $tailors = [
            ['first_name' => 'Rashid', 'last_name' => 'Khan', 'phone' => '0551112233', 'specialization' => 'Thobe & Traditional Wear', 'is_active' => true],
            ['first_name' => 'Fatima', 'last_name' => 'Al-Sayed', 'phone' => '0552223344', 'specialization' => 'Suits & Jackets', 'is_active' => true],
            ['first_name' => 'Imran', 'last_name' => 'Sheikh', 'phone' => '0553334455', 'specialization' => 'Trousers & Shirts', 'is_active' => true],
            ['first_name' => 'Noor', 'last_name' => 'Ahmed', 'phone' => '0554445566', 'specialization' => 'Alterations', 'is_active' => true],
            ['first_name' => 'Kamal', 'last_name' => 'Perera', 'phone' => '0555556677', 'specialization' => 'Embroidery & Finishing', 'is_active' => true],
            ['first_name' => 'Sana', 'last_name' => 'Iqbal', 'phone' => '0556667788', 'specialization' => 'Dress Making', 'is_active' => false],
        ];

        foreach ($tailors as $tailor) {
            Tailor::firstOrCreate(['phone' => $tailor['phone']], $tailor);
        }
    }
}
