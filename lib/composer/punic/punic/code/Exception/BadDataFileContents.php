<?php

namespace Punic\Exception;

/**
 * An exception raised when an data file contains malformed data.
 */
class BadDataFileContents extends \Punic\Exception
{
    protected $dataFilePath;

    protected $dataFileContents;

    /**
     * Initializes the instance.
     *
     * @param string $dataFilePath The path to the file with bad contents
     * @param string $dataFileContents The malformed of the file
     * @param \Exception $previous The previous exception used for the exception chaining
     */
    public function __construct($dataFilePath, $dataFileContents, $previous = null)
    {
        $this->dataFilePath = $dataFilePath;
        $this->dataFileContents = $dataFileContents;
        $message = "The file '$dataFilePath' contains malformed data";
        parent::__construct($message, \Punic\Exception::BAD_DATA_FILE_CONTENTS, $previous);
    }

    /**
     * Retrieves the path to the data file.
     *
     * @return string
     */
    public function getDataFilePath()
    {
        return $this->dataFilePath;
    }

    /**
     * Retrieves the malformed contents of the file.
     *
     * @return string
     */
    public function getDataFileContents()
    {
        return $this->dataFileContents;
    }
}
