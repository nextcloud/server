<?php
/**
 * Smarty plugin
 *
 * @package Smarty
 * @subpackage PluginsFunction
 */

/**
 * Smarty {fetch} plugin
 *
 * Type:     function<br>
 * Name:     fetch<br>
 * Purpose:  fetch file, web or ftp data and display results
 * @link http://smarty.php.net/manual/en/language.function.fetch.php {fetch}
 *       (Smarty online manual)
 * @author Monte Ohrt <monte at ohrt dot com>
 * @param array $params parameters
 * @param object $template template object
 * @return string|null if the assign parameter is passed, Smarty assigns the
 *                     result to a template variable
 */
function smarty_function_fetch($params, $template)
{
    if (empty($params['file'])) {
        trigger_error("[plugin] fetch parameter 'file' cannot be empty",E_USER_NOTICE);
        return;
    }

    $content = '';
    if (isset($template->security_policy) && !preg_match('!^(http|ftp)://!i', $params['file'])) {
        if(!$template->security_policy->isTrustedResourceDir($params['file'])) {
            return;
        }
        
        // fetch the file
        if($fp = @fopen($params['file'],'r')) {
            while(!feof($fp)) {
                $content .= fgets ($fp,4096);
            }
            fclose($fp);
        } else {
            trigger_error('[plugin] fetch cannot read file \'' . $params['file'] . '\'',E_USER_NOTICE);
            return;
        }
    } else {
        // not a local file
        if(preg_match('!^http://!i',$params['file'])) {
            // http fetch
            if($uri_parts = parse_url($params['file'])) {
                // set defaults
                $host = $server_name = $uri_parts['host'];
                $timeout = 30;
                $accept = "image/gif, image/x-xbitmap, image/jpeg, image/pjpeg, */*";
                $agent = "Smarty Template Engine ".$template->_version;
                $referer = "";
                $uri = !empty($uri_parts['path']) ? $uri_parts['path'] : '/';
                $uri .= !empty($uri_parts['query']) ? '?' . $uri_parts['query'] : '';
                $_is_proxy = false;
                if(empty($uri_parts['port'])) {
                    $port = 80;
                } else {
                    $port = $uri_parts['port'];
                }
                if(!empty($uri_parts['user'])) {
                    $user = $uri_parts['user'];
                }
                if(!empty($uri_parts['pass'])) {
                    $pass = $uri_parts['pass'];
                }
                // loop through parameters, setup headers
                foreach($params as $param_key => $param_value) {
                    switch($param_key) {
                        case "file":
                        case "assign":
                        case "assign_headers":
                            break;
                        case "user":
                            if(!empty($param_value)) {
                                $user = $param_value;
                            }
                            break;
                        case "pass":
                            if(!empty($param_value)) {
                                $pass = $param_value;
                            }
                            break;
                        case "accept":
                            if(!empty($param_value)) {
                                $accept = $param_value;
                            }
                            break;
                        case "header":
                            if(!empty($param_value)) {
                                if(!preg_match('![\w\d-]+: .+!',$param_value)) {
                                    trigger_error("[plugin] invalid header format '".$param_value."'",E_USER_NOTICE);
                                    return;
                                } else {
                                    $extra_headers[] = $param_value;
                                }
                            }
                            break;
                        case "proxy_host":
                            if(!empty($param_value)) {
                                $proxy_host = $param_value;
                            }
                            break;
                        case "proxy_port":
                            if(!preg_match('!\D!', $param_value)) {
                                $proxy_port = (int) $param_value;
                            } else {
                                trigger_error("[plugin] invalid value for attribute '".$param_key."'",E_USER_NOTICE);
                                return;
                            }
                            break;
                        case "agent":
                            if(!empty($param_value)) {
                                $agent = $param_value;
                            }
                            break;
                        case "referer":
                            if(!empty($param_value)) {
                                $referer = $param_value;
                            }
                            break;
                        case "timeout":
                            if(!preg_match('!\D!', $param_value)) {
                                $timeout = (int) $param_value;
                            } else {
                                trigger_error("[plugin] invalid value for attribute '".$param_key."'",E_USER_NOTICE);
                                return;
                            }
                            break;
                        default:
                            trigger_error("[plugin] unrecognized attribute '".$param_key."'",E_USER_NOTICE);
                            return;
                    }
                }
                if(!empty($proxy_host) && !empty($proxy_port)) {
                    $_is_proxy = true;
                    $fp = fsockopen($proxy_host,$proxy_port,$errno,$errstr,$timeout);
                } else {
                    $fp = fsockopen($server_name,$port,$errno,$errstr,$timeout);
                }

                if(!$fp) {
                    trigger_error("[plugin] unable to fetch: $errstr ($errno)",E_USER_NOTICE);
                    return;
                } else {
                    if($_is_proxy) {
                        fputs($fp, 'GET ' . $params['file'] . " HTTP/1.0\r\n");
                    } else {
                        fputs($fp, "GET $uri HTTP/1.0\r\n");
                    }
                    if(!empty($host)) {
                        fputs($fp, "Host: $host\r\n");
                    }
                    if(!empty($accept)) {
                        fputs($fp, "Accept: $accept\r\n");
                    }
                    if(!empty($agent)) {
                        fputs($fp, "User-Agent: $agent\r\n");
                    }
                    if(!empty($referer)) {
                        fputs($fp, "Referer: $referer\r\n");
                    }
                    if(isset($extra_headers) && is_array($extra_headers)) {
                        foreach($extra_headers as $curr_header) {
                            fputs($fp, $curr_header."\r\n");
                        }
                    }
                    if(!empty($user) && !empty($pass)) {
                        fputs($fp, "Authorization: BASIC ".base64_encode("$user:$pass")."\r\n");
                    }

                    fputs($fp, "\r\n");
                    while(!feof($fp)) {
                        $content .= fgets($fp,4096);
                    }
                    fclose($fp);
                    $csplit = preg_split("!\r\n\r\n!",$content,2);

                    $content = $csplit[1];

                    if(!empty($params['assign_headers'])) {
                        $template->assign($params['assign_headers'],preg_split("!\r\n!",$csplit[0]));
                    }
                }
            } else {
                trigger_error("[plugin fetch] unable to parse URL, check syntax",E_USER_NOTICE);
                return;
            }
        } else {
            // ftp fetch
            if($fp = @fopen($params['file'],'r')) {
                while(!feof($fp)) {
                    $content .= fgets ($fp,4096);
                }
                fclose($fp);
            } else {
                trigger_error('[plugin] fetch cannot read file \'' . $params['file'] .'\'',E_USER_NOTICE);
                return;
            }
        }

    }


    if (!empty($params['assign'])) {
        $template->assign($params['assign'],$content);
    } else {
        return $content;
    }
}

?>