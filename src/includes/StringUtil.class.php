<?php
/**
 * Zikula Application Framework
 *
 * @copyright Zikula Development Team
 * @link http://www.zikula.org
 * @version $Id: StringUtil.class.php 27362 2009-11-02 15:45:10Z mateo $
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @author Robert Gasch rgasch@gmail.com
 * @package Zikula_Core
 * @subpackage StringUtil
 */

/**
 * StringUtil
 *
 * @package Zikula_Core
 * @subpackage StringUtil
 */
class StringUtil
{
    /**
     * Count the instances of needle in the given string
     *
     * Why is this function here? PHP has a builtin substr_count()
     * to do the same.
     *
     * @param haystack  the string to search
     * @param needle    the needle to search for and count
     *
     * @return The numer of instances of needle in string
     */
    function countInstances($haystack, $needle)
    {
        return mb_substr_count($haystack, $needle);
    }

    /**
     * Truncate a string to a certain length
     *
     * @param string       the string to operate on
     * @param limit        the maximum number of characters displayed (optional) (default=80)
     * @param appendDots   whether or not to append '...' to the maximum number of characters displayed (optional) (default=80)
     *
     * @return The potentially truncated string
     */
    function getTruncatedString($string, $limit = 80, $appendDots = true)
    {
        $len = strlen($string);

        if ($len > $limit) {
            $string = mb_substr($string, 0, $limit);

            if ($appendDots)
                $string .= '...';
        }

        return $string;
    }

    /**
     * Translate html input newlines to <br /> sequences.
     * This function is necessary as inputted strings will contain
     * "\n\r" instead of just "\n"
     *
     * @param string    the string to operate on
     *
     * @return The converted string
     */
    function nl2html($string)
    {
        $str = str_replace("\n", '<br />', $string);
        $str = str_replace("\r", '', $str);

        return $str;
    }

    /**
     * Tokenize a string according to the given parameters.
     * This function just wraps explode to provide a more java-similar syntax
     *
     * @param string     the string to tokenize
     * @param delimeter  the delimeter to use
     * @param max        the maximal number of tokens to generate (optional) (default=999999)
     *
     * @return The token array
     */
    function tokenize($string, $delimeter, $max = 999999)
    {
        return explode($delimeter, $string, $max);
    }

    /**
     * Case-Insensitive version of strpos (standard only available in PHP 5)
     *
     * @param haystack  the string to search
     * @param needle    the string to search for
     * @param offset    the search start offset position (optional) (default=0)
     *
     * @return The token array
     */
    //if (!function_exists("stripos")) {
    function stripos($haystack, $needle, $offset = 0)
    {
        return mb_strpos(mb_strtoupper($haystack), mb_strtoupper($needle), $offset);
    }
    //}


    /**
     * Returns the left x chars of a string. If the string is longer than x,
     * the whole string is returned
     *
     * @param string       the string to operate on
     * @param left         the number of chars to return
     *
     * @return a part of the supplied string
     */
    function left($string, $left = 0)
    {
        $len = mb_strlen($string);
        if ($len > $left) {
            $string = mb_substr($string, 0, $left);
        }
        return $string;
    }

    /**
     * Returns the right x chars of a string. If the string is longer than x,
     * the whole string is returned
     *
     * @param string       the string to operate on
     * @param right        the number of chars to return
     *
     * @return a part of the supplied string
     */
    function right($string, $right = 0)
    {
        $len = mb_strlen($string);
        if ($len > $right) {
            $string = mb_substr($string, $len - $right, $right);
        }
        return $string;
    }

    function highlightWords($text, $wordStr, $contextSize = 200)
    {
        // Strip HTML tags and special chars completely
        $text = strip_tags($text);
        $text = html_entity_decode($text);

        // Split words into word array
        $words = preg_split('/ /', $wordStr, -1, PREG_SPLIT_NO_EMPTY);

        $firstMatch = true;

        $i = 1;
        foreach ($words as $word) {
            $text = str_replace($word, '<strong class="highlight' . ($i % 10) . '">' . $word . '</strong>', $text);
            ++$i;
        }
        $completeReplacedText = nl2br($text);

        // Find first position of first "<" (which is the first highlighted word)
        $startPos = mb_strpos($completeReplacedText, "<");
        if ($startPos === false) {
            $startPos = 0;
        }
        $startPos = max(0, $startPos - $contextSize / 2);
        while ($startPos > 0 && $completeReplacedText[$startPos] != ' ') {
            --$startPos;
        }

        // Find last position of ">" (last highlighted word).
        $endPos = mb_strrpos($completeReplacedText, ">");
        if ($endPos === false) {
            $endPos = $contextSize;
        }
        $endPos = min(mb_strlen($completeReplacedText), $endPos + $contextSize / 2);
        while ($endPos < mb_strlen($completeReplacedText) - 1 && $completeReplacedText[$endPos] != ' ') {
            ++$endPos;
        }

        return mb_substr($completeReplacedText, $startPos, $endPos - $startPos);
    }
}
