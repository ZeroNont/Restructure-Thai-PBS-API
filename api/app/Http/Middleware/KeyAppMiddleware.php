<?php

namespace App\Http\Middleware;

use Closure;
use Exception;
use App\Libraries\Response;

class KeyAppMiddleware
{

    public function handle($request, Closure $next)
    {

        if ($request->header('X-API-Key') !== env('API_KEY')) {
            $res = new Response(__METHOD__, $request->all());
            $res->set('AUTH');
            $response = $res->get();
            return response()->json($response['content'], $response['status']);
        }

        return $next($request);

    }
}
