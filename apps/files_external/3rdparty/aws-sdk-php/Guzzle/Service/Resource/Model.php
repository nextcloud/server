<?php

namespace Guzzle\Service\Resource;

use Guzzle\Common\Collection;
use Guzzle\Service\Description\Parameter;

/**
 * Default model created when commands create service description model responses
 */
class Model extends Collection
{
    /** @var Parameter Structure of the model */
    protected $structure;

    /**
     * @param array     $data      Data contained by the model
     * @param Parameter $structure The structure of the model
     */
    public function __construct(array $data = array(), Parameter $structure = null)
    {
        $this->data = $data;
        $this->structure = $structure ?: new Parameter();
    }

    /**
     * Get the structure of the model
     *
     * @return Parameter
     */
    public function getStructure()
    {
        return $this->structure;
    }

    /**
     * Provides debug information about the model object
     *
     * @return string
     */
    public function __toString()
    {
        $output = 'Debug output of ' . ($this->structure->getName() ?: ' the model');
        $output = str_repeat('=', strlen($output)) . "\n" . $output . "\n" . str_repeat('=', strlen($output)) . "\n\n";
        $output .= "Model data\n-----------\n\n";
        $output .= "This data can be retrieved from the model object using the get() method of the model "
            . "(e.g. \$model->get(\$key)) or accessing the model like an associative array (e.g. \$model['key']).\n\n";
        $lines = array_slice(explode("\n", trim(print_r($this->toArray(), true))), 2, -1);
        $output .=  implode("\n", $lines) . "\n\n";
        $output .= "Model structure\n---------------\n\n";
        $output .= "The following JSON document defines how the model was parsed from an HTTP response into the "
            . "associative array strucure you see above.\n\n";
        $output .= '  ' . json_encode($this->structure->toArray()) . "\n\n";

        return $output;
    }
}
