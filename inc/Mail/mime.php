<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */
// +-----------------------------------------------------------------------+
// | Copyright (c) 2002-2003  Richard Heyes                                |
// | Copyright (c) 2003-2005  The PHP Group                                |
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
// |         Tomas V.V.Cox <cox@idecnet.com> (port to PEAR)                |
// +-----------------------------------------------------------------------+
//
// $Id: mime.php,v 1.39 2005/06/13 21:24:16 cipri Exp $

require_once('PEAR.php');
require_once('Mail/mimePart.php');

/**
 * Mime mail composer class. Can handle: text and html bodies, embedded html
 * images and attachments.
 * Documentation and examples of this class are avaible here:
 * http://pear.php.net/manual/
 *
 * @notes This class is based on HTML Mime Mail class from
 *   Richard Heyes <richard@phpguru.org> which was based also
 *   in the mime_mail.class by Tobias Ratschiller <tobias@dnet.it> and
 *   Sascha Schumann <sascha@schumann.cx>
 *
 * @author   Richard Heyes <richard.heyes@heyes-computing.net>
 * @author   Tomas V.V.Cox <cox@idecnet.com>
 * @package  Mail
 * @access   public
 */
class Mail_mime
{
    /**
     * Contains the plain text part of the email
     * @var string
     */
    var $_txtbody;
    /**
     * Contains the html part of the email
     * @var string
     */
    var $_htmlbody;
    /**
     * contains the mime encoded text
     * @var string
     */
    var $_mime;
    /**
     * contains the multipart content
     * @var string
     */
    var $_multipart;
    /**
     * list of the attached images
     * @var array
     */
    var $_html_images = array();
    /**
     * list of the attachements
     * @var array
     */
    var $_parts = array();
    /**
     * Build parameters
     * @var array
     */
    var $_build_params = array();
    /**
     * Headers for the mail
     * @var array
     */
    var $_headers = array();
    /**
     * End Of Line sequence (for serialize)
     * @var string
     */
    var $_eol;


    /**
     * Constructor function
     *
     * @access public
     */
    function Mail_mime($crlf = "\r\n")
    {
        $this->_setEOL($crlf);
        $this->_build_params = array(
                                     'text_encoding' => '7bit',
                                     'html_encoding' => 'quoted-printable',
                                     '7bit_wrap'     => 998,
                                     'html_charset'  => 'ISO-8859-1',
                                     'text_charset'  => 'ISO-8859-1',
                                     'head_charset'  => 'ISO-8859-1'
                                    );
    }

    /**
     * Wakeup (unserialize) - re-sets EOL constant
     *
     * @access private
     */
    function __wakeup()
    {
        $this->_setEOL($this->_eol);
    }

    /**
     * Accessor function to set the body text. Body text is used if
     * it's not an html mail being sent or else is used to fill the
     * text/plain part that emails clients who don't support
     * html should show.
     *
     * @param  string  $data   Either a string or
     *                         the file name with the contents
     * @param  bool    $isfile If true the first param should be treated
     *                         as a file name, else as a string (default)
     * @param  bool    $append If true the text or file is appended to
     *                         the existing body, else the old body is
     *                         overwritten
     * @return mixed   true on success or PEAR_Error object
     * @access public
     */
    function setTXTBody($data, $isfile = false, $append = false)
    {
        if (!$isfile) {
            if (!$append) {
                $this->_txtbody = $data;
            } else {
                $this->_txtbody .= $data;
            }
        } else {
            $cont = $this->_file2str($data);
            if (PEAR::isError($cont)) {
                return $cont;
            }
            if (!$append) {
                $this->_txtbody = $cont;
            } else {
                $this->_txtbody .= $cont;
            }
        }
        return true;
    }

    /**
     * Adds a html part to the mail
     *
     * @param  string  $data   Either a string or the file name with the
     *                         contents
     * @param  bool    $isfile If true the first param should be treated
     *                         as a file name, else as a string (default)
     * @return mixed   true on success or PEAR_Error object
     * @access public
     */
    function setHTMLBody($data, $isfile = false)
    {
        if (!$isfile) {
            $this->_htmlbody = $data;
        } else {
            $cont = $this->_file2str($data);
            if (PEAR::isError($cont)) {
                return $cont;
            }
            $this->_htmlbody = $cont;
        }

        return true;
    }

    /**
     * Adds an image to the list of embedded images.
     *
     * @param  string  $file       The image file name OR image data itself
     * @param  string  $c_type     The content type
     * @param  string  $name       The filename of the image.
     *                             Only use if $file is the image data
     * @param  bool    $isfilename Whether $file is a filename or not
     *                             Defaults to true
     * @return mixed   true on success or PEAR_Error object
     * @access public
     */
    function addHTMLImage($file, $c_type='application/octet-stream',
                          $name = '', $isfilename = true)
    {
        $filedata = ($isfilename === true) ? $this->_file2str($file)
                                           : $file;
        if ($isfilename === true) {
            $filename = ($name == '' ? basename($file) : basename($name));
        } else {
            $filename = basename($name);
        }
        if (PEAR::isError($filedata)) {
            return $filedata;
        }
        $this->_html_images[] = array(
                                      'body'   => $filedata,
                                      'name'   => $filename,
                                      'c_type' => $c_type,
                                      'cid'    => md5(uniqid(time()))
                                     );
        return true;
    }

    /**
     * Adds a file to the list of attachments.
     *
     * @param  string  $file       The file name of the file to attach
     *                             OR the file data itself
     * @param  string  $c_type     The content type
     * @param  string  $name       The filename of the attachment
     *                             Only use if $file is the file data
     * @param  bool    $isFilename Whether $file is a filename or not
     *                             Defaults to true
     * @return mixed true on success or PEAR_Error object
     * @access public
     */
    function addAttachment($file, $c_type = 'application/octet-stream',
                           $name = '', $isfilename = true,
                           $encoding = 'base64')
    {
        $filedata = ($isfilename === true) ? $this->_file2str($file)
                                           : $file;
        if ($isfilename === true) {
            // Force the name the user supplied, otherwise use $file
            $filename = (!empty($name)) ? $name : $file;
        } else {
            $filename = $name;
        }
        if (empty($filename)) {
            return PEAR::raiseError(
              'The supplied filename for the attachment can\'t be empty'
            );
        }
        $filename = basename($filename);
        if (PEAR::isError($filedata)) {
            return $filedata;
        }

        $this->_parts[] = array(
                                'body'     => $filedata,
                                'name'     => $filename,
                                'c_type'   => $c_type,
                                'encoding' => $encoding
                               );
        return true;
    }

    /**
     * Get the contents of the given file name as string
     *
     * @param  string  $file_name  path of file to process
     * @return string  contents of $file_name
     * @access private
     */
    function &_file2str($file_name)
    {
        if (!is_readable($file_name)) {
            return PEAR::raiseError('File is not readable ' . $file_name);
        }
        if (!$fd = fopen($file_name, 'rb')) {
            return PEAR::raiseError('Could not open ' . $file_name);
        }
        $filesize = filesize($file_name);
        if ($filesize == 0){
            $cont =  "";
        }else{
            $cont = fread($fd, $filesize);
        }
        fclose($fd);
        return $cont;
    }

    /**
     * Adds a text subpart to the mimePart object and
     * returns it during the build process.
     *
     * @param mixed    The object to add the part to, or
     *                 null if a new object is to be created.
     * @param string   The text to add.
     * @return object  The text mimePart object
     * @access private
     */
    function &_addTextPart(&$obj, $text)
    {
        $params['content_type'] = 'text/plain';
        $params['encoding']     = $this->_build_params['text_encoding'];
        $params['charset']      = $this->_build_params['text_charset'];
        if (is_object($obj)) {
            return $obj->addSubpart($text, $params);
        } else {
            return new Mail_mimePart($text, $params);
        }
    }

    /**
     * Adds a html subpart to the mimePart object and
     * returns it during the build process.
     *
     * @param  mixed   The object to add the part to, or
     *                 null if a new object is to be created.
     * @return object  The html mimePart object
     * @access private
     */
    function &_addHtmlPart(&$obj)
    {
        $params['content_type'] = 'text/html';
        $params['encoding']     = $this->_build_params['html_encoding'];
        $params['charset']      = $this->_build_params['html_charset'];
        if (is_object($obj)) {
            return $obj->addSubpart($this->_htmlbody, $params);
        } else {
            return new Mail_mimePart($this->_htmlbody, $params);
        }
    }

    /**
     * Creates a new mimePart object, using multipart/mixed as
     * the initial content-type and returns it during the
     * build process.
     *
     * @return object  The multipart/mixed mimePart object
     * @access private
     */
    function &_addMixedPart()
    {
        $params['content_type'] = 'multipart/mixed';
        return new Mail_mimePart('', $params);
    }

    /**
     * Adds a multipart/alternative part to a mimePart
     * object (or creates one), and returns it during
     * the build process.
     *
     * @param  mixed   The object to add the part to, or
     *                 null if a new object is to be created.
     * @return object  The multipart/mixed mimePart object
     * @access private
     */
    function &_addAlternativePart(&$obj)
    {
        $params['content_type'] = 'multipart/alternative';
        if (is_object($obj)) {
            return $obj->addSubpart('', $params);
        } else {
            return new Mail_mimePart('', $params);
        }
    }

    /**
     * Adds a multipart/related part to a mimePart
     * object (or creates one), and returns it during
     * the build process.
     *
     * @param mixed    The object to add the part to, or
     *                 null if a new object is to be created
     * @return object  The multipart/mixed mimePart object
     * @access private
     */
    function &_addRelatedPart(&$obj)
    {
        $params['content_type'] = 'multipart/related';
        if (is_object($obj)) {
            return $obj->addSubpart('', $params);
        } else {
            return new Mail_mimePart('', $params);
        }
    }

    /**
     * Adds an html image subpart to a mimePart object
     * and returns it during the build process.
     *
     * @param  object  The mimePart to add the image to
     * @param  array   The image information
     * @return object  The image mimePart object
     * @access private
     */
    function &_addHtmlImagePart(&$obj, $value)
    {
        $params['content_type'] = $value['c_type'];
        $params['encoding']     = 'base64';
        $params['disposition']  = 'inline';
        $params['dfilename']    = $value['name'];
        $params['cid']          = $value['cid'];
        $obj->addSubpart($value['body'], $params);
    }

    /**
     * Adds an attachment subpart to a mimePart object
     * and returns it during the build process.
     *
     * @param  object  The mimePart to add the image to
     * @param  array   The attachment information
     * @return object  The image mimePart object
     * @access private
     */
    function &_addAttachmentPart(&$obj, $value)
    {
        $params['content_type'] = $value['c_type'];
        $params['encoding']     = $value['encoding'];
        $params['disposition']  = 'attachment';
        $params['dfilename']    = $value['name'];
        $obj->addSubpart($value['body'], $params);
    }

    /**
     * Builds the multipart message from the list ($this->_parts) and
     * returns the mime content.
     *
     * @param  array  Build parameters that change the way the email
     *                is built. Should be associative. Can contain:
     *                text_encoding  -  What encoding to use for plain text
     *                                  Default is 7bit
     *                html_encoding  -  What encoding to use for html
     *                                  Default is quoted-printable
     *                7bit_wrap      -  Number of characters before text is
     *                                  wrapped in 7bit encoding
     *                                  Default is 998
     *                html_charset   -  The character set to use for html.
     *                                  Default is iso-8859-1
     *                text_charset   -  The character set to use for text.
     *                                  Default is iso-8859-1
     *                head_charset   -  The character set to use for headers.
     *                                  Default is iso-8859-1
     * @return string The mime content
     * @access public
     */
    function &get($build_params = null)
    {
        if (isset($build_params)) {
            while (list($key, $value) = each($build_params)) {
                $this->_build_params[$key] = $value;
            }
        }

        if (!empty($this->_html_images) AND isset($this->_htmlbody)) {
            foreach ($this->_html_images as $value) {
                $regex = '#(\s)((?i)src|background|href(?-i))\s*=\s*(["\']?)' . preg_quote($value['name'], '#') .
                         '\3#';
                $rep = '\1\2=\3cid:' . $value['cid'] .'\3';
                $this->_htmlbody = preg_replace($regex, $rep,
                                       $this->_htmlbody
                                   );
            }
        }

        $null        = null;
        $attachments = !empty($this->_parts)                ? true : false;
        $html_images = !empty($this->_html_images)          ? true : false;
        $html        = !empty($this->_htmlbody)             ? true : false;
        $text        = (!$html AND !empty($this->_txtbody)) ? true : false;

        switch (true) {
        case $text AND !$attachments:
            $message =& $this->_addTextPart($null, $this->_txtbody);
            break;

        case !$text AND !$html AND $attachments:
            $message =& $this->_addMixedPart();
            for ($i = 0; $i < count($this->_parts); $i++) {
                $this->_addAttachmentPart($message, $this->_parts[$i]);
            }
            break;

        case $text AND $attachments:
            $message =& $this->_addMixedPart();
            $this->_addTextPart($message, $this->_txtbody);
            for ($i = 0; $i < count($this->_parts); $i++) {
                $this->_addAttachmentPart($message, $this->_parts[$i]);
            }
            break;

        case $html AND !$attachments AND !$html_images:
            if (isset($this->_txtbody)) {
                $message =& $this->_addAlternativePart($null);
                $this->_addTextPart($message, $this->_txtbody);
                $this->_addHtmlPart($message);
            } else {
                $message =& $this->_addHtmlPart($null);
            }
            break;

        case $html AND !$attachments AND $html_images:
            if (isset($this->_txtbody)) {
                $message =& $this->_addAlternativePart($null);
                $this->_addTextPart($message, $this->_txtbody);
                $related =& $this->_addRelatedPart($message);
            } else {
                $message =& $this->_addRelatedPart($null);
                $related =& $message;
            }
            $this->_addHtmlPart($related);
            for ($i = 0; $i < count($this->_html_images); $i++) {
                $this->_addHtmlImagePart($related, $this->_html_images[$i]);
            }
            break;

        case $html AND $attachments AND !$html_images:
            $message =& $this->_addMixedPart();
            if (isset($this->_txtbody)) {
                $alt =& $this->_addAlternativePart($message);
                $this->_addTextPart($alt, $this->_txtbody);
                $this->_addHtmlPart($alt);
            } else {
                $this->_addHtmlPart($message);
            }
            for ($i = 0; $i < count($this->_parts); $i++) {
                $this->_addAttachmentPart($message, $this->_parts[$i]);
            }
            break;

        case $html AND $attachments AND $html_images:
            $message =& $this->_addMixedPart();
            if (isset($this->_txtbody)) {
                $alt =& $this->_addAlternativePart($message);
                $this->_addTextPart($alt, $this->_txtbody);
                $rel =& $this->_addRelatedPart($alt);
            } else {
                $rel =& $this->_addRelatedPart($message);
            }
            $this->_addHtmlPart($rel);
            for ($i = 0; $i < count($this->_html_images); $i++) {
                $this->_addHtmlImagePart($rel, $this->_html_images[$i]);
            }
            for ($i = 0; $i < count($this->_parts); $i++) {
                $this->_addAttachmentPart($message, $this->_parts[$i]);
            }
            break;

        }

        if (isset($message)) {
            $output = $message->encode();
            $this->_headers = array_merge($this->_headers,
                                          $output['headers']);
            return $output['body'];

        } else {
            return false;
        }
    }

    /**
     * Returns an array with the headers needed to prepend to the email
     * (MIME-Version and Content-Type). Format of argument is:
     * $array['header-name'] = 'header-value';
     *
     * @param  array $xtra_headers Assoc array with any extra headers.
     *                             Optional.
     * @return array Assoc array with the mime headers
     * @access public
     */
    function &headers($xtra_headers = null)
    {
        // Content-Type header should already be present,
        // So just add mime version header
        $headers['MIME-Version'] = '1.0';
        if (isset($xtra_headers)) {
            $headers = array_merge($headers, $xtra_headers);
        }
        $this->_headers = array_merge($headers, $this->_headers);

        return $this->_encodeHeaders($this->_headers);
    }

    /**
     * Get the text version of the headers
     * (usefull if you want to use the PHP mail() function)
     *
     * @param  array   $xtra_headers Assoc array with any extra headers.
     *                               Optional.
     * @return string  Plain text headers
     * @access public
     */
    function txtHeaders($xtra_headers = null)
    {
        $headers = $this->headers($xtra_headers);
        $ret = '';
        foreach ($headers as $key => $val) {
            $ret .= "$key: $val" . MAIL_MIME_CRLF;
        }
        return $ret;
    }

    /**
     * Sets the Subject header
     *
     * @param  string $subject String to set the subject to
     * access  public
     */
    function setSubject($subject)
    {
        $this->_headers['Subject'] = $subject;
    }

    /**
     * Set an email to the From (the sender) header
     *
     * @param  string $email The email direction to add
     * @access public
     */
    function setFrom($email)
    {
        $this->_headers['From'] = $email;
    }

    /**
     * Add an email to the Cc (carbon copy) header
     * (multiple calls to this method are allowed)
     *
     * @param  string $email The email direction to add
     * @access public
     */
    function addCc($email)
    {
        if (isset($this->_headers['Cc'])) {
            $this->_headers['Cc'] .= ", $email";
        } else {
            $this->_headers['Cc'] = $email;
        }
    }

    /**
     * Add an email to the Bcc (blank carbon copy) header
     * (multiple calls to this method are allowed)
     *
     * @param  string $email The email direction to add
     * @access public
     */
    function addBcc($email)
    {
        if (isset($this->_headers['Bcc'])) {
            $this->_headers['Bcc'] .= ", $email";
        } else {
            $this->_headers['Bcc'] = $email;
        }
    }

    /**
     * Encodes a header as per RFC2047
     *
     * @param  string  $input The header data to encode
     * @return string  Encoded data
     * @access private
     */
    function _encodeHeaders($input)
    {
        foreach ($input as $hdr_name => $hdr_value) {
            preg_match_all('/(\w*[\x80-\xFF]+\w*)/', $hdr_value, $matches);
            foreach ($matches[1] as $value) {
                $replacement = preg_replace('/([\x80-\xFF])/e',
                                            '"=" .
                                            strtoupper(dechex(ord("\1")))',
                                            $value);
                $hdr_value = str_replace($value, '=?' .
                                         $this->_build_params['head_charset'] .
                                         '?Q?' . $replacement . '?=',
                                         $hdr_value);
            }
            $input[$hdr_name] = $hdr_value;
        }

        return $input;
    }

    /**
     * Set the object's end-of-line and define the constant if applicable
     *
     * @param string $eol End Of Line sequence
     * @access private
     */
    function _setEOL($eol)
    {
        $this->_eol = $eol;
        if (!defined('MAIL_MIME_CRLF')) {
            define('MAIL_MIME_CRLF', $this->_eol, true);
        }
    }

    

} // End of class
?>
