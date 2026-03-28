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
        $today = now()->toDateString();
        $dayOfWeek = (int) Carbon::parse($today)->dayOfWeekIso;
        $currentTime = now()->format('H:i:s');

        $schedules = TeacherSchedule::query()
            ->with(['teacher:id,name', 'class:id,name'])
            ->where('is_active', true)
            ->where('day_of_week', $dayOfWeek)
            ->whereNotNull('teacher_id')
            ->whereNotNull('class_id')
            ->where(function ($query) use ($currentTime) {
                $query->whereNotNull('end_time')
                    ->where('end_time', '<', $currentTime)
                    ->orWhere(function ($subQuery) use ($currentTime) {
                        $subQuery->whereNull('end_time')
                            ->where('start_time', '<', $currentTime);
                    });
            })
            ->get();

        $sent = 0;

        foreach ($schedules as $schedule) {
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
                (($schedule->class?->name ?? 'Sinif') . ' sinifi icin ' . ($schedule->lesson_name ?: 'ders') . ' yoklamasi henuz alinmadi. Lutfen yoklama islemini tamamlayin.'),
                route('attendance.index', ['date' => $today, 'schedule_id' => $schedule->id])
            );
        }

        $this->info("Gonderilen yoklama hatirlatmasi: {$sent}");

        return self::SUCCESS;
    }
}
