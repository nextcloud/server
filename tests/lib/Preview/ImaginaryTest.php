<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Preview;

use OC\Preview\Imaginary;
use OCP\Files\File;
use OCP\Http\Client\IClient;
use OCP\Http\Client\IClientService;
use OCP\Http\Client\IResponse;
use OCP\IConfig;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

class ImaginaryTest extends \Test\TestCase {
    private IConfig&MockObject $config;
    private IClientService&MockObject $clientService;
    private IClient&MockObject $client;
    private LoggerInterface&MockObject $logger;

    protected function setUp(): void {
        parent::setUp();

        $this->config = $this->createMock(IConfig::class);
        $this->clientService = $this->createMock(IClientService::class);
        $this->client = $this->createMock(IClient::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        // Wire mocks into the server container so Imaginary picks them up in constructor
        $this->overwriteService(IConfig::class, $this->config);
        $this->overwriteService(IClientService::class, $this->clientService);
        $this->overwriteService(LoggerInterface::class, $this->logger);

        $this->clientService->method('newClient')->willReturn($this->client);
    }

    public function testSendsContentTypeHeaderAndStreamsBody(): void {
        // Config values needed by provider
        $this->config->method('getSystemValueInt')
            ->with('preview_max_filesize_image', 50)
            ->willReturn(50);
        $this->config->method('getSystemValueString')
            ->willReturnCallback(function (string $key, $default = '') {
                return match ($key) {
                    'preview_imaginary_url' => 'http://imaginary:8080',
                    'preview_imaginary_key' => '',
                    'preview_format' => 'jpeg',
                    default => $default,
                };
            });
        $this->config->method('getAppValue')
            ->willReturnCallback(function (string $app, string $key, string $default) {
                // Use default quality settings
                return $default;
            });

        // File mock with a small readable stream
        $file = $this->createMock(File::class);
        $file->method('getSize')->willReturn(1024);
        $file->method('getMimeType')->willReturn('image/png');
        $stream = fopen('php://temp', 'w+');
        fwrite($stream, "\x89PNG\r\n\x1a\nmock");
        rewind($stream);
        $file->method('fopen')->with('r')->willReturn($stream);

        // Capture options passed to post()
        $captured = null;

        $response = $this->createMock(IResponse::class);
        $response->method('getStatusCode')->willReturn(200);
        $response->method('getHeader')
            ->willReturnCallback(function (string $key) {
                return match ($key) {
                    'Content-Type' => 'image/png',
                    'Image-Width' => '64',
                    'Image-Height' => '64',
                    default => ''
                };
            });
        $respStream = fopen('php://temp', 'w+');
        fwrite($respStream, str_repeat('\0', 32));
        rewind($respStream);
        $response->method('getBody')->willReturn($respStream);

        $this->client->expects($this->once())
            ->method('post')
            ->with(
                'http://imaginary:8080/pipeline',
                $this->callback(function (array $opts) use (&$captured) {
                    $captured = $opts;
                    // Assert the fix: Content-Type header present and body is a stream
                    return isset($opts['headers']['Content-Type'])
                        && $opts['headers']['Content-Type'] === 'image/png'
                        && isset($opts['body'])
                        && \is_resource($opts['body']);
                })
            )
            ->willReturn($response);

        $provider = new Imaginary([]);
        $img = $provider->getCroppedThumbnail($file, 128, 128, false);

        $this->assertNotNull($img, 'Expected a valid image back from Imaginary provider');
        $this->assertTrue($img->valid(), 'Image should be valid');
        $this->assertArrayHasKey('headers', $captured);
        $this->assertSame('image/png', $captured['headers']['Content-Type']);
    }
}
