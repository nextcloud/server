<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Template;

use OCP\App\IAppManager;
use OCP\AppFramework\Http\Events\BeforeTemplateRenderedEvent;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IRequest;
use OCP\Server;
use OCP\Template\ITemplate;
use OCP\Template\ITemplateManager;
use OCP\Template\TemplateNotFoundException;
use Psr\Log\LoggerInterface;

class TemplateManager implements ITemplateManager {
	public function __construct(
		private IAppManager $appManager,
		private IEventDispatcher $eventDispatcher,
	) {
	}

	/**
	 * @param TemplateResponse::RENDER_AS_* $renderAs
	 * @throws TemplateNotFoundException if the template cannot be found
	 */
	public function getTemplate(string $app, string $name, string $renderAs = TemplateResponse::RENDER_AS_BLANK, bool $registerCall = true): ITemplate {
		return new Template($app, $name, $renderAs, $registerCall);
	}

	/**
	 * Shortcut to print a simple page for guests
	 * @param string $application The application we render the template for
	 * @param string $name Name of the template
	 * @param array $parameters Parameters for the template
	 */
	public function printGuestPage(string $application, string $name, array $parameters = []): void {
		$content = $this->getTemplate($application, $name, $name === 'error' ? $name : 'guest');
		foreach ($parameters as $key => $value) {
			$content->assign($key, $value);
		}
		$content->printPage();
	}

	/**
	 * Print a fatal error page and terminates the script
	 * @param string $error_msg The error message to show
	 * @param string $hint An optional hint message - needs to be properly escape
	 */
	public function printErrorPage(string $error_msg, string $hint = '', int $statusCode = 500): never {
		if ($this->appManager->isEnabledForUser('theming') && !$this->appManager->isAppLoaded('theming')) {
			$this->appManager->loadApp('theming');
		}

		if ($error_msg === $hint) {
			// If the hint is the same as the message there is no need to display it twice.
			$hint = '';
		}
		$errors = [['error' => $error_msg, 'hint' => $hint]];

		http_response_code($statusCode);
		try {
			// Try rendering themed html error page
			$response = new TemplateResponse(
				'',
				'error',
				['errors' => $errors],
				TemplateResponse::RENDER_AS_ERROR,
				$statusCode,
			);
			$event = new BeforeTemplateRenderedEvent(false, $response);
			$this->eventDispatcher->dispatchTyped($event);
			print($response->render());
		} catch (\Throwable $e1) {
			$logger = \OCP\Server::get(LoggerInterface::class);
			$logger->error('Rendering themed error page failed. Falling back to un-themed error page.', [
				'app' => 'core',
				'exception' => $e1,
			]);

			try {
				// Try rendering unthemed html error page
				$content = $this->getTemplate('', 'error', 'error', false);
				$content->assign('errors', $errors);
				$content->printPage();
			} catch (\Exception $e2) {
				// If nothing else works, fall back to plain text error page
				$logger->error("$error_msg $hint", ['app' => 'core']);
				$logger->error('Rendering un-themed error page failed. Falling back to plain text error page.', [
					'app' => 'core',
					'exception' => $e2,
				]);

				header('Content-Type: text/plain; charset=utf-8');
				print("$error_msg $hint");
			}
		}
		die();
	}

	/**
	 * print error page using Exception details
	 */
	public function printExceptionErrorPage(\Throwable $exception, int $statusCode = 503): never {
		$debug = false;
		http_response_code($statusCode);
		try {
			$debug = (bool)Server::get(\OC\SystemConfig::class)->getValue('debug', false);
			$serverLogsDocumentation = Server::get(\OC\SystemConfig::class)->getValue('documentation_url.server_logs', '');
			$request = Server::get(IRequest::class);
			$content = $this->getTemplate('', 'exception', 'error', false);
			$content->assign('errorClass', get_class($exception));
			$content->assign('errorMsg', $exception->getMessage());
			$content->assign('errorCode', $exception->getCode());
			$content->assign('file', $exception->getFile());
			$content->assign('line', $exception->getLine());
			$content->assign('exception', $exception);
			$content->assign('debugMode', $debug);
			$content->assign('serverLogsDocumentation', $serverLogsDocumentation);
			$content->assign('remoteAddr', $request->getRemoteAddress());
			$content->assign('requestID', $request->getId());
			$content->printPage();
		} catch (\Exception $e) {
			try {
				$logger = Server::get(LoggerInterface::class);
				$logger->error($exception->getMessage(), ['app' => 'core', 'exception' => $exception]);
				$logger->error($e->getMessage(), ['app' => 'core', 'exception' => $e]);
			} catch (\Throwable $e) {
				// no way to log it properly - but to avoid a white page of death we send some output
				$this->printPlainErrorPage($e, $debug);

				// and then throw it again to log it at least to the web server error log
				throw $e;
			}

			$this->printPlainErrorPage($e, $debug);
		}
		die();
	}

	/**
	 * @psalm-taint-escape has_quotes
	 * @psalm-taint-escape html
	 */
	private function fakeEscapeForPlainText(string $str): string {
		return $str;
	}

	private function printPlainErrorPage(\Throwable $exception, bool $debug = false): void {
		header('Content-Type: text/plain; charset=utf-8');
		print("Internal Server Error\n\n");
		print("The server encountered an internal error and was unable to complete your request.\n");
		print("Please contact the server administrator if this error reappears multiple times, please include the technical details below in your report.\n");
		print("More details can be found in the server log.\n");

		if ($debug) {
			print("\n");
			print($exception->getMessage() . ' ' . $exception->getFile() . ' at ' . $exception->getLine() . "\n");
			print($this->fakeEscapeForPlainText($exception->getTraceAsString()));
		}
	}
}
