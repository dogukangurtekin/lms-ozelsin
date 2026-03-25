<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SendWhatsappRequest;
use App\Models\User;
use App\Services\WhatsappService;

class WhatsappController extends Controller
{
    public function send(SendWhatsappRequest $request, WhatsappService $whatsappService)
    {
        $receiver = User::findOrFail($request->validated('receiver_id'));

        $message = $whatsappService->queueMessage([
            'sender_id' => $request->user()->id,
            'receiver_id' => $receiver->id,
            'receiver_phone' => $receiver->phone,
            'type' => $request->validated('type'),
            'content' => $request->validated('content'),
        ]);

        return response()->json([
            'message' => 'WhatsApp mesaji kuyruga alindi.',
            'data' => $message,
        ], 202);
    }
}
