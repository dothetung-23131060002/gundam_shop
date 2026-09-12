<?php

namespace Database\Seeders;

use App\Models\Batch;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Database\Seeder;

class BatchSeeder extends Seeder
{
    public function run(): void
    {
        $demoUser = User::updateOrCreate(
            ['email' => 'demo@gundam.test'],
            [
                'name' => 'Demo User',
                'password' => bcrypt('password'),
                'role' => 'customer',
            ]
        );

        // Khách filler cho demo (role chuẩn 'customer', idempotent)
        $fillers = [];
        for ($f = 0; $f < 8; $f++) {
            $fillers[] = User::firstOrCreate(
                ['email' => "demo{$f}@gundam.test"],
                [
                    'name' => "Demo Khách {$f}",
                    'password' => bcrypt('password'),
                    'role' => 'customer',
                ]
            );
        }

        $products = Product::inRandomOrder()->limit(3)->get();

        if ($products->count() < 3) {
            return;
        }

        // Batch 1: Open, almost full (8/10 slots) - deadline +3 days
        $batch1 = Batch::create([
            'product_id' => $products[0]->id,
            'threshold' => 10,
            'deposit_amount' => 50000,
            'deadline' => now()->addDays(3),
            'status' => 'open',
        ]);

        for ($i = 0; $i < 8; $i++) {
            $user = $fillers[$i] ?? null;
            if (! $user) {
                break;
            }

            $reservation = Reservation::create([
                'user_id' => $user->id,
                'batch_id' => $batch1->id,
                'quantity' => 1,
                'deposit_paid' => $batch1->deposit_amount,
                'status' => 'reserved',
            ]);

            Payment::create([
                'user_id' => $user->id,
                'reservation_id' => $reservation->id,
                'amount' => $batch1->deposit_amount,
                'type' => 'deposit',
                'note' => "Demo: Đặt cọc slot cho batch #{$batch1->id}",
            ]);
        }

        // Give demo user a reservation on batch 1
        $demoReservation1 = Reservation::create([
            'user_id' => $demoUser->id,
            'batch_id' => $batch1->id,
            'quantity' => 1,
            'deposit_paid' => $batch1->deposit_amount,
            'status' => 'reserved',
        ]);

        Payment::create([
            'user_id' => $demoUser->id,
            'reservation_id' => $demoReservation1->id,
            'amount' => $batch1->deposit_amount,
            'type' => 'deposit',
            'note' => "Demo: Đặt cọc slot cho batch #{$batch1->id}",
        ]);

        // Batch 2: Open, 3/8 slots - deadline +7 days
        $batch2 = Batch::create([
            'product_id' => $products[1]->id,
            'threshold' => 8,
            'deposit_amount' => 80000,
            'deadline' => now()->addDays(7),
            'status' => 'open',
        ]);

        for ($i = 0; $i < 3; $i++) {
            $user = $fillers[$i] ?? null;
            if (! $user) {
                break;
            }

            $reservation = Reservation::create([
                'user_id' => $user->id,
                'batch_id' => $batch2->id,
                'quantity' => 1,
                'deposit_paid' => $batch2->deposit_amount,
                'status' => 'reserved',
            ]);

            Payment::create([
                'user_id' => $user->id,
                'reservation_id' => $reservation->id,
                'amount' => $batch2->deposit_amount,
                'type' => 'deposit',
                'note' => "Demo: Đặt cọc slot cho batch #{$batch2->id}",
            ]);
        }

        // Batch 3: Success (for demo pay-balance flow) - 5/5 slots filled
        $batch3 = Batch::create([
            'product_id' => $products[2]->id,
            'threshold' => 5,
            'deposit_amount' => 100000,
            'deadline' => now()->subDays(1),
            'status' => 'success',
        ]);

        for ($i = 0; $i < 4; $i++) {
            $user = $fillers[$i] ?? null;
            if (! $user) {
                break;
            }

            $reservation = Reservation::create([
                'user_id' => $user->id,
                'batch_id' => $batch3->id,
                'quantity' => 1,
                'deposit_paid' => $batch3->deposit_amount,
                'status' => 'reserved',
            ]);

            Payment::create([
                'user_id' => $user->id,
                'reservation_id' => $reservation->id,
                'amount' => $batch3->deposit_amount,
                'type' => 'deposit',
                'note' => "Demo: Đặt cọc slot cho batch #{$batch3->id}",
            ]);
        }

        // Demo user on batch 3 - can pay balance
        $demoReservation3 = Reservation::create([
            'user_id' => $demoUser->id,
            'batch_id' => $batch3->id,
            'quantity' => 1,
            'deposit_paid' => $batch3->deposit_amount,
            'status' => 'reserved',
        ]);

        Payment::create([
            'user_id' => $demoUser->id,
            'reservation_id' => $demoReservation3->id,
            'amount' => $batch3->deposit_amount,
            'type' => 'deposit',
            'note' => "Demo: Đặt cọc slot cho batch #{$batch3->id}",
        ]);
    }
}
