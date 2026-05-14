<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $map = [
            '1-A' => 24,
            '1-B' => 23,
            '1-C' => 23,
            '10-A' => 8,
            '10-B' => 8,
            '11-A' => 16,
            '11-B' => 9,
            '12-A' => 12,
            '2-A' => 19,
            '2-B' => 24,
            '2-C' => 12,
            '2-D' => 8,
            '3-A' => 11,
            '3-B' => 11,
            '3-C' => 18,
            '3-D' => 20,
            '4-A' => 10,
            '4-B' => 8,
            '4-C' => 12,
            '4-D' => 9,
            '4-E' => 11,
            '5-A' => 22,
            '5-B' => 22,
            '5-C' => 20,
            '6-A' => 12,
            '6-B' => 12,
            '6-C' => 12,
            '7-A' => 11,
            '7-B' => 12,
            '7-C' => 24,
            '8-A' => 12,
            '8-B' => 12,
            '8-C' => 10,
            '9-A' => 6,
            '9-B' => 6,
            'Akıl Oyunları-A' => 6,
            'Bilgisayar Sınıfı-A' => 12,
            'İspanyolca Sınıfı-A' => 8,
            'Kütüphane-A' => 12,
            'Nöbet Sınıfı-A' => 12,
            'Resim Sınıfı-A' => 8,
        ];

        foreach ($map as $name => $capacity) {
            DB::table('classes')->where('name', $name)->update(['capacity' => $capacity]);
        }
    }

    public function down(): void
    {
        // no-op
    }
};

