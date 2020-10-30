<?php
declare(strict_types = 1);
namespace Psalm\Internal\LanguageServer;

use AdvancedJsonRpc;
use function Amp\asyncCoroutine;
use function Amp\call;
use Amp\Promise;
use Amp\Success;
use function array_combine;
use function array_keys;
use function array_map;
use function array_shift;
use function array_unshift;
use function explode;
use function implode;
use LanguageServerProtocol\ClientCapabilities;
use LanguageServerProtocol\CompletionOptions;
use LanguageServerProtocol\Diagnostic;
use LanguageServerProtocol\DiagnosticSeverity;
use LanguageServerProtocol\InitializeResult;
use LanguageServerProtocol\Position;
use LanguageServerProtocol\Range;
use LanguageServerProtocol\ServerCapabilities;
use LanguageServerProtocol\SignatureHelpOptions;
use LanguageServerProtocol\TextDocumentSyncKind;
use LanguageServerProtocol\TextDocumentSyncOptions;
use function max;
use function parse_url;
use Psalm\Internal\Analyzer\IssueData;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\LanguageServer\Server\TextDocument;
use function rawurlencode;
use function str_replace;
use function strpos;
use function substr;
use Throwable;
use function trim;
use function urldecode;

/**
 * @internal
 */
class LanguageServer extends AdvancedJsonRpc\Dispatcher
{
    /**
     * Handles textDocument/* method calls
     *
     * @var ?Server\TextDocument
     */
    public $textDocument;

    /**
     * @var ProtocolReader
     */
    protected $protocolReader;

    /**
     * @var ProtocolWriter
     */
    protected $protocolWriter;

    /**
     * @var LanguageClient
     */
    public $client;

    /**
     * @var ProjectAnalyzer
     */
    protected $project_analyzer;

    /**
     * @var array<string, string>
     */
    protected $onsave_paths_to_analyze = [];

    /**
     * @var array<string, string>
     */
    protected $onchange_paths_to_analyze = [];

    public function __construct(
        ProtocolReader $reader,
        ProtocolWriter $writer,
        ProjectAnalyzer $project_analyzer
    ) {
        parent::__construct($this, '/');
        $this->project_analyzer = $project_analyzer;

        $this->protocolWriter = $writer;

        $this->protocolReader = $reader;
        $this->protocolReader->on(
            'close',
            function (): void {
                $this->shutdown();
                $this->exit();
            }
        );
        $this->protocolReader->on(
            'message',
            asyncCoroutine(
                /**
                 * @return \Generator<int, \Amp\Promise, mixed, void>
                 */
                function (Message $msg): \Generator {
                    if (!$msg->body) {
                        return;
                    }

                    // Ignore responses, this is the handler for requests and notifications
                    if (AdvancedJsonRpc\Response::isResponse($msg->body)) {
                        return;
                    }

                    /** @psalm-suppress UndefinedPropertyFetch */
                    if ($msg->body->method === 'textDocument/signatureHelp') {
                        $this->doAnalysis();
                    }

                    $result = null;
                    $error = null;
                    try {
                        // Invoke the method handler to get a result
                        /**
                         * @var Promise
                         * @psalm-suppress UndefinedDocblockClass
                         */
                        $dispatched = $this->dispatch($msg->body);
                        /** @psalm-suppress MixedAssignment */
                        $result = yield $dispatched;
                    } catch (AdvancedJsonRpc\Error $e) {
                        // If a ResponseError is thrown, send it back in the Response
                        $error = $e;
                    } catch (Throwable $e) {
                        // If an unexpected error occurred, send back an INTERNAL_ERROR error response
                        $error = new AdvancedJsonRpc\Error(
                            (string) $e,
                            AdvancedJsonRpc\ErrorCode::INTERNAL_ERROR,
                            null,
                            $e
                        );
                    }
                    // Only send a Response for a Request
                    // Notifications do not send Responses
                    /**
                     * @psalm-suppress UndefinedPropertyFetch
                     * @psalm-suppress MixedArgument
                     */
                    if (AdvancedJsonRpc\Request::isRequest($msg->body)) {
                        if ($error !== null) {
                            $responseBody = new AdvancedJsonRpc\ErrorResponse($msg->body->id, $error);
                        } else {
                            $responseBody = new AdvancedJsonRpc\SuccessResponse($msg->body->id, $result);
                        }
                        yield $this->protocolWriter->write(new Message($responseBody));
                    }
                }
            )
        );

        $this->protocolReader->on(
            'readMessageGroup',
            function (): void {
                $this->doAnalysis();
            }
        );

        $this->client = new LanguageClient($reader, $writer);

        $this->verboseLog("Language server has started.");
    }

    /**
     * The initialize request is sent as the first request from the client to the server.
     *
     * @param ClientCapabilities $capabilities The capabilities provided by the client (editor)
     * @param string|null $rootPath The rootPath of the workspace. Is null if no folder is open.
     * @param int|null $processId The process Id of the parent process that started the server.
     * Is null if the process has not been started by another process. If the parent process is
     * not alive then the server should exit (see exit notification) its process.
     * @psalm-return Promise<InitializeResult>
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function initialize(
        ClientCapabilities $capabilities,
        ?string $rootPath = null,
        ?int $processId = null
    ): Promise {
        return call(
            /** @return \Generator<int, true, mixed, InitializeResult> */
            function () use ($capabilities, $rootPath, $processId) {
                $this->verboseLog("Initializing...");
                $this->clientStatus('initializing');

                // Eventually, this might block on something. Leave it as a generator.
                /** @psalm-suppress TypeDoesNotContainType */
                if (false) {
                    yield true;
                }

                $this->verboseLog("Initializing: Getting code base...");
                $this->clientStatus('initializing', 'getting code base');
                $codebase = $this->project_analyzer->getCodebase();

                $this->verboseLog("Initializing: Scanning files...");
                $this->clientStatus('initializing', 'scanning files');
                $codebase->scanFiles($this->project_analyzer->threads);

                $this->verboseLog("Initializing: Registering stub files...");
                $this->clientStatus('initializing', 'registering stub files');
                $codebase->config->visitStubFiles($codebase, null);

                if ($this->textDocument === null) {
                    $this->textDocument = new TextDocument(
                        $this,
                        $codebase,
                        $this->project_analyzer->onchange_line_limit
                    );
                }

                $serverCapabilities = new ServerCapabilities();

                $textDocumentSyncOptions = new TextDocumentSyncOptions();

                if ($this->project_analyzer->onchange_line_limit === 0) {
                    $textDocumentSyncOptions->change = TextDocumentSyncKind::NONE;
                } else {
                    $textDocumentSyncOptions->change = TextDocumentSyncKind::FULL;
                }

                $serverCapabilities->textDocumentSync = $textDocumentSyncOptions;

                // Support "Find all symbols"
                $serverCapabilities->documentSymbolProvider = false;
                // Support "Find all symbols in workspace"
                $serverCapabilities->workspaceSymbolProvider = false;
                // Support "Go to definition"
                $serverCapabilities->definitionProvider = true;
                // Support "Find all references"
                $serverCapabilities->referencesProvider = false;
                // Support "Hover"
                $serverCapabilities->hoverProvider = true;
                // Support "Completion"

                if ($this->project_analyzer->provide_completion) {
                    $serverCapabilities->completionProvider = new CompletionOptions();
                    $serverCapabilities->completionProvider->resolveProvider = false;
                    $serverCapabilities->completionProvider->triggerCharacters = ['$', '>', ':'];
                }

                $serverCapabilities->signatureHelpProvider = new SignatureHelpOptions(['(', ',']);

                // Support global references
                $serverCapabilities->xworkspaceReferencesProvider = false;
                $serverCapabilities->xdefinitionProvider = false;
                $serverCapabilities->dependenciesProvider = false;

                $this->verboseLog("Initializing: Complete.");
                $this->clientStatus('initialized');
                return new InitializeResult($serverCapabilities);
            }
        );
    }

    /**
     * @psalm-suppress PossiblyUnusedMethod
     *
     */
    public function initialized(): void
    {
        $this->clientStatus('running');
    }

    public function queueTemporaryFileAnalysis(string $file_path, string $uri): void
    {
        $this->onchange_paths_to_analyze[$file_path] = $uri;
    }

    public function queueFileAnalysis(string $file_path, string $uri): void
    {
        $this->onsave_paths_to_analyze[$file_path] = $uri;
    }

    public function doAnalysis(): void
    {
        $this->clientStatus('analyzing');

        try {
            $codebase = $this->project_analyzer->getCodebase();

            $all_files_to_analyze = $this->onchange_paths_to_analyze + $this->onsave_paths_to_analyze;

            if (!$all_files_to_analyze) {
                return;
            }

            if ($this->onsave_paths_to_analyze) {
                $codebase->reloadFiles($this->project_analyzer, array_keys($this->onsave_paths_to_analyze));
            }

            if ($this->onchange_paths_to_analyze) {
                $codebase->reloadFiles($this->project_analyzer, array_keys($this->onchange_paths_to_analyze));
            }

            $all_file_paths_to_analyze = array_keys($all_files_to_analyze);
            $codebase->analyzer->addFilesToAnalyze(
                array_combine($all_file_paths_to_analyze, $all_file_paths_to_analyze)
            );
            $codebase->analyzer->analyzeFiles($this->project_analyzer, 1, false);

            $this->emitIssues($all_files_to_analyze);

            $this->onchange_paths_to_analyze = [];
            $this->onsave_paths_to_analyze = [];
        } finally {
            // we are done, so set the status back to running
            $this->clientStatus('running');
        }
    }

    /**
     * @param array<string, string> $uris
     *
     */
    public function emitIssues(array $uris): void
    {
        $data = \Psalm\IssueBuffer::clear();

        foreach ($uris as $file_path => $uri) {
            $diagnostics = array_map(
                function (IssueData $issue_data) use ($file_path) : Diagnostic {
                    //$check_name = $issue->check_name;
                    $description = $issue_data->message;
                    $severity = $issue_data->severity;

                    $start_line = max($issue_data->line_from, 1);
                    $end_line = $issue_data->line_to;
                    $start_column = $issue_data->column_from;
                    $end_column = $issue_data->column_to;
                    // Language server has 0 based lines and columns, phan has 1-based lines and columns.
                    $range = new Range(
                        new Position($start_line - 1, $start_column - 1),
                        new Position($end_line - 1, $end_column - 1)
                    );
                    switch ($severity) {
                        case \Psalm\Config::REPORT_INFO:
                            $diagnostic_severity = DiagnosticSeverity::WARNING;
                            break;
                        case \Psalm\Config::REPORT_ERROR:
                        default:
                            $diagnostic_severity = DiagnosticSeverity::ERROR;
                            break;
                    }
                    $diagnostic = new Diagnostic(
                        $description,
                        $range,
                        null,
                        $diagnostic_severity,
                        'Psalm'
                    );

                    //$code = 'PS' . \str_pad((string) $issue_data->shortcode, 3, "0", \STR_PAD_LEFT);
                    $code = $issue_data->link;

                    if ($this->project_analyzer->language_server_use_extended_diagnostic_codes) {
                        // Added in VSCode 1.43.0 and will be part of the LSP 3.16.0 standard.
                        // Since this new functionality is not backwards compatible, we use a
                        // configuration option so the end user must opt in to it using the cli argument.
                        // https://github.com/microsoft/vscode/blob/1.43.0/src/vs/vscode.d.ts#L4688-L4699

                        /** @psalm-suppress InvalidPropertyAssignmentValue */
                        $diagnostic->code = [
                            "value" => $code,
                            "target" => $issue_data->link,
                        ];
                    } else {
                        // the Diagnostic constructor only takes `int` for the code, but the property can be
                        // `int` or `string`, so we set the property directly because we want to use a `string`
                        $diagnostic->code = $code;
                    }

                    return $diagnostic;
                },
                $data[$file_path] ?? []
            );

            $this->client->textDocument->publishDiagnostics($uri, $diagnostics);
        }
    }

    /**
     * The shutdown request is sent from the client to the server. It asks the server to shut down,
     * but to not exit (otherwise the response might not be delivered correctly to the client).
     * There is a separate exit notification that asks the server to exit.
     *
     * @psalm-return Promise<null>
     */
    public function shutdown(): Promise
    {
        $this->clientStatus('closing');
        $this->verboseLog("Shutting down...");
        $codebase = $this->project_analyzer->getCodebase();
        $scanned_files = $codebase->scanner->getScannedFiles();
        $codebase->file_reference_provider->updateReferenceCache(
            $codebase,
            $scanned_files
        );
        $this->clientStatus('closed');
        return new Success(null);
    }

    /**
     * A notification to ask the server to exit its process.
     *
     */
    public function exit(): void
    {
        exit(0);
    }


    /**
     * Send log message to the client
     *
     * @param string $message The log message to send to the client.
     * @psalm-param 1|2|3|4 $type
     * @param int $type The log type:
     *  - 1 = Error
     *  - 2 = Warning
     *  - 3 = Info
     *  - 4 = Log
     */
    private function verboseLog(string $message, int $type = 4): Promise
    {
        if ($this->project_analyzer->language_server_verbose) {
            try {
                return $this->client->logMessage(
                    '[Psalm ' .PSALM_VERSION. ' - PHP Language Server] ' . $message,
                    $type
                );
            } catch (\Throwable $err) {
                // do nothing
            }
        }
        return new Success(null);
    }

    /**
     * Send status message to client.  This is the same as sending a log message,
     * except this is meant for parsing by the client to present status updates in a UI.
     *
     * @param string $status The log message to send to the client. Should not contain colons `:`.
     * @param string|null $additional_info This is additional info that the client
     *                                       can use as part of the display message.
     */
    private function clientStatus(string $status, ?string $additional_info = null): Promise
    {
        try {
            // here we send a notification to the client using the telemetry notification method
            return $this->client->logMessage(
                $status . (!empty($additional_info) ? ': ' . $additional_info : ''),
                3,
                'telemetry/event'
            );
        } catch (\Throwable $err) {
            return new Success(null);
        }
    }

    /**
     * Transforms an absolute file path into a URI as used by the language server protocol.
     *
     * @psalm-pure
     */
    public static function pathToUri(string $filepath): string
    {
        $filepath = trim(str_replace('\\', '/', $filepath), '/');
        $parts = explode('/', $filepath);
        // Don't %-encode the colon after a Windows drive letter
        $first = array_shift($parts);
        if (substr($first, -1) !== ':') {
            $first = rawurlencode($first);
        }
        $parts = array_map('rawurlencode', $parts);
        array_unshift($parts, $first);
        $filepath = implode('/', $parts);

        return 'file:///' . $filepath;
    }

    /**
     * Transforms URI into file path
     *
     *
     */
    public static function uriToPath(string $uri): string
    {
        $fragments = parse_url($uri);
        if ($fragments === false
            || !isset($fragments['scheme'])
            || $fragments['scheme'] !== 'file'
            || !isset($fragments['path'])
        ) {
            throw new \InvalidArgumentException("Not a valid file URI: $uri");
        }

        $filepath = urldecode((string) $fragments['path']);

        if (strpos($filepath, ':') !== false) {
            if ($filepath[0] === '/') {
                $filepath = substr($filepath, 1);
            }
            $filepath = str_replace('/', '\\', $filepath);
        }

        $realpath = \realpath($filepath);
        if ($realpath !== false) {
            return $realpath;
        }

        return $filepath;
    }
}
