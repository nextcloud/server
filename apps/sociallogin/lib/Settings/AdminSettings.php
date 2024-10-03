<?php

namespace OCA\SocialLogin\Settings;

use OCA\SocialLogin\Service\ProviderService;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\Settings\ISettings;
use OCP\IGroupManager;
use OCP\IURLGenerator;
use OCP\IConfig;
use OCP\Util;

class AdminSettings implements ISettings
{
    /** @var string */
    private $appName;
    /** @var IConfig */
    private $config;
    /** @var IURLGenerator */
    private $urlGenerator;
    /** @var IGroupManager */
    private $groupManager;

    public function __construct($appName, IConfig $config, IURLGenerator $urlGenerator, IGroupManager $groupManager)
    {
        $this->appName = $appName;
        $this->config = $config;
        $this->urlGenerator = $urlGenerator;
        $this->groupManager = $groupManager;
    }

    public function getForm()
    {
        Util::addScript($this->appName, 'settings');

        $groupNames = [];
        $groups = $this->groupManager->search('');
        foreach ($groups as $group) {
            $groupNames[] = $group->getGid();
        }
        $providers = [];
        $savedProviders = json_decode($this->config->getAppValue($this->appName, 'oauth_providers'), true) ?: [];
        foreach (ProviderService::DEFAULT_PROVIDERS as $provider) {
            if (array_key_exists($provider, $savedProviders)) {
                $providers[$provider] = $savedProviders[$provider];
            } else {
                $providers[$provider] = [
                    'appid' => '',
                    'secret' => '',
                ];
            }
        }
        $customProviders = json_decode($this->config->getAppValue($this->appName, 'custom_providers'), true);

        $params = [
            'app_name' => $this->appName,
            'action_url' => $this->urlGenerator->linkToRoute($this->appName.'.settings.saveAdmin'),
            'groups' => $groupNames,
            'custom_providers' => $customProviders,
            'providers' => $providers,
        ];
        foreach (ProviderService::OPTIONS as $paramName) {
            $params['options'][$paramName] = !!$this->config->getAppValue($this->appName, $paramName);
        }
        return new TemplateResponse($this->appName, 'admin', $params);
    }

    public function getSection()
    {
        return $this->appName;
    }

    public function getPriority()
    {
        return 0;
    }
}
