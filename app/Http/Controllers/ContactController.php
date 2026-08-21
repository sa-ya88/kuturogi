<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class ContactController extends Controller
{
    // お問い合わせフォームを表示
    public function index()
    {
        return Inertia::render('Contact');
    }

    // 送信処理
    public function send(Request $request)
    {
        // 1. バリデーション（入力チェック）
        $validated = $request->validate([
            'name'    => 'required|string|max:255',
            'email'   => 'required|email|max:255',
            'subject' => 'required|string',
            'message' => 'required|string|min:10',
        ]);

        Log::info('Contact form submitted.', [
            'subject' => $validated['subject'],
            'message_length' => mb_strlen($validated['message']),
        ]);

        return redirect()->route('contact.thanks');
    }

    public function thanks()
    {
        return Inertia::render('Contact/Thanks');
    }
}
