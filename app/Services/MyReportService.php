<?php

namespace App\Services;

use App\Models\ReadingPlan;
use App\Models\Review;
use App\Models\User;

class MyReportService
{
    /**
     * 指定ユーザーの読書・レビュー統計情報を集計する。
     *
     * 総レビュー数・平均評価・読了冊数・評価分布、
     * 高評価書籍TOP5・ジャンル別評価傾向TOP5を取得する。
     *
     * @param  User  $user  集計対象のユーザー
     * @return array<string, mixed> 読書レポートの統計情報
     */
    public function getStatsForUser(User $user): array
    {
        // 総レビュー数を取得
        $totalReviews = Review::where('user_id', $user->id)
            ->count();

        // 平均評価を取得
        $avgReviews = Review::where('user_id', $user->id)
            ->avg('rating') ?? 0;

        // 読了済みの読書計画を取得
        $booksReadCount = ReadingPlan::where('user_id', $user->id)
            ->whereNotNull('completed_at')
            ->get()
            ->unique('book_id')
            ->count();

        $reviews = Review::where('user_id', $user->id)->get();

        $groupedReviews = $reviews->groupBy('rating');

        // 評価分布を取得
        $ratingDistribution = collect([1, 2, 3, 4, 5])
            ->map(function ($rating) use ($groupedReviews) {
                return $groupedReviews->get($rating, collect())->count();
            });

        // 高評価書籍TOP5の取得
        $top5RatedBooks = Review::where('user_id', $user->id)
            ->with('book')
            ->where('rating', '>=', 4)
            ->orderByDesc('rating')
            ->orderBy('id')
            ->limit(5)
            ->get();

        $top5RatedBooks = $top5RatedBooks->map(function ($review) {
            return [
                'id' => $review->book->id,
                'title' => $review->book->title,
                'author' => $review->book->author,
                'rating' => $review->rating,
            ];
        });

        $topRatedGenres = Review::where('user_id', $user->id)
            ->with('book.genres')
            ->get();

        // ジャンル別評価傾向TOP5の取得
        $genreReviews = $topRatedGenres->flatMap(function ($review) {
            return $review->book->genres->map(function ($genre) use ($review) {
                return [
                    'genre' => $genre,
                    'rating' => $review->rating,
                ];
            });
        });

        $groupedGenres = $genreReviews->groupBy('genre.id');

        $genreRatings = $groupedGenres->map(function ($reviews, $genreId) {
            return [
                'id' => $genreId,
                'name' => $reviews->first()['genre']->name,
                'count' => $reviews->count(),
                'average_rating' => $reviews->avg('rating') ?? 0,
            ];
        });

        $genreRatings = $genreRatings->sortBy([
            ['average_rating', 'desc'],
            ['count', 'desc'],
        ])->take(5)->values();

        $stats = [
            'summary' => [
                'total_reviews' => $totalReviews,
                'average_rating' => $avgReviews,
                'books_read' => $booksReadCount,
            ],
            'rating_distribution' => $ratingDistribution,
            'top_rated_books' => $top5RatedBooks,
            'genre_ratings' => $genreRatings,
        ];

        return $stats;
    }
}
