<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Support\Zfy\ExtensionRegistry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ExtensionController extends Controller
{
    public function __invoke(Request $request, string $endpoint, ExtensionRegistry $registry)
    {
        $definition = $registry->get('api', $endpoint);
        abort_unless($definition, 404);
        abort_unless(in_array($request->method(), $definition['methods'], true), 405);
        $user = $request->user('sanctum');
        if (! $definition['public']) {
            abort_unless($user, 401);
            abort_if($user->is_banned, 403);
            abort_unless($user->can($definition['permission']), 403);
            abort_unless($user->tokenCan($definition['ability']), 403);
        }
        $validated = Validator::make($request->all(), $definition['rules'][$request->method()] ?? [])->validate();
        $result = ($definition['handler'])($request, $validated);

        return response()->json(['code' => 0, 'message' => 'ok', 'data' => $result])->header('Cache-Control', 'private, no-store');
    }
}
