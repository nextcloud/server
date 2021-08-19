<?php

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
    /**
     * @var string
     */
    protected $pattern;

    /**
     * @var string
     */
    protected $subject;

    /**
     * @var array
     */
    protected $groups = array();

    private $searchIndex = 0;

    /**
     * @param string $pattern
     * @param string $subject
     */
    public function __construct($pattern, $subject)
    {
        $this->pattern = str_replace('/', '\/', $pattern);
        $this->subject = $subject;
    }

    protected function doMatch($type = 'find', $offset = 0)
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

            $positions = array();

            foreach ($groups as $group) {
                $positions[] = array(
                    $group[0],
                    $offset + mb_strlen(substr($search, 0, $group[1]))
                );
            }

            $this->groups = $positions;
        }

        return ($result === 1);
    }

    /**
     * @return bool
     */
    public function matches()
    {
        return $this->doMatch('matches');
    }

    /**
     * @return bool
     */
    public function lookingAt()
    {
        return $this->doMatch('lookingAt');
    }

    /**
     * @return bool
     */
    public function find($offset = null)
    {
        if ($offset === null) {
            $offset = $this->searchIndex;
        }

        // Increment search index for the next time we call this
        $this->searchIndex++;
        return $this->doMatch('find', $offset);
    }

    /**
     * @return int
     */
    public function groupCount()
    {
        if (empty($this->groups)) {
            return null;
        }

        return count($this->groups) - 1;
    }

    /**
     * @param int $group
     * @return string
     */
    public function group($group = null)
    {
        if ($group === null) {
            $group = 0;
        }
        return isset($this->groups[$group][0]) ? $this->groups[$group][0] : null;
    }

    /**
     * @param int|null $group
     * @return int
     */
    public function end($group = null)
    {
        if ($group === null) {
            $group = 0;
        }
        if (!isset($this->groups[$group])) {
            return null;
        }
        return $this->groups[$group][1] + mb_strlen($this->groups[$group][0]);
    }

    public function start($group = null)
    {
        if ($group === null) {
            $group = 0;
        }
        if (!isset($this->groups[$group])) {
            return null;
        }

        return $this->groups[$group][1];
    }

    /**
     * @param string $replacement
     * @return string
     */
    public function replaceFirst($replacement)
    {
        return preg_replace('/' . $this->pattern . '/x', $replacement, $this->subject, 1);
    }

    /**
     * @param string $replacement
     * @return string
     */
    public function replaceAll($replacement)
    {
        return preg_replace('/' . $this->pattern . '/x', $replacement, $this->subject);
    }

    /**
     * @param string $input
     * @return Matcher
     */
    public function reset($input = '')
    {
        $this->subject = $input;

        return $this;
    }
}
