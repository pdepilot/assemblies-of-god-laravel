<?php

namespace App\Http\Controllers\RegistrationPortals;

use App\Services\RegistrationPortals\PublicRegistrationSubmitService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

final class PublicRegistrationSubmitController
{
    public function __construct(
        private readonly PublicRegistrationSubmitService $submit,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $slug = trim((string) $request->input('slug', ''));
            if ($slug === '') {
                throw new InvalidArgumentException('Event is required.');
            }

            $result = $this->submit->submit(
                $slug,
                $request->all(),
                $request->allFiles(),
                $request->ip(),
                (string) $request->userAgent(),
            );
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Registration successful!',
            'registrant' => [
                'registration_number' => $result['registration_number'],
                'full_name' => $result['full_name'],
                'status' => $result['status'],
            ],
        ]);
    }
}
