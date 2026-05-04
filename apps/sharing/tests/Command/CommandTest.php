<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

use OC\Core\Command\Base;
use OCA\Sharing\Command\AddShareRecipient;
use OCA\Sharing\Command\AddShareSource;
use OCA\Sharing\Command\CreateShare;
use OCA\Sharing\Command\DeleteShare;
use OCA\Sharing\Command\GetShare;
use OCA\Sharing\Command\ListShares;
use OCA\Sharing\Command\RemoveShareRecipient;
use OCA\Sharing\Command\RemoveShareSource;
use OCA\Sharing\Command\SharingBase;
use OCA\Sharing\Command\UpdateSharePermission;
use OCA\Sharing\Command\UpdateShareProperty;
use OCA\Sharing\Command\UpdateShareState;
use OCP\Server;
use OCP\Sharing\IManager;
use OCP\Sharing\Permission\SharePermission;
use OCP\Sharing\Property\ShareProperty;
use OCP\Sharing\Recipient\ShareRecipient;
use OCP\Sharing\ShareAccessContext;
use OCP\Sharing\ShareState;
use OCP\Sharing\Source\ShareSource;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\Console\Input\Input;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\Output;
use Test\Sharing\AbstractManagerTests;

#[Group(name: 'DB')]
final class CommandTest extends AbstractManagerTests {
	/** @var list<class-string<SharingBase>> */
	private array $commandClasses;

	#[\Override]
	public function setUp(): void {
		parent::setUp();

		$this->commandClasses = [
			AddShareRecipient::class,
			AddShareSource::class,
			CreateShare::class,
			DeleteShare::class,
			GetShare::class,
			ListShares::class,
			RemoveShareRecipient::class,
			RemoveShareSource::class,
			UpdateSharePermission::class,
			UpdateShareProperty::class,
			UpdateShareState::class,
		];
	}

	public function testDefaultShareAccessContext(): void {
		foreach ($this->commandClasses as $class) {
			/** @psalm-suppress UnsafeInstantiation */
			$command = new $class(Server::get(IManager::class));
			$this->assertEquals(new ShareAccessContext(force: true), $command->accessContext, $class);
		}
	}

	/**
	 * @param class-string<SharingBase> $class
	 * @param list<list<mixed>> $arguments
	 * @param list<list<mixed>> $options
	 */
	private function runCommand(ShareAccessContext $accessContext, string $class, array $arguments, array $options): string {
		if (!in_array($class, $this->commandClasses, true)) {
			throw new \RuntimeException('Command class ' . $class . ' is not allowed to be used unless added to the array.');
		}

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
		$command = new $class(Server::get(IManager::class));

		// We have to override the access context because commands always use force, but the tests don't expect that.
		$command->accessContext = $accessContext;

		/** @psalm-suppress InaccessibleMethod */
		$exitCode = $command->execute($input, $output);
		if ($exitCode === Base::SUCCESS) {
			return $stdout;
		}

		throw new RuntimeException($stderr);
	}

	#[\Override]
	protected function searchRecipients(ShareAccessContext $accessContext, ?string $recipientTypeClass, string $query, int $limit, int $offset): array {
		// We don't have a command for this, so we just call the real manager to make the test pass.
		/** @psalm-suppress ArgumentTypeCoercion */
		return ShareRecipient::formatMultiple($this->manager->searchRecipients($accessContext, $recipientTypeClass, $query, $limit, $offset));
	}

	/**
	 * @return array<string, mixed>
	 */
	#[\Override]
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
		/** @psalm-suppress MixedReturnStatement */
		return json_decode($stdout, true, 512, JSON_THROW_ON_ERROR);
	}

	/**
	 * @return array<string, mixed>
	 */
	#[\Override]
	protected function updateShareState(ShareAccessContext $accessContext, string $id, ShareState $state): array {
		$stdout = $this->runCommand(
			$accessContext,
			UpdateShareState::class,
			[
				['id', $id],
				['state', $state->value],
			],
			[],
		);
		/** @psalm-suppress MixedReturnStatement */
		return json_decode($stdout, true, 512, JSON_THROW_ON_ERROR);
	}

	/**
	 * @return array<string, mixed>
	 */
	#[\Override]
	protected function addShareSource(ShareAccessContext $accessContext, string $id, ShareSource $source): array {
		$stdout = $this->runCommand(
			$accessContext,
			AddShareSource::class,
			[
				['id', $id],
				['class', $source->class],
				['value', $source->value],
			],
			[],
		);
		/** @psalm-suppress MixedReturnStatement */
		return json_decode($stdout, true, 512, JSON_THROW_ON_ERROR);
	}

	/**
	 * @return array<string, mixed>
	 */
	#[\Override]
	protected function removeShareSource(ShareAccessContext $accessContext, string $id, ShareSource $source): array {
		$stdout = $this->runCommand(
			$accessContext,
			RemoveShareSource::class,
			[
				['id', $id],
				['class', $source->class],
				['value', $source->value],
			],
			[],
		);
		/** @psalm-suppress MixedReturnStatement */
		return json_decode($stdout, true, 512, JSON_THROW_ON_ERROR);
	}

	/**
	 * @return array<string, mixed>
	 */
	#[\Override]
	protected function addShareRecipient(ShareAccessContext $accessContext, string $id, ShareRecipient $recipient): array {
		$stdout = $this->runCommand(
			$accessContext,
			AddShareRecipient::class,
			[
				['id', $id],
				['class', $recipient->class],
				['value', $recipient->value],
			],
			[],
		);
		/** @psalm-suppress MixedReturnStatement */
		return json_decode($stdout, true, 512, JSON_THROW_ON_ERROR);
	}

	/**
	 * @return array<string, mixed>
	 */
	#[\Override]
	protected function removeShareRecipient(ShareAccessContext $accessContext, string $id, ShareRecipient $recipient): array {
		$stdout = $this->runCommand(
			$accessContext,
			RemoveShareRecipient::class,
			[
				['id', $id],
				['class', $recipient->class],
				['value', $recipient->value],
			],
			[],
		);
		/** @psalm-suppress MixedReturnStatement */
		return json_decode($stdout, true, 512, JSON_THROW_ON_ERROR);
	}

	/**
	 * @return array<string, mixed>
	 */
	#[\Override]
	protected function updateShareProperty(ShareAccessContext $accessContext, string $id, ShareProperty $property): array {
		$stdout = $this->runCommand(
			$accessContext,
			UpdateShareProperty::class,
			[
				['id', $id],
				['class', $property->class],
				['value', $property->value],
			],
			[],
		);
		/** @psalm-suppress MixedReturnStatement */
		return json_decode($stdout, true, 512, JSON_THROW_ON_ERROR);
	}

	/**
	 * @return array<string, mixed>
	 */
	#[\Override]
	protected function updateSharePermission(ShareAccessContext $accessContext, string $id, SharePermission $permission): array {
		$stdout = $this->runCommand(
			$accessContext,
			UpdateSharePermission::class,
			[
				['id', $id],
				['class', $permission->class],
				['enabled', $permission->enabled ? 'true' : 'false'],
			],
			[],
		);
		/** @psalm-suppress MixedReturnStatement */
		return json_decode($stdout, true, 512, JSON_THROW_ON_ERROR);
	}

	#[\Override]
	protected function deleteShare(ShareAccessContext $accessContext, string $id): void {
		$this->runCommand(
			$accessContext,
			DeleteShare::class,
			[
				['id', $id],
			],
			[],
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	#[\Override]
	protected function getShare(ShareAccessContext $accessContext, string $id): array {
		$stdout = $this->runCommand(
			$accessContext,
			GetShare::class,
			[
				['id', $id],
			],
			[],
		);
		/** @psalm-suppress MixedReturnStatement */
		return json_decode($stdout, true, 512, JSON_THROW_ON_ERROR);
	}

	/**
	 * @return array<string, mixed>
	 */
	#[\Override]
	protected function listShares(ShareAccessContext $accessContext, ?string $sourceTypeClass, ?string $lastShareID, ?int $limit): array {
		$stdout = $this->runCommand(
			$accessContext,
			ListShares::class,
			[],
			[
				['source-class', $sourceTypeClass],
				['last-share-id', $lastShareID],
				['limit', $limit],
			],
		);
		/** @psalm-suppress MixedReturnStatement */
		return json_decode($stdout, true, 512, JSON_THROW_ON_ERROR);
	}
}
