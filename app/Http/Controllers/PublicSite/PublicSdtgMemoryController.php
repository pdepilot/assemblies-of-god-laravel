<?php

namespace App\Http\Controllers\PublicSite;

use App\Http\Controllers\Controller;
use App\Services\Sdtg\SdtgCommunityWriteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

final class PublicSdtgMemoryController extends Controller
{
    public function __construct(
        private readonly SdtgCommunityWriteService $write,
    ) {}

    public function submit(Request $request): JsonResponse
    {
        $photos = $request->file('photos', $request->file('photo', []));
        if ($photos instanceof \Illuminate\Http\UploadedFile) {
            $photos = [$photos];
        }
        $videos = $request->file('videos', $request->file('video', []));
        if ($videos instanceof \Illuminate\Http\UploadedFile) {
            $videos = [$videos];
        }

        try {
            $result = $this->write->submitPublicMemory(
                $request->all(),
                is_array($photos) ? $photos : [],
                is_array($videos) ? $videos : [],
                $request->ip(),
            );
        } catch (InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Thank you! Your memory was received and will appear after review.',
            'id' => $result['id'],
        ]);
    }
}
