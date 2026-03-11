<?php

namespace App\Enums;

enum MemberImportRowStatus: string
{
    case ExactMatch = 'exact_match';
    case PossibleMatch = 'possible_match';
    case NewPerson = 'new_person';
    case Ignored = 'ignored';
    case Applied = 'applied';
    case Failed = 'failed';
}
