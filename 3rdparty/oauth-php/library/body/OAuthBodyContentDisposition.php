<?php

/**
 * Add the extra headers for a PUT or POST request with a file.
 * 
 * @version $Id$
 * @author Marc Worrell <marcw@pobox.com>
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

class OAuthBodyContentDisposition
{
    /**
     * Builds the request string.
     * 
     * The files array can be a combination of the following (either data or file):
     * 
     * file => "path/to/file", filename=, mime=, data=
     *
     * @param array files		(name => filedesc) (not urlencoded)
     * @return array (headers, body)
     */
    static function encodeBody ( $files )
    {
    	$headers  	= array();
		$body		= null;

		// 1. Add all the files to the post
		if (!empty($files))
		{
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
						throw new OAuthException2(sprintf('Could not read the file "%s" for request body', $f['file']));
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
				
				// When there is data, add it as a request body, otherwise silently skip the upload
				if ($data !== false)
				{
					if (isset($headers['Content-Disposition']))
					{
						throw new OAuthException2('Only a single file (or data) allowed in a signed PUT/POST request body.');
					}

					if (empty($filename))
					{
						$filename = 'untitled';
					}
					$mime  = !empty($f['mime']) ? $f['mime'] : 'application/octet-stream';
					
					$headers['Content-Disposition'] = 'attachment; filename="'.OAuthBodyContentDisposition::encodeParameterName($filename).'"';
					$headers['Content-Type']		= $mime;

					$body = $data;
				}
				
			}

			// When we have a body, add the content-length
			if (!is_null($body))
			{
				$headers['Content-Length'] = strlen($body);
			}
		}
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