<?php

declare(strict_types=1);

namespace OCA\SocialLogin\Controller;

use OCA\SocialLogin\Db\ConnectedLoginMapper;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCSController;
use OCP\IAppConfig;
use OCP\IConfig;
use OCP\IRequest;

class LinkController extends OCSController
{
    /** @var ConnectedLoginMapper */
    private $socialConnect;

    public function __construct(
        $appName,
        IRequest $request,
        ConnectedLoginMapper $socialConnect
    ) {
        parent::__construct($appName, $request);
        $this->socialConnect = $socialConnect;
    }

    /**
     * @PasswordConfirmationRequired
     * @param string $uid
     * @param string $identifier
     * @return DataResponse
     */
    public function connectSocialLogin($uid, $identifier): DataResponse
    {
        $this->socialConnect->connectLogin($uid, $identifier);
        return new DataResponse();
    }

    /**
     * @PasswordConfirmationRequired
     * @param string $identifier
     * @return DataResponse
     */
    public function disconnectSocialLogin($identifier): DataResponse
    {
        $this->socialConnect->disconnectLogin($identifier);
        return new DataResponse();
    }
}
