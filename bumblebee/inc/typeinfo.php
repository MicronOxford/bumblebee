<?php
/**
* functions for handling types, comparisons, conversions, validation etc
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id$
* @package    Bumblebee
* @subpackage Misc
*/

checkValidInclude();

function checkValidInclude() {
  if (! defined('BUMBLEBEE')) {
    die('Internal error: access denied to this location.');
  }
}

/**
* If an array key is set, return that value, else return a default
*
* Combines isset and ternary operator to make for cleaner code that
* is quiet when run with E_ALL.
*
* @param array &$a (passed by ref for efficiency only) array to lookup
* @param string $k  the key to be checked
* @param mixed $default (optional) the default value to return if not index not set
* @return mixed  returns either $a[$k] if it exists or $default
*/
function issetSet(&$a, $k, $default=NULL) {
  return (isset($a[$k]) ? $a[$k] : $default);
}

/**
* simple debugging function to print out arrays and objects
*
* uses print_r/var_dump or dBug within HTML pre tags for easier inspection of classes and arrays
* @param mixed $v  object or array to print
*/
function preDump($v) {
  // either use the dBug class for pretty printing or var_dump/print_r
  $conf = ConfigReader::getInstance();
  if ($conf->value('error_handling', 'UseDBug')) {
    new dBug($v);
  } else {
    echo '<pre>';
    var_dump($v);
    #print_r($v);
    echo '</pre>'."\n";
  }
}

/**
* debugging function to conditionally print data to the browser
* @param mixed $v  variable to be printed
* @param boolean $DEBUG   print data if true
*/
function echoData($v, $DEBUG=0) {
  $conf = ConfigReader::getInstance();
  if ($conf->VerboseData || $DEBUG) {
    preDump($v);
  }
}


/**
* is variable composed purely of alphabetic data [A-Za-z_-]
* @param string $var string to be tested
* @return boolean
*/
function is_alphabetic($var) {
  return preg_match('/^\w+$/', $var);
}

/**
* Quote data for passing to the database, enclosing data in quotes etc
*
* Fixes programatically generated data so that it is correctly escaped. Deals
* with magic_quotes_gpc to remove slashes so that the input is sensible and
* doesn't end up accummulating escape characters with multiple submissions.
*
* Also tests that the supplied string is actually UTF-8 encoded, as if it is
* correctly UTF-8 encoded then we can be sure that we are protected against
* byte munging multibyte attacks that addslashes() is normally susceptible to.
* (that is where the last byte of a multibyte sequence is 0x5c (\) so
* addslashes() is braindead enough to try and escape it creating a multibyte
* character followed by a backslash.... thus addslashes() has created a SQL
* injection vector rather than closing it.
* For more info see:          http://shiflett.org/archive/184
*
* @param string $v string to be quoted
* @return string '$v' with slashes added as appropriate.
*/
function qw($v) {
  if (! isUTF8($v)) {
    $orig = $v;
    // badness is here. this means that the user has tried to change the input
    // encoding to something other than the UTF-8 that was requested.
    // We will *try* to fix the string:
    if (function_exists("mb_convert_encoding")) {
      $v = mb_convert_encoding($v, 'UTF-8');
    } elseif (function_exists("iconv")) {
      $v = iconv('', 'UTF-8//IGNORE', $v);
    } else {
      // try converting from windows encoding to UTF-8
      $v = cp1252_to_utf8($v);
    }
    if (!isUTF8($v)) {
      // then we really don't know what to do so kill the data
      // better not to have a compromised db, really...
      $v = '';
    }
    logmsg(9, "Non UTF-8 data received. '$orig' was converted to '$v'.");
    //logmsg(9, "Bacon saved? '".addslashes($orig)."' was converted to '".addslashes($v)."'.");
  }
  // magic-quotes-gpc is a pain in the backside: I would rather I was just given
  // the data the user entered.
  // We can't just return the data if magic_quotes_gpc is turned on because
  // that would be wrong if there was programatically set data in there.
  if (get_magic_quotes_gpc()) {
    // first remove any (partial or full) escaping then add it in properly
    $v = addslashes(stripslashes($v));
  } else {
    // just add in the slashes
    $v = addslashes($v);
  }
  return "'".$v."'";
}

/**
* Quote each element in a set of values.
*
* @param array $list    list of values to qw quote for use in SQL
* @returns array  list of quoted strings
*/
function array_qw($list) {
  $newlist = array();
  foreach ($list as $a) {
    $newlist[] = qw($a);
  }
  return $newlist;
}

/**
* Remove quoting around expressions and remove slashes in data to escape bad chars
*
* @param string $v string to be unquoted
* @return string unquoted string
*/
function unqw($v) {
  if (preg_match("/'(.+)'/", $v, $match)) {
    $v = $match[1];
  }
  return stripslashes($v);
}

/**
* Verifies that the supplied string is correctly UTF-8 encoded
*
* Two versions are presented here -- the simple version with just the
* /u regexp is significantly faster than the more complicated byte-checking
* version but the /u regexp doesn't always catch bad UTF-8 sequences.
*
* PCRE /u version from:
*      http://www.phpwact.org/php/i18n/charsets%23checking_utf-8_for_well_formedness
*
* Regexp version from
*      http://w3.org/International/questions/qa-forms-utf-8.html
*
* @param string $v string to be tested
* @return boolean string is UTF-8 encoded
*/
function isUTF8($v) {
  return preg_match('@^(?:
          [\x09\x0A\x0D\x20-\x7E]            # ASCII
        | [\xC2-\xDF][\x80-\xBF]             # non-overlong 2-byte
        |  \xE0[\xA0-\xBF][\x80-\xBF]        # excluding overlongs
        | [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}  # straight 3-byte
        |  \xED[\x80-\x9F][\x80-\xBF]        # excluding surrogates
        |  \xF0[\x90-\xBF][\x80-\xBF]{2}     # planes 1-3
        | [\xF1-\xF3][\x80-\xBF]{3}          # planes 4-15
        |  \xF4[\x80-\x8F][\x80-\xBF]{2}     # plane 16
  )*$@x', $v);

/*
  if ( strlen($str) == 0 ) {
      return TRUE;
  }
  // If even just the first character can be matched, when the /u
  // modifier is used, then it's valid UTF-8. If the UTF-8 is somehow
  // invalid, nothing at all will match, even if the string contains
  // some valid sequences
  return (preg_match('/^.{1}/us',$str,$ar) == 1);
*/
}

/**
* Converts Windows characters (charset cp-1252 or windows-1252) to UTF-8
*
* @param string $v string to be tested
* @return string UTF-8 string
*/
function cp1252_to_utf8($str) {
  if (! function_exists('utf8_encode')) return $str;
  $cp1252_map = array(
    "\xc2\x80" => "\xe2\x82\xac", /* EURO SIGN */
    "\xc2\x82" => "\xe2\x80\x9a", /* SINGLE LOW-9 QUOTATION MARK */
    "\xc2\x83" => "\xc6\x92",     /* LATIN SMALL LETTER F WITH HOOK */
    "\xc2\x84" => "\xe2\x80\x9e", /* DOUBLE LOW-9 QUOTATION MARK */
    "\xc2\x85" => "\xe2\x80\xa6", /* HORIZONTAL ELLIPSIS */
    "\xc2\x86" => "\xe2\x80\xa0", /* DAGGER */
    "\xc2\x87" => "\xe2\x80\xa1", /* DOUBLE DAGGER */
    "\xc2\x88" => "\xcb\x86",     /* MODIFIER LETTER CIRCUMFLEX ACCENT */
    "\xc2\x89" => "\xe2\x80\xb0", /* PER MILLE SIGN */
    "\xc2\x8a" => "\xc5\xa0",     /* LATIN CAPITAL LETTER S WITH CARON */
    "\xc2\x8b" => "\xe2\x80\xb9", /* SINGLE LEFT-POINTING ANGLE QUOTATION */
    "\xc2\x8c" => "\xc5\x92",     /* LATIN CAPITAL LIGATURE OE */
    "\xc2\x8e" => "\xc5\xbd",     /* LATIN CAPITAL LETTER Z WITH CARON */
    "\xc2\x91" => "\xe2\x80\x98", /* LEFT SINGLE QUOTATION MARK */
    "\xc2\x92" => "\xe2\x80\x99", /* RIGHT SINGLE QUOTATION MARK */
    "\xc2\x93" => "\xe2\x80\x9c", /* LEFT DOUBLE QUOTATION MARK */
    "\xc2\x94" => "\xe2\x80\x9d", /* RIGHT DOUBLE QUOTATION MARK */
    "\xc2\x95" => "\xe2\x80\xa2", /* BULLET */
    "\xc2\x96" => "\xe2\x80\x93", /* EN DASH */
    "\xc2\x97" => "\xe2\x80\x94", /* EM DASH */
    "\xc2\x98" => "\xcb\x9c",     /* SMALL TILDE */
    "\xc2\x99" => "\xe2\x84\xa2", /* TRADE MARK SIGN */
    "\xc2\x9a" => "\xc5\xa1",     /* LATIN SMALL LETTER S WITH CARON */
    "\xc2\x9b" => "\xe2\x80\xba", /* SINGLE RIGHT-POINTING ANGLE QUOTATION*/
    "\xc2\x9c" => "\xc5\x93",     /* LATIN SMALL LIGATURE OE */
    "\xc2\x9e" => "\xc5\xbe",     /* LATIN SMALL LETTER Z WITH CARON */
    "\xc2\x9f" => "\xc5\xb8"      /* LATIN CAPITAL LETTER Y WITH DIAERESIS*/
  );
  // utf8_encode() converts from ISO-8859-1 to UTF-8; the strtr() converts the
  // differences between Windows-1252 and ISO-8859-1.
  return  strtr(utf8_encode($str), $cp1252_map);
}

/**
* quote words against XSS attacks by converting tags to html entities
*
* replace some bad HTML characters with entities to protext against
* cross-site scripting attacks. the generated code should be clean of
* nasty HTML
*
* @param string $v string to be quoted
* @param boolean $strip   strip slashes first
* @return string $v with html converted to entities
*/
function xssqw($v, $strip=true) {
  // once again magic_quotes_gpc gets in the way
  if ($strip && get_magic_quotes_gpc()) {
    // first remove any (partial or full) escaping then we'll do it properly below
    $v = stripslashes($v);
  }
  $replace = array(
        "/'/"                             =>   "&#039;",
        '/"/'                             =>   "&#034;",
        '@<@'                             =>   '&lt;',
        '@>@'                             =>   '&gt;',
        '@&(?!(\#\d{1,4})|(\w{2,4});)@'   =>   '&amp;'  // allow &#123; and &lt; through
  );
  return preg_replace(array_keys($replace), array_values($replace), $v);
  #return htmlentities($v, ENT_QUOTES);
}


/**
* quote words against XSS attacks but allow some html tags through
* (unsafe attributes are removed)
*
* @param string $v string to be quoted
* @return string $v with html converted to entities
*/
function xssqw_relaxed($v) {
  // once again magic_quotes_gpc gets in the way
  if (get_magic_quotes_gpc()) {
    // first remove any (partial or full) escaping then we'll do it properly below
    $v = stripslashes($v);
  }
  $keep = array(
          "i"     => array("style", "class"),
          "u"     => array("style", "class"),
          "b"     => array("style", "class"),
          "a"     => array("style", "class", "href", "id", "name"),
          "div"   => array("style", "class", "id"),
          "br"    => array(),
          "hr"    => array("style", "class", "id")
        );

  $s = xssqw($v);
  foreach ($keep as $tag => $attribs) {
    $s = preg_replace("@&lt;\s*($tag)((?:(?!&gt;).)*)&gt;"
                      ."((?:(?!&lt;\s*/\s*$tag).)*)"  // negative lookahead: no closing tag
                      ."&lt;\s*/$tag\s*&gt;@ise",
                      'xssqw_relaxed_helper($keep, "\1", "\2", "\3");',
                      $s);
    #echo $s;
  }
  return $s;
}

function xssqw_relaxed_helper($tags, $tag, $attribs, $content){
  #echo "tag='$tag'; attribs='$attribs'; content='$content'<br />\n";
  $attrlist = array();
  foreach ($tags[$tag] as $a) {
    $attrmatch = "@$a\s*=\s*(&#039;|&#034;)((?:[^\\1])*)\\1@";
    $m = array();
    if (preg_match($attrmatch, $attribs, $m)) {
      $attrlist[] = "$a='$m[2]'";
    }
  }
  $attrs = join($attrlist, " ");
  return "<$tag $attrs>$content</$tag>";
}

/**
* quote all elements of an array against XSS attacks using xssqw function
*
* @param array $a array of strings to be quoted
* @return array $a of strings quoted
*/
function array_xssqw($a) {
  return array_map('xssqw', $a);
}

/**
* tests if string is non-empty
*
* note that in PHP, '' == '0' etc so test has to use strlen
* @param string $v string to test for emptiness
* @return boolean
*/
function is_nonempty_string($v) {
  #echo "'val=$v' ";
  return !(strlen($v) == 0);
}

/**
* tests if string is a plausible member of a radio-button choice set
*
* @param string $v string to test
* @return boolean
*/
function choice_set($v) {
  #echo "'val=$v' ";
  return !($v == NULL || $v == '');
}

/**
* tests if string is a member of a radio button choice set
*
* @param string $v string to test
* @return boolean
*/
function is_valid_radiochoice($v) {
  #echo "'val=$v' ";
  return (choice_set($v) && is_numeric($v) && $v >= -1);
}

/**
* tests if string is a sensible email format
*
* does not test full RFC822 compliance or that the address exists, just that it looks
* like a standard email address with a username part @ and domain part with at least one dot
* @param string $v string to test for email format
* @return boolean
*/
function is_email_format($v) {
  #echo "'val=$v' ";
  #$pattern = '/^\w.+\@[A-Z_\-]+\.[A-Z_\-]/i';
  $pattern = '/^[_a-z0-9\-]+(?:\.[_a-z0-9\-]+)*@[a-z0-9\-]+(?:\.[a-z0-9\-]{2,})+$/i';
  return (preg_match($pattern, $v));
}

/**
* tests if string is number
*
* @param string $v string to test if it is a number
* @return boolean
*/
function is_number($v) {
  return is_numeric($v);
}

/**
* tests if string is a amount for a price
*
* @param string $v string to test if it is a valid cost
* @return boolean
* @todo //TODO: strengthen this test?
*/
function is_cost_amount($v) {
   return is_numeric($v);
}

/**
* tests if string is a amount for a price but allows blank entries
*
* @param string $v string to test if it is a valid cost
* @return boolean
*/
function is_cost_amount_or_blank($v) {
   return is_number($v) || $v === '' || $v === null;
}

/**
* tests if string is valid date-time expression YYYY-MM-DD HH:MM
*
* @param string $v string to test it is a date-time string
* @return boolean
* @todo //TODO: can this be relaxed to be more user-friendly without introducing errors
*/
function is_valid_datetime($v) {
  return (preg_match('/^\d\d\d\d-\d\d-\d\d \d\d:\d\d/',$v));
}

/**
* tests if string is valid time expression HH:MM or HH:MM:SS format
*
* @param string $v string to test it is a time string
* @return boolean
* @todo //TODO: can this be relaxed to be more user-friendly without introducing errors
*/
function is_valid_time($v) {
  return (preg_match('/^\d\d:\d\d/',$v) || preg_match('/^\d\d:\d\d:\d\d/',$v));
}

/**
* tests if string is valid time expression HH:MM or HH:MM:SS format other than 00:00:00
*
* @param string $v string to test if it is a time string
* @return boolean
* @todo //TODO: can this be relaxed to be more user-friendly without introducing errors
*/
function is_valid_nonzero_time($v) {
  return (preg_match('/^\d\d:\d\d/',$v) || preg_match('/^\d\d:\d\d:\d\d/',$v))
            && ! preg_match('/^00:00/',$v) && ! preg_match('/^00:00:00/',$v);
}

/**
* tests if a set of numbers add to 100 (set of percentages should add to 100)
*
* @param array $vs list of values to test if sum is 100
* @return boolean
*/
function sum_is_100($vs) {
  #echo "<br/>Checking sum<br/>";
  $sum=0;
  foreach ($vs as $v) {
    #echo "'$v', ";
    $sum += $v;
  }
  return ($sum == 100);
}

function currencyValueCleaner($value) {
  $conf = ConfigReader::getInstance();
  $re = $conf->value('language', 'removedCurrencySymbols');
  if ($re == '' || $re == null) return;

  $re = preg_quote($re, '@');
  $value = preg_replace("@[$re]@", '', $value);
  return trim($value);
}

?>
