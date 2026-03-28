<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Web\AssignmentController;
use App\Http\Controllers\Web\BookController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\AttendanceController;
use App\Http\Controllers\Web\LessonController;
use App\Http\Controllers\Web\TimetableController;
use App\Http\Controllers\Web\MeetingController;
use App\Http\Controllers\Web\PushNotificationController;
use App\Http\Controllers\Web\ReportController;
use App\Http\Controllers\Web\UserManagementController;
use App\Http\Controllers\Web\WhatsappController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard')->middleware('module:dashboard');
    Route::get('/notifications', [PushNotificationController::class, 'index'])->name('notifications.index')->middleware('module:dashboard');
    Route::get('/push/public-key', [PushNotificationController::class, 'publicKey'])->name('push.public-key');
    Route::post('/push/subscribe', [PushNotificationController::class, 'subscribe'])->name('push.subscribe');
    Route::delete('/push/unsubscribe', [PushNotificationController::class, 'unsubscribe'])->name('push.unsubscribe');
    Route::post('/push/send', [PushNotificationController::class, 'send'])->name('push.send')->middleware('role:admin')->middleware('module:dashboard');
    Route::post('/notifications/{notificationLog}/resend', [PushNotificationController::class, 'resend'])->name('notifications.resend')->middleware('role:admin')->middleware('module:dashboard');

    Route::get('books/create', [BookController::class, 'create'])->name('books.create')->middleware('role:admin,teacher')->middleware('module:books');
    Route::get('books', [BookController::class, 'index'])->name('books.index')->middleware('role:admin,teacher,student')->middleware('module:books');
    Route::post('books', [BookController::class, 'store'])->name('books.store')->middleware('role:admin,teacher')->middleware('module:books');
    Route::get('books/{book}/details', [BookController::class, 'details'])->name('books.details')->middleware('role:admin,teacher,student')->middleware('module:books');
    Route::post('books/{book}/details', [BookController::class, 'storeDetail'])->name('books.details.store')->middleware('role:admin,teacher')->middleware('module:books');
    Route::put('books/{book}/details/{detail}', [BookController::class, 'updateDetail'])->name('books.details.update')->middleware('role:admin,teacher')->middleware('module:books');
    Route::delete('books/{book}/details/{detail}', [BookController::class, 'destroyDetail'])->name('books.details.destroy')->middleware('role:admin,teacher')->middleware('module:books');
    Route::get('books/{book}/edit', [BookController::class, 'edit'])->name('books.edit')->middleware('role:admin,teacher')->middleware('module:books');
    Route::get('books/{book}', [BookController::class, 'show'])->name('books.show')->middleware('role:admin,teacher,student')->middleware('module:books');
    Route::put('books/{book}', [BookController::class, 'update'])->name('books.update')->middleware('role:admin,teacher')->middleware('module:books');
    Route::delete('books/{book}', [BookController::class, 'destroy'])->name('books.destroy')->middleware('role:admin,teacher')->middleware('module:books');
    Route::get('assignments/create', [AssignmentController::class, 'create'])->name('assignments.create')->middleware('role:admin,teacher')->middleware('module:assignments');
    Route::get('assignments/wizard', [AssignmentController::class, 'wizard'])->name('assignments.wizard')->middleware('role:admin,teacher')->middleware('module:assignments');
    Route::get('assignments', [AssignmentController::class, 'index'])->name('assignments.index')->middleware('role:admin,teacher,student')->middleware('module:assignments');
    Route::post('assignments', [AssignmentController::class, 'store'])->name('assignments.store')->middleware('role:admin,teacher')->middleware('module:assignments');
    Route::post('assignments/wizard', [AssignmentController::class, 'storeWizard'])->name('assignments.wizard.store')->middleware('role:admin,teacher')->middleware('module:assignments');
    Route::get('assignments/{assignment}/edit', [AssignmentController::class, 'edit'])->name('assignments.edit')->middleware('role:admin,teacher')->middleware('module:assignments');
    Route::get('assignments/{assignment}', [AssignmentController::class, 'show'])->name('assignments.show')->middleware('role:admin,teacher,student')->middleware('module:assignments');
    Route::put('assignments/{assignment}', [AssignmentController::class, 'update'])->name('assignments.update')->middleware('role:admin,teacher')->middleware('module:assignments');
    Route::delete('assignments/{assignment}', [AssignmentController::class, 'destroy'])->name('assignments.destroy')->middleware('role:admin,teacher')->middleware('module:assignments');
    Route::get('meetings/create', [MeetingController::class, 'create'])->name('meetings.create')->middleware('role:admin,teacher')->middleware('module:meetings');
    Route::get('meetings', [MeetingController::class, 'index'])->name('meetings.index')->middleware('role:admin,teacher,parent,student')->middleware('module:meetings');
    Route::post('meetings', [MeetingController::class, 'store'])->name('meetings.store')->middleware('role:admin,teacher')->middleware('module:meetings');
    Route::get('meetings/{meeting}/edit', [MeetingController::class, 'edit'])->name('meetings.edit')->middleware('role:admin,teacher')->middleware('module:meetings');
    Route::get('meetings/{meeting}', [MeetingController::class, 'show'])->name('meetings.show')->middleware('role:admin,teacher,parent,student')->middleware('module:meetings');
    Route::put('meetings/{meeting}', [MeetingController::class, 'update'])->name('meetings.update')->middleware('role:admin,teacher')->middleware('module:meetings');
    Route::put('meetings/{meeting}/status', [MeetingController::class, 'updateStatus'])->name('meetings.status.update')->middleware('role:admin,teacher')->middleware('module:meetings');
    Route::put('meetings/{meeting}/teacher-note', [MeetingController::class, 'updateTeacherNote'])->name('meetings.teacher-note.update')->middleware('role:admin,teacher')->middleware('module:meetings');
    Route::delete('meetings/{meeting}', [MeetingController::class, 'destroy'])->name('meetings.destroy')->middleware('role:admin,teacher')->middleware('module:meetings');
    Route::get('users', [UserManagementController::class, 'index'])->name('users.index')->middleware('role:admin')->middleware('module:users');
    Route::post('users', [UserManagementController::class, 'store'])->name('users.store')->middleware('role:admin')->middleware('module:users');
    Route::post('users/students', [UserManagementController::class, 'storeStudent'])->name('users.students.store')->middleware('role:admin')->middleware('module:users');
    Route::post('users/teachers', [UserManagementController::class, 'storeTeacher'])->name('users.teachers.store')->middleware('role:admin')->middleware('module:users');
    Route::post('users/classes', [UserManagementController::class, 'storeClass'])->name('users.classes.store')->middleware('role:admin')->middleware('module:users');
    Route::post('users/students/import', [UserManagementController::class, 'importStudents'])->name('users.students.import')->middleware('role:admin')->middleware('module:users');
    Route::post('users/teachers/import', [UserManagementController::class, 'importTeachers'])->name('users.teachers.import')->middleware('role:admin')->middleware('module:users');
    Route::post('users/classes/import', [UserManagementController::class, 'importClasses'])->name('users.classes.import')->middleware('role:admin')->middleware('module:users');
    Route::get('users/templates/{type}', [UserManagementController::class, 'downloadTemplate'])->name('users.templates.download')->middleware('role:admin')->middleware('module:users');
    Route::delete('users/students/{student}', [UserManagementController::class, 'destroyStudent'])->name('users.students.destroy')->middleware('role:admin')->middleware('module:users');
    Route::put('users/students/{student}', [UserManagementController::class, 'updateStudent'])->name('users.students.update')->middleware('role:admin')->middleware('module:users');
    Route::delete('users/teachers/{teacher}', [UserManagementController::class, 'destroyTeacher'])->name('users.teachers.destroy')->middleware('role:admin')->middleware('module:users');
    Route::put('users/teachers/{teacher}', [UserManagementController::class, 'updateTeacher'])->name('users.teachers.update')->middleware('role:admin')->middleware('module:users');
    Route::put('users/classes/{class}', [UserManagementController::class, 'updateClass'])->name('users.classes.update')->middleware('role:admin')->middleware('module:users');
    Route::delete('users/classes/{class}', [UserManagementController::class, 'destroyClass'])->name('users.classes.destroy')->middleware('role:admin')->middleware('module:users');
    Route::post('users/{user}/assign-role', [UserManagementController::class, 'assignRole'])->name('users.assign-role')->middleware('role:admin')->middleware('module:users');
    Route::post('users/{user}/assign-class', [UserManagementController::class, 'assignClass'])->name('users.assign-class')->middleware('role:admin')->middleware('module:users');
    Route::get('reports', [ReportController::class, 'index'])->name('reports.index')->middleware('role:admin,teacher')->middleware('module:reports');
    Route::get('reports/quick/student-pdf', [ReportController::class, 'quickStudentPdf'])->name('reports.quick.student-pdf')->middleware('role:admin,teacher')->middleware('module:reports');
    Route::get('reports/quick/attendance-pdf', [ReportController::class, 'quickAttendancePdf'])->name('reports.quick.attendance-pdf')->middleware('role:admin,teacher')->middleware('module:reports');
    Route::get('lessons', [LessonController::class, 'index'])->name('lessons.index')->middleware('role:admin')->middleware('module:lessons');
    Route::post('lessons', [LessonController::class, 'store'])->name('lessons.store')->middleware('role:admin')->middleware('module:lessons');
    Route::post('lessons/import', [LessonController::class, 'import'])->name('lessons.import')->middleware('role:admin')->middleware('module:lessons');
    Route::get('lessons/template', [LessonController::class, 'downloadTemplate'])->name('lessons.template')->middleware('role:admin')->middleware('module:lessons');
    Route::put('lessons/{lesson}', [LessonController::class, 'update'])->name('lessons.update')->middleware('role:admin')->middleware('module:lessons');
    Route::post('lessons/{lesson}/assign-teacher', [LessonController::class, 'assignTeacher'])->name('lessons.assign-teacher')->middleware('role:admin')->middleware('module:lessons');
    Route::delete('lessons/{lesson}', [LessonController::class, 'destroy'])->name('lessons.destroy')->middleware('role:admin')->middleware('module:lessons');
    Route::get('attendance', [AttendanceController::class, 'index'])->name('attendance.index')->middleware('role:admin,teacher')->middleware('module:attendance');
    Route::post('attendance/schedules', [AttendanceController::class, 'storeSchedule'])->name('attendance.schedules.store')->middleware('role:admin,teacher')->middleware('module:attendance');
    Route::post('attendance/take', [AttendanceController::class, 'take'])->name('attendance.take')->middleware('role:admin,teacher')->middleware('module:attendance');
    Route::get('timetables', [TimetableController::class, 'index'])->name('timetables.index')->middleware('role:admin,teacher')->middleware('module:timetables');
    Route::post('timetables', [TimetableController::class, 'store'])->name('timetables.store')->middleware('role:admin,teacher')->middleware('module:timetables');
    Route::post('timetables/settings', [TimetableController::class, 'updateSettings'])->name('timetables.settings.update')->middleware('role:admin,teacher')->middleware('module:timetables');
    Route::post('timetables/move', [TimetableController::class, 'move'])->name('timetables.move')->middleware('role:admin,teacher')->middleware('module:timetables');
    Route::post('timetables/import', [TimetableController::class, 'import'])->name('timetables.import')->middleware('role:admin,teacher')->middleware('module:timetables');
    Route::get('timetables/template', [TimetableController::class, 'downloadTemplate'])->name('timetables.template')->middleware('role:admin,teacher')->middleware('module:timetables');
    Route::get('timetables/teacher-pdf', [TimetableController::class, 'teacherPdf'])->name('timetables.teacher-pdf')->middleware('role:admin,teacher')->middleware('module:timetables');
    Route::get('timetables/class-pdf', [TimetableController::class, 'classPdf'])->name('timetables.class-pdf')->middleware('role:admin,teacher')->middleware('module:timetables');
    Route::delete('timetables/{schedule}', [TimetableController::class, 'destroy'])->name('timetables.destroy')->middleware('role:admin,teacher')->middleware('module:timetables');
    Route::get('whatsapp', [WhatsappController::class, 'index'])->name('whatsapp.index')->middleware('role:admin,teacher')->middleware('module:whatsapp');
    Route::post('whatsapp/send', [WhatsappController::class, 'send'])->name('whatsapp.send')->middleware('role:admin,teacher')->middleware('module:whatsapp');
    Route::post('whatsapp/requeue-queued', [WhatsappController::class, 'requeueQueued'])->name('whatsapp.requeue-queued')->middleware('role:admin,teacher')->middleware('module:whatsapp');
    Route::post('whatsapp/settings', [WhatsappController::class, 'updateSettings'])->name('whatsapp.settings.update')->middleware('role:admin')->middleware('module:whatsapp');

    Route::get('role-permissions', [\App\Http\Controllers\Web\RolePermissionController::class, 'index'])
        ->name('role-permissions.index')->middleware('role:admin')->middleware('module:role_permissions');
    Route::post('role-permissions/modules', [\App\Http\Controllers\Web\RolePermissionController::class, 'updateModules'])
        ->name('role-permissions.modules.update')->middleware('role:admin')->middleware('module:role_permissions');
    Route::post('role-permissions/users/{user}/assign-role', [\App\Http\Controllers\Web\RolePermissionController::class, 'assignRole'])
        ->name('role-permissions.assign-role')->middleware('role:admin')->middleware('module:role_permissions');

    Route::post('assignments/{assignment}/submit', [AssignmentController::class, 'submit'])
        ->name('assignments.submit')
        ->middleware('role:student')
        ->middleware('module:assignments');

    Route::post('assignment-submissions/{submission}/grade', [AssignmentController::class, 'grade'])
        ->name('assignment-submissions.grade')
        ->middleware('role:admin,teacher')
        ->middleware('module:assignments');

});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
