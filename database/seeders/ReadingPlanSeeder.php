<?php

namespace Database\Seeders;

use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class ReadingPlanSeeder extends Seeder
{
    /**
     * 読書計画のテストデータを6件作成する。
     */
    public function run(): void
    {
        // 読書計画を作成するユーザーを取得
        $yamada = User::where('email', 'yamada@example.com')->firstOrFail();
        $suzuki = User::where('email', 'suzuki@example.com')->firstOrFail();

        // 読書計画に使用する書籍を6冊取得
        $books = Book::orderBy('id')
            ->limit(6)
            ->get();

        if ($books->count() < 6) {
            throw new \RuntimeException('読書計画には6冊以上の書籍が必要です。');
        }

        // ① 山田太郎：3日後・進行中（3日前リマインダー対象）
        ReadingPlan::create([
            'user_id' => $yamada->id,
            'book_id' => $books[0]->id,
            'target_date' => Carbon::today()->addDays(3),
            'status' => ReadingPlanStatus::InProgress,
            'completed_at' => null,
        ]);

        // ② 山田太郎：当日・進行中（当日リマインダー対象）
        ReadingPlan::create([
            'user_id' => $yamada->id,
            'book_id' => $books[1]->id,
            'target_date' => Carbon::today(),
            'status' => ReadingPlanStatus::InProgress,
            'completed_at' => null,
        ]);

        // ③ 山田太郎：3日前・進行中（期限切れ更新バッチ対象）
        ReadingPlan::create([
            'user_id' => $yamada->id,
            'book_id' => $books[2]->id,
            'target_date' => Carbon::today()->subDays(3),
            'status' => ReadingPlanStatus::InProgress,
            'completed_at' => null,
        ]);

        // ④ 山田太郎：7日後・進行中（リマインダー対象外）
        ReadingPlan::create([
            'user_id' => $yamada->id,
            'book_id' => $books[3]->id,
            'target_date' => Carbon::today()->addDays(7),
            'status' => ReadingPlanStatus::InProgress,
            'completed_at' => null,
        ]);

        // ⑤ 山田太郎：10日前・完了（5日前に読了）
        ReadingPlan::create([
            'user_id' => $yamada->id,
            'book_id' => $books[4]->id,
            'target_date' => Carbon::today()->subDays(10),
            'status' => ReadingPlanStatus::Completed,
            'completed_at' => Carbon::today()->subDays(5),
        ]);

        // ⑥ 鈴木花子：5日後・進行中（他ユーザー認可テスト用）
        ReadingPlan::create([
            'user_id' => $suzuki->id,
            'book_id' => $books[5]->id,
            'target_date' => Carbon::today()->addDays(5),
            'status' => ReadingPlanStatus::InProgress,
            'completed_at' => null,
        ]);
    }
}
