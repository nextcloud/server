<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Command;

use OC\AppFramework\Http\Request;
use OCA\DAV\Server;
use OCP\Files\ISetupManager;
use OCP\IConfig;
use OCP\IRequestId;
use OCP\IUserManager;
use OCP\IUserSession;
use Sabre\DAV\Server as SabreServer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Lists all Sabre event listeners registered on the WebDAV server, together
 * with the event they listen to and their priority (lower runs first).
 *
 * The set of registered plugins depends on the request target, so use the
 * --uri option to inspect a specific subtree (e.g. calendars, address books).
 */
class ShowListenersCommand extends Command {
	public function __construct(
		private IConfig $config,
		private IRequestId $requestId,
		private IUserManager $userManager,
		private IUserSession $userSession,
		private ISetupManager $setupManager,
		private ListenerIntrospector $introspector,
	) {
		parent::__construct();
	}

	#[\Override]
	protected function configure(): void {
		$this
			->setName('dav:show-listeners')
			->setDescription('Show all Sabre event listeners with their event and priority')
			->addOption(
				'uri',
				null,
				InputOption::VALUE_REQUIRED,
				'DAV request path the server is built for; controls which subtree plugins are loaded (e.g. "calendars/admin", "addressbooks/admin", "files/admin")',
				'',
			)
			->addOption(
				'user',
				null,
				InputOption::VALUE_REQUIRED,
				'Set up the given user\'s session and filesystem so that plugins registered lazily during an authenticated request are listed too',
			)
			->addOption(
				'event',
				null,
				InputOption::VALUE_REQUIRED,
				'Only show listeners whose event name contains this string (case-insensitive)',
			)
			->addOption(
				'method',
				null,
				InputOption::VALUE_REQUIRED,
				'Show the resolved firing order for the given HTTP method (e.g. "GET"), merging wildcard and method-specific listeners the way Sabre does at emit time',
			);
	}

	#[\Override]
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$uri = (string)$input->getOption('uri');
		$user = $input->getOption('user');
		$eventFilter = (string)($input->getOption('event') ?? '');

		$withLazy = $user !== null && $user !== '';
		if ($withLazy) {
			$userObject = $this->userManager->get((string)$user);
			if ($userObject === null) {
				$output->writeln('<error>User "' . $user . '" does not exist.</error>');
				return self::FAILURE;
			}
			try {
				$this->userSession->setUser($userObject);
				$this->setupManager->tearDown();
				$this->setupManager->setupForUser($userObject);
			} catch (\Throwable $e) {
				$output->writeln('<error>Could not set up the filesystem for "' . $user . '": ' . $e->getMessage() . '</error>');
				$output->writeln('<comment>Continuing without lazily-registered listeners.</comment>');
				$withLazy = false;
			}
		}

		try {
			$davServer = new Server($this->buildRequest($uri), '/');
			if ($withLazy) {
				$this->triggerDeferredListeners($davServer->server, $output);
			}
		} catch (\Throwable $e) {
			$output->writeln('<error>Could not build the DAV server: ' . $e->getMessage() . '</error>');
			return self::FAILURE;
		}

		$method = (string)($input->getOption('method') ?? '');
		if ($method !== '') {
			if ($eventFilter !== '') {
				$output->writeln('<comment>--event is ignored when --method is given.</comment>');
			}
			return $this->showFiringOrder($davServer->server, strtoupper($method), $output);
		}

		$rows = $this->introspector->collectListeners($davServer->server);
		$registeredCount = count($rows);

		if ($eventFilter !== '') {
			$rows = array_values(array_filter(
				$rows,
				static fn (array $row): bool => stripos($row['event'], $eventFilter) !== false,
			));
		}

		if ($rows === []) {
			if ($registeredCount > 0) {
				$output->writeln('<info>No listeners match --event="' . $eventFilter . '" (' . $registeredCount . ' listener(s) registered for the given request).</info>');
			} else {
				$output->writeln('<info>No listeners registered for the given request.</info>');
			}
			return self::SUCCESS;
		}

		$table = new Table($output);
		$table->setHeaders(['Event', 'Priority', 'Listener']);
		$table->setRows(array_map(
			static fn (array $row): array => [$row['event'], $row['priority'], $row['listener']],
			$rows,
		));
		$table->render();

		$output->writeln('');
		$output->writeln('<comment>' . count($rows) . ' listener(s) shown for request URI "' . ($uri === '' ? '/' : $uri) . '".</comment>');
		if (!$withLazy) {
			$output->writeln('<comment>Note: some plugins (e.g. FilesPlugin, QuotaPlugin) register their listeners lazily inside a "beforeMethod:*" handler during an authenticated request. Pass --user=<uid> to list those too.</comment>');
		}

		return self::SUCCESS;
	}

	/**
	 * Builds and prints the resolved order in which listeners fire for the given
	 * HTTP method, across the method-scoped families and the method-independent
	 * lifecycle events that complete a request.
	 */
	private function showFiringOrder(SabreServer $server, string $method, OutputInterface $output): int {
		$this->renderChain($server, 'beforeMethod:' . $method, $output,
			'Always runs first. A listener returning false or throwing stops the request here (see skip rules below).');
		$this->renderChain($server, 'method:' . $method, $output,
			'Runs only if beforeMethod completed and preconditions held; a handler here serves the request.');
		$this->renderChain($server, 'afterMethod:' . $method, $output,
			'Runs only after the method was served successfully (skipped on the early-exit paths listed below).');

		$output->writeln('');
		$output->writeln('<comment>At most one of the two chains below normally runs per request — several early-exit paths run neither:</comment>');
		$this->renderChain($server, 'afterResponse', $output, 'Runs on the success path, after the response has been sent.');
		$this->renderChain($server, 'exception', $output, 'Runs on the failure path, when a listener or handler throws. If an afterResponse listener throws, both chains run.');

		$output->writeln('');
		$output->writeln('<comment>When chains are skipped for this request:</comment>');
		$output->writeln('<comment>  * a beforeMethod listener returns false -> method / afterMethod / afterResponse are skipped; that listener owns the response, and the exception chain does NOT run.</comment>');
		$output->writeln('<comment>  * a beforeMethod listener throws        -> method / afterMethod / afterResponse are skipped; the exception chain runs and Sabre sends an error response.</comment>');
		$output->writeln('<comment>  * conditional GET matches (304)         -> the 304 response is sent directly; method / afterMethod / afterResponse AND the exception chain are all skipped.</comment>');
		$output->writeln('<comment>  * any other precondition fails          -> PreconditionFailed is thrown, so the exception chain runs and Sabre sends a 412 response.</comment>');
		$output->writeln('<comment>  * no handler serves the method          -> NotImplemented is thrown, so the exception chain runs.</comment>');
		$output->writeln('<comment>  * an afterMethod listener returns false -> the response is never sent and afterResponse is skipped; the exception chain does NOT run.</comment>');

		return self::SUCCESS;
	}

	/**
	 * Renders the resolved firing order for a single event name as a table. An
	 * optional description clarifies when the chain runs and when it is skipped.
	 */
	private function renderChain(SabreServer $server, string $eventName, OutputInterface $output, ?string $description = null): void {
		$chain = $this->introspector->resolveFiringOrder($server, $eventName);

		$output->writeln('');
		$output->writeln('<info>' . $eventName . '</info> — ' . count($chain) . ' listener(s), executed top to bottom (lowest priority first):');
		if ($description !== null) {
			$output->writeln('  <comment>' . $description . '</comment>');
		}
		if ($chain === []) {
			$output->writeln('  <comment>(no listeners)</comment>');
			return;
		}

		$table = new Table($output);
		$table->setHeaders(['#', 'Priority', 'Registered on', 'Listener']);
		$table->setRows(array_map(
			static fn (array $row): array => [$row['order'], $row['priority'], $row['registeredOn'], $row['listener']],
			$chain,
		));
		$table->render();
	}

	/**
	 * Invokes the deferred registration handlers so file-related plugins (which
	 * the server only adds once auth and the filesystem are ready) register
	 * their listeners and become visible.
	 */
	private function triggerDeferredListeners(SabreServer $server, OutputInterface $output): void {
		$sourceFile = (new \ReflectionClass(Server::class))->getFileName();
		if ($sourceFile === false) {
			return;
		}

		foreach ($this->introspector->findDeferredHandlers($server, $sourceFile) as $handler) {
			try {
				$handler();
			} catch (\Throwable $e) {
				$output->writeln('<comment>Could not fully trigger lazy listeners: ' . $e->getMessage() . '</comment>');
			}
		}
	}

	private function buildRequest(string $uri): Request {
		$uri = '/' . ltrim($uri, '/');
		return new Request(
			[
				'server' => [
					'REQUEST_URI' => $uri,
					'REQUEST_METHOD' => 'PROPFIND',
					'SCRIPT_NAME' => '',
				],
				'method' => 'PROPFIND',
			],
			$this->requestId,
			$this->config,
		);
	}
}
