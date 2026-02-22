<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\Integrations\HokesenAssertionService;
use App\Services\Integrations\InvalidHokesenAssertionException;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateHokesenAssertion
{
    public function __construct(
        private readonly HokesenAssertionService $assertionService
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();
        if (! is_string($token) || trim($token) === '') {
            return $this->authenticationError('missing_token', 'Missing bearer assertion token.');
        }

        try {
            $claims = $this->assertionService->validate($token);
        } catch (InvalidHokesenAssertionException $exception) {
            Log::warning('Rejected hokesen assertion', [
                'reason' => $exception->reason(),
                'path' => $request->path(),
            ]);

            return $this->authenticationError($exception->reason(), 'Invalid integration assertion.');
        }

        $user = User::query()
            ->whereRaw('LOWER(email) = ?', [$claims['email']])
            ->first();

        if (! $user || $user->email_verified_at === null) {
            return response()->json([
                'error' => [
                    'code' => 'user_not_linked_or_unverified',
                    'message' => 'No matching verified user found.',
                ],
            ], 403);
        }

        $request->attributes->set('hokesen.claims', $claims);
        $request->attributes->set('hokesen.user', $user);

        return $next($request);
    }

    private function authenticationError(string $code, string $message): JsonResponse
    {
        return response()->json([
            'error' => [
                'code' => $code,
                'message' => $message,
            ],
        ], 401);
    }
}
