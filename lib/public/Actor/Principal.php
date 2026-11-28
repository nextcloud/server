<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Actor;

use InvalidArgumentException;
use JsonSerializable;
use OCP\App\IAppManager;
use OCP\AppFramework\Attribute\Consumable;
use OCP\Federation\ICloudIdManager;
use Override;

/**
 * A principal defines the identity of an actor.
 * It specifies who the actor is and where that identity lives.
 *
 * @psalm-type PrincipalData = array{type: non-empty-string, id: non-empty-string, origin: ?non-empty-string, source: ?non-empty-string}
 * @psalm-immutable
 * @since 36.0.0
 */
#[Consumable(since: '36.0.0')]
final readonly class Principal implements JsonSerializable {
	/**
	 * Remote instance for federated users, optional visitor id within the scope for anonymous users, null otherwise
	 *
	 * @var ?non-empty-string
	 * @since 36.0.0
	 */
	public ?string $origin;

	/**
	 * @param non-empty-string $id - user id, scope of anonymous users, bot id or component name of a system principal
	 * @param ?non-empty-string $origin - see `Principal::$origin`
	 * @param ?non-empty-string $source - app owning the principal: `appid:kind` for anonymous users, e.g. "spreed:guest",
	 *                                  the app id for bots and system components, e.g. "spreed"; null for users
	 */
	private function __construct(
		public PrincipalType $type,
		public string $id,
		?string $origin = null,
		public ?string $source = null,
	) {
		if ($type === PrincipalType::User && $origin !== null) {
			$origin = self::normalizeRemote($origin);
		}
		$originAllowed = match ($type) {
			PrincipalType::Anonymous, PrincipalType::User => $origin !== '',
			PrincipalType::Bot, PrincipalType::System => $origin === null,
		};
		if (!$originAllowed) {
			throw new InvalidArgumentException(sprintf(
				'Invalid origin for principal of type "%s"',
				$type->value,
			));
		}
		$this->origin = $origin;

		$sourceAllowed = match ($type) {
			PrincipalType::Anonymous, PrincipalType::Bot, PrincipalType::System => $source !== null && $source !== '',
			PrincipalType::User => $source === null,
		};
		if (!$sourceAllowed) {
			throw new InvalidArgumentException(sprintf(
				'Invalid source for principal of type "%s"',
				$type->value,
			));
		}

		if ($source !== null) {
			if ($type === PrincipalType::Anonymous) {
				self::validateAnonymousKind($source);
			} else {
				self::validateAppId($source);
			}
		}
	}

	/**
	 * Identifies a local or federated user.
	 *
	 * @param non-empty-string $userId - user id
	 * @param ?non-empty-string $remote - remote instance for federated users, null for local users;
	 *                                  a trailing slash and the "https://" scheme are removed, as in cloud ids
	 * @since 36.0.0
	 */
	public static function user(string $userId, ?string $remote = null): self {
		return new self(PrincipalType::User, $userId, $remote);
	}

	/**
	 * Identifies an anonymous user, e.g. a public visitor of a public share.
	 *
	 * @param non-empty-string $scope - what the anonymous user was granted access through, e.g. a share token, a Talk room token or a form hash
	 * @param non-empty-string $source - app-namespaced kind of anonymous user, e.g. `files_sharing:visitor` or `spreed:guest`
	 * @param ?non-empty-string $visitorId - stable id of the visitor within the scope, e.g. a Talk guest id or a session id; never the nickname
	 * @throws InvalidArgumentException - if the source is not of the form `appid:kind`
	 * @since 36.0.0
	 */
	public static function anonymous(string $scope, string $source, ?string $visitorId = null): self {
		return new self(PrincipalType::Anonymous, $scope, $visitorId, $source);
	}

	/**
	 * Identifies a bot, e.g. a Talk bot or the assistant.
	 *
	 * @param non-empty-string $botId - bot id, unique within the owning app
	 * @param non-empty-string $appId - app owning the bot, e.g. "spreed"
	 * @throws InvalidArgumentException - if the app id is not valid
	 * @since 36.0.0
	 */
	public static function bot(string $botId, string $appId): self {
		return new self(PrincipalType::Bot, $botId, null, $appId);
	}

	/**
	 * Identifies a system component.
	 *
	 * e.g. the "retention" component of the "files_versions" app: `Principal::system('retention', 'files_versions')`
	 *
	 * @param non-empty-string $component - component name, unique within the owning app
	 * @param non-empty-string $appId - app owning the component, or "core" for server components
	 * @throws InvalidArgumentException - if the app id is not valid
	 * @since 36.0.0
	 */
	public static function system(string $component, string $appId): self {
		return new self(PrincipalType::System, $component, null, $appId);
	}

	/**
	 * @since 36.0.0
	 */
	public function equals(self $other): bool {
		return $this->type === $other->type
			&& $this->id === $other->id
			&& $this->origin === $other->origin
			&& $this->source === $other->source;
	}

	/**
	 * Stable key for logs and cache keys
	 *
	 * e.g. a federated user: `user|bob@cloud.example`
	 * an anonymous visitor of a public share: `anonymous|sharetoken|files_sharing:visitor`
	 * a Talk guest: `anonymous|roomtoken@guestid|spreed:guest`
	 * or a system component: `system|retention|files_versions`
	 *
	 * The delimiters `|`, `@` and `#` are percent-encoded within the parts,
	 * e.g. the local user "bob@cloud.example": `user|bob%40cloud.example`
	 *
	 * @since 36.0.0
	 */
	public function key(): string {
		return $this->type->value . '|'
			. self::escapeKeyPart($this->id)
			. ($this->origin !== null ? '@' . self::escapeKeyPart($this->origin) : '')
			. ($this->source !== null ? '|' . self::escapeKeyPart($this->source) : '');
	}

	/**
	 * @psalm-pure
	 */
	private static function escapeKeyPart(string $part): string {
		return strtr($part, ['%' => '%25', '|' => '%7C', '@' => '%40', '#' => '%23']);
	}

	/**
	 * @return PrincipalData
	 * @since 36.0.0
	 */
	#[Override]
	public function jsonSerialize(): array {
		return [
			'type' => $this->type->value,
			'id' => $this->id,
			'origin' => $this->origin,
			'source' => $this->source,
		];
	}

	/**
	 * Rehydrate from a serialized payload.
	 *
	 * Payloads written by a newer server version may contain principal types unknown to this version,
	 * callers reading stored payloads must handle the exception.
	 *
	 * @param array<string, mixed> $data - see `Principal::jsonSerialize`
	 * @throws InvalidArgumentException if the data is invalid or the type is unknown
	 * @since 36.0.0
	 */
	public static function fromArray(array $data): self {
		self::assertValidData($data);
		$type = $data['type'];
		$id = $data['id'];
		$origin = $data['origin'] ?? null;
		$source = $data['source'] ?? null;

		return new self(
			PrincipalType::tryFrom($type) ?? throw new InvalidArgumentException(sprintf('Unknown principal type "%s"', $type)),
			$id,
			$origin,
			$source,
		);
	}

	/**
	 * @psalm-assert array{type: non-empty-string, id: non-empty-string, origin?: ?non-empty-string, source?: ?non-empty-string} $data
	 * @throws InvalidArgumentException - if the data is invalid
	 */
	private static function assertValidData(array $data): void {
		$type = $data['type'] ?? null;
		$id = $data['id'] ?? null;
		$origin = $data['origin'] ?? null;
		$source = $data['source'] ?? null;
		if (!is_string($type) || $type === ''
			|| !is_string($id) || $id === ''
			|| ($origin !== null && !is_string($origin)) || $origin === ''
			|| ($source !== null && !is_string($source)) || $source === '') {
			throw new InvalidArgumentException('Invalid principal data');
		}
	}

	/**
	 * Remove the trailing slash and the "https://" scheme, keeping an explicit "http://" as cloud ids do
	 */
	private static function normalizeRemote(string $remote): string {
		return rtrim(\OCP\Server::get(ICloudIdManager::class)->removeProtocolFromUrl($remote, true), '/');
	}

	/**
	 * Verify the given app id is well-formed, without checking whether the app is installed or enabled
	 * @param non-empty-string $appId - app id to validate
	 * @throws InvalidArgumentException - If the app id is invalid
	 */
	private static function validateAppId(string $appId): void {
		if ($appId !== \OCP\Server::get(IAppManager::class)->cleanAppId($appId)) {
			throw new InvalidArgumentException(sprintf('Invalid app id "%s"', $appId));
		}
	}

	/**
	 * Verify the given anonymous kind is of the form `appid:kind`, the kind consisting of lowercase alphanumerics, `_` and `-`
	 * @throws InvalidArgumentException - If the kind is invalid
	 */
	private static function validateAnonymousKind(string $kind): void {
		if (preg_match('/^([^:]+):[a-z0-9_-]+$/D', $kind, $matches) !== 1) {
			throw new InvalidArgumentException(sprintf('Invalid source "%s"', $kind));
		}
		self::validateAppId($matches[1]);
	}
}
