<?php
namespace Aws\EndpointDiscovery\Exception;

use Aws\HasMonitoringEventsTrait;
use Aws\MonitoringEventsInterface;

/**
 * Represents an error interacting with configuration for endpoint discovery
 */
class ConfigurationException extends \RuntimeException implements
    MonitoringEventsInterface
{
    use HasMonitoringEventsTrait;
}
