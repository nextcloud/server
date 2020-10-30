<?php

namespace LanguageServerProtocol;

class CompletionTriggerKind
{
    /**
     * Completion was triggered by invoking it manuall or using API.
     */
    const INVOKED = 1;

    /**
     * Completion was triggered by a trigger character.
     */
    const TRIGGER_CHARACTER = 2;
}
