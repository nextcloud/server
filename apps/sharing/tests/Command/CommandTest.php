<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Sharing\Tests\Command;

use Exception;
use NCU\Sharing\ISharingManager;
use NCU\Sharing\ISharingRegistry;
use NCU\Sharing\Permission\SharePermission;
use NCU\Sharing\Property\ShareProperty;
use NCU\Sharing\Recipient\ShareRecipient;
use NCU\Sharing\Share;
use NCU\Sharing\ShareAccessContext;
use NCU\Sharing\ShareState;
use NCU\Sharing\Source\ShareSource;
use OC\Core\Command\Base;
use OCA\Sharing\Command\AddShareRecipient;
use OCA\Sharing\Command\AddShareSource;
use OCA\Sharing\Command\CreateShare;
use OCA\Sharing\Command\DeleteShare;
use OCA\Sharing\Command\GetShare;
use OCA\Sharing\Command\GetShares;
use OCA\Sharing\Command\RemoveShareRecipient;
use OCA\Sharing\Command\RemoveShareSource;
use OCA\Sharing\Command\SelectSharePermissionPreset;
use OCA\Sharing\Command\SharingBase;
use OCA\Sharing\Command\UpdateSharePermission;
use OCA\Sharing\Command\UpdateShareProperty;
use OCA\Sharing\Command\UpdateShareRecipientSecret;
use OCA\Sharing\Command\UpdateShareState;
use OCP\HintException;
use OCP\IDBConnection;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\L10N\IFactory;
use OCP\Server;
use Override;
use PHPUnit\Framework\Attributes\Group;
use RuntimeException;
use Symfony\Component\Console\Input\Input;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\Output;
use Test\Sharing\AbstractSharingManagerTests;

/**
 * @psalm-import-type SharingShare from Share
 */
#[Group(name: 'DB')]
final class CommandTest extends AbstractSharingManagerTests {
	/** @var list<class-string<SharingBase>> */
	private array $commandClasses;

	#[Override]
	public function setUp(): void {
		parent::setUp();

		$this->commandClasses = [
			AddShareRecipient::class,
			AddShareSource::class,
			CreateShare::class,
			DeleteShare::class,
			GetShare::class,
			GetShares::class,
			RemoveShareRecipient::class,
			RemoveShareSource::class,
			SelectSharePermissionPreset::class,
			UpdateSharePermission::class,
			UpdateShareProperty::class,
			UpdateShareRecipientSecret::class,
			UpdateShareState::class,
		];
	}

	public function testDefaultShareAccessContext(): void {
		foreach ($this->commandClasses as $class) {
			/** @psalm-suppress UnsafeInstantiation */
			$command = new $class(
				Server::get(ISharingManager::class),
				Server::get(ISharingRegistry::class),
				Server::get(IFactory::class),
				Server::get(IURLGenerator::class),
				Server::get(IUserManager::class),
				Server::get(IDBConnection::class),
			);
			$this->assertEquals(new ShareAccessContext(overrideChecks: true), $command->accessContext, $class);
		}
	}

	/**
	 * @param class-string<SharingBase> $class
	 * @param list<list<mixed>> $arguments
	 * @param list<list<mixed>> $options
	 */
	private function runCommand(ShareAccessContext $accessContext, string $class, array $arguments, array $options): string {
		if (!in_array($class, $this->commandClasses, true)) {
			throw new RuntimeException('Command class ' . $class . ' is not allowed to be used unless added to the array.');
		}

		$options[] = ['actor', null];
		$input = $this->createMock(Input::class);
		$input
			->expects($this->exactly(count($arguments)))
			->method('getArgument')
			->willReturnMap($arguments);
		$input
			->expects($this->exactly(count($options)))
			->method('getOption')
			->willReturnMap($options);

		$stderr = '';
		$errorOutput = $this->createMock(Output::class);
		$errorOutput
			->method('writeln')
			->willReturnCallback(function (string $message) use (&$stderr): void {
				$stderr .= $message . "\n";
			});

		$stdout = '';
		$output = $this->createMock(ConsoleOutput::class);
		$output
			->method('writeln')
			->willReturnCallback(function (string $message) use (&$stdout): void {
				$stdout .= $message . "\n";
			});
		$output
			->method('getErrorOutput')
			->willReturn($errorOutput);

		/** @psalm-suppress UnsafeInstantiation */
		$command = new $class(
			Server::get(ISharingManager::class),
			Server::get(ISharingRegistry::class),
			Server::get(IFactory::class),
			Server::get(IURLGenerator::class),
			Server::get(IUserManager::class),
			Server::get(IDBConnection::class),
		);

		// We have to override the access context because commands always use force, but the tests don't expect that.
		$command->accessContext = $accessContext;

		/** @psalm-suppress InaccessibleMethod */
		$exitCode = $command->execute($input, $output);
		if ($exitCode === Base::SUCCESS) {
			return $stdout;
		}

		throw new HintException(rtrim($stderr, "\n"));
	}

	#[Override]
	protected function searchRecipients(ShareAccessContext $accessContext, ?array $filterRecipientTypeClasses, string $query, int $limit, int $offset, ?Share $forShare = null): array {
		// We don't have a command for this, so we just call the real manager to make the test pass.
		try {
			$this->dbConnection->beginTransaction();
			/** @psalm-suppress ArgumentTypeCoercion */
			$shares = ShareRecipient::formatMultiple($this->registry, Server::get(IFactory::class), Server::get(IURLGenerator::class), Server::get(IUserManager::class), $this->manager->searchRecipients($accessContext, $filterRecipientTypeClasses, $query, $limit, $offset, $forShare));
			$this->dbConnection->commit();
			return $shares;
		} catch (Exception $exception) {
			$this->dbConnection->rollBack();
			throw  $exception;
		}
	}

	/**
	 * @return SharingShare
	 */
	#[Override]
	protected function createShare(ShareAccessContext $accessContext): array {
		$this->assertNotNull($accessContext->currentUser);
		$stdout = $this->runCommand(
			$accessContext,
			CreateShare::class,
			[
				['owner', $accessContext->currentUser->getUID()],
			],
			[],
		);
		/** @var SharingShare */
		return json_decode($stdout, true, 512, JSON_THROW_ON_ERROR);
	}

	/**
	 * @return SharingShare
	 */
	#[Override]
	protected function updateShareState(ShareAccessContext $accessContext, Share $share, ShareState $state): array {
		$stdout = $this->runCommand(
			$accessContext,
			UpdateShareState::class,
			[
				['id', $share->id],
				['state', $state->value],
			],
			[],
		);
		/** @var SharingShare */
		return json_decode($stdout, true, 512, JSON_THROW_ON_ERROR);
	}

	/**
	 * @return SharingShare
	 */
	#[Override]
	protected function addShareSource(ShareAccessContext $accessContext, Share $share, ShareSource $source): array {
		$stdout = $this->runCommand(
			$accessContext,
			AddShareSource::class,
			[
				['id', $share->id],
				['class', $source->class],
				['value', $source->value],
			],
			[],
		);
		/** @var SharingShare */
		return json_decode($stdout, true, 512, JSON_THROW_ON_ERROR);
	}

	/**
	 * @return SharingShare
	 */
	#[Override]
	protected function removeShareSource(ShareAccessContext $accessContext, Share $share, ShareSource $source): array {
		$stdout = $this->runCommand(
			$accessContext,
			RemoveShareSource::class,
			[
				['id', $share->id],
				['class', $source->class],
				['value', $source->value],
			],
			[],
		);
		/** @var SharingShare */
		return json_decode($stdout, true, 512, JSON_THROW_ON_ERROR);
	}

	/**
	 * @return SharingShare
	 */
	#[Override]
	protected function addShareRecipient(ShareAccessContext $accessContext, Share $share, ShareRecipient $recipient): array {
		$stdout = $this->runCommand(
			$accessContext,
			AddShareRecipient::class,
			[
				['id', $share->id],
				['class', $recipient->class],
				['value', $recipient->value],
				['instance', $recipient->instance],
			],
			[],
		);
		/** @var SharingShare */
		return json_decode($stdout, true, 512, JSON_THROW_ON_ERROR);
	}

	/**
	 * @return SharingShare
	 */
	#[Override]
	protected function removeShareRecipient(ShareAccessContext $accessContext, Share $share, ShareRecipient $recipient): array {
		$stdout = $this->runCommand(
			$accessContext,
			RemoveShareRecipient::class,
			[
				['id', $share->id],
				['class', $recipient->class],
				['value', $recipient->value],
				['instance', $recipient->instance],
			],
			[],
		);
		/** @var SharingShare */
		return json_decode($stdout, true, 512, JSON_THROW_ON_ERROR);
	}

	/**
	 * @return SharingShare
	 */
	#[Override]
	protected function updateShareRecipientSecret(ShareAccessContext $accessContext, Share $share, ShareRecipient $recipient, string $secret): array {
		$stdout = $this->runCommand(
			$accessContext,
			UpdateShareRecipientSecret::class,
			[
				['id', $share->id],
				['class', $recipient->class],
				['value', $recipient->value],
				['instance', $recipient->instance],
				['secret', $secret],
			],
			[],
		);
		/** @var SharingShare */
		return json_decode($stdout, true, 512, JSON_THROW_ON_ERROR);
	}

	/**
	 * @return SharingShare
	 */
	#[Override]
	protected function updateShareProperty(ShareAccessContext $accessContext, Share $share, ShareProperty $property): array {
		$stdout = $this->runCommand(
			$accessContext,
			UpdateShareProperty::class,
			[
				['id', $share->id],
				['class', $property->class],
				['value', $property->value],
			],
			[],
		);
		/** @var SharingShare */
		return json_decode($stdout, true, 512, JSON_THROW_ON_ERROR);
	}

	/**
	 * @return SharingShare
	 */
	#[Override]
	protected function updateSharePermission(ShareAccessContext $accessContext, Share $share, SharePermission $permission): array {
		$stdout = $this->runCommand(
			$accessContext,
			UpdateSharePermission::class,
			[
				['id', $share->id],
				['class', $permission->class],
				['enabled', $permission->enabled ? 'true' : 'false'],
			],
			[],
		);
		/** @var SharingShare */
		return json_decode($stdout, true, 512, JSON_THROW_ON_ERROR);
	}

	/**
	 * @return SharingShare
	 */
	#[Override]
	protected function selectSharePermissionPreset(ShareAccessContext $accessContext, Share $share, string $permissionPresetClass): array {
		$stdout = $this->runCommand(
			$accessContext,
			SelectSharePermissionPreset::class,
			[
				['id', $share->id],
				['permission-preset', $permissionPresetClass],
			],
			[],
		);
		/** @var SharingShare */
		return json_decode($stdout, true, 512, JSON_THROW_ON_ERROR);
	}

	#[Override]
	protected function deleteShare(ShareAccessContext $accessContext, Share $share): void {
		$this->runCommand(
			$accessContext,
			DeleteShare::class,
			[
				['id', $share->id],
			],
			[],
		);
	}

	/**
	 * @return SharingShare
	 */
	#[Override]
	protected function getShare(ShareAccessContext $accessContext, string $id): array {
		$stdout = $this->runCommand(
			$accessContext,
			GetShare::class,
			[
				['id', $id],
			],
			[],
		);
		/** @var SharingShare */
		return json_decode($stdout, true, 512, JSON_THROW_ON_ERROR);
	}

	/**
	 * @return SharingShare[]
	 */
	#[Override]
	protected function getShares(ShareAccessContext $accessContext, ?string $filterSourceTypeClass, ?string $filterSourceTypeValue, ?string $lastShareID, ?int $limit): array {
		$stdout = $this->runCommand(
			$accessContext,
			GetShares::class,
			[],
			[
				['filter-source-type-class', $filterSourceTypeClass],
				['filter-source-type-value', $filterSourceTypeValue],
				['last-share-id', $lastShareID],
				['limit', $limit],
			],
		);
		/** @var SharingShare[] */
		return json_decode($stdout, true, 512, JSON_THROW_ON_ERROR);
	}
}
