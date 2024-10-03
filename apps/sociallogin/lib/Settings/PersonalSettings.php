<?php

namespace OCA\SocialLogin\Settings;

use OCA\SocialLogin\Db\ConnectedLoginMapper;
use OCA\SocialLogin\Service\ProviderService;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IConfig;
use OCP\IURLGenerator;
use OCP\IUserSession;
use OCP\Settings\ISettings;
use OCP\Util;
use Psr\Container\ContainerInterface;

class PersonalSettings implements ISettings
{
    /** @var string */
    private $appName;
    /** @var IConfig */
    private $config;
    /** @var IURLGenerator */
    private $urlGenerator;
    /** @var IUserSession */
    private $userSession;
    /** @var ConnectedLoginMapper */
    private $socialConnect;
    /** @var ProviderService */
    private $providerService;
    /** @var ContainerInterface */
    private $container;

    public function __construct(
        $appName,
        IConfig $config,
        IURLGenerator $urlGenerator,
        IUserSession $userSession,
        ConnectedLoginMapper $socialConnect,
        ProviderService $providerService,
        ContainerInterface $container
    ) {
        $this->appName = $appName;
        $this->config = $config;
        $this->urlGenerator = $urlGenerator;
        $this->userSession = $userSession;
        $this->socialConnect = $socialConnect;
        $this->providerService = $providerService;
        $this->container = $container;
    }

    public function getForm()
    {
        Util::addScript($this->appName, 'personal');
        $uid = $this->userSession->getUser()->getUID();
        $params = [
            'providers' => [],
            'connected_logins' => [],
            'action_url' => $this->urlGenerator->linkToRoute($this->appName.'.settings.savePersonal'),
            'allow_login_connect' => $this->config->getAppValue($this->appName, 'allow_login_connect', false),
            'disable_password_confirmation' => $this->config->getUserValue($uid, $this->appName, 'disable_password_confirmation', false),
        ];
        if ($params['allow_login_connect']) {
            $providers = json_decode($this->config->getAppValue($this->appName, 'oauth_providers', '[]'), true);
            if (is_array($providers)) {
                foreach ($providers as $name => $provider) {
                    if ($provider['appid']) {
                        $class = $this->providerService->getLoginClass($name);
                        $login = $this->container->get($class);
                        $login->load();
                        $params['providers'][ucfirst($name)] = ['url' => $login->getLink(), 'style' => $login->getClass()];
                    }
                }
            }
            $params['providers'] = array_merge($params['providers'], $this->getCustomProviders());

            $connectedLogins = $this->socialConnect->getConnectedLogins($uid);
            foreach ($connectedLogins as $login) {
                $params['connected_logins'][$login] = $this->urlGenerator->linkToRoute($this->appName.'.settings.disconnectSocialLogin', [
                    'login' => $login,
                    'requesttoken' => Util::callRegister(),
                ]);
            }
        }
        return new TemplateResponse($this->appName, 'personal', $params);
    }

    private function getCustomProviders()
    {
        $result = [];
        $providers = json_decode($this->config->getAppValue($this->appName, 'custom_providers'), true) ?: [];
        foreach ($providers as $providersType => $providerList) {
            foreach ($providerList as $provider) {
                $class = $this->providerService->getLoginClass($provider['name'], $provider, $providersType);
                $login = $this->container->get($class);
                $login->load();
                $title = $provider['title'];
                $result[$title] = ['url' => $login->getLink(), 'style' => $login->getClass()];
            }
        }

        return $result;
    }

    public function getSection()
    {
        return 'sociallogin';
    }

    public function getPriority()
    {
        return 0;
    }
}
