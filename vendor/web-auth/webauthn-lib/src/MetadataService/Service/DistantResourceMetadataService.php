<?php

declare(strict_types=1);

namespace Webauthn\MetadataService\Service;

use ParagonIE\ConstantTime\Base64;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Webauthn\AttestationStatement\AttestationStatementSupportManager;
use Webauthn\Denormalizer\WebauthnSerializerFactory;
use Webauthn\Event\CanDispatchEvents;
use Webauthn\Event\MetadataStatementFound;
use Webauthn\Event\NullEventDispatcher;
use Webauthn\Exception\MetadataStatementLoadingException;
use Webauthn\Exception\MissingMetadataStatementException;
use Webauthn\MetadataService\Statement\MetadataStatement;
use function sprintf;

final class DistantResourceMetadataService implements MetadataService, CanDispatchEvents
{
    private ?MetadataStatement $statement = null;

    private EventDispatcherInterface $dispatcher;

    private readonly ?SerializerInterface $serializer;

    /**
     * @param array<string, string> $additionalHeaderParameters
     */
    public function __construct(
        private readonly ?RequestFactoryInterface $requestFactory,
        private readonly ClientInterface|HttpClientInterface $httpClient,
        private readonly string $uri,
        private readonly bool $isBase64Encoded = false,
        private readonly array $additionalHeaderParameters = [],
        ?SerializerInterface $serializer = null,
    ) {
        if ($requestFactory !== null && ! $httpClient instanceof HttpClientInterface) {
            trigger_deprecation(
                'web-auth/metadata-service',
                '4.7.0',
                'The parameter "$requestFactory" will be removed in 5.0.0. Please set it to null and set an Symfony\Contracts\HttpClient\HttpClientInterface as "$httpClient" argument.'
            );
        }
        $this->serializer = $serializer ?? (new WebauthnSerializerFactory(
            AttestationStatementSupportManager::create()
        ))->create();
        $this->dispatcher = new NullEventDispatcher();
    }

    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher): void
    {
        $this->dispatcher = $eventDispatcher;
    }

    /**
     * @param array<string, mixed> $additionalHeaderParameters
     */
    public static function create(
        ?RequestFactoryInterface $requestFactory,
        ClientInterface|HttpClientInterface $httpClient,
        string $uri,
        bool $isBase64Encoded = false,
        array $additionalHeaderParameters = [],
        ?SerializerInterface $serializer = null
    ): self {
        return new self($requestFactory, $httpClient, $uri, $isBase64Encoded, $additionalHeaderParameters, $serializer);
    }

    public function list(): iterable
    {
        $this->loadData();
        $this->statement !== null || throw MetadataStatementLoadingException::create();
        $aaguid = $this->statement->aaguid;
        if ($aaguid === null) {
            yield from [];
        } else {
            yield from [$aaguid];
        }
    }

    public function has(string $aaguid): bool
    {
        $this->loadData();
        $this->statement !== null || throw MetadataStatementLoadingException::create();

        return $aaguid === $this->statement->aaguid;
    }

    public function get(string $aaguid): MetadataStatement
    {
        $this->loadData();
        $this->statement !== null || throw MetadataStatementLoadingException::create();

        if ($aaguid === $this->statement->aaguid) {
            $this->dispatcher->dispatch(MetadataStatementFound::create($this->statement));

            return $this->statement;
        }

        throw MissingMetadataStatementException::create($aaguid);
    }

    private function loadData(): void
    {
        if ($this->statement !== null) {
            return;
        }

        $content = $this->fetch();
        if ($this->isBase64Encoded) {
            $content = Base64::decode($content, true);
        }
        if ($this->serializer !== null) {
            $this->statement = $this->serializer->deserialize($content, MetadataStatement::class, 'json');
            return;
        }

        $this->statement = MetadataStatement::createFromString($content);
    }

    private function fetch(): string
    {
        if ($this->httpClient instanceof HttpClientInterface) {
            $content = $this->sendSymfonyRequest();
        } else {
            $content = $this->sendPsrRequest();
        }
        $content !== '' || throw MetadataStatementLoadingException::create(
            'Unable to contact the server. The response has no content'
        );

        return $content;
    }

    private function sendPsrRequest(): string
    {
        $request = $this->requestFactory->createRequest('GET', $this->uri);
        foreach ($this->additionalHeaderParameters as $k => $v) {
            $request = $request->withHeader($k, $v);
        }
        $response = $this->httpClient->sendRequest($request);
        $response->getStatusCode() === 200 || throw MetadataStatementLoadingException::create(sprintf(
            'Unable to contact the server. Response code is %d',
            $response->getStatusCode()
        ));
        $response->getBody()
            ->rewind();

        return $response->getBody()
            ->getContents();
    }

    private function sendSymfonyRequest(): string
    {
        $response = $this->httpClient->request('GET', $this->uri, [
            'headers' => $this->additionalHeaderParameters,
        ]);
        $response->getStatusCode() === 200 || throw MetadataStatementLoadingException::create(sprintf(
            'Unable to contact the server. Response code is %d',
            $response->getStatusCode()
        ));

        return $response->getContent();
    }
}
