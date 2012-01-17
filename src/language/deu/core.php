<?php
/**
 * Zikula Application Framework
 *
 * @copyright (c) 2008, Zikula German Translation Team
 * @link http://www.zikula.de
 * @version $Id: core.php 802 2009-10-10 23:06:56Z herr.vorragend $
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 */

// date and time defines
define('_DATE','Datum');
define('_DATEBRIEF','%d. %b %Y');
define('_DATELONG','%A, %d. %B %Y');
define('_DATESTRING','%A, %d. %B %Y um %H:%M Uhr');
define('_DATESTRING2', '%A, %B %d');
define('_DATETIMEBRIEF','%d.%m.%Y, %H:%M Uhr');
define('_DATETIMELONG','%A, %d. %B %Y, %H:%M Uhr');
define('_DATEINPUT', '%Y-%m-%d'); // Dateformat for input fields (parsable - do not try other formats!)
define('_DATETIMEINPUT', '%Y-%m-%d %H:%M'); // Date+time format for input fields (parsable - do not try other formats!)
define('_DATEFIRSTWEEKDAY', 1); // 0 = Sunday, 1 Monday and so on
define('_DAY_OF_WEEK_LONG','Sonntag Montag Dienstag Mittwoch Donnerstag Freitag Samstag');
define('_DAY_OF_WEEK_SHORT','Son Mon Die Mit Don Fre Sam');
define('_MONTH_LONG','Januar Februar März April Mai Juni Juli August September Oktober November Dezember');
define('_MONTH_SHORT','Jan Feb Mar Apr Mai Jun Jul Aug Sep Okt Nov Dez');
define('_TIME', 'Zeit');
define('_TIMEBRIEF','%H:%M');
define('_TIMELONG','%T %p');
define('_TIMEFORMAT', 24);  // use 12/24 depending on country
define('_SECOND', 'Sekunde');
define('_SECONDS', 'Sekunden');
define('_MINUTE', 'Minute');
define('_MINUTES', 'Minuten');
define('_DAY', 'Tag');
define('_DAYS', 'Tage');
define('_WEEK', 'Woche');
define('_WEEKS', 'Wochen');
define('_MONTH', 'Monat');
define('_MONTHS', 'Monate');
define('_YEAR', 'Jahr');
define('_YEARS', 'Jahre');
define('_NOTAVALIDCATEGORY', 'ungültige Kategorie');
define('_NOTAVALIDDATE', 'ungültiges Datum');
define('_NOTAVALIDINT', 'ungültige Zahl');
define('_NOTAVALIDNUMBER', 'ungültige Zahl');
define('_NOTAVALIDEMAIL', 'ungültige E-Mail-Adresse');
define('_NOTAVALIDURL', 'ungültige URL');

// time zone defines
define('_TIMEZONES','GMT-12 GMT-11 HST GMT-9:30 AKST PST MST CST EST AST GMT-3:30 GMT-3 GMT-2 GMT-1 GMT CET EET GMT+3 GMT+3:30 GMT+4 GMT+4:30 GMT+5 GMT+5:30 GMT+5:45 GMT+6 GMT+6:30 GMT+7 AWST ACDT JST ACST AEST GMT+11 GMT+11:30 GMT+12 GMT+12:45 GMT+13 GMT+14');
define('_TZOFFSETS','-12 -11 -10 -9.5 -9 -8 -7 -6 -5 -4 -3.5 -3 -2 -1 0 1 2 3 3.5 4 4.5 5 5.5 5.75 6 6.5 7 8 9 9.5 10 10.5 11 11.5 12 12.75 13 14');

// locale defines
define('_CHARSET','ISO-8859-15');
define('_LOCALE','de_DE');
define('_LOCALEWIN', 'deu');
define('_ERROR_LOCALENOTSET', 'Konnte Sprache nicht setzen: %locale%');
define('_PERMLINK_LOCALESEARCH', 'ÄÀÁÂÃÅäàáâãåÖÒÓÔÕØöòóôõøÈÉÊËèéêëÇçÌÍÎÏìíîïÜÙÚÛüùúûÿÑñß');
define('_PERMLINK_LOCALEREPLACE', 'AAAAAAaaaaaaOOOOOOooooooEEEEeeeeCcIIIIiiiiUUUUuuuuyNns');
define('_ALPHABET', 'A,B,C,D,E,F,G,H,I,J,K,L,M,N,O,P,Q,R,S,T,U,V,W,X,Y,Z'); 

// common footer defines
define('_CMSHOMELINK', 'Diese Seiten wurden mit Liebe und dem <a href="http://zikula.de">Zikula Application Framework</a> erstellt');
define('_XHTMLVALIDATORLINK', '<a href="http://validator.w3.org/check?uri=referer">XHTML</a>');
define('_CSSVALIDATORLINK', '<a href="http://jigsaw.w3.org/css-validator/">CSS</a>');
define('_ISPOWEREDBY', 'Erstellt mit');

// common words
define('_ZIKULA', 'Zikula');
define('_ALL','Alle');
define('_AND','und');
define('_BY','von');
define('_DOWN','runter');
define('_FOR', 'für');
define('_NO','Nein');
define('_NO_SHORT','N');
define('_OF','von');
define('_OK','OK');
define('_ON','am');
define('_OR', 'oder');
define('_TO','an');
define('_UP','hoch');
define('_URL', 'URL');
define('_YES','Ja');
define('_YES_SHORT','J');

// on/off states
define('_ONOFF_ON','Ein');
define('_ONOFF_OFF', 'Aus');
define('_OFF_UC','Aus');
define('_ON_UC','Ein');

// standard permissions levels
define('_ACCESS_ADD','Hinzufügen');
define('_ACCESS_ADMIN','Administrieren');
define('_ACCESS_COMMENT','Kommentieren');
define('_ACCESS_DELETE','Löschen');
define('_ACCESS_EDIT','Editieren');
define('_ACCESS_MODERATE','Moderieren');
define('_ACCESS_NONE','Keine Rechte');
define('_ACCESS_OVERVIEW','Übersicht');
define('_ACCESS_READ','Lesen');

// extended (pnobjlib) permission levels
define('_PN_TEXT_PERMISSION_BASIC_PRIVATE', 'Privat');
define('_PN_TEXT_PERMISSION_BASIC_GROUP', 'Gruppe');
define('_PN_TEXT_PERMISSION_BASIC_PUBLIC', 'Öffentlich');
define('_PN_TEXT_PERMISSION_BASIC_USER', 'Benutzer');
define('_PN_TEXT_PERMISSION_LEVEL_NONE', 'Keine');
define('_PN_TEXT_PERMISSION_LEVEL_READ', 'Lesen');
define('_PN_TEXT_PERMISSION_LEVEL_WRITE', 'Schreiben');
define('_PN_TEXT_NOAUTH_NONE', 'Keine Zugriffsrechte für dieses Modul.');
define('_PN_TEXT_NOAUTH_OVERVIEW', 'Keine Rechte zur Übersicht für dieses Modul.');
define('_PN_TEXT_NOAUTH_READ',  'Keine Leserechte für dieses Modul.');
define('_PN_TEXT_NOAUTH_COMMENT', 'Keine Rechte zur Kommentierung für dieses Modul.');
define('_PN_TEXT_NOAUTH_MODERATION', 'Keine Moderationsrechte für dieses Modul.');
define('_PN_TEXT_NOAUTH_EDIT', 'Keine Bearbeitungsrechte für dieses Modul.');
define('_PN_TEXT_NOAUTH_ADD', 'Keine Rechte zum Hinzufügen für dieses Modul.');
define('_PN_TEXT_NOAUTH_DELETE', 'Keine Rechte zum Löschen für dieses Modul.');
define('_PN_TEXT_NOAUTH_ADMIN', 'Keine Administrationsrechte für dieses Modul.');

// common actions & results
define('_ACTIONS', 'Aktionen');
define('_ACTION', 'Aktion');
define('_ACTIVATE','Aktivieren');
define('_ACTIVE','Aktiv');
define('_ACTIVATED', 'aktiviert');
define('_ADD','Hinzufügen');
define('_BACK', 'zurück');
define('_CANCEL', 'Abbruch');
define('_CLEAR', 'Löschen');
define('_CLOSE', 'Schließen');
define('_CONFIRM', 'Bestätigen');
define('_CONTINUE', 'Weiter');
define('_COMMIT', 'Bestätigen');
define('_COPY', 'Kopieren');
define('_CREATE', 'Anlegen');
define('_DEACTIVATE','Deaktivieren');
define('_DEACTIVATED', 'deaktiviert');
define('_DEFAULT', 'Vorgabe');
define('_DEFAULTS', 'Vorgaben');
define('_DELETE','Löschen');
define('_DETAILS', 'Details');
define('_EDIT','Editieren');
define('_FILTER', 'Filter');
define('_FORWARD', 'weiter');
define('_HELP', 'Hilfe');
define('_HELPPAGE', 'Hilfeseite');
define('_MESSAGESYOUMIGHTSEE', 'Mögliche Meldungen');
define('_CONFIRMATIONPROMPT', 'Bestätigungsaufforderung');
define('_IGNORE','Ignorieren');
define('_INACTIVE','Inaktiv');
define('_LOGIN','Anmelden');
define('_LOGIN_FLC','Anmelden');
define('_LOGOUT','Abmelden');
define('_MODIFY','Modifizieren');
define('_MOVE', 'Bewegen');
define('_NEW','Neu');
define('_NEXT', 'weiter');
define('_OPEN', 'Öffnen');
define('_PREVIOUS', 'zurück');
define('_REMOVE', 'entfernen');
define('_RESET', 'Reset');
define('_SAVE', 'Speichern');
define('_SEARCH', 'Suchen');
define('_STATE','Status');
define('_SUBMIT','Abschicken');
define('_UPDATE', 'Aktualisieren');
define('_VIEW', 'Ansicht');

//common module names
define('_COMMENTS', 'Kommentare');
define('_DOWNLOADS', 'Downloads');
define('_SUBMITNEWS', 'News einreichen');
define('_USERSMANAGER', 'Benutzer');
define('_WEB_LINKS', 'Weblinks');

//common module fields
define('_PROPERTIES', 'Eigenschaften');
define('_CONTENT', 'Inhalt');
define('_DESCRIPTION', 'Beschreibung');
define('_EMAIL' ,'E-Mail-Adresse');
define('_ID', 'ID');
define('_LANGUAGE', 'Sprache');
define('_META', 'Metadata');
define('_META_FLC', 'Metadata');
define('_NAME', 'Name');
define('_OPTIONAL', 'Optional');
define('_OPTIONS', 'Optionen');
define('_PASSWORD', 'Kennwort');
define('_REQUIRED', 'Notwendig');
define('_TITLE', 'Titel');
define('_USERID', 'Benutzer ID');
define('_USERNAME' ,'Benutzername');
define('_USERNAME_FLC', 'Benutzername');
define('_VALUE', 'Wert');
define('_LINKSPERPAGE', 'Links pro Seite');

// permalinks system
define('_PERMALINKS', 'Permalinks');
define('_PERMALINKTITLE', 'URL (für Permalinks)');
define('_PERMALINKTITLEBLANK', 'Leer lassen für automatischen Permalink Titel');
define('_PURGEPERMALINKS', 'PermaLinks bereinigen'); 
define('_PURGEPERMALINKSSUCCESFUL', 'Das Bereinigen der Permalinks war erfolgreich'); 
define('_PURGEPERMALINKSFAILED', 'Das Bereinigen der Permalinks ist fehlgeschlagen'); 
define('_ADDCATEGORYTITLETOPERMALINK', 'Kategorietitel zum Permalink hinzufügen');

// member descriptors
define('_GUEST','Gast');
define('_GUEST0','Gäste');
define('_GUESTS','Gäste');
define('_MEMBER','registrierter Benutzer');
define('_MEMBER0','registrierte Benutzer');
define('_MEMBERS','registrierte Benutzer');

// member states
define('_ONLINE','online');
define('_OFFLINE','offline');

// common Zikula terms
define('_BLOCK' ,'Block');
define('_BLOCKS' ,'Blöcke');
define('_CUSTOMARGS', 'Benutzderfinierte Argument');
define('_FUNCTIONTYPE', 'Funktionstyp');
define('_FUNCTIONTYPES', 'Funktionstyp(en)');
define('_FUNCTION', 'Funktion');
define('_FUNCTIONS', 'Funktionen');
define('_HOOK', 'Hook');
define('_HOOKS', 'Hooks');
define('_LEGACY', 'Legacy');
define('_MODULE', 'Modul');
define('_MODULES', 'Module');
define('_PARAMETERS', 'Parameter');
define('_PLUGIN', 'Plugin');
define('_PLUGINS', 'Plugins');
define('_PROFILE', 'Persönliche Informationen'); 
define('_TEMPLATE', 'Template');
define('_TEMPLATES', 'Templates');
define('_THEME', 'Theme');
define('_THEMES', 'Themes');

// other common terms
define('_DIRECTORY', 'Verzeichnis');
define('_POWEREDBY', 'Erstellt mit');
define('_VERSION', 'Version');
define('_COPYRIGHT', 'Copyright 2008');
define('_VALIDXHTML', 'Valid XHTML');
define('_VALIDCSS', 'Valid CSS');
define('_MOREINFOHERE_LC', 'weitere Informationen hier');
define('_MOREINFOHERE_UC', 'Weitere Informationen hier');
define('_PERMITTEDHTMLTAGSREMINDER', 'Erlaubte HTML Tags:');
define('_PERMITTEDHTMLTAGSSHORTREMINDER', 'HTML erlaubt');
define('_PUNC_PERIOD', '.');
define('_PUNC_COLON', ':');
define('_PUNC_SEMICOLON', ';');
define('_PUNC_QUESTIONMARK', '?');
define('_PUNC_OPENPARENTHESIS','(');
define('_PUNC_CLOSEPARENTHESIS', ')');
define('_PUNC_OPENDOUBLEQUOTE', '"');
define('_PUNC_CLOSEDOUBLEQUOTE', '"');
define('_PUNC_OPENSINGLEQUOTE', '\'');
define('_PUNC_CLOSESINGLEQUOTE', '\'');


// module system
define('_BADAUTHKEY', 'Ungültiger Authkey:  Mögliche Fehlerquellen: Navigation via Browser-Back oder aber der "Authkey" ist abgelaufen. Bitte die Seite neu laden und erneut probieren.');
define('_CANCELDELETE', 'Löschen abbrechen');
define('_CANCELEDIT', 'Bearbeiten abbrechen');
define('_CONFIGUPDATED', 'Modulkonfiguration aktualisiert');
define('_CONFIGUPDATEFAILED', 'Modulkonfiguration konnte nicht aktualisiert werden');
define('_CONFIRMDELETE', 'Löschen bestätigen');
define('_CONFIRMDELETEITEM', 'Löschen bestätigen von: %i%');
define('_CREATEDBY', 'Angelegt von %username%');
define('_CREATEDBYON', 'Angelegt von %username% am %date%');
define('_CREATEDON', 'Angelegt am %date%');
define('_CREATEITEM', '%i% anlegen');
define('_CREATEFAILED','Fehler! Anlegen fehlgeschlagen');
define('_CREATEINDEXFAILED', 'Fehler! Index konnte nicht angelegt werden');
define('_CREATEITEMSUCCEDED','%i% angelegt.');
define('_CREATESUCCEDED','Eintrag angelegt');
define('_CREATETABLEFAILED','Fehler! Tabelle konnte nicht angelegt werden');
define('_DELETEITEM', '%i% löschen');
define('_DELETEFAILED','Fehler! Eintrag konnte nicht gelöscht werden');
define('_DELETEITEMSUCCEDED','%i% gelöscht.');
define('_DELETESUCCEDED','Eintrag gelöscht.');
define('_DELETETABLEFAILED','Fehler! Tabelle konnte nicht gelöscht werden');
define('_DROPCOLUMNFAILED', 'Fehler! Feld konnte nicht gelöscht werden');
define('_DROPINDEXFAILED', 'Fehler! Der Index konnte nicht gelöscht werden');
define('_FAILEDTOLOADMODULE', 'Modul <strong>%m%<strong> konnte nicht geladen werden');
define('_FAILEDTOLOADMODULEATFUNC', 'Modul <strong>%m%</strong> konnte nicht geladen werden (Funktion: <strong>%f%</strong>)');
define('_GETFAILED', 'Fehler! Eintrag konnte nicht geladen werden');
define('_GETITEMSFAILED', 'Fehler! %i% konnte nicht geladen werden');
define('_GENERALSETTINGS', 'Allgemeine Einstellungen');
define('_LOADAPIFAILED', 'Fehler! Beim Laden der API ist ein Problem aufgetreten');
define('_LOADFAILED','Fehler! Beim Laden des Moduls ist ein Fehler aufgetreten');
define('_MODARGSERROR','Fehler! Variablen wurden von der API Funktion nicht akzeptiert');
define('_MODIFYCONFIG', 'Konfiguration modifizieren');
define('_MODIFYCONFIGITEM', '%1% Konfiguration');
define('_MODIFYITEM', '%i% modifizieren');
define('_MODULENOAUTH', 'Fehler! Keine Berechtigung für das Modul');
define('_MODULENODIRECTACCESS', 'Fehler! Das Modul kann nicht direkt aufgerufen werden');
define('_MODULENOTAVAILABLE', 'Modul <strong>%m%</strong> nicht verfügbar');
define('_MODULERETURNED', 'Funktion <strong>%f%</strong> im Modul <strong>%m%</strong> abgeschlossen.');
define('_MUSTBENUMERIC', 'Die \'%s%\' Einstellung muss numerisch sein.');
define('_NEWITEM', 'Neu %i%');
define('_NOITEMSFOUND', 'Keine Einträge gefunden');
define('_NOFOUND', 'Kein %i% gefunden');
define('_NOSUCHITEM', 'Keine entsprechenden Einträge');
define('_NOSUCHITEMFOUND', 'Kein %i%');
define('_REGISTERFAILED', 'Fehler! Hook konnte nicht registriert werden');
define('_RENAMECOLUMNFAILED', 'Fehler! Feld konnte nicht umbenannt werden');
define('_RENAMETABLEFAILED', 'Fehler! Tabelle konnte nicht umbenannt werden');
define('_SEARCHITEMS', 'Suche nach %i%');
define('_SEARCHRESULTSFOUND', '%x% Ergebnis(se) gefunden.');
define('_SEARCHRESULTSNOITEMSFOUND', 'Kein %i% gefunden.');
define('_TEMPLATENOTAVAILABLE', 'Template <strong>%t%</strong> für Modul <strong>%m%</strong> nicht gefunden');
define('_TRANSACTIONFAILED', 'Transaction fehlgeschlagen ... Rollback erfolgt!<br />');
define('_UNABLETOLOADCLASS', 'Fehler! Kann Klasse [%s] nicht laden');
define('_UNABLETOLOADMODULECLASS', 'Fehler! Kann Klasse [%s] für Modul [%s] nicht laden');
define('_UNABLETOLOADMODULEARRAYCLASS', 'Fehler! Kann Modul-Array-Klasse [%s] für Modul [%s] nicht laden');
define('_UNKNOWNFUNC', 'Fehler: Unbekannte Funktion');
define('_UNKNOWNUSER', 'unbekannter Benutzer');
define('_UNREGISTERFAILED', 'Fehler: Hook konnte nicht unregistriert werden');
define('_UPDATEITEM', 'Aktualisiere %i%');
define('_UPDATECONFIG', 'Aktualisieren');
define('_UPDATEDBY', 'Aktualisiert von %username%');
define('_UPDATEDBYON', 'Aktualisiert von %username% am %date%');
define('_UPDATEDON', 'Aktualisiert am %date%');
define('_UPDATEFAILED','Fehler! Aktualisierungsversuch fehlgeschlagen');
define('_UPDATETABLEFAILED','Fehler! Tabellenaktualisierung fehlgeschlagen');
define('_UPDATEITEMSUCCEDED','%i% aktualisiert');
define('_UPDATESUCCEDED','Eintrag aktualisiert');
define('_VIEWCONFIG', 'Konfiguration anzeigen');
define('_VIEWITEMS', 'Zeige %i%');

// Central administration define
define('_ADMINMENU','Administration');

// defines for the pager plugin
define('_FIRSTPAGE', 'Erste');
define('_FIRSTPAGE_TITLE', 'Erste Seite');
define('_ITEMSPERPAGE', 'Einträge pro Seite');
define('_PREVIOUSPAGE', 'Vorherige');
define('_PREVIOUSPAGE_TITLE', 'Vorherige Seite');
define('_LASTPAGE', 'Letzte');
define('_LASTPAGE_TITLE', 'Letzte Seite');
define('_NEXTPAGE', 'Nächste');
define('_NEXTPAGE_TITLE', 'Nächste Seite');
define('_NONEXTPAGE', 'Keine weiteren Seiten');
define('_NOPREVIOUSPAGE', 'Keine vorherige Seiten');
define('_PAGE', 'Seite');
define('_PERPAGE', '%i% pro Seite');
define('_TOTAL', 'Insgesamt');

// WorkflowUtil
define('_WF_STATEERROR', 'Workflow Statusfehler');

// Form utilities
define('_PNFORM_MANDATORYERROR', 'Bitte dieses Feld ausfüllen');
define('_PNFORM_MANDATORYSELECTERROR', 'Bitte etwas auswählen');
define('_PNFORM_MAXLENGTHERROR', 'Text darf maximal %s Zeichen lang sein');
define('_PNFORM_SELECTDATE', 'Datum wählen');
define('_PNFORM_RANGEERROR', 'Der Wert ist nicht im gültigen Bereich');
define('_PNFORM_RANGEMINERROR', 'Der Wert muss größer oder gleich %i% sein');
define('_PNFORM_RANGEMAXERROR', 'Der Wert muss kleiner oder gleich %i% sein');
define('_PNFORM_UPLOADERROR', 'Fehler beim Upload der Datei.');

// categories system
define('_ALLCATEGORIES', '-- Alle --');
define('_CATEGORY', 'Kategorie');
define('_CATEGORY_LC', 'Kategorie');
define('_CATEGORIES', 'Kategorien');
define('_CATEGORIESMAPPINGS', 'Multi-Kategorie Mappings');
define('_CATEGORIESMAPPINGSCOUNT', 'Anzahl der Multi-Kategorie Mappings');
define('_CHOOSECATEGORY', '-- wählen --');
define('_CHOOSEMODULE', 'Modul wählen');
define('_CHOOSETABLE', 'Tabelle wählen');
define('_CHOOSEONE', 'Bitte auswählen');
define('_ENABLECATEGORIZATION', 'Kategorisierung aktivieren');
define('_NOASSIGNEDCATEGORIES', 'Keine Kategorie zugeordnet'); 
define('_MODULECATEGORY', 'Modulkategorie');
define('_MODULECATEGORY_LC', 'Modulkategorie');
define('_MODULECATEGORIES', 'Modulkategorien');
define('_CATEGORIZATION', 'Kategorisierung');

// 'templates' for error message
define('_ERROR_ADMIN', '%message% %func% in Zeile %line% in Datei %file%.');

// userlinks plugin
define('_YOURACCOUNT', 'Profil');
define('_CREATEACCOUNT', 'Profil anlegen');

// online plugin
define('_CURRENTLYONLINE', 'Zur Zeit sind %numguests% %gueststext% und %numusers% %userstext% online.');

// user welcome plugin
define('_WELCOMEUSER', 'Willkommen %username%');

// login/logout procedure
define('_UNABLETOSAVELOGINDATE', 'Anmeldedatum konnte nicht gespeichert werden');
define('_LOGOUTFORCED', 'Es erfolgte eine Abmeldung durch einen Administrator. Bitte neu anmelden');

// jscalendar
define('_DATE_SELECTOR', 'Datum wählen');

// mailer
define('_ERROR_SENDINGMAIL', 'Beim Senden einer Mail ist ein Fehler aufgetreten');
define('_ERROR_SENDINGMAIL_ADMINLOG', 'Beim Senden einer Mail von %fromname% (%fromaddress%) an %toname% (%toaddress%) mit dem Betreff \'%subject\' ist ein Fehler aufgetreten: %errorinfo%');
define('_ERROR_UNKNOWNMAILERERROR', 'Unbekannter Fehler beim Mailversand');

// module vars
define('_ERROR_NONULLVALUEALLOWED', 'Modulevariablen mit NULL-Werten sind nicht erlaubt (%modname%/%varname%)');

// site disabled template 
define('_THISSITEDISABLED', 'Diese Seite wurde deaktiviert.'); 
define('_ADMINLOGINREQUIRED', 'Admin-Login erforderlich, um fortzufahren'); 
define('_ADMINLOGIN', 'Administrator Login'); 

// exit functionality 
define('_EXITINSTALLERROR', 'Installationsfehler:'); 
define('_EXITHANDLER', 'Exit-Handler: '); 
define('_EXITSTACKTRACE', 'Stack Trace:'); 