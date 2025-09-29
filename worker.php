<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/**
 * RoadRunner Worker Entry Point for Nextcloud
 *
 * This file serves as the main entry point for RoadRunner workers running Nextcloud.
 * It addresses the performance enhancement request in GitHub issue #36290.
 *
 * Key features:
 * - Keeps PHP processes in memory for better performance
 * - Proper state management for stateful PHP applications
 * - PSR-7 compatibility layer for Nextcloud responses
 * - Graceful error handling and recovery
 */

use Spiral\RoadRunner\Worker;
use Spiral\RoadRunner\Http\PSR7Worker;
use OC\RoadRunner\NextcloudRequestHandler;

// Bootstrap Nextcloud
require_once __DIR__ . '/lib/base.php';

// Ensure we have the required dependencies
if (!class_exists('Spiral\RoadRunner\Worker')) {
    http_response_code(500);
    echo json_encode([
        'error' => 'RoadRunner dependencies not found',
        'message' => 'Please install spiral/roadrunner-http and spiral/roadrunner-worker via Composer'
    ]);
    exit(1);
}

// Create RoadRunner worker
$worker = Worker::create();

// Create PSR-7 HTTP worker
$psrWorker = new PSR7Worker($worker);

// Create Nextcloud request handler
$handler = new NextcloudRequestHandler();

// Log worker startup
if (function_exists('\\OCP\\Server::get')) {
    \OCP\Server::get(\Psr\Log\LoggerInterface::class)->info(
        'RoadRunner worker started',
        [
            'worker_pid' => getmypid(),
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time')
        ]
    );
}

// Main worker loop
while ($request = $psrWorker->waitRequest()) {
    try {
        if ($request === null) {
            break;
        }

        // Process the request through Nextcloud
        $response = $handler->handle($request);

        // Send response back to RoadRunner
        $psrWorker->respond($response);

    } catch (\Throwable $e) {
        // Log critical errors
        if (function_exists('\\OCP\\Server::get')) {
            \OCP\Server::get(\Psr\Log\LoggerInterface::class)->critical(
                'Critical worker error: ' . $e->getMessage(),
                [
                    'exception' => $e,
                    'worker_pid' => getmypid()
                ]
            );
        } else {
            error_log('RoadRunner Worker Critical Error: ' . $e->getMessage());
        }

        // Send 500 error response
        $errorResponse = new \Nyholm\Psr7\Response(
            500,
            ['Content-Type' => 'application/json'],
            json_encode([
                'error' => 'Critical Worker Error',
                'message' => 'The worker encountered a fatal error and cannot continue',
                'worker_pid' => getmypid(),
                'timestamp' => date('c')
            ])
        );

        try {
            $psrWorker->respond($errorResponse);
        } catch (\Throwable $responseError) {
            // If we can't even send an error response, log it and exit
            error_log('Failed to send error response: ' . $responseError->getMessage());
            break;
        }
    }
}

// Cleanup on worker shutdown
if (function_exists('\\OCP\\Server::get')) {
    \OCP\Server::get(\Psr\Log\LoggerInterface::class)->info(
        'RoadRunner worker shutting down',
        ['worker_pid' => getmypid()]
    );
}