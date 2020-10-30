<?php
declare(strict_types = 1);
namespace Psalm\Internal\LanguageServer\Server;

use Amp\Promise;
use Amp\Success;
use function count;
use function error_log;
use LanguageServerProtocol\CompletionList;
use LanguageServerProtocol\Hover;
use LanguageServerProtocol\Location;
use LanguageServerProtocol\MarkedString;
use LanguageServerProtocol\Position;
use LanguageServerProtocol\Range;
use LanguageServerProtocol\TextDocumentIdentifier;
use LanguageServerProtocol\TextDocumentItem;
use LanguageServerProtocol\VersionedTextDocumentIdentifier;
use Psalm\Codebase;
use Psalm\Internal\LanguageServer\LanguageServer;
use function substr_count;

/**
 * Provides method handlers for all textDocument/* methods
 */
class TextDocument
{
    /**
     * @var LanguageServer
     */
    protected $server;

    /**
     * @var Codebase
     */
    protected $codebase;

    /** @var ?int */
    protected $onchange_line_limit;

    public function __construct(
        LanguageServer $server,
        Codebase $codebase,
        ?int $onchange_line_limit
    ) {
        $this->server = $server;
        $this->codebase = $codebase;
        $this->onchange_line_limit = $onchange_line_limit;
    }

    /**
     * The document open notification is sent from the client to the server to signal newly opened text documents. The
     * document's truth is now managed by the client and the server must not try to read the document's truth using the
     * document's uri.
     *
     * @param \LanguageServerProtocol\TextDocumentItem $textDocument the document that was opened
     */
    public function didOpen(TextDocumentItem $textDocument): void
    {
        $file_path = LanguageServer::uriToPath($textDocument->uri);

        if (!$this->codebase->config->isInProjectDirs($file_path)) {
            error_log($file_path . ' is not in project');

            return;
        }

        $this->codebase->file_provider->openFile($file_path);

        $this->server->queueFileAnalysis($file_path, $textDocument->uri);
    }

    public function didSave(TextDocumentItem $textDocument): void
    {
        $file_path = LanguageServer::uriToPath($textDocument->uri);

        if (!$this->codebase->config->isInProjectDirs($file_path)) {
            return;
        }

        // reopen file
        $this->codebase->removeTemporaryFileChanges($file_path);
        $this->codebase->file_provider->setOpenContents($file_path, $textDocument->text);

        $this->server->queueFileAnalysis($file_path, $textDocument->uri);
    }

    /**
     * The document change notification is sent from the client to the server to signal changes to a text document.
     *
     * @param \LanguageServerProtocol\VersionedTextDocumentIdentifier $textDocument
     * @param \LanguageServerProtocol\TextDocumentContentChangeEvent[] $contentChanges
     */
    public function didChange(VersionedTextDocumentIdentifier $textDocument, array $contentChanges): void
    {
        $file_path = \Psalm\Internal\LanguageServer\LanguageServer::uriToPath($textDocument->uri);

        if (!$this->codebase->config->isInProjectDirs($file_path)) {
            return;
        }

        if ($this->onchange_line_limit === 0) {
            return;
        }

        if (count($contentChanges) === 1 && $contentChanges[0]->range === null) {
            $new_content = $contentChanges[0]->text;
        } else {
            throw new \UnexpectedValueException('Not expecting partial diff');
        }

        if ($this->onchange_line_limit !== null) {
            if (substr_count($new_content, "\n") > $this->onchange_line_limit) {
                return;
            }
        }

        $this->codebase->addTemporaryFileChanges($file_path, $new_content);
        $this->server->queueTemporaryFileAnalysis($file_path, $textDocument->uri);
    }

    /**
     * The document close notification is sent from the client to the server when the document got closed in the client.
     * The document's truth now exists where the document's uri points to (e.g. if the document's uri is a file uri the
     * truth now exists on disk).
     *
     * @param \LanguageServerProtocol\TextDocumentIdentifier $textDocument The document that was closed
     *
     */
    public function didClose(TextDocumentIdentifier $textDocument): void
    {
        $file_path = LanguageServer::uriToPath($textDocument->uri);

        $this->codebase->file_provider->closeFile($file_path);
        $this->server->client->textDocument->publishDiagnostics($textDocument->uri, []);
    }

    /**
     * The goto definition request is sent from the client to the server to resolve the definition location of a symbol
     * at a given text document position.
     *
     * @param TextDocumentIdentifier $textDocument The text document
     * @param Position $position The position inside the text document
     * @psalm-return Promise<Location>|Promise<null>
     */
    public function definition(TextDocumentIdentifier $textDocument, Position $position): Promise
    {
        $file_path = LanguageServer::uriToPath($textDocument->uri);

        try {
            $reference_location = $this->codebase->getReferenceAtPosition($file_path, $position);
        } catch (\Psalm\Exception\UnanalyzedFileException $e) {
            $this->codebase->file_provider->openFile($file_path);
            $this->server->queueFileAnalysis($file_path, $textDocument->uri);

            return new Success(null);
        }

        if ($reference_location === null) {
            return new Success(null);
        }

        [$reference] = $reference_location;

        $code_location = $this->codebase->getSymbolLocation($file_path, $reference);

        if (!$code_location) {
            return new Success(null);
        }

        return new Success(
            new Location(
                LanguageServer::pathToUri($code_location->file_path),
                new Range(
                    new Position($code_location->getLineNumber() - 1, $code_location->getColumn() - 1),
                    new Position($code_location->getEndLineNumber() - 1, $code_location->getEndColumn() - 1)
                )
            )
        );
    }

    /**
     * The hover request is sent from the client to the server to request
     * hover information at a given text document position.
     *
     * @param TextDocumentIdentifier $textDocument The text document
     * @param Position $position The position inside the text document
     * @psalm-return Promise<Hover>|Promise<null>
     */
    public function hover(TextDocumentIdentifier $textDocument, Position $position): Promise
    {
        $file_path = LanguageServer::uriToPath($textDocument->uri);

        try {
            $reference_location = $this->codebase->getReferenceAtPosition($file_path, $position);
        } catch (\Psalm\Exception\UnanalyzedFileException $e) {
            $this->codebase->file_provider->openFile($file_path);
            $this->server->queueFileAnalysis($file_path, $textDocument->uri);

            return new Success(null);
        }

        if ($reference_location === null) {
            return new Success(null);
        }

        [$reference, $range] = $reference_location;

        $symbol_information = $this->codebase->getSymbolInformation($file_path, $reference);

        if ($symbol_information === null) {
            return new Success(null);
        }

        $contents = [];
        $contents[] = new MarkedString('php', $symbol_information);

        return new Success(new Hover($contents, $range));
    }

    /**
     * The Completion request is sent from the client to the server to compute completion items at a given cursor
     * position. Completion items are presented in the IntelliSense user interface. If computing full completion items
     * is expensive, servers can additionally provide a handler for the completion item resolve request
     * ('completionItem/resolve'). This request is sent when a completion item is selected in the user interface. A
     * typically use case is for example: the 'textDocument/completion' request doesn't fill in the documentation
     * property for returned completion items since it is expensive to compute. When the item is selected in the user
     * interface then a 'completionItem/resolve' request is sent with the selected completion item as a param. The
     * returned completion item should have the documentation property filled in.
     *
     * @param TextDocumentIdentifier The text document
     * @param Position $position The position
     * @psalm-return Promise<array<empty, empty>>|Promise<CompletionList>
     */
    public function completion(TextDocumentIdentifier $textDocument, Position $position): Promise
    {
        $this->server->doAnalysis();

        $file_path = LanguageServer::uriToPath($textDocument->uri);
        if (!$this->codebase->config->isInProjectDirs($file_path)) {
            return new Success([]);
        }

        try {
            $completion_data = $this->codebase->getCompletionDataAtPosition($file_path, $position);
        } catch (\Psalm\Exception\UnanalyzedFileException $e) {
            $this->codebase->file_provider->openFile($file_path);
            $this->server->queueFileAnalysis($file_path, $textDocument->uri);

            return new Success([]);
        }

        if (!$completion_data) {
            error_log('completion not found at ' . $position->line . ':' . $position->character);

            return new Success([]);
        }

        [$recent_type, $gap, $offset] = $completion_data;

        if ($gap === '->' || $gap === '::') {
            $completion_items = $this->codebase->getCompletionItemsForClassishThing($recent_type, $gap);
        } else {
            $completion_items = $this->codebase->getCompletionItemsForPartialSymbol($recent_type, $offset, $file_path);
        }

        return new Success(new CompletionList($completion_items, false));
    }

    public function signatureHelp(TextDocumentIdentifier $textDocument, Position $position): Promise
    {
        $file_path = LanguageServer::uriToPath($textDocument->uri);

        try {
            $argument_location = $this->codebase->getFunctionArgumentAtPosition($file_path, $position);
        } catch (\Psalm\Exception\UnanalyzedFileException $e) {
            $this->codebase->file_provider->openFile($file_path);
            $this->server->queueFileAnalysis($file_path, $textDocument->uri);

            return new Success(new \LanguageServerProtocol\SignatureHelp());
        }

        if ($argument_location === null) {
            return new Success(new \LanguageServerProtocol\SignatureHelp());
        }

        $signature_information = $this->codebase->getSignatureInformation($argument_location[0]);

        if (!$signature_information) {
            return new Success(new \LanguageServerProtocol\SignatureHelp());
        }

        return new Success(new \LanguageServerProtocol\SignatureHelp([
            $signature_information,
        ], 0, $argument_location[1]));
    }
}
