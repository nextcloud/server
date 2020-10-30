<?php

namespace LanguageServerProtocol;

/**
 * Defines how the host (editor) should sync document changes to the language server.
 */
abstract class TextDocumentSyncKind
{
    /**
     * Documents should not be synced at all.
     */
    const NONE = 0;

    /**
     * Documents are synced by always sending the full content of the document.
     */
    const FULL = 1;

    /**
     * Documents are synced by sending the full content on open. After that only
     * incremental updates to the document are sent.
     */
    const INCREMENTAL = 2;
}
