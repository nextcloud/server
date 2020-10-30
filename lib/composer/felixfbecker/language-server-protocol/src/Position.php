<?php

namespace LanguageServerProtocol;

/**
 * Position in a text document expressed as zero-based line and character offset.
 */
class Position
{
    /**
     * Line position in a document (zero-based).
     *
     * @var int
     */
    public $line;

    /**
     * Character offset on a line in a document (zero-based).
     *
     * @var int
     */
    public $character;

    public function __construct(int $line = null, int $character = null)
    {
        $this->line = $line;
        $this->character = $character;
    }

    /**
     * Compares this position to another position
     * Returns
     *  - 0 if the positions match
     *  - a negative number if $this is before $position
     *  - a positive number otherwise
     *
     * @param Position $position
     * @return int
     */
    public function compare(Position $position): int
    {
        if ($this->line === $position->line && $this->character === $position->character) {
            return 0;
        }

        if ($this->line !== $position->line) {
            return $this->line - $position->line;
        }

        return $this->character - $position->character;
    }

    /**
     * Returns the offset of the position in a string
     *
     * @param string $content
     * @return int
     */
    public function toOffset(string $content): int
    {
        $lines = explode("\n", $content);
        $slice = array_slice($lines, 0, $this->line);
        return (int) array_sum(array_map('strlen', $slice)) + count($slice) + $this->character;
    }
}
