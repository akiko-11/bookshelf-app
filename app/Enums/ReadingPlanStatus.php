<?php

namespace App\Enums;

enum ReadingPlanStatus: string
{
    // 進行中
    case InProgress = 'in_progress';

    // 完了
    case Completed = 'completed';

    // 期限切れ
    case Expired = 'expired';
}
