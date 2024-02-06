<?php

declare(strict_types=1);

namespace GSU\D2L\DataHub\Extract\Repository;

enum ExtractPathType
{
    case Extract;
    case Download;
    case Index;
    case FullDiff;
    case Process;
    case Upload;
}
