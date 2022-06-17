<?php

namespace App\Http\Middleware;

use Closure;
use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\ExpiredException;
use App\Libraries\Response;
use App\Libraries\ActiveSession;

class AuthSecretMiddleware
{

    private const ACTOR_ALLOW = ['THANOS', 'ADMIN', 'SECRET'];

    public function handle($request, Closure $next)
    {

        $res = new Response(__METHOD__, $request->all());
        $res->set('AUTH');
        $response = $res->get();

        try {

            $token = $request->header('Authorization');
            $auth = JWT::decode($token, env('JWT_KEY'), ['HS256']);
            if ($auth->iss != env('APP_NAME') || !in_array($auth->actor_code, self::ACTOR_ALLOW)) {
                throw new Exception();
            }

            // Checking Active Session
            if (!ActiveSession::exist($auth->user_id, $token)) {
                throw new Exception();
            }

            $request->merge([
                'auth_actor_code' => $auth->actor_code,
                'auth_user_id' => $auth->user_id,
                'auth_token' => $token
            ]);

        } catch (ExpiredException $e) {

            $res->debug($e->getMessage());
            return response()->json($response['content'], $response['status']);

        } catch (Exception $e) {

            $res->debug($e->getMessage());
            return response()->json($response['content'], $response['status']);

        }

        return $next($request);

    }
}
