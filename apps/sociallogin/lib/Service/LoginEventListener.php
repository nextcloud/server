<?php
namespace OCA\SocialLogin\Service;

use OCA\SocialLogin\Service\LoginAttemptEvent;
use OCP\EventDispatcher\Event;
use OCA\SocialLogin\Service\ProviderService;

class LoginEventListener {

    
        
    public function handleLoginAttempt(LoginAttemptEvent $event) {

        //custom_oauth2
		//awscognito

        $providerService = \OC::$server->get(ProviderService::class);

        $request = $event->getRequest();
        
        // Add your custom login logic here
        //if ($request->getHeader('X-Custom-oauth')) {
        //    // Mark the login as successful

        $authHeader = $request->getHeader('Authorization');

		// Check if $request->getHeader('Authorization') returned an empty string or null
		if (empty($authHeader)) {
			// If empty, fall back to using getallheaders()
			$headers = getallheaders();
			
			// Look for the Authorization header in the headers array
			if (isset($headers['Authorization'])) {
				$authHeader = $headers['Authorization'];
			} elseif (isset($headers['authorization'])) {
				$authHeader = $headers['authorization'];
			}
		}

		$providerService->handleCustomForToken('custom_oauth2','awscognito');


            $event->setLoginSuccess(true);
        //}
    }
}
