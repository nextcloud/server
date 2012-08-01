<?php

/**
 * Storage container for the oauth credentials, both server and consumer side.
 * This is the factory to select the store you want to use
 * 
 * @version $Id: OAuthSession.php 67 2010-01-12 18:42:04Z brunobg@corollarium.com $
 * @author brunobg@corollarium.com
 * 
 * 
 * The MIT License
 * 
 * Copyright (c) 2007-2008 Mediamatic Lab
 * Copyright (c) 2010 Corollarium Technologies
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

require_once dirname(__FILE__) . '/OAuthException2.php';

class OAuthSession
{
	static private $instance = false;

	/**
	 * Request an instance of the OAuthSession
	 */
	public static function instance ( $store = 'SESSION', $options = array() )
	{
	    if (!OAuthSession::$instance)
	    {
			// Select the store you want to use
			if (strpos($store, '/') === false)
			{
				$class = 'OAuthSession'.$store;
				$file  = dirname(__FILE__) . '/session/'.$class.'.php';
			}
			else
			{
				$file  = $store;
				$store = basename($file, '.php');
				$class = $store;
			}

			if (is_file($file))
			{
				require_once $file;
				
				if (class_exists($class))
				{
					OAuthSession::$instance = new $class($options);
				}
				else
				{
					throw new OAuthException2('Could not find class '.$class.' in file '.$file);
				}
			}
			else
			{
				throw new OAuthException2('No OAuthSession for '.$store.' (file '.$file.')');
			}
	    }
	    return OAuthSession::$instance;	
	}
}


/* vi:set ts=4 sts=4 sw=4 binary noeol: */

?>