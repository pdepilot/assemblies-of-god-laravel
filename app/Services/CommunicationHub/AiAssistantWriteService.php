<?php

namespace App\Services\CommunicationHub;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

final class AiAssistantWriteService
{
    /** @return list<array{value: string, label: string}> */
    public function capabilityOptions(): array
    {
        return [
            ['value' => 'birthday', 'label' => 'Generate Birthday Message'],
            ['value' => 'sermon', 'label' => 'Generate Sermon Announcement'],
            ['value' => 'easter', 'label' => 'Generate Easter Greeting'],
            ['value' => 'christmas', 'label' => 'Generate Christmas Message'],
            ['value' => 'event', 'label' => 'Generate Event Invitation'],
            ['value' => 'rewrite', 'label' => 'Rewrite / Improve Tone'],
            ['value' => 'translate', 'label' => 'Translate Message'],
        ];
    }

    /** @return list<string> */
    public function capabilities(): array
    {
        return [
            'Generate Birthday Messages',
            'Generate Sermon Announcements',
            'Generate Easter / Christmas Greetings',
            'Generate Event Invitations',
            'Rewrite / Improve Tone',
            'Translate Messages',
        ];
    }

    /**
     * @param  array{job_type: string, prompt: string}  $data
     * @return array{id: int, status: string, message: string, capabilities: list<string>}
     */
    public function createJob(array $data, int $adminId): array
    {
        if (! Schema::hasTable('ai_message_jobs')) {
            throw new InvalidArgumentException('AI message jobs table is not available.');
        }

        $prompt = trim((string) ($data['prompt'] ?? ''));
        if ($prompt === '') {
            throw new InvalidArgumentException('Prompt is required.');
        }

        $jobType = trim((string) ($data['job_type'] ?? 'generate'));

        $id = (int) DB::table('ai_message_jobs')->insertGetId([
            'job_type' => $jobType,
            'prompt' => $prompt,
            'context_json' => null,
            'status' => 'pending',
            'provider' => 'future',
            'created_by' => $adminId > 0 ? $adminId : null,
            'created_at' => now(),
        ]);

        return [
            'id' => $id,
            'status' => 'pending',
            'message' => 'AI Message Assistant is architected. Provider integration is pending — job queued for future processing.',
            'capabilities' => $this->capabilities(),
        ];
    }
}
