<?php

namespace Masterminds\HTML5\Parser;

/**
 * The FileInputStream loads a file to be parsed.
 *
 * So right now we read files into strings and then process the
 * string. We chose to do this largely for the sake of expediency of
 * development, and also because we could optimize toward processing
 * arbitrarily large chunks of the input. But in the future, we'd
 * really like to rewrite this class to efficiently handle lower level
 * stream reads (and thus efficiently handle large documents).
 *
 * @deprecated since 2.4, to remove in 3.0. Use a string in the scanner instead.
 */
class FileInputStream extends StringInputStream implements InputStream
{
    /**
     * Load a file input stream.
     *
     * @param string $data     The file or url path to load.
     * @param string $encoding The encoding to use for the data.
     * @param string $debug    A fprintf format to use to echo the data on stdout.
     */
    public function __construct($data, $encoding = 'UTF-8', $debug = '')
    {
        // Get the contents of the file.
        $content = file_get_contents($data);

        parent::__construct($content, $encoding, $debug);
    }
}
