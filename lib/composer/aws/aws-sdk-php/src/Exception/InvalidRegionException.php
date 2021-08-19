<?php
namespace Aws\Exception;

use Aws\HasMonitoringEventsTrait;
use Aws\MonitoringEventsInterface;

class InvalidRegionException extends \RuntimeException implements
    MonitoringEventsInterface
{
    use HasMonitoringEventsTrait;
}
