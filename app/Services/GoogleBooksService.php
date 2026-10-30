<?php

namespace App\Services;

use App\Exceptions\GoogleBooksException;
use GuzzleHttp\Exception\ConnectException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class GoogleBooksService
{
    /**
     * ISBNから書籍情報を取得する。
     */
    public function search(string $isbn): array
    {
        try {
            // Google Books APIにリクエストを送信
            $response = Http::timeout(10)->get(
                'https://www.googleapis.com/books/v1/volumes',
                [
                    'q' => $isbn,
                    'key' => config('services.google_books.key'),
                ]
            );
        } catch (ConnectionException $e) {
            // 元の通信例外を取得
            $previous = $e->getPrevious();

            // cURLのエラー番号28（タイムアウト）か判定
            $isTimeout = $previous instanceof ConnectException
                && ($previous->getHandlerContext()['errno'] ?? null) === 28;

            // タイムアウトとその他の接続失敗を区別する
            throw new GoogleBooksException(
                $isTimeout ? 'timeout' : 'connection_failure',
                previous: $e
            );
        }

        // APIがエラーステータスを返した場合
        if ($response->failed()) {
            throw new GoogleBooksException('api_failure');
        }

        $data = $response->json();

        // JSONの形式を確認
        if (! is_array($data)) {
            throw new GoogleBooksException('api_failure');
        }

        // 検索結果件数の形式を確認
        if (! is_int($data['totalItems'] ?? null)) {
            throw new GoogleBooksException('api_failure');
        }

        // 検索結果が0件の場合
        if ($data['totalItems'] === 0) {
            throw new GoogleBooksException('book_not_found');
        }

        $items = $data['items'] ?? null;

        // 検索結果の構造を確認
        if (! is_array($items) || $items === []) {
            throw new GoogleBooksException('api_failure');
        }

        $volumeInfo = null;

        // 検索結果を1件ずつ確認
        foreach ($items as $item) {
            $info = $item['volumeInfo'] ?? null;

            if (! is_array($info)) {
                throw new GoogleBooksException('api_failure');
            }

            $identifiers = $info['industryIdentifiers'] ?? [];

            if (! is_array($identifiers)) {
                throw new GoogleBooksException('api_failure');
            }

            foreach ($identifiers as $identifier) {
                if (! is_array($identifier)) {
                    throw new GoogleBooksException('api_failure');
                }

                // ISBN-13が入力値と一致する書籍を採用
                if (($identifier['type'] ?? null) === 'ISBN_13'
                    && ($identifier['identifier'] ?? null) === $isbn) {
                    $volumeInfo = $info;
                    break 2;
                }
            }
        }

        // 入力ISBNと一致する書籍がない場合
        if ($volumeInfo === null) {
            throw new GoogleBooksException('book_not_found');
        }

        // 出版日を取得
        $publishedDate = $volumeInfo['publishedDate'] ?? null;

        // YYYY-MM-DD形式かつ実在する日付か確認
        if (! is_string($publishedDate)
            || ! preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $publishedDate, $matches)
            || ! checkdate((int) $matches[2], (int) $matches[3], (int) $matches[1])) {
            // 不正・不完全な日付は返さない
            $publishedDate = null;
        }

        // フォームの自動入力に必要な情報を返す
        return [
            'title' => is_string($volumeInfo['title'] ?? null)
                ? $volumeInfo['title']
                : null,

            'author' => is_array($volumeInfo['authors'] ?? null)
                ? implode(', ', array_filter($volumeInfo['authors'], 'is_string'))
                : null,

            'description' => is_string($volumeInfo['description'] ?? null)
                ? $volumeInfo['description']
                : null,

            'image_url' => is_string($volumeInfo['imageLinks']['thumbnail'] ?? null)
                ? $volumeInfo['imageLinks']['thumbnail']
                : null,

            'published_date' => $publishedDate,
        ];
    }
}
