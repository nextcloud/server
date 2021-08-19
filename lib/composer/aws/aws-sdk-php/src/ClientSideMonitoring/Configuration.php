<?php
namespace Aws\ClientSideMonitoring;

class Configuration implements ConfigurationInterface
{
    private $clientId;
    private $enabled;
    private $host;
    private $port;

    /**
     * Constructs a new Configuration object with the specified CSM options set.
     *
     * @param mixed $enabled
     * @param string $host
     * @param string|int $port
     * @param string $clientId
     */
    public function __construct($enabled, $host, $port, $clientId = '')
    {
        $this->host = $host;
        $this->port = filter_var($port, FILTER_VALIDATE_INT);
        if ($this->port === false) {
            throw new \InvalidArgumentException(
                "CSM 'port' value must be an integer!");
        }

        // Unparsable $enabled flag errors on the side of disabling CSM
        $this->enabled = filter_var($enabled, FILTER_VALIDATE_BOOLEAN);
        $this->clientId = trim($clientId);
    }

    /**
     * {@inheritdoc}
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    /**
     * {@inheritdoc}
     */
    public function getClientId()
    {
        return $this->clientId;
    }

    /**
     * /{@inheritdoc}
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * {@inheritdoc}
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        return [
            'client_id' => $this->getClientId(),
            'enabled' => $this->isEnabled(),
            'host' => $this->getHost(),
            'port' => $this->getPort()
        ];
    }
}