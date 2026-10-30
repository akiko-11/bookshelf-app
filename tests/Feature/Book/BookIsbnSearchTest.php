<?php

namespace Tests\Feature\Book;

use App\Models\User;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BookIsbnSearchTest extends TestCase
{
    use RefreshDatabase;

    // 正しい13桁ISBNで書籍情報を取得できる
    public function test_can_fetch_book_information_by_isbn(): void
    {
        Http::fake([
            'www.googleapis.com/books/v1/volumes*' => Http::response([
                'totalItems' => 1,
                'items' => [
                    [
                        'volumeInfo' => [
                            'title' => 'Laravel入門',
                            'authors' => ['山田太郎', '佐藤花子'],
                            'description' => 'Laravelの解説書',
                            'publishedDate' => '2025-04-15',
                            'imageLinks' => [
                                'thumbnail' => 'https://example.com/book.jpg',
                            ],
                            'industryIdentifiers' => [
                                [
                                    'type' => 'ISBN_13',
                                    'identifier' => '9784101010014',
                                ],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get(route('isbn.index', [
                'isbn' => '9784101010014',
            ]));

        $response->assertOk();
        $response->assertJson([
            'title' => 'Laravel入門',
            'author' => '山田太郎, 佐藤花子',
            'description' => 'Laravelの解説書',
            'image_url' => 'https://example.com/book.jpg',
            'published_date' => '2025-04-15',
        ]);
    }

    // ISBNが13桁未満の場合は422エラーになる
    public function test_isbn_must_be_13_digits(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get(route('isbn.index', [
                'isbn' => '978410101001',
            ]));

        $response->assertStatus(422);
        $response->assertJson([
            'error' => 'ISBNは13桁で入力してください。',
        ]);
    }

    // ISBNが13桁を超える場合は422エラーになる
    public function test_isbn_cannot_exceed_13_digits(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get(route('isbn.index', [
                'isbn' => '97841010100141',
            ]));

        $response->assertStatus(422);
        $response->assertJson([
            'error' => 'ISBNは13桁で入力してください。',
        ]);
    }

    // ISBNが13文字でも数字以外を含む場合は422エラーになる
    public function test_isbn_must_contain_only_digits(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get(route('isbn.index', [
                'isbn' => '97841010100A4',
            ]));

        $response->assertStatus(422);
        $response->assertJson([
            'error' => 'ISBNは13桁の数字で入力してください。',
        ]);
    }

    // Google Books APIの検索結果が0件の場合は404エラーになる
    public function test_returns_404_when_book_is_not_found(): void
    {
        Http::fake([
            'www.googleapis.com/books/v1/volumes*' => Http::response([
                'totalItems' => 0,
            ], 200),
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get(route('isbn.index', [
                'isbn' => '9784101010014',
            ]));

        $response->assertStatus(404);
        $response->assertJson([
            'error' => '書籍情報が見つかりませんでした。',
        ]);
    }

    // Google Books APIが403を返した場合は502エラーになる
    public function test_returns_502_when_google_books_api_fails(): void
    {
        Http::fake([
            'www.googleapis.com/books/v1/volumes*' => Http::response([], 403),
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get(route('isbn.index', [
                'isbn' => '9784101010014',
            ]));

        $response->assertStatus(502);
        $response->assertJson([
            'error' => '書籍情報の取得に失敗しました。',
        ]);
    }

    // Google Books APIが429を返した場合は502エラーになる
    public function test_returns_502_when_google_books_api_is_rate_limited(): void
    {
        Http::fake([
            'www.googleapis.com/books/v1/volumes*' => Http::response([], 429),
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get(route('isbn.index', [
                'isbn' => '9784101010014',
            ]));

        $response->assertStatus(502);
        $response->assertJson([
            'error' => '書籍情報の取得に失敗しました。',
        ]);
    }

    // Google Books APIが500を返した場合は502エラーになる
    public function test_returns_502_when_google_books_api_returns_server_error(): void
    {
        Http::fake([
            'www.googleapis.com/books/v1/volumes*' => Http::response([], 500),
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get(route('isbn.index', [
                'isbn' => '9784101010014',
            ]));

        $response->assertStatus(502);
        $response->assertJson([
            'error' => '書籍情報の取得に失敗しました。',
        ]);
    }

    // Google Books APIへの接続に失敗した場合は502エラーになる
    public function test_returns_502_when_google_books_api_connection_fails(): void
    {
        Http::fake(function () {
            throw new ConnectionException('Connection failed');
        });

        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get(route('isbn.index', [
                'isbn' => '9784101010014',
            ]));

        $response->assertStatus(502);
        $response->assertJson([
            'error' => '書籍情報の取得に失敗しました。',
        ]);
    }

    // Google Books APIとの通信がタイムアウトした場合は504エラーになる
    public function test_returns_504_when_google_books_api_times_out(): void
    {
        Http::fake(function () {
            $guzzleException = new ConnectException(
                'Operation timed out',
                new Request('GET', 'https://www.googleapis.com/books/v1/volumes'),
                null,
                ['errno' => 28]
            );

            throw new ConnectionException('Operation timed out', 0, $guzzleException);
        });

        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get(route('isbn.index', [
                'isbn' => '9784101010014',
            ]));

        $response->assertStatus(504);
        $response->assertJson([
            'error' => '通信がタイムアウトしました。',
        ]);
    }

    // Google Books APIが不正なJSONを返した場合は502エラーになる
    public function test_returns_502_when_google_books_api_returns_invalid_json(): void
    {
        Http::fake([
            'www.googleapis.com/books/v1/volumes*' => Http::response(
                'invalid-json',
                200,
                ['Content-Type' => 'application/json']
            ),
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get(route('isbn.index', [
                'isbn' => '9784101010014',
            ]));

        $response->assertStatus(502);
        $response->assertJson([
            'error' => '書籍情報の取得に失敗しました。',
        ]);
    }

    // Google Books APIのレスポンスにtotalItemsがない場合は502エラーになる
    public function test_returns_502_when_total_items_is_missing(): void
    {
        Http::fake([
            'www.googleapis.com/books/v1/volumes*' => Http::response([
                'items' => [],
            ], 200),
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get(route('isbn.index', [
                'isbn' => '9784101010014',
            ]));

        $response->assertStatus(502);
        $response->assertJson([
            'error' => '書籍情報の取得に失敗しました。',
        ]);
    }

    // Google Books APIのレスポンスにvolumeInfoがない場合は502エラーになる
    public function test_returns_502_when_volume_info_is_missing(): void
    {
        Http::fake([
            'www.googleapis.com/books/v1/volumes*' => Http::response([
                'totalItems' => 1,
                'items' => [
                    [],
                ],
            ], 200),
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get(route('isbn.index', [
                'isbn' => '9784101010014',
            ]));

        $response->assertStatus(502);
        $response->assertJson([
            'error' => '書籍情報の取得に失敗しました。',
        ]);
    }

    // 出版日が年月までしか取得できない場合はnullを返す
    public function test_does_not_return_incomplete_published_date(): void
    {
        Http::fake([
            'www.googleapis.com/books/v1/volumes*' => Http::response([
                'totalItems' => 1,
                'items' => [
                    [
                        'volumeInfo' => [
                            'title' => 'Laravel入門',
                            'publishedDate' => '2025-04',
                            'industryIdentifiers' => [
                                [
                                    'type' => 'ISBN_13',
                                    'identifier' => '9784101010014',
                                ],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get(route('isbn.index', [
                'isbn' => '9784101010014',
            ]));

        $response->assertOk();
        $response->assertJsonPath('published_date', null);
    }

    // 出版日が存在しない日付の場合はnullを返す
    public function test_does_not_return_invalid_published_date(): void
    {
        Http::fake([
            'www.googleapis.com/books/v1/volumes*' => Http::response([
                'totalItems' => 1,
                'items' => [
                    [
                        'volumeInfo' => [
                            'title' => 'Laravel入門',
                            'publishedDate' => '2025-02-30',
                            'industryIdentifiers' => [
                                [
                                    'type' => 'ISBN_13',
                                    'identifier' => '9784101010014',
                                ],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get(route('isbn.index', [
                'isbn' => '9784101010014',
            ]));

        $response->assertOk();
        $response->assertJsonPath('published_date', null);
    }

    // Google Books APIで取得できなかった書籍情報はnullを返す
    public function test_returns_null_for_missing_book_information(): void
    {
        Http::fake([
            'www.googleapis.com/books/v1/volumes*' => Http::response([
                'totalItems' => 1,
                'items' => [
                    [
                        'volumeInfo' => [
                            'title' => 'Laravel入門',
                            'industryIdentifiers' => [
                                [
                                    'type' => 'ISBN_13',
                                    'identifier' => '9784101010014',
                                ],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get(route('isbn.index', [
                'isbn' => '9784101010014',
            ]));

        $response->assertOk();

        $response->assertJson([
            'title' => 'Laravel入門',
            'author' => null,
            'description' => null,
            'image_url' => null,
            'published_date' => null,
        ]);
    }

    // 検索結果のISBNが入力ISBNと一致しない場合は404エラーになる
    public function test_returns_404_when_isbn_does_not_match(): void
    {
        Http::fake([
            'www.googleapis.com/books/v1/volumes*' => Http::response([
                'totalItems' => 1,
                'items' => [
                    [
                        'volumeInfo' => [
                            'title' => '別の書籍',
                            'industryIdentifiers' => [
                                [
                                    'type' => 'ISBN_13',
                                    'identifier' => '9784873115658',
                                ],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get(route('isbn.index', [
                'isbn' => '9784101010014',
            ]));

        $response->assertStatus(404);

        $response->assertJson([
            'error' => '書籍情報が見つかりませんでした。',
        ]);
    }

    // 複数の検索結果から入力ISBNと一致する書籍情報を取得できる
    public function test_fetches_matching_book_from_multiple_results(): void
    {
        Http::fake([
            'www.googleapis.com/books/v1/volumes*' => Http::response([
                'totalItems' => 2,
                'items' => [
                    [
                        'volumeInfo' => [
                            'title' => '別の書籍',
                            'industryIdentifiers' => [
                                [
                                    'type' => 'ISBN_13',
                                    'identifier' => '9784873115658',
                                ],
                            ],
                        ],
                    ],
                    [
                        'volumeInfo' => [
                            'title' => 'Laravel入門',
                            'industryIdentifiers' => [
                                [
                                    'type' => 'ISBN_13',
                                    'identifier' => '9784101010014',
                                ],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get(route('isbn.index', [
                'isbn' => '9784101010014',
            ]));

        $response->assertOk();

        $response->assertJsonPath('title', 'Laravel入門');
    }
}
