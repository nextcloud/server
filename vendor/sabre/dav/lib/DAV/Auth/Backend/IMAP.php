<?php

namespace Sabre\DAV\Auth\Backend;

/**
 * This is an authentication backend that uses imap.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Michael Niewöhner (foss@mniewoehner.de)
 * @author rosali (https://github.com/rosali)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class IMAP extends AbstractBasic
{
    /**
     * IMAP server in the form {host[:port][/flag1/flag2...]}.
     *
     * @see http://php.net/manual/en/function.imap-open.php
     *
     * @var string
     */
    protected $mailbox;

    /**
     * Creates the backend object.
     *
     * @param string $mailbox
     */
    public function __construct($mailbox)
    {
        $this->mailbox = $mailbox;
    }

    /**
     * Connects to an IMAP server and tries to authenticate.
     *
     * @param string $username
     * @param string $password
     *
     * @return bool
     */
    protected function imapOpen($username, $password)
    {
        $success = false;

        try {
            $imap = imap_open($this->mailbox, $username, $password, OP_HALFOPEN | OP_READONLY, 1);
            if ($imap) {
                $success = true;
            }
        } catch (\ErrorException $e) {
            error_log($e->getMessage());
        }

        $errors = imap_errors();
        if ($errors) {
            foreach ($errors as $error) {
                error_log($error);
            }
        }

        if (isset($imap) && $imap) {
            imap_close($imap);
        }

        return $success;
    }

    /**
     * Validates a username and password by trying to authenticate against IMAP.
     *
     * @param string $username
     * @param string $password
     *
     * @return bool
     */
    protected function validateUserPass($username, $password)
    {
        return $this->imapOpen($username, $password);
    }
}
