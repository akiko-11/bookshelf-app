<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        // 401：認証エラー
        $this->renderable(function (
            AuthenticationException $e,
            Request $request
        ) {
            if ($request->is('api/v1/books', 'api/v1/books/*')
                && in_array($request->method(), ['POST', 'PUT', 'DELETE'], true)) {
                return response()->json([
                    'message' => '認証が必要です。',
                ], 401);
            }
        });

        // 403：認可エラー
        $this->renderable(function (
            AccessDeniedHttpException $e,
            Request $request
        ) {
            if ($request->is('api/v1/books/*')
                && in_array($request->method(), ['PUT', 'DELETE'], true)) {
                return response()->json([
                    'message' => 'この書籍を操作する権限がありません。',
                ], 403);
            }
        });
    }
}
