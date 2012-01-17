<?php
/**
 * Zikula Application Framework
 *
 * @copyright (c) 2001, Zikula Development Team
 * @link http://www.zikula.org
 * @version $Id: SessionUtil.class.php 27233 2009-10-27 20:07:15Z mateo $
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @package Zikula_Core
 * @subpackage SessionUtil
 */

/**
 * SessionUtil
 *
 * @package Zikula_Core
 * @subpackage SessionUtil
 */
class SessionUtil
{
    /**
     * Set up session handling
     * Set all PHP options for Zikula session handling
     *
     * @return void
     */
    function _setup()
    {
        $path = pnGetBaseURI();
        if (empty($path)) {
            $path = '/';
        } elseif (substr($path, -1, 1) != '/') {
            $path .= '/';
        }

        $host = pnServerGetVar('HTTP_HOST');

        if (($pos = strpos($host, ':')) !== false) {
            $host = substr($host, 0, $pos);
        }

        // PHP configuration variables
        ini_set('session.use_trans_sid', 0); // Stop adding SID to URLs
        @ini_set('url_rewriter.tags', ''); // some environments dont allow this value to be set causing an error that prevents installation
        ini_set('session.serialize_handler', 'php'); // How to store data
        ini_set('session.use_cookies', 1); // Use cookie to store the session ID
        ini_set('session.auto_start', 1); // Auto-start session


        ini_set('session.name', SessionUtil::getCookieName()); // Name of our cookie


        // Set lifetime of session cookie
        $seclevel = pnConfigGetVar('seclevel');
        switch ($seclevel) {
            case 'High':
                // Session lasts duration of browser
                $lifetime = 0;
                // Referer check
                // ini_set('session.referer_check', $host.$path);
                ini_set('session.referer_check', $host);
                break;
            case 'Medium':
                // Session lasts set number of days
                $lifetime = pnConfigGetVar('secmeddays') * 86400;
                break;
            case 'Low':
            default:
                // Session lasts unlimited number of days (well, lots, anyway)
                // (Currently set to 25 years)
                $lifetime = 788940000;
                break;
        }
        ini_set('session.cookie_lifetime', $lifetime);

        // domain and path settings for session cookie
        // if (pnConfigGetVar('intranet') == false) {
        // Cookie path
        ini_set('session.cookie_path', $path);

        // Garbage collection
        ini_set('session.gc_probability', pnConfigGetVar('gc_probability'));
        ini_set('session.gc_divisor', 10000);
        ini_set('session.gc_maxlifetime', pnConfigGetVar('secinactivemins') * 60); // Inactivity timeout for user sessions


        // change session hash length according to PHP version
        ini_set('session.hash_function', (((float) phpversion() >= 5) ? 1 : 0));

        // Set custom session handlers
        ini_set('session.save_handler', 'user');
        if (pnConfigGetVar('sessionstoretofile')) {
            ini_set('session.save_path', pnConfigGetVar('sessionsavepath'));
        }
        // PHP 5.2 workaround
        if (version_compare(phpversion(), '5.2.0', '>=')) {
            register_shutdown_function('session_write_close');
        }
        // Do not call any of these functions directly.  Marked as private with _
        session_set_save_handler('_SessionUtil__Start', '_SessionUtil__Close', '_SessionUtil__Read', '_SessionUtil__Write',   /* use session_write_close(); */
                                 '_SessionUtil__Destroy', /* use session_destroy(); */
                                 '_SessionUtil__GC');
    }

    /**
     * Initialise session
     *
     * @return bool
     */
    function initialize()
    {
        SessionUtil::_setup();

        // First thing we do is ensure that there is no attempted pollution
        // of the session namespace
        if (ini_get('register_globals')) {
            foreach ($GLOBALS as $k => $v) {
                if (substr($k, 0, 4) == 'PNSV') {
                    return false;
                }
            }
        }

        // create IP finger print
        $current_ipaddr = '';
        $_REMOTE_ADDR = pnServerGetVar('REMOTE_ADDR');
        $_HTTP_X_FORWARDED_FOR = pnServerGetVar('HTTP_X_FORWARDED_FOR');

        if (pnConfigGetVar('sessionipcheck')) {
            /* -- feature for after 0.8 release - drak
            // todo - add dropdown option for sessionipcheckmask for /32, /24, /16 CIDR

            $ipmask = pnConfigGetVar('sessionipcheckmask');
            if ($ipmask <> 32) {
                // since we're not a /32 we need to handle in case multiple ips returned
                if ($_HTTP_X_FORWARDED_FOR && strstr($_HTTP_X_FORWARDED_FOR, ', ')) {
                    $_ips = explode(', ', $_HTTP_X_FORWARDED_FOR);
                    $_HTTP_X_FORWARDED_FOR = $_ips[0];
                }

                // apply CIDR mask to allow IP checks on clients assigned
                // dynamic IP addresses - e.g. A O *cough* L
                if ($ipmask == 24) {
                    $_REMOTE_ADDR = preg_replace('/[^.]+.$/', '*', $_REMOTE_ADDR);
                    $_HTTP_X_FORWARDED_FOR = ($_HTTP_X_FORWARDED_FOR ? preg_replace('/[^.]+.$/', '*', $_HTTP_X_FORWARDED_FOR) : '');
                } else if ($ipmask == 16) {
                    $_REMOTE_ADDR = preg_replace('/[0-9]*.\.[^.]+.$/', '*', $_REMOTE_ADDR);
                    $_HTTP_X_FORWARDED_FOR = ($_HTTP_X_FORWARDED_FOR ? preg_replace('/[0-9]*.\.[^.]+.$/', '*', $fullhost) : '');
                } else { // must be a /32 CIDR
                    null; // nothing to do
                }
            }
            */
        }

        // create the ip fingerprint
        $current_ipaddr = md5($_REMOTE_ADDR . $_HTTP_X_FORWARDED_FOR);

        // start session check expiry and ip fingerprint if required
        if (session_start() && isset($GLOBALS['_PNSession']['obj']) && $GLOBALS['_PNSession']['obj']) {
            // check if session has expired or not
            $now = time();
            $inactive = ($now - (int) (pnConfigGetVar('secinactivemins') * 60));
            $daysold = ($now - (int) (pnConfigGetVar('secmeddays') * 86400));
            $lastused = strtotime($GLOBALS['_PNSession']['obj']['lastused']);
            $rememberme = SessionUtil::getVar('rememberme');
            $uid = $GLOBALS['_PNSession']['obj']['uid'];
            $ipaddr = $GLOBALS['_PNSession']['obj']['ipaddr'];

            // IP check
            if (pnConfigGetVar('sessionipcheck', false)) {
                if ($ipaddr !== $current_ipaddr) {
                    session_destroy();
                    return false;
                }
            }

            switch (pnConfigGetVar('seclevel')) {
                case 'Low':
                    // Low security - users stay logged in permanently
                    //                no special check necessary
                    break;
                case 'Medium':
                    // Medium security - delete session info if session cookie has
                    // expired or user decided not to remember themself and inactivity timeout
                    // OR max number of days have elapsed without logging back in
                    if ((!$rememberme && $lastused < $inactive) || ($lastused < $daysold) || ($uid == '0' && $lastused < $inactive)) {
                        SessionUtil::expire();
                    }
                    break;
                case 'High':
                default:
                    // High security - delete session info if user is inactive
                    //if ($rememberme && ($lastused < $inactive)) { // see #427
                    if ($lastused < $inactive) {
                        SessionUtil::expire();
                    }
                    break;
            }
        } else {
            // *must* regenerate new session otherwise the default sessid will be
            // taken from any session cookie that was submitted (bad bad bad)
            SessionUtil::regenerate(true);
            SessionUtil::_createNew(session_id(), $current_ipaddr);
        }

        if (isset($_SESSION['_PNSession']['obj'])) {
            unset($_SESSION['_PNSession']['obj']);
        }

        return true;
    }

    /**
     * Create a new session
     *
     * @access private
     * @param sessid $ the session ID
     * @param ipaddr $ the IP address of the host with this session
     *
     * @return bool
     */
    function _createNew($sessid, $ipaddr)
    {
        $now = date('Y-m-d H:i:s', time());
        $obj = array('sessid' => $sessid, 'ipaddr' => $ipaddr, 'uid' => 0, 'lastused' => $now);
        $GLOBALS['_PNSession']['obj'] = $obj;
        $GLOBALS['_PNSession']['new'] = true;
        // Generate a random number, used for some authentication (using prime numer bounds)
        //SessionUtil::setVar('rand', RandomUtil::getString(32, 40, false, true, true, false, true, true, true));
        // Initialize the array of random values for modules authentication
        SessionUtil::setVar('rand', array());
        // write hash of useragent into the session for later validation
        SessionUtil::setVar('useragent', sha1(pnServerGetVar('HTTP_USER_AGENT')));

        // init status & error message arrays
        SessionUtil::setVar('_PNErrorMsg', array());
        SessionUtil::setVar('_PNStatusMsg', array());

        return true;
    }

    /**
     * Get a session variable
     *
     * @param sring $name of the session variable to get
     * @param string $default the default value to return if the requested session variable is not set
     * @param autocreate $autocreate whether or not to autocreate the supplied path (optional) (default=true)
     * @param overwriteExistingVar $overwriteExistingVar whether or not to overwrite existing/set variable entries which the given path requires to be arrays (optional) (default=false)
     * @return string session variable requested
     */
    function getVar($name, $default = false, $path = '/', $autocreate = true, $overwriteExistingVar = false)
    {
        /* Legacy Handling
         * $lang in session has deprecated and code should use ZLanguage::getLanguageCodeLegacy();
         * if you need the current language code use ZLanguage::getLanguageCode();
         */
        if ($name == 'lang') {
            return ZLanguage::getLanguageCodeLegacy();
        }

        if ($path == '/' || $path === '') {
            if (isset($_SESSION['PNSV' . $name])) {
                return $_SESSION['PNSV' . $name];
            }
        } else {
            $parent = & SessionUtil::_resolvePath($path, $autocreate, $overwriteExistingVar);
            if ($parent === false) { // path + autocreate or overwriteExistingVar Error
                return false;
            }

            if (isset($parent[$name])) {
                return $parent[$name];
            } else if ($autocreate) {
                $parent[$name] = $default;
            }
        }

        return $default;
    }

    /**
     * Set a session variable
     *
     * @param string $name of the session variable to set
     * @param value $value to set the named session variable
     * @param path $path to traverse to reach the element we wish to return (optional) (default='/')
     * @param autocreate $autocreate whether or not to autocreate the supplied path (optional) (default=true)
     * @param overwriteExistingVar $overwriteExistingVar whether or not to overwrite existing/set variable entries which the given path requires to be arrays (optional) (default=false)
     * @return bool true upon success, false upon failure
     */
    function setVar($name, $value, $path = '/', $autocreate = true, $overwriteExistingVar = false)
    {
        global $PNConfig;

        if (($name == 'errormsg' || $name == 'statusmsg' || $name == '_PNErrorMsg' || $name == '_PNStatusMsg') && !is_array($value)) {
            if ($PNConfig['System']['development']) {
                LogUtil::log(__("Error! This use of 'SessionUtil::setVar()' is no longer valid. Please use the LogUtil API to manipulate status messages and error messages."));
            }
            if ($name == '_PNErrorMsg' || $name == 'errormsg') {
                return LogUtil::registerError($value);
            }
            if ($name == '_PNStatusMsg' || $name == 'statusmsg') {
                return LogUtil::registerStatus($value);
            }
        }

        // temporary fix for bug #3770
        // $value = str_replace('\\', '/', $value);


        // cause session on regeration on uid change
        if ($name == 'uid') {
            SessionUtil::regenerate();
        }

        if ($path == '/' || $path === '') {
            $_SESSION['PNSV' . $name] = $value;
        } else {
            $parent = & SessionUtil::_resolvePath($path, $autocreate, $overwriteExistingVar);
            if ($parent === false) { // path + autocreate or overwriteExistingVar Error
                return false;
            }

            $parent[$name] = $value;
        }

        return true;
    }

    /**
     * Delete a session variable
     *
     * @param string $name of the session variable to delete
     * @param string $default the default value to return if the requested session variable is not set
     * @param path $path to traverse to reach the element we wish to return (optional) (default='/')
     * @return bool true
     */
    function delVar($name, $default = false, $path = '/')
    {
        $value = false;

        if ($path == '/' || $path === '') {
            if (isset($_SESSION['PNSV' . $name])) {
                $value = $_SESSION['PNSV' . $name];
                unset($_SESSION['PNSV' . $name]);
            } else {
                $value = $default;
            }
        } else {
            $parent = & SessionUtil::_resolvePath($path, false, false);
            if ($parent === false) { // path + autocreate or overwriteExistingVar Error
                return false;
            }

            if (isset($parent[$name])) {
                $value = $parent[$name];
                unset($parent[$name]);
            } else {
                $value = $default;
            }
        }

        // unset if registerglobals are on
        unset($GLOBALS['PNSV' . $name]);

        return $value;
    }

    /**
     * Traverse the session data structure according to the path given and return a reference to last object in the path
     *
     * @access private
     * @param path $path to traverse to reach the element we wish to return
     * @param autocreate $autocreate whether or not to autocreate the supplied path (optional) (default=true)
     * @param overwriteExistingVar $overwriteExistingVar whether or not to overwrite existing/set variable entries which the given path requires to be arrays (optional) (default=false)
     * @return mixed array upon successful location/creation of path element(s), false upon failure
     */
    function &_resolvePath($path, $autocreate = true, $overwriteExistingVar = false)
    {
        // now traverse down the path and set the var
        if ($path == '/' || !$path) {
            return LogUtil::registerError(__f('Error! Invalid %s received.', 'path'));
        }

        // remove leading '/' so that explode doesn't deliver an empty 1st element
        if (strpos($path, '/') === 0) {
            $path = substr($path, 1);
        }

        $c = 0;
        $parent = & $_SESSION;
        $paths = explode('/', $path);
        foreach ($paths as $p) {
            $pFixed = ($c == 0 ? 'PNSV' . $p : $p);
            if (!isset($parent[$pFixed])) {
                if ($autocreate) {
                    $parent[$pFixed] = array();
                    $parent = & $parent[$pFixed];
                } else {
                    $false = false;
                    return $false;
                }
            } else {
                if (!is_array($parent[$pFixed])) {
                    if ($overwriteExistingVar) {
                        $parent[$pFixed] = array();
                    } else {
                        $false = false;
                        return $false;
                    }
                }
                $parent = & $parent[$pFixed];
            }
            $c++;
        }

        return $parent;
    }

    /**
     * Session required
     * Starts a session or terminates loading.
     *
     */
    function requireSession()
    {
        // check if we need to create a session
        if (!session_id()) {
            // Start session
            if (!SessionUtil::initialize()) {
                // session initialization failed so display templated error
                header('HTTP/1.1 503 Service Unavailable');
                if (file_exists('config/templates/sessionfailed.htm')) {
                    Loader::requireOnce('config/templates/sessionfailed.htm');
                } else {
                    Loader::requireOnce('includes/templates/sessionfailed.htm');
                }
                // terminate execution
                pnShutDown();
            }
        }
    }

    /**
     * Let session expire nicely
     *
     * @return void
     */
    function expire()
    {
        if (SessionUtil::getVar('uid') == '0') {
            // no need to do anything for guests without sessions
            if (pnConfigGetVar('anonymoussessions') == '0')
                return;

            // no need to display expiry for anon users with sessions since it's invisible anyway
            // handle expired sessions differently
            SessionUtil::_createNew(session_id(), $GLOBALS['_PNSession']['obj']['ipaddr']);
            // session is not new, remove flag
            unset($GLOBALS['_PNSession']['new']);
            SessionUtil::regenerate(true);
            return;
        }

        // for all logged in users with session destroy session and set flag
        session_destroy();
        $GLOBALS['_PNSession']['expired'] = true;
    }

    /**
     * Check if a session has expired or not
     *
     * @return bool
     */
    function hasExpired()
    {
        if (isset($GLOBALS['_PNSession']['expired']) && $GLOBALS['_PNSession']['expired']) {
            unset($GLOBALS['_PNSession']);
            return true;
        }

        return false;
    }

    /**
     * regerate session id
     *
     * @param bool $force default false force regeneration
     * @return void
     *
     */
    function regenerate($force = false)
    {
        // only regenerate if set in admin
        if ($force == false) {
            if (!pnConfigGetVar('sessionregenerate') || pnConfigGetVar('sessionregenerate') == 0) {
                // there is no point changing a newly generated session.
                if (isset($GLOBALS['_PNSession']['new']) && $GLOBALS['_PNSession']['new'] == true) {
                    return;
                }
                return;
            }
        }

        // dont allow multiple regerations
        if (isset($GLOBALS['_PNSession']['regenerated']) && $GLOBALS['_PNSession']['regenerated'] == true) {
            return;
        }

        $GLOBALS['_PNSession']['sessid_old'] = session_id(); // save old session id


        if (session_regenerate_id()) {
            // need to handle php < 4.3.3 bug that doesnt issue
            // session cookie to browser after regeneration [drak]
            if (!version_compare(phpversion(), '4.3.3', '>=')) {
                setcookie(session_name(), session_id(), time() + ini_get('session.cookie_lifetime'), ini_get('session.cookie_path'), ini_get('session.cookie_domain'), (pnServerGetProtocol() == 'https' ? true : false));
            }
        }

        $GLOBALS['_PNSession']['obj']['sessid'] = session_id(); // commit new sessid
        $GLOBALS['_PNSession']['regenerated'] = true; // flag regeneration
        return;
    }

    /**
     * Regenerate session according to probability set by admin
     *
     */
    function random_regenerate()
    {
        if (!pnConfigGetVar('sessionrandregenerate')) {
            return;
        }

        $chance = 100 - pnConfigGetVar('sessionregeneratefreq');
        $a = rand(0, $chance);
        $b = rand(0, $chance);
        if ($a == $b) {
            SessionUtil::regenerate();
        }
    }

    /**
     * Define the name of our session cookie
     *
     * @access private
     */
    function getCookieName()
    {
        // Include number of dots in session name such that we use a different session for
        // www.domain.xx and domain.xx. Otherwise we run into problems with both cookies for
        // www.domain.xx as well as domain.xx being sent to www.domain.xx simultaneously!
        $hostNameDotCount = substr_count(pnGetHost(), '.');
        return pnConfigGetVar('sessionname') . $hostNameDotCount;
    }
}

// emulate session_regenerate_id function if missing
if (!function_exists('session_regenerate_id')) {
    /**
     * Regenerate session
     *
     * @return string session_id
     */
    function session_regenerate_id()
    {
        $len = (ini_get('session.hash_function') == 0) ? 32 : 40;
        return (bool) session_id(RandomUtil::getString($len, $len, false, false, true, false, true, false, false));
    }
}

/* Following _Session__* API are for internal class use.  Do not call directly */

/**
 * PHP function to start the session
 *
 * @access private
 * @return bool true
 */
function _SessionUtil__Start($path, $name)
{
    // Nothing to do
    return true;
}

/**
 * PHP function to close the session
 *
 * @access private
 * @return bool true
 */
function _SessionUtil__Close()
{
    // nothing to do
    return true;
}

/**
 * PHP function to read a set of session variables
 *
 * @access private
 * @param string $sessid session id
 * @return mixed bool of false or string session variable if true
 */
function _SessionUtil__Read($sessid)
{
    // if (pnConfigGetVar('anonymoussessions') == '0') {
    if (pnConfigGetVar('sessionstoretofile')) {
        $path = DataUtil::formatForOS(session_save_path());
        if (file_exists("$path/$sessid")) {
            $result = file_get_contents("$path/$sessid");
            if ($result) {
                $result = unserialize($result);
            }
        }
    } else {
        $result = DBUtil::selectObjectByID('session_info', $sessid, 'sessid');
        if (!$result) {
            return false;
        }
    }

    if (is_array($result) && isset($result['sessid'])) {
        $GLOBALS['_PNSession']['obj'] = array('sessid' => $result['sessid'], 'ipaddr' => $result['ipaddr'], 'uid' => $result['uid'], 'lastused' => $result['lastused']);
    }

    // security feature to change session id's periodically
    SessionUtil::random_regenerate();

    return (isset($result['vars']) ? $result['vars'] : '');
}

/**
 * PHP function to write a set of session variables
 *
 * DO NOT CALL THIS DIRECTLY use session_write_close()
 *
 * @access private
 * @param string $sessid session id
 * @param string $vars session variables
 * @return bool
 */
function _SessionUtil__Write($sessid, $vars)
{
    $obj = $GLOBALS['_PNSession']['obj'];
    $obj['vars'] = $vars;
    $obj['remember'] = (SessionUtil::getVar('rememberme') ? SessionUtil::getVar('rememberme') : 0);
    $obj['uid'] = (SessionUtil::getVar('uid') ? SessionUtil::getVar('uid') : 0);
    $obj['lastused'] = date('Y-m-d H:i:s', time());

    if (pnConfigGetVar('sessionstoretofile')) {
        $path = DataUtil::formatForOS(session_save_path());

        // if session was regenerate, delete it first
        if (isset($GLOBALS['_PNSession']['regenerated']) && $GLOBALS['_PNSession']['regenerated'] == true) {
            unlink("$path/$sessid");
        }

        // now write session
        if ($fp = @fopen("$path/$sessid", "w")) {
            $res = fwrite($fp, serialize($obj));
            fclose($fp);
        } else {
            return false;
        }
    } else {
        if (isset($GLOBALS['_PNSession']['new']) && $GLOBALS['_PNSession']['new'] == true) {
            $res = DBUtil::insertObject($obj, 'session_info', 'sessid', true);
            unset($GLOBALS['_PNSession']['new']);
        } else {
            // check for regenerated session and update ID in database
            if (isset($GLOBALS['_PNSession']['regenerated']) && $GLOBALS['_PNSession']['regenerated'] == true) {
                $sessiontable = pnDBGetTables();
                $columns = $sessiontable['session_info_column'];
                $where = "WHERE $columns[sessid] = '" . DataUtil::formatForStore($GLOBALS['_PNSession']['sessid_old']) . "'";
                $res = DBUtil::updateObject($obj, 'session_info', $where, 'sessid', true, true);
            } else {
                $res = DBUtil::updateObject($obj, 'session_info', '', 'sessid', true);
            }
        }
    }

    return (bool) $res;
}

/**
 * PHP function to destroy a session
 *
 * DO NOT CALL THIS FUNCTION DIRECTLY use session_destory();
 *
 * @access private
 * @param string $sessid session id
 * @return bool
 */
function _SessionUtil__Destroy($sessid)
{
    if (isset($GLOBALS['_PNSession'])) {
        unset($GLOBALS['_PNSession']);
    }

    // expire the cookie
    setcookie(session_name(), '', 0, ini_get('session.cookie_path'));

    // can exit if anon user and anon session disabled
    if (pnConfigGetVar('anonymoussessions') == '0' && SessionUtil::getVar('uid') == '0') {
        return true;
    }

    // ensure we delete the stored session (not a regenerated one)
    if (isset($GLOBALS['_PNSession']['regenerated']) && $GLOBALS['_PNSession']['regenerated'] == true) {
        $sessid = $GLOBALS['_PNSession']['sessid_old'];
    } else {
        $sessid = session_id();
    }

    if (pnConfigGetVar('sessionstoretofile')) {
        $path = DataUtil::formatForOS(session_save_path(), true);
        return unlink("$path/$sessid");
    } else {
        $res = DBUtil::deleteObjectByID('session_info', $sessid, 'sessid');
        return (bool) $res;
    }
}

/**
 * PHP function to garbage collect session information
 *
 * @access private
 * @param int $maxlifetime maxlifetime of the session
 * @return bool
 */
function _SessionUtil__GC($maxlifetime)
{
    $now = time();
    $inactive = ($now - (int) (pnConfigGetVar('secinactivemins') * 60));
    $daysold = ($now - (int) (pnConfigGetVar('secmeddays') * 86400));

    // find the hash length dynamically
    $hash = ini_get('session.hash_function');
    if (empty($hash) || $hash == 0) {
        $sessionlength = 32;
    } else {
        $sessionlength = 40;
    }

    if (pnConfigGetVar('sessionstoretofile')) {
        // file based GC
        $path = DataUtil::formatForOS(session_save_path(), true);
        // get files
        $files = array();
        if ($handle = opendir($path)) {
            while (false !== ($file = readdir($handle))) {
                if ($file != '.' && $file != '..' && strlen($file) == $sessionlength) {
                    // filename, created, last modified
                    $file = "$path/$file";
                    $files[] = array('name' => $file, 'lastused' => filemtime($file));
                }
            }
        }

        // check we have something to do
        if (count($files) == 0) {
            return true;
        }

        // do GC
        switch (pnConfigGetVar('seclevel')) {
            case 'Low':
                // Low security - delete session info if user decided not to
                //                remember themself and session is inactive
                foreach ($files as $file) {
                    $name = $file['name'];
                    $lastused = $file['lastused'];
                    $session = unserialize(file_get_contents($name));
                    if ($lastused < $inactive && !isset($session['PNSVrememberme'])) {
                        unlink($name);
                    }
                }
                break;
            case 'Medium':
                // Medium security - delete session info if session cookie has
                // expired or user decided not to remember themself and inactivity timeout
                // OR max number of days have elapsed without logging back in
                foreach ($files as $file) {
                    $name = $file['name'];
                    $lastused = $file['lastused'];
                    $session = unserialize(file_get_contents($name));
                    if ($lastused < $inactive && !isset($session['PNSVrememberme'])) {
                        unlink($name);
                    } else if (($lastused < $daysold)) {
                        unlink($name);
                    }
                }
                break;
            case 'High':
                // High security - delete session info if user is inactive
                foreach ($files as $file) {
                    $name = $file['name'];
                    $lastused = $file['lastused'];
                    if ($lastused < $inactive) {
                        unlink($name);
                    }
                }
                break;
        }
        return true;

    } else {
        // DB based GC
        $pntable = pnDBGetTables();
        $sessioninfocolumn = $pntable['session_info_column'];
        $inactive = DataUtil::formatForStore(date('Y-m-d H:i:s', $inactive));
        $daysold = DataUtil::formatForStore(date('Y-m-d H:i:s', $daysold));

        switch (pnConfigGetVar('seclevel')) {
            case 'Low':
                // Low security - delete session info if user decided not to
                //                remember themself and inactivity timeout
                $where = "WHERE $sessioninfocolumn[remember] = 0
                          AND $sessioninfocolumn[lastused] < '$inactive'";
                break;
            case 'Medium':
                // Medium security - delete session info if session cookie has
                // expired or user decided not to remember themself and inactivity timeout
                // OR max number of days have elapsed without logging back in
                $where = "WHERE ($sessioninfocolumn[remember] = 0
                          AND $sessioninfocolumn[lastused] < '$inactive')
                          OR ($sessioninfocolumn[lastused] < '$daysold')
                          OR ($sessioninfocolumn[uid] = 0 AND $sessioninfocolumn[lastused] < '$inactive')";
                break;
            case 'High':
            default:
                // High security - delete session info if user is inactive
                $where = "WHERE $sessioninfocolumn[lastused] < '$inactive'";
                break;
        }

        $res = DBUtil::deleteWhere('session_info', $where);
        return (bool) $res;
    }
}

/* Legacy APIs to be removed at a later date */

/**
 * Get a session variable
 *
 * @deprecated
 * @see SessionUtil::getVar
 * @param sring $name of the session variable to get
 * @param string $default the default value to return if the requested session variable is not set
 * @return string session variable requested
 */
function pnSessionGetVar($name, $default = false)
{
    return SessionUtil::getVar($name, $default);
}

/**
 * Set a session variable
 *
 * @deprecated
 * @see SessionUtil::setVar
 * @param string $name of the session variable to set
 * @param value $value to set the named session variable
 * @return bool true
 */
function pnSessionSetVar($name, $value)
{
    return SessionUtil::setVar($name, $value);
}

/**
 * Delete a session variable
 *
 * @deprecated
 * @see SessionUtil::delVar
 * @param string $name of the session variable to delete
 * @return bool true
 */
function pnSessionDelVar($name)
{
    return SessionUtil::delVar($name);
}
