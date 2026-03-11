<?php

namespace App\Enums;

enum MemberImportBatchStatus: string
{
    case Uploaded = 'uploaded';
    case Staged = 'staged';
    case Applied = 'applied';
    case Failed = 'failed';
}
