<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC;

use OCP\AppFramework\QueryException;
use OCP\Capabilities\ICapability;
use OCP\Capabilities\IInitialStateExcludedCapability;
use OCP\Capabilities\IPublicCapability;
use Psr\Log\LoggerInterface;

class CapabilitiesManager {
	/** @var \Closure[] */
	private $capabilities = [];

	/** @var LoggerInterface */
	private $logger;

	public function __construct(LoggerInterface $logger) {
		$this->logger = $logger;
	}

	/**
	 * Get an array of al the capabilities that are registered at this manager
	 *
	 * @param bool $public get public capabilities only
	 * @throws \InvalidArgumentException
	 * @return array<string, mixed>
	 */
	public function getCapabilities(bool $public = false, bool $initialState = false) : array {
		$capabilities = [];
		foreach ($this->capabilities as $capability) {
			try {
				$c = $capability();
			} catch (QueryException $e) {
				$this->logger->error('CapabilitiesManager', [
					'exception' => $e,
				]);
				continue;
			}

			if ($c instanceof ICapability) {
				if (!$public || $c instanceof IPublicCapability) {
					if ($initialState && ($c instanceof IInitialStateExcludedCapability)) {
						// Remove less important capabilities information that are expensive to query
						// that we would otherwise inject to every page load
						continue;
					}
					$capabilities = array_replace_recursive($capabilities, $c->getCapabilities());
				}
			} else {
				throw new \InvalidArgumentException('The given Capability (' . get_class($c) . ') does not implement the ICapability interface');
			}
		}

		return $capabilities;
	}

	/**
	 * In order to improve lazy loading a closure can be registered which will be called in case
	 * capabilities are actually requested
	 *
	 * $callable has to return an instance of OCP\Capabilities\ICapability
	 *
	 * @param \Closure $callable
	 */
	public function registerCapability(\Closure $callable) {
		$this->capabilities[] = $callable;
	}
}
