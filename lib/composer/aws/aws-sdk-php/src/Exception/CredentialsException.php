<?php
namespace Aws\Exception;

use Aws\HasMonitoringEventsTrait;
use Aws\MonitoringEventsInterface;

class CredentialsException extends \RuntimeException implements
    MonitoringEventsInterface
{
    use HasMonitoringEventsTrait;
}
