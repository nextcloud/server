<?php

namespace LanguageServerProtocol;

/**
 * Represents a diagnostic, such as a compiler error or warning. Diagnostic objects are only valid in the scope of a
 * resource.
 */
class Diagnostic
{
    /**
     * The range at which the message applies.
     *
     * @var Range
     */
    public $range;

    /**
     * The diagnostic's severity. Can be omitted. If omitted it is up to the
     * client to interpret diagnostics as error, warning, info or hint.
     *
     * @var int|null
     */
    public $severity;

    /**
     * The diagnostic's code. Can be omitted.
     *
     * @var int|string|null
     */
    public $code;

    /**
     * A human-readable string describing the source of this
     * diagnostic, e.g. 'typescript' or 'super lint'.
     *
     * @var string|null
     */
    public $source;

    /**
     * The diagnostic's message.
     *
     * @var string
     */
    public $message;

    /**
     * @param  string $message  The diagnostic's message
     * @param  Range  $range    The range at which the message applies
     * @param  int    $code     The diagnostic's code
     * @param  int    $severity DiagnosticSeverity
     * @param  string $source   A human-readable string describing the source of this diagnostic
     */
    public function __construct(string $message = null, Range $range, int $code = null, int $severity = null, string $source = null)
    {
        $this->message = $message;
        $this->range = $range;
        $this->code = $code;
        $this->severity = $severity;
        $this->source = $source;
    }
}
