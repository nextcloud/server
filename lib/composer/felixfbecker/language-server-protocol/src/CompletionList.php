<?php

namespace LanguageServerProtocol;

/**
 * Represents a collection of completion items to be presented in
 * the editor.
 */
class CompletionList
{
    /**
     * This list it not complete. Further typing should result in recomputing this
     * list.
     *
     * @var bool
     */
    public $isIncomplete;

    /**
     * The completion items.
     *
     * @var CompletionItem[]
     */
    public $items;

    /**
     * @param CompletionItem[] $items        The completion items.
     * @param bool             $isIncomplete This list it not complete. Further typing should result in recomputing this list.
     */
    public function __construct(array $items = [], bool $isIncomplete = false)
    {
        $this->items = $items;
        $this->isIncomplete = $isIncomplete;
    }
}
