<?php

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Activity;

use OCP\Activity\ActivitySettings;
use OCP\Activity\Exceptions\FilterNotFoundException;
use OCP\Activity\Exceptions\IncompleteActivityException;
use OCP\Activity\Exceptions\SettingNotFoundException;
use OCP\Activity\IBulkConsumer;
use OCP\Activity\IConsumer;
use OCP\Activity\IEvent;
use OCP\Activity\IFilter;
use OCP\Activity\IManager;
use OCP\Activity\IProvider;
use OCP\Activity\ISetting;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use OCP\RichObjectStrings\IRichTextFormatter;
use OCP\RichObjectStrings\IValidator;

class Manager implements IManager {

	/** @var string */
	protected $formattingObjectType;

	/** @var int */
	protected $formattingObjectId;

	/** @var bool */
	protected $requirePNG = false;

	/** @var string */
	protected $currentUserId;

	public function __construct(
		protected IRequest $request,
		protected IUserSession $session,
		protected IConfig $config,
		protected IValidator $validator,
		protected IRichTextFormatter $richTextFormatter,
		protected IL10N $l10n,
		protected ITimeFactory $timeFactory,
	) {
	}

	/** @var \Closure[] */
	private $consumersClosures = [];

	/** @var IConsumer[] */
	private $consumers = [];

	/**
	 * @return \OCP\Activity\IConsumer[]
	 */
	protected function getConsumers(): array {
		if (!empty($this->consumers)) {
			return $this->consumers;
		}

		$this->consumers = [];
		foreach ($this->consumersClosures as $consumer) {
			$c = $consumer();
			if ($c instanceof IConsumer) {
				$this->consumers[] = $c;
			} else {
				throw new \InvalidArgumentException('The given consumer does not implement the \OCP\Activity\IConsumer interface');
			}
		}

		return $this->consumers;
	}

	/**
	 * Generates a new IEvent object
	 *
	 * Make sure to call at least the following methods before sending it to the
	 * app with via the publish() method:
	 *  - setApp()
	 *  - setType()
	 *  - setAffectedUser()
	 *  - setSubject()
	 *
	 * @return IEvent
	 */
	public function generateEvent(): IEvent {
		return new Event($this->validator, $this->richTextFormatter);
	}

	/**
	 * {@inheritDoc}
	 */
	public function publish(IEvent $event): void {
		if ($event->getAuthor() === '' && $this->session->getUser() instanceof IUser) {
			$event->setAuthor($this->session->getUser()->getUID());
		}

		if (!$event->getTimestamp()) {
			$event->setTimestamp($this->timeFactory->getTime());
		}

		if ($event->getAffectedUser() === '' || !$event->isValid()) {
			throw new IncompleteActivityException('The given event is invalid');
		}

		foreach ($this->getConsumers() as $c) {
			$c->receive($event);
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function bulkPublish(IEvent $event, array $affectedUserIds, ISetting $setting): void {
		if (empty($affectedUserIds)) {
			throw new IncompleteActivityException('The given event is invalid');
		}

		if ($event->getAuthor() === '') {
			if ($this->session->getUser() instanceof IUser) {
				$event->setAuthor($this->session->getUser()->getUID());
			}
		}

		if (!$event->getTimestamp()) {
			$event->setTimestamp($this->timeFactory->getTime());
		}

		if (!$event->isValid()) {
			throw new IncompleteActivityException('The given event is invalid');
		}

		foreach ($this->getConsumers() as $c) {
			if ($c instanceof IBulkConsumer) {
				$c->bulkReceive($event, $affectedUserIds, $setting);
			}
			foreach ($affectedUserIds as $affectedUserId) {
				$event->setAffectedUser($affectedUserId);
				$c->receive($event);
			}
		}
	}


	/**
	 * In order to improve lazy loading a closure can be registered which will be called in case
	 * activity consumers are actually requested
	 *
	 * $callable has to return an instance of OCA\Activity\IConsumer
	 *
	 * @param \Closure $callable
	 */
	public function registerConsumer(\Closure $callable): void {
		$this->consumersClosures[] = $callable;
		$this->consumers = [];
	}

	/** @var string[] */
	protected $filterClasses = [];

	/** @var IFilter[] */
	protected $filters = [];

	/**
	 * @param string $filter Class must implement OCA\Activity\IFilter
	 * @return void
	 */
	public function registerFilter(string $filter): void {
		$this->filterClasses[$filter] = false;
	}

	/**
	 * @return IFilter[]
	 * @throws \InvalidArgumentException
	 */
	public function getFilters(): array {
		foreach ($this->filterClasses as $class => $false) {
			/** @var IFilter $filter */
			$filter = \OCP\Server::get($class);

			if (!$filter instanceof IFilter) {
				throw new \InvalidArgumentException('Invalid activity filter registered');
			}

			$this->filters[$filter->getIdentifier()] = $filter;

			unset($this->filterClasses[$class]);
		}
		return $this->filters;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getFilterById(string $id): IFilter {
		$filters = $this->getFilters();

		if (isset($filters[$id])) {
			return $filters[$id];
		}

		throw new FilterNotFoundException($id);
	}

	/** @var string[] */
	protected $providerClasses = [];

	/** @var IProvider[] */
	protected $providers = [];

	/**
	 * @param string $provider Class must implement OCA\Activity\IProvider
	 * @return void
	 */
	public function registerProvider(string $provider): void {
		$this->providerClasses[$provider] = false;
	}

	/**
	 * @return IProvider[]
	 * @throws \InvalidArgumentException
	 */
	public function getProviders(): array {
		foreach ($this->providerClasses as $class => $false) {
			/** @var IProvider $provider */
			$provider = \OCP\Server::get($class);

			if (!$provider instanceof IProvider) {
				throw new \InvalidArgumentException('Invalid activity provider registered');
			}

			$this->providers[] = $provider;

			unset($this->providerClasses[$class]);
		}
		return $this->providers;
	}

	/** @var string[] */
	protected $settingsClasses = [];

	/** @var ISetting[] */
	protected $settings = [];

	/**
	 * @param string $setting Class must implement OCA\Activity\ISetting
	 * @return void
	 */
	public function registerSetting(string $setting): void {
		$this->settingsClasses[$setting] = false;
	}

	/**
	 * @return ActivitySettings[]
	 * @throws \InvalidArgumentException
	 */
	public function getSettings(): array {
		foreach ($this->settingsClasses as $class => $false) {
			/** @var ISetting $setting */
			$setting = \OCP\Server::get($class);

			if ($setting instanceof ISetting) {
				if (!$setting instanceof ActivitySettings) {
					$setting = new ActivitySettingsAdapter($setting, $this->l10n);
				}
			} else {
				throw new \InvalidArgumentException('Invalid activity filter registered');
			}

			$this->settings[$setting->getIdentifier()] = $setting;

			unset($this->settingsClasses[$class]);
		}
		return $this->settings;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getSettingById(string $id): ActivitySettings {
		$settings = $this->getSettings();

		if (isset($settings[$id])) {
			return $settings[$id];
		}

		throw new SettingNotFoundException($id);
	}


	/**
	 * @param string $type
	 * @param int $id
	 */
	public function setFormattingObject(string $type, int $id): void {
		$this->formattingObjectType = $type;
		$this->formattingObjectId = $id;
	}

	/**
	 * @return bool
	 */
	public function isFormattingFilteredObject(): bool {
		return $this->formattingObjectType !== null && $this->formattingObjectId !== null
			&& $this->formattingObjectType === $this->request->getParam('object_type')
			&& $this->formattingObjectId === (int)$this->request->getParam('object_id');
	}

	/**
	 * @param bool $status Set to true, when parsing events should not use SVG icons
	 */
	public function setRequirePNG(bool $status): void {
		$this->requirePNG = $status;
	}

	/**
	 * @return bool
	 */
	public function getRequirePNG(): bool {
		return $this->requirePNG;
	}

	/**
	 * Set the user we need to use
	 *
	 * @param string|null $currentUserId
	 */
	public function setCurrentUserId(?string $currentUserId = null): void {
		$this->currentUserId = $currentUserId;
	}

	/**
	 * Get the user we need to use
	 *
	 * Either the user is logged in, or we try to get it from the token
	 *
	 * @return string
	 * @throws \UnexpectedValueException If the token is invalid, does not exist or is not unique
	 */
	public function getCurrentUserId(): string {
		if ($this->currentUserId !== null) {
			return $this->currentUserId;
		}

		if (!$this->session->isLoggedIn()) {
			return $this->getUserFromToken();
		}

		return $this->session->getUser()->getUID();
	}

	/**
	 * Get the user for the token
	 *
	 * @return string
	 * @throws \UnexpectedValueException If the token is invalid, does not exist or is not unique
	 */
	protected function getUserFromToken(): string {
		$token = (string)$this->request->getParam('token', '');
		if (strlen($token) !== 30) {
			throw new \UnexpectedValueException('The token is invalid');
		}

		$users = $this->config->getUsersForUserValue('activity', 'rsstoken', $token);

		if (count($users) !== 1) {
			// No unique user found
			throw new \UnexpectedValueException('The token is invalid');
		}

		// Token found login as that user
		return array_shift($users);
	}
}
