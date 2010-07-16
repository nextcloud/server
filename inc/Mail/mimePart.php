<?php
// +-----------------------------------------------------------------------+
// | Copyright (c) 2002-2003  Richard Heyes                                     |
// | All rights reserved.                                                  |
// |                                                                       |
// | Redistribution and use in source and binary forms, with or without    |
// | modification, are permitted provided that the following conditions    |
// | are met:                                                              |
// |                                                                       |
// | o Redistributions of source code must retain the above copyright      |
// |   notice, this list of conditions and the following disclaimer.       |
// | o Redistributions in binary form must reproduce the above copyright   |
// |   notice, this list of conditions and the following disclaimer in the |
// |   documentation and/or other materials provided with the distribution.|
// | o The names of the authors may not be used to endorse or promote      |
// |   products derived from this software without specific prior written  |
// |   permission.                                                         |
// |                                                                       |
// | THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS   |
// | "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT     |
// | LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR |
// | A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT  |
// | OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, |
// | SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT      |
// | LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, |
// | DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY |
// | THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT   |
// | (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE |
// | OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.  |
// |                                                                       |
// +-----------------------------------------------------------------------+
// | Author: Richard Heyes <richard@phpguru.org>                           |
// +-----------------------------------------------------------------------+

/**
*
*  Raw mime encoding class
*
* What is it?
*   This class enables you to manipulate and build
*   a mime email from the ground up.
*
* Why use this instead of mime.php?
*   mime.php is a userfriendly api to this class for
*   people who aren't interested in the internals of
*   mime mail. This class however allows full control
*   over the email.
*
* Eg.
*
* // Since multipart/mixed has no real body, (the body is
* // the subpart), we set the body argument to blank.
*
* $params['content_type'] = 'multipart/mixed';
* $email = new Mail_mimePart('', $params);
*
* // Here we add a text part to the multipart we have
* // already. Assume $body contains plain text.
*
* $params['content_type'] = 'text/plain';
* $params['encoding']     = '7bit';
* $text = $email->addSubPart($body, $params);
*
* // Now add an attachment. Assume $attach is
* the contents of the attachment
*
* $params['content_type'] = 'application/zip';
* $params['encoding']     = 'base64';
* $params['disposition']  = 'attachment';
* $params['dfilename']    = 'example.zip';
* $attach =& $email->addSubPart($body, $params);
*
* // Now build the email. Note that the encode
* // function returns an associative array containing two
* // elements, body and headers. You will need to add extra
* // headers, (eg. Mime-Version) before sending.
*
* $email = $message->encode();
* $email['headers'][] = 'Mime-Version: 1.0';
*
*
* Further examples are available at http://www.phpguru.org
*
* TODO:
*  - Set encode() to return the $obj->encoded if encode()
*    has already been run. Unless a flag is passed to specifically
*    re-build the message.
*
* @author  Richard Heyes <richard@phpguru.org>
* @version $Revision: 1.13 $
* @package Mail
*/

class Mail_mimePart {

   /**
    * The encoding type of this part
    * @var string
    */
    var $_encoding;

   /**
    * An array of subparts
    * @var array
    */
    var $_subparts;

   /**
    * The output of this part after being built
    * @var string
    */
    var $_encoded;

   /**
    * Headers for this part
    * @var array
    */
    var $_headers;

   /**
    * The body of this part (not encoded)
    * @var string
    */
    var $_body;

    /**
     * Constructor.
     *
     * Sets up the object.
     *
     * @param $body   - The body of the mime part if any.
     * @param $params - An associative array of parameters:
     *                  content_type - The content type for this part eg multipart/mixed
     *                  encoding     - The encoding to use, 7bit, 8bit, base64, or quoted-printable
     *                  cid          - Content ID to apply
     *                  disposition  - Content disposition, inline or attachment
     *                  dfilename    - Optional filename parameter for content disposition
     *                  description  - Content description
     *                  charset      - Character set to use
     * @access public
     */
    function Mail_mimePart($body = '', $params = array())
    {
        if (!defined('MAIL_MIMEPART_CRLF')) {
            define('MAIL_MIMEPART_CRLF', defined('MAIL_MIME_CRLF') ? MAIL_MIME_CRLF : "\r\n", TRUE);
        }

        foreach ($params as $key => $value) {
            switch ($key) {
                case 'content_type':
                    $headers['Content-Type'] = $value . (isset($charset) ? '; charset="' . $charset . '"' : '');
                    break;

                case 'encoding':
                    $this->_encoding = $value;
                    $headers['Content-Transfer-Encoding'] = $value;
                    break;

                case 'cid':
                    $headers['Content-ID'] = '<' . $value . '>';
                    break;

                case 'disposition':
                    $headers['Content-Disposition'] = $value . (isset($dfilename) ? '; filename="' . $dfilename . '"' : '');
                    break;

                case 'dfilename':
                    if (isset($headers['Content-Disposition'])) {
                        $headers['Content-Disposition'] .= '; filename="' . $value . '"';
                    } else {
                        $dfilename = $value;
                    }
                    break;

                case 'description':
                    $headers['Content-Description'] = $value;
                    break;

                case 'charset':
                    if (isset($headers['Content-Type'])) {
                        $headers['Content-Type'] .= '; charset="' . $value . '"';
                    } else {
                        $charset = $value;
                    }
                    break;
            }
        }

        // Default content-type
        if (!isset($headers['Content-Type'])) {
            $headers['Content-Type'] = 'text/plain';
        }

        //Default encoding
        if (!isset($this->_encoding)) {
            $this->_encoding = '7bit';
        }

        // Assign stuff to member variables
        $this->_encoded  = array();
        $this->_headers  = $headers;
        $this->_body     = $body;
    }

    /**
     * encode()
     *
     * Encodes and returns the email. Also stores
     * it in the encoded member variable
     *
     * @return An associative array containing two elements,
     *         body and headers. The headers element is itself
     *         an indexed array.
     * @access public
     */
    function encode()
    {
        $encoded =& $this->_encoded;

        if (!empty($this->_subparts)) {
            srand((double)microtime()*1000000);
            $boundary = '=_' . md5(rand() . microtime());
            $this->_headers['Content-Type'] .= ';' . MAIL_MIMEPART_CRLF . "\t" . 'boundary="' . $boundary . '"';

            // Add body parts to $subparts
            for ($i = 0; $i < count($this->_subparts); $i++) {
                $headers = array();
                $tmp = $this->_subparts[$i]->encode();
                foreach ($tmp['headers'] as $key => $value) {
                    $headers[] = $key . ': ' . $value;
                }
                $subparts[] = implode(MAIL_MIMEPART_CRLF, $headers) . MAIL_MIMEPART_CRLF . MAIL_MIMEPART_CRLF . $tmp['body'];
            }

            $encoded['body'] = '--' . $boundary . MAIL_MIMEPART_CRLF .
                               implode('--' . $boundary . MAIL_MIMEPART_CRLF, $subparts) .
                               '--' . $boundary.'--' . MAIL_MIMEPART_CRLF;

        } else {
            $encoded['body'] = $this->_getEncodedData($this->_body, $this->_encoding) . MAIL_MIMEPART_CRLF;
        }

        // Add headers to $encoded
        $encoded['headers'] =& $this->_headers;

        return $encoded;
    }

    /**
     * &addSubPart()
     *
     * Adds a subpart to current mime part and returns
     * a reference to it
     *
     * @param $body   The body of the subpart, if any.
     * @param $params The parameters for the subpart, same
     *                as the $params argument for constructor.
     * @return A reference to the part you just added. It is
     *         crucial if using multipart/* in your subparts that
     *         you use =& in your script when calling this function,
     *         otherwise you will not be able to add further subparts.
     * @access public
     */
    function &addSubPart($body, $params)
    {
        $this->_subparts[] = new Mail_mimePart($body, $params);
        return $this->_subparts[count($this->_subparts) - 1];
    }

    /**
     * _getEncodedData()
     *
     * Returns encoded data based upon encoding passed to it
     *
     * @param $data     The data to encode.
     * @param $encoding The encoding type to use, 7bit, base64,
     *                  or quoted-printable.
     * @access private
     */
    function _getEncodedData($data, $encoding)
    {
        switch ($encoding) {
            case '8bit':
            case '7bit':
                return $data;
                break;

            case 'quoted-printable':
                return $this->_quotedPrintableEncode($data);
                break;

            case 'base64':
                return rtrim(chunk_split(base64_encode($data), 76, MAIL_MIMEPART_CRLF));
                break;

            default:
                return $data;
        }
    }

    /**
     * quoteadPrintableEncode()
     *
     * Encodes data to quoted-printable standard.
     *
     * @param $input    The data to encode
     * @param $line_max Optional max line length. Should
     *                  not be more than 76 chars
     *
     * @access private
     */
    function _quotedPrintableEncode($input , $line_max = 76)
    {
        $lines  = preg_split("/\r?\n/", $input);
        $eol    = MAIL_MIMEPART_CRLF;
        $escape = '=';
        $output = '';

        while(list(, $line) = each($lines)){

            $linlen     = strlen($line);
            $newline = '';

            for ($i = 0; $i < $linlen; $i++) {
                $char = substr($line, $i, 1);
                $dec  = ord($char);

                if (($dec == 32) AND ($i == ($linlen - 1))){    // convert space at eol only
                    $char = '=20';

                } elseif(($dec == 9) AND ($i == ($linlen - 1))) {  // convert tab at eol only
                    $char = '=09';
                } elseif($dec == 9) {
                    ; // Do nothing if a tab.
                } elseif(($dec == 61) OR ($dec < 32 ) OR ($dec > 126)) {
                    $char = $escape . strtoupper(sprintf('%02s', dechex($dec)));
                }

                if ((strlen($newline) + strlen($char)) >= $line_max) {        // MAIL_MIMEPART_CRLF is not counted
                    $output  .= $newline . $escape . $eol;                    // soft line break; " =\r\n" is okay
                    $newline  = '';
                }
                $newline .= $char;
            } // end of for
            $output .= $newline . $eol;
        }
        $output = substr($output, 0, -1 * strlen($eol)); // Don't want last crlf
        return $output;
    }
} // End of class
?>
