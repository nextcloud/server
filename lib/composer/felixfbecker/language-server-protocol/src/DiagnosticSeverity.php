<?php

namespace LanguageServerProtocol;

abstract class DiagnosticSeverity
{
    /**
     * Reports an error.
     */
    const ERROR = 1;

    /**
     * Reports a warning.
     */
    const WARNING = 2;

    /**
     * Reports an information.
     */
    const INFORMATION = 3;

    /**
     * Reports a hint.
     */
    const HINT = 4;
}
