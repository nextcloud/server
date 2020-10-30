<?php
declare(strict_types = 1);
namespace Psalm\Internal\LanguageServer;

/**
 * Must emit a "message" event with a Message object as parameter
 * when a message comes in
 *
 * Must emit a "close" event when the stream closes
 */
interface ProtocolReader extends EmitterInterface
{
}
