<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
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

        // 2. メール送信処理（後ほど Mailable を作成します）
        // 一旦、ログに記録するだけにします（動作確認用）
        \Log::info('お問い合わせ届きました: ', $validated);

        // 3. 元のページに戻る（React側の onSuccess が実行されます）
        return back();
    }
}
