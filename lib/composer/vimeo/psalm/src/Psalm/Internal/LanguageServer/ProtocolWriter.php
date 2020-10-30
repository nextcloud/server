<?php
declare(strict_types = 1);
namespace Psalm\Internal\LanguageServer;

use Amp\Promise;

interface ProtocolWriter
{
    /**
     * Sends a Message to the client
     *
     *
     * @return Promise Resolved when the message has been fully written out to the output stream
     */
    public function write(Message $msg): Promise;
}
