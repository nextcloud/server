<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

use Behat\Config\Config;
use Behat\Config\Extension;
use Behat\Config\Formatter\JUnitFormatter;
use Behat\Config\Formatter\PrettyFormatter;
use Behat\Config\Profile;
use Behat\Config\Suite;

require_once __DIR__ . '/features/bootstrap/PHPUnitAssertionsExtension.php';

$baseUrl = 'http://localhost:8080';
$ocsUrl = $baseUrl . '/ocs/';
$adminArgs = [
	'admin' => ['admin', 'admin'],
	'regular_user_password' => 123456,
];

/**
 * Contexts shared by the main feature suites (default, comments, dav, files).
 */
$commonContexts = static function (Suite $suite, string $mainContext) use ($baseUrl, $ocsUrl, $adminArgs): Suite {
	return $suite
		->addContext($mainContext, ['baseUrl' => $ocsUrl] + $adminArgs)
		->addContext('CommentsContext', ['baseUrl' => $baseUrl])
		->addContext('TagsContext', ['baseUrl' => $baseUrl])
		->addContext('CardDavContext', ['baseUrl' => $baseUrl])
		->addContext('CalDavContext', ['baseUrl' => $baseUrl])
		->addContext('ChecksumsContext', ['baseUrl' => $baseUrl])
		->addContext('CommandLineContext', ['baseUrl' => $baseUrl, 'ocPath' => '../../']);
};

/**
 * Suite with a single context that is configured with the OCS base URL and admin credentials.
 */
$ocsSuite = static function (string $name, string $path, string $context) use ($ocsUrl, $adminArgs): Suite {
	return (new Suite($name))
		->withPaths('%paths.base%/' . $path)
		->addContext($context, ['baseUrl' => $ocsUrl] + $adminArgs);
};

/**
 * Suite with a single context that is configured with the plain base URL and admin credentials.
 */
$plainSuite = static function (string $name, string $path, string $context) use ($baseUrl, $adminArgs): Suite {
	return (new Suite($name))
		->withPaths('%paths.base%/' . $path)
		->addContext($context, ['baseUrl' => $baseUrl] + $adminArgs);
};

$profile = (new Profile('default', [
	'autoload' => [
		'' => '%paths.base%/features/bootstrap',
	],
]))
	->withExtension(new Extension(PHPUnitAssertionsExtension::class))
	->withFormatter((new JUnitFormatter())->withOutputPath('%paths.base%/output'))
	->withFormatter((new PrettyFormatter())->withOutputStyles(['comment' => ['bright-blue']]))
	->withSuite($commonContexts((new Suite('default'))->withPaths('%paths.base%/features'), 'FeatureContext'))
	->withSuite($commonContexts((new Suite('comments'))->withPaths('%paths.base%/comments_features'), 'FeatureContext'))
	->withSuite(
		$commonContexts((new Suite('dav'))->withPaths('%paths.base%/dav_features'), 'DavFeatureContext')
			->addContext('PrincipalPropertySearchContext', ['baseUrl' => $baseUrl])
	)
	->withSuite(
		(new Suite('encryption'))
			->withPaths('%paths.base%/encryption_features')
			->addContext('FeatureContext', ['baseUrl' => $ocsUrl] + $adminArgs)
			->addContext('CommandLineContext', ['baseUrl' => $baseUrl, 'ocPath' => '../../'])
	)
	->withSuite($ocsSuite('federation', 'federation_features', 'FederationContext'))
	->withSuite(
		$commonContexts((new Suite('files'))->withPaths('%paths.base%/files_features'), 'FeatureContext')
			->addContext('MetadataContext', ['baseUrl' => $baseUrl] + $adminArgs)
	)
	->withSuite($plainSuite('files_conversion', 'file_conversions', 'ConversionsContext'))
	->withSuite($plainSuite('files_reminders', 'files_reminders', 'FilesRemindersContext'))
	->withSuite($ocsSuite('capabilities', 'capabilities_features', 'CapabilitiesContext'))
	->withSuite($ocsSuite('collaboration', 'collaboration_features', 'CollaborationContext'))
	->withSuite($ocsSuite('sharees', 'sharees_features', 'ShareesContext'))
	->withSuite($ocsSuite('sharing', 'sharing_features', 'SharingContext')->addContext('TalkContext'))
	->withSuite($ocsSuite('videoverification', 'videoverification_features', 'SharingContext')->addContext('TalkContext'))
	->withSuite(
		(new Suite('guests'))
			->withPaths('%paths.base%/guests_features')
			->addContext('GuestsContext')
			->addContext('SharingContext', ['baseUrl' => $ocsUrl] + $adminArgs)
	)
	->withSuite($ocsSuite('setup', 'setup_features', 'SetupContext'))
	->withSuite($plainSuite('filesdrop', 'filesdrop_features', 'FilesDropContext'))
	->withSuite($plainSuite('ldap', 'ldap_features', 'LDAPContext'))
	->withSuite($plainSuite('openldap_numerical', 'openldap_numerical_features', 'LDAPContext'))
	->withSuite($plainSuite('openldap', 'openldap_features', 'LDAPContext'))
	->withSuite($plainSuite('ratelimiting', 'ratelimiting_features', 'RateLimitingContext'))
	->withSuite($plainSuite('routing', 'routing_features', 'RoutingContext'))
	->withSuite($plainSuite('theming', 'theming_features', 'FeatureContext'));

return (new Config())->withProfile($profile);
