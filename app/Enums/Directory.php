<?php

namespace App\Enums;

use App\Traits\EnumTrait;

enum Directory: string
{
    use EnumTrait;

    case USER_IMAGES = 'users';
    case IMAGES = 'images';
    case DOCUMENTS = 'documents';
    case PRODUCTS = 'products';
    case CATEGORIES = 'categories';
    case MACHINERY = 'machinery';
}
