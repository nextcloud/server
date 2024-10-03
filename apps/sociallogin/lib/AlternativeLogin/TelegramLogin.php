<?php

namespace OCA\SocialLogin\AlternativeLogin;

use OCP\EventDispatcher\IEventDispatcher;
use OCP\Security\CSP\AddContentSecurityPolicyEvent;
use OCP\IConfig;
use OCP\Util;

class TelegramLogin extends SocialLogin
{
    private $appName;
    /** @var IEventDispatcher */
    private $dispatcher;
    /** @var IConfig */
    private $config;

    public function __construct($appName, IEventDispatcher $dispatcher, IConfig $config)
    {
        $this->appName = $appName;
        $this->dispatcher = $dispatcher;
        $this->config = $config;
    }

    public function getLink(): string
    {
        return 'javascript:;';
    }

    public function getClass(): string
    {
        return 'telegram';
    }

    public function load(): void
    {
        parent::load();
        $this->dispatcher->addListener(AddContentSecurityPolicyEvent::class, function ($event) {
            $csp = new \OCP\AppFramework\Http\ContentSecurityPolicy();
            $csp->addAllowedScriptDomain('telegram.org');
            $event->addPolicy($csp);
        });
        $providers = json_decode($this->config->getAppValue($this->appName, 'oauth_providers', '[]'), true);
        $token = $providers['telegram']['secret'] ?? '';
        list($botId) = explode(':', $token);
        Util::addHeader('meta', [
            'id' => 'tg-data',
            'data-bot-id' => $botId,
            'data-redirect-url' => parent::getLink(),
        ]);
        Util::addScript($this->appName, 'telegram');
    }
}
