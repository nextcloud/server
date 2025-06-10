<?php
/**
 * SPDX-FileCopyrightText: 2017-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud GmbH
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Testing\AppInfo;

use OCA\Testing\AlternativeHomeUserBackend;
use OCA\Testing\Conversion\ConversionProvider;
use OCA\Testing\HiddenGroupBackend;
use OCA\Testing\Listener\GetDeclarativeSettingsValueListener;
use OCA\Testing\Listener\RegisterDeclarativeSettingsListener;
use OCA\Testing\Listener\SetDeclarativeSettingsValueListener;
use OCA\Testing\Provider\FakeText2ImageProvider;
use OCA\Testing\Provider\FakeTextProcessingProvider;
use OCA\Testing\Provider\FakeTextProcessingProviderSync;
use OCA\Testing\Provider\FakeTranslationProvider;
use OCA\Testing\Settings\DeclarativeSettingsForm;
use OCA\Testing\TaskProcessing\FakeContextWriteProvider;
use OCA\Testing\TaskProcessing\FakeTextToImageProvider;
use OCA\Testing\TaskProcessing\FakeTextToTextProvider;
use OCA\Testing\TaskProcessing\FakeTextToTextSummaryProvider;
use OCA\Testing\TaskProcessing\FakeTranscribeProvider;
use OCA\Testing\TaskProcessing\FakeTranslateProvider;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\IGroupManager;
use OCP\Settings\Events\DeclarativeSettingsGetValueEvent;
use OCP\Settings\Events\DeclarativeSettingsRegisterFormEvent;
use OCP\Settings\Events\DeclarativeSettingsSetValueEvent;

class Application extends App implements IBootstrap {
	public const APP_ID = 'testing';

	public function __construct(array $urlParams = []) {
		parent::__construct(self::APP_ID, $urlParams);
	}

	public function register(IRegistrationContext $context): void {
		$context->registerTranslationProvider(FakeTranslationProvider::class);
		$context->registerTextProcessingProvider(FakeTextProcessingProvider::class);
		$context->registerTextProcessingProvider(FakeTextProcessingProviderSync::class);
		$context->registerTextToImageProvider(FakeText2ImageProvider::class);

		$context->registerTaskProcessingProvider(FakeTextToTextProvider::class);
		$context->registerTaskProcessingProvider(FakeTextToTextSummaryProvider::class);
		$context->registerTaskProcessingProvider(FakeTextToImageProvider::class);
		$context->registerTaskProcessingProvider(FakeTranslateProvider::class);
		$context->registerTaskProcessingProvider(FakeTranscribeProvider::class);
		$context->registerTaskProcessingProvider(FakeContextWriteProvider::class);

		$context->registerFileConversionProvider(ConversionProvider::class);

		$context->registerDeclarativeSettings(DeclarativeSettingsForm::class);
		$context->registerEventListener(DeclarativeSettingsRegisterFormEvent::class, RegisterDeclarativeSettingsListener::class);
		$context->registerEventListener(DeclarativeSettingsGetValueEvent::class, GetDeclarativeSettingsValueListener::class);
		$context->registerEventListener(DeclarativeSettingsSetValueEvent::class, SetDeclarativeSettingsValueListener::class);
	}

	public function boot(IBootContext $context): void {
		$server = $context->getServerContainer();
		$config = $server->getConfig();
		if ($config->getAppValue(self::APP_ID, 'enable_alt_user_backend', 'no') === 'yes') {
			$userManager = $server->getUserManager();

			// replace all user backends with this one
			$userManager->clearBackends();
			$userManager->registerBackend($context->getAppContainer()->get(AlternativeHomeUserBackend::class));
		}

		$groupManager = $server->get(IGroupManager::class);
		$groupManager->addBackend($server->get(HiddenGroupBackend::class));
	}
}
