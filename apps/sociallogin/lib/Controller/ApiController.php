<?php

declare(strict_types=1);

namespace OCA\SocialLogin\Controller;

use OCP\AppFramework\ApiController as BaseController;
use OCP\AppFramework\Http\Response;
use OCP\IConfig;
use OCP\IRequest;

class ApiController extends BaseController
{
    /** @var IConfig */
    private $config;

    public function __construct(
        $appName,
        IRequest $request,
        IConfig $config
    ) {
        parent::__construct($appName, $request);
        $this->config = $config;
    }

    /**
     * @NoCSRFRequired
     */
    public function setConfig($key, $config)
    {
        $this->config->setAppValue($this->appName, $key, $config);
        return new Response();
    }
}
