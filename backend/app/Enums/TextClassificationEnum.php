<?php

namespace App\Enums;
enum TextClassificationEnum: int
{
    case positive = 1;
    case negative = 2;
    case neutral = 3;
    case meaningless = 4;
}
