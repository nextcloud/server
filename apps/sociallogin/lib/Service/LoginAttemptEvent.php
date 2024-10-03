<?php
namespace OCA\SocialLogin\Service;

use OCP\EventDispatcher\Event;
use OCP\IRequest;

class LoginAttemptEvent extends Event {
    private $request;
    private $loginSuccess = false;  // A property to hold the result

    public function __construct(IRequest $request) {
        $this->request = $request;
    }

    public function getRequest(): IRequest {
        return $this->request;
    }

    public function isLoginSuccess(): bool {
        return $this->loginSuccess;
    }

    public function setLoginSuccess(bool $success): void {
        $this->loginSuccess = $success;
    }
}
