<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Gia tham khao tu Senshi Hobby (senshihobby.com), AZGundam (azgundam.com)
     * va HACOM (hacom.vn, SD Exia 159.000d), thang 09/2026. Anh san pham tai ve tu CDN cua shop (chi dung cho demo hoc tap).
     */
    public function run(): void
    {
        $this->call([
            AdminSeeder::class,
            SettingSeeder::class,
        ]);

        $bandai = Brand::firstOrCreate(['name' => 'Bandai']);

        $data = [
            'SD' => [
                ['SD Gundam Exia', 159000, 'sd-exia.jpg', 'Mo hinh SD EX-Standard Exia nho gon, de lap rap.'],
            ],
            'HG' => [
                ['HG Reginlaze Julia', 455000, 'hg-reginlaze-julia.jpg', 'HG 1/144 Reginlaze Julia tu Iron-Blooded Orphans.'],
                ['HG IBO Graze Custom', 429000, 'hg-graze-custom.jpg', 'HG 1/144 Graze Custom de do, de choi.'],
                ['HG CE Destiny Gundam', 625000, 'hg-ce-destiny.jpg', 'HG 1/144 Destiny tu Gundam SEED.'],
                ["HG IBO Carta's Graze Ritter", 474000, 'hg-graze-ritter.jpg', 'HG 1/144 Graze Ritter cua Carta Issue.'],
                ['HG Graze Standard/Commander', 450000, 'hg-graze-standard.jpg', 'HG 1/144 Graze ban tieu chuan.'],
            ],
            'RG' => [
                ['RG Shining Gundam', 809000, 'rg-shining.jpg', 'RG 1/144 Shining Gundam tu G Gundam.'],
            ],
            'MG' => [
                ['MG 1/100 MSN-06S Sinanju OVA', 1729000, 'mg-sinanju-ova.jpg', 'MG 1/100 Sinanju ban OVA chi tiet cao.'],
                ['MG 1/100 RX-0 Full Armor Unicorn Ver.Ka', 1839000, 'mg-full-armor-unicorn.jpg', 'MG 1/100 Full Armor Unicorn Ver.Ka sieu chi tiet.'],
            ],
            'PG' => [
                ['PG Unleashed RX-78-2', 6500000, 'pg-unleashed.png', 'PG 1/60 Unleashed dinh cao ky thuat Gunpla.'],
            ],
        ];

        foreach ($data as $grade => $products) {
            $category = Category::firstOrCreate(
                ['name' => $grade],
                ['description' => 'Dong mo hinh Gundam '.$grade]
            );

            foreach ($products as [$name, $price, $image, $description]) {
                Product::firstOrCreate(
                    ['name' => $name],
                    [
                        'category_id' => $category->id,
                        'brand_id' => $bandai->id,
                        'price' => $price,
                        'quantity' => 20,
                        'image' => $image,
                        'description' => $description,
                    ]
                );
            }
        }

        // Demo group-buy data — local/dev only, never on production.
        // Đặt SAU khi seed products vì BatchSeeder cần product có sẵn.
        if (app()->environment('local')) {
            $this->call([
                BatchSeeder::class,
            ]);
        }
    }
}
