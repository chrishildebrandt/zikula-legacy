<?php
/**
 * Zikula Application Framework
 *
 * @copyright  (c) Zikula Development Team
 * @link       http://www.zikula.org
 * @version    $Id: function.gt.php 28082 2010-01-09 08:11:51Z drak $
 * @license    GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @category   Zikula_Core
 * @package    System_Modules
 * @subpackage Theme
 */

/**
 * Smarty function to use the PHP dgettext() function
 *
 * This function takes a identifier and returns the corresponding language constant.
 *
 * Available parameters:
 *   - text:     (required) string to translate
 *   - plural:   (optional) plural version of the string
 *   - count:    (optional) if we have plural we need to specify the count
 *   - tagN:     (optional) replace for sprintf() e.g. %s or %1$s
 *   - domain:   (optional) textdomain to be used (not required, the system will fill this out automatically
 *   - comment:  (optional) comment to the translator (this is not processed by this code)
 *   - assign:   If set, the results are assigned to the corresponding variable instead of printed out
 *
 * Examples
 * <!--[gt text="Hello world"]-->
 * <!--[gt text="Hello %s" tag1=$name]-->
 * <!--[gt text="You want one cup" plural="You want two cups" count=2]-->
 * <!--[gt text='Hello %1$s, welcome to %2$s' tag1=$city tag2=$country comment="%1 is a name %2 is the place"]-->
 * ## WARNING! When using %1$s in a template, smarty compiles this to PHP so the string must be in single quotes or
 * ## the $s will be evaluated as variable $s
 *
 *
 * String replacement follows the rules at http://php.net/sprintf but please note Smarty seems to pass
 * all variables as strings so %s and %n$s are mostly used.
 *
 * @author       Bernd Plagge
 * @author       Drak
 * @param        array       $params      All attributes passed to this function from the template
 * @param        object      &$smarty     Reference to the Smarty object
 * @return       string      the language constant
 */
function smarty_function_gt($params, &$smarty)
{
    // the check order here is important because:
    // if we are calling from a theme both $smarty->themeDomain and $smarty->renderDomain are set.
    // if the call was from a template only $smarty->renderDomain is set.
    if (isset($params['domain'])) {
        $domain = (strtolower($params['domain']) == 'zikula' ? null : $params['domain']);
    } elseif (isset($smarty->themeDomain)) {
        $domain = $smarty->themeDomain;
    } elseif (isset($smarty->renderDomain)) {
        $domain = $smarty->renderDomain;
    } else {
        $domain = null; // default domain
    }

    $domain = ($domain == 'zikula' ? null : $domain);

    if (!isset($params['text'])) {
        $smarty->trigger_error(__f('Error! in %1$s: the %2$s parameter must be specified.', array('smarty_function_gt', 'text')));
        return false;
    }
    $text = $params['text'];

    // validate plural settings if applicable
    if ((!isset($params['count']) && isset($params['plural'])) || (isset($params['count']) && !isset($params['plural']))) {
        $smarty->trigger_error(__('Error! If you use a plural or count in gettext, you must use both parameters together.'));
        return false;
    }

    $count = (isset($params['count']) ? (int)$params['count'] : 0);
    $plural = (isset($params['plural']) ? $params['plural'] : false);

    // build array for tags (for %s, %1$s etc) if applicable
    ksort($params);
    $tags = array();
    foreach ($params as $key => $value) {
        if (preg_match('#^tag([0-9]{1,2})$#', $key)) {
            $tags[] = $value;
        }
    }
    $tags = (count($tags) == 0 ? null : $tags);

    // perform gettext
    if ($plural) {
        $result = (isset($tags) ? _fn($text, $plural, $count, $tags, $domain) : _n($text, $plural, $count, $domain));
    } else {
        $result = (isset($tags) ? __f($text, $tags, $domain) : __($text, $domain));
    }

    // assign or return
    if (isset($params['assign'])) {
        $smarty->assign($params['assign'], $result);
    } else {
        return $result;
    }
}
