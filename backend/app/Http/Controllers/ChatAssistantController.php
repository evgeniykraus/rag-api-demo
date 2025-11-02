<?php

namespace App\Http\Controllers;

use App\Services\ChatAssistantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * @group ChatAssistant
 */
class ChatAssistantController extends Controller
{
    public function __construct(protected ChatAssistantService $chatAssistantService)
    {
    }

    /**
     * @param Request $request
     * @return StreamedResponse
     */
    public function chat(Request $request)
    {
        $message = $request->input('message');
        $sessionId = $request->getSession()->getId();

        return $this->chatAssistantService->chat($sessionId, $message);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function history(Request $request)
    {
        $sessionId = $request->getSession()->getId();

        return response()->json(
            $this->chatAssistantService->history($sessionId)
        );
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function clearHistory(Request $request)
    {
        $sessionId = $request->getSession()->getId();
        $this->chatAssistantService->clearHistory($sessionId);

        return response()->json([
            'success' => true,
            'message' => 'History cleared successfully',
        ]);
    }
}
