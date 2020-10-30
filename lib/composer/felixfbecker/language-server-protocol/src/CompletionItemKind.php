<?php

namespace LanguageServerProtocol;

/**
 * The kind of a completion entry.
 */
abstract class CompletionItemKind
{
    const TEXT = 1;
    const METHOD = 2;
    const FUNCTION = 3;
    const CONSTRUCTOR = 4;
    const FIELD = 5;
    const VARIABLE = 6;
    const CLASS_ = 7;
    const INTERFACE = 8;
    const MODULE = 9;
    const PROPERTY = 10;
    const UNIT = 11;
    const VALUE = 12;
    const ENUM = 13;
    const KEYWORD = 14;
    const SNIPPET = 15;
    const COLOR = 16;
    const FILE = 17;
    const REFERENCE = 18;

    /**
     * Returns the CompletionItemKind for a SymbolKind
     *
     * @param int $kind A SymbolKind
     * @return int The CompletionItemKind
     */
    public static function fromSymbolKind(int $kind): int
    {
        switch ($kind) {
            case SymbolKind::PROPERTY:
            case SymbolKind::FIELD:
                return self::PROPERTY;
            case SymbolKind::METHOD:
                return self::METHOD;
            case SymbolKind::CLASS_:
                return self::CLASS_;
            case SymbolKind::INTERFACE:
                return self::INTERFACE;
            case SymbolKind::FUNCTION:
                return self::FUNCTION;
            case SymbolKind::NAMESPACE:
            case SymbolKind::MODULE:
            case SymbolKind::PACKAGE:
                return self::MODULE;
            case SymbolKind::FILE:
                return self::FILE;
            case SymbolKind::STRING:
                return self::TEXT;
            case SymbolKind::NUMBER:
            case SymbolKind::BOOLEAN:
            case SymbolKind::ARRAY:
                return self::VALUE;
            case SymbolKind::ENUM:
                return self::ENUM;
            case SymbolKind::CONSTRUCTOR:
                return self::CONSTRUCTOR;
            case SymbolKind::VARIABLE:
            case SymbolKind::CONSTANT:
                return self::VARIABLE;
        }
    }
}
