<?php

namespace App\Enums;

enum RelationshipType: string
{
    case Spouse = 'spouse';
    case Parent = 'parent';
    case Child = 'child';
    case Guardian = 'guardian';
    case Sibling = 'sibling';
    case Grandparent = 'grandparent';
    case Grandchild = 'grandchild';
    case CareContact = 'care_contact';
    case OtherFamily = 'other_family';
}
