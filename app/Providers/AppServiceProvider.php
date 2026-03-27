<?php

namespace App\Providers;

use App\Models\Assignment;
use App\Models\Book;
use App\Models\Meeting;
use App\Policies\AssignmentPolicy;
use App\Policies\BookPolicy;
use App\Policies\MeetingPolicy;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        // Force Turkish locale and UTF-8 defaults application-wide.
        App::setLocale(config('app.locale', 'tr'));
        setlocale(LC_ALL, 'tr_TR.UTF-8', 'tr_TR', 'turkish');
        mb_internal_encoding('UTF-8');
        mb_http_output('UTF-8');

        Gate::policy(Book::class, BookPolicy::class);
        Gate::policy(Assignment::class, AssignmentPolicy::class);
        Gate::policy(Meeting::class, MeetingPolicy::class);
    }
}
