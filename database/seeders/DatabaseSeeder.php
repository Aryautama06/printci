<?php

namespace Database\Seeders;

use App\Models\PaymentCategory;
use App\Models\Printer;
use App\Models\ReceiptTemplate;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        PaymentCategory::create(['name' => 'Transfer Bank', 'color' => '#818cf8', 'icon' => 'bank']);
        PaymentCategory::create(['name' => 'QRIS', 'color' => '#34d399', 'icon' => 'qr']);
        PaymentCategory::create(['name' => 'Tunai', 'color' => '#fbbf24', 'icon' => 'cash']);
        ReceiptTemplate::create([
            'name' => 'Struk Utama',
            'paper_size' => '58mm',
            'font_size' => 'normal',
            'header' => 'PRINTCI\nPayment Point',
            'footer' => 'Terima kasih atas pembayaran Anda.',
            'elements' => ['customer' => true, 'bank' => true, 'transaction_id' => true],
            'is_default' => true,
        ]);
        Printer::create(['name' => 'Thermal Kasir 01', 'connection_type' => 'bluetooth', 'status' => 'offline', 'paper_size' => '58mm']);
    }
}
