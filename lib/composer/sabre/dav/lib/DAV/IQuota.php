<?php

declare(strict_types=1);

namespace Sabre\DAV;

/**
 * IQuota interface.
 *
 * Implement this interface to add the ability to return quota information. The ObjectTree
 * will check for quota information on any given node. If the information is not available it will
 * attempt to fetch the information from the root node.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
interface IQuota extends ICollection
{
    /**
     * Returns the quota information.
     *
     * This method MUST return an array with 2 values, the first being the total used space,
     * the second the available space (in bytes)
     */
    public function getQuotaInfo();
}
