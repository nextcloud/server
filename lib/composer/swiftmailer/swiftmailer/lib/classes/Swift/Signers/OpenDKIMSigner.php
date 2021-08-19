<?php

/*
 * This file is part of SwiftMailer.
 * (c) 2004-2009 Chris Corbyn
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * DKIM Signer used to apply DKIM Signature to a message
 * Takes advantage of pecl extension.
 *
 * @author     Xavier De Cock <xdecock@gmail.com>
 *
 * @deprecated since SwiftMailer 6.1.0; use Swift_Signers_DKIMSigner instead.
 */
class Swift_Signers_OpenDKIMSigner extends Swift_Signers_DKIMSigner
{
    private $peclLoaded = false;

    private $dkimHandler = null;

    private $dropFirstLF = true;

    const CANON_RELAXED = 1;
    const CANON_SIMPLE = 2;
    const SIG_RSA_SHA1 = 3;
    const SIG_RSA_SHA256 = 4;

    public function __construct($privateKey, $domainName, $selector)
    {
        if (!\extension_loaded('opendkim')) {
            throw new Swift_SwiftException('php-opendkim extension not found');
        }

        $this->peclLoaded = true;

        parent::__construct($privateKey, $domainName, $selector);
    }

    public function addSignature(Swift_Mime_SimpleHeaderSet $headers)
    {
        $header = new Swift_Mime_Headers_OpenDKIMHeader('DKIM-Signature');
        $headerVal = $this->dkimHandler->getSignatureHeader();
        if (false === $headerVal || \is_int($headerVal)) {
            throw new Swift_SwiftException('OpenDKIM Error: '.$this->dkimHandler->getError());
        }
        $header->setValue($headerVal);
        $headers->set($header);

        return $this;
    }

    public function setHeaders(Swift_Mime_SimpleHeaderSet $headers)
    {
        $hash = 'rsa-sha1' == $this->hashAlgorithm ? OpenDKIMSign::ALG_RSASHA1 : OpenDKIMSign::ALG_RSASHA256;
        $bodyCanon = 'simple' == $this->bodyCanon ? OpenDKIMSign::CANON_SIMPLE : OpenDKIMSign::CANON_RELAXED;
        $headerCanon = 'simple' == $this->headerCanon ? OpenDKIMSign::CANON_SIMPLE : OpenDKIMSign::CANON_RELAXED;
        $this->dkimHandler = new OpenDKIMSign($this->privateKey, $this->selector, $this->domainName, $headerCanon, $bodyCanon, $hash, -1);
        // Hardcode signature Margin for now
        $this->dkimHandler->setMargin(78);

        if (!is_numeric($this->signatureTimestamp)) {
            OpenDKIM::setOption(OpenDKIM::OPTS_FIXEDTIME, time());
        } else {
            if (!OpenDKIM::setOption(OpenDKIM::OPTS_FIXEDTIME, $this->signatureTimestamp)) {
                throw new Swift_SwiftException('Unable to force signature timestamp ['.openssl_error_string().']');
            }
        }
        if (isset($this->signerIdentity)) {
            $this->dkimHandler->setSigner($this->signerIdentity);
        }
        $listHeaders = $headers->listAll();
        foreach ($listHeaders as $hName) {
            // Check if we need to ignore Header
            if (!isset($this->ignoredHeaders[strtolower($hName)])) {
                $tmp = $headers->getAll($hName);
                if ($headers->has($hName)) {
                    foreach ($tmp as $header) {
                        if ('' != $header->getFieldBody()) {
                            $htosign = $header->toString();
                            $this->dkimHandler->header($htosign);
                            $this->signedHeaders[] = $header->getFieldName();
                        }
                    }
                }
            }
        }

        return $this;
    }

    public function startBody()
    {
        if (!$this->peclLoaded) {
            return parent::startBody();
        }
        $this->dropFirstLF = true;
        $this->dkimHandler->eoh();

        return $this;
    }

    public function endBody()
    {
        if (!$this->peclLoaded) {
            return parent::endBody();
        }
        $this->dkimHandler->eom();

        return $this;
    }

    public function reset()
    {
        $this->dkimHandler = null;
        parent::reset();

        return $this;
    }

    /**
     * Set the signature timestamp.
     *
     * @param int $time
     *
     * @return $this
     */
    public function setSignatureTimestamp($time)
    {
        $this->signatureTimestamp = $time;

        return $this;
    }

    /**
     * Set the signature expiration timestamp.
     *
     * @param int $time
     *
     * @return $this
     */
    public function setSignatureExpiration($time)
    {
        $this->signatureExpiration = $time;

        return $this;
    }

    /**
     * Enable / disable the DebugHeaders.
     *
     * @param bool $debug
     *
     * @return $this
     */
    public function setDebugHeaders($debug)
    {
        $this->debugHeaders = (bool) $debug;

        return $this;
    }

    // Protected

    protected function canonicalizeBody($string)
    {
        if (!$this->peclLoaded) {
            return parent::canonicalizeBody($string);
        }
        if (true === $this->dropFirstLF) {
            if ("\r" == $string[0] && "\n" == $string[1]) {
                $string = substr($string, 2);
            }
        }
        $this->dropFirstLF = false;
        if (\strlen($string)) {
            $this->dkimHandler->body($string);
        }
    }
}
