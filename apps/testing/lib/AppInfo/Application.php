<?php
/**
 * @copyright Copyright (c) 2016, ownCloud GmbH
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Vincent Petry <vincent@nextcloud.com>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCA\Testing\AppInfo;

use OCA\Testing\AlternativeHomeUserBackend;
use OCA\Testing\Listener\GetDeclarativeSettingsValueListener;
use OCA\Testing\Listener\RegisterDeclarativeSettingsListener;
use OCA\Testing\Listener\SetDeclarativeSettingsValueListener;
use OCA\Testing\Provider\FakeText2ImageProvider;
use OCA\Testing\Provider\FakeTextProcessingProvider;
use OCA\Testing\Provider\FakeTextProcessingProviderSync;
use OCA\Testing\Provider\FakeTranslationProvider;
use OCA\Testing\Settings\DeclarativeSettingsForm;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\Settings\Events\DeclarativeSettingsGetValueEvent;
use OCP\Settings\Events\DeclarativeSettingsRegisterFormEvent;
use OCP\Settings\Events\DeclarativeSettingsSetValueEvent;

class Application extends App implements IBootstrap {
	public function __construct(array $urlParams = []) {
		parent::__construct('testing', $urlParams);
	}

	public function register(IRegistrationContext $context): void {
		$context->registerTranslationProvider(FakeTranslationProvider::class);
		$context->registerTextProcessingProvider(FakeTextProcessingProvider::class);
		$context->registerTextProcessingProvider(FakeTextProcessingProviderSync::class);
		$context->registerTextToImageProvider(FakeText2ImageProvider::class);

		$context->registerDeclarativeSettings(DeclarativeSettingsForm::class);
		$context->registerEventListener(DeclarativeSettingsRegisterFormEvent::class, RegisterDeclarativeSettingsListener::class);
		$context->registerEventListener(DeclarativeSettingsGetValueEvent::class, GetDeclarativeSettingsValueListener::class);
		$context->registerEventListener(DeclarativeSettingsSetValueEvent::class, SetDeclarativeSettingsValueListener::class);
	}

	public function boot(IBootContext $context): void {
		$server = $context->getServerContainer();
		$config = $server->getConfig();
		if ($config->getAppValue('testing', 'enable_alt_user_backend', 'no') === 'yes') {
			$userManager = $server->getUserManager();

			// replace all user backends with this one
			$userManager->clearBackends();
			$userManager->registerBackend($context->getAppContainer()->get(AlternativeHomeUserBackend::class));
		}
	}
}
