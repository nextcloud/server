<?php

namespace LanguageServerProtocol;

class MarkedString
{
    /**
     * @var string
     */
    public $language;

    /**
     * @var string
     */
    public $value;

    public function __construct(string $language = null, string $value = null)
    {
        $this->language = $language;
        $this->value = $value;
    }
}
