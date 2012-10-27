<?php

namespace Sabre\VObject\Component;

use Sabre\VObject;

/**
 * The VCard component
 *
 * This component represents the BEGIN:VCARD and END:VCARD found in every
 * vcard.
 *
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class VCard extends VObject\Component {

    /**
     * VCards with version 2.1, 3.0 and 4.0 are found.
     *
     * If the VCARD doesn't know its version, 4.0 is assumed.
     */
    const DEFAULT_VERSION = '4.0';

    /**
     * Validates the node for correctness.
     *
     * The following options are supported:
     *   - Node::REPAIR - If something is broken, and automatic repair may
     *                    be attempted.
     *
     * An array is returned with warnings.
     *
     * Every item in the array has the following properties:
     *    * level - (number between 1 and 3 with severity information)
     *    * message - (human readable message)
     *    * node - (reference to the offending node)
     *
     * @param int $options
     * @return array
     */
    public function validate($options = 0) {

        $warnings = array();

        $version = $this->select('VERSION');
        if (count($version)!==1) {
            $warnings[] = array(
                'level' => 1,
                'message' => 'The VERSION property must appear in the VCARD component exactly 1 time',
                'node' => $this,
            );
            if ($options & self::REPAIR) {
                $this->VERSION = self::DEFAULT_VERSION;
            }
        } else {
            $version = (string)$this->VERSION;
            if ($version!=='2.1' && $version!=='3.0' && $version!=='4.0') {
                $warnings[] = array(
                    'level' => 1,
                    'message' => 'Only vcard version 4.0 (RFC6350), version 3.0 (RFC2426) or version 2.1 (icm-vcard-2.1) are supported.',
                    'node' => $this,
                );
                if ($options & self::REPAIR) {
                    $this->VERSION = '4.0';
                }
            }

        }
        $version = $this->select('FN');
        if (count($version)!==1) {
            $warnings[] = array(
                'level' => 1,
                'message' => 'The FN property must appear in the VCARD component exactly 1 time',
                'node' => $this,
            );
            if (($options & self::REPAIR) && count($version) === 0) {
                // We're going to try to see if we can use the contents of the
                // N property.
                if (isset($this->N)) {
                    $value = explode(';', (string)$this->N);
                    if (isset($value[1]) && $value[1]) {
                        $this->FN = $value[1] . ' ' . $value[0];
                    } else {
                        $this->FN = $value[0];
                    }

                // Otherwise, the ORG property may work
                } elseif (isset($this->ORG)) {
                    $this->FN = (string)$this->ORG;
                }

            }
        }

        return array_merge(
            parent::validate($options),
            $warnings
        );

    }

}

