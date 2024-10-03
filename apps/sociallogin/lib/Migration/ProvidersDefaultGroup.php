<?php
namespace OCA\SocialLogin\Migration;

use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;
use OCP\IConfig;

class ProvidersDefaultGroup implements IRepairStep
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
        return 'Set default group in each provider';
    }

    public function run(IOutput $output)
    {
        if (version_compare($this->config->getAppValue($this->appName, 'installed_version'), '1.15.1') >= 0) {
            return;
        }

        $defaultGroup = $this->config->getAppValue($this->appName, 'new_user_group');

        $this->setProvidersGroup('oauth_providers', $defaultGroup);
        $this->setProvidersGroup('openid_providers', $defaultGroup);
        $this->setProvidersGroup('custom_oidc_providers', $defaultGroup);
        $this->setProvidersGroup('custom_oauth2_providers', $defaultGroup);

        if ($defaultGroup) {
            $this->config->setAppValue($this->appName, 'tg_group', $defaultGroup);
        }

        $this->config->deleteAppValue($this->appName, 'new_user_group');
    }

    private function setProvidersGroup($configKey, $defaultGroup)
    {
        $providers = json_decode($this->config->getAppValue($this->appName, $configKey), true);
        if (is_array($providers)) {
            foreach ($providers as &$provider) {
                if (!isset($provider['defaultGroup'])) {
                    $provider['defaultGroup'] = $defaultGroup;
                }
            }
            $this->config->setAppValue($this->appName, $configKey, json_encode($providers));
        }
    }
}
