<?php

declare(strict_types=1);

/**
 * @copyright 2020 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCP\AppFramework\Bootstrap;

use OCP\AppFramework\IAppContainer;
use OCP\Authentication\TwoFactorAuth\IProvider;
use OCP\Calendar\ICalendarProvider;
use OCP\Capabilities\ICapability;
use OCP\Collaboration\Reference\IReferenceProvider;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\Template\ICustomTemplateProvider;
use OCP\IContainer;
use OCP\TextProcessing\IProvider as ITextProcessingProvider;
use OCP\TextToImage\IProvider as ITextToImageProvider;
use OCP\Notification\INotifier;
use OCP\Preview\IProviderV2;
use OCP\SpeechToText\ISpeechToTextProvider;
use OCP\Translation\ITranslationProvider;

/**
 * The context object passed to IBootstrap::register
 *
 * @since 20.0.0
 * @see IBootstrap::register()
 */
interface IRegistrationContext {
	/**
	 * @param string $capability
	 * @psalm-param class-string<ICapability> $capability
	 * @see IAppContainer::registerCapability
	 *
	 * @since 20.0.0
	 */
	public function registerCapability(string $capability): void;

	/**
	 * Register an implementation of \OCP\Support\CrashReport\IReporter that
	 * will receive unhandled exceptions and throwables
	 *
	 * @param string $reporterClass
	 * @psalm-param class-string<\OCP\Support\CrashReport\IReporter> $reporterClass
	 * @return void
	 * @since 20.0.0
	 */
	public function registerCrashReporter(string $reporterClass): void;

	/**
	 * Register an implementation of \OCP\Dashboard\IWidget that
	 * will handle the implementation of a dashboard widget
	 *
	 * @param string $widgetClass
	 * @psalm-param class-string<\OCP\Dashboard\IWidget> $widgetClass
	 * @return void
	 * @since 20.0.0
	 */
	public function registerDashboardWidget(string $widgetClass): void;

	/**
	 * Register a service
	 *
	 * @param string $name
	 * @param callable $factory
	 * @psalm-param callable(\Psr\Container\ContainerInterface): mixed $factory
	 * @param bool $shared
	 *
	 * @return void
	 * @see IContainer::registerService()
	 *
	 * @since 20.0.0
	 */
	public function registerService(string $name, callable $factory, bool $shared = true): void;

	/**
	 * @param string $alias
	 * @psalm-param string|class-string $alias
	 * @param string $target
	 * @psalm-param string|class-string $target
	 *
	 * @return void
	 * @see IContainer::registerAlias()
	 *
	 * @since 20.0.0
	 */
	public function registerServiceAlias(string $alias, string $target): void;

	/**
	 * @param string $name
	 * @param mixed $value
	 *
	 * @return void
	 * @see IContainer::registerParameter()
	 *
	 * @since 20.0.0
	 */
	public function registerParameter(string $name, $value): void;

	/**
	 * Register a service listener
	 *
	 * This is equivalent to calling IEventDispatcher::addServiceListener
	 *
	 * @psalm-template T of \OCP\EventDispatcher\Event
	 * @param string $event preferably the fully-qualified class name of the Event sub class to listen for
	 * @psalm-param string|class-string<T> $event preferably the fully-qualified class name of the Event sub class to listen for
	 * @param string $listener fully qualified class name (or ::class notation) of a \OCP\EventDispatcher\IEventListener that can be built by the DI container
	 * @psalm-param class-string<\OCP\EventDispatcher\IEventListener<T>> $listener fully qualified class name that can be built by the DI container
	 * @param int $priority The higher this value, the earlier an event
	 *                      listener will be triggered in the chain (defaults to 0)
	 *
	 * @see IEventDispatcher::addServiceListener()
	 *
	 * @since 20.0.0
	 */
	public function registerEventListener(string $event, string $listener, int $priority = 0): void;

	/**
	 * @param string $class
	 * @param bool $global load this middleware also for requests of other apps? Added in Nextcloud 26
	 * @psalm-param class-string<\OCP\AppFramework\Middleware> $class
	 *
	 * @return void
	 * @see IAppContainer::registerMiddleWare()
	 *
	 * @since 20.0.0
	 * @since 26.0.0 Added optional argument $global
	 */
	public function registerMiddleware(string $class, bool $global = false): void;

	/**
	 * Register a search provider for the unified search
	 *
	 * It is allowed to register more than one provider per app as the search
	 * results can go into distinct sections, e.g. "Files" and "Files shared
	 * with you" in the Files app.
	 *
	 * @param string $class
	 * @psalm-param class-string<\OCP\Search\IProvider> $class
	 *
	 * @return void
	 *
	 * @since 20.0.0
	 */
	public function registerSearchProvider(string $class): void;

	/**
	 * Register an alternative login option
	 *
	 * It is allowed to register more than one option per app.
	 *
	 * @param string $class
	 * @psalm-param class-string<\OCP\Authentication\IAlternativeLogin> $class
	 *
	 * @return void
	 *
	 * @since 20.0.0
	 */
	public function registerAlternativeLogin(string $class): void;

	/**
	 * Register an initialstate provider
	 *
	 * It is allowed to register more than one provider per app.
	 *
	 * @param string $class
	 * @psalm-param class-string<\OCP\AppFramework\Services\InitialStateProvider> $class
	 *
	 * @return void
	 *
	 * @since 21.0.0
	 */
	public function registerInitialStateProvider(string $class): void;

	/**
	 * Register a well known protocol handler
	 *
	 * It is allowed to register more than one handler per app.
	 *
	 * @param string $class
	 * @psalm-param class-string<\OCP\Http\WellKnown\IHandler> $class
	 *
	 * @return void
	 *
	 * @since 21.0.0
	 */
	public function registerWellKnownHandler(string $class): void;

	/**
	 * Register a custom SpeechToText provider class that can provide transcription
	 * of audio through the OCP\SpeechToText APIs
	 *
	 * @param string $providerClass
	 * @psalm-param class-string<ISpeechToTextProvider> $providerClass
	 * @since 27.0.0
	 */
	public function registerSpeechToTextProvider(string $providerClass): void;

	/**
	 * Register a custom text processing provider class that provides a promptable language model
	 * through the OCP\TextProcessing APIs
	 *
	 * @param string $providerClass
	 * @psalm-param class-string<ITextProcessingProvider> $providerClass
	 * @since 27.1.0
	 */
	public function registerTextProcessingProvider(string $providerClass): void;

	/**
	 * Register a custom text2image provider class that provides the possibility to generate images
	 * through the OCP\TextToImage APIs
	 *
	 * @param string $providerClass
	 * @psalm-param class-string<ITextToImageProvider> $providerClass
	 * @since 28.0.0
	 */
	public function registerTextToImageProvider(string $providerClass): void;

	/**
	 * Register a custom template provider class that is able to inject custom templates
	 * in addition to the user defined ones
	 *
	 * @param string $providerClass
	 * @psalm-param class-string<ICustomTemplateProvider> $providerClass
	 * @since 21.0.0
	 */
	public function registerTemplateProvider(string $providerClass): void;

	/**
	 * Register a custom translation provider class that can provide translation
	 * between languages through the OCP\Translation APIs
	 *
	 * @param string $providerClass
	 * @psalm-param class-string<ITranslationProvider> $providerClass
	 * @since 21.0.0
	 */
	public function registerTranslationProvider(string $providerClass): void;

	/**
	 * Register an INotifier class
	 *
	 * @param string $notifierClass
	 * @psalm-param class-string<INotifier> $notifierClass
	 * @since 22.0.0
	 */
	public function registerNotifierService(string $notifierClass): void;

	/**
	 * Register a two-factor provider
	 *
	 * @param string $twoFactorProviderClass
	 * @psalm-param class-string<IProvider> $twoFactorProviderClass
	 * @since 22.0.0
	 */
	public function registerTwoFactorProvider(string $twoFactorProviderClass): void;

	/**
	 * Register a preview provider
	 *
	 * It is allowed to register more than one provider per app.
	 *
	 * @param string $previewProviderClass
	 * @param string $mimeTypeRegex
	 * @psalm-param class-string<IProviderV2> $previewProviderClass
	 * @since 23.0.0
	 */
	public function registerPreviewProvider(string $previewProviderClass, string $mimeTypeRegex): void;

	/**
	 * Register a calendar provider
	 *
	 * @param string $class
	 * @psalm-param class-string<ICalendarProvider> $class
	 * @since 23.0.0
	 */
	public function registerCalendarProvider(string $class): void;

	/**
	 * Register a reference provider
	 *
	 * @param string $class
	 * @psalm-param class-string<IReferenceProvider> $class
	 * @since 25.0.0
	 */
	public function registerReferenceProvider(string $class): void;

	/**
	 * Register an implementation of \OCP\Profile\ILinkAction that
	 * will handle the implementation of a profile link action
	 *
	 * @param string $actionClass
	 * @psalm-param class-string<\OCP\Profile\ILinkAction> $actionClass
	 * @return void
	 * @since 23.0.0
	 */
	public function registerProfileLinkAction(string $actionClass): void;

	/**
	 * Register the backend of the Talk app
	 *
	 * This service must only be used by the Talk app
	 *
	 * @param string $backend
	 * @return void
	 * @since 24.0.0
	 */
	public function registerTalkBackend(string $backend): void;

	/**
	 * Register a resource backend for the DAV server
	 *
	 * @param string $actionClass
	 * @psalm-param class-string<\OCP\Calendar\Resource\IBackend> $actionClass
	 * @return void
	 * @since 24.0.0
	 */
	public function registerCalendarResourceBackend(string $class): void;

	/**
	 * Register a room backend for the DAV server
	 *
	 * @param string $actionClass
	 * @psalm-param class-string<\OCP\Calendar\Room\IBackend> $actionClass
	 * @return void
	 * @since 24.0.0
	 */
	public function registerCalendarRoomBackend(string $class): void;

	/**
	 * Register an implementation of \OCP\UserMigration\IMigrator that
	 * will handle the implementation of a migrator
	 *
	 * @param string $migratorClass
	 * @psalm-param class-string<\OCP\UserMigration\IMigrator> $migratorClass
	 * @return void
	 * @since 24.0.0
	 */
	public function registerUserMigrator(string $migratorClass): void;

	/**
	 * Announce methods of classes that may contain sensitive values, which
	 * should be obfuscated before being logged.
	 *
	 * @param string $class
	 * @param string[] $methods
	 * @return void
	 * @since 25.0.0
	 */
	public function registerSensitiveMethods(string $class, array $methods): void;

	/**
	 * Register an implementation of IPublicShareTemplateProvider.
	 *
	 * @param string $class
	 * @psalm-param class-string<\OCP\Share\IPublicShareTemplateProvider> $class
	 * @return void
	 * @since 26.0.0
	 */
	public function registerPublicShareTemplateProvider(string $class): void;

	/**
	 * Register an implementation of \OCP\SetupCheck\ISetupCheck that
	 * will handle the implementation of a setup check
	 *
	 * @param class-string<\OCP\SetupCheck\ISetupCheck> $setupCheckClass
	 * @since 28.0.0
	 */
	public function registerSetupCheck(string $setupCheckClass): void;
}
