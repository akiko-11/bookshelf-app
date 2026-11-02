<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\View\View;

class NotificationController extends Controller
{
    /**
     * ログインユーザーの通知一覧を表示する。
     *
     * ログインユーザー自身の通知を作成日時の降順で取得し、
     * 通知一覧画面へ渡す。
     *
     * @param  Request  $request  ログインユーザーを取得するためのリクエスト
     * @return View 通知一覧画面
     */
    public function index(Request $request): View
    {
        $notifications = $request->user()
            ->notifications()
            ->orderByDesc('created_at')
            ->get();

        return view('notifications.index', [
            'notifications' => $notifications,
        ]);
    }

    /**
     * 指定された通知を既読にする。
     *
     * 通知の存在と所有者を確認し、未読の場合のみ既読日時を設定する。
     * 既読済みの場合は既読状態を維持する。
     *
     * @param  Request  $request  ログインユーザーを取得するためのリクエスト
     * @param  string  $notification  既読化対象の通知ID
     * @return RedirectResponse 通知一覧画面へのリダイレクト
     */
    public function markAsRead(
        Request $request,
        string $notification
    ): RedirectResponse {
        // 通知IDから対象を取得
        $notificationRecord = DatabaseNotification::find($notification);

        // 存在しない通知は404
        if ($notificationRecord === null) {
            abort(404, '通知が見つかりません。');
        }

        // 他人の通知は403
        $user = $request->user();

        if (
            $notificationRecord->notifiable_type !== $user->getMorphClass()
            || (string) $notificationRecord->notifiable_id !== (string) $user->getKey()
        ) {
            abort(403, 'この通知を操作する権限がありません。');
        }

        // 未読の場合のみ既読にする
        if ($notificationRecord->read_at === null) {
            $notificationRecord->markAsRead();
        }

        return redirect()
            ->route('notifications.index')
            ->with('success', '通知を既読にしました。');
    }
}
