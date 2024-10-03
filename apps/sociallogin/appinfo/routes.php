<?php
/**
 * Create your routes in here. The name is the lowercase name of the controller
 * without the controller part, the stuff after the hash is the method.
 * e.g. page#index -> OCA\SocialLogin\Controller\PageController->index()
 *
 * The controller class has to be registered in the application.php file since
 * it's instantiated in there
 */
return [
    'routes' => [
        ['name' => 'settings#saveAdmin', 'url' => '/settings/save-admin', 'verb' => 'POST'],
        ['name' => 'settings#disconnectSocialLogin', 'url' => '/disconnect-social/{login}', 'verb' => 'GET'],
        ['name' => 'settings#savePersonal', 'url' => '/settings/save-personal', 'verb' => 'POST'],
        ['name' => 'api#setConfig', 'url' => '/api/config', 'verb' => 'POST'],
        ['name' => 'login#oauth', 'url' => '/oauth/{provider}', 'verb' => 'GET'],
        ['name' => 'login#oauth', 'url' => '/oauth/{provider}', 'postfix' => '.post', 'verb' => 'POST'],
        ['name' => 'login#custom', 'url' => '/{type}/{provider}', 'verb' => 'GET'],
        ['name' => 'login#custom', 'url' => '/{type}/{provider}', 'postfix' => '.post', 'verb' => 'POST'],
    ],
    'ocs' => [
        ['name' => 'link#connectSocialLogin', 'url' => '/api/connect/{uid}', 'verb' => 'POST'],
        ['name' => 'link#disconnectSocialLogin', 'url' => '/api/connect/{identifier}', 'verb' => 'DELETE'],
    ]
];
