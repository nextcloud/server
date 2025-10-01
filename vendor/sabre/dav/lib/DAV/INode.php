<?php

declare(strict_types=1);

namespace Sabre\DAV;

/**
 * The INode interface is the base interface, and the parent class of both ICollection and IFile.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
interface INode
{
    /**
     * Deleted the current node.
     */
    public function delete();

    /**
     * Returns the name of the node.
     *
     * This is used to generate the url.
     *
     * @return string
     */
    public function getName();

    /**
     * Renames the node.
     *
     * @param string $name The new name
     */
    public function setName($name);

    /**
     * Returns the last modification time, as a unix timestamp. Return null
     * if the information is not available.
     *
     * @return int|null
     */
    public function getLastModified();
}
