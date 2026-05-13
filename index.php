<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

require_once __DIR__ . '/lib/versioncheck.php';

use OC\ServiceUnavailableException;
use OC\User\LoginException;
use OCP\HintException;
use OCP\IRequest;
use OCP\Security\Bruteforce\MaxDelayReached;
use OCP\Server;
use OCP\Template\ITemplateManager;
use Psr\Log\LoggerInterface;

/**
 * Respond with JSON for non-HTML requests, otherwise render an HTML page.
 * If a template name is provided, render it instead of the default error page template.
 */
function respondWithHtmlOrJsonError(int $statusCode, string $message, ?string $templateName = null): never
{
	$request = Server::get(IRequest::class);

	// TODO: consider parsing the Accept header properly instead of checking for "html".
	if (stripos($request->getHeader('Accept'), 'html') === false) {
		http_response_code($statusCode);
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(['message' => $message]);
		exit();
	}

	$templateManager = Server::get(ITemplateManager::class);

	if ($templateName !== null) {
		// Guest pages do not set the HTTP status code; do it here.
		http_response_code($statusCode);
		$templateManager->printGuestPage('core', $templateName);
		exit();
	}

	$templateManager->printErrorPage($message, $message, $statusCode);
}

// Main entrypoint
// TODO: consider whether other error paths should also support a non-HTML response (e.g. ServiceUnavailableException, Exception, HintException).

try {
	require_once __DIR__ . '/lib/base.php';

	$logger = Server::get(LoggerInterface::class);
	$templateManager = Server::get(ITemplateManager::class);

	OC::handleRequest();
} catch (ServiceUnavailableException $ex) {
	// Server-side failure: log it because it may require admin attention.
	$logger->error($ex->getMessage(), ['app' => 'index', 'exception' => $ex]);

	$templateManager->printExceptionErrorPage($ex, 503);
} catch (HintException $ex) {
	try {
		// Expected client-side failure; return an appropriate response without logging.
		$templateManager->printErrorPage($ex->getMessage(), $ex->getHint(), 503);
	} catch (Exception $ex2) {
		try {
			// Error-page rendering failed; log both failures if possible before falling back again.
			$logger->error($ex->getMessage(), ['app' => 'index', 'exception' => $ex]);
			$logger->error($ex2->getMessage(), ['app' => 'index', 'exception' => $ex2]);
		} catch (Throwable $e) {
			// Logging failed as well, so avoid a white page of death and continue to the last resort.
		}

		// Last resort: try the exception-based error page after the direct template render failed.
		$templateManager->printExceptionErrorPage($ex, 500);
	}
} catch (LoginException $ex) {
	// Expected client-side failure; return an appropriate response without logging.
	respondWithHtmlOrJsonError(401, $ex->getMessage());
} catch (MaxDelayReached $ex) {
	// Expected client-side failure; return an appropriate response without logging.
	respondWithHtmlOrJsonError(429, $ex->getMessage(), '429');
} catch (Exception $ex) {
	// Server-side failure: log it because it may require admin attention.
	$logger->error($ex->getMessage(), ['app' => 'index', 'exception' => $ex]);

	$templateManager->printExceptionErrorPage($ex, 500);
} catch (Error $ex) {
	try {
		// Fatal-error handling path: try to log the original error before rendering the fallback page.
		$logger->error($ex->getMessage(), ['app' => 'index', 'exception' => $ex]);
	} catch (Error $e) {
		// Last resort: even logging failed, so emit a plain-text response and rethrow for the webserver log.
		http_response_code(500);
		header('Content-Type: text/plain; charset=utf-8');

		$timestamp = gmdate('Y-m-d H:i:s \U\T\C');

		print("Internal Server Error\n\n");
		print("The server encountered an error and could not complete your request.\n");
		print("If this continues, please contact the administrator and provide the timestamp below\n");
		print("along with a brief description of what you were trying to do.\n\n");

		print("----------------------------------------\n");
		print("Error timestamp: {$timestamp}\n");
		print("----------------------------------------\n\n");

		print("For administrators: check the application and webserver logs for details.\n");

		throw $ex;
	}

	// Logging succeeded, so attempt the detailed exception page.
	$templateManager->printExceptionErrorPage($ex, 500);
}
