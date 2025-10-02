<?php

/*
 * Copyright (c) Fusonic GmbH. All rights reserved.
 * Licensed under the MIT License. See LICENSE file in the project root for license information.
 */

declare(strict_types=1);

namespace Fusonic\OpenGraph;

use Fusonic\OpenGraph\Objects\ObjectBase;
use Fusonic\OpenGraph\Objects\Website;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Consumer that extracts Open Graph data from either a URL or an HTML string.
 */
class Consumer
{
    /**
     * When enabled, crawler will read content of title and meta description if no
     * Open Graph data is provided by target page.
     */
    public bool $useFallbackMode = false;

    /**
     * When enabled, crawler will throw exceptions for some crawling errors like unexpected
     * Open Graph elements.
     */
    public bool $debug = false;

    /**
     * @param ClientInterface|null         $client         a PSR-18 ClientInterface implementation
     * @param RequestFactoryInterface|null $requestFactory a PSR-17 RequestFactoryInterface implementation
     */
    public function __construct(
        private ?ClientInterface $client = null,
        private ?RequestFactoryInterface $requestFactory = null,
    ) {
    }

    /**
     * Fetches HTML content from the given URL and then crawls it for Open Graph data.
     *
     * @param string $url URL to be crawled
     *
     * @throws ClientExceptionInterface
     */
    public function loadUrl(string $url): ObjectBase
    {
        if (null === $this->client || null === $this->requestFactory) {
            throw new \LogicException(
                'To use loadUrl() you must provide $client and $requestFactory when instantiating the consumer.'
            );
        }

        $request = $this->requestFactory->createRequest('GET', $url);
        $response = $this->client->sendRequest($request);

        return $this->loadHtml($response->getBody()->getContents(), $url);
    }

    /**
     * Crawls the given HTML string for OpenGraph data.
     *
     * @param string      $html        HTML string, usually whole content of crawled web resource
     * @param string|null $fallbackUrl URL to use when fallback mode is enabled
     */
    public function loadHtml(string $html, ?string $fallbackUrl = null): ObjectBase
    {
        // Extract all data that can be found
        $page = $this->extractOpenGraphData($html);

        // Use the user's URL as fallback
        if ($this->useFallbackMode && null === $page->url) {
            $page->url = $fallbackUrl;
        }

        // Return result
        return $page;
    }

    private function extractOpenGraphData(string $content): ObjectBase
    {
        $crawler = new Crawler();
        $crawler->addHtmlContent(content: $content);

        $properties = [];
        foreach (['name', 'property'] as $t) {
            // Get all meta-tags starting with "og:"
            $ogMetaTags = $crawler->filter("meta[{$t}^='og:']");

            // Create clean property array
            $props = [];

            /** @var \DOMElement $tag */
            foreach ($ogMetaTags as $tag) {
                $name = strtolower(trim($tag->getAttribute($t)));
                $value = trim($tag->getAttribute('content'));
                $props[] = new Property($name, $value);
            }

            $properties = array_merge($properties, $props);
        }

        // Create new object
        $object = new Website();

        // Assign all properties to the object
        $object->assignProperties($properties, $this->debug);

        // Fallback for url
        if ($this->useFallbackMode && null === $object->url) {
            $urlElement = $crawler->filter("link[rel='canonical']")->first();
            if ($urlElement->count() > 0) {
                $object->url = trim($urlElement->attr('href') ?? '');
            }
        }

        // Fallback for title
        if ($this->useFallbackMode && null === $object->title) {
            $titleElement = $crawler->filter('title')->first();
            if ($titleElement->count() > 0) {
                $object->title = trim($titleElement->text());
            }
        }

        // Fallback for description
        if ($this->useFallbackMode && null === $object->description) {
            $descriptionElement = $crawler->filter("meta[property='description']")->first();
            if ($descriptionElement->count() > 0) {
                $object->description = trim($descriptionElement->attr('content') ?? '');
            }
        }

        return $object;
    }
}
