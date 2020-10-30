<?php

namespace LanguageServerProtocol;

/**
 * Represents a parameter of a callable-signature. A parameter can
 * have a label and a doc-comment.
 */
class ParameterInformation
{
    /**
     * The label of this parameter information.
     *
     * Either a string or an inclusive start and exclusive end offsets within its containing
     * signature label. (see SignatureInformation.label). The offsets are based on a UTF-16
     * string representation as `Position` and `Range` does.
     *
     * *Note*: a label of type string should be a substring of its containing signature label.
     * Its intended use case is to highlight the parameter label part in the `SignatureInformation.label`.
     *
     * @var string|int[]
     */
    public $label;

    /**
     * The human-readable doc-comment of this signature. Will be shown
     * in the UI but can be omitted.
     *
     * @var string|null
     */
    public $documentation;

    /**
     * Create ParameterInformation
     *
     * @param string|int[] $label   The label of this parameter information.
     * @param string $documentation The human-readable doc-comment of this signature. Will be shown in the UI but can
     *                              be omitted.
     */
    public function __construct($label, string $documentation = null)
    {
        $this->label = $label;
        $this->documentation = $documentation;
    }
}
