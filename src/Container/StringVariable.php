<?php
declare(strict_types=1);

namespace App\FileServer\Container;

enum StringVariable: string
{

    case DirRoot = __DIR__ . '/../../';

    case AccessToken = 'ACCESS_TOKEN';

    case TargetHost = 'TARGET_HOST';
}
