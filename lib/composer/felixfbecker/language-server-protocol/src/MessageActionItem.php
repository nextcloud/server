<?php

namespace LanguageServerProtocol;

class MessageActionItem
{
    /**
     * A short title like 'Retry', 'Open Log' etc.
     *
     * @var string
     */
    public $title;

    public function __construct(string $title = null)
    {
        $this->title = $title;
    }
}
