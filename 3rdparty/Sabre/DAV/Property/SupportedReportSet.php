<?php

/**
 * supported-report-set property.
 *
 * This property is defined in RFC3253, but since it's
 * so common in other webdav-related specs, it is part of the core server.
 *
 * @package Sabre
 * @subpackage DAV
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_DAV_Property_SupportedReportSet extends Sabre_DAV_Property {

    /**
     * List of reports
     *
     * @var array
     */
    protected $reports = array();

    /**
     * Creates the property
     *
     * Any reports passed in the constructor
     * should be valid report-types in clark-notation.
     *
     * Either a string or an array of strings must be passed.
     *
     * @param mixed $reports
     */
    public function __construct($reports = null) {

        if (!is_null($reports))
            $this->addReport($reports);

    }

    /**
     * Adds a report to this property
     *
     * The report must be a string in clark-notation.
     * Multiple reports can be specified as an array.
     *
     * @param mixed $report
     * @return void
     */
    public function addReport($report) {

        if (!is_array($report)) $report = array($report);

        foreach($report as $r) {

            if (!preg_match('/^{([^}]*)}(.*)$/',$r))
                throw new Sabre_DAV_Exception('Reportname must be in clark-notation');

            $this->reports[] = $r;

        }

    }

    /**
     * Returns the list of supported reports
     *
     * @return array
     */
    public function getValue() {

        return $this->reports;

    }

    /**
     * Serializes the node
     *
     * @param Sabre_DAV_Server $server
     * @param DOMElement $prop
     * @return void
     */
    public function serialize(Sabre_DAV_Server $server, DOMElement $prop) {

        foreach($this->reports as $reportName) {

            $supportedReport = $prop->ownerDocument->createElement('d:supported-report');
            $prop->appendChild($supportedReport);

            $report = $prop->ownerDocument->createElement('d:report');
            $supportedReport->appendChild($report);

            preg_match('/^{([^}]*)}(.*)$/',$reportName,$matches);

            list(, $namespace, $element) = $matches;

            $prefix = isset($server->xmlNamespaces[$namespace])?$server->xmlNamespaces[$namespace]:null;

            if ($prefix) {
                $report->appendChild($prop->ownerDocument->createElement($prefix . ':' . $element));
            } else {
                $report->appendChild($prop->ownerDocument->createElementNS($namespace, 'x:' . $element));
            }

        }

    }

}
