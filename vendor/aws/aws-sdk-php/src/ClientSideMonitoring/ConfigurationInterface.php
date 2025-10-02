<?php
namespace Aws\ClientSideMonitoring;

/**
 * Provides access to client-side monitoring configuration options:
 * 'client_id', 'enabled', 'host', 'port'
 */
interface ConfigurationInterface
{
    /**
     * Checks whether or not client-side monitoring is enabled.
     *
     * @return bool
     */
    public function isEnabled();

    /**
     * Returns the Client ID, if available.
     *
     * @return string|null
     */
    public function getClientId();

    /**
     * Returns the configured host.
     *
     * @return string|null
     */
    public function getHost();

    /**
     * Returns the configured port.
     *
     * @return int|null
     */
    public function getPort();

    /**
     * Returns the configuration as an associative array.
     *
     * @return array
     */
    public function toArray();
}
