<?php

namespace App\Http\Controllers\PublicSite;

use App\Http\Controllers\Controller;
use App\Services\Testimonies\SiteTestimonyWriteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

final class PublicTestimonyController extends Controller
{
    public function __construct(
        private readonly SiteTestimonyWriteService $write,
    ) {}

    public function submit(Request $request): JsonResponse
    {
        $payload = $request->all();
        if ($request->isJson()) {
            $payload = array_merge($payload, $request->json()->all());
        }

        try {
            $result = $this->write->submit($payload, $request->ip());
        } catch (InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Thank you! Your testimony was received and will be reviewed shortly.',
            'id' => $result['id'],
            'status' => $result['status'],
        ]);
    }
}
