<?php
##
## this file name is 'class.calendar.php'
##
## calendar object
##
## [author]
##  - Chilbong Kim, <san2(at)linuxchannel.net>
##  - http://linuxchannel.net/
##
## [changes]
##  - 2020.01.13 : polar() to simple
##  - 2018.08.12 : tojd0() function name bug fixed
##  - 2018.08.11 : (int) to (float)
##  - 2018.07.31 : calendar::_todate()
##  - 2018.07.28 : bug to delete san2@2018.07.28
##  - 2016.04.07 : date('N')
##  - 2012.01.12 : add find_nw_in_month(), current_us_dst()
##  - 2012.01.07 : support auto Julian calendar(date < 1582-10-15), _getjd(), _todate()
##  - 2012.01.05 : add deltaT()
##  - 2011.12.26 : add more utils
##  - 2011.11.13 : some add/moved utils and added location_kr()
##  - 2011.04.29 : comment patch(UT = TT - delta T)
##  - 2011.04.24 : bug fixed, calendar::date('D')
##  - 2010.05.20 : bug fixed, calendar::date('W') of ISO-8601
##  - 2010.05.19 : added calendar::date('J'), is a JD
##  - 2010.05.18 : support calendar::date('I'), DST(daylight saving time) and all support of date() format.
##  - 2009.06.08 : some
##  - 2007.07.28 : support win32 PHP4(on Microsoft Windows) and Unix
##  - 2005.04.12 : new build
##
## [valid date]
##  - unix timestamp base: 1902-01-01 00:00:00 <= date <= 2037-12-31 23:59:59 (guess)
##  - JD(Julian Day) base: JD 0.0 == BC 4713-01-01 12:00 UTC <= Gregorian date <= AD 9999 (guess)
##
## [download & online source view]
##  - http://ftp.linuxchannel.net/devel/php_calendar/
##
## [demo]
##  - http://linuxchannel.net/gaggle/calendar.php // yet
##
## [references]
##  - http://www.linuxchannel.net/docs/solar-24terms.txt
##  - http://www.linuxchannel.net/docs/lunar.txt
##  - http://www.merlyn.demon.co.uk/    // Astronomy and Astronautics
##  - http://www.boogle.com/info/cal-overview.html
##  - http://star-www.st-and.ac.uk/~fv/webnotes/index.html  // Positional Astronomy
##  - http://www.jgiesen.de/astro/astroJS/sunriseJS/index.htm // in rsTL.js
##  - http://bodmas.org/astronomy/riset.html // same as rsTL.js
##  - http://eclipse.gsfc.nasa.gov/SEhelp/deltatpoly2004.html // deltaT
##  - http://blueedu.dothome.co.kr/xe/index.php?mid=astro&category=190&document_srl=11131 // deltaT
##  - http://www.moshier.net/ deltaT from aa200c.zip: JPL ephemeris reader
##
## [julian date] -- this program
##     input date time   => return JD => return date time 
##  1582-10-03 00:00 UTC => 2299158.5 => 1582-10-03 00:00 Julian date
##  1582-10-04 00:00 UTC => 2299159.5 => 1582-10-04 00:00 Julian date
##  1582-10-05 00:00 UTC => 2299160.5 => 1582-10-15 00:00 -
##  1582-10-06 00:00 UTC => 2299161.5 => 1582-10-16 00:00 -
##  1582-10-07 00:00 UTC => 2299162.5 => 1582-10-17 00:00 -
##  1582-10-08 00:00 UTC => 2299163.5 => 1582-10-18 00:00 -
##  1582-10-09 00:00 UTC => 2299164.5 => 1582-10-19 00:00 -
##  1582-10-10 00:00 UTC => 2299165.5 => 1582-10-20 00:00 -
##  1582-10-11 00:00 UTC => 2299166.5 => 1582-10-21 00:00 -
##  1582-10-12 00:00 UTC => 2299167.5 => 1582-10-22 00:00 -
##  1582-10-13 00:00 UTC => 2299168.5 => 1582-10-23 00:00 -
##  1582-10-14 00:00 UTC => 2299169.5 => 1582-10-24 00:00 -
##  1582-10-15 00:00 UTC => 2299160.5 => 1582-10-15 00:00 Gregorian date
##  1582-10-16 00:00 UTC => 2299161.5 => 1582-10-16 00:00 Gregorian date
##
## [time format]
##  - UT = TT - dT
##  - DT = in korean 'yeok-hak-si'
##  - TDT(Terrestrial Dynamical Time) = DT(Dynamical Time) = TT(Terrestrial Time)
##  - local = time(),date()
##  - UT    = gmtime(),gmdate() or local-time_offset
##  - TT    = UT + dT // use the astro caculating
##  - JD <-> UT <-> GST <-> LST
##            <------------->
##     <------------->
##     <-------------------->
##  - jd2ut(JD,-time_offset) <-> ut2jd(ut,+time_offset)
##  - ut2gst(ut) <-> gst2ut(gst)
##  - gst2lst(gst,+lon_offset) <-> lst2gst(lst,-lon_offset)
##
## [float]
##  - 1 second = 1/86400 = 0.000 011 574 074 074 074 = %.8f // Sufficient
##  - 1 arcsec = 1/3600  = 0.000 277 78 = %.8f // Sufficient
##
## [JD/TT]
##  ----------------------------------------------------------------------------------------
##   JD (not TT)                            JD or TT                      TT base
##  ----------------------------------------------------------------------------------------
##  _todate($JD, $_timezone=NULL)           jd2jd0($JD,$_timezone=NULL)   tt2j2000t($TT)
##   gmdate($format,$JD=NULL)               tojd0($JD, $_timezone=NULL)  _jd2j2000t($JD)
##   date($format,$JD=NULL,$_timezone=NULL) jd0($JD, $_timezone=NULL)     ecliptice($t=0)
##   jd2lst($JD, $lon=0)                    -                             tt2ecliptice($TT)
##   deltaT($Y='', $JD=NULL)                -                            _jd2ecliptice($JD)
##   -                                      -                             psi($t=0)
##   -                                      -                             eps($t=0)
##  ----------------------------------------------------------------------------------------
##
## [compare 1. PHP4(win32) internal functions VS this method(object)]
##  - time()                ( mkjd() )
##  - date()               _date(utime)      // private, base on unix timestamp, BC 4313 ~ AD 9999(guess)
##  - gmdate()             _gmdate(utime)    // private
##  - mktime()             _mktime()         // private, support native value, BC 4313 ~ AD 9999(guess)
##  - gmmktime()           _gmmktime()       // private
##
## [compare 2. PHP4(win32) calendar module VS this method(object)]
##  - gregoriantojd()       mkjd(),gmmkjd()  // public, support hour, minute, seconds, BC 4313 ~ AD 9999(guess)
##  - jdtogregorian()       date(JD)         // public, same as PHP `date()', but JD base
##                          gmdate(JD)       // public, JD base
##  - jddayofweek()         jddayofweek(JD)  // public, similar
##  - cal_days_in_month()   days_in_month()  // public, similar
##  - unixtojd()           _utime2jd()       // private, same above
##  - jdtounix()           _jd2utime()       // private, same above
##
## [usage] -- see that last row of this source
##  $jd = calendar::mkjd(23,59,59,12,31,1901);
##  echo calendar::date('Y-m-d H:i:s T',$jd);
##

@error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED);
if(!defined('__TIMEZONE__')) define('__TIMEZONE__',date('Z')/3600); // system timezone for hours

class calendar
{
  ## private, get Julian day -- same as gregoriantojd()
  ##
  ## http://en.wikipedia.org/wiki/Julian_day
  ## http://ko.wikipedia.org/wiki/%EC%9C%A8%EB%A6%AC%EC%9A%B0%EC%8A%A4%EC%9D%BC
  ## ftp://ssd.jpl.nasa.gov/pub/eph/export/C-versions/hoffman/
  ## http://blog.jidolstar.com/482
  ## http://geekswithblogs.net/bosuch/archive/2011/05/13/determining-julian-date-in-c-or-java--android.aspx
  ##
  ## Julian date
  ## JD 0.0 == BC 4713-01-01 12:00 UTC == -4712-01-01 12:00:00 UTC
  ##
  ## input: local date time or unix timestamp
  ## $_timezone: input date timezone
  ##
  ## return: JD (UTC base)
  ##
  function &_getjd($Y, $M=1, $D=1, $H=21, $I=0, $S=0, $_timezone=NULL)
  {
    ## delta T
    ##
    //$dT = 64/86400; // is J2000.0 delta 'T', TT = UT + dT

    ## timezone
    ##
    if($_timezone === NULL) $_timezone = __TIMEZONE__; // system timezone for hours

    ## check arguments
    ##
    if(func_num_args() < 3) // $Y is local unix_timestamp
    { list($Y,$M,$D,$H,$I,$S) = explode(' ',calendar::_date('Y n j G i s',$Y)); } // local date time

    if($M < 3) { $M += 12; $Y--; }

    ## auto change to julian date
    ##
    $isjulian = sprintf('%d%02d%02d',$Y,$M,$D);

    $D += ($H/24.0) + ($I/1440.0) + ($S/86400.0);
    $A = floor($Y/100.0); // excel INT() == floor() != (int)
    $B = ($isjulian<15821015) ? 0 : (2.0 - $A + floor($A/4.0)); // juliantojd() $B = 0
    $JD = floor(365.25*($Y+4716.0)) + floor(30.6001*($M+1.0)) + $D + $B - 1524.5;

    ## force local time to UTC
    ##
    $JD -= $_timezone/24;

    ## change to TT
    ##
    //$JD += $dT; // UT = TT - dT, do not plus $S or $JD of sprintf() internal

    $JD = sprintf('%.8f',$JD);
    $D = sprintf('%.8f',$JD-2451545.0); // float, number of days
    $J = sprintf('%.4f',2000.0+($D/365.25)); // // Jxxxx.xxxx format
    $T = sprintf('%.8f',$D/36525.0); // // Julian century

    return array($JD,$J,$D,$T); // JD (UTC base)
  }

  ## private, get JD(julian day) from unix timestamp -- same as unixtojd()
  ##
  ## D = get the number of days from base JD
  ## D = JD(Julian Day) - 2451545.0, base JD(J2000.0)
  ##
  ## base position (J2000.0), 2000-01-01 12:00:00 UTC
  ## as   mktime(12,0,0,1,1,2000) == 946695600 unix timestamp at KST
  ## as gmmktime(12,0,0,1,1,2000) == 946728000 unix timestamp at UTC
  ##
  ## display date:      KST = GMT + 9h
  ##     numberic: mktime() = gmmktime() - 9h
  ##
  ## input: utime (local time)
  ## return: JD (UTC base)
  ##
  function &_utime2jd($utime)
  {
    $D = $utime - 946728000; // change local time to UTC base, see above comments
    $D = sprintf('%.8f',$D/86400); // float, number of days
    $JD = sprintf('%.8f',$D+2451545.0); // float, Julian Day

    return $JD; // float
  }

  ## private, get unix timestamp from JD -- same as jdtounix()
  ##
  ## 1970-01-01 00:00:00 UTC == 2440587.5 JD == J1970.0
  ## 1970-01-01 00:00:00 UTC == 1970-01-01 09:00:00 KST == 0 unix timestamp
  ## date(0) == auto change to local date time
  ##
  ## input: JD (UTC base)
  ## return: utime (from J1970.0)
  ##
  function &_jd2utime($JD)
  {
    $D = $JD - 2440587.5; // shift to J1970.0
    $utime = round($D*86400);

     return $utime;
  }

  ## private, check datetime that there is null or not null
  ##
  ## display date:      KST = GMT + 9h
  ##     numberic: mktime() = gmmktime() - 9h
  ##
  ## return: local date time
  ##
  function &__check_datetime($argc, &$Y, &$M, &$D, &$H, &$I, &$S, $_timezone)
  {
    if($argc >= 6) return TRUE;

    list($Y,$_M,$_D,$_H,$_I,$_S) = explode(' ',gmdate('Y n j G i s',time()+$_timezone*3600));
    if($argc < 5) $D = $_D;
    if($argc < 4) $M = $_M;
    if($argc < 3) $S = $_S;
    if($argc < 2) $I = $_I;
    if($argc < 1) $H = $_H;
  }

  ## public, make JD -- match to mktime()
  ##
  ## Julian date
  ## JD 0.0 == BC 4713-01-01 12:00 UTC == -4712-01-01 12:00:00 UTC ~ AD 9999
  ##
  function &mkjd($H=21, $I=0, $S=0, $M=1, $D=1, $Y=NULL, $_timezone=NULL)
  {
    if($_timezone === NULL) $_timezone = __TIMEZONE__;

    calendar::__check_datetime(func_num_args(),$Y,$M,$D,$H,$I,$S,$_timezone);
    list($JD) = calendar::_getjd($Y,$M,$D,$H,(int)$I,(int)$S,$_timezone);

    return $JD; // folat, JD (UTC base)
  }

  ## private, get unix timestamp from date -- same as mktime()
  ##
  ## valid date: 1902-01-01 00:00:00 ZONE <= date <= 2037-12-31 23:59:59 ZONE
  ##
  function &_mktime($H=0, $I=0, $S=0, $M=1, $D=1, $Y=NULL, $_timezone=NULL)
  {
    if($_timezone === NULL) $_timezone = __TIMEZONE__;

    ## bug to delete san2@2018.07.28
    //if($Y>1970 && $Y<2038) return gmmktime($H,$I,$S,$M,$D,$Y)+($_timezone*3600); // local utime

    calendar::__check_datetime(func_num_args(),$Y,$M,$D,$H,$I,$S,$_timezone);

    $JD = calendar::mkjd($H,$I,$S,$M,$D,$Y,$_timezone);
    $utime = calendar::_jd2utime($JD);

     return $utime; // float, local utime
  }

  function &gmmkjd($H=0, $I=0, $S=0, $M=1, $D=1, $Y=NULL) // input date is UTC base
  {
    return calendar::mkjd($H,$I,$S,$M,$D,$Y,0);
  }

  function &_gmmktime($H=0, $I=0, $S=0, $M=1, $D=1, $Y=NULL)
  {
    return calendar::_mktime($H,$I,$S,$M,$D,$Y,0);
  }

  function &_gmdate($format, $utime=NULL)
  {
    return calendar::_date($format,$utime,0);
  }

  function &gmdate($format, $JD=NULL)
  {
    return calendar::date($format,$JD,0);
  }

  ## private, get date(gregorian) from JD -- same as jdtogregorian()
  ##
  ## input: JD (UTC base, not TT)
  ## return: local date
  ##
  function &_todate($JD, $_timezone=NULL)
  {
    static $_months = array(31,0,31,30,31,30,31,31,30,31,30,31); // patch san2@2018.07.31

    ## change TT to UT
    ##
    //$JD -= 64/86400; // is J2000.0 delta 'T', UT = TT - dT, patch san2@2007.07.28

    ## change UT to local time
    ##
    if($_timezone === NULL) $_timezone = __TIMEZONE__;
    $JD += $_timezone/24; // JD to local zone(JD)

    $T = $JD + 0.5; // shift to 00:00:00
    $Z = floor($T);
    $W = floor(($Z-1867216.25) / 36524.25); // for gregorian
    $X = floor($W / 4); // for gregorian
    $A = ($Z<2299161.0) ? $Z : floor($Z + 1 + $W - $X); // is julian ? Z : gregorian
    $B = floor($A + 1524.0);
    $C = floor(($B-122.1) / 365.25);
    $D = floor(365.25 * $C);
    $E = floor(($B-$D) / 30.6001);

    $_d = $B - $D - floor(30.6001*$E);
    $_m = ($E<14) ? ($E-1) : ($E-13);
    $_y = ($_m>2) ? ($C-4716) : ($C-4715);

    $_U = $T - $Z; // flat, it's a UT 00:00 ~ 23:59:59
    $_U = $_U * 24.0; // to hours
    $_h = (int)$_U;
    $_U = ($_U - $_h) * 60.0; // to minutes
    $_i = (int)$_U;
    $_U = ($_U - $_i) * 60.0; // to seconds
    $_s = (int)($_U + 0.001); // patch for round-off error, by san2@2012.01.10

    ## patch san2@2012.02.19
    ##
    if($_s > 59)
    {
        $_s -= 60; $_i++;
        if($_i > 59)
        {
            $_i -= 60; $_h++;
            if($_h > 23)
            {
                $_h -= 24; $_d++;

                ## patch san2@2018.07.31
                ##
                $_tmpm = $_months[$_m-1];
                if($_m == 2)
                {
                    if($_d > 28)
                    {
                        if(calendar::isleap($_y))
                        {
                            if($_d > 29) { $_d -= 29; $_m++; }
                        }
                        else { $_d -= 28; $_m++; }
                    }
                }
                else if($_d > $tmpm)
                {
                    $_d -= $tmpm; $_m++;
                    if($_m > 12) { $_m -= 12; $_y++; }
                }
            }
        }
    }

    return array($_y,$_m,$_d,$_h,$_i,$_s);
  }

  ## private,  same as `date()' function, base on unix timestamp(support Microsoft Windows PHP4)
  ##
  ## display date:      KST = GMT + 9h
  ##     numberic: mktime() = gmmktime() - 9h
  ##
  ## return: local date time
  ##
  function &_date($format, $utime=NULL, $_timezone=NULL)
  {
    if($utime === NULL) $utime = time(); // local utime
    if($_timezone === NULL) $_timezone = __TIMEZONE__;

    ## bug to delete san2@2018.07.28
    //if($utime>=0 && $utime<2145884400) return gmdate($format,($utime+$_timezone*3600)); // local date time

    $JD = calendar::_utime2jd($utime);
    $str = calendar::date($format,$JD,$_timezone); // local date time

     return $str; // local date time
  }

  ## public, same as `date()' function, but base on JD by UT(delta T)
  ##
  ## valid JD: JD 0.0 == BC 4713-01-01 12:00 UTC == -4712-01-01 12:00:00 UTC ~ AD 9999
  ##
  function &date($format, $JD=NULL, $_timezone=NULL)
  {
    static $_weeks = array('Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday');
    static $_months = array('January','February','March','April','May','June','July','August',
        'September','Octorber','November','December');
    static $_ordinals = array(1=>'st',21=>'st',31=>'st',2=>'nd',22=>'nd',3=>'rd',23=>'rd');

    if($_timezone === NULL)
    {
        $_timezone = __TIMEZONE__;
        $_dolocaldate = TRUE;
    }

    $_timezonemark = $_timezone<0 ? '-' : '+';

    if(func_num_args()<2 || $JD===NULL) $JD = calendar::mkjd(); // current JD(UT)
    if(!$format || is_array($format)) return calendar::_todate($JD,$_timezone); // array

    list($Y,$M,$D,$H,$I,$S) = calendar::_todate($JD,$_timezone);

    ## get DST(daylight saving time), patch san2@2010.05.19
    ## http://www.alpheratz.net/php/RiseSetPHP // cf
    ##
    if($Y<1916 || $Y>2037 || !$_dolocaldate)
    {
        $_DST = 0;
        $_T = 'GMT'.$_timezonemark.$_timezone.'H'; // Timezone abbreviation
    } else
    {
        list($_DST,$_T) = explode(' ',date('I T',mktime(12,0,0,$M,$D,$Y)));
    }

    ## patch san2@2010.05.19
    ##
    if($Y>1970 && $Y<2038) $_U = mktime($H,$I,$S,$M,$D,$Y);
    else $_U = calendar::_jd2utime($JD);

    $_Y = sprintf('%d',$Y); // not %04d
    $_M = sprintf('%02d',$M);
    $_D = sprintf('%02d',$D);
    $_H = sprintf('%02d',$H);
    $_I = sprintf('%02d',$I);
    $_S = sprintf('%02d',$S);
    $_Z = $_timezone*3600; // seconds
    $_O = $_timezonemark.gmdate('Hi',$_Z);
    $_w = calendar::jddayofweek($JD + ($_timezone/24)); // JD apply to local TimeZone
    $_W = calendar::weeknumber($Y,$M,$D); // ISO-8601
    $_R = substr($_weeks[$_W],0,3).", $_D ".substr($_months[$M-1],0,3)." $H:$I:$S $_O";
    $_P = substr($_O,0,3).':'.substr($_O,-2);
    $_C = "${_Y}-${_M}-${_D}T${_H}:${_I}:${_S}${_P}";
    $_N = ($_w == 0) ? 7 : $_w; // patch san2@2016.04.07
    $_o = ($M==12 && $_W==1) ? $Y+1 : (($M==1 && $_W>=52) ? $Y-1 : $Y);

    $r = '';
    $nextskip = FALSE;
    $l = strlen($format);
    for($i=0; $i<$l; $i++)
    {
        $char = $format[$i];
        if(!trim($char)) { $r .= $char; continue; }
        if($nextskip) { $r .= $char; $nextskip = FALSE; continue; } // patch san2@2010.05.19
        if($char == '\\') { $nextskip = TRUE; continue; } else $nextskip = FALSE;
        switch($char)
        {
            case 'a': $r .= ($H<12) ? 'am' : 'pm'; break;
            case 'A': $r .= ($H<12) ? 'AM' : 'PM'; break;
            case 'B': $r .= calendar::itime($H,$I,$S,$_timezone); break;
            case 'c': $r .= $_C; break; // ISO 8601 date (added in PHP5)
            case 'd': $r .= $_D; break;
            case 'D': $r .= substr($_weeks[$_w],0,3); break;
            case 'F': $r .= $_months[$M-1]; break;
            case 'g': $r .= (($H-1) % 12) + 1; break;
            case 'G': $r .= $H; break;
            case 'h': $r .= sprintf('%02d',(($H-1)%12)+1); break;
            case 'H': $r .= $_H; break;
            case 'i': $r .= $_I; break;
            case 'I': $r .= $_DST; break;
            case 'j': $r .= $D; break;
            case 'J': $r .= $JD; break;
            case 'l': $r .= $_weeks[$_W]; break;
            case 'L': $r .= calendar::isleap($Y); break;
            case 'm': $r .= $_M; break;
            case 'M': $r .= substr($_months[$M-1],0,3); break;
            case 'n': $r .= $M; break;
            case 'N': $r .= $_N; break; // ISO-8601, day of the week, 1(Monday) ~ 7(Sunday)
            case 'o': $r .= $_o; break; // ISO-8601 year number
            case 'O': $r .= $_O; break;
            case 'P': $r .= $_P; break;
            case 'r': $r .= $_R; break;
            case 's': $r .= $_S; break;
            case 'S': $r .= $_ordinals[$D] ? $_ordinals[$D] : 'th'; break;
            case 't': $r .= calendar::days_in_month($Y,$M); break;
            case 'T': $r .= $_T; break;
            case 'u': $r .= date('u'); break;
            case 'U': $r .= $_U; break;
            case 'w': $r .= $_w; break; // JD to local zone
            case 'W': $r .= sprintf('%02d',$_W); break; // ISO-8601
            case 'y': $r .= substr($_Y,-2); break;
            case 'Y': $r .= $_Y; break; // patch san2@2018.07.31
            case 'z': $r .= calendar::dayofyear($Y,$M,$D); break; // starting from 0
            case 'Z': $r .= $_Z; break; // KST zone +9H, in seconds

            default : $r .= $char; break;
        }
    }

    return $r; // string
  }

  ## public, get leap year
  ##
  ## #define isleap(y) ((((y) % 4) == 0 && ((y) % 100) != 0) || ((y) % 400) == 0)
  ##
  ## +-- 4*Y ! // normal ------------------ FALSE
  ## `-- 4*Y
  ##      |-- 100*Y ! // leap ------------- TRUE
  ##      `-- 100*Y
  ##           |-- 400*Y ! // normal ------ FALSE
  ##           `-- 400*Y   // leap -------- TRUE
  ##
  ## but, 4000*Y is not normal year, is leap year
  ## http://user.chollian.net/~kimdbin/re/leap_year.html
  ##
  function &isleap($year)
  {
    ## for julian calendar
    ##
    if($year < 1582) return ($year%4) ? FALSE : TRUE; // for julian calendar

    ## for gregorian calendar
    ##
    if($year % 4) return FALSE;
    else if($year % 100) return TRUE;
    else if($year % 400) return FALSE;

    return TRUE; // else 400*Y
  }

  ## public, get week idx
  ##
  ## 0(sun), 1(mon), 2(tue), 3(wed), 4(thu), 5(fri), 6(sat)
  ##
  function &jddayofweek($JD)
  {
    return floor($JD+1.5) % 7; // integer
  }

  function &dayofyear($Y, $M, $D)
  {
    list($JDS) = calendar::_getjd($Y,1,1,12,0,0,0);
    list($JDE) = calendar::_getjd($Y,$M,$D,12,0,0,0);

    return (int)($JDE - $JDS); // starting from 0
  }

  ## ISO-8601, start on Monday
  ##
  function &weeknumber_m($Y, $M, $D)
  {
    list($JD) = calendar::_getjd($Y,1,1,12,0,0,0);

    $widx = calendar::jddayofweek($JD);
    $days = calendar::dayofyear($Y,$M,$D);

    $midx = ($widx==0) ? 7 : $widx; // to ISO-8601
    $days += ($midx>1) ? ($midx-7-1) : 0;
    $n = ceil($days/7);

    if($n >= 52) // last week
    {
        list($JD) = calendar::_getjd($Y,12,31,12,0,0,0);
        $lidx = calendar::jddayofweek($JD);
        if($widx>0 && $lidx>0) $n = 1;
    }
    else if($n <= 1) // first week
    {
        list($JD) = calendar::_getjd($Y-1,1,1,12,0,0,0);
        $widx = calendar::jddayofweek($JD);
        $n = ($widx>1) ? 52 : 53;
    }

    return $n; // integer
  }

  ## ISO-8601, start on Monday
  ## patch san2@2010.05.20
  ##
  function &weeknumber($Y, $M, $D)
  {
    list($JD) = calendar::_getjd($Y,1,1,12,0,0,0);

    $widx = calendar::jddayofweek($JD) - 1;
    $days = calendar::dayofyear($Y,$M,$D);

    $midx = ($widx<0) ? 6 : $widx;
    $days = ($midx<4) ? ($days+$midx) : ($days+$midx-7);
    $n = floor($days/7) + 1;

    if($n == 0) // ok, first week or last of preious year
    {
        list($JD) = calendar::_getjd($Y-1,1,1,12,0,0,0);
        $widx = calendar::jddayofweek($JD);
        $n = ($widx>4) ? 52 : 53;
    }
    else if($n > 52) // last week or first week of next year
    {
        list($JD) = calendar::_getjd($Y,12,31,12,0,0,0);
        $widx = calendar::jddayofweek($JD);
        if($widx>0 && $widx<4) $n = 1; // Monday ~ Wednesday
    }

    return $n; // integer
  }

  ## public, get swatch internet time, base BMT = UTC + 1
  ## same as date('B')
  ##
  function &itime($H, $I, $S, $_timezone)
  {
    $B = ($H-$_timezone+1)*41.666 + $I*0.6944 + $S*0.01157;
    $B = ($B>0) ? $B : $B+1000.0;

    return sprintf('%03d',$B);
  }

  ## public
  ##
  function &days_in_month($year, $month)
  {
    static $_months = array(31,0,31,30,31,30,31,31,30,31,30,31);

    if($year.$month == 158210) return 21; // 1582-10-01 ~ 31 == 21 days

    $n = $_months[$month-1];
    $n = $n ? $n : (calendar::isleap($year) ? 29 : 28);

    return $n; // integer
  }

  ## find a day of the n'th week in month
  ##
  ## $n = 1'th week, 2nd week, ... 5'th week, Sunday is the start of a week
  ## $w = Sunday(0), Monday(1), ... Saturday(6)
  ##
  ## default) find a day of the first week of sunday in this month
  ##
  function &find_nw_in_month($n=1, $w=0, $month=NULL, $year=NULL)
  {
    if($n<1 || $n>5) $n = 1;
    if($w<0 || $w>6) $w = 0;

    if($month===NULL || $year===NULL)
    {
        list($_y,$_m) = explode(' ',date('Y n'));
        if($month === NULL) $month = $_m;
        if($year === NULL) $year = $_y;
    }

    list($JD) = calendar::_getjd($year,$month,1,12,0,0,0);
    list($t,$week) = explode(' ',calendar::date('t w',$JD));
    $sunday = ($week==0) ? 1 : (8-$week); // day of first sunday
    $day = ($sunday+$w) + ($n-1)*7;
    $day -= ($day>$t) ? $t : 0; // next month

    if($year == 1582)
    {
        if($month==9 && $n==5 && $w>4) $day += 10;
        else if($month==10 && $n<3) $day += 10;
        else if($month==10 && $n==3 && $w==0) $day += 10; // 31
    }

    return $day;
  }

  ## USA DST(daylight saving time)
  ##
  ## Asia/Seoul
  ## Asia/Tokyo
  ## Asia/Shanghai
  ## America/Los_Angeles
  ##
  function &current_us_dst()
  {
    $ctz = date_default_timezone_get();
    date_default_timezone_set('America/Los_Angeles');
    $dst = date('I'); // is DST ?
    date_default_timezone_set($ctz);

    return $dst;
  }

  ## this function does working
  ##
  ## USA 1986-2006, from  first Sunday in April to the  last Sunday in  October
  ## USA      2007, from second Sunday in March to the first Sunday in November
  ## http://www.neoprogrammics.com/perpetual_calendar_algorithms/Daylight_Saving_Time_USA.php
  ##
  ## EU Since 1998, from the last Sunday in March to the last Sunday in October
  ## http://www.neoprogrammics.com/perpetual_calendar_algorithms/Summer_Time_EU.php
  ##
  function &_current_us_dst($_timezone=-8)
  {
    $ustime = time() - date('Z') + ($_timezone*3600); // gmmktime() == mktime() why?
    $stime = mktime(12,0,0,3,calendar::find_nw_in_month(2,0,3)); // second Sunday in March
    $etime = mktime(12,0,0,11,calendar::find_nw_in_month(1,0,11)); // first Sunday in November

    return  ($ustime>$stime && $ustime<$etime) ? 1 : 0;
  }

  ## public
  ##
  function &month_info($year, $month)
  {
    if($year<1902 || $year>2037)
    {
        list($JD) = calendar::_getjd($year,$month,1,12,0,0,0);
        $term = calendar::days_in_month($year,$month);
        $week = calendar::jddayofweek($JD); // week idx
        $minfo = array($week,$term);
    } else
    {
        $utime = mktime(12,0,0,$month,1,$year);
        $minfo = explode(' ',date('w t',$utime));
    }

    return $minfo; // array($week,$term)
  }  

  ## [utils]
  ##
  ## deg
  ##   deg2dms
  ##   deg2dm
  ##   deg2h
  ##   deg2hms
  ##   deg2hm
  ## h
  ##   h2hms
  ##   h2hm
  ##   h2hm30 // add
  ##   h2deg
  ##   h2dms
  ##   h2dm
  ## dms(dm)
  ##   dms2deg
  ##   dms2dm
  ##   dms2h
  ##   dms2hms
  ##   dms2hm
  ## hms(hm)
  ##   hms2h
  ##   hms2hm
  ##   hms2deg
  ##   hms2dms
  ##   hms2dm
  ##

  function &deg2dms($deg, $singed=FALSE)
  {
    if($singed) $singed = '+';
    if($deg <0) { $singed = '-'; $deg = abs($deg); }

    $d = floor($deg);
    $deg = ($deg-$d) * 60;
    $m = floor($deg);
    $deg = ($deg-$m) * 60;
    $s = floor($deg); // not round()

    return $singed.sprintf('%02d %02d %02d',$d,$m,$s);
  }

  function &deg2dm($deg, $singed=FALSE)
  {
    if($singed) $singed = '+';
    if($deg < 0) { $singed = '-'; $deg = abs($deg); }

    $d = floor($deg);
    $m = ($deg-$d) * 60;
    $m = round($m); // round

    if($m > 59) { $m -= 60; $d++; }

    return $singed.sprintf('%02d %02d',$d,$m);
  }

  function &deg2h($deg)
  {
    return ($deg/15);
  }

  function &deg2hms($deg, $singed=FALSE)
  {
    return calendar::deg2dms($deg/15,$singed);
  }

  function &deg2hm($deg, $singed=FALSE)
  {
    return calendar::deg2dm($deg/15,$singed);
  }

  function &h2hms($h)
  {
    return calendar::deg2dms($h); // match h to d
  }

  function &h2hm($h)
  {
    return str_replace(' ',':',calendar::deg2dm($h)); // match h to d
  }

  function &h2hm30($h)
  {
    $d = floor($h);
    $m = ($h-$d) * 60;
    $m = round($m); // round

    $k = floor($m/15);
    $k = ceil($k/2);
    $m = $k * 30;

    if($m > 59) { $m -= 60; $d++; }

    return sprintf('%02d:%02d',$d,$m);    
  }

  function &h2deg($h)
  {
    return ($h*15);
  }

  function &h2dms($h)
  {
    return calendar::deg2dms($h*15);
  }

  function &h2dm($h)
  {
    return calendar::deg2dm($h*15);
  }

  function &dms2deg($dms, $f=8)
  {
    if(!is_array($dms) && preg_match('/^[\d.+-]+$/',$dms)) return sprintf("%.${f}f",$dms);

    if(is_array($dms)) list($d,$m,$s) = $dms;
    else
    {
        $dms = preg_replace('/[^\d.+-]+/',' ',trim($dms));
        list($d,$m,$s) = explode(' ',$dms);
    }

    if(preg_match('/^-/',$d)) $deg = sprintf("%.${f}f",$d-$m/60-$s/3600); // -0 to < 0
    else $deg = sprintf("%.${f}f",$d+$m/60+$s/3600);

    return $deg;
  }

  function &dms2dm($dms)
  {
    return calendar::deg2dm(calendar::dms2deg($dms));
  }

  function &dms2h($dms)
  {
    return (calendar::dms2deg($dms) / 15);
  }

  function &dms2hms($dms)
  {
    return calendar::deg2hms(calendar::dms2deg($dms));
  }

  function &dms2hm($dms)
  {
    return calendar::deg2hm(calendar::dms2deg($dms));
  }

  function &hms2h($hms)
  {
    return calendar::dms2deg($hms); // match h to d
  }

  function &hms2hm($hms)
  {
    return str_replace(' ',':',calendar::dms2dm($hms)); // match h to d
  }

  function &hms2deg($hms)
  {
    return (calendar::dms2deg($hms) * 15); // match h to d and *15
  }

  function &hms2dms($hms)
  {
    return calendar::deg2dms(calendar::hms2deg($hms));
  }

  function &hms2dm($hms)
  {
    return calendar::deg2dm(calendar::hms2deg($hms));
  }

  function &str2deg($str)
  {
    list($od,$om,$os,$td,$tm,$ts) = preg_split('/[^\d.+-]+/',trim($str));
    if($ts != '') // 0 is not null
    {
        $r[0] = calendar::dms2deg("$od $om $os",2);
        $r[1] = calendar::dms2deg("$td $tm $ts",2);
    }
    else if($td != '') // 0 is not null
    {
        $r[0] = calendar::dms2deg("$od $om",2);
        $r[1] = calendar::dms2deg("$os $td",2);
    }
    else $r = array($od,$om);

    return $r;
  }

  ## 1 solar year == 365.242190 days == 31556925.216 seconds
  ## 1 degress == (31556925.216 seconds / 360 degress) == 87658.1256 seconds
  ##
  function &deg2solartime($deg)
  {
    return sprintf('%.4f',$deg*87658.1256); // seconds
  }

  function &deg2valid($deg)
  {
    $deg = ($deg<0) ? fmod($deg,360)+360.0 : fmod($deg,360);

    return $deg;
  }

  function &moon2valid($moon)
  {
    if($moon < 1) $moon = 1;
    else if($moon > 12) $moon = 12;

    return (int)$moon;
  }

  ## RA to valid ( 0 ~ 24)
  ##
  function &ra2valid($ra)
  {
    $ra = ($ra<0) ? fmod($ra,24.0)+24.0 : fmod($ra,24.0);

    return $ra;
  }

  ## HA valid (-12h to 12h)
  ##
  function &ha2valid($ha)
  {
    $ha = fmod($ha,24.0);
    $ha += ($ha>12.0) ? -24.0 : (($ha<-12.0) ? 24.0 : 0);

    return $ha;
  }

  ## alt/dec from South or North
  ## -90 < alt < +90
  ##
  function &alt2valid($alt, $base='S')
  {
    $base = strtoupper($base);
    $alt = fmod($alt,360.0);
    $alt +=  ($alt<-180.0) ? 360.0 : (($alt>180.0) ? -360.0 : 0);

    if(abs($alt) < 90.0) return array($alt,$base);

    $alt = ($alt>0) ? (180.0-$alt) : (-180.0-$alt);
    $base = ($base=='S') ? 'N' : 'S'; // reverse

    return array($alt,$base);
  }

  function &jd2mjd($JD)
  {
    return ($JD - 2400000.5);
  }

  function &mjd2jd($MJD)
  {
    return ($MJD + 2400000.5);
  }

  ## JD to 00:00:00 UT (JD to 0.5)
  ##
  function &jd2jd0($JD, $_timezone=NULL)
  {
    if($_timezone === NULL) $_timezone = __TIMEZONE__; // system timezone for hours

    $offset = 0.5 - ($_timezone/24.0);
    $JD0 = floor($JD); // to noon
    $JD0 += (($JD-$JD0)<$offset) ? ($offset-1) : $offset; // PM ? (-1+0.5) : 0.5

    return $JD0;
  }

  function &tojd0($JD, $_timezone=NULL)
  {
    return calendar::jdtojd0($JD,$_timezone);
  }

  function &jd0($JD, $_timezone=NULL)
  {
    return calendar::jdtojd0($JD,$_timezone);
  }

  ## note: TT base
  ##
  function &tt2j2000t($TT)
  {
    $t = ($TT-2451545.0) / 36525.0; // J2000.0 century

    return sprintf('%.12f',$t); // float
  }

  ## alias of tt2j2000t(TT)
  ##
  function &_jd2j2000t($JD)
  {
    return calendar::tt2j2000t($JD);
  }

  ## IAU2006
  ## 84381".406 == 23 26' 21".406
  ## 84381".406 - 46".836769 t - 0".0001831 t2 + 0".00200340 t3 - 0".000000576 t4 - 0".0000000434 t5
  ##
  ## $t is TT base J2000.0 Julian Century
  ##
  function &_ecliptice($t=0)
  {
    $t2 = $t*$t;
    $t3 = $t*$t2;
    $e = 84381.406 - (46.836769*$t) - (0.0001831*$t2) + (0.00200340*$t3)
        - (0.000000576*$t2*$t2) - (0.0000000434*$t2*$t3);

    return sprintf('%.8f',$e/3600); // degrees
  }

  ## 84381.448" == 23 26' 21.448" at J2000.0
  ## http://www.neoprogrammics.com/obliquity_of_the_ecliptic/
  ## http://www.neoprogrammics.com/obliquity_of_the_ecliptic/Obliquity_Of_The_Ecliptic.php
  ##
  ## $t is TT base J2000.0 Julian Century
  ##
  function &ecliptice($t=0)
  {
    ## J. Laskar's Formula For The Mean Obliquity 
    ## 
    $u = $t/100; // ($JD-2451545.0)/3652500.0, 10000 Julian years from J2000.0
    $u2 = $u*$u;
    $u3 = $u*$u2;
    $u4 = $u*$u3;
    $e = 84381.448 - (4680.93*$u) - (1.55*$u2)
        + (1999.25*$u3) - (51.38*$u4) - (249.67*$u2*$u3)
        - (39.05*$u3*$u3) + (7.12*$u3*$u4) + (27.87*$u4*$u4)
        + (5.79*$u4*$u5) + (2.45*$u5*$u5);
    $e /= 3600; // to degrees

    ## True Obliquity == e + eps
    ##
    $eps = calendar::eps($t); // degrees, $t is J2000.0 Julian century

    return sprintf('%.8f',$e+$eps); // degrees
  }

  ## TT base
  ##
  function &tt2ecliptice($TT)
  {
    return calendar::ecliptice(($TT-2451545.0)/36525.0);
  }

  ## JD is TT base
  ##
  function &_jd2ecliptice($JD)
  {
    return calendar::ecliptice(($JD-2451545.0)/36525.0);
  }

  ## delta psi
  ##
  ## Algorithm for computing the Nutation (Delta Psi) In Ecliptical Longitude
  ## http://www.neoprogrammics.com/nutations/dPsi_2000B_Algorithm_Guide.html
  ##
  ## true = mean + psi
  ## use in: lst, L(sun/moon)
  ##
  ## $t is TT base J2000.0 Julian Century
  ##
  function &psi($t=0)
  {
    $t2 = $t*$t;
    $t3 = $t*$t2;
    $t4 = $t*$t3;

    $L0 = (450160.398036 - 6962890.5431*$t + 7.4722*$t2  + 0.007702*$t3 - 0.00005939*$t4) / 3600.0;
    $L  = deg2rad((485868.249036 + 1717915923.2178*$t + 31.8792*$t2 + 0.051635*$t3 - 0.00024470*$t4) / 3600.0);
    $LS = deg2rad((1287104.79305 + 129596581.0481*$t  - 0.5532*$t2  + 0.000136*$t3 - 0.00001149*$t4) / 3600.0);
    $D  = deg2rad((1072260.70369 + 1602961601.2090*$t - 6.3706*$t2  + 0.006593*$t3 - 0.00003169*$t4) / 3600.0);
    $F  = deg2rad((335779.526232 + 1739527262.8478*$t - 12.7512*$t2 - 0.001037*$t3 + 0.00000417*$t4) / 3600.0);
    $OM = deg2rad($L0);

    $psi
    = (-172064161 - 174666*$t)*sin($OM) + 33386*cos($OM)
    + (-13170906 - 1675*$t)*sin(2*($F - $D + $OM)) - 13696*cos(2*($F - $D + $OM))
    + (-2276413 - 234*$t)*sin(2*($F + $OM)) + 2796*cos(2*($F + $OM))
    + (2074554 + 207*$t)*sin(2*$OM) - 698*cos(2*$OM)
    + (1475877 - 3633*$t)*sin($LS) + 11817*cos($LS)
    + (-516821 + 1226*$t)*sin($LS + 2*($F - $D + $OM)) - 524*cos($LS + 2*($F - $D + $OM))
    + (711159 + 73*$t)*sin($L) - 872*cos($L)
    + (-387298 - 367*$t)*sin(2*$F + $OM) + 380*cos(2*$F + $OM)
    + (-301461 - 36*$t)*sin($L + 2*($F + $OM)) + 816*cos($L + 2*($F + $OM))
    + (215829 - 494*$t)*sin(2*($F - $D + $OM) - $LS) + 111*cos(2*($F - $D + $OM) - $LS)
    + (128227 + 137*$t)*sin(2*($F - $D) + $OM) + 181*cos(2*($F - $D) + $OM)
    + (123457 + 11*$t)*sin(2*($F + $OM) - $L) + 19*cos(2*($F + $OM) - $L)
    + (156994 + 10*$t)*sin(2*$D - $L) - 168*cos(2*$D - $L)
    + (63110 + 63*$t)*sin($L + $OM) + 27*cos($L + $OM)
    + (-57976 - 63*$t)*sin($OM - $L) - 189*cos($OM - $L)
    + (-59641 - 11*$t)*sin(2*($F + $D + $OM) - $L) + 149*cos(2*($F + $D + $OM) - $L)
    + (-51613 - 42*$t)*sin($L + 2*$F + $OM) + 129*cos($L + 2*$F + $OM)
    + (45893 + 50*$t)*sin(2*($F - $L) + $OM) + 31*cos(2*($F - $L) + $OM)
    + (63384 + 11*$t)*sin(2*$D) - 150*cos(2*$D)
    + (-38571 - $t)*sin(2*($F + $D + $OM)) + 158*cos(2*($F + $D + $OM))
    + 32481*sin(2*($F - $LS - $D + $OM))
    - 47722*sin(2*($D - $L)) - 18*cos(2*($D - $L))
    + (-31046 - $t)*sin(2*($L + $F + $OM)) + 131*cos(2*($L + $F + $OM))
    + 28593*sin($L + 2*($F - $D + $OM)) - cos($L + 2*($F - $D + $OM))
    + (20441 + 21*$t)*sin(2*$F + $OM - $L) + 10*cos(2*$F + $OM - $L)
    + 29243*sin(2*$L) - 74*cos(2*$L)
    + 25887*sin(2*$F) - 66*cos(2*$F)
    + (-14053 - 25*$t)*sin($LS + $OM) + 79*cos($LS + $OM)
    + (15164 + 10*$t)*sin(2*$D - $L + $OM) + 11*cos(2*$D - $L + $OM)
    + (-15794 + 72*$t)*sin(2*($LS + $F - $D + $OM)) - 16*cos(2*($LS + $F - $D + $OM))
    + 21783*sin(2*($D - $F)) + 13*cos(2*($D - $F))
    + (-12873 - 10*$t)*sin($L - 2*$D + $OM) - 37*cos($L - 2*$D + $OM)
    + (-12654 + 11*$t)*sin($OM - $LS) + 63*cos($OM - $LS)
    - 10204*sin(2*($F + $D) + $OM - $L) + 25*cos(2*($F + $D) + $OM - $L)
    + (16707 - 85*$t)*sin(2*$LS) - 10*cos(2*$LS)
    - 7691*sin($L + 2*($F + $D + $OM)) + 44*cos($L + 2*($F + $D + $OM))
    - 11024*sin(2*($F - $L)) - 14*cos(2*($F - $L))
    + (7566 - 21*$t)*sin($LS + 2*($F + $OM)) - 11*cos($LS + 2*($F + $OM))
    + (-6637 - 11*$t)*sin(2*($F + $D) + $OM) + 25*cos(2*($F + $D) + $OM)
    + (-7141 + 21*$t)*sin(2*($F + $OM) - $LS) + 8*cos(2*($F + $OM) - $LS)
    + (-6302 - 11*$t)*sin(2*$D + $OM) + 2*cos(2*$D + $OM)
    + (5800 + 10*$t)*sin($L + 2*($F - $D) + $OM) + 2*cos($L + 2*($F - $D) + $OM)
    + 6443*sin(2*($L + $F - $D + $OM)) - 7*cos(2*($L + $F - $D + $OM))
    + (-5774 - 11*$t)*sin(2*($D - $L) + $OM) - 15*cos(2*($D - $L) + $OM)
    - 5350*sin(2*($L + $F) + $OM) + 21*cos(2*($L + $F) + $OM)
    + (-4752 - 11*$t)*sin(2*($F - $D) + $OM - $LS) - 3*cos(2*($F - $D) + $OM - $LS)
    + (-4940 - 11*$t)*sin($OM - 2*$D) - 21*cos($OM - 2*$D)
    + 7350*sin(2*$D - $L - $LS) - 8*cos(2*$D - $L - $LS)
    + 4065*sin(2*($L - $D) + $OM) + 6*cos(2*($L - $D) + $OM)
    + 6579*sin($L + 2*$D) - 24*cos($L + 2*$D)
    + 3579*sin($LS + 2*($F - $D) + $OM) + 5*cos($LS + 2*($F - $D) + $OM)
    + 4725*sin($L - $LS) - 6*cos($L - $LS)
    - 3075*sin(2*($F + $OM - $L)) - 2*cos(2*($F + $OM - $L))
    - 2904*sin(3*$L + 2*($F + $OM)) + 15*cos(3*$L + 2*($F + $OM))
    + 4348*sin(2*$D - $LS) - 10*cos(2*$D - $LS)
    - 2878*sin($L - $LS + 2*($F + $OM)) + 8*cos($L - $LS + 2*($F + $OM))
    - 4230*sin($D) + 5*cos($D)
    - 2819*sin(2*($F + $D + $OM) - $L - $LS) + 7*cos(2*($F + $D + $OM) - $L - $LS)
    - 4056*sin(2*$F - $L) + 5*cos(2*$F - $L)
    - 2647*sin(2*($F + $D + $OM) - $LS) + 11*cos(2*($F + $D + $OM) - $LS)
    - 2294*sin($OM - 2*$L) - 10*cos($OM - 2*$L)
    + 2481*sin($L + $LS + 2*($F + $OM)) - 7*cos($L + $LS + 2*($F + $OM))
    + 2179*sin(2*$L + $OM) - 2*cos(2*$L + $OM)
    + 3276*sin($LS + $D - $L) + cos($LS + $D - $L)
    - 3389*sin($L + $LS) + 5*cos($L + $LS)
    + 3339*sin($L + 2*$F) - 13*cos($L + 2*$F)
    - 1987*sin(2*($F - $D) + $OM - $L) - 6*cos(2*($F - $D) + $OM - $L)
    - 1981*sin($L + 2*$OM)
    + 4026*sin($D - $L) - 353*cos($D - $L)
    + 1660*sin(2*$F + $D + 2*$OM) - 5*cos($D + 2*($F + $OM))
    - 1521*sin(2*($F + 2*$D + $OM) - $L) + 9*cos(2*($F + 2*$D + $OM) - $L)
    + 1314*sin($LS + $D + $OM - $L)
    - 1283*sin(2*($F - $D - $LS) + $OM)
    - 1331*sin($L + 2*$F + 2*$D + $OM) + 8*cos($L + 2*($F + $D) + $OM)
    + 1383*sin(2*($F - $L + $D + $OM)) - 2*cos(2*($F - $L + $D + $OM))
    + 1405*sin(2*$OM - $L) + 4*cos(2*$OM - $L)
    + 1290*sin($L + $LS + 2*($F - $D + $OM));

    $psi /= 36000000000.0; // to grees

    return $psi; // float, degrees
  }

  ## delta epsilon
  ##
  ## Algorithm For computing the Nutation in Obliquity (Delta Epsilon) of the Ecliptic
  ## http://www.neoprogrammics.com/nutations/dEps_2000B_Algorithm_Guide.html
  ## http://www.neoprogrammics.com/obliquity_of_the_ecliptic/Obliquity_Of_The_Ecliptic.php
  ##
  ## true = mean + eps
  ## use in: eclipice, B(sun/moon)
  ##
  ## $t is TT base J2000.0 Julian Century
  ##
  function &eps($t=0)
  {
    $t2 = $t*$t;
    $t3 = $t*$t2;
    $t4 = $t*$t3;

    $L0 = (450160.398036 - 6962890.5431*$t + 7.4722*$t2  + 0.007702*$t3 - 0.00005939*$t4) / 3600.0;
    $L  = deg2rad((485868.249036 + 1717915923.2178*$t + 31.8792*$t2 + 0.051635*$t3 - 0.00024470*$t4) / 3600.0);
    $LS = deg2rad((1287104.79305 + 129596581.0481*$t  - 0.5532*$t2  + 0.000136*$t3 - 0.00001149*$t4) / 3600.0);
    $D  = deg2rad((1072260.70369 + 1602961601.2090*$t - 6.3706*$t2  + 0.006593*$t3 - 0.00003169*$t4) / 3600.0);
    $F  = deg2rad((335779.526232 + 1739527262.8478*$t - 12.7512*$t2 - 0.001037*$t3 + 0.00000417*$t4) / 3600.0);
    $OM = deg2rad($L0);

    $eps
    = (92052331 + 9086*$t)*cos($OM) + 15377*sin($OM)
    + (5730336 - 3015*$t)*cos(2*($F - $D + $OM)) - 4587*sin(2*($F - $D + $OM))
    + (978459 - 485*$t)*cos(2*($F + $OM)) + 1374*sin(2*($F + $OM))
    + (-897492 + 470*$t)*cos(2*$OM) - 291*sin(2*$OM)
    + (73871 - 184*$t)*cos($LS) - 1924*sin($LS)
    + (224386 - 677*$t)*cos($LS + 2*($F - $D + $OM)) - 174*sin($LS + 2*($F - $D + $OM))
    - 6750*cos($L) + 358*sin($L)
    + (200728 + 18*$t)*cos(2*$F + $OM) + 318*sin(2*$F + $OM)
    + (129025 - 63*$t)*cos($L + 2*($F + $OM)) + 367*sin($L + 2*($F + $OM))
    + (-95929 + 299*$t)*cos(2*($F - $D + $OM) - $LS) + 132*sin(2*($F - $D + $OM) - $LS)
    + (-68982 - 9*$t)*cos(2*($F - $D) + $OM) + 39*sin(2*($F - $D) + $OM)
    + (-53311 + 32*$t)*cos(2*($F + $OM) - $L) - 4*sin(2*($F + $OM) - $L)
    - 1235*cos(2*$D - $L) + 82*sin(2*$D - $L)
    - 33228*cos($L + $OM) - 9*sin($L + $OM)
    + 31429*cos($OM - $L) - 75*sin($OM - $L)
    + (25543 - 11*$t)*cos(2*($F + $D + $OM) - $L) + 66*sin(2*($F + $D + $OM) - $L)
    + 26366*cos($L + 2*$F + $OM) + 78*sin($L + 2*$F + $OM)
    + (-24236 - 10*$t)*cos(2*($F - $L) + $OM) + 20*sin(2*($F - $L) + $OM)
    - 1220*cos(2*$D) + 29*sin(2*$D)
    + (16452 - 11*$t)*cos(2*($F + $D + $OM)) + 68*sin(2*($F + $D + $OM))
    - 13870*cos(2*($F - $LS - $D + $OM))
    + 477*cos(2*($D - $L)) - 25*sin(2*($D - $L))
    + (13238 - 11*$t)*cos(2*($L + $F + $OM)) + 59*sin(2*($L + $F + $OM))
    + (-12338 + 10*$t)*cos($L + 2*($F - $D + $OM)) - 3*sin($L + 2*($F - $D + $OM))
    - 10758*cos(2*$F + $OM - $L) - 3*sin(2*$F + $OM - $L)
    - 609*cos(2*$L) + 13*sin(2*$L)
    - 550*cos(2*$F) + 11*sin(2*$F)
    + (8551 - 2*$t)*cos($LS + $OM) - 45*sin($LS + $OM)
    - 8001*cos(2*$D + $OM - $L) - sin(2*$D + $OM - $L)
    + (6850 - 42*$t)*cos(2*($LS + $F - $D + $OM)) - 5*sin(2*($LS + $F - $D + $OM))
    - 167*cos(2*($D - $F)) + 13*sin(2*($D - $F))
    + 6953*cos($L - 2*$D + $OM) - 14*sin($L - 2*$D + $OM)
    + 6415*cos($OM - $LS) + 26*sin($OM - $LS)
    + 5222*cos(2*($F + $D) + $OM - $L) + 15*sin(2*($F + $D) + $OM - $L)
    + (168 - $t)*cos(2*$LS) + 10*sin(2*$LS)
    + 3268*cos($L + 2*($F + $D + $OM)) + 19*sin($L + 2*($F + $D + $OM))
    + 104*cos(2*($F - $L)) + 2*sin(2*($F - $L))
    - 3250*cos($LS + 2*($F + $OM)) - 5*sin($LS + 2*($F + $OM))
    + 3353*cos(2*($F + $D) + $OM) + 14*sin(2*($F + $D) + $OM)
    + 3070*cos(2*($F + $OM) - $LS) + 4*sin(2*($F + $OM) - $LS)
    + 3272*cos(2*$D + $OM) + 4*sin(2*$D + $OM)
    - 3045*cos($L + 2*($F - $D) + $OM) - sin($L + 2*($F - $D) + $OM)
    - 2768*cos(2*($L + $F - $D + $OM)) - 4*sin(2*($L + $F - $D + $OM))
    + 3041*cos(2*($D - $L) + $OM) - 5*sin(2*($D - $L) + $OM)
    + 2695*cos(2*($L + $F) + $OM) + 12*sin(2*($L + $F) + $OM)
    + 2719*cos(2*($F - $D) + $OM - $LS) - 3*sin(2*($F - $D) + $OM - $LS)
    + 2720*cos($OM - 2*$D) - 9*sin($OM - 2*$D)
    - 51*cos(2*$D - $L - $LS) + 4*sin(2*$D - $L - $LS)
    - 2206*cos(2*($L - $D) + $OM) + sin(2*($L - $D) + $OM)
    - 199*cos($L + 2*$D) + 2*sin($L + 2*$D)
    - 1900*cos($LS + 2*($F - $D) + $OM) + sin($LS + 2*($F - $D) + $OM)
    - 41*cos($L - $LS) + 3*sin($L - $LS)
    + 1313*cos(2*($F - $L + $OM)) - sin(2*($F - $L + $OM))
    + 1233*cos(3*$L + 2*($F + $OM)) + 7*sin(3*$L + 2*($F + $OM))
    - 81*cos(2*$D - $LS) + 2*sin(2*$D - $LS)
    + 1232*cos($L - $LS + 2*($F + $OM)) + 4*sin($L - $LS + 2*($F + $OM))
    - 20*cos($D) - 2*sin($D)
    + 1207*cos(2*($F + $D + $OM) - $L - $LS) + 3*sin(2*($F + $D + $OM) - $L - $LS)
    + 40*cos(2*$F - $L) - 2*sin(2*$F - $L)
    + 1129*cos(2*($F + $D + $OM) - $LS) + 5*sin(2*($F + $D + $OM) - $LS)
    + 1266*cos($OM - 2*$L) - 4*sin($OM - 2*$L)
    - 1062*cos($L + $LS + 2*($F + $OM)) - 3*sin($L + $LS + 2*($F + $OM))
    - 1129*cos(2*$L + $OM) - 2*sin(2*$L + $OM)
    - 9*cos($LS + $D - $L)
    + 35*cos($L + $LS) - 2*sin($L + $LS)
    - 107*cos($L + 2*$F) + sin($L + 2*$F)
    + 1073*cos(2*($F - $D) + $OM - $L) - 2*sin(2*($F - $D) + $OM - $L)
    + 854*cos($L + 2*$OM)
    - 553*cos($D - $L) - 139*sin($D - $L)
    - 710*cos(2*($F + $OM) + $D) - 2*sin(2*($F + $OM) + $D)
    + 647*cos(2*($F + 2*$D + $OM) - $L) + 4*sin(2*($F + 2*$D + $OM) - $L)
    - 700*cos($LS + $D + $OM - $L)
    + 672*cos(2*($F - $LS - $D) + $OM)
    + 663*cos($L + 2*($F + $D) + $OM) + 4*sin($L + 2*($F + $D) + $OM)
    - 594*cos(2*($F - $L + $D + $OM)) - 2*sin(2*($F - $L + $D + $OM))
    - 610*cos(2*$OM - $L) + 2*sin(2*$OM - $L)
    - 556*cos($L + $LS + 2*($F - $D + $OM));

    $eps /= 36000000000.0; // to degrees

    return $eps; // degrees, float
  }

  ## ec2eq(L, B, e=23.4595) to array(RA,dec)
  ## eq2ec(ra, dec, e=23.4594) to array(L,B)
  ## eq2ha(dec, lat, alt=0) to HA
  ## eq2alt(dec, lat, ha) to alt
  ## eq2az(dec, lat, ha) to AZ
  ## az2polar(az) to polar

  ## sin(dec) = sin(B)*cos(e) + cos(B)*sin(e)*sin(L)
  ## tan(RA) = (sin(L)*cos(e) - tan(B)*sin(e)) / cos(L) = x/y
  ##
  function &ec2eq($L, $B, $e=23.4395)
  {
    $L = deg2rad($L);
    $B = deg2rad($B);
    $e = deg2rad($e);

    $sindec = sin($B)*cos($e) + cos($B)*sin($e)*sin($L);
    $dec = rad2deg(asin($sindec));

    $x = sin($L)*cos($e) - tan($B)*sin($e);
    $y = cos($L);
    $ra = rad2deg(atan2($x,$y)) / 15;
    $ra = ($ra<0) ? fmod($ra,24.0)+24.0 : fmod($ra,24.0); // ra valid, calendar::ra2valid($ra)

    return array($ra,$dec);
  }

  ## sin(B) = sin(dec)*cos(e) - cos(dec)*sin(e)*sin(RA)
  ## tan(L) = (sin(RA)*cos(e) + tan(dec)*sin(e)) / cos(RA) = x/y
  ##
  function &eq2ec($ra, $dec, $e=23.4395)
  {
    $ra = deg2rad($ra); // input ra is a degrees
    $dec = deg2rad($dec);
    $e = deg2rad($e);

    $sinb = sin($dec)*cos($e) - cos($dec)*sin($e)*sin($ra);
    $B = rad2deg(asin($sinb));

    $x = sin($ra)*cos($e) + tan($dec)*sin($e);
    $y = cos($ra);
    $L = rad2deg(atan2($x,$y));

    if($L < 0) $L += 360.0;

    return array($L,$B);
  }

  ## sin(h) = sin(d)*sin(La) + cos(d)*cos(La)*cos(Ha)
  ##
  function &eq2ha($dec, $lat, $alt=0)
  {
    $dec = deg2rad($dec);
    $lat = deg2rad($lat);
    $alt = deg2rad($alt);

    $x = sin($dec)*sin($lat) - sin($alt);
    $y = cos($dec)*cos($lat);
    $ha = (180 - rad2deg(acos($x/$y))) / 15; // to hour angle
    $ha = calendar::ha2valid($ha);

    return sprintf('%.8f',$ha);
  }

  ## sin(h) = sin(d)*sin(La) + cos(d)*cos(La)*cos(Ha)
  ##
  function &eq2alt($dec, $lat, $ha)
  {
    $dec = deg2rad($dec);
    $lat = deg2rad($lat);
    $ha = deg2rad($ha); // input ha is a degrees

    $sinh = sin($dec)*sin($lat) + cos($dec)*cos($lat)*cos($ha);
    $alt = rad2deg(asin($sinh));

    return sprintf('%.8f',$alt);
  }

  ## Azimuth
  ## tan(A) = -cos(dec)*sin(Ha) / (sin(dec)*cos(lat) - cos(dec)*sin(lat)*cos(Ha))
  ##
  function &eq2az($dec, $lat, $ha)
  {
    $dec = deg2rad($dec);
    $ha = deg2rad($ha); // input ha is a degrees
    $lat = deg2rad($lat);

    $x = -cos($dec)*sin($ha);
    $y = sin($dec)*cos($lat) - cos($dec)*sin($lat)*cos($ha);

    return calendar::compass($x,$y);
  }

  ## Azimuth
  ## http://kr.php.net/manual/en/function.atan2.php
  ##
  function &compass($x, $y)
  {
    if($x==0 && $y==0) return 0; // ...or return 360

    $deg = ($x<0) ? rad2deg(atan2($x,$y))+360.0 : rad2deg(atan2($x,$y));

    return sprintf('%.8f',$deg); // float degress
  }

  ## Azimuth
  ## tan(A) = -cos(dec)*sin(Ha) / (sin(dec)*cos(lat) - cos(dec)*sin(lat)*cos(Ha))
  ## http://kr.php.net/manual/en/function.atan2.php
  ##
  function &polar($x, $y)
  {
    $NS = ($y>=0) ? 'N' : 'S';
    $EW = ($x>=0) ? 'E' : 'W';
    return $NS.$EW;
  }

  ## div by 45.0 degrees
  ## 337.5 -  22.5 N
  ##  22.5 -  67.5 NE
  ##  67.5 - 112.5 E
  ## 112.5 - 157.5 SE
  ## 157.5 - 202.5 S
  ## 202.5 - 247.5 SW
  ## 247.5 - 292.5 W
  ## 292.5 - 337.5 NW
  ##
  function &az2polar($az)
  {
    static $_polars = array('N','NE','E','SE','S','SW','W','NW','N');

    $az = ($az<0) ? fmod($az,360)+360.0 : fmod($az,360); // to valid
    $k = floor(($az+22.5)/45); // shift and 8 pos

    return $_polars[$k];
  }

  function &location_kr($l=0)
  {
    static $_locations = array
    (
        array(126.9669,37.5497,'서울'),        // 서울
        array(131.87,37.24,'독도'),        // 독도
        array(129.37,36.04,'포항'),        // 포항
        array(126.35,36.52,'안면도(충남)'),    // 안면
    );

    if($l === NULL) return $_locations;

    if(is_array($l))
    {
        $r[0] = calendar::dms2deg($l[0],4);
        $r[1] = calendar::dms2deg($l[1],4);
    }
    else if(preg_match('/^[\d]+$/',$l)) $r = $_locations[$l];
    else if(preg_match('/[^\d.+-]/',$l)) $r = calendar::str2deg($l);
    else $r = $_locations[0];

    return ($r ? $r : $_locations[0]);
  }

  /***
  ## JD to Local/Greenwich Sidreal Time
  ## http://www.jgiesen.de/astro/astroJS/sunriseJS/index.htm // in rsTL.js
  ## JD 2400000.5 == 1858-11-17 00:00:00 UT
  ##
  ## note: JD or TT
  ##
  function &_jd2lst($JD, $longit=0)
  {
    $MJD = $JD - 2400000.5;
    $MJD0 = floor($MJD);
    $ut = ($MJD - $MJD0) * 24.0;    
    $t = ($MJD0 - 51544.5) / 36525.0;

    ## is a ut (24 hours unit)
    ##
    $gst = 6.697374558 + (1.00273790935*$ut) + (8640184.812866 + (0.093104-0.0000062*$t)*$t) * $t/3600.0;

    $lst = $gst + ($longit/15);
    $lst = ($lst<0) ? fmod($lst,24)+24.0 : fmod($lst,24);

    return sprintf('%.8f',$lst); // 24 hours unit (hour angle)
  }
  ***/

  ## Calculate the mean sidereal time at the meridian of Greenwich of a given date.
  ## returns apparent sidereal time (decimal hours).
  ## Formula 11.1, 11.4 pg 83 Jean Meeus: Astronomical Algorithms
  ## http://www.jgiesen.de/elevaz/basics/meeus.htm
  ##
  ## stellarium-0.11.1/src/core/planetsephems/sideral_time.c
  ## result same as _jd2lst() but, this logic some good speed
  ##
  ## note: JD is UT base, not TT
  ##
  function &jd2lst($JD, $lon=0)
  {
    $D = $JD - 2451545.0;
    $t = $D / 36525.0; // J2000.0 base Julian Century
    $t2 = $t*$t;
    $t3 = $t*$t2;

    ## calculate mean angle
    ##
    $gst = 280.46061837 + (360.98564736629*$D) + (0.000387933*$t2) - ($t3/38710000.0);

    ## convert degress to hour angle
    ##
    $lst = ($gst+$lon) / 15; // to lst hour angle
    $lst = ($lst<0) ? fmod($lst,24)+24.0 : fmod($lst,24);

    return sprintf('%.8f',$lst); // 24 hours unit (hour angle)
  } 

  ## http://eclipse.gsfc.nasa.gov/SEhelp/deltatpoly2004.html // deltaT
  ## http://eclipse.gsfc.nasa.gov/5MCSE/5MCSEcatalog.txt // compare of solar eclipses
  ## http://eclipse.gsfc.nasa.gov/5MCLE/5MKLEcatalog.txt // compare of lunar eclipses
  ## http://www.moshier.net/ deltaT from aa200c.zip: JPL ephemeris reader
  ##
  ## input $Y example:
  ## BC 0002-01-01 ==> -0001-01-01 == (int)-1 + (int)(1/12) == -0.9167 year
  ##
  ## difference of dT (cal - NASA eclipses Ephemerides) at 5MKLEcatalog.txt (VSOP87/ELP2000-82)
  ##   - cal date: -2127 ~ 3000
  ##   - max = 343.43
  ##   - std = 79.077349064742
  ##
  function &deltaT($Y='', &$JD=NULL)
  {
    ## check year, JD (is not TT)
    ##
    if($JD !== NULL) list($Y,$M) = calendar::_todate($JD,0);
    else if($Y !== '')
    {
        if(preg_match('/[^\d.+-]/',$Y)) // Y is float or integer
        {
            if(preg_match('/BC/i',$Y)) $BC = TRUE;
            $Y = preg_replace('/[^\d.+-]+/','',$Y); // rewrite
            if($BC) $Y = (abs($Y)-1) * -1;
        }
        $x = $Y - floor($Y);
        if($x > 0.0) { $Y = floor($Y); $M = (int)($x*12); } // not (int), see above comment
        else $M = 1;
    }
    else list($Y,$M) = explode(' ',date('Y n')); // else Y==NULL or empty

    $y = $Y + ($M-0.5)/12;

    if($Y > 2150) // 2151+
    {
        $u = ($Y-1820)/100; // Y is 'year'
        $u2 = $u*$u;
        $dT = -20 + (32*$u2);
        $dT += 3.5 + 117.5*sin(($y-2150)/850); // patch san2@2012.01.16, valid of (Y<3000)
    }
    else if($Y > 2050) // 2051 - 2150
    {
        $u = ($y-1820)/100;
        $u2 = $u*$u;
        $dT = -20 + (32*$u2) - (0.5628*(2150-$y));
        $dT += 1.0 + 2.5*($y-2050)/100; // patch san2@2012.01.16
    }
    else if($Y > 2005) // 2006 - 2050, 2010=66.9s, 2050=93s
    {
        $t = $y-2000;
        $t2 = $t*$t;
        $dT = 62.92 + (0.32217*$t) + (0.005589*$t2);
    }
    else if($Y > 1986) // 1987 - 2005
    {
        $t = $y-2000;
        $t2 = $t*$t;
        $t3 = $t*$t2;
        $dT = 63.86 + (0.3345*$t) - (0.060374*$t2) + (0.0017275*$t3) + (0.000651814*$t2*$t2)
        + (0.00002373599*$t2*$t3);
    }
    else if($Y > 1961) // 1962 - 1986
    {
        $t = $y-1975;
        $t2 = $t*$t;
        $t3 = $t*$t2;
        $dT = 45.45 + (1.067*$t) - ($t2/260) - ($t3/718);
    }
    else if($Y > 1940) // 1942 - 1961
    {
        $t = $y-1950;
        $t2 = $t*$t;
        $t3 = $t*$t2;
        $dT = 29.07 + (0.407*$t) - ($t2/233) + ($t3/2547);
    }
    else if($Y > 1920) // 1921 -1941
    {
        $t = $y-1920;
        $t2 = $t*$t;
        $t3 = $t*$t2;
        $dT = 21.20 + (0.84493*$t) - (0.076100*$t2) + (0.0020936*$t3);
    }
    else if($Y > 1900) // 1901 - 1920
    {
        $t = $y-1900;
        $t2 = $t*$t;
        $t3 = $t*$t2;
        $dT = -2.79 + (1.494119*$t) - (0.0598939*$t2) + (0.0061966*$t3) - (0.000197*$t2*$t2);
    }
    else if($Y > 1860) // 1861 - 1900
    {
        $t = $y-1860;
        $t2 = $t*$t;
        $t3 = $t*$t2;
        $dT = 7.62 + (0.5737*$t) - (0.251754*$t2) + (0.01680668*$t3) - (0.0004473624*$t2*$t2) + ($t2*$t3/ 233174);
    }
    else if($Y > 1800) // 1801 - 1860
    {
        $t = $y-1800;
        $t2 = $t*$t;
        $t3 = $t*$t2;
        $t4 = $t*$t3;
        $dT = 13.72 - (0.332447*$t) + (0.0068612*$t2) + (0.0041116*$t3) - (0.00037436*$t4)
        + (0.0000121272*$t2*$t3) - (0.0000001699*$t3*$t3) + (0.000000000875*$t3*$t4);
    }
    else if($Y > 1700) // 1701 - 1800
    {
        $t = $y-1700;
        $t2 = $t*$t;
        $t3 = $t*$t2;
        $dT = 8.83 + (0.1603*$t) - (0.0059285*$t2) + (0.00013336*$t3) - ($t2*$t2/1174000);
    }
    else if($Y > 1600) // 1601 - 1700
    {
        $t = $y-1600;
        $t2 = $t*$t;
        $t3 = $t*$t2;
        $dT = 120 - (0.9808*$t) - (0.01532*$t2) + ($t3/7129);
    }
    else if($Y > 500) // 501 - 1600
    {
        $u = ($y-1000)/100; 
        $u2 = $u*$u;
        $u3 = $u*$u2;
        $dT = 1574.2 - (556.01*$u) + (71.23472*$u*$u) + (0.319781*$u3)
        - (0.8503463*$u2*$u2) - (0.005050998*$u2*$u3) + (0.0083572073*$u3*$u3);
        $dT += ($y>1200) ? (7*($y-1600)/400) : (-7 + 130*($y-1200)/700);// patch san2@2012.01.16
    }
    else if($Y > -500) // -499 - 500, max dT = 17203.7 (not 17190)
    {
        $u = $y/100;
        $u2 = $u*$u;
        $u3 = $u*$u2;
        $dT = 10583.6 - (1014.41*$u) + (33.78311*$u2) - (5.952053*$u3)
        - (0.1798452*$u2*$u2) + (0.022174192*$u2*$u3) + (0.0090316521*$u3*$u3);
        $dT += ($y>0) ? (-151 + 133*($y-500)/500) : (-260 + 450*$y/500); // patch san2@2012.01.16
    }
    else // -500
    {
        $u = ($Y-1820)/100;
        $u2 = $u*$u;
        $dT = -20 + 32 * $u2;
        $dT += -700 + 3300*($y+500)/2500; // patch san2@2012.01.16, valid (Y<-3000)
    }

    ## Secular Acceleration of the Moon
    ## http://eclipse.gsfc.nasa.gov/SEcat5/secular.html
    ##
    ## c = -0.91072 * ( -25.858 + 26.0 ) *  t^2
    ## where: t = (year-1955)/100
    ## or
    ## c = -0.000012932 * (y - 1955)^2
    ##
    $t = $y - 1955;
    $dT += -0.000012932 * $t*$t;

    return sprintf('%.2f',$dT); // seconds
  }

  ## public
  ##
  function &calendar($year, $month)
  {
    list($week,$term) = calendar::month_info($year,$month);

    $eidx = 3;
    $refs = range(1,$term); // reference of days
    $fsat = 7 - $week; // first Saturday

    ## make index array such as (Sun,Sat)
    ##
    for($i=0; $i<=3; $i++)
    {
        $isat = $fsat + ($i*7); // index of Saturday
        $idxs[] = array($isat-6,$isat);
    }

    ## check last Saturday and Sunday
    ##
    if(($fsat+28) <= $term) $idxs[++$eidx] = array($fsat+22,$fsat+28);
    if(($term-$idxs[$eidx][1]) > 0)
    {
        $idxs[] = array($idxs[$eidx][0]+7,$idxs[$eidx][1]+7);
        $eidx++;
    }

    ## rewrite days
    ##
    for($i=0; $i<=$eidx; $i++)
    {
        for($j=$idxs[$i][0]; $j<=$idxs[$i][1]; $j++) $r[$i][] = &$refs[$j-1];
    }

    return $r; // array
  }
} // end of class

return; // do not any print at below this line

/**** example *********
$_y = 2040;
$_m = 12;
$r = calendar::calendar($_y,$_m);

echo '<PRE>';
echo "      $_m $_y\n";
echo "Su Mo Tu We Th Fr Sa\n";

$size = sizeof($r);
for($i=0; $i<$size; $i++)
{
  printf("%2s",$r[$i][0]);
  for($j=1; $j<7; $j++) printf("%3s",$r[$i][$j]);
  echo "\n";
}
print_r($r);
**********************/

