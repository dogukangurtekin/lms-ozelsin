<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$rows = Illuminate\Support\Facades\DB::table('whatsapp_logs')->orderByDesc('id')->limit(12)->get(['id','status','scheduled_for','created_at','error_message','updated_at']);
foreach ($rows as $r) {
  echo $r->id.' | '.$r->status.' | '.$r->scheduled_for.' | '.$r->updated_at.' | '.($r->error_message ?? '-').PHP_EOL;
}
