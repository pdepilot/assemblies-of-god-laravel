<?php

namespace App\Jobs;

use App\Services\Contact\ContactMailService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

final class SendContactAcknowledgementJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    /** @param  array<string, mixed>  $submission */
    public function __construct(
        public readonly array $submission,
    ) {}

    public function handle(ContactMailService $mail): void
    {
        $mail->sendAcknowledgement($this->submission);
    }
}
