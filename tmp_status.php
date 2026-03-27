<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$log = App\Models\WhatsappLog::query()->where('id',12)->first(['id','status','scheduled_for','created_at','error_message','updated_at']);
if ($log) {
  echo "LOG#12 => {$log->status} | sched=".($log->scheduled_for?->format('Y-m-d H:i:s') ?? 'null')." | upd=".$log->updated_at->format('Y-m-d H:i:s')." | err=".($log->error_message ?? '-').PHP_EOL;
}

$failed = Illuminate\Support\Facades\DB::table('failed_jobs')->orderByDesc('id')->first(['id','failed_at','exception']);
if ($failed) {
  echo "FAILED_JOB => #{$failed->id} at {$failed->failed_at}".PHP_EOL;
  echo substr($failed->exception,0,600).PHP_EOL;
}
