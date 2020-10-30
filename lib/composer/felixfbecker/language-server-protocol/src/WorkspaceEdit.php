<?php

namespace LanguageServerProtocol;

/**
 * A workspace edit represents changes to many resources managed in the workspace.
 */
class WorkspaceEdit
{
    /**
     * Holds changes to existing resources. Associative Array from URI to TextEdit
     *
     * @var TextEdit[]
     */
    public $changes;

    /**
     * @param TextEdit[] $changes
     */
    public function __construct(array $changes = [])
    {
        $this->changes = $changes;
    }
}
