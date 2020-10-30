<?php

namespace LanguageServerProtocol;

/**
 * A symbol kind.
 */
abstract class SymbolKind
{
    const FILE = 1;
    const MODULE = 2;
    const NAMESPACE = 3;
    const PACKAGE = 4;
    const CLASS_ = 5;
    const METHOD = 6;
    const PROPERTY = 7;
    const FIELD = 8;
    const CONSTRUCTOR = 9;
    const ENUM = 10;
    const INTERFACE = 11;
    const FUNCTION = 12;
    const VARIABLE = 13;
    const CONSTANT = 14;
    const STRING = 15;
    const NUMBER = 16;
    const BOOLEAN = 17;
    const ARRAY = 18;
}
