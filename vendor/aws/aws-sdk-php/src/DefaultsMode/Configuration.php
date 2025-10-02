<?php
namespace Aws\DefaultsMode;

use Aws\DefaultsMode\Exception\ConfigurationException;

class Configuration implements ConfigurationInterface
{
    private $mode;
    private $retryMode;
    private $stsRegionalEndpoints;
    private $s3UsEast1RegionalEndpoints;
    private $connectTimeoutInMillis;
    private $httpRequestTimeoutInMillis;
    private $validModes = [
        'legacy',
        'standard',
        'cross-region',
        'in-region',
        'mobile',
        'auto',
    ];

    public function __construct($mode = 'legacy')
    {
        $mode = strtolower($mode);
        if (!in_array($mode, $this->validModes)) {
            throw new \InvalidArgumentException("'{$mode}' is not a valid mode."
                . " The mode has to be 'legacy', 'standard', 'cross-region', 'in-region',"
                . " 'mobile', or 'auto'.");
        }

        $this->mode = $mode;
        if ($this->mode == 'legacy') {
            return;
        }

        $data = \Aws\load_compiled_json(
            __DIR__ . '/../data/sdk-default-configuration.json'
        );

        $this->retryMode = $data['base']['retryMode'];
        $this->stsRegionalEndpoints = $data['base']['stsRegionalEndpoints'];
        $this->s3UsEast1RegionalEndpoints = $data['base']['s3UsEast1RegionalEndpoints'];
        $this->connectTimeoutInMillis = $data['base']['connectTimeoutInMillis'];

        if (isset($data['modes'][$mode])) {
            $modeData = $data['modes'][$mode];
            foreach ($modeData as $settingName => $settingValue) {
                if (isset($this->$settingName)) {
                    if (isset($settingValue['override'])) {
                        $this->$settingName = $settingValue['override'];
                    } else if (isset($settingValue['multiply'])) {
                        $this->$settingName *= $settingValue['multiply'];
                    } else if (isset($settingValue['add'])) {
                        $this->$settingName += $settingValue['add'];
                    }
                } else {
                    if (isset($settingValue['override'])) {
                        if (property_exists($this, $settingName)) {
                            $this->$settingName = $settingValue['override'];
                        }
                    }
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getMode()
    {
        return $this->mode;
    }

    /**
     * {@inheritdoc}
     */
    public function getRetryMode()
    {
        return $this->retryMode;
    }

    /**
     * {@inheritdoc}
     */
    public function getStsRegionalEndpoints()
    {
        return $this->stsRegionalEndpoints;
    }

    /**
     * {@inheritdoc}
     */
    public function getS3UsEast1RegionalEndpoints()
    {
        return $this->s3UsEast1RegionalEndpoints;
    }

    /**
     * {@inheritdoc}
     */
    public function getConnectTimeoutInMillis()
    {
        return $this->connectTimeoutInMillis;
    }

    /**
     * {@inheritdoc}
     */
    public function getHttpRequestTimeoutInMillis()
    {
        return $this->httpRequestTimeoutInMillis;
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        return [
            'mode'                                       => $this->getMode(),
            'retry_mode'                                 => $this->getRetryMode(),
            'sts_regional_endpoints'                     => $this->getStsRegionalEndpoints(),
            's3_us_east_1_regional_endpoint'             => $this->getS3UsEast1RegionalEndpoints(),
            'connect_timeout_in_milliseconds'            => $this->getConnectTimeoutInMillis(),
            'http_request_timeout_in_milliseconds'       => $this->getHttpRequestTimeoutInMillis(),
        ];
    }

}
