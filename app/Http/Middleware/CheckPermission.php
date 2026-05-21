<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $parts = explode('/', $permission);
        $module = $parts[0] ?? '';
        $action = $parts[1] ?? '';

        if (empty($module) || empty($action)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid permission format.',
            ], 400);
        }

        if ($user instanceof \App\Models\Customer) {
            return response()->json([
                'success' => false,
                'message' => 'Customers do not have permission to perform this action.',
            ], 403);
        }

        if (!$user->hasPermission($module, $action)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to perform this action.',
            ], 403);
        }

        return $next($request);
    }
}
