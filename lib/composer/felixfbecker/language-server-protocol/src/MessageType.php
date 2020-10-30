<?php

namespace LanguageServerProtocol;

/**
 * Enum
 */
abstract class MessageType
{
    /**
     * An error message.
     */
    const ERROR = 1;

    /**
     * A warning message.
     */
    const WARNING = 2;

    /**
     * An information message.
     */
    const INFO = 3;

    /**
     * A log message.
     */
    const LOG = 4;
}
