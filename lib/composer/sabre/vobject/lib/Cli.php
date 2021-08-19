<?php

namespace Sabre\VObject;

use InvalidArgumentException;

/**
 * This is the CLI interface for sabre-vobject.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class Cli
{
    /**
     * No output.
     *
     * @var bool
     */
    protected $quiet = false;

    /**
     * Help display.
     *
     * @var bool
     */
    protected $showHelp = false;

    /**
     * Whether to spit out 'mimedir' or 'json' format.
     *
     * @var string
     */
    protected $format;

    /**
     * JSON pretty print.
     *
     * @var bool
     */
    protected $pretty;

    /**
     * Source file.
     *
     * @var string
     */
    protected $inputPath;

    /**
     * Destination file.
     *
     * @var string
     */
    protected $outputPath;

    /**
     * output stream.
     *
     * @var resource
     */
    protected $stdout;

    /**
     * stdin.
     *
     * @var resource
     */
    protected $stdin;

    /**
     * stderr.
     *
     * @var resource
     */
    protected $stderr;

    /**
     * Input format (one of json or mimedir).
     *
     * @var string
     */
    protected $inputFormat;

    /**
     * Makes the parser less strict.
     *
     * @var bool
     */
    protected $forgiving = false;

    /**
     * Main function.
     *
     * @return int
     */
    public function main(array $argv)
    {
        // @codeCoverageIgnoreStart
        // We cannot easily test this, so we'll skip it. Pretty basic anyway.

        if (!$this->stderr) {
            $this->stderr = fopen('php://stderr', 'w');
        }
        if (!$this->stdout) {
            $this->stdout = fopen('php://stdout', 'w');
        }
        if (!$this->stdin) {
            $this->stdin = fopen('php://stdin', 'r');
        }

        // @codeCoverageIgnoreEnd

        try {
            list($options, $positional) = $this->parseArguments($argv);

            if (isset($options['q'])) {
                $this->quiet = true;
            }
            $this->log($this->colorize('green', 'sabre/vobject ').$this->colorize('yellow', Version::VERSION));

            foreach ($options as $name => $value) {
                switch ($name) {
                    case 'q':
                        // Already handled earlier.
                        break;
                    case 'h':
                    case 'help':
                        $this->showHelp();

                        return 0;
                        break;
                    case 'format':
                        switch ($value) {
                            // jcard/jcal documents
                            case 'jcard':
                            case 'jcal':
                            // specific document versions
                            case 'vcard21':
                            case 'vcard30':
                            case 'vcard40':
                            case 'icalendar20':
                            // specific formats
                            case 'json':
                            case 'mimedir':
                            // icalendar/vcad
                            case 'icalendar':
                            case 'vcard':
                                $this->format = $value;
                                break;

                            default:
                                throw new InvalidArgumentException('Unknown format: '.$value);
                        }
                        break;
                    case 'pretty':
                        if (version_compare(PHP_VERSION, '5.4.0') >= 0) {
                            $this->pretty = true;
                        }
                        break;
                    case 'forgiving':
                        $this->forgiving = true;
                        break;
                    case 'inputformat':
                        switch ($value) {
                            // json formats
                            case 'jcard':
                            case 'jcal':
                            case 'json':
                                $this->inputFormat = 'json';
                                break;

                            // mimedir formats
                            case 'mimedir':
                            case 'icalendar':
                            case 'vcard':
                            case 'vcard21':
                            case 'vcard30':
                            case 'vcard40':
                            case 'icalendar20':
                                $this->inputFormat = 'mimedir';
                                break;

                            default:
                                throw new InvalidArgumentException('Unknown format: '.$value);
                        }
                        break;
                    default:
                        throw new InvalidArgumentException('Unknown option: '.$name);
                }
            }

            if (0 === count($positional)) {
                $this->showHelp();

                return 1;
            }

            if (1 === count($positional)) {
                throw new InvalidArgumentException('Inputfile is a required argument');
            }

            if (count($positional) > 3) {
                throw new InvalidArgumentException('Too many arguments');
            }

            if (!in_array($positional[0], ['validate', 'repair', 'convert', 'color'])) {
                throw new InvalidArgumentException('Unknown command: '.$positional[0]);
            }
        } catch (InvalidArgumentException $e) {
            $this->showHelp();
            $this->log('Error: '.$e->getMessage(), 'red');

            return 1;
        }

        $command = $positional[0];

        $this->inputPath = $positional[1];
        $this->outputPath = isset($positional[2]) ? $positional[2] : '-';

        if ('-' !== $this->outputPath) {
            $this->stdout = fopen($this->outputPath, 'w');
        }

        if (!$this->inputFormat) {
            if ('.json' === substr($this->inputPath, -5)) {
                $this->inputFormat = 'json';
            } else {
                $this->inputFormat = 'mimedir';
            }
        }
        if (!$this->format) {
            if ('.json' === substr($this->outputPath, -5)) {
                $this->format = 'json';
            } else {
                $this->format = 'mimedir';
            }
        }

        $realCode = 0;

        try {
            while ($input = $this->readInput()) {
                $returnCode = $this->$command($input);
                if (0 !== $returnCode) {
                    $realCode = $returnCode;
                }
            }
        } catch (EofException $e) {
            // end of file
        } catch (\Exception $e) {
            $this->log('Error: '.$e->getMessage(), 'red');

            return 2;
        }

        return $realCode;
    }

    /**
     * Shows the help message.
     */
    protected function showHelp()
    {
        $this->log('Usage:', 'yellow');
        $this->log('  vobject [options] command [arguments]');
        $this->log('');
        $this->log('Options:', 'yellow');
        $this->log($this->colorize('green', '  -q            ')."Don't output anything.");
        $this->log($this->colorize('green', '  -help -h      ').'Display this help message.');
        $this->log($this->colorize('green', '  --format      ').'Convert to a specific format. Must be one of: vcard, vcard21,');
        $this->log($this->colorize('green', '  --forgiving   ').'Makes the parser less strict.');
        $this->log('                vcard30, vcard40, icalendar20, jcal, jcard, json, mimedir.');
        $this->log($this->colorize('green', '  --inputformat ').'If the input format cannot be guessed from the extension, it');
        $this->log('                must be specified here.');
        // Only PHP 5.4 and up
        if (version_compare(PHP_VERSION, '5.4.0') >= 0) {
            $this->log($this->colorize('green', '  --pretty      ').'json pretty-print.');
        }
        $this->log('');
        $this->log('Commands:', 'yellow');
        $this->log($this->colorize('green', '  validate').' source_file              Validates a file for correctness.');
        $this->log($this->colorize('green', '  repair').' source_file [output_file]  Repairs a file.');
        $this->log($this->colorize('green', '  convert').' source_file [output_file] Converts a file.');
        $this->log($this->colorize('green', '  color').' source_file                 Colorize a file, useful for debugging.');
        $this->log(
        <<<HELP

If source_file is set as '-', STDIN will be used.
If output_file is omitted, STDOUT will be used.
All other output is sent to STDERR.

HELP
        );

        $this->log('Examples:', 'yellow');
        $this->log('   vobject convert contact.vcf contact.json');
        $this->log('   vobject convert --format=vcard40 old.vcf new.vcf');
        $this->log('   vobject convert --inputformat=json --format=mimedir - -');
        $this->log('   vobject color calendar.ics');
        $this->log('');
        $this->log('https://github.com/fruux/sabre-vobject', 'purple');
    }

    /**
     * Validates a VObject file.
     *
     * @return int
     */
    protected function validate(Component $vObj)
    {
        $returnCode = 0;

        switch ($vObj->name) {
            case 'VCALENDAR':
                $this->log('iCalendar: '.(string) $vObj->VERSION);
                break;
            case 'VCARD':
                $this->log('vCard: '.(string) $vObj->VERSION);
                break;
        }

        $warnings = $vObj->validate();
        if (!count($warnings)) {
            $this->log('  No warnings!');
        } else {
            $levels = [
                1 => 'REPAIRED',
                2 => 'WARNING',
                3 => 'ERROR',
            ];
            $returnCode = 2;
            foreach ($warnings as $warn) {
                $extra = '';
                if ($warn['node'] instanceof Property) {
                    $extra = ' (property: "'.$warn['node']->name.'")';
                }
                $this->log('  ['.$levels[$warn['level']].'] '.$warn['message'].$extra);
            }
        }

        return $returnCode;
    }

    /**
     * Repairs a VObject file.
     *
     * @return int
     */
    protected function repair(Component $vObj)
    {
        $returnCode = 0;

        switch ($vObj->name) {
            case 'VCALENDAR':
                $this->log('iCalendar: '.(string) $vObj->VERSION);
                break;
            case 'VCARD':
                $this->log('vCard: '.(string) $vObj->VERSION);
                break;
        }

        $warnings = $vObj->validate(Node::REPAIR);
        if (!count($warnings)) {
            $this->log('  No warnings!');
        } else {
            $levels = [
                1 => 'REPAIRED',
                2 => 'WARNING',
                3 => 'ERROR',
            ];
            $returnCode = 2;
            foreach ($warnings as $warn) {
                $extra = '';
                if ($warn['node'] instanceof Property) {
                    $extra = ' (property: "'.$warn['node']->name.'")';
                }
                $this->log('  ['.$levels[$warn['level']].'] '.$warn['message'].$extra);
            }
        }
        fwrite($this->stdout, $vObj->serialize());

        return $returnCode;
    }

    /**
     * Converts a vObject file to a new format.
     *
     * @param Component $vObj
     *
     * @return int
     */
    protected function convert($vObj)
    {
        $json = false;
        $convertVersion = null;
        $forceInput = null;

        switch ($this->format) {
            case 'json':
                $json = true;
                if ('VCARD' === $vObj->name) {
                    $convertVersion = Document::VCARD40;
                }
                break;
            case 'jcard':
                $json = true;
                $forceInput = 'VCARD';
                $convertVersion = Document::VCARD40;
                break;
            case 'jcal':
                $json = true;
                $forceInput = 'VCALENDAR';
                break;
            case 'mimedir':
            case 'icalendar':
            case 'icalendar20':
            case 'vcard':
                break;
            case 'vcard21':
                $convertVersion = Document::VCARD21;
                break;
            case 'vcard30':
                $convertVersion = Document::VCARD30;
                break;
            case 'vcard40':
                $convertVersion = Document::VCARD40;
                break;
        }

        if ($forceInput && $vObj->name !== $forceInput) {
            throw new \Exception('You cannot convert a '.strtolower($vObj->name).' to '.$this->format);
        }
        if ($convertVersion) {
            $vObj = $vObj->convert($convertVersion);
        }
        if ($json) {
            $jsonOptions = 0;
            if ($this->pretty) {
                $jsonOptions = JSON_PRETTY_PRINT;
            }
            fwrite($this->stdout, json_encode($vObj->jsonSerialize(), $jsonOptions));
        } else {
            fwrite($this->stdout, $vObj->serialize());
        }

        return 0;
    }

    /**
     * Colorizes a file.
     *
     * @param Component $vObj
     *
     * @return int
     */
    protected function color($vObj)
    {
        fwrite($this->stdout, $this->serializeComponent($vObj));
    }

    /**
     * Returns an ansi color string for a color name.
     *
     * @param string $color
     *
     * @return string
     */
    protected function colorize($color, $str, $resetTo = 'default')
    {
        $colors = [
            'cyan' => '1;36',
            'red' => '1;31',
            'yellow' => '1;33',
            'blue' => '0;34',
            'green' => '0;32',
            'default' => '0',
            'purple' => '0;35',
        ];

        return "\033[".$colors[$color].'m'.$str."\033[".$colors[$resetTo].'m';
    }

    /**
     * Writes out a string in specific color.
     *
     * @param string $color
     * @param string $str
     */
    protected function cWrite($color, $str)
    {
        fwrite($this->stdout, $this->colorize($color, $str));
    }

    protected function serializeComponent(Component $vObj)
    {
        $this->cWrite('cyan', 'BEGIN');
        $this->cWrite('red', ':');
        $this->cWrite('yellow', $vObj->name."\n");

        /**
         * Gives a component a 'score' for sorting purposes.
         *
         * This is solely used by the childrenSort method.
         *
         * A higher score means the item will be lower in the list.
         * To avoid score collisions, each "score category" has a reasonable
         * space to accommodate elements. The $key is added to the $score to
         * preserve the original relative order of elements.
         *
         * @param int   $key
         * @param array $array
         *
         * @return int
         */
        $sortScore = function ($key, $array) {
            if ($array[$key] instanceof Component) {
                // We want to encode VTIMEZONE first, this is a personal
                // preference.
                if ('VTIMEZONE' === $array[$key]->name) {
                    $score = 300000000;

                    return $score + $key;
                } else {
                    $score = 400000000;

                    return $score + $key;
                }
            } else {
                // Properties get encoded first
                // VCARD version 4.0 wants the VERSION property to appear first
                if ($array[$key] instanceof Property) {
                    if ('VERSION' === $array[$key]->name) {
                        $score = 100000000;

                        return $score + $key;
                    } else {
                        // All other properties
                        $score = 200000000;

                        return $score + $key;
                    }
                }
            }
        };

        $children = $vObj->children();
        $tmp = $children;
        uksort(
            $children,
            function ($a, $b) use ($sortScore, $tmp) {
                $sA = $sortScore($a, $tmp);
                $sB = $sortScore($b, $tmp);

                return $sA - $sB;
            }
        );

        foreach ($children as $child) {
            if ($child instanceof Component) {
                $this->serializeComponent($child);
            } else {
                $this->serializeProperty($child);
            }
        }

        $this->cWrite('cyan', 'END');
        $this->cWrite('red', ':');
        $this->cWrite('yellow', $vObj->name."\n");
    }

    /**
     * Colorizes a property.
     */
    protected function serializeProperty(Property $property)
    {
        if ($property->group) {
            $this->cWrite('default', $property->group);
            $this->cWrite('red', '.');
        }

        $this->cWrite('yellow', $property->name);

        foreach ($property->parameters as $param) {
            $this->cWrite('red', ';');
            $this->cWrite('blue', $param->serialize());
        }
        $this->cWrite('red', ':');

        if ($property instanceof Property\Binary) {
            $this->cWrite('default', 'embedded binary stripped. ('.strlen($property->getValue()).' bytes)');
        } else {
            $parts = $property->getParts();
            $first1 = true;
            // Looping through property values
            foreach ($parts as $part) {
                if ($first1) {
                    $first1 = false;
                } else {
                    $this->cWrite('red', $property->delimiter);
                }
                $first2 = true;
                // Looping through property sub-values
                foreach ((array) $part as $subPart) {
                    if ($first2) {
                        $first2 = false;
                    } else {
                        // The sub-value delimiter is always comma
                        $this->cWrite('red', ',');
                    }

                    $subPart = strtr(
                        $subPart,
                        [
                            '\\' => $this->colorize('purple', '\\\\', 'green'),
                            ';' => $this->colorize('purple', '\;', 'green'),
                            ',' => $this->colorize('purple', '\,', 'green'),
                            "\n" => $this->colorize('purple', "\\n\n\t", 'green'),
                            "\r" => '',
                        ]
                    );

                    $this->cWrite('green', $subPart);
                }
            }
        }
        $this->cWrite('default', "\n");
    }

    /**
     * Parses the list of arguments.
     */
    protected function parseArguments(array $argv)
    {
        $positional = [];
        $options = [];

        for ($ii = 0; $ii < count($argv); ++$ii) {
            // Skipping the first argument.
            if (0 === $ii) {
                continue;
            }

            $v = $argv[$ii];

            if ('--' === substr($v, 0, 2)) {
                // This is a long-form option.
                $optionName = substr($v, 2);
                $optionValue = true;
                if (strpos($optionName, '=')) {
                    list($optionName, $optionValue) = explode('=', $optionName);
                }
                $options[$optionName] = $optionValue;
            } elseif ('-' === substr($v, 0, 1) && strlen($v) > 1) {
                // This is a short-form option.
                foreach (str_split(substr($v, 1)) as $option) {
                    $options[$option] = true;
                }
            } else {
                $positional[] = $v;
            }
        }

        return [$options, $positional];
    }

    protected $parser;

    /**
     * Reads the input file.
     *
     * @return Component
     */
    protected function readInput()
    {
        if (!$this->parser) {
            if ('-' !== $this->inputPath) {
                $this->stdin = fopen($this->inputPath, 'r');
            }

            if ('mimedir' === $this->inputFormat) {
                $this->parser = new Parser\MimeDir($this->stdin, ($this->forgiving ? Reader::OPTION_FORGIVING : 0));
            } else {
                $this->parser = new Parser\Json($this->stdin, ($this->forgiving ? Reader::OPTION_FORGIVING : 0));
            }
        }

        return $this->parser->parse();
    }

    /**
     * Sends a message to STDERR.
     *
     * @param string $msg
     */
    protected function log($msg, $color = 'default')
    {
        if (!$this->quiet) {
            if ('default' !== $color) {
                $msg = $this->colorize($color, $msg);
            }
            fwrite($this->stderr, $msg."\n");
        }
    }
}
