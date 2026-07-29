<?php

namespace App\Http\Controllers\PublicSite;

use App\Http\Controllers\Controller;
use App\Services\Contact\ContactSubmissionWriteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

final class PublicContactController extends Controller
{
    public function __construct(
        private readonly ContactSubmissionWriteService $write,
    ) {}

    public function submit(Request $request): JsonResponse
    {
        $action = (string) $request->input('action', 'submit');
        if ($action !== 'submit') {
            return response()->json(['success' => false, 'message' => 'Unknown action.'], 400);
        }

        try {
            $submission = $this->write->submitInquiry(
                $request->all(),
                $request->ip(),
                (string) $request->userAgent(),
            );
        } catch (InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Thank you! Your message has been received. Our team will respond with care.',
            'reference' => $submission['submission_code'],
            'inquiry_type' => $submission['inquiry_type'],
        ]);
    }
}
