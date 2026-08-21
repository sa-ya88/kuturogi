<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class ContactLoggingTest extends TestCase
{
    use RefreshDatabase;

    public function test_contact_form_does_not_log_personal_information(): void
    {
        Event::fake([MessageLogged::class]);

        $payload = [
            'name' => '採用 花子',
            'email' => 'recruiter@example.com',
            'subject' => '宿泊について',
            'message' => 'これは十分に長いダミーの問い合わせ本文です。',
        ];

        $this->post(route('contact.send'), $payload)
            ->assertRedirect(route('contact.thanks'));

        Event::assertDispatched(MessageLogged::class, function (MessageLogged $event) use ($payload) {
            $encoded = json_encode($event->context, JSON_UNESCAPED_UNICODE) ?: '';

            return $event->level === 'info'
                && ! str_contains($encoded, $payload['email'])
                && ! str_contains($encoded, $payload['name'])
                && ! str_contains($encoded, $payload['message'])
                && ! str_contains($event->message, $payload['email']);
        });
    }
}
