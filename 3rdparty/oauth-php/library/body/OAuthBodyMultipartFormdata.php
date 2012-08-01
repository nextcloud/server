<?php

/**
 * Create the body for a multipart/form-data message.
 * 
 * @version $Id: OAuthMultipartFormdata.php 6 2008-02-13 12:35:09Z marcw@pobox.com $
 * @author Marc Worrell <marcw@pobox.com>
 * @date  Jan 31, 2008 12:50:05 PM
 * 
 * The MIT License
 * 
 * Copyright (c) 2007-2008 Mediamatic Lab
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */


class OAuthBodyMultipartFormdata
{
    /**
     * Builds the request string.
     * 
     * The files array can be a combination of the following (either data or file):
     * 
     * file => "path/to/file", filename=, mime=, data=
     *
     * @param array params		(name => value) (all names and values should be urlencoded)
     * @param array files		(name => filedesc) (not urlencoded)
     * @return array (headers, body)
     */
    static function encodeBody ( $params, $files )
    {
    	$headers  	= array();
		$body		= '';
		$boundary	= 'OAuthRequester_'.md5(uniqid('multipart') . microtime());
		$headers['Content-Type'] = 'multipart/form-data; boundary=' . $boundary;


		// 1. Add the parameters to the post
		if (!empty($params))
		{
			foreach ($params as $name => $value)
			{
				$body .= '--'.$boundary."\r\n";
				$body .= 'Content-Disposition: form-data; name="'.OAuthBodyMultipartFormdata::encodeParameterName(rawurldecode($name)).'"';
				$body .= "\r\n\r\n";
				$body .= urldecode($value);
				$body .= "\r\n";
			}
		}
		
		// 2. Add all the files to the post
		if (!empty($files))
		{
			$untitled = 1;
			
			foreach ($files as $name => $f)
			{
				$data     = false;
				$filename = false;

				if (isset($f['filename']))
				{
					$filename = $f['filename'];
				}

				if (!empty($f['file']))
				{
					$data = @file_get_contents($f['file']);
					if ($data === false)
					{
						throw new OAuthException2(sprintf('Could not read the file "%s" for form-data part', $f['file']));
					}
					if (empty($filename))
					{
						$filename = basename($f['file']);
					}
				}
				else if (isset($f['data']))
				{
					$data = $f['data'];
				}
				
				// When there is data, add it as a form-data part, otherwise silently skip the upload
				if ($data !== false)
				{
					if (empty($filename))
					{
						$filename = sprintf('untitled-%d', $untitled++);
					}
					$mime  = !empty($f['mime']) ? $f['mime'] : 'application/octet-stream';
					$body .= '--'.$boundary."\r\n";
					$body .= 'Content-Disposition: form-data; name="'.OAuthBodyMultipartFormdata::encodeParameterName($name).'"; filename="'.OAuthBodyMultipartFormdata::encodeParameterName($filename).'"'."\r\n";
					$body .= 'Content-Type: '.$mime;
					$body .= "\r\n\r\n";
					$body .= $data;
					$body .= "\r\n";
				}
				
			}
		}
		$body .= '--'.$boundary."--\r\n";

		$headers['Content-Length'] = strlen($body);
		return array($headers, $body);
	}
	
	
	/**
	 * Encode a parameter's name for use in a multipart header.
	 * For now we do a simple filter that removes some unwanted characters.
	 * We might want to implement RFC1522 here.  See http://tools.ietf.org/html/rfc1522
	 * 
	 * @param string name
	 * @return string
	 */
	static function encodeParameterName ( $name )
	{
		return preg_replace('/[^\x20-\x7f]|"/', '-', $name);
	}
}


/* vi:set ts=4 sts=4 sw=4 binary noeol: */


?>