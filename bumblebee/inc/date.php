<?php
/**
* Simple date and time classes to perform basic date calculations
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id$
* @package    Bumblebee
* @subpackage Misc
*/

/** Load ancillary functions */
require_once 'inc/typeinfo.php';
checkValidInclude();

/** config system */
require_once 'inc/bb/configreader.php';

/**
* Simple date class to perform basic date calculations
*
* WARNING USING TICKS DIRECTLY IS DANGEROUS
*
* The ticks variable here is a little problematic... daylight saving transitions tend
* to make it rather frought. For example, for the purposes of this system,
* if you want to know what 4 days after 9am 20th March
* is, you expect to get 9am 23rd March regardless of a timezone change from daylight saving.
*
* For example, this might not give you what you want:<code>
* $date = new SimpleDate('2005-03-20');
* $date->addSecs(4*24*60*60);   // add 4 days
* </code>
* But this will:<code>
* $date = new SimpleDate('2005-03-20');
* $date->addDays(4);   // add 4 days
* </code>
*
* @package    Bumblebee
* @subpackage Misc
*/
class SimpleDate {
  /**
  * cache of date in string format
  * @var string
  */
  var $_cache = array();
  /**
  * date in seconds since epoch
  * @var integer
  */
  var $ticks  = '';
  /**
  * is a valid date-time
  * @var boolean
  */
  var $isValid = 1;

  /**
  * construct a Date-Time object
  *
  * constructor will work with $time in the following formats:
  * - YYYY-MM-DD            (assumes 00:00:00 for time part)
  * - YYYY-MM-DD HH:MM      (assumes :00 for seconds part)
  * - YYYY-MM-DD HH:MM:SS
  * - seconds since epoch
  * - SimpleDate object
  * @param mixed $time  initial time to use
  */
  function SimpleDate($time) {
    ($time == NULL) && $time = 0;
    if (is_numeric($time)) {
      $this->setTicks($time);
    } elseif (type_is_a($time, 'SimpleDate')) {
      $this->setTicks($time->ticks);
    } else {
      $this->setStr($time);
    }
  }

  /**
  * set the date and time from a string
  *
  * - YYYY-MM-DD            (assumes 00:00:00)
  * - YYYY-MM-DD HH:MM      (assumes :00)
  * - YYYY-MM-DD HH:MM:SS
  * @param mixed $s date time string
  */
  function setStr($s) {
    $this->isValid = 1;
    $this->_setTicks($s);
  }

  function dateTimeString() {
    if (! isset($this->_cache['datetime'])) {
      $this->_cache['datetime'] = strftime('%Y-%m-%d %H:%M:%S', $this->ticks);
    }
    return $this->_cache['datetime'];
  }

  function timeString() {
    if (! isset($this->_cache['time'])) {
      $this->_cache['time'] = strftime('%H:%M:%S', $this->ticks);
    }
    return $this->_cache['time'];
  }

  function dateString() {
    if (! isset($this->_cache['date'])) {
      $this->_cache['date'] = strftime('%Y-%m-%d', $this->ticks);
    }
    return $this->_cache['date'];
  }

  /**
  * get a short string representation for the current locale
  *
  * @return string time value in short time format
  */
  function getShortDateString() {
    if (! isset($this->_cache['lshortDate'])) {
      $conf = ConfigReader::getInstance();
      $format = $conf->value('language', 'date_shortdate', 'Y-m-d');
      $this->_cache['lshortDate'] = $this->getStringByFormat($format);
    }
    return $this->_cache['lshortDate'];
  }

  /**
  * get a short string representation for the current locale
  *
  * @return string time value in short time format
  */
  function getShortTimeString() {
    if (! isset($this->_cache['lshortTime'])) {
      $conf = ConfigReader::getInstance();
      $format = $conf->value('language', 'date_shorttime', 'H:i');
      $this->_cache['lshortTime'] = $this->getStringByFormat($format);
    }
    return $this->_cache['lshortTime'];
  }

  /**
  * get a short string representation for the current locale
  *
  * @return string time value in short time format
  */
  function getShortDateTimeString() {
    if (! isset($this->_cache['lshortDateTime'])) {
      $conf = ConfigReader::getInstance();
      $format = $conf->value('language', 'date_shortdatetime', 'Y-m-d H:i');
      $this->_cache['lshortDateTime'] = $this->getStringByFormat($format);
    }
    return $this->_cache['lshortDateTime'];
  }

  /**
  * get a long string representation for the current locale
  *
  * @return string time value in long time format
  */
  function getLongDateString() {
    if (! isset($this->_cache['llongDate'])) {
      $conf = ConfigReader::getInstance();
      $format = $conf->value('language', 'date_longdate', 'D F j, Y');
      $this->_cache['llongDate'] = $this->getStringByFormat($format);
    }
    return $this->_cache['llongDate'];
  }

  /**
  * get a long string representation for the current locale
  *
  * @return string time value in long time format
  */
  function getLongTimeString() {
    if (! isset($this->_cache['llongTime'])) {
      $conf = ConfigReader::getInstance();
      $format = $conf->value('language', 'date_longtime', 'H:i:s');
      $this->_cache['llongTime'] = $this->getStringByFormat($format);
    }
    return $this->_cache['llongTime'];
  }

  /**
  * get a long string representation for the current locale
  *
  * @return string time value in long time format
  */
  function getLongDateTimeString() {
    if (! isset($this->_cache['llongDateTime'])) {
      $conf = ConfigReader::getInstance();
      $format = $conf->value('language', 'date_longdatetime', 'H:i:s, D F j, Y');
      $this->_cache['llongDateTime'] = $this->getStringByFormat($format);
    }
    return $this->_cache['llongDateTime'];
  }

  /**
  * Get a string representation of this time in the specified format
  *
  * Recognised format characters are as follows:
  *
  * d	Day of the month, 2 digits with leading zeros	01 to 31
  * D	A textual representation of a day, three letters	Mon through Sun
  * j	Day of the month without leading zeros	1 to 31
  * l (lowercase 'L')	A full textual representation of the day of the week	Sunday through Saturday
  *
  * F	A full textual representation of a month, such as January or March	January through December
  * m	Numeric representation of a month, with leading zeros	01 through 12
  * M	A short textual representation of a month, three letters	Jan through Dec
  * n	Numeric representation of a month, without leading zeros	1 through 12
  *
  * Y	A full numeric representation of a year, 4 digits	Examples: 1999 or 2003
  * y	A two digit representation of a year	Examples: 99 or 03
  *
  * a	Lowercase Ante meridiem and Post meridiem	am or pm
  * A	Uppercase Ante meridiem and Post meridiem	AM or PM
  * g	12-hour format of an hour without leading zeros	1 through 12
  * G	24-hour format of an hour without leading zeros	0 through 23
  * h	12-hour format of an hour with leading zeros	01 through 12
  * H	24-hour format of an hour with leading zeros	00 through 23
  * i	Minutes with leading zeros	00 to 59
  * s	Seconds, with leading zeros	00 through 59
  *
  * @return string time value in short time format
  */
  function getStringByFormat($format) {
    static $i18n = null;
    if ($i18n === null) {
      $i18n = date_make_translation_array();
    }
    $s = '';  // return string
    $c = '';  // current character
    $p = '';  // previous character

    #print "Analysing $format";
    $formatLen = strlen($format);
    for ($i=0; $i<$formatLen; $i++) {
      $c = $format[$i];
      if ($i > 0 && $format[$i-1] == '\\') {
        $s .= $c;
        next;
      }
      #print " $i: $c ($s)...";

      switch ($c) {
        // day
        case 'd':
        case 'j':
        // month
        case 'm':
        case 'n':
        // year
        case 'y':
        case 'Y':
        // time
        case 'g':
        case 'G':
        case 'h':
        case 'H':
        case 'i':
        case 's':
          $s .= date($c, $this->ticks);
          break;

        // day
        case 'D':
        case 'l':
        // month
        case 'F':
        case 'M':
        // time
        case 'a':
        case 'A':
          $s .= $i18n[date($c, $this->ticks)];
          #$s .= date($c, $this->ticks);
          break;

        case '\\':
          break;
        default:
          $s .= $c;
          break;
      }
    }
    return $s;
  }

  /**
  * set the date and time from seconds since epoch
  *
  * @param mixed $t ticks
  */
  function setTicks($t) {
    $this->isValid = 1;
    $this->ticks = $t;
    $this->_cache = array();
    //$this->_setStr();
  }

  function _setTicks($s) {
    #echo "SimpleDate::Ticks $s<br />";
    #preDump(debug_backtrace());
    $this->ticks = strtotime($s);
    $this->_cache = array();
    $this->isValid = $this->isValid && ($this->ticks != '' && $this->ticks != -1);
  }

  /**
  * add a whole number of days to the current date-time
  * @param integer $d days to add
  */
  function addDays($d) {
    $this->addTimeParts(0,0,0,$d,0,0);
  }

  /**
  * add a time (i.e. a number of seconds) to the current date-time
  *
  * - SimpleTime object
  * - seconds
  * @param mixed $d days to add
  */
  function addTime($t) {
    if (type_is_a($t, 'SimpleTime')) {
      $this->ticks += $t->seconds();
    } else {
      $this->ticks += $t;
    }
    $this->_cache = array();
  }

  /**
  * add a whole number of seconds to the current date-time
  * @param integer $s seconds to add
  */
  function addSecs($s) {
    $this->ticks += $s;
    $this->_cache = array();
  }

  /**
  * round (down) the date-time to the current day
  *
  * sets the current YYY-MM-DD HH:MM:SS to YYYY-MM-DD 00:00:00
  */
  function dayRound() {
    // this might look as though it would be faster by avoiding the expensive strtotime() call,
    // (10000 reps took average 0.490s with PHP 4.4.2, libc6-2.3.6)
    //    $this->setTimeParts(0,0,0,date('d', $this->ticks),date('m', $this->ticks),date('Y', $this->ticks));
    // but this is actually faster.
    // (10000 reps took average 0.385s with PHP 4.4.2, libc6-2.3.6; that's 21% faster)
    $this->setStr($this->dateString());
  }

  /**
  * round (down) the date-time to the start of the current week (Sunday)
  */
  function weekRound() {
    $this->dayRound();
    $this->addDays(-1 * $this->dow());
  }

  /**
  * round (down) the date-time to the start of the current month (the 1st)
  */
  function monthRound() {
    $this->dayRound();
    $this->addDays(-1*$this->dom()+1);
  }

  /**
  * round (down) the date-time to the start of the current quarter (1st Jan, 1st Apr, 1st Jul, 1st Oct)
  */
  function quarterRound() {
    $month = $this->moy();
    $quarter = floor(($month-1)/3)*3+1;
    $this->setTimeParts(0,0,0,1,$quarter,$this->year());
  }

  /**
  * round (down) the date-time to the start of the current year (1st Jan)
  */
  function yearRound() {
    $this->dayRound();
    $this->addDays(-1*$this->doy());
  }

  /**
  * returns the number of days between two dates ($this - $date)
  * note that it will return fractional days across daylight saving boundaries
  * @param SimpleDate $date date to subtract from this date
  */
  function daysBetween($date) {
    return $this->subtract($date) / (24 * 60 * 60);
  }

  /**
  * returns the number of days between two dates ($this - $date) accounting for daylight saving
  * @param SimpleDate $date date to subtract from this date
  */
  function dsDaysBetween($date) {
    //Calculate the number of days as a fraction, removing fractions due to daylight saving
    $numdays = $this->daysBetween($date);

    //We don't want to count an extra day (or part thereof) just because the day range
    //includes going from summertime to wintertime so the date range includes an extra hour!

    $tz1 = date('Z', $this->ticks);
    $tz2 = date('Z', $date->ticks);
    if ($tz1 == $tz2) {
      // same timezone, so return the computed amount
      #echo "Using numdays $tz1 $tz2 ";
      return $numdays;
    } else {
      // subtract the difference in the timezones to fix this
      #echo "Using tzinfo: $tz1 $tz2 ";
      return $numdays - ($tz2-$tz1) / (24*60*60);
    }
  }

  /**
  * returns the number of days (or part thereof) between two dates ($this - $d)
  * @param SimpleDate $date date to subtract from this date
  */
  function partDaysBetween($date) {
    //we want this to be an integer and since we want "part thereof" we'd normally round up
    //but daylight saving might cause problems....  We also have to include the part day at
    //the beginning and the end

    $startwhole = $date;
    $startwhole->dayRound();
    $stopwhole = $this;
    $stopwhole->ticks += 24*60*60-1;
//     $stopwhole->_setStr();
    $stopwhole->dayRound();

    return $stopwhole->dsDaysBetween($startwhole);
  }

  /**
  * returns the number of seconds between two times
  * NB this does not specially account for daylight saving changes, so will not always give
  * the 24*60*60 for two datetimes that are 1 day apart on the calendar...!
  * @param SimpleDate $date date to subtract from this date
  */
  function subtract($date) {
    #echo "$this->ticks - $date->ticks ";
    return $this->ticks - $date->ticks;
  }

  /**
  * returns a SimpleTime object for just the time component of this date time
  *
  * @return SimpleTime object of just the HH:MM:SS component of this date
  */
  function timePart() {
    $timestring = strftime('%H:%M:%S', $this->ticks);
    return new SimpleTime($timestring);
  }

  /**
  * Sets the time component of this date-time to the specified time but with the same date as currently set
  *
  * The specified time can be HH:MM HH:MM:SS, seconds since midnight or a SimpleTime object
  * @param mixed time to use
  */
  function setTime($s) {
    //echo $this->dump().$s.'<br/>';

    // Benchmarks of 10000 calls to setTime($time)
    //
    // Original algorithm, dayRound() and then addTimeParts(); avg over 10 benchmark runs = 1.28s
    // $this->dayRound();
    // $time = new SimpleTime($s);
    // $this->addTimeParts($time->part('s'), $time->part('i'), $time->part('H'), 0,0,0);

    // setTimeParts() directly; avg over 10 benchmark runs = 0.80s
    $time = new SimpleTime($s);
    //$this->setTimeParts(date('s', $time->ticks),date('i', $time->ticks),date('H', $time->ticks),
    //                    date('d', $this->ticks),date('m', $this->ticks),date('Y', $this->ticks));
    $this->setTimeParts($time->part('s'),        $time->part('i'),        $time->part('H'),
                        date('d', $this->ticks), date('m', $this->ticks), date('Y', $this->ticks));

    return $this;
  }


  /**
  * Sets this SimpleDate to the earlier of $this and $t
  *
  * @param SimpleDate $t
  */
  function min($t) {
    $this->setTicks(min($t->ticks, $this->ticks));
  }

  /**
  * Sets this SimpleDate to the later of $this and $t
  *
  * @param SimpleDate $t
  */
  function max($t) {
    $this->setTicks(max($t->ticks, $this->ticks));
  }

  /**
  * round time down to the nearest $g time-granularity measure
  *
  * Example: if this date time is set to 2005-11-21 17:48 and
  * $g represents 00:15:00 (i.e. 15 minutes) then this date-time would
  * be set to 2005-11-21 17:45 by rounding to the nearest 15 minutes.
  *
  * @param SimpleTime $g
  */
  function floorTime($g) {
    $tp = $this->timePart();
    $tp->floorTime($g);
    $this->setTime($tp->timeString());
  }

  /**
  * Add components to the current date-time
  *
  * Note that this function will take account of daylight saving in unusual (but quite sensible)
  * ways... for example, if $today is a SimpleDate object representing midday the day before
  * daylight saving ends, then $today->addTimeParts(24*60*60) will give a different result
  * to $today->addTimeParts(0,0,0,1). The former will be exactly 24 hours later than the original
  * value of $today (11:00), but the latter will 1 calendar day later (12:00).
  *
  * @param integer $sec  (optional) number of seconds to add to this date-time
  * @param integer $min  (optional) number of minutes to add to this date-time
  * @param integer $hour (optional) number of hours to add to this date-time
  * @param integer $day  (optional) number of days to add to this date-time
  * @param integer $month  (optional) number of months to add to this date-time
  * @param integer $year  (optional) number of years to add to this date-time
  */
  function addTimeParts($sec=0, $min=0, $hour=0, $day=0, $month=0, $year=0) {
    $this->ticks = mktime(
                            date('H',$this->ticks) + $hour,
                            date('i',$this->ticks) + $min,
                            date('s',$this->ticks) + $sec,
                            date('m',$this->ticks) + $month,
                            date('d',$this->ticks) + $day,
                            date('y',$this->ticks) + $year
                        );
    $this->_cache = array();
  }

  /**
  * Set current date-time by components
  *
  * @param integer $sec  (optional) seconds to set
  * @param integer $min  (optional) minutes to set
  * @param integer $hour (optional) hours to add set
  * @param integer $day  (optional) days to add set
  * @param integer $month  (optional) months to set
  * @param integer $year  (optional) years to set
  */
  function setTimeParts($sec=0, $min=0, $hour=0, $day=0, $month=0, $year=0) {
    $this->ticks = mktime(
                            $hour,
                            $min,
                            $sec,
                            $month,
                            $day,
                            $year
                        );
    $this->_cache = array();
  }

  /**
  * return the day of week of the current date.
  * @return integer day of week (0 == Sunday, 6 == Saturday)
  */
  function dow() {
    return date('w', $this->ticks);
  }

  /**
  * return the day of week of the current date as a string (in current language)
  * @return string day of week (Sunday, Monday, etc)
  */
  function dowStr() {
    return $this->_T_(date('l', $this->ticks));
  }

  /**
  * return the (short) day of week of the current date as a string (in current language)
  * @return string day of week (Sun, Mon, etc)
  */
  function dowShortStr() {
    return $this->_T_(date('D', $this->ticks));
  }

  /**
  * return the day of month
  * @return integer day of month (1..31)
  */
  function dom() {
    return intval(date('d', $this->ticks));  // use intval to remove the leading zero
  }

  /**
  * return integer month of year (1..12)
  * @return integer month of year (1..12)
  */
  function moy() {
    return date('m', $this->ticks);
  }

  /**
  * return the month of year of the current date as a string (in current language)
  * @return string month of the year (January, February etc)
  */
  function moyStr() {
    return $this->_T_(date('F', $this->ticks));
  }

  /**
  * return the (short) month of the year of the current date as a string (in current language)
  * @return string month of the year (Jan, Feb etc)
  */
  function moyShortStr() {
    return $this->_T_(date('M', $this->ticks));
  }

  /**
  * day of year (0..365)
  * returns 365 only in leap years
  * @return integer day of year (0..365)
  */
  function doy() {
    return date('z', $this->ticks);
  }

  /**
  * four-digit year (YYYY)
  * @return integer year (e.g. 2005)
  */
  function year() {
    return date('Y', $this->ticks);
  }

  /**
  * dump the datetimestring and ticks in a readable format
  * @param boolean $html (optional) use html line endings
  * @return string datetimestring and ticks
  */
  function dump($html=1) {
    $s = 'ticks = ' . $this->ticks . ', ' . $this->dateTimeString();
    $s .= ($html ? '<br />' : '') . "\n";
    return $s;
  }

  /**
  * translate an individual date-related word (day of week, month of year)
  *
  * @param string   $word    word to translate
  * @returns string          translated word
  */
  function _T_($word) {
    $i18n = date_make_translation_array();
    return $i18n[$word];
  }

} // class SimpleDate


/**
* Simple time class to perform basic time calculations
*
* @package    Bumblebee
* @subpackage Misc
*/
class SimpleTime {
  /**
  * cache of string formatted data
  * @var array
  */
  var $_cache = array();
  /**
  * current time in integer seconds since midnight
  * @var integer
  */
  var $ticks = '';
  /**
  * is set to a valid value
  * @var boolean
  */
  var $isValid = 1;

  /**
  * Constructor for class
  *
  * Accepts the following for the initial time:
  * - HH:MM:SS
  * - HH:MM  (assumes :00 for seconds part)
  * - SimpleTime object
  */
  function SimpleTime($time) {
    #echo "New SimpleTime: $time, $type<br />";
    if (is_numeric($time)) {
      $this->setTicks($time);
    } elseif (type_is_a($time, 'SimpleTime')) {
      $this->setTicks($time->ticks);
    } else {
      $this->setStr($time);
    }
  }

  /**
  * Set time by a string
  */
  function setStr($s) {
    $this->_setTicks($s);
    //$this->_setStr();
  }

  function timeString() {
    if (! isset($this->_cache['time'])) {
      $this->_cache['time'] = sprintf('%02d:%02d', $this->ticks/3600, ($this->ticks%3600)/60);;
    }
    return $this->_cache['time'];
  }

  /**
  * Set time by seconds since midnight
  */
  function setTicks($t) {
    $this->ticks = $t;
    $this->_cache = array();
  }

  function _setTicks($s) {
    if (preg_match('/^(\d+):(\d\d):(\d\d)$/', $s, $t)) {
      #preDump($t);
      $this->ticks = $t[1]*3600+$t[2]*60+$t[3];
    } elseif (preg_match('/^(\d+):(\d\d)$/', $s, $t)) {
      #preDump($t);
      $this->ticks = $t[1]*3600+$t[2]*60;
    } else {
      $this->ticks = 0;
      $this->inValid = 0;
    }
    $this->_cache = array();
  }

  /**
  * subtract seconds $this -  $other
  * @param SimpleTime $other
  * @return integer seconds difference
  */
  function subtract($other) {
    return $this->ticks - $other->ticks;
  }

  /**
  * add seconds to this time
  * @param integer $s
  */
  function addSecs($s) {
    $this->ticks += $s;
    $this->_cache = array();
  }

  /**
  * return current seconds
  * @return integer current seconds since midnight
  */
  function seconds() {
    return $this->ticks;
  }

  /**
  * set this value to the earlier of $this and $other
  * @param SimpleTime $other
  */
  function min($other) {
    $this->setTicks(min($other->ticks, $this->ticks));
  }

  /**
  * set this value to the later of $this and $other
  * @param SimpleTime $other
  */
  function max($t) {
    $this->setTicks(max($t->ticks, $this->ticks));
  }

  /**
  * get a string representation that includes the number of seconds
  * @return string time value in HH:MM:SS format
  */
  function getHMSstring() {
    return sprintf('%02d:%02d:%02d', $this->ticks/3600, ($this->ticks%3600)/60, $this->ticks%60);
  }

  /**
  * get a short string representation for the current locale
  *
  * @return string time value in short time format
  */
  function getShortString() {
    if (! isset($this->_cache['short'])) {
      $conf = ConfigReader::getInstance();
      $format = $conf->value('language', 'date_shorttime', 'H:i');
      $this->_cache['short'] = $this->getStringByFormat($format);
    }
    return $this->_cache['short'];
  }

  /**
  * get a long string representation for the current locale
  *
  * @return string time value in long time format
  */
  function getLongString() {
    if (! isset($this->_cache['long'])) {
      $conf = ConfigReader::getInstance();
      $format = $conf->value('language', 'date_longtime', 'H:i:s');
      $this->_cache['long'] = $this->getStringByFormat($format);
    }
    return $this->_cache['long'];
  }

  /**
  * Get a string representation of this time in the specified format
  *
  * Recognised format characters are as follows:
  *
  * a	Lowercase Ante meridiem and Post meridiem	am or pm
  * A	Uppercase Ante meridiem and Post meridiem	AM or PM
  * g	12-hour format of an hour without leading zeros	1 through 12
  * G	24-hour format of an hour without leading zeros	0 through 23
  * h	12-hour format of an hour with leading zeros	01 through 12
  * H	24-hour format of an hour with leading zeros	00 through 23
  * i	Minutes with leading zeros	00 to 59
  * s	Seconds, with leading zeros	00 through 59
  *
  * @return string time value in short time format
  */
  function getStringByFormat($format) {
    // cache the AM/PM data to reduce time spent in i18n function
    static $i18n = null;
    if ($i18n === null) {
      $i18n = array();
      $i18n['am'] = T_('am');
      $i18n['AM'] = T_('AM');
      $i18n['pm'] = T_('pm');
      $i18n['PM'] = T_('PM');
    }

    $s = '';  // return string
    $c = '';  // current character
    $p = '';  // previous character

    #print "Analysing $format";
    $formatLen = strlen($format);
    for ($i=0; $i<$formatLen; $i++) {
      $c = $format[$i];
      if ($i > 0 && $format[$i-1] == '\\') {
        $s .= $c;
        next;
      }
      #print " $i: $c ($s)...";

      switch ($c) {
        case 'a':
          $s .= $i18n[$this->part('a')];
          break;
        case 'A':
          $s .= $i18n[$this->part('A')];
          break;
        case 'g':
          $s .= ($this->part('h')-1) % 12 + 1;
          break;
        case 'G':
          $s .= $this->part('h');
          break;
        case 'h':
          $s .= sprintf('%02d', ($this->part('h')-1) % 12 + 1);
          break;
        case 'H':
          $s .= sprintf('%02d', $this->part('h'));
          break;
        case 'i':
          $s .= sprintf('%02d', $this->part('i'));
          break;
        case 's':
          $s .= sprintf('%02d', $this->part('s'));
          break;
        case '\\':
          break;
        default:
          $s .= $c;
          break;
      }
      #print " $s<br />";
    }
    return $s;
  }

  /**
  * round time down to the nearest $g time-granularity measure
  *
  * @see SimpleDate::floorTime()
timestring = '';
//     $this->_setStr();  * @param SimpleTime $g time granularity
  */
  function floorTime($g) {
    $gt = $g->seconds();
    $this->setTicks(floor(($this->ticks+1)/$gt)*$gt);
  }

  /**
  * round time up to the nearest $g time-granularity measure
  *
  * @see SimpleTime::floorTime()
  * @param SimpleTime $g time granularity
  */
  function ceilTime($g) {
    $gt = $g->seconds();
    $this->setTicks(ceil(($this->ticks-1)/$gt)*$gt);
  }

  /**
  * Obtain hour, minute or seconds parts of the time
  *
  * return hour, minute or seconds parts of the time, emulating the date('H', $ticks) etc
  * functions, but not using them as they get too badly confused with timezones to be useful
  * in many situations
  *
  * @param char $s time part to obtain (valid parts: h, i, s for hours, mins, secs)
  * @return integer part of the time
  */
  function part($s) {
    switch ($s) {
      //we don't actually care about zero padding in this case.
      case 'H':
      case 'h':
        return floor($this->ticks/(3600));
      //let's just allow 'm' to give minutes as well, as it's easier
      case 'i':
      case 'm':
        return floor(($this->ticks%3600) / 60);
      case 's':
        return floor($this->ticks % 60);
      case 'a':
        return ($this->ticks % 86400 < 43200) ? 'am' : 'pm';
      case 'A':
        return ($this->ticks % 86400 < 43200) ? 'AM' : 'PM';
    }
    //we can't use this as we're not actually using the underlying date-time types here.
    //return date($s, $this->ticks);
  }

  /**
  * Add another time to this time
  *
  * @param SimpleTime $t time to add to this one
  */
  function addTime($t) {
    $this->ticks += $t->ticks;
    $this->_cache = array();
  }

  /**
  * dump the timestring and ticks in a readable format
  * @param boolean $html (optional) use html line endings
  * @return string timestring and ticks
  */
  function dump($html=1) {
    $s = 'ticks = ' . $this->ticks . ', ' . $this->timeString();
    $s .= ($html ? '<br />' : '') . "\n";
    return $s;
  }
} // class SimpleTime


// We need to define these just so that the date formatting routines can always
// return English names for the days and dates and the translation layer will
// provide the correct translation. We can't just use setlocale() and strftime()
// to do this because they only work if the locale is installed on the server and
// this is frequently not the case (which is why we are using gettext emulation
// to begin with!)
function date_make_translation_array() {
  static $i18n = null;

  if ($i18n !== null) return $i18n;

  $i18n = array();
  $i18n['Monday']    = T_('Monday');
  $i18n['Mon']       = T_('Mon');
  $i18n['Tuesday']   = T_('Tuesday');
  $i18n['Tue']       = T_('Tue');
  $i18n['Tues']      = T_('Tue');
  $i18n['Wednesday'] = T_('Wednesday');
  $i18n['Wed']       = T_('Wed');
  $i18n['Thursday']  = T_('Thursday');
  $i18n['Thu']       = T_('Thu');
  $i18n['Thurs']     = T_('Thu');
  $i18n['Friday']    = T_('Friday');
  $i18n['Fri']       = T_('Fri');
  $i18n['Saturday']  = T_('Saturday');
  $i18n['Sat']       = T_('Sat');
  $i18n['Sunday']    = T_('Sunday');
  $i18n['Sun']       = T_('Sun');

  $i18n['January']   = T_('January');
  $i18n['Jan']       = T_('Jan');
  $i18n['February']  = T_('February');
  $i18n['Feb']       = T_('Feb');
  $i18n['March']     = T_('March');
  $i18n['Mar']       = T_('Mar');
  $i18n['April']     = T_('April');
  $i18n['Apr']       = T_('Apr');
  $i18n['May']       = T_('May');
  $i18n['June']      = T_('June');
  $i18n['Jun']       = T_('Jun');
  $i18n['July']      = T_('July');
  $i18n['Jul']       = T_('Jul');
  $i18n['August']    = T_('August');
  $i18n['Aug']       = T_('Aug');
  $i18n['September'] = T_('September');
  $i18n['Sep']       = T_('Sep');
  $i18n['October']   = T_('October');
  $i18n['Oct']       = T_('Oct');
  $i18n['November']  = T_('November');
  $i18n['Nov']       = T_('Nov');
  $i18n['December']  = T_('December');
  $i18n['Dec']       = T_('Dec');

  $i18n['am'] = T_('am');
  $i18n['AM'] = T_('AM');
  $i18n['pm'] = T_('pm');
  $i18n['PM'] = T_('PM');

  return $i18n;
}

?>
