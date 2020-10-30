<?php
declare(strict_types = 1);
namespace Psalm\Internal\LanguageServer\Client;

use function Amp\call;
use Amp\Promise;
use JsonMapper;
use LanguageServerProtocol\Diagnostic;
use LanguageServerProtocol\TextDocumentIdentifier;
use LanguageServerProtocol\TextDocumentItem;
use Psalm\Internal\LanguageServer\ClientHandler;

/**
 * Provides method handlers for all textDocument/* methods
 */
class TextDocument
{
    /**
     * @var ClientHandler
     */
    private $handler;

    /**
     * @var JsonMapper
     */
    private $mapper;

    public function __construct(ClientHandler $handler, JsonMapper $mapper)
    {
        $this->handler = $handler;
        $this->mapper = $mapper;
    }

    /**
     * Diagnostics notification are sent from the server to the client to signal results of validation runs.
     *
     * @param Diagnostic[] $diagnostics
     *
     * @return Promise<void>
     */
    public function publishDiagnostics(string $uri, array $diagnostics): Promise
    {
        return $this->handler->notify('textDocument/publishDiagnostics', [
            'uri' => $uri,
            'diagnostics' => $diagnostics,
        ]);
    }

    /**
     * The content request is sent from a server to a client
     * to request the current content of a text document identified by the URI
     *
     * @param TextDocumentIdentifier $textDocument The document to get the content for
     *
     * @return Promise<TextDocumentItem> The document's current content
     */
    public function xcontent(TextDocumentIdentifier $textDocument): Promise
    {
        return call(
            /**
             * @return \Generator<int, Promise<object>, object, TextDocumentItem>
             */
            function () use ($textDocument) {
                /** @var Promise<object> */
                $promise = $this->handler->request(
                    'textDocument/xcontent',
                    ['textDocument' => $textDocument]
                );

                $result = yield $promise;

                /** @var TextDocumentItem */
                return $this->mapper->map($result, new TextDocumentItem);
            }
        );
    }
}
