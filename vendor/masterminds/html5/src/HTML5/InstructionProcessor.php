<?php
/**
 * A handler for processor instructions.
 */

namespace Masterminds\HTML5;

/**
 * Provide an processor to handle embedded instructions.
 *
 * XML defines a mechanism for inserting instructions (like PHP) into a
 * document. These are called "Processor Instructions." The HTML5 parser
 * provides an opportunity to handle these processor instructions during
 * the tree-building phase (before the DOM is constructed), which makes
 * it possible to alter the document as it is being created.
 *
 * One could, for example, use this mechanism to execute well-formed PHP
 * code embedded inside of an HTML5 document.
 */
interface InstructionProcessor
{
    /**
     * Process an individual processing instruction.
     *
     * The process() function is responsible for doing the following:
     * - Determining whether $name is an instruction type it can handle.
     * - Determining what to do with the data passed in.
     * - Making any subsequent modifications to the DOM by modifying the
     * DOMElement or its attached DOM tree.
     *
     * @param \DOMElement $element The parent element for the current processing instruction.
     * @param string      $name    The instruction's name. E.g. `&lt;?php` has the name `php`.
     * @param string      $data    All of the data between the opening and closing PI marks.
     *
     * @return \DOMElement The element that should be considered "Current". This may just be
     *                     the element passed in, but if the processor added more elements,
     *                     it may choose to reset the current element to one of the elements
     *                     it created. (When in doubt, return the element passed in.)
     */
    public function process(\DOMElement $element, $name, $data);
}
