<?php

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Teams;

use OC\AppFramework\Bootstrap\Coordinator;
use OCA\Circles\CirclesManager;
use OCA\Circles\Exceptions\CircleNotFoundException;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Member;
use OCP\IURLGenerator;
use OCP\Server;
use OCP\Teams\ITeamManager;
use OCP\Teams\ITeamResourceProvider;
use OCP\Teams\Team;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class TeamManager implements ITeamManager {

	/** @var ?ITeamResourceProvider[] */
	private ?array $providers = null;

	public function __construct(
		private Coordinator $bootContext,
		private IURLGenerator $urlGenerator,
		private ?CirclesManager $circlesManager,
	) {
	}

	public function hasTeamSupport(): bool {
		return $this->circlesManager !== null;
	}

	public function getProviders(): array {
		if (!$this->hasTeamSupport()) {
			return [];
		}

		if ($this->providers !== null) {
			return $this->providers;
		}

		$this->providers = [];
		foreach ($this->bootContext->getRegistrationContext()->getTeamResourceProviders() as $providerRegistration) {
			try {
				/** @var ITeamResourceProvider $provider */
				$provider = Server::get($providerRegistration->getService());
				$this->providers[$provider->getId()] = $provider;
			} catch (NotFoundExceptionInterface|ContainerExceptionInterface $e) {
			}
		}
		return $this->providers;
	}

	public function getProvider(string $providerId): ITeamResourceProvider {
		$providers = $this->getProviders();
		if (isset($providers[$providerId])) {
			return $providers[$providerId];
		}

		throw new \RuntimeException('No provider found for id ' . $providerId);
	}

	public function getSharedWith(string $teamId, string $userId): array {
		if (!$this->hasTeamSupport()) {
			return [];
		}

		if ($this->getTeam($teamId, $userId) === null) {
			return [];
		}

		$resources = [];

		foreach ($this->getProviders() as $provider) {
			array_push($resources, ...$provider->getSharedWith($teamId));
		}

		return array_values($resources);
	}

	public function getTeamsForResource(string $providerId, string $resourceId, string $userId): array {
		if (!$this->hasTeamSupport()) {
			return [];
		}

		$provider = $this->getProvider($providerId);
		return array_values(array_filter(array_map(function ($teamId) use ($userId) {
			$team = $this->getTeam($teamId, $userId);
			if ($team === null) {
				return null;
			}

			return new Team(
				$teamId,
				$team->getDisplayName(),
				$this->urlGenerator->linkToRouteAbsolute('contacts.contacts.directcircle', ['singleId' => $teamId]),
			);
		}, $provider->getTeamsForResource($resourceId))));
	}

	private function getTeam(string $teamId, string $userId): ?Circle {
		if (!$this->hasTeamSupport()) {
			return null;
		}

		try {
			$federatedUser = $this->circlesManager->getFederatedUser($userId, Member::TYPE_USER);
			$this->circlesManager->startSession($federatedUser);
			return $this->circlesManager->getCircle($teamId);
		} catch (CircleNotFoundException) {
			return null;
		}
	}
}
