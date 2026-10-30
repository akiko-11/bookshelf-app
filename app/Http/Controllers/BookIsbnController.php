<?php

namespace App\Http\Controllers;

use App\Exceptions\GoogleBooksException;
use App\Services\GoogleBooksService;
use Illuminate\Http\JsonResponse;

class BookIsbnController extends Controller
{
    /**
     * ISBNから書籍情報を取得し、JSONレスポンスを返す。
     *
     * ISBNの形式を検証し、Google Books APIから書籍情報を取得する。
     * エラー時は適切なHTTPステータスとメッセージを返す。
     *
     * @param  string  $isbn  検索対象の13桁ISBN
     * @param  GoogleBooksService  $googleBooksService  書籍情報取得サービス
     * @return JsonResponse 書籍情報またはエラーメッセージ
     */
    public function index(string $isbn, GoogleBooksService $googleBooksService): JsonResponse
    {
        if (strlen($isbn) !== 13) {
            return response()->json([
                'error' => 'ISBNは13桁で入力してください。',
            ], 422);
        } elseif (! ctype_digit($isbn)) {
            return response()->json([
                'error' => 'ISBNは13桁の数字で入力してください。',
            ], 422);
        }

        try {
            $bookData = $googleBooksService->search($isbn);

            return response()->json($bookData);

        } catch (GoogleBooksException $e) {
            // 以降、Serviceから投げられた例外処理

            if ($e->getMessage() === 'book_not_found') {
                return response()->json([
                    'error' => '書籍情報が見つかりませんでした。',
                ], 404);
            }

            if ($e->getMessage() === 'timeout') {
                return response()->json([
                    'error' => '通信がタイムアウトしました。',
                ], 504);
            }

            return response()->json([
                'error' => '書籍情報の取得に失敗しました。',
            ], 502);
        }
    }
}
