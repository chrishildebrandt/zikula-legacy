<?php
/**
 * Zikula Application Framework
 *
 * @copyright Zikula Development Team
 * @link http://www.zikula.org
 * @version $Id: DataUtil.class.php 28254 2010-02-16 14:47:01Z drak $
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @author Robert Gasch rgasch@gmail.com
 * @uses generic data utililty class
 * @package Zikula_Core
 */

/**
 * DataUtil
 *
 * @package Zikula_Core
 * @subpackage DataUtil
 */
class DataUtil
{
    /**
     * Clean a variable, remove slashes. This method is recursive array safe.
     *
     * @param var        The variable to clean
     *
     * @return The formatted variable
     */
    function cleanVar($var)
    {
        if (!get_magic_quotes_gpc()) {
            return $var;
        }

        if (is_array($var)) {
            foreach ($var as $k => $v) {
                $var[$k] = DataUtil::cleanVar($v);
            }
        } else {
            pnStripslashes($var);
        }

        return $var;
    }

    /**
     * Decode a character a previously encoded character
     *
     * @param value      The value we wish to encode
     *
     * @return The decoded value
     */
    function decode($value)
    {
        return base64_decode($value);
    }

    /**
     * Take a name-value-pair string and convert it to an associative array, optionally urldecoding the response.
     *
     * @param string  $nvpstr    Name-value-pair String.
     * @param string  $separator Separator used in the NVP string.
     * @param boolean $urldecode Whether to urldecode the NVP fields.
     *
     * @return array Assoc is associative array.
    */
    function decodeNVP ($nvpstr, $separator='&', $urldecode=true)
    {
        $assoc = array();
        $items = explode ($separator, $nvpstr);
        foreach ($items as $item) {
            $fields = explode ('=', $item);
            $key    = $urldecode ? urldecode($fields[0]) : $fields[0];
            $value  = $urldecode ? urldecode($fields[1]) : $fields[1];
            $assoc[$key] = $value;
        }

        return $assoc;
    }

    /**
     * Decrypt the given value using the mcrypt library function. If the mcrypt
     * functions do not exist, we fallback to the RC4 implementation which is
     * shipped with Zikula.
     *
     * @param value      The value we wish to decrypt
     * @param key        The encryption key to use (optional) (default=null)
     * @param alg        The encryption algirthm to use (only used with mcrypt functions) (optional) (default=null, signifies MCRYPT_RIJNDAEL_128)
     * @param encoded    Whether or not the value is base64 encoded (optional) (default=true)
     *
     * @return The decrypted value
     */
    function decrypt($value, $key = null, $alg = null, $encoded = true)
    {
        $res = false;
        $key = ($key ? $key : 'ZikulaEncryptionKey');
        $val = ($encoded ? DataUtil::decode($value) : $value);

        if (function_exists('mcrypt_create_iv') && function_exists('mcrypt_decrypt')) {
            $alg = ($alg ? $alg : MCRYPT_RIJNDAEL_128);
            $iv = mcrypt_create_iv(mcrypt_get_iv_size($alg, MCRYPT_MODE_ECB), crc32($key));
            $res = mcrypt_decrypt($alg, $key, $val, MCRYPT_MODE_CBC);
        } else {
            Loader::requireOnce('includes/classes/encryption/rc4crypt.class.php');
            $res = rc4crypt::decrypt($key, $val);
        }

        return $res;
    }

    /**
     * Encode a character sting such that it's 8-bit clean. It maps to base64_encode().
     *
     * @param value      The value we wish to encode
     *
     * @return The encoded value
     */
    function encode($value)
    {
        return base64_encode($value);
    }

    /**
     * Take a key and value and encode them into an NVP-string entity.
     *
     * @param string  $key          The key to encode.
     * @param string  $value        The value to encode.
     * @param string  $separator    The Separator to use in the NVP string.
     * @param boolean $includeEmpty Whether to also include empty values.
     *
     * @return string String-encoded NVP or an empty string.
    */
    function encodeNVP ($key, $value, $separator='&', $includeEmpty=true)
    {
        if (!$key) {
            return LogUtil::registerError ('Invalid NVP key received');
        }

        if ($includeEmpty || ($value != null && strlen($value) > 1)) {
            return ("&".urlencode($key) ."=" .urlencode($value));
        }

        return '';
    }

    /**
     * Take an array and encode it as a NVP string.
     *
     * @param string  $nvps         The array of name-value paris.
     * @param string  $separator    The Separator to use in the NVP string.
     * @param boolean $includeEmpty Whether to also include empty values.
     *
     * @return string String-encoded NVP or an empty string.
    */
    function encodeNVPArray ($nvps, $separator='&', $includeEmpty=true)
    {
        if (!is_array($nvps)) {
            return LogUtil::registerError ('NVPS array is not an array');
        }

        $str = '';
        foreach ($nvps as $k => $v) {
            $str .= DataUtil::encodeNVP ($k, $v, $separator, $includeEmpty);
        }

        return $str;
    }

    /**
     * Encrypt the given value using the mcrypt library function. If the mcrypt
     * functions do not exist, we fallback to the RC4 implementation which is
     * shipped with Zikula.
     *
     * @param value      The value we wish to decrypt
     * @param key        The encryption key to use (optional) (default=null)
     * @param alg        The encryption algirthm to use (only used with mcrypt functions) (optional) (default=null, signifies MCRYPT_RIJNDAEL_128)
     * @param encoded    Whether or not the value is base64 encoded (optional) (default=true)
     *
     * @return The encrypted value
     */
    function encrypt($value, $key = null, $alg = null, $encoded = true)
    {
        $res = false;
        $key = ($key ? $key : 'ZikulaEncryptionKey');

        if (function_exists('mcrypt_create_iv') && function_exists('mcrypt_decrypt')) {
            $alg = ($alg ? $alg : MCRYPT_RIJNDAEL_128);
            $iv = mcrypt_create_iv(mcrypt_get_iv_size($alg, MCRYPT_MODE_ECB), crc32($key));
            $res = mcrypt_encrypt($alg, $key, $value, MCRYPT_MODE_CBC);
        } else {
            Loader::requireOnce('includes/classes/encryption/rc4crypt.class.php');
            $res = rc4crypt::encrypt($key, $value);
        }

        return ($encoded && $res ? DataUtil::encode($res) : $res);
    }

    /**
     * Format a variable for display. This method is recursive array safe.
     *
     * @param var        The variable to format
     *
     * @return The formatted variable
     */
    function formatForDisplay($var)
    {
        // This search and replace finds the text 'x@y' and replaces
        // it with HTML entities, this provides protection against
        // email harvesters
        static $search = array('/(.)@(.)/se');

        static $replace = array('"&#" .
                                sprintf("%03d", ord("\\1")) .
                                ";&#064;&#" .
                                sprintf("%03d", ord("\\2")) . ";";');

        if (is_array($var)) {
            foreach ($var as $k => $v) {
                $var[$k] = DataUtil::formatForDisplay($v);
            }
        } else {
            $var = htmlspecialchars((string) $var);
            $var = preg_replace($search, $replace, $var);
        }

        return $var;
    }

    /**
     * Format a variable for HTML display. This method is recursive array safe.
     *
     * @param var        The variable to format
     *
     * @return The formatted variable
     */
    function formatForDisplayHTML($var)
    {
        // This search and replace finds the text 'x@y' and replaces
        // it with HTML entities, this provides protection against
        // email harvesters
        //
        // Note that the use of \024 and \022 are needed to ensure that
        // this does not break HTML tags that might be around either
        // the username or the domain name
        static $search = array(
            '/([^\024])@([^\022])/se');

        static $replace = array('"&#" .
                                sprintf("%03d", ord("\\1")) .
                                ";&#064;&#" .
                                sprintf("%03d", ord("\\2")) . ";";');

        static $allowedtags = NULL;
        static $outputfilter;

        if (!isset($allowedtags)) {
            $allowedHTML = array();
            $allowableHTML = pnConfigGetVar('AllowableHTML');
            if (is_array($allowableHTML)) {
                foreach ($allowableHTML as $k => $v) {
                    if ($k == '!--') {
                        if ($v != 0) {
                            $allowedHTML[] = "$k.*?--";
                        }
                    } else {
                        switch ($v) {
                            case 0:
                                break;
                            case 1:
                                $allowedHTML[] = "/?$k\s*/?";
                                break;
                            case 2:
                                // intelligent regex to deal with > in parameters, bug #1782 credits to jln
                                $allowedHTML[] = "/?\s*$k" . "(\s+[\w:]+\s*=\s*(\"[^\"]*\"|'[^']*'))*" . '\s*/?';
                                break;
                        }
                    }
                }
            }

            if (count($allowedHTML) > 0) {
                // 2nd part of bugfix #1782
                $allowedtags = '~<\s*(' . join('|', $allowedHTML) . ')\s*>~is';
            } else {
                $allowedtags = '';
            }
        }

        if (!isset($outputfilter)) {
            if (pnModAvailable('SecurityCenter') && !defined('_PNINSTALLVER')) {
                $outputfilter = pnConfigGetVar('outputfilter');
            } else {
                $outputfilter = 0;
            }
        }

        if (is_array($var)) {
            foreach ($var as $k => $v) {
                $var[$k] = DataUtil::formatForDisplayHTML($v);
            }
        } else {
            // Run additional filters
            if ($outputfilter > 0) {
                $var = pnModAPIFunc('SecurityCenter', 'user', 'secureoutput', array('var' => $var, 'filter' => $outputfilter));
            }

            // Preparse var to mark the HTML that we want
            if (!empty($allowedtags)) {
                $var = preg_replace($allowedtags, "\022\\1\024", $var);
            }

            // Encode email addresses
            $var = preg_replace($search, $replace, $var);

            // Fix html entities
            $var = htmlspecialchars($var);

            // Fix the HTML that we want
            $var = preg_replace_callback('/\022([^\024]*)\024/', 'DataUtil_pnVarPrepHTMLDisplay__callback', $var);

            // Fix entities if required
            if (pnConfigGetVar('htmlentities')) {
                $var = preg_replace('/&amp;([a-z#0-9]+);/i', "&\\1;", $var);
            }
        }

        return $var;
    }

    /**
     * Format a variable for DB-storage. This method is recursive array safe.
     *
     * @param var        The variable to format
     *
     * @return The formatted variable
     */
    function formatForStore($var)
    {
        if (is_array($var)) {
            foreach ($var as $k => $v) {
                $var[$k] = DataUtil::formatForStore($v);
            }
        } else {
            $dbType = DBConnectionStack::getConnectionDBType();
            if ($dbType == 'mssql' || $dbType == 'oci8' || $dbType == 'oracle') {
                $var = str_replace("'", "''", $var);
            } else {
                $var = addslashes($var);
            }
        }

        return $var;
    }

    /**
     * Format a variable for operating-system usage. This method is recursive array safe.
     *
     * @param var        The variable to format
     * @param absolute   Allow absolute paths (default=false) (optional)
     *
     * @return The formatted variable
     */
    function formatForOS($var, $absolute = false)
    {
        if (is_array($var)) {
            foreach ($var as $k => $v) {
                $var[$k] = DataUtil::formatForOS($v);
            }
        } else {
            static $cached;
            if ($cached == null) {
                $cached = array();
            }

            if (isset($cached[$var])) {
                return $cached[$var];
            }
            $orgVar = $var;

            $clean_array = array();

            //if we're supporting absolute paths and the first charater is a slash and , then
            //an absolute path is passed
            $absolutepathused = ($absolute && substr($var, 0, 1) == '/');

            // Split the path at possible path delimiters.
            // Setting PREG_SPLIT_NOEMPTY eliminates double delimiters on the fly.
            $dirty_array = preg_split('#[:/\\\\]#', $var, -1, PREG_SPLIT_NO_EMPTY);

            // now walk the path and do the relevant things
            foreach ($dirty_array as $current) {
                if ($current == '.') {
                    // current path element is a dot, so we don't do anything
                } elseif ($current == '..') {
                    // current path element is .., so we remove the last path in case of relative paths
                    if (!$absolutepathused) {
                        array_pop($clean_array);
                    }
                } else {
                    // current path element is valid, so we add it to the path
                    $clean_array[] = $current;
                }
            }

            // build the path
            // should we use DIRECTORY_SEPARATOR here?
            $var = implode('/', $clean_array);
            //if an absolute path was passed to the function, we need to make it absolute again
            if ($absolutepathused) {
                $var = '/' . $var;
            }

            // Prepare var
            // needed for magic_quotes_runtime = 0
            $var = addslashes($var);

            $cached[$orgVar] = $var;
        }

        return $var;
    }

    function formatForURL($var)
    {
        return DataUtil::formatPermalink($var);
    }

    function formatPermalink($var)
    {
        static $permalinksseparator;
        if (!isset($permalinksseparator)) {
            $permalinksseparator = pnConfigGetVar('shorturlsseparator');
        }

        $permasearch = explode(',', pnConfigGetVar('permasearch'));
        $permareplace = explode(',', pnConfigGetVar('permareplace'));

        // replace all chars $permasearch with the one in $permareplace
        foreach ($permasearch as $key => $value) {
            $var = mb_ereg_replace("[$value]", $permareplace[$key], $var);
        }

        $var = preg_replace("#(\s*\/\s*|\s*\+\s*|\s+)#", '-', strtolower($var));

        // final clean
        $var = mb_ereg_replace("[^a-z0-9_{$permalinksseparator}]", '', $var, "imsr");
        $var = trim($var, $permalinksseparator);

        return $var;
    }

    /**
     * Censor variable contents. This method is recursive array safe.
     *
     * @param var        The variable to censor
     *
     * @return The censored variable
     */
    function censor($var)
    {
        static $doCensor;
        if (!isset($doCensor)) {
            $doCensor = pnModAvailable('MultiHook');
        }

        if (!$doCensor) {
            return $var;
        }

        if (is_array($var)) {
            foreach ($var as $k => $v) {
                $var[$k] = DataUtil::censor($v);
            }
        } else {
            $var = pnModAPIFunc('MultiHook', 'user', 'censor', array('word' => $var)); // preg_replace($search, $replace, $var);
        }

        return $var;
    }

    /**
     * Perform SHA1 or SHA256 hashing on a string using native
     * PHP functions if available and if not uses own classes.
     *
     * @author Drak
     * @param $string string to be hashed
     * @param $type string md5, sha1 (default), sha256
     * @return string hex hash
     */
    function hash($string, $type = 'sha1')
    {
        $type = strtolower($type);
        if ($type == 'sha1') {
            return sha1($string);
        } else if ($type == 'sha256') {
            if (function_exists('mhash')) {
                return bin2hex(mhash(MHASH_SHA256, $string));
            } else {
                if (!class_exists('SHA256')) {
                    Loader::requireOnce('includes/classes/hashes/sha256.class.php');
                }
                return SHA256::hash($string);
            }
        } else if ($type == 'md5') {
            return md5($string);
        }

        return false;
    }

    /**
     * This method converts the several possible return values from
     * allegedly "boolean" ini settings to proper booleans
     * Properly converted input values are: 'off', 'on', 'false', 'true', '0', '1'
     * If the ini_value doesn't match any of those, the value is returned as-is.
     *
     * @author Ed Finkler
     * @param string $ini_key   the ini_key you need the value of
     * @return boolean|mixed
     */
    function getBooleanIniValue($ini_key)
    {
        $ini_val = ini_get($ini_key);
        switch (strtolower($ini_val)) {
            case 'off':
                return false;
                break;
            case 'on':
                return true;
                break;
            case 'false':
                return false;
                break;
            case 'true':
                return true;
                break;
            case '0':
                return false;
                break;
            case '1':
                return true;
                break;
            default:
                return $ini_val;
        }
    }

    /**
     * check for serialization
     *
     * @param string $string
     * @param checkmb true or false
     * @return bool
     */
    function is_serialized($string, $checkmb = true)
    {
        if ($string == 'b:0;') {
            return true;
        }

        if ($checkmb) {
            return (DataUtil::mb_unserialize($string) === false ? false : true);
        } else {
            return (@unserialize($string) === false ? false : true);
        }
    }

    /**
     * Will unserialise serialised data that was previously encoded as iso and converted to utf8
     * This generally not required.
     *
     * @param $string serialised data
     * @return mixed
     */
    function mb_unserialize($string)
    {
        // we use a callback here to avoid problems with certain characters (single quotes and dollarsign) - drak
        return @unserialize(preg_replace_callback('#s:(\d+):"(.*?)";#s', create_function('$m', 'return DataUtil::_mb_unserialize_callback($m);'), $string));
    }

    /**
     * private callback function for mb_unserialize()
     * Note this is still a private method although we have to use public visibility
     *
     * @access private
     * @param string $match
     */
    function _mb_unserialize_callback($match)
    {
        $length = strlen($match[2]);
        return "s:$length:\"$match[2]\";";
    }

    /**
     * convertToUTF8()
     * converts a string or an array (recursivly) to utf-8
     *
     * @param input - string or array to convert to utf-8
     * @return converted string or array
     * @author Frank Schummertz
     *
     */
    function convertToUTF8($input = '')
    {
        if (is_array($input)) {
            $return = array();
            foreach ($input as $key => $value) {
                $return[$key] = DataUtil::convertToUTF8($value);
            }
            return $return;
        } elseif (is_string($input)) {
            if (function_exists('mb_convert_encoding')) {
                return mb_convert_encoding($input, 'UTF-8', strtoupper(ZLanguage::getEncoding()));
            } else {
                return utf8_encode($input);
            }
        } else {
            return $input;
        }
    }

    /**
     * convertFromUTF8()
     * converts a string from utf-8
     *
     * @param input - string or array to convert from utf-8
     * @return converted string
     * @author Frank Schummertz
     *
     */
    function convertFromUTF8($input = '')
    {
        if (is_array($input)) {
            $return = array();
            foreach ($input as $key => $value) {
                $return[$key] = DataUtil::convertFromUTF8($value);
            }
            return $return;
        } elseif (is_string($input)) {
            if (function_exists('mb_convert_encoding')) {
                return mb_convert_encoding($input, strtoupper(ZLanguage::getEncoding()), 'UTF-8');
            } else {
                return utf8_decode($input);
            }
        } else {
            return $input;
        }
    }


    /**
     * take user input and transform to a number according to locale
     * @param $number
     * @return unknown_type
     */
    function transformNumberInternal($number)
    {
        $i18n = & ZI18n::getInstance();
        return $i18n->transformNumberInternal($number);
    }

    /**
     * transform a currency to an internal number according to locale
     * @param $number
     * @return unknown_type
     */
    function transformCurrencyInternal($number)
    {
        $i18n = & ZI18n::getInstance();
        return $i18n->transformCurrencyInternal($number);
    }

    /**
     * format a number to currency according to locale
     *
     * @param $number
     * @return unknown_type
     */
    function formatCurrency($number)
    {
        $i18n = & ZI18n::getInstance();
        return $i18n->transformCurrencyDisplay($number);
    }

    /**
     * format a number for display in locale
     *
     * @param $number
     * @param $decimal_points null=default locale, false=precision, int=precision
     * @return unknown_type
     */
    function formatNumber($number, $decimal_points=null)
    {
        $i18n = & ZI18n::getInstance();
        return $i18n->transformNumberDisplay($number, $decimal_points);
    }

    /**
     * native ini file parser because PHP can't handle such a simple function cross platform
     *
     * taken from http://mach13.com/loose-and-multiline-parse_ini_file-function-in-php
     *
     * @param $iIniFile
     * @return array
     */
    function parseIniFile($iIniFile)
    {
        $aResult = array();
        $aMatches = array();

        $a = &$aResult;
        $s = '\s*([[:alnum:]_\- \*]+?)\s*';

        preg_match_all('#^\s*((\['.$s.'\])|(("?)'.$s.'\\5\s*=\s*("?)(.*?)\\7))\s*(;[^\n]*?)?$#ms', file_get_contents($iIniFile), $aMatches, PREG_SET_ORDER);

        foreach ($aMatches as $aMatch) {
            if (empty($aMatch[2])) {
                $a[$aMatch[6]] = $aMatch[8];
            } else {
                $a = &$aResult[$aMatch[3]];
            }
       }
       return $aResult;
    }
}

/**
 * Callback function for pnVarPrepHTMLDisplay
 *
 * @author Xaraya development team
 * @access private
 */
function DataUtil_pnVarPrepHTMLDisplay__callback($matches)
{
    if (empty($matches)) {
        return;
    }

    $res = '<' . strtr($matches[1], array('&gt;' => '>', '&lt;' => '<', '&quot;' => '"'/*,
                             '&amp;' => '&'*/)) . '>';

    return $res;
}
