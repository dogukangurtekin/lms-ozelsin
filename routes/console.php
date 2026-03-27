<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('phones:normalize-tr', function () {
    $normalize = static function (?string $phone): ?string {
        if ($phone === null) {
            return null;
        }

        $value = trim($phone);
        if ($value === '') {
            return $phone;
        }

        $value = preg_replace('/[^\d+]/', '', $value) ?? '';
        if ($value === '') {
            return $phone;
        }

        if (str_starts_with($value, '+')) {
            $digits = substr($value, 1);
            return ctype_digit($digits) && strlen($digits) >= 10 ? '+' . $digits : $phone;
        }

        if (str_starts_with($value, '00')) {
            $digits = substr($value, 2);
            return ctype_digit($digits) && strlen($digits) >= 10 ? '+' . $digits : $phone;
        }

        $digits = preg_replace('/\D/', '', $value) ?? '';
        if ($digits === '') {
            return $phone;
        }

        if (strlen($digits) === 10 && str_starts_with($digits, '5')) {
            return '+90' . $digits;
        }

        if (strlen($digits) === 11 && str_starts_with($digits, '0')) {
            return '+90' . substr($digits, 1);
        }

        if (strlen($digits) === 12 && str_starts_with($digits, '90')) {
            return '+' . $digits;
        }

        return $phone;
    };

    $updatedUsers = 0;
    $updatedLogs = 0;

    DB::table('users')
        ->select('id', 'phone')
        ->whereNotNull('phone')
        ->where('phone', '<>', '')
        ->orderBy('id')
        ->chunkById(500, function ($rows) use ($normalize, &$updatedUsers) {
            foreach ($rows as $row) {
                $normalized = $normalize((string) $row->phone);
                if ($normalized !== null && $normalized !== $row->phone) {
                    DB::table('users')->where('id', $row->id)->update(['phone' => $normalized]);
                    $updatedUsers++;
                }
            }
        });

    DB::table('whatsapp_logs')
        ->select('id', 'receiver_phone')
        ->whereNotNull('receiver_phone')
        ->where('receiver_phone', '<>', '')
        ->orderBy('id')
        ->chunkById(500, function ($rows) use ($normalize, &$updatedLogs) {
            foreach ($rows as $row) {
                $normalized = $normalize((string) $row->receiver_phone);
                if ($normalized !== null && $normalized !== $row->receiver_phone) {
                    DB::table('whatsapp_logs')->where('id', $row->id)->update(['receiver_phone' => $normalized]);
                    $updatedLogs++;
                }
            }
        });

    $this->info("users.phone güncellendi: {$updatedUsers}");
    $this->info("whatsapp_logs.receiver_phone güncellendi: {$updatedLogs}");
})->purpose('Türkiye telefonlarını E.164 (+90...) formatına normalize eder');

Artisan::command('whatsapp:mark-stuck-queued {--minutes=3}', function () {
    $minutes = max(1, (int) $this->option('minutes'));
    $threshold = now()->subMinutes($minutes);

    $stuck = DB::table('whatsapp_logs')
        ->where('status', 'queued')
        ->where(function ($q) use ($threshold) {
            $q->where(function ($q2) use ($threshold) {
                $q2->whereNotNull('scheduled_for')
                    ->where('scheduled_for', '<=', $threshold);
            })->orWhere(function ($q2) use ($threshold) {
                $q2->whereNull('scheduled_for')
                    ->where('created_at', '<=', $threshold);
            });
        })
        ->get(['id']);

    $count = 0;
    foreach ($stuck as $row) {
        DB::table('whatsapp_logs')
            ->where('id', $row->id)
            ->update([
                'status' => 'failed',
                'error_message' => 'Queue timeout: queued record exceeded expected send window.',
                'sent_at' => now(),
                'updated_at' => now(),
            ]);
        $count++;
    }

    $this->info("stuck queued -> failed: {$count}");
})->purpose('Marks old queued WhatsApp logs as failed to prevent stuck status in UI');
