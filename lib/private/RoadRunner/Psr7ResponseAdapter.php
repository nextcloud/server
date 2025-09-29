<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\RoadRunner;

use OCP\AppFramework\Http\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Nyholm\Psr7\Response as Psr7Response;
use Nyholm\Psr7\Stream;

/**
 * Adapter to convert Nextcloud's Response objects to PSR-7 compatible responses
 *
 * This class addresses the PSR-7 compatibility requirement mentioned in
 * GitHub issue #36290 for RoadRunner integration.
 *
 * @since 30.0.0
 */
class Psr7ResponseAdapter {

    /**
     * Convert a Nextcloud Response to PSR-7 ResponseInterface
     *
     * @param Response $nextcloudResponse The Nextcloud response object
     * @return ResponseInterface PSR-7 compatible response
     * @since 30.0.0
     */
    public function adaptResponse(Response $nextcloudResponse): ResponseInterface {
        // Get response data
        $statusCode = $nextcloudResponse->getStatus();
        $headers = $nextcloudResponse->getHeaders();
        $body = $nextcloudResponse->render();

        // Create PSR-7 stream from body
        $stream = Stream::create($body);

        // Create PSR-7 response
        $psr7Response = new Psr7Response(
            $statusCode,
            $headers,
            $stream
        );

        // Handle cookies if present
        $cookies = $this->extractCookies($nextcloudResponse);
        foreach ($cookies as $cookie) {
            $cookieHeader = $this->formatCookieHeader($cookie);
            $psr7Response = $psr7Response->withAddedHeader('Set-Cookie', $cookieHeader);
        }

        return $psr7Response;
    }

    /**
     * Extract cookies from Nextcloud Response using reflection
     *
     * This is necessary because cookies are private properties in Response class
     *
     * @param Response $response The Nextcloud response
     * @return array Array of cookie data
     * @since 30.0.0
     */
    private function extractCookies(Response $response): array {
        try {
            $reflection = new \ReflectionClass($response);
            $cookiesProperty = $reflection->getProperty('cookies');
            $cookiesProperty->setAccessible(true);
            return $cookiesProperty->getValue($response) ?? [];
        } catch (\ReflectionException $e) {
            // If reflection fails, return empty array
            return [];
        }
    }

    /**
     * Format cookie for Set-Cookie header
     *
     * @param array $cookie Cookie data array
     * @return string Formatted cookie header string
     * @since 30.0.0
     */
    private function formatCookieHeader(array $cookie): string {
        $cookieString = ($cookie['name'] ?? '') . '=' . ($cookie['value'] ?? '');

        if (isset($cookie['expire']) && $cookie['expire'] !== null) {
            $cookieString .= '; expires=' . gmdate('D, d M Y H:i:s T', $cookie['expire']);
        }

        if (isset($cookie['maxAge']) && $cookie['maxAge'] !== null) {
            $cookieString .= '; Max-Age=' . $cookie['maxAge'];
        }

        if (isset($cookie['path']) && $cookie['path'] !== null) {
            $cookieString .= '; path=' . $cookie['path'];
        }

        if (isset($cookie['domain']) && $cookie['domain'] !== null) {
            $cookieString .= '; domain=' . $cookie['domain'];
        }

        if (isset($cookie['secure']) && $cookie['secure']) {
            $cookieString .= '; secure';
        }

        if (isset($cookie['httpOnly']) && $cookie['httpOnly']) {
            $cookieString .= '; HttpOnly';
        }

        if (isset($cookie['sameSite']) && $cookie['sameSite'] !== null) {
            $cookieString .= '; SameSite=' . $cookie['sameSite'];
        }

        return $cookieString;
    }
}