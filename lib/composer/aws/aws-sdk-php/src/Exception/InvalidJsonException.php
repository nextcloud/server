<?php
namespace Aws\Exception;

use Aws\HasMonitoringEventsTrait;
use Aws\MonitoringEventsInterface;

class InvalidJsonException extends \RuntimeException implements
    MonitoringEventsInterface
{
    use HasMonitoringEventsTrait;
}
