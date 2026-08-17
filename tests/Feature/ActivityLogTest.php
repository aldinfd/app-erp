<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_a_user_is_logged_with_causer_and_subject(): void
    {
        $actor = User::factory()->create();

        $target = User::factory()->create(['name' => 'Target User']);

        $this->assertDatabaseHas('activity_log', [
            'event' => 'created',
            'subject_type' => User::class,
            'subject_id' => $target->id,
        ]);
    }

    public function test_updating_a_user_logs_before_and_after_attributes(): void
    {
        $target = User::factory()->create(['name' => 'Nama Lama']);

        $target->update(['name' => 'Nama Baru']);

        $entry = DB::table('activity_log')
            ->where('event', 'updated')
            ->where('subject_type', User::class)
            ->where('subject_id', $target->id)
            ->first();

        $this->assertNotNull($entry, 'Aktivitas updated tidak tercatat di activity_log.');

        $changes = json_decode((string) $entry->attribute_changes, true);

        $this->assertSame('Nama Lama', $changes['old']['name'] ?? null);
        $this->assertSame('Nama Baru', $changes['attributes']['name'] ?? null);
    }

    public function test_sensitive_attributes_are_not_logged(): void
    {
        $target = User::factory()->create();

        $target->update([
            'name' => 'Nama Baru',
            'password' => 'password-baru',
        ]);

        $entry = DB::table('activity_log')
            ->where('event', 'updated')
            ->where('subject_id', $target->id)
            ->first();

        $this->assertNotNull($entry);

        $this->assertStringNotContainsString(
            'password',
            (string) $entry->attribute_changes,
            'Kolom password tidak boleh tercatat di activity_log.',
        );
    }

    public function test_deleting_a_user_is_logged(): void
    {
        $target = User::factory()->create();

        $target->delete();

        $this->assertDatabaseHas('activity_log', [
            'event' => 'deleted',
            'subject_id' => $target->id,
        ]);
    }
}
