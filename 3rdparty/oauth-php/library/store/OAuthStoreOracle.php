<?php

/**
 * Added by Vinay Kant Sahu.
 * Replaced all the MySQL queries with Oracle SPs. (ref: OAuthStoreSQL.php)
 * vinaykant.sahu@gmail.com
 *
 * Storage container for the oauth credentials, both server and consumer side.
 * Based on Oracle
 *
 * @author Vinay Kant Sahu <vinaykant.sahu@gmail.com>
 * @date  Aug 6, 2010
 * 
 * The MIT License
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

require_once dirname(__FILE__) . '/OAuthStoreAbstract.class.php';

abstract class OAuthStoreOracle extends OAuthStoreAbstract {
    /**
     * Maximum delta a timestamp may be off from a previous timestamp.
     * Allows multiple consumers with some clock skew to work with the same token.
     * Unit is seconds, default max skew is 10 minutes.
     */
    protected $max_timestamp_skew = MAX_TIMESTAMP_SKEW;

    /**
     * Default ttl for request tokens
     */
    protected $max_request_token_ttl = MAX_REQUEST_TOKEN_TIME;


    /**
     * Construct the OAuthStoreMySQL.
     * In the options you have to supply either:
     * - server, username, password and database (for a mysql_connect)
     * - conn (for the connection to be used)
     *
     * @param array options
     */
    function __construct ( $options = array() ) {
        if (isset($options['conn'])) {
            $this->conn = $options['conn'];
        }
        else {
            $this->conn=oci_connect(DBUSER,DBPASSWORD,DBHOST);

            if ($this->conn === false) {
                throw new OAuthException2('Could not connect to database');
            }

            // $this->query('set character set utf8');
        }
    }

	/**
	 * Find stored credentials for the consumer key and token. Used by an OAuth server
	 * when verifying an OAuth request.
	 * 
	 * @param string consumer_key
	 * @param string token
	 * @param string token_type		false, 'request' or 'access'
	 * @exception OAuthException2 when no secrets where found
	 * @return array	assoc (consumer_secret, token_secret, osr_id, ost_id, user_id)
	 */
	public function getSecretsForVerify ($consumer_key, $token, $token_type = 'access' ) {
            $sql = "BEGIN SP_GET_SECRETS_FOR_VERIFY(:P_CONSUMER_KEY, :P_TOKEN, :P_TOKEN_TYPE, :P_ROWS, :P_RESULT); END;";

            // parse sql
            $stmt = oci_parse($this->conn, $sql) or die ('Can not parse query');
            
            // Bind In and Out Variables
            oci_bind_by_name($stmt, ':P_CONSUMER_KEY', $consumer_key, 255);
            oci_bind_by_name($stmt, ':P_TOKEN', $token, 255);
            oci_bind_by_name($stmt, ':P_TOKEN_TYPE', $token_type, 255);
            oci_bind_by_name($stmt, ':P_RESULT', $result, 20);

            //Bind the ref cursor
            $p_row = oci_new_cursor($this->conn);
            oci_bind_by_name($stmt, ':P_ROWS', $p_row, -1, OCI_B_CURSOR);

            //Execute the statement
            oci_execute($stmt);

            // treat the ref cursor as a statement resource
            oci_execute($p_row, OCI_DEFAULT);
            oci_fetch_all($p_row, $getSecretsForVerifyList, null, null, OCI_FETCHSTATEMENT_BY_ROW);

            $rs =$getSecretsForVerifyList;
            if (empty($rs)) {
                throw new OAuthException2('The consumer_key "'.$consumer_key.'" token "'.$token.'" combination does not exist or is not enabled.');
            }
 
            return $rs[0];
        }


	/**
	 * Find the server details for signing a request, always looks for an access token.
	 * The returned credentials depend on which local user is making the request.
	 * 
	 * The consumer_key must belong to the user or be public (user id is null)
	 * 
	 * For signing we need all of the following:
	 * 
	 * consumer_key			consumer key associated with the server
	 * consumer_secret		consumer secret associated with this server
	 * token				access token associated with this server
	 * token_secret			secret for the access token
	 * signature_methods	signing methods supported by the server (array)
	 * 
	 * @todo filter on token type (we should know how and with what to sign this request, and there might be old access tokens)
	 * @param string uri	uri of the server
	 * @param int user_id	id of the logged on user
	 * @param string name	(optional) name of the token (case sensitive)
	 * @exception OAuthException2 when no credentials found
	 * @return array
	 */
	public function getSecretsForSignature ( $uri, $user_id, $name = '' ) {
            // Find a consumer key and token for the given uri
            $ps     = parse_url($uri);
            $host   = isset($ps['host']) ? $ps['host'] : 'localhost';
            $path   = isset($ps['path']) ? $ps['path'] : '';

            if (empty($path) || substr($path, -1) != '/') {
                $path .= '/';
            }
            //
            $sql = "BEGIN SP_GET_SECRETS_FOR_SIGNATURE(:P_HOST, :P_PATH, :P_USER_ID, :P_NAME, :P_ROWS, :P_RESULT); END;";

            // parse sql
            $stmt = oci_parse($this->conn, $sql) or die ('Can not parse query');

            // Bind In and Out Variables
            oci_bind_by_name($stmt, ':P_HOST', $host, 255);
            oci_bind_by_name($stmt, ':P_PATH', $path, 255);
            oci_bind_by_name($stmt, ':P_USER_ID', $user_id, 20);
            oci_bind_by_name($stmt, ':P_NAME', $name, 255);
            oci_bind_by_name($stmt, ':P_RESULT', $result, 20);

            //Bind the ref cursor
            $p_row = oci_new_cursor($this->conn);
            oci_bind_by_name($stmt, ':P_ROWS', $p_row, -1, OCI_B_CURSOR);

            //Execute the statement
            oci_execute($stmt);

            // treat the ref cursor as a statement resource
            oci_execute($p_row, OCI_DEFAULT);
            oci_fetch_all($p_row, $getSecretsForSignatureList, null, null, OCI_FETCHSTATEMENT_BY_ROW);
            $secrets  = $getSecretsForSignatureList[0];
            //
            // The owner of the consumer_key is either the user or nobody (public consumer key)
            /*$secrets = $this->query_row_assoc('
					SELECT	ocr_consumer_key		as consumer_key,
							ocr_consumer_secret		as consumer_secret,
							oct_token				as token,
							oct_token_secret		as token_secret,
							ocr_signature_methods	as signature_methods
					FROM oauth_consumer_registry
						JOIN oauth_consumer_token ON oct_ocr_id_ref = ocr_id
					WHERE ocr_server_uri_host = \'%s\'
					  AND ocr_server_uri_path = LEFT(\'%s\', LENGTH(ocr_server_uri_path))
					  AND (ocr_usa_id_ref = %s OR ocr_usa_id_ref IS NULL)
					  AND oct_usa_id_ref	  = %d
					  AND oct_token_type      = \'access\'
					  AND oct_name			  = \'%s\'
					  AND oct_token_ttl       >= NOW()
					ORDER BY ocr_usa_id_ref DESC, ocr_consumer_secret DESC, LENGTH(ocr_server_uri_path) DESC
					LIMIT 0,1
					', $host, $path, $user_id, $user_id, $name
					);
            */
            if (empty($secrets)) {
                throw new OAuthException2('No server tokens available for '.$uri);
            }
            $secrets['signature_methods'] = explode(',', $secrets['signature_methods']);
            return $secrets;
        }


	/**
	 * Get the token and token secret we obtained from a server.
	 * 
	 * @param string	consumer_key
	 * @param string 	token
	 * @param string	token_type
	 * @param int		user_id			the user owning the token
	 * @param string	name			optional name for a named token
	 * @exception OAuthException2 when no credentials found
	 * @return array
	 */
	public function getServerTokenSecrets ($consumer_key,$token,$token_type,$user_id,$name = '')
	{
		if ($token_type != 'request' && $token_type != 'access')
		{
			throw new OAuthException2('Unkown token type "'.$token_type.'", must be either "request" or "access"');
		}
                //
                $sql = "BEGIN SP_GET_SERVER_TOKEN_SECRETS(:P_CONSUMER_KEY, :P_TOKEN, :P_TOKEN_TYPE, :P_USER_ID, :P_ROWS, :P_RESULT); END;";

                // parse sql
                $stmt = oci_parse($this->conn, $sql) or die ('Can not parse query');
                
                // Bind In and Out Variables
                oci_bind_by_name($stmt, ':P_CONSUMER_KEY', $consumer_key, 255);
                oci_bind_by_name($stmt, ':P_TOKEN', $token, 255);
                oci_bind_by_name($stmt, ':P_TOKEN_TYPE', $token_type, 20);
                oci_bind_by_name($stmt, ':P_USER_ID', $user_id, 255);
                oci_bind_by_name($stmt, ':P_RESULT', $result, 20);

                //Bind the ref cursor
                $p_row = oci_new_cursor($this->conn);
                oci_bind_by_name($stmt, ':P_ROWS', $p_row, -1, OCI_B_CURSOR);

                //Execute the statement
                oci_execute($stmt);

                // treat the ref cursor as a statement resource
                oci_execute($p_row, OCI_DEFAULT);
                oci_fetch_all($p_row, $getServerTokenSecretsList, null, null, OCI_FETCHSTATEMENT_BY_ROW);
                $r=$getServerTokenSecretsList[0];
                //
		// Take the most recent token of the given type
		/*$r = $this->query_row_assoc('
					SELECT	ocr_consumer_key		as consumer_key,
							ocr_consumer_secret		as consumer_secret,
							oct_token				as token,
							oct_token_secret		as token_secret,
							oct_name				as token_name,
							ocr_signature_methods	as signature_methods,
							ocr_server_uri			as server_uri,
							ocr_request_token_uri	as request_token_uri,
							ocr_authorize_uri		as authorize_uri,
							ocr_access_token_uri	as access_token_uri,
							IF(oct_token_ttl >= \'9999-12-31\', NULL, UNIX_TIMESTAMP(oct_token_ttl) - UNIX_TIMESTAMP(NOW())) as token_ttl
					FROM oauth_consumer_registry
							JOIN oauth_consumer_token
							ON oct_ocr_id_ref = ocr_id
					WHERE ocr_consumer_key = \'%s\'
					  AND oct_token_type   = \'%s\'
					  AND oct_token        = \'%s\'
					  AND oct_usa_id_ref   = %d
					  AND oct_token_ttl    >= NOW()
					', $consumer_key, $token_type, $token, $user_id
					);*/
					
		if (empty($r))
		{
			throw new OAuthException2('Could not find a "'.$token_type.'" token for consumer "'.$consumer_key.'" and user '.$user_id);
		}
		if (isset($r['signature_methods']) && !empty($r['signature_methods']))
		{
			$r['signature_methods'] = explode(',',$r['signature_methods']);
		}
		else
		{
			$r['signature_methods'] = array();
		}
		return $r;		
	}


	/**
	 * Add a request token we obtained from a server.
	 * 
	 * @todo remove old tokens for this user and this ocr_id
	 * @param string consumer_key	key of the server in the consumer registry
	 * @param string token_type		one of 'request' or 'access'
	 * @param string token
	 * @param string token_secret
	 * @param int 	 user_id			the user owning the token
	 * @param array  options			extra options, name and token_ttl
	 * @exception OAuthException2 when server is not known
	 * @exception OAuthException2 when we received a duplicate token
	 */
	public function addServerToken ( $consumer_key, $token_type, $token, $token_secret, $user_id, $options = array() )
	{
		if ($token_type != 'request' && $token_type != 'access')
		{
			throw new OAuthException2('Unknown token type "'.$token_type.'", must be either "request" or "access"');
		}

		// Maximum time to live for this token
		if (isset($options['token_ttl']) && is_numeric($options['token_ttl']))
		{
			$ttl = intval($options['token_ttl']);
		}
		else if ($token_type == 'request')
		{
			$ttl =intval($this->max_request_token_ttl);
		}
		else
		{
			$ttl = NULL;
		}
		
		
		
		// Named tokens, unique per user/consumer key
		if (isset($options['name']) && $options['name'] != '')
		{
			$name = $options['name'];
		}
		else
		{
			$name = '';
		}
                //
                $sql = "BEGIN SP_ADD_SERVER_TOKEN(:P_CONSUMER_KEY, :P_USER_ID, :P_NAME, :P_TOKEN_TYPE, :P_TOKEN, :P_TOKEN_SECRET, :P_TOKEN_INTERVAL_IN_SEC, :P_RESULT); END;";

                // parse sql
                $stmt = oci_parse($this->conn, $sql) or die ('Can not parse query');

                // Bind In and Out Variables
                oci_bind_by_name($stmt, ':P_CONSUMER_KEY', $consumer_key, 255);
                oci_bind_by_name($stmt, ':P_USER_ID', $user_id, 40);
                oci_bind_by_name($stmt, ':P_NAME', $name, 255);
                oci_bind_by_name($stmt, ':P_TOKEN_TYPE', $token_type, 20);
                oci_bind_by_name($stmt, ':P_TOKEN', $token, 255);
                oci_bind_by_name($stmt, ':P_TOKEN_SECRET', $token_secret, 255);
                oci_bind_by_name($stmt, ':P_TOKEN_INTERVAL_IN_SEC', $ttl, 40);
                oci_bind_by_name($stmt, ':P_RESULT', $result, 20);

                //Execute the statement
                oci_execute($stmt);
                //

              
		
		if (!$result)
		{
			throw new OAuthException2('Received duplicate token "'.$token.'" for the same consumer_key "'.$consumer_key.'"');
		}
	}


	/**
	 * Delete a server key.  This removes access to that site.
	 * 
	 * @param string consumer_key
	 * @param int user_id	user registering this server
	 * @param boolean user_is_admin
	 */
	public function deleteServer ( $consumer_key, $user_id, $user_is_admin = false )
	{
		
            $sql = "BEGIN SP_DELETE_SERVER(:P_CONSUMER_KEY, :P_USER_ID, :P_USER_IS_ADMIN, :P_RESULT); END;";

                // parse sql
                $stmt = oci_parse($this->conn, $sql) or die ('Can not parse query');

                // Bind In and Out Variables
                oci_bind_by_name($stmt, ':P_CONSUMER_KEY', $consumer_key, 255);
                oci_bind_by_name($stmt, ':P_USER_ID', $user_id, 40);
                oci_bind_by_name($stmt, ':P_USER_IS_ADMIN', $user_is_admin, 255);
                oci_bind_by_name($stmt, ':P_RESULT', $result, 20);

                //Execute the statement
                oci_execute($stmt);
	}
	
	
	/**
	 * Get a server from the consumer registry using the consumer key
	 * 
	 * @param string consumer_key
	 * @param int user_id
	 * @param boolean user_is_admin (optional)
	 * @exception OAuthException2 when server is not found
	 * @return array
	 */	
	public function getServer ( $consumer_key, $user_id, $user_is_admin = false )
	{
		
                //
                $sql = "BEGIN SP_GET_SERVER(:P_CONSUMER_KEY, :P_USER_ID, :P_ROWS, :P_RESULT); END;";

                // parse sql
                $stmt = oci_parse($this->conn, $sql) or die ('Can not parse query');

                // Bind In and Out Variables
                oci_bind_by_name($stmt, ':P_CONSUMER_KEY', $consumer_key, 255);                
                oci_bind_by_name($stmt, ':P_USER_ID', $user_id, 40);
                oci_bind_by_name($stmt, ':P_RESULT', $result, 20);

                //Bind the ref cursor
                $p_row = oci_new_cursor($this->conn);
                oci_bind_by_name($stmt, ':P_ROWS', $p_row, -1, OCI_B_CURSOR);

                //Execute the statement
                oci_execute($stmt);

                // treat the ref cursor as a statement resource
                oci_execute($p_row, OCI_DEFAULT);
                oci_fetch_all($p_row, $getServerList, null, null, OCI_FETCHSTATEMENT_BY_ROW);
                $r = $getServerList;
                //
		if (empty($r))
		{
			throw new OAuthException2('No server with consumer_key "'.$consumer_key.'" has been registered (for this user)');
		}
			
		if (isset($r['signature_methods']) && !empty($r['signature_methods']))
		{
			$r['signature_methods'] = explode(',',$r['signature_methods']);
		}
		else
		{
			$r['signature_methods'] = array();
		}
		return $r;
	}



	/**
	 * Find the server details that might be used for a request
	 * 
	 * The consumer_key must belong to the user or be public (user id is null)
	 * 
	 * @param string uri	uri of the server
	 * @param int user_id	id of the logged on user
	 * @exception OAuthException2 when no credentials found
	 * @return array
	 */
	public function getServerForUri ( $uri, $user_id )
	{
		// Find a consumer key and token for the given uri
		$ps	= parse_url($uri);
		$host	= isset($ps['host']) ? $ps['host'] : 'localhost';
		$path	= isset($ps['path']) ? $ps['path'] : '';
		
		if (empty($path) || substr($path, -1) != '/')
		{
			$path .= '/';
		}

		
                //
                $sql = "BEGIN SP_GET_SERVER_FOR_URI(:P_HOST, :P_PATH,:P_USER_ID, :P_ROWS, :P_RESULT); END;";

                // parse sql
                $stmt = oci_parse($this->conn, $sql) or die ('Can not parse query');

                // Bind In and Out Variables
                oci_bind_by_name($stmt, ':P_HOST', $host, 255);
                oci_bind_by_name($stmt, ':P_PATH', $path, 255);
                oci_bind_by_name($stmt, ':P_USER_ID', $user_id, 40);
                oci_bind_by_name($stmt, ':P_RESULT', $result, 20);

                //Bind the ref cursor
                $p_row = oci_new_cursor($this->conn);
                oci_bind_by_name($stmt, ':P_ROWS', $p_row, -1, OCI_B_CURSOR);

                //Execute the statement
                oci_execute($stmt);

                // treat the ref cursor as a statement resource
                oci_execute($p_row, OCI_DEFAULT);
                oci_fetch_all($p_row, $getServerForUriList, null, null, OCI_FETCHSTATEMENT_BY_ROW);
                $server = $getServerForUriList;
                //		
		if (empty($server))
		{
			throw new OAuthException2('No server available for '.$uri);
		}
		$server['signature_methods'] = explode(',', $server['signature_methods']);
		return $server;
	}


	/**
	 * Get a list of all server token this user has access to.
	 * 
	 * @param int usr_id
	 * @return array
	 */
	public function listServerTokens ( $user_id )
	{
		
                $sql = "BEGIN SP_LIST_SERVER_TOKENS(:P_USER_ID, :P_ROWS, :P_RESULT); END;";

                // parse sql
                $stmt = oci_parse($this->conn, $sql) or die ('Can not parse query');

                // Bind In and Out Variables                
                oci_bind_by_name($stmt, ':P_USER_ID', $user_id, 40);
                oci_bind_by_name($stmt, ':P_RESULT', $result, 20);

                //Bind the ref cursor
                $p_row = oci_new_cursor($this->conn);
                oci_bind_by_name($stmt, ':P_ROWS', $p_row, -1, OCI_B_CURSOR);

                //Execute the statement
                oci_execute($stmt);

                // treat the ref cursor as a statement resource
                oci_execute($p_row, OCI_DEFAULT);
                oci_fetch_all($p_row, $listServerTokensList, null, null, OCI_FETCHSTATEMENT_BY_ROW);
                $ts = $listServerTokensList;
		return $ts;
	}


	/**
	 * Count how many tokens we have for the given server
	 * 
	 * @param string consumer_key
	 * @return int
	 */
	public function countServerTokens ( $consumer_key )
	{
		
                //
                $count =0;
                $sql = "BEGIN SP_COUNT_SERVICE_TOKENS(:P_CONSUMER_KEY, :P_COUNT, :P_RESULT); END;";

                // parse sql
                $stmt = oci_parse($this->conn, $sql) or die ('Can not parse query');

                // Bind In and Out Variables
                oci_bind_by_name($stmt, ':P_CONSUMER_KEY', $consumer_key, 255);
                oci_bind_by_name($stmt, ':P_COUNT', $count, 40);
                oci_bind_by_name($stmt, ':P_RESULT', $result, 20);
                
                //Execute the statement
                oci_execute($stmt);                
                //
		return $count;
	}


	/**
	 * Get a specific server token for the given user
	 * 
	 * @param string consumer_key
	 * @param string token
	 * @param int user_id
	 * @exception OAuthException2 when no such token found
	 * @return array
	 */
	public function getServerToken ( $consumer_key, $token, $user_id )
	{
		
                $sql = "BEGIN SP_GET_SERVER_TOKEN(:P_CONSUMER_KEY, :P_USER_ID,:P_TOKEN, :P_ROWS, :P_RESULT); END;";

                // parse sql
                $stmt = oci_parse($this->conn, $sql) or die ('Can not parse query');

                // Bind In and Out Variables
                oci_bind_by_name($stmt, ':P_CONSUMER_KEY', $consumer_key, 255);
                oci_bind_by_name($stmt, ':P_USER_ID', $user_id, 40);
                oci_bind_by_name($stmt, ':P_TOKEN', $token, 255);
                oci_bind_by_name($stmt, ':P_RESULT', $result, 20);

                //Bind the ref cursor
                $p_row = oci_new_cursor($this->conn);
                oci_bind_by_name($stmt, ':P_ROWS', $p_row, -1, OCI_B_CURSOR);

                //Execute the statement
                oci_execute($stmt);

                // treat the ref cursor as a statement resource
                oci_execute($p_row, OCI_DEFAULT);
                oci_fetch_all($p_row, $getServerTokenList, null, null, OCI_FETCHSTATEMENT_BY_ROW);
                $ts = $getServerTokenList;
                //
		
		if (empty($ts))
		{
			throw new OAuthException2('No such consumer key ('.$consumer_key.') and token ('.$token.') combination for user "'.$user_id.'"');
		}
		return $ts;
	}


	/**
	 * Delete a token we obtained from a server.
	 * 
	 * @param string consumer_key
	 * @param string token
	 * @param int user_id
	 * @param boolean user_is_admin
	 */
	public function deleteServerToken ( $consumer_key, $token, $user_id, $user_is_admin = false )
	{
		
                //
                $sql = "BEGIN SP_DELETE_SERVER_TOKEN(:P_CONSUMER_KEY, :P_USER_ID,:P_TOKEN, :P_USER_IS_ADMIN, :P_RESULT); END;";

                // parse sql
                $stmt = oci_parse($this->conn, $sql) or die ('Can not parse query');

                // Bind In and Out Variables
                oci_bind_by_name($stmt, ':P_CONSUMER_KEY', $consumer_key, 255);
                oci_bind_by_name($stmt, ':P_USER_ID', $user_id, 40);
                oci_bind_by_name($stmt, ':P_TOKEN', $token, 255);
                oci_bind_by_name($stmt, ':P_USER_IS_ADMIN', $user_is_admin, 40);
                oci_bind_by_name($stmt, ':P_RESULT', $result, 20);                

                //Execute the statement
                oci_execute($stmt);
                //

        }


	/**
	 * Set the ttl of a server access token.  This is done when the
	 * server receives a valid request with a xoauth_token_ttl parameter in it.
	 * 
	 * @param string consumer_key
	 * @param string token
	 * @param int token_ttl
	 */
	public function setServerTokenTtl ( $consumer_key, $token, $token_ttl )
	{
		if ($token_ttl <= 0)
		{
			// Immediate delete when the token is past its ttl
			$this->deleteServerToken($consumer_key, $token, 0, true);
		}
		else
		{
			// Set maximum time to live for this token
			
                         //
                         $sql = "BEGIN SP_SET_SERVER_TOKEN_TTL(:P_TOKEN_TTL, :P_CONSUMER_KEY, :P_TOKEN, :P_RESULT); END;";

                         // parse sql
                         $stmt = oci_parse($this->conn, $sql) or die ('Can not parse query');

                         // Bind In and Out Variables
                         oci_bind_by_name($stmt, ':P_TOKEN_TTL', $token_ttl, 40);
                         oci_bind_by_name($stmt, ':P_CONSUMER_KEY', $consumer_key, 255);
                         oci_bind_by_name($stmt, ':P_TOKEN', $token, 255);
                         oci_bind_by_name($stmt, ':P_RESULT', $result, 20);                         

                         //Execute the statement
                         oci_execute($stmt);
                         //                    
		}
	}


	/**
	 * Get a list of all consumers from the consumer registry.
	 * The consumer keys belong to the user or are public (user id is null)
	 * 
	 * @param string q	query term
	 * @param int user_id
	 * @return array
	 */	
	public function listServers ( $q = '', $user_id )
	{
		$q    = trim(str_replace('%', '', $q));
		$args = array();

		
                //
                $sql = "BEGIN SP_LIST_SERVERS(:P_Q, :P_USER_ID, :P_ROWS, :P_RESULT); END;";

                // parse sql
                $stmt = oci_parse($this->conn, $sql) or die ('Can not parse query');

                // Bind In and Out Variables
                oci_bind_by_name($stmt, ':P_Q', $q, 255);
                oci_bind_by_name($stmt, ':P_USER_ID', $user_id, 40);
                oci_bind_by_name($stmt, ':P_RESULT', $result, 20);

                //Bind the ref cursor
                $p_row = oci_new_cursor($this->conn);
                oci_bind_by_name($stmt, ':P_ROWS', $p_row, -1, OCI_B_CURSOR);

                //Execute the statement
                oci_execute($stmt);

                // treat the ref cursor as a statement resource
                oci_execute($p_row, OCI_DEFAULT);
                oci_fetch_all($p_row, $listServersList, null, null, OCI_FETCHSTATEMENT_BY_ROW);
                $servers = $listServersList;
                //

		return $servers;
	}


	/**
	 * Register or update a server for our site (we will be the consumer)
	 * 
	 * (This is the registry at the consumers, registering servers ;-) )
	 * 
	 * @param array server
	 * @param int user_id	user registering this server
	 * @param boolean user_is_admin
	 * @exception OAuthException2 when fields are missing or on duplicate consumer_key
	 * @return consumer_key
	 */
        public function updateServer ( $server, $user_id, $user_is_admin = false ) {
            foreach (array('consumer_key', 'server_uri') as $f) {
                if (empty($server[$f])) {
                    throw new OAuthException2('The field "'.$f.'" must be set and non empty');
                }
            }
            $parts = parse_url($server['server_uri']);
            $host  = (isset($parts['host']) ? $parts['host'] : 'localhost');
            $path  = (isset($parts['path']) ? $parts['path'] : '/');

            if (isset($server['signature_methods'])) {
                if (is_array($server['signature_methods'])) {
                    $server['signature_methods'] = strtoupper(implode(',', $server['signature_methods']));
                }
            }
            else {
                $server['signature_methods'] = '';
            }
            // When the user is an admin, then the user can update the user_id of this record
            if ($user_is_admin && array_key_exists('user_id', $server)) {
                $flag=1;
            }
            if($flag) {
                if (is_null($server['user_id'])) {
                    $ocr_usa_id_ref= NULL;
                }
                else {
                    $ocr_usa_id_ref = $server['user_id'];
                }
            }
            else {
                $flag=0;
                $ocr_usa_id_ref=$user_id;
            }
            //sp
            $sql = "BEGIN SP_UPDATE_SERVER(:P_CONSUMER_KEY, :P_USER_ID, :P_OCR_ID, :P_USER_IS_ADMIN,
                 :P_OCR_CONSUMER_SECRET, :P_OCR_SERVER_URI, :P_OCR_SERVER_URI_HOST, :P_OCR_SERVER_URI_PATH,
                 :P_OCR_REQUEST_TOKEN_URI, :P_OCR_AUTHORIZE_URI, :P_OCR_ACCESS_TOKEN_URI, :P_OCR_SIGNATURE_METHODS,
                 :P_OCR_USA_ID_REF, :P_UPDATE_P_OCR_USA_ID_REF_FLAG, :P_RESULT); END;";

            // parse sql
            $stmt = oci_parse($this->conn, $sql) or die ('Can not parse query');
            $server['request_token_uri'] = isset($server['request_token_uri']) ? $server['request_token_uri'] : '';
            $server['authorize_uri'] = isset($server['authorize_uri'])     ? $server['authorize_uri']     : '';
            $server['access_token_uri'] = isset($server['access_token_uri'])  ? $server['access_token_uri']  : '';
            // Bind In and Out Variables
            oci_bind_by_name($stmt, ':P_CONSUMER_KEY', $server['consumer_key'], 255);
            oci_bind_by_name($stmt, ':P_USER_ID', $user_id, 40);
            oci_bind_by_name($stmt, ':P_OCR_ID', $server['id'], 40);
            oci_bind_by_name($stmt, ':P_USER_IS_ADMIN', $user_is_admin, 40);
            oci_bind_by_name($stmt, ':P_OCR_CONSUMER_SECRET', $server['consumer_secret'], 255);
            oci_bind_by_name($stmt, ':P_OCR_SERVER_URI', $server['server_uri'], 255);
            oci_bind_by_name($stmt, ':P_OCR_SERVER_URI_HOST', strtolower($host), 255);
            oci_bind_by_name($stmt, ':P_OCR_SERVER_URI_PATH', $path, 255);
            oci_bind_by_name($stmt, ':P_OCR_REQUEST_TOKEN_URI', $server['request_token_uri'], 255);
            oci_bind_by_name($stmt, ':P_OCR_AUTHORIZE_URI', $server['authorize_uri'], 255);
            oci_bind_by_name($stmt, ':P_OCR_ACCESS_TOKEN_URI', $server['access_token_uri'], 255);
            oci_bind_by_name($stmt, ':P_OCR_SIGNATURE_METHODS', $server['signature_methods'], 255);
            oci_bind_by_name($stmt, ':P_OCR_USA_ID_REF', $ocr_usa_id_ref, 40);
            oci_bind_by_name($stmt, ':P_UPDATE_P_OCR_USA_ID_REF_FLAG', $flag, 40);
            oci_bind_by_name($stmt, ':P_RESULT', $result, 20);
            
            //Execute the statement
            oci_execute($stmt);

            return $server['consumer_key'];
        }

	/**
	 * Insert/update a new consumer with this server (we will be the server)
	 * When this is a new consumer, then also generate the consumer key and secret.
	 * Never updates the consumer key and secret.
	 * When the id is set, then the key and secret must correspond to the entry
	 * being updated.
	 * 
	 * (This is the registry at the server, registering consumers ;-) )
	 * 
	 * @param array consumer
	 * @param int user_id	user registering this consumer
	 * @param boolean user_is_admin
	 * @return string consumer key
	 */
	public function updateConsumer ( $consumer, $user_id, $user_is_admin = false ) {
            $consumer_key = $this->generateKey(true);
            $consumer_secret = $this->generateKey();
			
 $consumer['callback_uri'] = isset($consumer['callback_uri'])? $consumer['callback_uri']: '';
            $consumer['application_uri'] = isset($consumer['application_uri'])? $consumer['application_uri']: '';
            $consumer['application_title'] = isset($consumer['application_title'])? $consumer['application_title']: '';
            $consumer['application_descr'] = isset($consumer['application_descr'])? $consumer['application_descr']: '';
            $consumer['application_notes'] = isset($consumer['application_notes'])? $consumer['application_notes']: '';
            $consumer['application_type'] = isset($consumer['application_type'])? $consumer['application_type']: '';
            $consumer['application_commercial'] = isset($consumer['application_commercial'])?$consumer['application_commercial']:0;
			
            //sp
            $sql = "BEGIN SP_UPDATE_CONSUMER(:P_OSR_USA_ID_REF, :P_OSR_CONSUMER_KEY, :P_OSR_CONSUMER_SECRET, :P_OSR_REQUESTER_NAME, :P_OSR_REQUESTER_EMAIL, :P_OSR_CALLBACK_URI, :P_OSR_APPLICATION_URI, :P_OSR_APPLICATION_TITLE  , :P_OSR_APPLICATION_DESCR, :P_OSR_APPLICATION_NOTES, :P_OSR_APPLICATION_TYPE, :P_OSR_APPLICATION_COMMERCIAL, :P_RESULT); END;";

            // parse sql
            $stmt = oci_parse($this->conn, $sql) or die ('Can not parse query');
			
           
            // Bind In and Out Variables
            oci_bind_by_name($stmt, ':P_OSR_USA_ID_REF', $user_id, 40);
            oci_bind_by_name($stmt, ':P_OSR_CONSUMER_KEY', $consumer_key, 255);
            oci_bind_by_name($stmt, ':P_OSR_CONSUMER_SECRET', $consumer_secret, 255);
            oci_bind_by_name($stmt, ':P_OSR_REQUESTER_NAME', $consumer['requester_name'], 255);
            oci_bind_by_name($stmt, ':P_OSR_REQUESTER_EMAIL', $consumer['requester_email'], 255);
            oci_bind_by_name($stmt, ':P_OSR_CALLBACK_URI', $consumer['callback_uri'], 255);
            oci_bind_by_name($stmt, ':P_OSR_APPLICATION_URI', $consumer['application_uri'], 255);
            oci_bind_by_name($stmt, ':P_OSR_APPLICATION_TITLE', $consumer['application_title'], 255);
            oci_bind_by_name($stmt, ':P_OSR_APPLICATION_DESCR', $consumer['application_descr'], 255);
            oci_bind_by_name($stmt, ':P_OSR_APPLICATION_NOTES', $consumer['application_notes'], 255);
            oci_bind_by_name($stmt, ':P_OSR_APPLICATION_TYPE', $consumer['application_type'], 255);
            oci_bind_by_name($stmt, ':P_OSR_APPLICATION_COMMERCIAL', $consumer['application_commercial'], 40);
            oci_bind_by_name($stmt, ':P_RESULT', $result, 20);

            //Execute the statement
            oci_execute($stmt);
			echo $result;
            return $consumer_key;
        }



	/**
	 * Delete a consumer key.  This removes access to our site for all applications using this key.
	 * 
	 * @param string consumer_key
	 * @param int user_id	user registering this server
	 * @param boolean user_is_admin
	 */
	public function deleteConsumer ( $consumer_key, $user_id, $user_is_admin = false )
	{
		
                //
                $sql = "BEGIN SP_DELETE_CONSUMER(:P_CONSUMER_KEY, :P_USER_ID, :P_USER_IS_ADMIN, :P_RESULT); END;";

                // parse sql
                $stmt = oci_parse($this->conn, $sql) or die ('Can not parse query');

                // Bind In and Out Variables
                oci_bind_by_name($stmt, ':P_CONSUMER_KEY', $consumer_key, 255);
                oci_bind_by_name($stmt, ':P_USER_ID', $user_id, 40);
                oci_bind_by_name($stmt, ':P_USER_IS_ADMIN', $user_is_admin, 40);
                oci_bind_by_name($stmt, ':P_RESULT', $result, 20);               

                //Execute the statement
                oci_execute($stmt);
                //
        }
	
	
	
	/**
	 * Fetch a consumer of this server, by consumer_key.
	 * 
	 * @param string consumer_key
	 * @param int user_id
	 * @param boolean user_is_admin (optional)
	 * @exception OAuthException2 when consumer not found
	 * @return array
	 */
	public function getConsumer ( $consumer_key, $user_id, $user_is_admin = false ) {
           
            $sql = "BEGIN SP_GET_CONSUMER(:P_CONSUMER_KEY, :P_ROWS, :P_RESULT); END;";

            // parse sql
            $stmt = oci_parse($this->conn, $sql) or die ('Can not parse query');

            // Bind In and Out Variables
            oci_bind_by_name($stmt, ':P_CONSUMER_KEY', $consumer_key, 255);
            oci_bind_by_name($stmt, ':P_RESULT', $result, 20);

            //Bind the ref cursor
            $p_row = oci_new_cursor($this->conn);
            oci_bind_by_name($stmt, ':P_ROWS', $p_row, -1, OCI_B_CURSOR);

            //Execute the statement
            oci_execute($stmt);

            // treat the ref cursor as a statement resource
            oci_execute($p_row, OCI_DEFAULT);
            oci_fetch_all($p_row, $getConsumerList, null, null, OCI_FETCHSTATEMENT_BY_ROW);

            $consumer = $getConsumerList;
           
		    if (!is_array($consumer)) {
                throw new OAuthException2('No consumer with consumer_key "'.$consumer_key.'"');
            }

            $c = array();
            foreach ($consumer as $key => $value) {
                $c[substr($key, 4)] = $value;
            }
            $c['user_id'] = $c['usa_id_ref'];

            if (!$user_is_admin && !empty($c['user_id']) && $c['user_id'] != $user_id) {
                throw new OAuthException2('No access to the consumer information for consumer_key "'.$consumer_key.'"');
            }
            return $c;
        }


	/**
	 * Fetch the static consumer key for this provider.  The user for the static consumer 
	 * key is NULL (no user, shared key).  If the key did not exist then the key is created.
	 * 
	 * @return string
	 */
	public function getConsumerStatic ()
	{
		
		 //
		$sql = "BEGIN SP_GET_CONSUMER_STATIC_SELECT(:P_OSR_CONSUMER_KEY, :P_RESULT); END;";

		// parse sql
		$stmt = oci_parse($this->conn, $sql) or die ('Can not parse query');

		// Bind In and Out Variables
		oci_bind_by_name($stmt, ':P_OSR_CONSUMER_KEY', $consumer, 255);
		oci_bind_by_name($stmt, ':P_RESULT', $result, 20);               

		//Execute the statement
		oci_execute($stmt);

		if (empty($consumer))
		{
			$consumer_key = 'sc-'.$this->generateKey(true);
			
			$sql = "BEGIN SP_CONSUMER_STATIC_SAVE(:P_OSR_CONSUMER_KEY, :P_RESULT); END;";

			// parse sql
			$stmt = oci_parse($this->conn, $sql) or die ('Can not parse query');

			// Bind In and Out Variables
			oci_bind_by_name($stmt, ':P_OSR_CONSUMER_KEY', $consumer_key, 255);
			oci_bind_by_name($stmt, ':P_RESULT', $result, 20);               

			//Execute the statement
			oci_execute($stmt);
			
			
			// Just make sure that if the consumer key is truncated that we get the truncated string
			$consumer = $consumer_key;
		}
		return $consumer;
	}


	/**
	 * Add an unautorized request token to our server.
	 * 
	 * @param string consumer_key
	 * @param array options		(eg. token_ttl)
	 * @return array (token, token_secret)
	 */
	public function addConsumerRequestToken ( $consumer_key, $options = array() )
	{
		$token  = $this->generateKey(true);
		$secret = $this->generateKey();
		 

		if (isset($options['token_ttl']) && is_numeric($options['token_ttl']))
		{
			$ttl = intval($options['token_ttl']);
		}
		else
		{
			$ttl = $this->max_request_token_ttl;
		}

		if (!isset($options['oauth_callback'])) {
	 		// 1.0a Compatibility : store callback url associated with request token
			$options['oauth_callback']='oob';
 		}
		$options_oauth_callback =$options['oauth_callback'];
		$sql = "BEGIN SP_ADD_CONSUMER_REQUEST_TOKEN(:P_TOKEN_TTL, :P_CONSUMER_KEY, :P_TOKEN, :P_TOKEN_SECRET, :P_CALLBACK_URL, :P_RESULT); END;";

			// parse sql
		$stmt = oci_parse($this->conn, $sql) or die ('Can not parse query');
 
		// Bind In and Out Variables
		oci_bind_by_name($stmt, ':P_TOKEN_TTL', $ttl, 20);
		oci_bind_by_name($stmt, ':P_CONSUMER_KEY', $consumer_key, 255);
		oci_bind_by_name($stmt, ':P_TOKEN', $token, 255);
		oci_bind_by_name($stmt, ':P_TOKEN_SECRET', $secret, 255);
		oci_bind_by_name($stmt, ':P_CALLBACK_URL', $options_oauth_callback, 255);
		oci_bind_by_name($stmt, ':P_RESULT', $result, 20);

		//Execute the statement
		oci_execute($stmt);
		

		$returnArray= array('token'=>$token, 'token_secret'=>$secret, 'token_ttl'=>$ttl);
		return $returnArray;
	}
	
	
	/**
	 * Fetch the consumer request token, by request token.
	 * 
	 * @param string token
	 * @return array  token and consumer details
	 */
	public function getConsumerRequestToken ( $token )
	{
		 
		$sql = "BEGIN SP_GET_CONSUMER_REQUEST_TOKEN(:P_TOKEN, :P_ROWS, :P_RESULT); END;";

            // parse sql
            $stmt = oci_parse($this->conn, $sql) or die ('Can not parse query');

            // Bind In and Out Variables
            oci_bind_by_name($stmt, ':P_TOKEN', $token, 255);
            oci_bind_by_name($stmt, ':P_RESULT', $result, 20);

            //Bind the ref cursor
            $p_row = oci_new_cursor($this->conn);
            oci_bind_by_name($stmt, ':P_ROWS', $p_row, -1, OCI_B_CURSOR);

            //Execute the statement
            oci_execute($stmt);

            // treat the ref cursor as a statement resource
            oci_execute($p_row, OCI_DEFAULT);
			  
            oci_fetch_all($p_row, $rs, null, null, OCI_FETCHSTATEMENT_BY_ROW);
 
		return $rs[0];
	}
	

	/**
	 * Delete a consumer token.  The token must be a request or authorized token.
	 * 
	 * @param string token
	 */
	public function deleteConsumerRequestToken ( $token )
	{
		 
		$sql = "BEGIN SP_DEL_CONSUMER_REQUEST_TOKEN(:P_TOKEN, :P_RESULT); END;";

			// parse sql
		$stmt = oci_parse($this->conn, $sql) or die ('Can not parse query');

		// Bind In and Out Variables
		oci_bind_by_name($stmt, ':P_TOKEN', $token, 255);
		oci_bind_by_name($stmt, ':P_RESULT', $result, 20);

		//Execute the statement
		oci_execute($stmt);
	}
	

	/**
	 * Upgrade a request token to be an authorized request token.
	 * 
	 * @param string token
	 * @param int	 user_id  user authorizing the token
	 * @param string referrer_host used to set the referrer host for this token, for user feedback
	 */
	public function authorizeConsumerRequestToken ( $token, $user_id, $referrer_host = '' )
	{
 		// 1.0a Compatibility : create a token verifier
 		$verifier = substr(md5(rand()),0,10);
		
		$sql = "BEGIN SP_AUTH_CONSUMER_REQ_TOKEN(:P_USER_ID, :P_REFERRER_HOST, :P_VERIFIER, :P_TOKEN, :P_RESULT); END;";

			// parse sql
		$stmt = oci_parse($this->conn, $sql) or die ('Can not parse query');

		// Bind In and Out Variables
		oci_bind_by_name($stmt, ':P_USER_ID', $user_id, 255);
		oci_bind_by_name($stmt, ':P_REFERRER_HOST', $referrer_host, 255);
		oci_bind_by_name($stmt, ':P_VERIFIER', $verifier, 255);
		oci_bind_by_name($stmt, ':P_TOKEN', $token, 255);
		oci_bind_by_name($stmt, ':P_RESULT', $result, 20);


		//Execute the statement
		oci_execute($stmt);

		return $verifier;
	}


	/**
	 * Count the consumer access tokens for the given consumer.
	 * 
	 * @param string consumer_key
	 * @return int
	 */
	public function countConsumerAccessTokens ( $consumer_key )
	{
		/*$count = $this->query_one('
					SELECT COUNT(ost_id)
					FROM oauth_server_token
							JOIN oauth_server_registry
							ON ost_osr_id_ref = osr_id
					WHERE ost_token_type   = \'access\'
					  AND osr_consumer_key = \'%s\'
					  AND ost_token_ttl    >= NOW()
					', $consumer_key);
		*/
		$sql = "BEGIN SP_COUNT_CONSUMER_ACCESS_TOKEN(:P_CONSUMER_KEY, :P_COUNT, :P_RESULT); END;";

			// parse sql
		$stmt = oci_parse($this->conn, $sql) or die ('Can not parse query');

		// Bind In and Out Variables
		oci_bind_by_name($stmt, ':P_CONSUMER_KEY', $consumer_key, 255);
		oci_bind_by_name($stmt, ':P_COUNT', $count, 20);
		oci_bind_by_name($stmt, ':P_RESULT', $result, 20);


		//Execute the statement
		oci_execute($stmt);

		return $count;
	}


	/**
	 * Exchange an authorized request token for new access token.
	 * 
	 * @param string token
	 * @param array options		options for the token, token_ttl
	 * @exception OAuthException2 when token could not be exchanged
	 * @return array (token, token_secret)
	 */
	public function exchangeConsumerRequestForAccessToken ( $token, $options = array() )
	{
		$new_token  = $this->generateKey(true);
		$new_secret = $this->generateKey();
		
		$sql = "BEGIN SP_EXCH_CONS_REQ_FOR_ACC_TOKEN(:P_TOKEN_TTL, :P_NEW_TOKEN, :P_TOKEN, :P_TOKEN_SECRET, :P_VERIFIER, :P_OUT_TOKEN_TTL, :P_RESULT); END;";

			// parse sql
		$stmt = oci_parse($this->conn, $sql) or die ('Can not parse query');

		// Bind In and Out Variables
		oci_bind_by_name($stmt, ':P_TOKEN_TTL', $options['token_ttl'], 255);
		oci_bind_by_name($stmt, ':P_NEW_TOKEN', $new_token, 255);
		oci_bind_by_name($stmt, ':P_TOKEN', $token, 255);
		oci_bind_by_name($stmt, ':P_TOKEN_SECRET', $new_secret, 255);
		oci_bind_by_name($stmt, ':P_VERIFIER', $options['verifier'], 255);
		oci_bind_by_name($stmt, ':P_OUT_TOKEN_TTL', $ttl, 255);
		oci_bind_by_name($stmt, ':P_RESULT', $result, 20);


		//Execute the statement
		oci_execute($stmt);

		$ret = array('token' => $new_token, 'token_secret' => $new_secret);
		if (is_numeric($ttl))
		{
			$ret['token_ttl'] = intval($ttl);
		}
		return $ret;
	}


	/**
	 * Fetch the consumer access token, by access token.
	 * 
	 * @param string token
	 * @param int user_id
	 * @exception OAuthException2 when token is not found
	 * @return array  token and consumer details
	 */
	public function getConsumerAccessToken ( $token, $user_id )
	{
		 
		$sql = "BEGIN SP_GET_CONSUMER_ACCESS_TOKEN(:P_USER_ID, :P_TOKEN, :P_ROWS :P_RESULT); END;";

            // parse sql
            $stmt = oci_parse($this->conn, $sql) or die ('Can not parse query');

            // Bind In and Out Variables
            oci_bind_by_name($stmt, ':P_USER_ID',$user_id, 255);
            oci_bind_by_name($stmt, ':P_TOKEN', $token, 255);
			oci_bind_by_name($stmt, ':P_RESULT', $result, 20);

            //Bind the ref cursor
            $p_row = oci_new_cursor($this->conn);
            oci_bind_by_name($stmt, ':P_ROWS', $p_row, -1, OCI_B_CURSOR);

            //Execute the statement
            oci_execute($stmt);

            // treat the ref cursor as a statement resource
            oci_execute($p_row, OCI_DEFAULT);
            oci_fetch_all($p_row, $rs, null, null, OCI_FETCHSTATEMENT_BY_ROW);
		if (empty($rs))
		{
			throw new OAuthException2('No server_token "'.$token.'" for user "'.$user_id.'"');
		}
		return $rs;
	}


	/**
	 * Delete a consumer access token.
	 * 
	 * @param string token
	 * @param int user_id
	 * @param boolean user_is_admin
	 */
	public function deleteConsumerAccessToken ( $token, $user_id, $user_is_admin = false )
	{
		/*if ($user_is_admin)
		{
			$this->query('
						DELETE FROM oauth_server_token
						WHERE ost_token 	 = \'%s\'
						  AND ost_token_type = \'access\'
						', $token);
		}
		else
		{
			$this->query('
						DELETE FROM oauth_server_token
						WHERE ost_token 	 = \'%s\'
						  AND ost_token_type = \'access\'
						  AND ost_usa_id_ref = %d
						', $token, $user_id);
		}*/
		$sql = "BEGIN SP_DEL_CONSUMER_ACCESS_TOKEN(:P_USER_ID, :P_TOKEN, :P_USER_IS_ADMIN, :P_RESULT); END;";

			// parse sql
		$stmt = oci_parse($this->conn, $sql) or die ('Can not parse query');

		// Bind In and Out Variables
		oci_bind_by_name($stmt, ':P_USER_ID', $user_id, 255);
		oci_bind_by_name($stmt, ':P_TOKEN', $token, 255);
		oci_bind_by_name($stmt, ':P_USER_IS_ADMIN', $user_is_admin, 20);
		oci_bind_by_name($stmt, ':P_RESULT', $result, 20);


		//Execute the statement
		oci_execute($stmt);
	}


	/**
	 * Set the ttl of a consumer access token.  This is done when the
	 * server receives a valid request with a xoauth_token_ttl parameter in it.
	 * 
	 * @param string token
	 * @param int ttl
	 */
	public function setConsumerAccessTokenTtl ( $token, $token_ttl )
	{
		if ($token_ttl <= 0)
		{
			// Immediate delete when the token is past its ttl
			$this->deleteConsumerAccessToken($token, 0, true);
		}
		else
		{
			// Set maximum time to live for this token
			 
			
			$sql = "BEGIN SP_SET_CONSUMER_ACC_TOKEN_TTL(:P_TOKEN, :P_TOKEN_TTL, :P_RESULT); END;";

			// parse sql
			$stmt = oci_parse($this->conn, $sql) or die ('Can not parse query');

			// Bind In and Out Variables
			oci_bind_by_name($stmt, ':P_TOKEN', $token, 255);
			oci_bind_by_name($stmt, ':P_TOKEN_TTL', $token_ttl, 20);
			oci_bind_by_name($stmt, ':P_RESULT', $result, 20);


			//Execute the statement
			oci_execute($stmt);
		}
	}


	/**
	 * Fetch a list of all consumer keys, secrets etc.
	 * Returns the public (user_id is null) and the keys owned by the user
	 * 
	 * @param int user_id
	 * @return array
	 */
	public function listConsumers ( $user_id )
	{

		$sql = "BEGIN SP_LIST_CONSUMERS(:P_USER_ID, :P_ROWS, :P_RESULT); END;";

		// parse sql
		$stmt = oci_parse($this->conn, $sql) or die ('Can not parse query');

		// Bind In and Out Variables
		oci_bind_by_name($stmt, ':P_USER_ID', $user_id, 255);
		oci_bind_by_name($stmt, ':P_RESULT', $result, 20);

		//Bind the ref cursor
		$p_row = oci_new_cursor($this->conn);
		oci_bind_by_name($stmt, ':P_ROWS', $p_row, -1, OCI_B_CURSOR);

		//Execute the statement
		oci_execute($stmt);

		// treat the ref cursor as a statement resource
		oci_execute($p_row, OCI_DEFAULT);
		oci_fetch_all($p_row, $rs, null, null, OCI_FETCHSTATEMENT_BY_ROW);

		return $rs;
	}

	/**
	 * List of all registered applications. Data returned has not sensitive 
	 * information and therefore is suitable for public displaying.
	 * 
	 * @param int $begin
	 * @param int $total
	 * @return array
	 */
	public function listConsumerApplications($begin = 0, $total = 25) 
	{
		// TODO
		return array();
	}

	/**
	 * Fetch a list of all consumer tokens accessing the account of the given user.
	 * 
	 * @param int user_id
	 * @return array
	 */
	public function listConsumerTokens ( $user_id )
	{
	 
		$sql = "BEGIN SP_LIST_CONSUMER_TOKENS(:P_USER_ID, :P_ROWS, :P_RESULT); END;";

		// parse sql
		$stmt = oci_parse($this->conn, $sql) or die ('Can not parse query');

		// Bind In and Out Variables
		oci_bind_by_name($stmt, ':P_USER_ID', $user_id, 255);
		oci_bind_by_name($stmt, ':P_RESULT', $result, 20);

		//Bind the ref cursor
		$p_row = oci_new_cursor($this->conn);
		oci_bind_by_name($stmt, ':P_ROWS', $p_row, -1, OCI_B_CURSOR);

		//Execute the statement
		oci_execute($stmt);

		// treat the ref cursor as a statement resource
		oci_execute($p_row, OCI_DEFAULT);
		oci_fetch_all($p_row, $rs, null, null, OCI_FETCHSTATEMENT_BY_ROW);

		return $rs;
	}


	/**
	 * Check an nonce/timestamp combination.  Clears any nonce combinations
	 * that are older than the one received.
	 * 
	 * @param string	consumer_key
	 * @param string 	token
	 * @param int		timestamp
	 * @param string 	nonce
	 * @exception OAuthException2	thrown when the timestamp is not in sequence or nonce is not unique
	 */
	public function checkServerNonce ( $consumer_key, $token, $timestamp, $nonce )
	{
		
		 $sql = "BEGIN SP_CHECK_SERVER_NONCE(:P_CONSUMER_KEY, :P_TOKEN, :P_TIMESTAMP, :P_MAX_TIMESTAMP_SKEW, :P_NONCE, :P_RESULT); END;";

			// parse sql
		$stmt = oci_parse($this->conn, $sql) or die ('Can not parse query');

		// Bind In and Out Variables
		oci_bind_by_name($stmt, ':P_CONSUMER_KEY', $consumer_key, 255);
		oci_bind_by_name($stmt, ':P_TOKEN', $token, 255);
		oci_bind_by_name($stmt, ':P_TIMESTAMP', $timestamp, 255);
		oci_bind_by_name($stmt, ':P_MAX_TIMESTAMP_SKEW', $this->max_timestamp_skew, 20);
		oci_bind_by_name($stmt, ':P_NONCE', $nonce, 255);
		oci_bind_by_name($stmt, ':P_RESULT', $result, 20);


		//Execute the statement
		oci_execute($stmt);
		
	}


	/**
	 * Add an entry to the log table
	 * 
	 * @param array keys (osr_consumer_key, ost_token, ocr_consumer_key, oct_token)
	 * @param string received
	 * @param string sent
	 * @param string base_string
	 * @param string notes
	 * @param int (optional) user_id
	 */
	public function addLog ( $keys, $received, $sent, $base_string, $notes, $user_id = null )
	{
		$args = array();
		$ps   = array();
		foreach ($keys as $key => $value)
		{
			$args[] = $value;
			$ps[]   = "olg_$key = '%s'";
		}

		if (!empty($_SERVER['REMOTE_ADDR']))
		{
			$remote_ip = $_SERVER['REMOTE_ADDR'];
		}	
		else if (!empty($_SERVER['REMOTE_IP']))
		{
			$remote_ip = $_SERVER['REMOTE_IP'];
		}
		else
		{
			$remote_ip = '0.0.0.0';
		}

		// Build the SQL
		$olg_received = $this->makeUTF8($received);
		$olg_sent = $this->makeUTF8($sent);
		$olg_base_string = $base_string;
		$olg_notes = $this->makeUTF8($notes);
		$olg_usa_id_ref = $user_id;
		$olg_remote_ip = $remote_ip;

		 

		$sql = "BEGIN SP_ADD_LOG(:P_RECEIVED, :P_SENT, :P_BASE_STRING, :P_NOTES, :P_USA_ID_REF, :P_REMOTE_IP, :P_RESULT); END;";

			// parse sql
		$stmt = oci_parse($this->conn, $sql) or die ('Can not parse query');

		// Bind In and Out Variables
		oci_bind_by_name($stmt, ':P_RECEIVED', $olg_received, 255);
		oci_bind_by_name($stmt, ':P_SENT', $olg_sent, 255);
		oci_bind_by_name($stmt, ':P_BASE_STRING', $olg_base_string, 255);
		oci_bind_by_name($stmt, ':P_NOTES', $olg_notes, 255);
		oci_bind_by_name($stmt, ':P_USA_ID_REF', $olg_usa_id_ref, 255);
		oci_bind_by_name($stmt, ':P_REMOTE_IP', $olg_remote_ip, 255);
		oci_bind_by_name($stmt, ':P_RESULT', $result, 20);


		//Execute the statement
		oci_execute($stmt);
	}
	
	
	/**
	 * Get a page of entries from the log.  Returns the last 100 records
	 * matching the options given.
	 * 
	 * @param array options
	 * @param int user_id	current user
	 * @return array log records
	 */
	public function listLog ( $options, $user_id )
	{
		
		 if (empty($options))
		{
			$optionsFlag=NULL;
			
		}
		else
		{
			$optionsFlag=1;
			
		}
	
		$sql = "BEGIN SP_LIST_LOG(:P_OPTION_FLAG, :P_USA_ID, :P_OSR_CONSUMER_KEY, :P_OCR_CONSUMER_KEY, :P_OST_TOKEN, :P_OCT_TOKEN, :P_ROWS, :P_RESULT); END;";

            // parse sql
            $stmt = oci_parse($this->conn, $sql) or die ('Can not parse query');

            // Bind In and Out Variables
            oci_bind_by_name($stmt, ':P_OPTION_FLAG', $optionsFlag, 255);
            oci_bind_by_name($stmt, ':P_USA_ID', $user_id, 40);
			oci_bind_by_name($stmt, ':P_OSR_CONSUMER_KEY', $options['osr_consumer_key'], 255);
            oci_bind_by_name($stmt, ':P_OCR_CONSUMER_KEY', $options['ocr_consumer_key'], 255);
			oci_bind_by_name($stmt, ':P_OST_TOKEN', $options['ost_token'], 255);
            oci_bind_by_name($stmt, ':P_OCT_TOKEN', $options['oct_token'], 255);
            oci_bind_by_name($stmt, ':P_RESULT', $result, 20);

            //Bind the ref cursor
            $p_row = oci_new_cursor($this->conn);
            oci_bind_by_name($stmt, ':P_ROWS', $p_row, -1, OCI_B_CURSOR);

            //Execute the statement
            oci_execute($stmt);

            // treat the ref cursor as a statement resource
            oci_execute($p_row, OCI_DEFAULT);
            oci_fetch_all($p_row, $rs, null, null, OCI_FETCHSTATEMENT_BY_ROW);

		return $rs;
	}

	/**
	 * Initialise the database
	 */
	public function install ()
	{
		require_once dirname(__FILE__) . '/oracle/install.php';
	}
}


/* vi:set ts=4 sts=4 sw=4 binary noeol: */

?>