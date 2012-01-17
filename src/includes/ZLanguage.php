<?php
/**
 * Zikula Application Framework
 *
 * @copyright Zikula Development Team
 * @link http://www.zikula.org
 * @version $Id: ZLanguage.php 26071 2009-07-27 08:15:38Z drak $
 * @license GNU/LGPL - http://www.gnu.org/copyleft/lgpl.html
 * @author Drak drak@zikula.org
 * @package Zikula_Core

 **
 * ZLanguage
 *
 * This is deliberately written for PHP4 compatability.
 * Please do not convert to PHP5 when merged into 2.0 for the time being until
 * everything is stablised.  After that it can be restructured to PHP5 without any fuss
 *
 * Properties and methods are marked for later conversion in 2.0
 */
class ZLanguage
{
    // public properties
    var $langRequested;
    var $langSession;
    var $langDetect;
    var $langSystemDefault;
    var $langFixSession;
    var $dbCharset;
    var $encoding;
    var $languageCodeLegacy;
    var $languageCode;
    var $browserLanguagePref;
    var $domainCache;
    var $multiLingualCapable;
    var $langUrlRule;
    var $errors = array();
    var $locale = false;
    var $i18n; // object

    // public function __construct() { }


    function ZLanguage()
    {
        $this->langSession = SessionUtil::getVar('language', null);
        $this->langSystemDefault = pnConfigGetVar('language_i18n', 'en');
        $this->langFixSession = preg_replace('#[^a-z-].#', '', FormUtil::getPassedValue('setsessionlanguage', null, 'POST'));
        $this->multiLingualCapable = pnConfigGetVar('multilingual');
        $this->langUrlRule = pnConfigGetVar('languageurl', 0);
        $this->langDetect = pnConfigGetVar('language_detect', 0);
        $this->setDBCharset();
        $this->setEncoding();
    }

    // private
    function setup()
    {
        $this->handleLegacy();
        $this->langRequested = preg_replace('#[^a-z-].#', '', FormUtil::getPassedValue('lang', null, 'GET')); // language for this request
        $this->detectLanguage();
        $this->validate();
        $this->fixLanguageToSession();
        $this->setlocale($this->languageCode);
        $this->bindCoreDomain();
        $this->loadLegacyDefines();
        $this->processErrors();
    }

    // public static
    function &getInstance()
    {
        static $instance;
        if (!isset($instance)) {
            $instance = new ZLanguage();
        }
        return $instance;
    }

    //private
    function handleLegacy()
    {
        // TODO D [deprecate along with ZLanguage::handleLegacy when language defines deprecate] (drak)
        $newlang = FormUtil::getPassedValue('newlang', null, 'GET');
        if ($newlang) {
            $lang = ZLanguage::translateLegacyCode($newlang);
            $lang = ($lang ? $lang : '');
            pnQueryStringSetVar('lang', $lang);
            unset($_REQUEST['newlang']);
            unset($_POST['newlang']);
        }
    }

    // static
    function lookupLegacyCode($lookup)
    {
        $map = cnvlanguagelist();
        return (isset($map[$lookup]) ? $map[$lookup] : 'eng');

    }

    // private
    function fixLanguageToSession()
    {
        if ($this->langFixSession) {
            SessionUtil::setVar('language', $this->languageCode);
        }
    }

    // private
    function detectLanguage()
    {
        if ($this->langFixSession) {
            $this->langRequested = $this->langFixSession;
        }

        if (!$this->multiLingualCapable) {
            // multi lingual option is disabled only set system language
            if ($this->langRequested) {
                if ($this->langRequested != $this->langSystemDefault) {
                    // can't directly issue error yet since we haven't initialised gettext yet
                    $this->registerError(__f("Error! Multi-lingual functionality is not enabled. This page cannot be displayed in %s language.", $this->langRequested));
                }
            }
            $this->languageCode = $this->langSystemDefault;
            return;
        }

        if ($this->langRequested) {
            $this->languageCode = $this->langRequested;
        } else {
            if ($this->langSession) {
                $this->languageCode = $this->langSession;
            } elseif ($this->langDetect) {
                if ($this->discoverBrowserPrefs()) {
                    $this->languageCode = $this->browserLanguagePref;
                } else {
                    $this->languageCode = $this->langSystemDefault;
                }
            } else {
                $this->languageCode = $this->langSystemDefault;
            }
        }
    }

    // private
    function validate()
    {
        $available = $this->getInstalledLanguages();
        if (!in_array($this->languageCode, $available)) {
            $this->registerError(__f("Error! The requested language %s is not available.", $this->languageCode));
            $this->languageCode = $this->langSystemDefault;
        }
    }

    function registerError($msg)
    {
        $this->errors[] = array($msg);
    }

    function processErrors()
    {
        if (count($this->errors) == 0) {
            return;
        }

        // fatal errors require 404
        header('HTTP/1.1 404 Not Found');
        foreach ($this->errors as $error) {
            LogUtil::registerError($error);
        }
    }

    // public static
    function setLocale($locale, $lc = LC_ALL)
    {
        $_this = & ZLanguage::getInstance();
        $_this->languageCode = $locale; // on purpose
        $_this->languageCodeLegacy = $_this->lookupLegacyCode($_this->languageCode);
        $_this->locale = ZLanguage::transformInternal(_setlocale($lc, ZLanguage::transformFS($locale)));
        $_this->i18n = & ZI18n::getInstance($locale);
    }

    //public static
    function getLocale()
    {
        $_this = & ZLanguage::getInstance();
        return $_this->locale;
    }

    // private
    function setTextDomain()
    {
        _textdomain('zikula');
    }

    // public static
    function getLanguageCode()
    {
        $_this = & ZLanguage::getInstance();
        return $_this->languageCode;
    }

    // public static
    function getLanguageCodeLegacy()
    {
        $_this = & ZLanguage::getInstance();
        return $_this->languageCodeLegacy;
    }

    // public static
    function getDBCharset()
    {
        $_this = & ZLanguage::getInstance();
        return $_this->dbCharset;
    }

    //public static
    function getEncoding()
    {
        $_this = & ZLanguage::getInstance();
        return $_this->encoding;
    }

    // public static
    function bindDomain($domain, $path)
    {
        $_this = & ZLanguage::getInstance();
        $locale = $_this->getLocale();

        // exit if the language system hasnt yet fully initialised
        if (!$locale) {
            return;
        }

        // prevent double loading
        if (isset($_this->domainCache[$locale][$domain])) {
            return true;
        }

        _bindtextdomain($domain, $path);
        _bind_textdomain_codeset($domain, $_this->encoding);

        return $_this->domainCache[$locale][$domain];
    }

    // public static
    function bindThemeDomain($themeName)
    {
        $_this  = ZLanguage::getInstance();
        $domain = ZLanguage::getThemeDomain($themeName);
        return ZLanguage::bindDomain($domain, $_this->searchOverrides($domain, "themes/$themeName/locale"));
    }

    // public static
    function bindModuleDomain($moduleName)
    {
        // system modules are in the zikula domain
        $module = pnModGetInfo(pnModGetIDFromName($moduleName));
        if ($module['type'] == '3') {
            return 'zikula';
        }

        $_this  = ZLanguage::getInstance();
        $domain = ZLanguage::getModuleDomain($moduleName);
        return ZLanguage::bindDomain($domain, $_this->searchOverrides($domain, "modules/$moduleName/locale"));
    }

    //public static
    function bindCoreDomain()
    {
        $_this = & ZLanguage::getInstance();
        $_this->bindDomain('zikula', $_this->searchOverrides('zikula', 'locale')); // bind system domain
        $_this->setTextDomain('zikula');
    }

    function searchOverrides($domain, $path)
    {
        $lang = ZLanguage::transformFS($this->languageCode);
        $prefix = realpath(realpath(dirname(__FILE__)) . DIRECTORY_SEPARATOR . '..');
        $override = "$prefix/config/locale/$lang/LC_MESSAGES/$domain.mo";
        return (is_readable($override) ? "$prefix/config/locale" : "$prefix/$path");
    }

    // public static
    function getModuleDomain($moduleName)
    {
        return strtolower("module_$moduleName");
    }

    // public static
    function getThemeDomain($themeName)
    {
        return strtolower("theme_$themeName");
    }

    // public static
    function getLangUrlRule()
    {
        $_this = & ZLanguage::getInstance();
        return $_this->langUrlRule;
    }

    // public static
    function isRequiredLangParam()
    {
        $_this = & ZLanguage::getInstance();
        if ($_this->langUrlRule) {
            // always append
            return true;
        } else {
            // append only when current language and system language are different
            return (($_this->langSystemDefault != $_this->languageCode) ? true : false);
        }
    }

    // private
    function discoverBrowserPrefs()
    {
        $available = $this->getInstalledLanguages();
        $detector = new ZLanguageBrowser($available);
        $this->browserLanguagePref = $detector->discover();
        return $this->browserLanguagePref;
    }

    // public
    function loadLegacyDefines()
    {
        loadLegacyLanguageDefines($this->languageCodeLegacy); //$this->lang);
        languagelist(); // old lang refine list

        // TODO A [hunt down legacy dependents and migrate where possible] (drak)
        define('_LOCALE', $this->languageCode);
        define('_LOCALEWIN', $this->languageCodeLegacy);
        define('_CHARSET', $this->encoding);
        define('_DATELONG', 'datelong');
        define('_DATEBRIEF', 'datebrief');
        define('_DATESTRING', 'datestring');
        define('_DATESTRING2', 'datestring2');
        define('_DATETIMEBRIEF', 'datetimebrief');
        define('_DATETIMELONG', 'datetimelong');
        define('_TIMEBRIEF', 'timebrief');
        define('_TIMELONG', 'timelong');

        // special cases
        define('_DATEINPUT', '%Y-%m-%d'); // Dateformat for input fields (parsable - do not try other formats!)
        define('_DATETIMEINPUT', '%Y-%m-%d %H:%M'); // Date+time format for input fields (parsable - do not try other formats!)
    }

    /**
     * Get array of installed languages by code
     * @return array
     */
    function getInstalledLanguages()
    {
        static $localeArray;

        if (isset($localeArray)) {
            return $localeArray;
        }

        Loader::loadClass('FileUtil');
        $languageVariations = pnConfigGetVar('language_bc');
        // search for locale and config overrides
        $localeArray = array();
        $search = array('config/locale', 'locale');
        foreach ($search as $k) {
            // get only the directories of the search paths
            $locales = FileUtil::getFiles($k, false, true, null, 'd');
            foreach ($locales as $locale) {
                $code = ZLanguage::transformInternal($locale);
                if ($languageVariations) {
                    $localeArray[] = $code;
                } else if (!strpos($locale, '-')) {
                    $localeArray[] = $code;
                }
            }
        }
        return $localeArray;
    }

    /**
     * get array of language names by code
     * @return associative array
     */
    function getInstalledLanguageNames()
    {
        $locales = ZLanguage::getInstalledLanguages();
        foreach ($locales as $locale) {
            $name = ZLanguage::getLanguageName($locale);
            if ($name) {
                $languagesArray[$locale] = $name;
            }
        }
        return $languagesArray;
    }

    //private
    function setEncoding()
    {
        if (preg_match('#utf([-]{0,1})8#', $this->dbCharset)) {
            $this->encoding = 'utf-8';
            return;
        } elseif (preg_match('#^latin([0-9]{1,2})#', $this->dbCharset)) {
            $this->encoding = preg_replace('#latin([0-9]{1,2})#', 'iso-8859-$1', $this->dbCharset);
            return;
        } elseif (defined('_PNINSTALLVER')) {
            $this->encoding = 'utf-8';
        } else {
            $this->registerError(__f("Error! Could not set encoding based on database character set '%s'.", $this->dbCharset));
        }
    }

    //private
    function setDBCharset()
    {
        $this->dbCharset = (defined('_PNINSTALLVER') ? 'utf8' : strtolower(DBConnectionStack::getConnectionInfo('default', 'dbcharset')));
    }

    //public static
    function isLangParam($lang)
    {
        $_this = & ZLanguage::getInstance();
        if ($_this->langUrlRule) {
            return true;
        } else {
            // check if it LOOKS like a language param
            if (preg_match('#(^[a-z]{2,3}$)|(^[a-z]{2,3}-[a-z]{2,3}$)|(^[a-z]{2,3}-[a-z]{2,3}-[a-z]{2,3}$)#', $lang)) {
                return true;
            }
        }
        return false;
    }

    //public static
    function getDirection()
    {
        $_this = & ZLanguage::getInstance();
        return $_this->i18n->locale->getLanguage_direction();
    }

    /**
     * translate old lang requests into new code
     *
     * TODO D [deprecate along with ZLanguage::hangleLegacy when language defines deprecate] (drak)
     * @param string legacy language $code
     * @return string language code
     */
    //public static
    function translateLegacyCode($code)
    {
        $map = ZLanguage::legacyCodeMap();
        return (isset($map[$code]) ? $map[$code] : false);
    }

    // public static
    function transformInternal($m)
    {
        return preg_replace('/_/', '-', strtolower($m));
    }

    // public static
    function transformFS($m)
    {
        $lang = substr($m, 0, (strpos($m, '-') ? strpos($m, '-') : strlen($m)));
        $country = ($lang != $m ? strtoupper(str_replace("$lang-", '', $m)) : false);
        return ($country ? "{$lang}_{$country}" : $lang);
    }

    function getLegacyLanguageName($language)
    {
        $map = ZLanguage::legacyMap();
        if (isset($map[$language])) {
            return $map[$language];
        }

        // strange behaviour but required for legacy
        return false;
    }

    function getCountryName($country)
    {
        $country = strtolower($country);
        $map = ZLanguage::countryMap();
        if (isset($map[$country])) {
            return $map[$country];
        }

        // strange behaviour but required for legacy
        return false;
    }

    function getLanguageName($language)
    {
        $language = ZLanguage::transformInternal($language);
        $map = ZLanguage::languageMap();
        if (isset($map[$language])) {
            return $map[$language];
        }

        // strange behaviour but required for legacy
        return false;
    }

    // map of l3 legacy code to name
    function legacyMap()
    {
        return array(
            'aar' => __('Afar'),
            'abk' => __('Abkhazian'),
            'ave' => __('Avestan'),
            'afr' => __('Afrikaans'),
            'aka' => __('Akan'),
            'amh' => __('Amharic'),
            'arg' => __('Aragonese'),
            'ara' => __('Arabic'),
            'asm' => __('Assamese'),
            'ava' => __('Avaric'),
            'aym' => __('Aymara'),
            'aze' => __('Azerbaijani'),
            'bak' => __('Bashkir'),
            'bel' => __('Belarusian'),
            'bul' => __('Bulgarian'),
            'bih' => __('Bihari'),
            'bis' => __('Bislama'),
            'bam' => __('Bambara'),
            'ben' => __('Bengali'),
            'bod' => __('Tibetan'),
            'bre' => __('Breton'),
            'bos' => __('Bosnian'),
            'cat' => __('Catalan'),
            'che' => __('Chechen'),
            'cha' => __('Chamorro'),
            'cos' => __('Corsican'),
            'cre' => __('Cree'),
            'ces' => __('Czech'),
            'chu' => __('Church Slavic'),
            'chv' => __('Chuvash'),
            'cym' => __('Welsh'),
            'dan' => __('Danish'),
            'deu' => __('German'),
            'div' => __('Divehi'),
            'dzo' => __('Dzongkha'),
            'ewe' => __('Ewe'),
            'ell' => __('Greek'),
            'eng' => __('English'),
            'epo' => __('Esperanto'),
            'spa' => __('Spanish'),
            'est' => __('Estonian'),
            'eus' => __('Basque'),
            'fas' => __('Persian'),
            'ful' => __('Fulah'),
            'fin' => __('Finnish'),
            'fij' => __('Fijian'),
            'fao' => __('Faroese'),
            'fra' => __('French'),
            'fry' => __('Frisian'),
            'gle' => __('Irish'),
            'gla' => __('Scottish Gaelic'),
            'glg' => __('Galician'),
            'grn' => __('Guarani'),
            'guj' => __('Gujarati'),
            'glv' => __('Manx'),
            'hau' => __('Hausa'),
            'haw' => __('Hawaiian'),
            'heb' => __('Hebrew'),
            'hin' => __('Hindi'),
            'hmo' => __('Hiri Motu'),
            'hrv' => __('Croatian'),
            'hat' => __('Haitian'),
            'hun' => __('Hungarian'),
            'hye' => __('Armenian'),
            'her' => __('Herero'),
            'ina' => __('Interlingua'),
            'ind' => __('Indonesian'),
            'ile' => __('Interlingue'),
            'ibo' => __('Igbo'),
            'iii' => __('Sichuan Yi'),
            'ipk' => __('Inupiaq'),
            'ido' => __('Ido'),
            'isl' => __('Icelandic'),
            'ita' => __('Italian'),
            'iku' => __('Inuktitut'),
            'jav' => __('Javanese'),
            'jpn' => __('Japanese'),
            'kat' => __('Georgian'),
            'kon' => __('Kongo'),
            'kik' => __('Kikuyu'),
            'kua' => __('Kwanyama'),
            'kaz' => __('Kazakh'),
            'kal' => __('Kalaallisut'),
            'khm' => __('Khmer'),
            'kan' => __('Kannada'),
            'kor' => __('Korean'),
            'kau' => __('Kanuri'),
            'kas' => __('Kashmiri'),
            'kur' => __('Kurdish'),
            'kom' => __('Komi'),
            'cor' => __('Cornish'),
            'kir' => __('Kirghiz'),
            'lat' => __('Latin'),
            'ltz' => __('Luxembourgish'),
            'lug' => __('Ganda'),
            'lim' => __('Limburgish'),
            'lin' => __('Lingala'),
            'lao' => __('Lao'),
            'lit' => __('Lithuanian'),
            'lub' => __('Luba-Katanga'),
            'lav' => __('Latvian'),
            'mlg' => __('Malagasy'),
            'mah' => __('Marshallese'),
            'mri' => __('Maori'),
            'mkd' => __('Macedonian'),
            'mal' => __('Malayalam'),
            'mon' => __('Mongolian'),
            'mar' => __('Marathi'),
            'msa' => __('Malay'),
            'mlt' => __('Maltese'),
            'mya' => __('Burmese'),
            'nau' => __('Nauru'),
            'nob' => __('Norwegian Bokmal'),
            'nde' => __('North Ndebele'),
            'nep' => __('Nepali'),
            'ndo' => __('Ndonga'),
            'nld' => __('Dutch'),
            'nno' => __('Norwegian Nynorsk'),
            'nor' => __('Norwegian'),
            'nbl' => __('South Ndebele'),
            'nav' => __('Navajo'),
            'nya' => __('Chichewa'),
            'oci' => __('Occitan'),
            'oji' => __('Ojibwa'),
            'orm' => __('Oromo'),
            'ori' => __('Oriya'),
            'oss' => __('Ossetian'),
            'pan' => __('Panjabi'),
            'pli' => __('Pali'),
            'pol' => __('Polish'),
            'pus' => __('Pushto'),
            'por' => __('Portuguese'),
            'que' => __('Quechua'),
            'roh' => __('Raeto-Romance'),
            'run' => __('Rundi'),
            'ron' => __('Romanian'),
            'rus' => __('Russian'),
            'kin' => __('Kinyarwanda'),
            'san' => __('Sanskrit'),
            'srd' => __('Sardinian'),
            'snd' => __('Sindhi'),
            'sme' => __('Northern Sami'),
            'sag' => __('Sango'),
            'sin' => __('Sinhalese'),
            'slk' => __('Slovak'),
            'slv' => __('Slovenian'),
            'smo' => __('Samoan'),
            'sna' => __('Shona'),
            'som' => __('Somali'),
            'sqi' => __('Albanian'),
            'srp' => __('Serbian'),
            'ssw' => __('Swati'),
            'sot' => __('Southern Sotho'),
            'sun' => __('Sundanese'),
            'swe' => __('Swedish'),
            'swa' => __('Swahili'),
            'tam' => __('Tamil'),
            'tel' => __('Telugu'),
            'tgk' => __('Tajik'),
            'tha' => __('Thai'),
            'tir' => __('Tigrinya'),
            'tuk' => __('Turkmen'),
            'tgl' => __('Tagalog'),
            'tsn' => __('Tswana'),
            'ton' => __('Tonga'),
            'tur' => __('Turkish'),
            'tso' => __('Tsonga'),
            'tat' => __('Tatar'),
            'twi' => __('Twi'),
            'tah' => __('Tahitian'),
            'uig' => __('Uighur'),
            'ukr' => __('Ukrainian'),
            'urd' => __('Urdu'),
            'uzb' => __('Uzbek'),
            'ven' => __('Venda'),
            'vie' => __('Vietnamese'),
            'vol' => __('Volapuk'),
            'wln' => __('Walloon'),
            'wol' => __('Wolof'),
            'xho' => __('Xhosa'),
            'yid' => __('Yiddish'),
            'yor' => __('Yoruba'),
            'zha' => __('Zhuang'),
            'zho' => __('Chinese'),
            'zul' => __('Zulu'));
    }

    // map of l2 country names
    function countryMap()
    {
        return array(
            'ad' => __('Andorra'),
            'ae' => __('United Arab Emirates'),
            'af' => __('Afghanistan'),
            'ag' => __('Antigua & Barbuda'),
            'ai' => __('Anguilla'),
            'al' => __('Albania'),
            'am' => __('Armenia'),
            'an' => __('Netherlands Antilles'),
            'ao' => __('Angola'),
            'aq' => __('Antarctica'),
            'ar' => __('Argentina'),
            'as' => __('American Samoa'),
            'at' => __('Austria'),
            'au' => __('Australia'),
            'aw' => __('Aruba'),
            'az' => __('Azerbaijan'),
            'ba' => __('Bosnia and Herzegovina'),
            'bb' => __('Barbados'),
            'bd' => __('Bangladesh'),
            'be' => __('Belgium'),
            'bf' => __('Burkina Faso'),
            'bg' => __('Bulgaria'),
            'bh' => __('Bahrain'),
            'bi' => __('Burundi'),
            'bj' => __('Benin'),
            'bm' => __('Bermuda'),
            'bn' => __('Brunei Darussalam'),
            'bo' => __('Bolivia'),
            'br' => __('Brazil'),
            'bs' => __('Bahama'),
            'bt' => __('Bhutan'),
            'bv' => __('Bouvet Island'),
            'bw' => __('Botswana'),
            'by' => __('Belarus'),
            'bz' => __('Belize'),
            'ca' => __('Canada'),
            'cc' => __('Cocos (Keeling) Islands'),
            'cf' => __('Central African Republic'),
            'cg' => __('Congo'),
            'ch' => __('Switzerland'),
            'ci' => __("Cote d'Ivoire (Ivory Coast)"),
            'ck' => __('Cook Islands'),
            'cl' => __('Chile'),
            'cm' => __('Cameroon'),
            'cn' => __('China'),
            'co' => __('Colombia'),
            'cr' => __('Costa Rica'),
            'cu' => __('Cuba'),
            'cv' => __('Cape Verde'),
            'cx' => __('Christmas Island'),
            'cy' => __('Cyprus'),
            'cz' => __('Czech Republic'),
            'de' => __('Germany'),
            'dj' => __('Djibouti'),
            'dk' => __('Denmark'),
            'dm' => __('Dominica'),
            'do' => __('Dominican Republic'),
            'dz' => __('Algeria'),
            'ec' => __('Ecuador'),
            'ee' => __('Estonia'),
            'eg' => __('Egypt'),
            'eh' => __('Western Sahara'),
            'er' => __('Eritrea'),
            'es' => __('Spain'),
            'et' => __('Ethiopia'),
            'fi' => __('Finland'),
            'fj' => __('Fiji'),
            'fk' => __('Falkland Islands (Malvinas)'),
            'fm' => __('Micronesia'),
            'fo' => __('Faroe Islands'),
            'fr' => __('France'),
            'fx' => __('France, Metropolitan'),
            'ga' => __('Gabon'),
            'gb' => __('United Kingdom (Great Britain)'),
            'gd' => __('Grenada'),
            'ge' => __('Georgia'),
            'gf' => __('French Guiana'),
            'gh' => __('Ghana'),
            'gi' => __('Gibraltar'),
            'gl' => __('Greenland'),
            'gm' => __('Gambia'),
            'gn' => __('Guinea'),
            'gp' => __('Guadeloupe'),
            'gq' => __('Equatorial Guinea'),
            'gr' => __('Greece'),
            'gs' => __('South Georgia and the South Sandwich Islands'),
            'gt' => __('Guatemala'),
            'gu' => __('Guam'),
            'gw' => __('Guinea-Bissau'),
            'gy' => __('Guyana'),
            'hk' => __('Hong Kong'),
            'hm' => __('Heard & McDonald Islands'),
            'hn' => __('Honduras'),
            'hr' => __('Croatia'),
            'ht' => __('Haiti'),
            'hu' => __('Hungary'),
            'id' => __('Indonesia'),
            'ie' => __('Ireland'),
            'il' => __('Israel'),
            'im' => __('Isle of Man'),
            'in' => __('India'),
            'io' => __('British Indian Ocean Territory'),
            'iq' => __('Iraq'),
            'ir' => __('Islamic Republic of Iran'),
            'is' => __('Iceland'),
            'it' => __('Italy'),
            'jm' => __('Jamaica'),
            'jo' => __('Jordan'),
            'jp' => __('Japan'),
            'ke' => __('Kenya'),
            'kg' => __('Kyrgyzstan'),
            'kh' => __('Cambodia'),
            'ki' => __('Kiribati'),
            'km' => __('Comoros'),
            'kn' => __('St. Kitts and Nevis'),
            'kp' => __("Korea, Democratic People's Republic of"),
            'kr' => __('Korea, Republic of'),
            'kw' => __('Kuwait'),
            'ky' => __('Cayman Islands'),
            'kz' => __('Kazakhstan'),
            'la' => __("Lao People's Democratic Republic"),
            'lb' => __('Lebanon'),
            'lc' => __('Saint Lucia'),
            'li' => __('Liechtenstein'),
            'lk' => __('Sri Lanka'),
            'lr' => __('Liberia'),
            'ls' => __('Lesotho'),
            'lt' => __('Lithuania'),
            'lu' => __('Luxembourg'),
            'lv' => __('Latvia'),
            'ly' => __('Libyan Arab Jamahiriya'),
            'ma' => __('Morocco'),
            'mc' => __('Monaco'),
            'md' => __('Moldova, Republic of'),
            'mg' => __('Madagascar'),
            'mh' => __('Marshall Islands'),
            'mk' => __('Macedonia, the form Republic of'),
            'ml' => __('Mali'),
            'mn' => __('Mongolia'),
            'mm' => __('Myanmar'),
            'mo' => __('Macau'),
            'mp' => __('Northern Mariana Islands'),
            'mq' => __('Martinique'),
            'mr' => __('Mauritania'),
            'ms' => __('Monserrat'),
            'mt' => __('Malta'),
            'mu' => __('Mauritius'),
            'mv' => __('Maldives'),
            'mw' => __('Malawi'),
            'mx' => __('Mexico'),
            'my' => __('Malaysia'),
            'mz' => __('Mozambique'),
            'na' => __('Namibia'),
            'nc' => __('New Caledonia'),
            'ne' => __('Niger'),
            'nf' => __('Norfolk Island'),
            'ng' => __('Nigeria'),
            'ni' => __('Nicaragua'),
            'nl' => __('Netherlands'),
            'no' => __('Norway'),
            'np' => __('Nepal'),
            'nr' => __('Nauru'),
            'nu' => __('Niue'),
            'nz' => __('New Zealand'),
            'om' => __('Oman'),
            'pa' => __('Panama'),
            'pe' => __('Peru'),
            'pf' => __('French Polynesia'),
            'pg' => __('Papua New Guinea'),
            'ph' => __('Philippines'),
            'pk' => __('Pakistan'),
            'pl' => __('Poland'),
            'pm' => __('St. Pierre & Miquelon'),
            'pn' => __('Pitcairn'),
            'pr' => __('Puerto Rico'),
            'pt' => __('Portugal'),
            'pw' => __('Palau'),
            'py' => __('Paraguay'),
            'qa' => __('Qatar'),
            're' => __('Reunion'),
            'ro' => __('Romania'),
            'rs' => __('Serbia'),
            'ru' => __('Russian Federation'),
            'rw' => __('Rwanda'),
            'sa' => __('Saudi Arabia'),
            'sb' => __('Solomon Islands'),
            'sc' => __('Seychelles'),
            'sd' => __('Sudan'),
            'sg' => __('Singapore'),
            'sh' => __('St. Helena'),
            'si' => __('Slovenia'),
            'sj' => __('Svalbard & Jan Mayen Islands'),
            'sk' => __('Slovakia'),
            'sl' => __('Sierra Leone'),
            'sm' => __('San Marino'),
            'sn' => __('Senegal'),
            'so' => __('Somalia'),
            'sr' => __('Suriname'),
            'st' => __('Sao Tome & Principe'),
            'sv' => __('Sweden'),
            'sy' => __('Syrian Arab Republic'),
            'sz' => __('Swaziland'),
            'tc' => __('Turks & Caicos Islands'),
            'td' => __('Chad'),
            'tf' => __('French Southern Territories'),
            'tg' => __('Togo'),
            'th' => __('Thailand'),
            'tj' => __('Tajikistan'),
            'tk' => __('Tokelau'),
            'tm' => __('Turkmenistan'),
            'tn' => __('Tunisia'),
            'to' => __('Tonga'),
            'tp' => __('East Timor'),
            'tr' => __('Turkey'),
            'tt' => __('Trinidad & Tobago'),
            'tv' => __('Tuvalu'),
            'tw' => __('Taiwan, Province of China'),
            'tz' => __('Tanzania, United Republic of'),
            'ua' => __('Ukraine'),
            'ug' => __('Uganda'),
            'um' => __('United States Minor Outlying Islands'),
            'us' => __('United States of America'),
            'uy' => __('Uruguay'),
            'uz' => __('Uzbekistan'),
            'va' => __('Vatican City State (Holy See)'),
            'vc' => __('St. Vincent & the Grenadines'),
            've' => __('Venezuela'),
            'vg' => __('British Virgin Islands'),
            'vi' => __('United States Virgin Islands'),
            'vn' => __('Vietnam'),
            'vu' => __('Vanuatu'),
            'wf' => __('Wallis & Futuna Islands'),
            'ws' => __('Samoa'),
            'ye' => __('Yemen'),
            'yt' => __('Mayotte'),
            'za' => __('South Africa'),
            'zm' => __('Zambia'),
            'zr' => __('Zaire'),
            'zw' => __('Zimbabwe'),
            'zz' => __('Unknown or unspecified country'));
    }

    // map of language codes
    function languageMap()
    {
        // TODO A [make list complete - this is just a start] (drak)
        return array(
            'af' => __('Afrikaans'),
            'ar' => __('Arabic'),
            'ar-ae' => __('Arabic (United Arab Emirates)'),
            'ar-bh' => __('Arabic (Bahrain)'),
            'ar-dz' => __('Arabic (Algeria)'),
            'ar-eg' => __('Arabic (Egypt)'),
            'ar-iq' => __('Arabic (Iraq)'),
            'ar-jo' => __('Arabic (Jordan)'),
            'ar-kw' => __('Arabic (Kuwait)'),
            'ar-lb' => __('Arabic (Lebanon)'),
            'ar-ly' => __('Arabic (Libya)'),
            'ar-ma' => __('Arabic (Morocco)'),
            'ar-om' => __('Arabic (Oman)'),
            'ar-qa' => __('Arabic (Qatar)'),
            'ar-sa' => __('Arabic (Saudi Arabia)'),
            'ar-sd' => __('Arabic (Sudan)'),
            'ar-sy' => __('Arabic (Syria)'),
            'ar-tn' => __('Arabic (Tunisia)'),
            'ar-ye' => __('Arabic (Yemen)'),
            'be' => __('Belarusian'),
            'be-by' => __('Belarusian (Belarus)'),
            'bg' => __('Bulgarian'),
            'bg-bg' => __('Bulgarian (Bulgaria)'),
            'bn-in' => __('Bengali (India)'),
            'ca' => __('Catalan'),
            'ca-es' => __('Catalan (Spain)'),
            'cs' => __('Czech'),
            'cs-cz' => __('Czech (Czech Republic)'),
            'da' => __('Danish'),
            'da-dk' => __('Danish (Denmark)'),
            'de' => __('German'),
            'de-at' => __('German (Austria)'),
            'de-ch' => __('German (Switzerland)'),
            'de-de' => __('German (Germany)'),
            'de-lu' => __('German (Luxembourg)'),
            'el' => __('Greek'),
            'el-cy' => __('Greek (Cyprus)'),
            'el-gr' => __('Greek (Greece)'),
            'en' => __('English'),
            'en-au' => __('English (Australia)'),
            'en-ca' => __('English (Canada)'),
            'en-gb' => __('English (United Kingdom)'),
            'en-ie' => __('English (Ireland)'),
            'en-in' => __('English (India)'),
            'en-mt' => __('English (Malta)'),
            'en-nz' => __('English (New Zealand)'),
            'en-ph' => __('English (Philippines)'),
            'en-sg' => __('English (Singapore)'),
            'en-us' => __('English (United States)'),
            'en-za' => __('English (South Africa)'),
            'es' => __('Spanish'),
            'es-ar' => __('Spanish (Argentina)'),
            'es-bo' => __('Spanish (Bolivia)'),
            'es-cl' => __('Spanish (Chile)'),
            'es-co' => __('Spanish (Colombia)'),
            'es-cr' => __('Spanish (Costa Rica)'),
            'es-do' => __('Spanish (Dominican Republic)'),
            'es-ec' => __('Spanish (Ecuador)'),
            'es-es' => __('Spanish (Spain)'),
            'es-gt' => __('Spanish (Guatemala)'),
            'es-hn' => __('Spanish (Honduras)'),
            'es-mx' => __('Spanish (Mexico)'),
            'es-ni' => __('Spanish (Nicaragua)'),
            'es-pa' => __('Spanish (Panama)'),
            'es-pe' => __('Spanish (Peru)'),
            'es-pr' => __('Spanish (Puerto Rico)'),
            'es-py' => __('Spanish (Paraguay)'),
            'es-sv' => __('Spanish (El Salvador)'),
            'es-us' => __('Spanish (United States)'),
            'es-uy' => __('Spanish (Uruguay)'),
            'es-ve' => __('Spanish (Venezuela)'),
            'et' => __('Estonian'),
            'et-ee' => __('Estonian (Estonia)'),
            'eu' => __('Basque'),
            'fa' => __('Persian'),
            'fi' => __('Finnish'),
            'fi-fi' => __('Finnish (Finland)'),
            'fr' => __('French'),
            'fr-be' => __('French (Belgium)'),
            'fr-ca' => __('French (Canada)'),
            'fr-ch' => __('French (Switzerland)'),
            'fr-fr' => __('French (France)'),
            'fr-lu' => __('French (Luxembourg)'),
            'fur' => __('Friulian'),
            'ga' => __('Irish'),
            'ga-ie' => __('Irish (Ireland)'),
            'gl' => __('Galician'),
            'hi' => __('Hindi'),
            'hi-in' => __('Hindi (India)'),
            'hr' => __('Croatian'),
            'hr-hr' => __('Croatian (Croatia)'),
            'hu' => __('Hungarian'),
            'hu-hu' => __('Hungarian (Hungary)'),
            'id' => __('Indonesian'),
            'in' => __('Indonesian'),
            'in-id' => __('Indonesian (Indonesia)'),
            'is' => __('Icelandic'),
            'is-is' => __('Icelandic (Iceland)'),
            'it' => __('Italian'),
            'it-ch' => __('Italian (Switzerland)'),
            'it-it' => __('Italian (Italy)'),
            'iw' => __('Hebrew'),
            'iw-il' => __('Hebrew (Israel)'),
            'ja' => __('Japanese'),
            'ja-jp' => __('Japanese (Japan)'),
            'ka' => __('Georgian'),
            'ko' => __('Korean'),
            'ko-kr' => __('Korean (South Korea)'),
            'lt' => __('Lithuanian'),
            'lt-lt' => __('Lithuanian (Lithuania)'),
            'lv' => __('Latvian'),
            'lv-lv' => __('Latvian (Latvia)'),
            'mk' => __('Macedonian'),
            'mk-mk' => __('Macedonian (Macedonia)'),
            'ml' => __('Malayalam'),
            'ms' => __('Malay'),
            'ms-my' => __('Malay (Malaysia)'),
            'mt' => __('Maltese'),
            'mt-mt' => __('Maltese (Malta)'),
            'nds' => __('German (Luxembourg)'),
            'ne' => __('Nepali'),
            'nl' => __('Dutch'),
            'nl-be' => __('Dutch (Belgium)'),
            'nl-nl' => __('Dutch (Netherlands)'),
            'no' => __('Norwegian'),
            'no-no' => __('Norwegian (Norway)'),
            'no-no-ny' => __('Norwegian (Norway, Nynorsk)'),
            'pl' => __('Polish'),
            'pl-pl' => __('Polish (Poland)'),
            'pt' => __('Portuguese'),
            'pt-br' => __('Portuguese (Brazil)'),
            'pt-pt' => __('Portuguese (Portugal)'),
            'ro' => __('Romanian'),
            'ro-ro' => __('Romanian (Romania)'),
            'ru' => __('Russian'),
            'ru-ru' => __('Russian (Russia)'),
            'sk' => __('Slovak'),
            'sk-sk' => __('Slovak (Slovakia)'),
            'sl' => __('Slovenian'),
            'sl-si' => __('Slovenian (Slovenia)'),
            'sq' => __('Albanian'),
            'sq-al' => __('Albanian (Albania)'),
            'sr' => __('Serbian'),
            'sr-ba' => __('Serbian (Bosnia and Herzegovina)'),
            'sr-cs' => __('Serbian (Serbia and Montenegro)'),
            'sr-me' => __('Serbian (Montenegro)'),
            'sr-rs' => __('Serbian (Serbia)'),
            'st' => __('Sotho, Southern'),
            'sv' => __('Swedish'),
            'sv-se' => __('Swedish (Sweden)'),
            'th' => __('Thai'),
            'th-th' => __('Thai (Thailand)'),
            'th-th-th' => __('Thai (Thailand, TH)'),
            'tr' => __('Turkish'),
            'tr-tr' => __('Turkish (Turkey)'),
            'uk' => __('Ukrainian'),
            'uk-ua' => __('Ukrainian (Ukraine)'),
            'vi' => __('Vietnamese'),
            'vi-vn' => __('Vietnamese (Vietnam)'),
            'wo' => __('Wolof'),
            'zh' => __('Chinese'),
            'zh-cn' => __('Chinese (China)'),
            'zh-hk' => __('Chinese (Hong Kong)'),
            'zh-sg' => __('Chinese (Singapore)'),
            'zh-tw' => __('Chinese (Taiwan)'));
    }

    // legacy to l2 mapping
    function legacyCodeMap()
    {
        return array(
            'aar' => 'aa',
            'abk' => 'ab',
            'ave' => 'ae',
            'afr' => 'af',
            'aka' => 'ak',
            'amh' => 'am',
            'arg' => 'an',
            'ara' => 'ar',
            'asm' => 'as',
            'ava' => 'av',
            'aym' => 'ay',
            'aze' => 'az',
            'bak' => 'ba',
            'bel' => 'be',
            'bul' => 'bg',
            'bih' => 'bh',
            'bis' => 'bi',
            'bam' => 'bm',
            'ben' => 'bn',
            'bod' => 'bo',
            'bre' => 'br',
            'bos' => 'bs',
            'cat' => 'ca',
            'che' => 'ce',
            'cha' => 'ch',
            'cos' => 'co',
            'cre' => 'cr',
            'ces' => 'cs',
            'chu' => 'cu',
            'chv' => 'cv',
            'cym' => 'cy',
            'dan' => 'da',
            'deu' => 'de',
            'div' => 'dv',
            'dzo' => 'dz',
            'ewe' => 'ee',
            'ell' => 'el',
            'eng' => 'en',
            'enu' => 'eu',
            'spa' => 'es',
            'est' => 'et',
            'eus' => 'eu',
            'fas' => 'fa',
            'ful' => 'ff',
            'fin' => 'fi',
            'fij' => 'fj',
            'fao' => 'fo',
            'fra' => 'fr',
            'fry' => 'fy',
            'gle' => 'ga',
            'gla' => 'gd',
            'glg' => 'gl',
            'grn' => 'gn',
            'guj' => 'gu',
            'glv' => 'gv',
            'hau' => 'ha',
            'heb' => 'he',
            'hin' => 'hi',
            'hmo' => 'ho',
            'hrv' => 'hr',
            'hat' => 'ht',
            'hun' => 'hu',
            'hye' => 'hy',
            'her' => 'hz',
            'ina' => 'ia',
            'ind' => 'id',
            'ile' => 'ie',
            'ibo' => 'ig',
            'iii' => 'ii',
            'ipk' => 'ik',
            'ido' => 'io',
            'isl' => 'is',
            'ita' => 'it',
            'iku' => 'iu',
            'jpn' => 'ja',
            'jav' => 'jv',
            'kat' => 'ka',
            'kon' => 'kg',
            'kik' => 'ki',
            'kua' => 'kj',
            'kaz' => 'kk',
            'kal' => 'kl',
            'khm' => 'km',
            'kan' => 'kn',
            'kor' => 'ko',
            'kau' => 'kr',
            'kas' => 'ks',
            'kur' => 'ku',
            'kom' => 'kv',
            'cor' => 'kw',
            'kir' => 'ky',
            'lat' => 'la',
            'ltz' => 'lb',
            'lug' => 'lg',
            'lim' => 'li',
            'lin' => 'ln',
            'lao' => 'lo',
            'lit' => 'lt',
            'lub' => 'lu',
            'lav' => 'lv',
            'mlg' => 'mg',
            'mah' => 'mh',
            'mri' => 'mi',
            'mkd' => 'mk',
            'mal' => 'ml',
            'mon' => 'mn',
            'mar' => 'mr',
            'msa' => 'ms',
            'mlt' => 'mt',
            'mya' => 'my',
            'nau' => 'na',
            'nob' => 'nb',
            'nde' => 'nd',
            'nds' => 'nds',
            'nep' => 'ne',
            'ndo' => 'ng',
            'nld' => 'nl',
            'nno' => 'nn',
            'nor' => 'no',
            'nbl' => 'nr',
            'nav' => 'nv',
            'nya' => 'ny',
            'oci' => 'oc',
            'oji' => 'oj',
            'orm' => 'om',
            'ori' => 'or',
            'oss' => 'os',
            'pan' => 'pa',
            'pli' => 'pi',
            'pol' => 'pl',
            'pus' => 'ps',
            'por' => 'pt',
            'que' => 'qu',
            'roh' => 'rm',
            'run' => 'rn',
            'ron' => 'ro',
            'rus' => 'ru',
            'kin' => 'rw',
            'san' => 'sa',
            'srd' => 'sc',
            'snd' => 'sd',
            'sme' => 'se',
            'sag' => 'sg',
            'sin' => 'si',
            'slk' => 'sk',
            'slv' => 'sl',
            'smo' => 'sm',
            'sna' => 'sn',
            'som' => 'so',
            'sqi' => 'sq',
            'srp' => 'sr',
            'ssw' => 'ss',
            'sot' => 'st',
            'sun' => 'su',
            'swe' => 'sv',
            'swa' => 'sw',
            'tam' => 'ta',
            'tel' => 'te',
            'tgk' => 'tg',
            'tha' => 'th',
            'tir' => 'ti',
            'tuk' => 'tk',
            'tgl' => 'tl',
            'tsn' => 'tn',
            'ton' => 'to',
            'tur' => 'tr',
            'tso' => 'ts',
            'tat' => 'tt',
            'twi' => 'tw',
            'tah' => 'ty',
            'uig' => 'ug',
            'ukr' => 'uk',
            'urd' => 'ur',
            'uzb' => 'uz',
            'ven' => 've',
            'vie' => 'vi',
            'vol' => 'vo',
            'wln' => 'wa',
            'wol' => 'wo',
            'xho' => 'xh',
            'yid' => 'yi',
            'yor' => 'yo',
            'zha' => 'za',
            'zho' => 'zh',
            'zul' => 'zu');
    }
}
