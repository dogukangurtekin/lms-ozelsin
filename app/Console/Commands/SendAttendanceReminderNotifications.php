<?php

namespace App\Console\Commands;

use App\Models\AttendanceSession;
use App\Models\TeacherSchedule;
use App\Services\PushNotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class SendAttendanceReminderNotifications extends Command
{
    protected $signature = 'notifications:attendance-reminders';

    protected $description = 'Yoklama alinmayan dersler icin ogretmenlere otomatik push hatirlatmasi gonderir';

    public function handle(PushNotificationService $pushNotifications): int
    {
        $tz = 'Europe/Istanbul';
        $now = now($tz);
        $today = $now->toDateString();
        $dayOfWeek = (int) $now->dayOfWeekIso;

        $schedules = TeacherSchedule::query()
            ->with(['teacher:id,name', 'class:id,name'])
            ->where('is_active', true)
            ->where('day_of_week', $dayOfWeek)
            ->whereNotNull('teacher_id')
            ->whereNotNull('class_id')
            ->whereNotNull('end_time')
            ->get();

        $sent = 0;

        foreach ($schedules as $schedule) {
            $endTime = (string) $schedule->end_time;
            if ($endTime === '') {
                continue;
            }

            $lessonEnd = Carbon::createFromFormat('Y-m-d H:i:s', $today.' '.substr($endTime, 0, 8), $tz);
            $reminderStart = $lessonEnd->copy()->subMinutes(10);

            // Sadece dersin son 10 dakikasi icinde, dakika bazli hatirlatma gonder.
            if (! $now->betweenIncluded($reminderStart, $lessonEnd)) {
                continue;
            }

            $session = AttendanceSession::query()
                ->where('schedule_id', $schedule->id)
                ->whereDate('attendance_date', $today)
                ->first();

            if ($session && $session->taken_at) {
                continue;
            }

            $sent += $pushNotifications->sendToUsers(
                [$schedule->teacher_id],
                'Yoklama hatirlatmasi',
                (($schedule->class?->name ?? 'Sinif') . ' sinifi icin ' . ($schedule->lesson_name ?: 'ders') . ' yoklamasi henuz alinmadi. Dersin bitimine 10 dakikadan az kaldi, lutfen yoklamayi tamamlayin.'),
                route('attendance.index', ['date' => $today, 'schedule_id' => $schedule->id]),
                ['notification_type' => 'attendance_reminder']
            );
        }

        $this->info("Gonderilen yoklama hatirlatmasi: {$sent}");

        return self::SUCCESS;
    }
}
