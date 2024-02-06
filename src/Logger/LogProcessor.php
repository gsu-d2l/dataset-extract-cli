<?php

declare(strict_types=1);

namespace GSU\D2L\DataHub\Extract\Logger;

use mjfklib\Logger\Processor\ElapsedTimeProcessor;
use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

class LogProcessor extends ElapsedTimeProcessor implements ProcessorInterface
{
    /**
     * @param LogRecord $record
     * @return LogRecord
     */
    public function __invoke(LogRecord $record): LogRecord
    {
        $record = parent::__invoke($record);
        $record->extra['pid'] = getmypid();
        return $record;
    }
}
