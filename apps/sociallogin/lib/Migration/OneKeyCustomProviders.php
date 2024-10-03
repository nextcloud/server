<?php
namespace OCA\SocialLogin\Migration;

use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;
use OCP\IConfig;

class OneKeyCustomProviders implements IRepairStep
{
    /** @var IConfig */
    private $config;

    private $appName = 'sociallogin';

    public function __construct(IConfig $config)
    {
        $this->config = $config;
    }

    public function getName()
    {
        return 'Migrate custom providers to one config key';
    }

    public function run(IOutput $output)
    {
        if (version_compare($this->config->getAppValue($this->appName, 'installed_version'), '3.1.0') > 0) {
            return;
        }
        $customProviders = json_decode($this->config->getAppValue($this->appName, 'custom_providers'), true) ?: [];
        $customProvidersNames = ['openid', 'custom_oidc', 'custom_oauth2'];
        foreach ($customProvidersNames as $providerName) {
            $configKey = $providerName.'_providers';
            $providers = json_decode($this->config->getAppValue($this->appName, $configKey), true);
            if (!empty($providers)) {
                $customProviders[$providerName] = $providers;
            }
            $this->config->deleteAppValue($this->appName, $configKey);
        }
        $this->config->setAppValue($this->appName, 'custom_providers', json_encode($customProviders));
    }
}
