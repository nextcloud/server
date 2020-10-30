<?php

namespace LanguageServerProtocol;

/**
 * The file event type. Enum
 */
abstract class FileChangeType
{
    /**
     * The file got created.
     */
    const CREATED = 1;

    /**
     * The file got changed.
     */
    const CHANGED = 2;

    /**
     * The file got deleted.
     */
    const DELETED = 3;
}
