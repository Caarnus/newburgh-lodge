<?php

namespace App\Helpers;

class AppConstants
{
    public const JEOPARDY_CATEGORY_NUM = 5;
    public const JEOPARDY_QUESTION_PER_CATEGORY = 8;
    public const SCHOLARSHIP_APPLICATION_REVIEW_SCORE_MIN = 0;
    public const SCHOLARSHIP_APPLICATION_REVIEW_SCORE_MAX = 10;
    public const SCHOLARSHIP_APPLICATION_STATUS_PENDING = ['label' => 'Pending Verification', 'value' => 'pending_verification'];
    public const SCHOLARSHIP_APPLICATION_STATUS_NEW = ['label' => 'New', 'value' => 'new'];
    public const SCHOLARSHIP_APPLICATION_STATUS_REVIEW = ['label' => 'In Review', 'value' => 'in_review'];
    public const SCHOLARSHIP_APPLICATION_STATUS_FINALIST = ['label' => 'Finalist', 'value' => 'finalist'];
    public const SCHOLARSHIP_APPLICATION_STATUS_AWARDED = ['label' => 'Awarded', 'value' => 'awarded'];
    public const SCHOLARSHIP_APPLICATION_STATUS_DECLINED = ['label' => 'Declined', 'value' => 'declined'];
    public const SCHOLARSHIP_APPLICATION_STATUS_LIST = [
        self::SCHOLARSHIP_APPLICATION_STATUS_PENDING['value'],
        self::SCHOLARSHIP_APPLICATION_STATUS_NEW['value'],
        self::SCHOLARSHIP_APPLICATION_STATUS_REVIEW['value'],
        self::SCHOLARSHIP_APPLICATION_STATUS_FINALIST['value'],
        self::SCHOLARSHIP_APPLICATION_STATUS_AWARDED['value'],
        self::SCHOLARSHIP_APPLICATION_STATUS_DECLINED['value'],
    ];
}
