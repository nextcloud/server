<?php

declare(strict_types=1);

namespace libphonenumber;

/**
 * Matcher for various regex matching
 *
 * Note that this is NOT the same as google's java PhoneNumberMatcher class.
 * This class is a minimal port of java's built-in matcher class, whereas PhoneNumberMatcher
 * is designed to recognize phone numbers embedded in any text.
 *
 * @internal
 */
class Matcher
{
    protected string $pattern;

    protected string $subject = '';

    /**
     * @var array<int,mixed>
     */
    protected array $groups = [];

    private int $searchIndex = 0;

    public function __construct(string $pattern, string $subject)
    {
        $this->pattern = str_replace('/', '\/', $pattern);
        $this->subject = $subject;
    }

    protected function doMatch(string $type = 'find', int $offset = 0): bool
    {
        $final_pattern = '(?:' . $this->pattern . ')';
        switch ($type) {
            case 'matches':
                $final_pattern = '^' . $final_pattern . '$';
                break;
            case 'lookingAt':
                $final_pattern = '^' . $final_pattern;
                break;
            case 'find':
            default:
                // no changes
                break;
        }
        $final_pattern = '/' . $final_pattern . '/ui';

        $search = mb_substr($this->subject, $offset);

        $result = preg_match($final_pattern, $search, $groups, PREG_OFFSET_CAPTURE);

        if ($result === 1) {
            // Expand $groups into $this->groups, but being multi-byte aware

            $positions = [];

            foreach ($groups as $group) {
                $positions[] = [
                    $group[0],
                    $offset + mb_strlen(substr($search, 0, $group[1])),
                ];
            }

            $this->groups = $positions;
        }

        return ($result === 1);
    }

    public function matches(): bool
    {
        return $this->doMatch('matches');
    }

    public function lookingAt(): bool
    {
        return $this->doMatch('lookingAt');
    }

    public function find(?int $offset = null): bool
    {
        if ($offset === null) {
            $offset = $this->searchIndex;
        }

        // Increment search index for the next time we call this
        $this->searchIndex++;
        return $this->doMatch('find', $offset);
    }

    public function groupCount(): ?int
    {
        if ($this->groups === []) {
            return null;
        }

        return count($this->groups) - 1;
    }

    public function group(?int $group = null): ?string
    {
        if ($group === null) {
            $group = 0;
        }
        return $this->groups[$group][0] ?? null;
    }

    public function end(?int $group = null): ?int
    {
        if ($group === null) {
            $group = 0;
        }
        if (!isset($this->groups[$group])) {
            return null;
        }
        return $this->groups[$group][1] + mb_strlen($this->groups[$group][0]);
    }

    public function start(?int $group = null): mixed
    {
        if ($group === null) {
            $group = 0;
        }
        if (!isset($this->groups[$group])) {
            return null;
        }

        return $this->groups[$group][1];
    }

    public function replaceFirst(string $replacement): string
    {
        return preg_replace('/' . $this->pattern . '/x', $replacement, $this->subject, 1);
    }

    public function replaceAll(string $replacement): string
    {
        return preg_replace('/' . $this->pattern . '/x', $replacement, $this->subject);
    }

    public function reset(string $input = ''): static
    {
        $this->subject = $input;

        return $this;
    }
}
