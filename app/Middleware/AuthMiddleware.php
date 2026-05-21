<?php

namespace App\Middleware;

use App\Models\User;
use App\Response\JsonResponse;
use Closure;
use Core\Auth\Auth;
use Core\Http\Request;
use Core\Middleware\MiddlewareInterface;
use Core\Valid\Validator;
use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

final class AuthMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->bearerToken()) {
            try {
                if (!env('JWT_KEY')) {
                    throw new Exception('JWT Key not found!.');
                }

                $token = JWT::decode(
                    $request->bearerToken(),
                    new Key(env('JWT_KEY'), env('JWT_ALGO', 'HS256'))
                );

                $user = User::find(intval($token->sub));
                if (!$user->exist()) {
                    throw new Exception('user not found');
                }

                if (!$user->isActive()) {
                    throw new Exception('user not active');
                }

                $user->setAsAdmin();

                Auth::login($user);
            } catch (Exception $e) {
                return (new JsonResponse)->errorBadRequest([$e->getMessage()]);
            }

            return $next($request);
        }
header('Content-Type: application/json');

echo json_encode([
    'server' => $_SERVER,
    'headers' => getallheaders()
], JSON_PRETTY_PRINT);

exit;
        $valid = Validator::make(
            [
                'key' => getallheaders()['X-Access-Key'] ?? null
            ],
            [
                'key' => ['required', 'str', 'trim', 'alpha_num', 'min:49', 'max:225']
            ]
        );

        if ($valid->fails()) {
            return (new JsonResponse)->errorBadRequest($valid->messages());
        }

        $user = User::where('access_key', $valid->key)->limit(1)->first();
        if (!$user->exist()) {
            return (new JsonResponse)->errorBadRequest(['user not found.']);
        }

        if (!$user->isActive()) {
            return (new JsonResponse)->errorBadRequest(['user not active.']);
        }

        $user->setAsNonAdmin();

        Auth::login($user);
        return $next($request);
    }
}
