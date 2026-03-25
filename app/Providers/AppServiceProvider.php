<?php

namespace App\Providers;

use App\Models\Assignment;
use App\Models\Book;
use App\Models\Meeting;
use App\Policies\AssignmentPolicy;
use App\Policies\BookPolicy;
use App\Policies\MeetingPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Gate::policy(Book::class, BookPolicy::class);
        Gate::policy(Assignment::class, AssignmentPolicy::class);
        Gate::policy(Meeting::class, MeetingPolicy::class);
    }
}
