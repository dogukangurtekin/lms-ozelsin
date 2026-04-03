<?php

namespace App\Console\Commands;

use App\Models\AttendanceSession;
use App\Models\NotificationLog;
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
        $byTeacher = $schedules
            ->groupBy('teacher_id')
            ->map(fn ($rows) => $rows->sortBy(fn ($s) => (string) $s->start_time)->values());

        foreach ($byTeacher as $teacherSchedules) {
            foreach ($teacherSchedules as $idx => $schedule) {
                $endTime = (string) $schedule->end_time;
                if ($endTime === '') {
                    continue;
                }

                $lessonEnd = Carbon::createFromFormat('Y-m-d H:i:s', $today.' '.substr($endTime, 0, 8), $tz);
                $reminderStart = $lessonEnd->copy()->subMinutes(5);
                $isInLastFiveMinutes = $now->betweenIncluded($reminderStart, $lessonEnd);

                $nextSchedule = $teacherSchedules->get($idx + 1);
                $isPastNextLesson20Minutes = false;
                if ($nextSchedule && ! empty($nextSchedule->start_time)) {
                    $nextStart = Carbon::createFromFormat(
                        'Y-m-d H:i:s',
                        $today.' '.substr((string) $nextSchedule->start_time, 0, 8),
                        $tz
                    );
                    $isPastNextLesson20Minutes = $now->greaterThanOrEqualTo($nextStart->copy()->addMinutes(20));
                }

                // Son ders fallback: sonraki ders yoksa, ders bitiminden 20 dk sonra da bir kez hatirlat.
                $isPastLessonEnd20Minutes = $nextSchedule ? false : $now->greaterThanOrEqualTo($lessonEnd->copy()->addMinutes(20));

                // 1) Dersin son 5 dakikasi
                // 2) Sonraki ders baslayip ilk 20 dk gecmis
                // 3) Son dersse bitimden 20 dk gecmis
                if (! $isInLastFiveMinutes && ! $isPastNextLesson20Minutes && ! $isPastLessonEnd20Minutes) {
                    continue;
                }

                $session = AttendanceSession::query()
                    ->where('schedule_id', $schedule->id)
                    ->whereDate('attendance_date', $today)
                    ->first();

                if ($session && $session->taken_at) {
                    continue;
                }

                // Ayni ders + ayni gun icin sadece 1 kez bildirim gonder.
                $reminderKey = 'attendance_reminder:'.$today.':'.$schedule->id;
                $alreadySent = NotificationLog::query()
                    ->where('channel', 'push')
                    ->where('target_type', 'attendance_reminder')
                    ->where('target_summary', $reminderKey)
                    ->exists();

                if ($alreadySent) {
                    continue;
                }

                $periodLabel = $schedule->period_no ? ($schedule->period_no.'. ders') : 'ilgili ders';
                $reason = $isInLastFiveMinutes
                    ? 'Dersin bitimine 5 dakikadan az kaldi'
                    : ($isPastNextLesson20Minutes ? 'Sonraki dersin ilk 20 dakikasi da gecti' : 'Ders bitiminden 20 dakika gecti');

                $sent += $pushNotifications->sendToUsers(
                    [$schedule->teacher_id],
                    'Yoklama hatirlatmasi',
                    (($schedule->class?->name ?? 'Sinif') . ' sinifi icin ' . $periodLabel . ' (' . ($schedule->lesson_name ?: 'ders') . ') yoklamasi henuz alinmadi. ' . $reason . ', lutfen yoklamayi tamamlayin.'),
                    route('attendance.index', ['date' => $today, 'schedule_id' => $schedule->id]),
                    [
                        'notification_type' => 'attendance_reminder',
                        'target_type' => 'attendance_reminder',
                        'target_summary' => $reminderKey,
                        'target_count' => 1,
                        'user_id' => $schedule->teacher_id,
                    ]
                );
            }
        }

        $this->info("Gonderilen yoklama hatirlatmasi: {$sent}");

        return self::SUCCESS;
    }
}
