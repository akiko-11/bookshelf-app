<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReviewSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $reviews = [
            // 吾輩は猫である
            [
                'isbn' => '9784101010014',
                'email' => 'yamada@example.com',
                'rating' => 5,
                'comment' => '猫の視点から人間社会を描く発想が面白かったです。',
            ],
            [
                'isbn' => '9784101010014',
                'email' => 'suzuki@example.com',
                'rating' => 4,
                'comment' => '独特な語り口で、最後まで楽しく読めました。',
            ],
            [
                'isbn' => '9784101010014',
                'email' => 'tanaka@example.com',
                'rating' => 3,
                'comment' => '昔の表現に少し難しさを感じましたが興味深い作品でした。',
            ],

            // 人を動かす
            [
                'isbn' => '9784422100524',
                'email' => 'sato@example.com',
                'rating' => 5,
                'comment' => '人との接し方について実践的な学びが多かったです。',
            ],
            [
                'isbn' => '9784422100524',
                'email' => 'takahashi@example.com',
                'rating' => 4,
                'comment' => '仕事だけでなく日常生活にも活かせる内容でした。',
            ],
            [
                'isbn' => '9784422100524',
                'email' => 'yamada@example.com',
                'rating' => 4,
                'comment' => '具体例が多く、内容を理解しやすかったです。',
            ],

            // リーダブルコード
            [
                'isbn' => '9784873115658',
                'email' => 'suzuki@example.com',
                'rating' => 5,
                'comment' => '読みやすいコードを書くための考え方がよく分かりました。',
            ],
            [
                'isbn' => '9784873115658',
                'email' => 'tanaka@example.com',
                'rating' => 5,
                'comment' => 'すぐに実践できるテクニックが多く参考になりました。',
            ],
            [
                'isbn' => '9784873115658',
                'email' => 'sato@example.com',
                'rating' => 4,
                'comment' => 'プログラミングを学ぶ人におすすめしたい一冊です。',
            ],

            // 7つの習慣
            [
                'isbn' => '9784863940246',
                'email' => 'takahashi@example.com',
                'rating' => 5,
                'comment' => '自分の行動を見直すきっかけになりました。',
            ],
            [
                'isbn' => '9784863940246',
                'email' => 'yamada@example.com',
                'rating' => 4,
                'comment' => '内容は多いですが、長く役立つ考え方だと思います。',
            ],
            [
                'isbn' => '9784863940246',
                'email' => 'suzuki@example.com',
                'rating' => 4,
                'comment' => '習慣として実践したい内容が多くありました。',
            ],

            // 坊っちゃん
            [
                'isbn' => '9784101010021',
                'email' => 'tanaka@example.com',
                'rating' => 4,
                'comment' => '主人公のまっすぐな性格が印象的でした。',
            ],
            [
                'isbn' => '9784101010021',
                'email' => 'sato@example.com',
                'rating' => 5,
                'comment' => 'テンポが良く、今読んでも楽しめる作品でした。',
            ],
            [
                'isbn' => '9784101010021',
                'email' => 'takahashi@example.com',
                'rating' => 3,
                'comment' => '古典ですが比較的読みやすかったです。',
            ],

            // サピエンス全史
            [
                'isbn' => '9784309226712',
                'email' => 'yamada@example.com',
                'rating' => 5,
                'comment' => '人類の歴史を大きな視点から考えられる本でした。',
            ],
            [
                'isbn' => '9784309226712',
                'email' => 'suzuki@example.com',
                'rating' => 4,
                'comment' => '歴史と科学を組み合わせた説明が興味深かったです。',
            ],
            [
                'isbn' => '9784309226712',
                'email' => 'tanaka@example.com',
                'rating' => 5,
                'comment' => 'これまでとは違う視点で歴史を見ることができました。',
            ],

            // Clean Code
            [
                'isbn' => '9784048930598',
                'email' => 'sato@example.com',
                'rating' => 5,
                'comment' => 'コード品質について深く考えるきっかけになりました。',
            ],
            [
                'isbn' => '9784048930598',
                'email' => 'takahashi@example.com',
                'rating' => 4,
                'comment' => '実務で意識したい原則が多く紹介されています。',
            ],
            [
                'isbn' => '9784048930598',
                'email' => 'yamada@example.com',
                'rating' => 4,
                'comment' => '少し難しい部分もありますが勉強になりました。',
            ],

            // 嫌われる勇気
            [
                'isbn' => '9784478025819',
                'email' => 'suzuki@example.com',
                'rating' => 5,
                'comment' => '対話形式なのでアドラー心理学を理解しやすかったです。',
            ],
            [
                'isbn' => '9784478025819',
                'email' => 'tanaka@example.com',
                'rating' => 4,
                'comment' => '人間関係について考え直すきっかけになりました。',
            ],
            [
                'isbn' => '9784478025819',
                'email' => 'sato@example.com',
                'rating' => 3,
                'comment' => '共感できる部分と難しく感じる部分がありました。',
            ],

            // 火花
            [
                'isbn' => '9784163902302',
                'email' => 'takahashi@example.com',
                'rating' => 4,
                'comment' => '芸人の世界の葛藤がリアルに描かれていました。',
            ],
            [
                'isbn' => '9784163902302',
                'email' => 'yamada@example.com',
                'rating' => 5,
                'comment' => '登場人物の関係性が印象に残る作品でした。',
            ],
            [
                'isbn' => '9784163902302',
                'email' => 'suzuki@example.com',
                'rating' => 4,
                'comment' => '独特の雰囲気があり、一気に読めました。',
            ],

            // FACTFULNESS
            [
                'isbn' => '9784822289607',
                'email' => 'tanaka@example.com',
                'rating' => 5,
                'comment' => 'データを見ることの重要性を改めて感じました。',
            ],
            [
                'isbn' => '9784822289607',
                'email' => 'sato@example.com',
                'rating' => 5,
                'comment' => '思い込みに気づかされる内容が多かったです。',
            ],
            [
                'isbn' => '9784822289607',
                'email' => 'takahashi@example.com',
                'rating' => 4,
                'comment' => '世界を見る視点が変わる興味深い本でした。',
            ],

            // コンテナ物語
            [
                'isbn' => '9784822251468',
                'email' => 'yamada@example.com',
                'rating' => 4,
                'comment' => '物流の変化が世界経済に与えた影響がよく分かりました。',
            ],
            [
                'isbn' => '9784822251468',
                'email' => 'suzuki@example.com',
                'rating' => 4,
                'comment' => '身近なコンテナから経済史を学べて面白かったです。',
            ],
        ];

        foreach ($reviews as $reviewData) {
            $user = User::where('email', $reviewData['email'])->firstOrFail();
            $book = Book::where('isbn', $reviewData['isbn'])->firstOrFail();

            Review::create([
                'user_id' => $user->id,
                'book_id' => $book->id,
                'rating' => $reviewData['rating'],
                'comment' => $reviewData['comment'],
            ]);
        }
    }
}
