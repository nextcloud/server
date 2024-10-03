<?php
namespace OCA\SocialLogin\Migration;

use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;
use OCP\IConfig;

class TelegramToProviders implements IRepairStep
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
        return 'Move telegram config to comman providers';
    }

    public function run(IOutput $output)
    {
        if (version_compare($this->config->getAppValue($this->appName, 'installed_version'), '3.1.1') > 0) {
            return;
        }
        $providers = json_decode($this->config->getAppValue($this->appName, 'oauth_providers'), true) ?: [];
        $providers['telegram'] = [
            'appid' => $this->config->getAppValue($this->appName, 'tg_bot'),
            'secret' => $this->config->getAppValue($this->appName, 'tg_token'),
            'defaultGroup' => $this->config->getAppValue($this->appName, 'tg_group'),
        ];
        $this->config->setAppValue($this->appName, 'oauth_providers', json_encode($providers));
        $this->config->deleteAppValue($this->appName, 'tg_bot');
        $this->config->deleteAppValue($this->appName, 'tg_token');
        $this->config->deleteAppValue($this->appName, 'tg_group');
    }
}
