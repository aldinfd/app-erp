<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\SystemNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_notification_is_stored_in_the_database_channel(): void
    {
        $user = User::factory()->create();

        $user->notify(new SystemNotification(
            title: 'Selamat datang',
            body: 'Notifikasi uji coba dari sistem ERP.',
        ));

        $this->assertDatabaseHas('notifications', [
            'type' => SystemNotification::class,
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
        ]);

        $stored = $user->notifications()->first();

        $this->assertNotNull($stored);
        $this->assertSame('Selamat datang', $stored->data['title']);
        $this->assertSame('Notifikasi uji coba dari sistem ERP.', $stored->data['body']);
    }

    public function test_notification_is_sent_via_database_and_mail_channels(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $user->notify(new SystemNotification(
            title: 'Judul Uji',
            body: 'Isi uji.',
        ));

        Notification::assertSentTo(
            $user,
            SystemNotification::class,
            fn (SystemNotification $notification, array $channels): bool => $channels === ['database', 'mail'],
        );
    }

    public function test_mail_representation_uses_the_notification_title_as_subject(): void
    {
        $user = User::factory()->create();
        $notification = new SystemNotification(title: 'Judul Mail', body: 'Isi mail.');

        $mail = $notification->toMail($user);

        $this->assertSame('Judul Mail', $mail->subject);
    }
}
