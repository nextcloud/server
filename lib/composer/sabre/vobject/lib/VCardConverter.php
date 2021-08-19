<?php

namespace Sabre\VObject;

/**
 * This utility converts vcards from one version to another.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class VCardConverter
{
    /**
     * Converts a vCard object to a new version.
     *
     * targetVersion must be one of:
     *   Document::VCARD21
     *   Document::VCARD30
     *   Document::VCARD40
     *
     * Currently only 3.0 and 4.0 as input and output versions.
     *
     * 2.1 has some minor support for the input version, it's incomplete at the
     * moment though.
     *
     * If input and output version are identical, a clone is returned.
     *
     * @param int $targetVersion
     */
    public function convert(Component\VCard $input, $targetVersion)
    {
        $inputVersion = $input->getDocumentType();
        if ($inputVersion === $targetVersion) {
            return clone $input;
        }

        if (!in_array($inputVersion, [Document::VCARD21, Document::VCARD30, Document::VCARD40])) {
            throw new \InvalidArgumentException('Only vCard 2.1, 3.0 and 4.0 are supported for the input data');
        }
        if (!in_array($targetVersion, [Document::VCARD30, Document::VCARD40])) {
            throw new \InvalidArgumentException('You can only use vCard 3.0 or 4.0 for the target version');
        }

        $newVersion = Document::VCARD40 === $targetVersion ? '4.0' : '3.0';

        $output = new Component\VCard([
            'VERSION' => $newVersion,
        ]);

        // We might have generated a default UID. Remove it!
        unset($output->UID);

        foreach ($input->children() as $property) {
            $this->convertProperty($input, $output, $property, $targetVersion);
        }

        return $output;
    }

    /**
     * Handles conversion of a single property.
     *
     * @param int $targetVersion
     */
    protected function convertProperty(Component\VCard $input, Component\VCard $output, Property $property, $targetVersion)
    {
        // Skipping these, those are automatically added.
        if (in_array($property->name, ['VERSION', 'PRODID'])) {
            return;
        }

        $parameters = $property->parameters();
        $valueType = null;
        if (isset($parameters['VALUE'])) {
            $valueType = $parameters['VALUE']->getValue();
            unset($parameters['VALUE']);
        }
        if (!$valueType) {
            $valueType = $property->getValueType();
        }
        if (Document::VCARD30 !== $targetVersion && 'PHONE-NUMBER' === $valueType) {
            $valueType = null;
        }
        $newProperty = $output->createProperty(
            $property->name,
            $property->getParts(),
            [], // parameters will get added a bit later.
            $valueType
        );

        if (Document::VCARD30 === $targetVersion) {
            if ($property instanceof Property\Uri && in_array($property->name, ['PHOTO', 'LOGO', 'SOUND'])) {
                $newProperty = $this->convertUriToBinary($output, $newProperty);
            } elseif ($property instanceof Property\VCard\DateAndOrTime) {
                // In vCard 4, the birth year may be optional. This is not the
                // case for vCard 3. Apple has a workaround for this that
                // allows applications that support Apple's extension still
                // omit birthyears in vCard 3, but applications that do not
                // support this, will just use a random birthyear. We're
                // choosing 1604 for the birthyear, because that's what apple
                // uses.
                $parts = DateTimeParser::parseVCardDateTime($property->getValue());
                if (is_null($parts['year'])) {
                    $newValue = '1604-'.$parts['month'].'-'.$parts['date'];
                    $newProperty->setValue($newValue);
                    $newProperty['X-APPLE-OMIT-YEAR'] = '1604';
                }

                if ('ANNIVERSARY' == $newProperty->name) {
                    // Microsoft non-standard anniversary
                    $newProperty->name = 'X-ANNIVERSARY';

                    // We also need to add a new apple property for the same
                    // purpose. This apple property needs a 'label' in the same
                    // group, so we first need to find a groupname that doesn't
                    // exist yet.
                    $x = 1;
                    while ($output->select('ITEM'.$x.'.')) {
                        ++$x;
                    }
                    $output->add('ITEM'.$x.'.X-ABDATE', $newProperty->getValue(), ['VALUE' => 'DATE-AND-OR-TIME']);
                    $output->add('ITEM'.$x.'.X-ABLABEL', '_$!<Anniversary>!$_');
                }
            } elseif ('KIND' === $property->name) {
                switch (strtolower($property->getValue())) {
                    case 'org':
                        // vCard 3.0 does not have an equivalent to KIND:ORG,
                        // but apple has an extension that means the same
                        // thing.
                        $newProperty = $output->createProperty('X-ABSHOWAS', 'COMPANY');
                        break;

                    case 'individual':
                        // Individual is implicit, so we skip it.
                        return;

                    case 'group':
                        // OS X addressbook property
                        $newProperty = $output->createProperty('X-ADDRESSBOOKSERVER-KIND', 'GROUP');
                        break;
                }
            }
        } elseif (Document::VCARD40 === $targetVersion) {
            // These properties were removed in vCard 4.0
            if (in_array($property->name, ['NAME', 'MAILER', 'LABEL', 'CLASS'])) {
                return;
            }

            if ($property instanceof Property\Binary) {
                $newProperty = $this->convertBinaryToUri($output, $newProperty, $parameters);
            } elseif ($property instanceof Property\VCard\DateAndOrTime && isset($parameters['X-APPLE-OMIT-YEAR'])) {
                // If a property such as BDAY contained 'X-APPLE-OMIT-YEAR',
                // then we're stripping the year from the vcard 4 value.
                $parts = DateTimeParser::parseVCardDateTime($property->getValue());
                if ($parts['year'] === $property['X-APPLE-OMIT-YEAR']->getValue()) {
                    $newValue = '--'.$parts['month'].'-'.$parts['date'];
                    $newProperty->setValue($newValue);
                }

                // Regardless if the year matched or not, we do need to strip
                // X-APPLE-OMIT-YEAR.
                unset($parameters['X-APPLE-OMIT-YEAR']);
            }
            switch ($property->name) {
                case 'X-ABSHOWAS':
                    if ('COMPANY' === strtoupper($property->getValue())) {
                        $newProperty = $output->createProperty('KIND', 'ORG');
                    }
                    break;
                case 'X-ADDRESSBOOKSERVER-KIND':
                    if ('GROUP' === strtoupper($property->getValue())) {
                        $newProperty = $output->createProperty('KIND', 'GROUP');
                    }
                    break;
                case 'X-ANNIVERSARY':
                    $newProperty->name = 'ANNIVERSARY';
                    // If we already have an anniversary property with the same
                    // value, ignore.
                    foreach ($output->select('ANNIVERSARY') as $anniversary) {
                        if ($anniversary->getValue() === $newProperty->getValue()) {
                            return;
                        }
                    }
                    break;
                case 'X-ABDATE':
                    // Find out what the label was, if it exists.
                    if (!$property->group) {
                        break;
                    }
                    $label = $input->{$property->group.'.X-ABLABEL'};

                    // We only support converting anniversaries.
                    if (!$label || '_$!<Anniversary>!$_' !== $label->getValue()) {
                        break;
                    }

                    // If we already have an anniversary property with the same
                    // value, ignore.
                    foreach ($output->select('ANNIVERSARY') as $anniversary) {
                        if ($anniversary->getValue() === $newProperty->getValue()) {
                            return;
                        }
                    }
                    $newProperty->name = 'ANNIVERSARY';
                    break;
                // Apple's per-property label system.
                case 'X-ABLABEL':
                    if ('_$!<Anniversary>!$_' === $newProperty->getValue()) {
                        // We can safely remove these, as they are converted to
                        // ANNIVERSARY properties.
                        return;
                    }
                    break;
            }
        }

        // set property group
        $newProperty->group = $property->group;

        if (Document::VCARD40 === $targetVersion) {
            $this->convertParameters40($newProperty, $parameters);
        } else {
            $this->convertParameters30($newProperty, $parameters);
        }

        // Lastly, we need to see if there's a need for a VALUE parameter.
        //
        // We can do that by instantiating a empty property with that name, and
        // seeing if the default valueType is identical to the current one.
        $tempProperty = $output->createProperty($newProperty->name);
        if ($tempProperty->getValueType() !== $newProperty->getValueType()) {
            $newProperty['VALUE'] = $newProperty->getValueType();
        }

        $output->add($newProperty);
    }

    /**
     * Converts a BINARY property to a URI property.
     *
     * vCard 4.0 no longer supports BINARY properties.
     *
     * @param Property\Uri $property the input property
     * @param $parameters list of parameters that will eventually be added to
     *                    the new property
     *
     * @return Property\Uri
     */
    protected function convertBinaryToUri(Component\VCard $output, Property\Binary $newProperty, array &$parameters)
    {
        $value = $newProperty->getValue();
        $newProperty = $output->createProperty(
            $newProperty->name,
            null, // no value
            [], // no parameters yet
            'URI' // Forcing the BINARY type
        );

        $mimeType = 'application/octet-stream';

        // See if we can find a better mimetype.
        if (isset($parameters['TYPE'])) {
            $newTypes = [];
            foreach ($parameters['TYPE']->getParts() as $typePart) {
                if (in_array(
                    strtoupper($typePart),
                    ['JPEG', 'PNG', 'GIF']
                )) {
                    $mimeType = 'image/'.strtolower($typePart);
                } else {
                    $newTypes[] = $typePart;
                }
            }

            // If there were any parameters we're not converting to a
            // mime-type, we need to keep them.
            if ($newTypes) {
                $parameters['TYPE']->setParts($newTypes);
            } else {
                unset($parameters['TYPE']);
            }
        }

        $newProperty->setValue('data:'.$mimeType.';base64,'.base64_encode($value));

        return $newProperty;
    }

    /**
     * Converts a URI property to a BINARY property.
     *
     * In vCard 4.0 attachments are encoded as data: uri. Even though these may
     * be valid in vCard 3.0 as well, we should convert those to BINARY if
     * possible, to improve compatibility.
     *
     * @param Property\Uri $property the input property
     *
     * @return Property\Binary|null
     */
    protected function convertUriToBinary(Component\VCard $output, Property\Uri $newProperty)
    {
        $value = $newProperty->getValue();

        // Only converting data: uris
        if ('data:' !== substr($value, 0, 5)) {
            return $newProperty;
        }

        $newProperty = $output->createProperty(
            $newProperty->name,
            null, // no value
            [], // no parameters yet
            'BINARY'
        );

        $mimeType = substr($value, 5, strpos($value, ',') - 5);
        if (strpos($mimeType, ';')) {
            $mimeType = substr($mimeType, 0, strpos($mimeType, ';'));
            $newProperty->setValue(base64_decode(substr($value, strpos($value, ',') + 1)));
        } else {
            $newProperty->setValue(substr($value, strpos($value, ',') + 1));
        }
        unset($value);

        $newProperty['ENCODING'] = 'b';
        switch ($mimeType) {
            case 'image/jpeg':
                $newProperty['TYPE'] = 'JPEG';
                break;
            case 'image/png':
                $newProperty['TYPE'] = 'PNG';
                break;
            case 'image/gif':
                $newProperty['TYPE'] = 'GIF';
                break;
        }

        return $newProperty;
    }

    /**
     * Adds parameters to a new property for vCard 4.0.
     */
    protected function convertParameters40(Property $newProperty, array $parameters)
    {
        // Adding all parameters.
        foreach ($parameters as $param) {
            // vCard 2.1 allowed parameters with no name
            if ($param->noName) {
                $param->noName = false;
            }

            switch ($param->name) {
                // We need to see if there's any TYPE=PREF, because in vCard 4
                // that's now PREF=1.
                case 'TYPE':
                    foreach ($param->getParts() as $paramPart) {
                        if ('PREF' === strtoupper($paramPart)) {
                            $newProperty->add('PREF', '1');
                        } else {
                            $newProperty->add($param->name, $paramPart);
                        }
                    }
                    break;
                // These no longer exist in vCard 4
                case 'ENCODING':
                case 'CHARSET':
                    break;

                default:
                    $newProperty->add($param->name, $param->getParts());
                    break;
            }
        }
    }

    /**
     * Adds parameters to a new property for vCard 3.0.
     */
    protected function convertParameters30(Property $newProperty, array $parameters)
    {
        // Adding all parameters.
        foreach ($parameters as $param) {
            // vCard 2.1 allowed parameters with no name
            if ($param->noName) {
                $param->noName = false;
            }

            switch ($param->name) {
                case 'ENCODING':
                    // This value only existed in vCard 2.1, and should be
                    // removed for anything else.
                    if ('QUOTED-PRINTABLE' !== strtoupper($param->getValue())) {
                        $newProperty->add($param->name, $param->getParts());
                    }
                    break;

                /*
                 * Converting PREF=1 to TYPE=PREF.
                 *
                 * Any other PREF numbers we'll drop.
                 */
                case 'PREF':
                    if ('1' == $param->getValue()) {
                        $newProperty->add('TYPE', 'PREF');
                    }
                    break;

                default:
                    $newProperty->add($param->name, $param->getParts());
                    break;
            }
        }
    }
}
