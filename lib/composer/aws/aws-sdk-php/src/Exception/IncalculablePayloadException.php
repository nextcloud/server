<?php
namespace Aws\Exception;

use Aws\HasMonitoringEventsTrait;
use Aws\MonitoringEventsInterface;

class IncalculablePayloadException extends \RuntimeException implements
    MonitoringEventsInterface
{
    use HasMonitoringEventsTrait;
}
