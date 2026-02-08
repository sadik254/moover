<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserToken
{
    public function handle(Request $request, Closure $next, ...$allowedTypes): Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        if (! empty($allowedTypes)) {
            $allowedTypes = array_map('strtolower', $allowedTypes);
            $userType = strtolower((string) $user->user_type);

            if (! in_array($userType, $allowedTypes, true)) {
                return response()->json([
                    'message' => 'Unauthorized'
                ], 403);
            }
        }

        return $next($request);
    }
}
