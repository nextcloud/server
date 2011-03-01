<?php
/**
 * Smarty plugin
 *
 * @package Smarty
 * @subpackage PluginsFunction
 */

/**
 * Smarty {mailto} function plugin
 *
 * Type:     function<br>
 * Name:     mailto<br>
 * Date:     May 21, 2002
 * Purpose:  automate mailto address link creation, and optionally
 *            encode them.<br>
 *
 * Examples:
 * <pre>
 * {mailto address="me@domain.com"}
 * {mailto address="me@domain.com" encode="javascript"}
 * {mailto address="me@domain.com" encode="hex"}
 * {mailto address="me@domain.com" subject="Hello to you!"}
 * {mailto address="me@domain.com" cc="you@domain.com,they@domain.com"}
 * {mailto address="me@domain.com" extra='class="mailto"'}
 * </pre>
 *
 * @link http://smarty.php.net/manual/en/language.function.mailto.php {mailto}
 *          (Smarty online manual)
 * @version 1.2
 * @author Monte Ohrt <monte at ohrt dot com>
 * @author credits to Jason Sweat (added cc, bcc and subject functionality)
 * @param array $params parameters
 * Input:<br>
 *          - address = e-mail address
 *          - text = (optional) text to display, default is address
 *          - encode = (optional) can be one of:
 *                 * none : no encoding (default)
 *                 * javascript : encode with javascript
 *                 * javascript_charcode : encode with javascript charcode
 *                 * hex : encode with hexidecimal (no javascript)
 *          - cc = (optional) address(es) to carbon copy
 *          - bcc = (optional) address(es) to blind carbon copy
 *          - subject = (optional) e-mail subject
 *          - newsgroups = (optional) newsgroup(s) to post to
 *          - followupto = (optional) address(es) to follow up to
 *          - extra = (optional) extra tags for the href link
 * @param object $template template object
 * @return string
 */
function smarty_function_mailto($params, $template)
{
    $extra = '';

    if (empty($params['address'])) {
        trigger_error("mailto: missing 'address' parameter",E_USER_WARNING);
        return;
    } else {
        $address = $params['address'];
    }

    $text = $address;
    // netscape and mozilla do not decode %40 (@) in BCC field (bug?)
    // so, don't encode it.
    $search = array('%40', '%2C');
    $replace = array('@', ',');
    $mail_parms = array();
    foreach ($params as $var => $value) {
        switch ($var) {
            case 'cc':
            case 'bcc':
            case 'followupto':
                if (!empty($value))
                    $mail_parms[] = $var . '=' . str_replace($search, $replace, rawurlencode($value));
                break;

            case 'subject':
            case 'newsgroups':
                $mail_parms[] = $var . '=' . rawurlencode($value);
                break;

            case 'extra':
            case 'text':
                $$var = $value;

            default:
        }
    }

    $mail_parm_vals = '';
    for ($i = 0; $i < count($mail_parms); $i++) {
        $mail_parm_vals .= (0 == $i) ? '?' : '&';
        $mail_parm_vals .= $mail_parms[$i];
    }
    $address .= $mail_parm_vals;

    $encode = (empty($params['encode'])) ? 'none' : $params['encode'];
    if (!in_array($encode, array('javascript', 'javascript_charcode', 'hex', 'none'))) {
        trigger_error("mailto: 'encode' parameter must be none, javascript or hex",E_USER_WARNING);
        return;
    }

    if ($encode == 'javascript') {
        $string = 'document.write(\'<a href="mailto:' . $address . '" ' . $extra . '>' . $text . '</a>\');';

        $js_encode = '';
        for ($x = 0; $x < strlen($string); $x++) {
            $js_encode .= '%' . bin2hex($string[$x]);
        }

        return '<script type="text/javascript">eval(unescape(\'' . $js_encode . '\'))</script>';
    } elseif ($encode == 'javascript_charcode') {
        $string = '<a href="mailto:' . $address . '" ' . $extra . '>' . $text . '</a>';

        for($x = 0, $y = strlen($string); $x < $y; $x++) {
            $ord[] = ord($string[$x]);
        }

        $_ret = "<script type=\"text/javascript\" language=\"javascript\">\n";
        $_ret .= "<!--\n";
        $_ret .= "{document.write(String.fromCharCode(";
        $_ret .= implode(',', $ord);
        $_ret .= "))";
        $_ret .= "}\n";
        $_ret .= "//-->\n";
        $_ret .= "</script>\n";

        return $_ret;
    } elseif ($encode == 'hex') {
        preg_match('!^(.*)(\?.*)$!', $address, $match);
        if (!empty($match[2])) {
            trigger_error("mailto: hex encoding does not work with extra attributes. Try javascript.",E_USER_WARNING);
            return;
        }
        $address_encode = '';
        for ($x = 0; $x < strlen($address); $x++) {
            if (preg_match('!\w!', $address[$x])) {
                $address_encode .= '%' . bin2hex($address[$x]);
            } else {
                $address_encode .= $address[$x];
            }
        }
        $text_encode = '';
        for ($x = 0; $x < strlen($text); $x++) {
            $text_encode .= '&#x' . bin2hex($text[$x]) . ';';
        }

        $mailto = "&#109;&#97;&#105;&#108;&#116;&#111;&#58;";
        return '<a href="' . $mailto . $address_encode . '" ' . $extra . '>' . $text_encode . '</a>';
    } else {
        // no encoding
        return '<a href="mailto:' . $address . '" ' . $extra . '>' . $text . '</a>';
    }
}

?>