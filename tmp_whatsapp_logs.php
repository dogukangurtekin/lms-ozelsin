<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$rows = App\Models\WhatsappLog::query()
    ->latest('id')
    ->take(8)
    ->get(['id','status','receiver_phone','scheduled_for','created_at','error_message']);
foreach ($rows as $x) {
    echo $x->id.' | '.$x->status.' | '.($x->scheduled_for ? $x->scheduled_for->format('Y-m-d H:i:s') : 'null').' | '.($x->error_message ?? '-').PHP_EOL;
}
