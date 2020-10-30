<?php

namespace LanguageServerProtocol;

/**
 * Options controlling what is sent to the server with save notifications.
 */
class SaveOptions
{
    /**
     * The client is supposed to include the content on save.
     * @var bool|null
     */
    public $includeText;
}
