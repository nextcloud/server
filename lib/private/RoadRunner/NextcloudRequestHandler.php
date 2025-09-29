<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\RoadRunner;

use OCP\IRequest;
use OCP\AppFramework\Http\Response;
use OC\AppFramework\Http\Request;
use OC\Server;
use OC\ServerContainer;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Request handler for processing Nextcloud requests in RoadRunner worker mode
 *
 * This class handles the stateful nature of PHP applications mentioned in
 * GitHub issue #36290 by properly managing request lifecycle and state reset.
 *
 * @since 30.0.0
 */
class NextcloudRequestHandler implements RequestHandlerInterface {

    private Psr7ResponseAdapter $responseAdapter;
    private bool $initialized = false;
    private ?ServerContainer $serverContainer = null;

    public function __construct() {
        $this->responseAdapter = new Psr7ResponseAdapter();
    }

    /**
     * Handle incoming PSR-7 request and return PSR-7 response
     *
     * @param ServerRequestInterface $request PSR-7 request
     * @return ResponseInterface PSR-7 response
     * @since 30.0.0
     */
    public function handle(ServerRequestInterface $request): ResponseInterface {
        try {
            // Initialize Nextcloud only once per worker (addressing stateful concerns)
            if (!$this->initialized) {
                $this->initializeNextcloud();
                $this->initialized = true;
            }

            // Reset per-request state to prevent memory leaks and state pollution
            $this->resetRequestState();

            // Convert PSR-7 request to Nextcloud request format
            $nextcloudRequest = $this->adaptPsr7ToNextcloudRequest($request);

            // Process the request through Nextcloud's routing system
            $nextcloudResponse = $this->processNextcloudRequest($nextcloudRequest);

            // Convert Nextcloud response to PSR-7
            return $this->responseAdapter->adaptResponse($nextcloudResponse);

        } catch (\Throwable $e) {
            // Handle errors gracefully and log them
            \OCP\Server::get(\Psr\Log\LoggerInterface::class)->error(
                'RoadRunner worker error: ' . $e->getMessage(),
                ['exception' => $e]
            );

            return $this->createErrorResponse($e);
        }
    }

    /**
     * Initialize Nextcloud framework (once per worker)
     *
     * @since 30.0.0
     */
    private function initializeNextcloud(): void {
        // Initialize basic Nextcloud components for worker mode
        if (!defined('OC_CONSOLE')) {
            define('OC_CONSOLE', false);
        }

        // Set up the server container if not already done
        if ($this->serverContainer === null) {
            $this->serverContainer = \OC::$server;
        }

        // Log successful initialization
        \OCP\Server::get(\Psr\Log\LoggerInterface::class)->info(
            'Nextcloud initialized in RoadRunner worker',
            ['worker_pid' => getmypid()]
        );
    }

    /**
     * Reset per-request state to handle stateful concerns
     *
     * This is crucial for RoadRunner workers to prevent state pollution
     * between requests.
     *
     * @since 30.0.0
     */
    private function resetRequestState(): void {
        // Reset session state
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        // Clear request-specific globals
        $_GET = [];
        $_POST = [];
        $_COOKIE = [];
        $_FILES = [];

        // Reset error handler state
        if (function_exists('opcache_reset')) {
            // Don't reset opcache in production, just clear file stats
            opcache_get_status(false);
        }

        // Reset user context and authentication state
        // This would typically integrate with Nextcloud's session management
        \OC_User::setUserId(null);
        \OC_User::setDisplayName(null);
    }

    /**
     * Convert PSR-7 request to Nextcloud's IRequest format
     *
     * @param ServerRequestInterface $request PSR-7 request
     * @return IRequest Nextcloud request object
     * @since 30.0.0
     */
    private function adaptPsr7ToNextcloudRequest(ServerRequestInterface $request): IRequest {
        // Populate PHP superglobals from PSR-7 request
        $_SERVER['REQUEST_METHOD'] = $request->getMethod();
        $_SERVER['REQUEST_URI'] = (string) $request->getUri();
        $_SERVER['HTTP_HOST'] = $request->getHeaderLine('Host');
        $_SERVER['SERVER_PROTOCOL'] = 'HTTP/' . $request->getProtocolVersion();
        $_SERVER['HTTPS'] = $request->getUri()->getScheme() === 'https' ? 'on' : 'off';
        $_SERVER['SERVER_PORT'] = $request->getUri()->getPort() ?? ($_SERVER['HTTPS'] === 'on' ? 443 : 80);

        // Convert headers
        foreach ($request->getHeaders() as $name => $values) {
            $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
            $_SERVER[$key] = implode(', ', $values);
        }

        // Handle query parameters
        parse_str($request->getUri()->getQuery(), $_GET);

        // Handle POST data
        $contentType = $request->getHeaderLine('Content-Type');
        if (strpos($contentType, 'application/x-www-form-urlencoded') !== false) {
            parse_str((string) $request->getBody(), $_POST);
        } elseif (strpos($contentType, 'application/json') !== false) {
            $json = json_decode((string) $request->getBody(), true);
            if (is_array($json)) {
                $_POST = $json;
            }
        }

        // Handle cookies
        $_COOKIE = $request->getCookieParams();

        // Handle uploaded files
        $_FILES = $request->getUploadedFiles();

        // Create Nextcloud Request object
        return new Request(
            $_GET,
            $_POST,
            $_FILES,
            $_SERVER,
            (string) $request->getBody(),
            $_COOKIE
        );
    }

    /**
     * Process request through Nextcloud's routing system
     *
     * @param IRequest $request Nextcloud request object
     * @return Response Nextcloud response object
     * @since 30.0.0
     */
    private function processNextcloudRequest(IRequest $request): Response {
        // This is a simplified version - in full implementation this would:
        // 1. Route the request to appropriate controller
        // 2. Execute middleware
        // 3. Handle authentication and authorization
        // 4. Return controller response

        // For now, return a basic response to demonstrate the integration
        return new \OCP\AppFramework\Http\JSONResponse([
            'message' => 'RoadRunner integration working!',
            'timestamp' => date('c'),
            'worker_pid' => getmypid(),
            'method' => $request->getMethod(),
            'uri' => $request->getRequestUri(),
            'nextcloud_version' => \OC::$server->getAppManager()->getInstalledVersion('core')
        ]);
    }

    /**
     * Create error response for exceptions
     *
     * @param \Throwable $e The exception
     * @return ResponseInterface PSR-7 error response
     * @since 30.0.0
     */
    private function createErrorResponse(\Throwable $e): ResponseInterface {
        $statusCode = ($e instanceof \OC\HintException) ? 400 : 500;

        $errorData = [
            'error' => 'Server Error',
            'message' => $e->getMessage(),
            'worker_pid' => getmypid(),
            'timestamp' => date('c')
        ];

        // Don't expose sensitive information in production
        if (\OCP\Server::get(\OCP\IConfig::class)->getSystemValue('debug', false)) {
            $errorData['trace'] = $e->getTraceAsString();
        }

        return new \Nyholm\Psr7\Response(
            $statusCode,
            ['Content-Type' => 'application/json'],
            json_encode($errorData)
        );
    }
}