<?php

use Sabre_CalDAV_SharingPlugin as SharingPlugin;

/**
 * Invite property
 *
 * This property encodes the 'invite' property, as defined by
 * the 'caldav-sharing-02' spec, in the http://calendarserver.org/ns/
 * namespace.
 *
 * @package Sabre
 * @subpackage CalDAV
 * @see https://trac.calendarserver.org/browser/CalendarServer/trunk/doc/Extensions/caldav-sharing-02.txt
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_CalDAV_Property_Invite extends Sabre_DAV_Property {

    /**
     * The list of users a calendar has been shared to.
     *
     * @var array
     */
    protected $users;

    /**
     * Creates the property.
     *
     * Users is an array. Each element of the array has the following
     * properties:
     *
     *   * href - Often a mailto: address
     *   * commonName - Optional, for example a first and lastname for a user.
     *   * status - One of the SharingPlugin::STATUS_* constants.
     *   * readOnly - true or false
     *   * summary - Optional, description of the share
     *
     * @param array $users
     */
    public function __construct(array $users) {

        $this->users = $users;

    }

    /**
     * Returns the list of users, as it was passed to the constructor.
     *
     * @return array
     */
    public function getValue() {

        return $this->users;

    }

    /**
     * Serializes the property in a DOMDocument
     *
     * @param Sabre_DAV_Server $server
     * @param DOMElement $node
     * @return void
     */
    public function serialize(Sabre_DAV_Server $server,DOMElement $node) {

       $doc = $node->ownerDocument;
       foreach($this->users as $user) {

           $xuser = $doc->createElement('cs:user');

           $href = $doc->createElement('d:href');
           $href->appendChild($doc->createTextNode($user['href']));
           $xuser->appendChild($href);

           if (isset($user['commonName']) && $user['commonName']) {
               $commonName = $doc->createElement('cs:common-name');
               $commonName->appendChild($doc->createTextNode($user['commonName']));
               $xuser->appendChild($commonName);
           }

           switch($user['status']) {

               case SharingPlugin::STATUS_ACCEPTED :
                   $status = $doc->createElement('cs:invite-accepted');
                   $xuser->appendChild($status);
                   break;
               case SharingPlugin::STATUS_DECLINED :
                   $status = $doc->createElement('cs:invite-declined');
                   $xuser->appendChild($status);
                   break;
               case SharingPlugin::STATUS_NORESPONSE :
                   $status = $doc->createElement('cs:invite-noresponse');
                   $xuser->appendChild($status);
                   break;
               case SharingPlugin::STATUS_INVALID :
                   $status = $doc->createElement('cs:invite-invalid');
                   $xuser->appendChild($status);
                   break;

           }

           $xaccess = $doc->createElement('cs:access');

           if ($user['readOnly']) {
                $xaccess->appendChild(
                    $doc->createElement('cs:read')
                );
           } else {
                $xaccess->appendChild(
                    $doc->createElement('cs:read-write')
                );
           }
           $xuser->appendChild($xaccess);

           if (isset($user['summary']) && $user['summary']) {
               $summary = $doc->createElement('cs:summary');
               $summary->appendChild($doc->createTextNode($user['summary']));
               $xuser->appendChild($summary);
           }

           $node->appendChild($xuser);

       }

    }

    /**
     * Unserializes the property.
     *
     * This static method should return a an instance of this object.
     *
     * @param DOMElement $prop
     * @return Sabre_DAV_IProperty
     */
    static function unserialize(DOMElement $prop) {

        $xpath = new \DOMXPath($prop->ownerDocument);
        $xpath->registerNamespace('cs', Sabre_CalDAV_Plugin::NS_CALENDARSERVER);
        $xpath->registerNamespace('d',  'DAV:');

        $users = array();

        foreach($xpath->query('cs:user', $prop) as $user) {

            $status = null;
            if ($xpath->evaluate('boolean(cs:invite-accepted)', $user)) {
                $status = SharingPlugin::STATUS_ACCEPTED;
            } elseif ($xpath->evaluate('boolean(cs:invite-declined)', $user)) {
                $status = SharingPlugin::STATUS_DECLINED;
            } elseif ($xpath->evaluate('boolean(cs:invite-noresponse)', $user)) {
                $status = SharingPlugin::STATUS_NORESPONSE;
            } elseif ($xpath->evaluate('boolean(cs:invite-invalid)', $user)) {
                $status = SharingPlugin::STATUS_INVALID;
            } else {
                throw new Sabre_DAV_Exception('Every cs:user property must have one of cs:invite-accepted, cs:invite-declined, cs:invite-noresponse or cs:invite-invalid');
            }
            $users[] = array(
                'href' => $xpath->evaluate('string(d:href)', $user),
                'commonName' => $xpath->evaluate('string(cs:common-name)', $user),
                'readOnly' => $xpath->evaluate('boolean(cs:access/cs:read)', $user),
                'summary' => $xpath->evaluate('string(cs:summary)', $user),
                'status' => $status,
            );

        }

        return new self($users);

    }

}
