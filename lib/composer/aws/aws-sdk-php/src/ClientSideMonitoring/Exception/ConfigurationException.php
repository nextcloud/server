<?php
namespace Aws\ClientSideMonitoring\Exception;

use Aws\HasMonitoringEventsTrait;
use Aws\MonitoringEventsInterface;


/**
 * Represents an error interacting with configuration for client-side monitoring.
 */
class ConfigurationException extends \RuntimeException implements
    MonitoringEventsInterface
{
    use HasMonitoringEventsTrait;
}
