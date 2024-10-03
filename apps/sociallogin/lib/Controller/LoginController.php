<?php

namespace OCA\SocialLogin\Controller;

use OC\User\LoginException;
use OCA\SocialLogin\Service\ProviderService;
use OCP\AppFramework\Controller;
use OCP\IRequest;

class LoginController extends Controller
{
    /** @var ProviderService */
    private $providerService;

    public function __construct($appName, IRequest $request, ProviderService $providerService)
    {
        parent::__construct($appName, $request);
        $this->providerService = $providerService;
    }

    /**
     * @PublicPage
     * @NoCSRFRequired
     * @UseSession
     */
    public function oauth($provider)
    {
        return $this->providerService->handleDefault($provider);
    }

    /**
     * @PublicPage
     * @NoCSRFRequired
     * @UseSession
     */
    public function custom($type, $provider)
    {
        if (!isset(ProviderService::TYPE_CLASSES[$type])) {
            throw new LoginException(sprintf('Unknown provider type: %s', $type));
        }
        return $this->providerService->handleCustom($type, $provider);
    }
}
