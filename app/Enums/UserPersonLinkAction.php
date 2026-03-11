<?php

namespace App\Enums;

enum UserPersonLinkAction: string
{
    case AutoMatched = 'auto_matched';
    case ManualLinked = 'manual_linked';
    case ManualRelinked = 'manual_relinked';
    case ManualUnlinked = 'manual_unlinked';
}
