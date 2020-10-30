<?php

namespace Psalm\Type;

/**
 * An Enum class holding all the taint types that Psalm recognises
 */
class TaintKindGroup
{
    public const ALL_INPUT = [
        TaintKind::INPUT_HTML,
        TaintKind::INPUT_SHELL,
        TaintKind::INPUT_SQL,
        TaintKind::INPUT_TEXT,
    ];
}
