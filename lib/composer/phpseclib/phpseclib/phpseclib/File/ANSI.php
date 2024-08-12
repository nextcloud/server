<?php

/**
 * Pure-PHP ANSI Decoder
 *
 * PHP version 5
 *
 * If you call read() in \phpseclib3\Net\SSH2 you may get {@link http://en.wikipedia.org/wiki/ANSI_escape_code ANSI escape codes} back.
 * They'd look like chr(0x1B) . '[00m' or whatever (0x1B = ESC).  They tell a
 * {@link http://en.wikipedia.org/wiki/Terminal_emulator terminal emulator} how to format the characters, what
 * color to display them in, etc. \phpseclib3\File\ANSI is a {@link http://en.wikipedia.org/wiki/VT100 VT100} terminal emulator.
 *
 * @author    Jim Wigginton <terrafrost@php.net>
 * @copyright 2012 Jim Wigginton
 * @license   http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link      http://phpseclib.sourceforge.net
 */

namespace phpseclib3\File;

/**
 * Pure-PHP ANSI Decoder
 *
 * @author  Jim Wigginton <terrafrost@php.net>
 */
class ANSI
{
    /**
     * Max Width
     *
     * @var int
     */
    private $max_x;

    /**
     * Max Height
     *
     * @var int
     */
    private $max_y;

    /**
     * Max History
     *
     * @var int
     */
    private $max_history;

    /**
     * History
     *
     * @var array
     */
    private $history;

    /**
     * History Attributes
     *
     * @var array
     */
    private $history_attrs;

    /**
     * Current Column
     *
     * @var int
     */
    private $x;

    /**
     * Current Row
     *
     * @var int
     */
    private $y;

    /**
     * Old Column
     *
     * @var int
     */
    private $old_x;

    /**
     * Old Row
     *
     * @var int
     */
    private $old_y;

    /**
     * An empty attribute cell
     *
     * @var object
     */
    private $base_attr_cell;

    /**
     * The current attribute cell
     *
     * @var object
     */
    private $attr_cell;

    /**
     * An empty attribute row
     *
     * @var array
     */
    private $attr_row;

    /**
     * The current screen text
     *
     * @var list<string>
     */
    private $screen;

    /**
     * The current screen attributes
     *
     * @var array
     */
    private $attrs;

    /**
     * Current ANSI code
     *
     * @var string
     */
    private $ansi;

    /**
     * Tokenization
     *
     * @var array
     */
    private $tokenization;

    /**
     * Default Constructor.
     *
     * @return \phpseclib3\File\ANSI
     */
    public function __construct()
    {
        $attr_cell = new \stdClass();
        $attr_cell->bold = false;
        $attr_cell->underline = false;
        $attr_cell->blink = false;
        $attr_cell->background = 'black';
        $attr_cell->foreground = 'white';
        $attr_cell->reverse = false;
        $this->base_attr_cell = clone $attr_cell;
        $this->attr_cell = clone $attr_cell;

        $this->setHistory(200);
        $this->setDimensions(80, 24);
    }

    /**
     * Set terminal width and height
     *
     * Resets the screen as well
     *
     * @param int $x
     * @param int $y
     */
    public function setDimensions($x, $y)
    {
        $this->max_x = $x - 1;
        $this->max_y = $y - 1;
        $this->x = $this->y = 0;
        $this->history = $this->history_attrs = [];
        $this->attr_row = array_fill(0, $this->max_x + 2, $this->base_attr_cell);
        $this->screen = array_fill(0, $this->max_y + 1, '');
        $this->attrs = array_fill(0, $this->max_y + 1, $this->attr_row);
        $this->ansi = '';
    }

    /**
     * Set the number of lines that should be logged past the terminal height
     *
     * @param int $history
     */
    public function setHistory($history)
    {
        $this->max_history = $history;
    }

    /**
     * Load a string
     *
     * @param string $source
     */
    public function loadString($source)
    {
        $this->setDimensions($this->max_x + 1, $this->max_y + 1);
        $this->appendString($source);
    }

    /**
     * Appdend a string
     *
     * @param string $source
     */
    public function appendString($source)
    {
        $this->tokenization = [''];
        for ($i = 0; $i < strlen($source); $i++) {
            if (strlen($this->ansi)) {
                $this->ansi .= $source[$i];
                $chr = ord($source[$i]);
                // http://en.wikipedia.org/wiki/ANSI_escape_code#Sequence_elements
                // single character CSI's not currently supported
                switch (true) {
                    case $this->ansi == "\x1B=":
                        $this->ansi = '';
                        continue 2;
                    case strlen($this->ansi) == 2 && $chr >= 64 && $chr <= 95 && $chr != ord('['):
                    case strlen($this->ansi) > 2 && $chr >= 64 && $chr <= 126:
                        break;
                    default:
                        continue 2;
                }
                $this->tokenization[] = $this->ansi;
                $this->tokenization[] = '';
                // http://ascii-table.com/ansi-escape-sequences-vt-100.php
                switch ($this->ansi) {
                    case "\x1B[H": // Move cursor to upper left corner
                        $this->old_x = $this->x;
                        $this->old_y = $this->y;
                        $this->x = $this->y = 0;
                        break;
                    case "\x1B[J": // Clear screen from cursor down
                        $this->history = array_merge($this->history, array_slice(array_splice($this->screen, $this->y + 1), 0, $this->old_y));
                        $this->screen = array_merge($this->screen, array_fill($this->y, $this->max_y, ''));

                        $this->history_attrs = array_merge($this->history_attrs, array_slice(array_splice($this->attrs, $this->y + 1), 0, $this->old_y));
                        $this->attrs = array_merge($this->attrs, array_fill($this->y, $this->max_y, $this->attr_row));

                        if (count($this->history) == $this->max_history) {
                            array_shift($this->history);
                            array_shift($this->history_attrs);
                        }
                        // fall-through
                    case "\x1B[K": // Clear screen from cursor right
                        $this->screen[$this->y] = substr($this->screen[$this->y], 0, $this->x);

                        array_splice($this->attrs[$this->y], $this->x + 1, $this->max_x - $this->x, array_fill($this->x, $this->max_x - ($this->x - 1), $this->base_attr_cell));
                        break;
                    case "\x1B[2K": // Clear entire line
                        $this->screen[$this->y] = str_repeat(' ', $this->x);
                        $this->attrs[$this->y] = $this->attr_row;
                        break;
                    case "\x1B[?1h": // set cursor key to application
                    case "\x1B[?25h": // show the cursor
                    case "\x1B(B": // set united states g0 character set
                        break;
                    case "\x1BE": // Move to next line
                        $this->newLine();
                        $this->x = 0;
                        break;
                    default:
                        switch (true) {
                            case preg_match('#\x1B\[(\d+)B#', $this->ansi, $match): // Move cursor down n lines
                                $this->old_y = $this->y;
                                $this->y += (int) $match[1];
                                break;
                            case preg_match('#\x1B\[(\d+);(\d+)H#', $this->ansi, $match): // Move cursor to screen location v,h
                                $this->old_x = $this->x;
                                $this->old_y = $this->y;
                                $this->x = $match[2] - 1;
                                $this->y = (int) $match[1] - 1;
                                break;
                            case preg_match('#\x1B\[(\d+)C#', $this->ansi, $match): // Move cursor right n lines
                                $this->old_x = $this->x;
                                $this->x += $match[1];
                                break;
                            case preg_match('#\x1B\[(\d+)D#', $this->ansi, $match): // Move cursor left n lines
                                $this->old_x = $this->x;
                                $this->x -= $match[1];
                                if ($this->x < 0) {
                                    $this->x = 0;
                                }
                                break;
                            case preg_match('#\x1B\[(\d+);(\d+)r#', $this->ansi, $match): // Set top and bottom lines of a window
                                break;
                            case preg_match('#\x1B\[(\d*(?:;\d*)*)m#', $this->ansi, $match): // character attributes
                                $attr_cell = &$this->attr_cell;
                                $mods = explode(';', $match[1]);
                                foreach ($mods as $mod) {
                                    switch ($mod) {
                                        case '':
                                        case '0': // Turn off character attributes
                                            $attr_cell = clone $this->base_attr_cell;
                                            break;
                                        case '1': // Turn bold mode on
                                            $attr_cell->bold = true;
                                            break;
                                        case '4': // Turn underline mode on
                                            $attr_cell->underline = true;
                                            break;
                                        case '5': // Turn blinking mode on
                                            $attr_cell->blink = true;
                                            break;
                                        case '7': // Turn reverse video on
                                            $attr_cell->reverse = !$attr_cell->reverse;
                                            $temp = $attr_cell->background;
                                            $attr_cell->background = $attr_cell->foreground;
                                            $attr_cell->foreground = $temp;
                                            break;
                                        default: // set colors
                                            //$front = $attr_cell->reverse ? &$attr_cell->background : &$attr_cell->foreground;
                                            $front = &$attr_cell->{ $attr_cell->reverse ? 'background' : 'foreground' };
                                            //$back = $attr_cell->reverse ? &$attr_cell->foreground : &$attr_cell->background;
                                            $back = &$attr_cell->{ $attr_cell->reverse ? 'foreground' : 'background' };
                                            switch ($mod) {
                                                // @codingStandardsIgnoreStart
                                                case '30': $front = 'black'; break;
                                                case '31': $front = 'red'; break;
                                                case '32': $front = 'green'; break;
                                                case '33': $front = 'yellow'; break;
                                                case '34': $front = 'blue'; break;
                                                case '35': $front = 'magenta'; break;
                                                case '36': $front = 'cyan'; break;
                                                case '37': $front = 'white'; break;

                                                case '40': $back = 'black'; break;
                                                case '41': $back = 'red'; break;
                                                case '42': $back = 'green'; break;
                                                case '43': $back = 'yellow'; break;
                                                case '44': $back = 'blue'; break;
                                                case '45': $back = 'magenta'; break;
                                                case '46': $back = 'cyan'; break;
                                                case '47': $back = 'white'; break;
                                                // @codingStandardsIgnoreEnd

                                                default:
                                                    //user_error('Unsupported attribute: ' . $mod);
                                                    $this->ansi = '';
                                                    break 2;
                                            }
                                    }
                                }
                                break;
                            default:
                                //user_error("{$this->ansi} is unsupported\r\n");
                        }
                }
                $this->ansi = '';
                continue;
            }

            $this->tokenization[count($this->tokenization) - 1] .= $source[$i];
            switch ($source[$i]) {
                case "\r":
                    $this->x = 0;
                    break;
                case "\n":
                    $this->newLine();
                    break;
                case "\x08": // backspace
                    if ($this->x) {
                        $this->x--;
                        $this->attrs[$this->y][$this->x] = clone $this->base_attr_cell;
                        $this->screen[$this->y] = substr_replace(
                            $this->screen[$this->y],
                            $source[$i],
                            $this->x,
                            1
                        );
                    }
                    break;
                case "\x0F": // shift
                    break;
                case "\x1B": // start ANSI escape code
                    $this->tokenization[count($this->tokenization) - 1] = substr($this->tokenization[count($this->tokenization) - 1], 0, -1);
                    //if (!strlen($this->tokenization[count($this->tokenization) - 1])) {
                    //    array_pop($this->tokenization);
                    //}
                    $this->ansi .= "\x1B";
                    break;
                default:
                    $this->attrs[$this->y][$this->x] = clone $this->attr_cell;
                    if ($this->x > strlen($this->screen[$this->y])) {
                        $this->screen[$this->y] = str_repeat(' ', $this->x);
                    }
                    $this->screen[$this->y] = substr_replace(
                        $this->screen[$this->y],
                        $source[$i],
                        $this->x,
                        1
                    );

                    if ($this->x > $this->max_x) {
                        $this->x = 0;
                        $this->newLine();
                    } else {
                        $this->x++;
                    }
            }
        }
    }

    /**
     * Add a new line
     *
     * Also update the $this->screen and $this->history buffers
     *
     */
    private function newLine()
    {
        //if ($this->y < $this->max_y) {
        //    $this->y++;
        //}

        while ($this->y >= $this->max_y) {
            $this->history = array_merge($this->history, [array_shift($this->screen)]);
            $this->screen[] = '';

            $this->history_attrs = array_merge($this->history_attrs, [array_shift($this->attrs)]);
            $this->attrs[] = $this->attr_row;

            if (count($this->history) >= $this->max_history) {
                array_shift($this->history);
                array_shift($this->history_attrs);
            }

            $this->y--;
        }
        $this->y++;
    }

    /**
     * Returns the current coordinate without preformating
     *
     * @param \stdClass $last_attr
     * @param \stdClass $cur_attr
     * @param string $char
     * @return string
     */
    private function processCoordinate(\stdClass $last_attr, \stdClass $cur_attr, $char)
    {
        $output = '';

        if ($last_attr != $cur_attr) {
            $close = $open = '';
            if ($last_attr->foreground != $cur_attr->foreground) {
                if ($cur_attr->foreground != 'white') {
                    $open .= '<span style="color: ' . $cur_attr->foreground . '">';
                }
                if ($last_attr->foreground != 'white') {
                    $close = '</span>' . $close;
                }
            }
            if ($last_attr->background != $cur_attr->background) {
                if ($cur_attr->background != 'black') {
                    $open .= '<span style="background: ' . $cur_attr->background . '">';
                }
                if ($last_attr->background != 'black') {
                    $close = '</span>' . $close;
                }
            }
            if ($last_attr->bold != $cur_attr->bold) {
                if ($cur_attr->bold) {
                    $open .= '<b>';
                } else {
                    $close = '</b>' . $close;
                }
            }
            if ($last_attr->underline != $cur_attr->underline) {
                if ($cur_attr->underline) {
                    $open .= '<u>';
                } else {
                    $close = '</u>' . $close;
                }
            }
            if ($last_attr->blink != $cur_attr->blink) {
                if ($cur_attr->blink) {
                    $open .= '<blink>';
                } else {
                    $close = '</blink>' . $close;
                }
            }
            $output .= $close . $open;
        }

        $output .= htmlspecialchars($char);

        return $output;
    }

    /**
     * Returns the current screen without preformating
     *
     * @return string
     */
    private function getScreenHelper()
    {
        $output = '';
        $last_attr = $this->base_attr_cell;
        for ($i = 0; $i <= $this->max_y; $i++) {
            for ($j = 0; $j <= $this->max_x; $j++) {
                $cur_attr = $this->attrs[$i][$j];
                $output .= $this->processCoordinate($last_attr, $cur_attr, isset($this->screen[$i][$j]) ? $this->screen[$i][$j] : '');
                $last_attr = $this->attrs[$i][$j];
            }
            $output .= "\r\n";
        }
        $output = substr($output, 0, -2);
        // close any remaining open tags
        $output .= $this->processCoordinate($last_attr, $this->base_attr_cell, '');
        return rtrim($output);
    }

    /**
     * Returns the current screen
     *
     * @return string
     */
    public function getScreen()
    {
        return '<pre width="' . ($this->max_x + 1) . '" style="color: white; background: black">' . $this->getScreenHelper() . '</pre>';
    }

    /**
     * Returns the current screen and the x previous lines
     *
     * @return string
     */
    public function getHistory()
    {
        $scrollback = '';
        $last_attr = $this->base_attr_cell;
        for ($i = 0; $i < count($this->history); $i++) {
            for ($j = 0; $j <= $this->max_x + 1; $j++) {
                $cur_attr = $this->history_attrs[$i][$j];
                $scrollback .= $this->processCoordinate($last_attr, $cur_attr, isset($this->history[$i][$j]) ? $this->history[$i][$j] : '');
                $last_attr = $this->history_attrs[$i][$j];
            }
            $scrollback .= "\r\n";
        }
        $base_attr_cell = $this->base_attr_cell;
        $this->base_attr_cell = $last_attr;
        $scrollback .= $this->getScreen();
        $this->base_attr_cell = $base_attr_cell;

        return '<pre width="' . ($this->max_x + 1) . '" style="color: white; background: black">' . $scrollback . '</span></pre>';
    }
}
