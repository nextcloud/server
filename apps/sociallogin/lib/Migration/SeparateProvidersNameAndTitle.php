<?php
namespace OCA\SocialLogin\Migration;

use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;
use OCP\IDBConnection;
use OCP\IConfig;

class SeparateProvidersNameAndTitle implements IRepairStep
{
    /** @var IConfig */
    private $config;

    /** @var IDBConnection */
    private $db;

    private $appName = 'sociallogin';

    public function __construct(IConfig $config, IDBConnection $db)
    {
        $this->config = $config;
        $this->db = $db;
    }

    public function getName()
    {
        return 'Separate user configured providers internal name and title. Also removes old unnecessary user config.';
    }

    public function run(IOutput $output)
    {
        if (version_compare($this->config->getAppValue($this->appName, 'installed_version'), '1.15.1') >= 0) {
            return;
        }

        $this->setProvidersName('openid_providers');
        $this->setProvidersName('custom_oidc_providers');

        //Removes old user config "password"
        $sql = "DELETE FROM `*PREFIX*preferences` WHERE `appid` = 'sociallogin' AND `configkey` = 'password'";
        $this->db->executeUpdate($sql);
    }

    private function setProvidersName($configKey)
    {
        $providers = json_decode($this->config->getAppValue($this->appName, $configKey), true);
        if (is_array($providers)) {
            foreach ($providers as &$provider) {
                if (!isset($provider['name'])) {
                    $provider['name'] = $provider['title'];
                }
            }
            $this->config->setAppValue($this->appName, $configKey, json_encode($providers));
        }
    }
}
