<?php
## this file name is 'class.lunar.php'
##
## lunar object -- get moon position or sun <-> moon, constellation, eclipses
##
## [author]
##  - Chilbong Kim, <san2(at)linuxchannel.net>
##  - http://linuxchannel.net/
##
## [changes]
##  - 2018.08.11 : (int) to (float) at _newmoon() _fullmoon() ....
##  - 2017.05.03 : calltime reference of easter()
##  - 2014.09.26 : support PHP/5.4 (calltime reference)
##  - 2013.08.22 : bug fixed of tolunar(): $age
##  - 2011.04.24 : add easter()
##  - 2011.04.08 : bug fixed of _newmoon(): $ffx[20170226] was unsed
##  - 2010.10.15 : bug fixed of _constellation()
##  - 2010.05.17 : critical bug fixed, _newmooninfo()
##  - 2009.06.08 : extended, date() to calendar::_date(), mktime() to calendar::_mktime() of class.calendar.php
##  - 2007.09.27 : bug fixed, tolunar(): current-fullmoon argument(1296000->1382400)
##  - 2005.02.25 : patch _gettm()
##  - 2005.02.16 : _getutimestr() rename to _newmooninfo()
##  - 2005.02.15 : _conjunction() rename to _newmoon()
##  - 2005.02.03 : bug fixed, moon()
##  - 2005.02.02 : bug fixed, tosolar()
##  - 2005.01.24 : new build
##
## [conjunction error by approximative method]
##  - avg: -6538 seconds(+598 seconds, -11787 seconds)
##
## [conjunction error by approximative method day patch]
##  - avg: -368 seconds(+592 seconds, -1081 seconds)
##  - but, wrong day not exists :)
##
## [valid date]
##  - solar : 1902-01-10 - 2038-01-18
##  - lunar : 1901-12-01 - 2037-12-14
##
## [support date]
##  - unix timestamp base: 1902-01-01 00:00:00 <= date <= 2037-12-31 23:59:59 (guess)
##  - JD(Julian Day) base: BC 4713-01-01 12:00 UTC <= Gregorian date <= AD 9999 (guess)
##
## [support date table - further]
##  ----------------------------------------------------------------------
##  -4712  -1999  1391  1902  2037  2050  4000  9999
##  ----------------------------------------------------------------------
##                        A     A                      current
##                  A     A     A     A                base on KASI.RE.KR
##            B     A     A     A     A     B          base on NASA
##    C       B     A     A     A     A     B     C    only computing
##  ----------------------------------------------------------------------
##  - A: support lunar date and leap, both exactly              
##  - B: support lunar date and leap, but leap not exactly              
##  - C: only computing, both not exactly
##
## [download & online source view]
##  - http://ftp.linuxchannel.net/devel/php_lunar/
##  - http://ftp.linuxchannel.net/devel/php_calendar/
##
## [demo]
##  - http://linuxchannel.net/gaggle/lunar.php
##
## [docs]
##  - http://linuxchannel.net/docs/lunar.txt
##
## [study]
##  - http://www.kao.re.kr/html/study/qna/index.html
##  - http://sunearth.gsfc.nasa.gov/eclipse/SEsaros/SEsaros.html
##  - Synodic Month (new moon to new moon)     29.53059 days  = 29d 12h 44m (A)
##  - Draconic Month (node to node)            27.21222 days  = 27d 05h 06m (B)
##  - Anomalistic Month (perigee to perigee)   27.55455 days  = 27d 13h 19m
##  - (223 * A) = 6585.36 days, (242 * B) = 6585.32 days 
##  - 1 Saros = 6585.32 days = 18 years 11 days 8 hours
## 
## [eclipses]
##  - Solar Eclipses: T(Total), A(Annular), H(Hybrid(Annular/Total)), P(Partial)
##  - Lunar Eclipses: t(Total), p(Umbral(Partial)), n(Penumbral)
##
## [eclipses(e) by this program]
##  - S A 104 0.010582273616323 1.06751983653440(1.067743)
##  - S T  91 0.014674627401046 1.04166992331860
##  - S H   6 0.210833791736160 0.96121766199154(0.964180)
##  - S P 106 0.829402137331320 1.59530999819400
##  - L t 114 0.010630181434784 0.52538271749109(0.527908)
##  - L p  85 0.294681791935650 1.14183526374750
##  - L n 111 0.832900661071600 1.57436507046800(1.578244)
##
## [reference]
##  - http://user.chollian.net/~kimdbin/re/moonpos.html
##  - http://zimmer.csufresno.edu/~fringwal/skycalc.c
##  - http://williams.best.vwh.net/sunrise_sunset_example.htm
##  - http://aa.usno.navy.mil/faq/docs/SunApprox.html
##  - ftp://ssd.jpl.nasa.gov/pub/eph/export/C-versions/hoffman/
##  - http://www.linuxchannel.net/docs/solar-24terms.txt
##  - http://home.tiscali.se/pausch/ // good, same as below
##  - http://www.stjarnhimlen.se/english.html  // good, planetary positions, rise/set time etc
##  - http://www.stargazing.net/kepler/ // good, approximate astronomical postions
##  - http://sunearth.gsfc.nasa.gov/eclipse/eclipse.html // good
##  - http://sunearth.gsfc.nasa.gov/eclipse/phase/phasecat.html
##  - http://sunearth.gsfc.nasa.gov/eclipse/phase/phase2001gmt.html
##  - http://sunearth.gsfc.nasa.gov/eclipse/LEvis/LEaltitude.html
##  - http://sunearth.gsfc.nasa.gov/eclipse/resource.html
##  - http://www.mreclipse.com/Special/SEprimer.html // Solar Eclipses for Beginners
##  - http://www.mreclipse.com/Special/LEprimer.html // Lunar Eclipses for Beginners
##  - http://www.mreclipse.com/MrEclipse.html // for Beginners
##  - http://astronote.org/
##  - http://myhome.naver.com/dudwn1109/appearance/solor_eclipse.htm
##  - http://www.kao.re.kr/html/study/
##

@error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED);
if(!defined('__TIMEZONE__')) define('__TIMEZONE__',date('Z')/3600); // system timezone for hours

## private _solar
##
class _solar
{
  ## private, get JD(julian day)
  ##
  ## D -- get the number of days from base JD
  ## D = JD(Julian Day) - 2451545.0, base JD(J2000.0)
  ##
  ## base position (J2000.0), 2000-01-01 12:00:00, UT
  ## as   mktime(12,0,0-64,1,1,2000) == 946695536 unix timestamp at KST
  ## as gmmktime(12,0,0-64,1,1,2000) == 946727936 unix timestamp at UTC
  ##
  function &_getjd($utime)
  {
    $D = $utime - 946727936; // number of time
    $D = sprintf('%.8f',$D/86400); // float, number of days
    $JD= sprintf('%.8f',$D+2451545.0); // float, Julian Day
    $J = sprintf('%.4f',2000.0+($D/365.25)); // Jxxxx.xxxx format
    $T = sprintf('%.8f',$D/36525.0); // Julian century

    return array($JD,$J,$D,$T);
  }

  ## private, degress to valid
  ##
  function &_deg2valid($deg)
  {
    $deg = ($deg<0) ? fmod($deg,360)+360.0 : fmod($deg,360);

    return sprintf('%.8f',$deg); // float degress
  }

  ## private
  ##
  function &_moon2valid($moon)
  {
    if($moon < 1) $moon = 1;
    else if($moon > 12) $moon = 12;

    return (int)$moon;
  }

  ## private, degress to time(seconds)
  ##
  function &_deg2daytime($deg)
  {
    return sprintf('%.4f',$deg*240); // seconds
  }

  ## private, degress to angle
  ##
  function &_deg2angle($deg, $singed=FALSE)
  {
    if($singed) $singed = '+';
    if($deg <0) { $singed = '-'; $deg = abs($deg); }

    $time = sprintf('%.4f',$deg*3600);
    $degr = (int)$deg.chr(161).chr(198); //sprintf('%d',$deg);
    $time = sprintf('%.4f',$time-($degr*3600)); // fmod
    $mins = sprintf('%02d',$time/60).chr(161).chr(199);
    $secs = sprintf('%.4f',$time-($mins*60)).chr(161).chr(200); // fmod

    return $singed.$degr.$mins.$secs;
  }

  ## private, degress to solar time
  ##
  ## 1 solar year == 365.242190 days == 31556925.216 seconds
  ## 1 degress == 31556925.216 seconds / 360 degress == 87658.1256 seconds
  ##
  function &_deg2solartime($deg)
  {
    return sprintf('%.4f',$deg*87658.1256); // seconds
  }

  ## private, get sun's approximation to the Sun's geocentric apparent ecliptic longitude
  ##
  function &_sunl($D)
  {
    $g = 357.529 + (0.98560028*$D); // default 357.529, fixed 357.550
    $q = 280.459 + (0.98564736*$D);

    ## fixed
    ##
    $g = _solar::_deg2valid($g); // to valid degress
    $q = _solar::_deg2valid($q); // to valid degress

    ## convert
    ##
    $deg2rad = array();
    $deg2rad['g'] = deg2rad($g); // radian
    $deg2rad['2g'] = deg2rad($g*2); // radian

    $sing = sin($deg2rad['g']); // degress
    $sin2g = sin($deg2rad['2g']); // degress

    ## L is an approximation to the Sun's geocentric apparent ecliptic longitude
    ##
    $L = $q + (1.915*$sing) + (0.020*$sin2g);
    $L = _solar::_deg2valid($L); // degress
    $atime = _solar::_deg2solartime(round($L)-$L); // float

    return array($L,$atime); // array, float degress, float seconds
  }

  ## private, get solar 24 terms
  ##
  function &_terms($year=0, $smoon=1, $length=12)
  {
    ## mktime(7+9,36,19-64,3,20,2000), 2000-03-20 16:35:15(KST)
    ##
    static $start = 953537715; // start base unix timestamp
    static $tyear = 31556940; // tropicalyear to seconds
    static $byear = 2000; // start base year

    static $tterms = array
    (
    -6418939, -5146737, -3871136, -2589569, -1299777,        0,
     1310827,  2633103,  3966413,  5309605,  6660762,  8017383,
     9376511, 10735018, 12089855, 13438199, 14777792, 16107008,
    17424841, 18731368, 20027093, 21313452, 22592403, 23866369
    );

    static $ffd = array // patch day, {YYYY}{idx}
    (
    190311 => 1440, 191914 => -480, 192223 => -240, 192912 => 1920,
    193116 => 1920, 193910 => -780, 19547  => 3600, 195422 => 3480,
    195513 => 2880, 195523 => 3420, 19565  => 2880, 195812 => 3240,
    195815 => 3120, 19603  => 3240, 196111 => 3480, 196215 => 2160,
    196519 => 2520, 196520 => 2400, 196810 => 1860, 198218 =>  660,
    19879  =>-3840, 198813 =>-3840, 199122 => -480, 20136  =>  360,
    20230  =>  600, 202311 => -400, 20303  => -420
    );

    $stime = $start + ($year - $byear) * $tyear;

    if($length < -12) $length = -12;
    else if($length > 12) $length = 12;

    $smoon = _solar::_moon2valid($smoon);
    $emoon = _solar::_moon2valid($smoon+$length);

    $sidx =  (min($smoon,$emoon) - 1) * 2;
    $eidx = ((max($smoon,$emoon) - 1) * 2) + 1;

    for($i=$sidx; $i<=$eidx; $i++)
    {
        $utime = $stime + $tterms[$i];
        list(,,$D) = _solar::_getjd($utime);
        list(,$atime) = _solar::_sunl($D); // ($utime-946727936)/86400;
        $utime += $atime + $ffd["$year$i"]; // re-fixed
        $terms[] = calendar::_date('nd',$utime);
    }

    return $terms; // array
  }

  ## private, get a Constellation of zodiac
  ##
  function &_constellation($y, $m, $d)
  {
    static $ffd = array // patch day
    (
    19030622 => -24, 19221222 =>   4, 19540420 => -61, 19550723 => -48,
    19551222 => -57, 19560320 => -48, 19580823 => -52, 19600219 => -55,
    19610221 => -59, 19620823 => -37, 19651023 => -42, 19870525 =>  64,
    19880722 =>  61, 20230621 =>   4, 20300218 =>   7
    );

    $horoscope = array // do not set `static' variable
    (
    array(chr(187).chr(234).chr(190).chr(231),'Aries'),
    array(chr(200).chr(178).chr(188).chr(210),'Taurus'),
    array(chr(189).chr(214).chr(181).chr(213).chr(192).chr(204),'Gemini'),
    array(chr(176).chr(212),'Cancer'),
    array(chr(187).chr(231).chr(192).chr(218),'Leo'),
    array(chr(195).chr(179).chr(179).chr(224),'Virgo'),
    array(chr(195).chr(181).chr(196).chr(170),'Libra'),
    array(chr(192).chr(252).chr(176).chr(165),'Scorpius'),
    array(chr(177).chr(195).chr(188).chr(246),'Sagittarius'),
    array(chr(191).chr(176).chr(188).chr(210),'Capricon'),
    array(chr(185).chr(176).chr(186).chr(180),'Aquarius'),
    array(chr(185).chr(176).chr(176).chr(237).chr(177).chr(226),'Pisces')
    );

    //list(,$nd) = _solar::_terms($y,$m,0);
    //$idx = ($m.$d<$nd) ? $m-2 : $m-1;
    //if($idx < 0) $idx += 12;

    $fk = sprintf('%d%02d%d',$y,$m,$d);
    //list(,,$D) = _solar::_getjd(mktime(23,59+(int)$ffd[$fk],59,$m,$d,$y));
    //list(,,$D) = _solar::_getjd(calendar::_mktime(23,59+(int)$ffd[$fk],59,$m,$d,$y));
    list(,,$D) = calendar::_getjd($y,$m,$d,23,59+(int)$ffd[$fk],59);
    list($L) = _solar::_sunl($D);

    return $horoscope[floor($L/30)];
  }
} // end of class

## public lunar
##
class lunar extends _solar
{
  ## private, time to moon time(h i s)
  ##
  function &_time2mtime($time, $singed=FALSE)
  {
    if($singed) $singed = '+';
    if($time<0) { $singed = '-'; $time = abs($time); }

    return $singed.calendar::_date('H i s',$time-date('Z'));
  }

  ## private, get moon's approximation to the Moon's geocentric apparent
  ## ecliptic longitude
  ## Meeus, J., 1998, Astronomical Algorithms
  ##
  function &_moonl($T)
  {
    $lambda = 218.32 + (481267.883*$T)
    + 6.29 * sin(deg2rad(134.9 + 477198.85*$T))
    - 1.27 * sin(deg2rad(259.2 - 413335.38*$T))
    + 0.66 * sin(deg2rad(235.7 + 890534.23*$T))
    + 0.21 * sin(deg2rad(269.9 + 954397.7*$T))
    - 0.19 * sin(deg2rad(357.5 + 35999.05*$T))
    - 0.11 * sin(deg2rad(186.6 + 966404.05*$T));

    return array(lunar::_deg2valid($lambda),$lambda);
  }

  ## private, get Moon's ecliptic
  ## Meeus, J., 1998, Astronomical Algorithms
  ##
  function &_moone($T)
  {
    $beta = 5.13 * sin(deg2rad(93.3 + 483202.03*$T))
    + 0.28 * sin(deg2rad(228.2 + 960400.87*$T))
    - 0.28 * sin(deg2rad(318.3 + 6003.18*$T))
    - 0.17 * sin(deg2rad(217.6 - 407332.2*$T));

    return $beta; // float
  }

  ## public, get moon positon
  ##
  ## http://user.chollian.net/~kimdbin/re/moonpos.html
  ##
  function &moon($utime=0)
  {
    //static $D, $J, $JD, $T, $L, $RA, $e, $d, $lambda;
    //static $y, $b, $l, $m, $n;

    /***
    if($utime<-2142664200 || $utime>2146229999)
    {
        echo "\nerror: invalid input $utime, 1902.02.08 00:00:00 <= utime <= 2038.01.04 23:59:59\n";
        return -1;
    }
    ***/

    if($utime == '') $utime = time();

    list($JD,$J,$D,$T) = lunar::_getjd($utime);
    list($L,$lambda) = lunar::_moonl($T);
    $e = lunar::_moone($T); // is beta, Moon's ecliptic

    $y = deg2rad($lambda);
    $b = deg2rad($e);

    $l = cos($b) * cos($y);
    $m = 0.9175 * cos($b) * sin($y) - (0.3978 * sin($b));
    $n = 0.3978 * cos($b) * sin($y) + (0.9175 * sin($b));

    $RA = rad2deg(atan2($m,$l));
    $RA = lunar::_deg2valid($RA); // Moon's right ascension
    $d = rad2deg(asin($n)); // Moon's declination

    $mtime = lunar::_deg2daytime($L); // seconds
    $dtime = lunar::_deg2daytime($RA); // seconds

    return array
    (
    'JD'=> sprintf('%.10f',$JD),    /*** Julian Date ***/
    'J' => 'J'.$J,        /*** Julian Date Jxxxx.xxxx format ***/
    'L' => $L,        /*** Moon's geocentric apparent ecliptic longitude ***/
    'e' => $e,        /*** Moon's ecliptic ***/
    'RA'=> $RA,        /*** Moon's right ascension  ***/
    'd' => $d,        /*** Moon's declination ***/
    'mtime' => $mtime,    /*** seconds ***/
    'dtime' => $dtime,    /*** seconds ***/
    'utime' => $utime,
    'date'  => calendar::_date('D, d M Y H:i:s T',$utime),    /*** KST date ***/
    'gmdate'=> calendar::_date('D, d M Y H:i:s ',$utime-date('Z')).'GMT', /*** GMT date ***/
    '_L'    => lunar::_deg2angle($L),        /*** angle ***/
    '_e'    => lunar::_deg2angle($e,1),        /*** angle ***/
    '_RA'   => lunar::_deg2angle($RA),        /*** angle ***/
    '_d'    => lunar::_deg2angle($d,1),
    '_mtime'=> lunar::_time2mtime($mtime),
    '_dtime'=> lunar::_time2mtime($dtime)
    );
  }

  ## private, get moon's degress - sun's degress
  ##
  function &_gettd($utime)
  {
    list(,,$D,$T) = lunar::_getjd($utime);
    list($s) = lunar::_sunl($D);
    list($l) = lunar::_moonl($T);

    return lunar::_deg2valid($l-$s); // float
  }

  ## private, get unix timestamp of conjunction, new moon day(xxxx-xx-01)
  ##
  ## - http://www.kao.re.kr/html/study/faq/index.html?f=3&idx=79
  ## - http://www.kao.re.kr/html/study/faq/index.html?f=3&idx=43
  ##
  ## 1 solar year == 365.242190 days == 31556925.216 seconds
  ## 1 degress == 31556925.216 seconds / 360 degress == 87658.1256 seconds
  ##
  ## 1 (new moon) moon == 29.53059 dayes = 2551442.976 seconds
  ## 1 degress == 2551442.976 seconds / 360 degress = 7087.3416 seconds
  ##
  ## sun : moon == 1 : 12.3682659235728 == 0.0808520779048 : 1
  ##
  ## move to 1 degress = 7710.7736596978271 seconds (sun and moon => same line)
  ##     7087.3416 +
  ##      573.0262951811299 +
  ##       46.3303666594836 +
  ##        3.7459064145105 +
  ##        0.3028643172501 +
  ##        0.0244872093729 +
  ##        0.0019798417599 +
  ##        0.0001600743202
  ##
  ## move to 86400 seconds :
  ##     avg(13.2433041906) std(1.16287866011) max(15.2504690629) min(11.8423079447)
  ## move to 1 degress :
  ##     mix(7295.8751286879124083279675026639 seconds)
  ##     avg(6524.0516080062621468182286547561 seconds)
  ##     max(5665.3995128704809432711052144198 seconds)
  ##
  function &_newmoon($_y, $_m=0, $_d=0, &$ctime=0)
  {
    static $unit = 5665; // see above comments
    static $ffx = array
    (
    19310517=>2640, 19321030=>-840, 19341008=> 840, 19530612=>-600,
    19550222=>4020, 19580218=>2700, 19860311=>-540, 19950727=>1140,
    20120619=> 780, 20150815=>-540, /*20170226=> 480,*/ 20350109=> 540,
    );

    $utime = $ctime = (func_num_args()<2) ? $_y : calendar::_mktime(23,59,59,$_m,$_d,$_y);
    $td = lunar::_gettd($utime);

    //echo $td."\n";
    if($td > 359.5)
    {
        //echo $td."\n";
        $utime += 86400;
        $td = lunar::_gettd($utime);
    }

    while($td > 0.000177) // 1/$unit
    {
        //echo $td."\n";
        $utime -= $td * $unit;
        $otd = $td;
        $td = lunar::_gettd($utime);
        if($td > $otd) break;
    }

    $utime += (int)$ffx[calendar::_date('Ymd',$utime)];
    if($ctime < $utime) $utime -= 2592000; // 86400 * 30;

    return (float)floor($utime);
  }

  ## private, get tdm
  ##
  function &_gettm($utime)
  {
    list(,,$D,$T) = lunar::_getjd($utime);
    list($s) = lunar::_sunl($D);
    list($l) = lunar::_moonl($T);

    $tm = lunar::_deg2valid($s-180) - $l; // float

    //echo "$s $l => $tm\n"; // debug
    if($tm > 180.0) $tm -= 360.0; // patch 2005.02.25

    return $tm;
  }

  ## private, get unix timestamp of full moon day(xxxx-xx-15)
  ##
  function &_fullmoon($_y, $_m=0, $_d=0)
  {
    static $unit = 5665; // see above comments
    static $ffx = array
    (
    19031006=>2000, 19131213=>  120, 19280801=>2100, 19360506=> 400,
    19380712=> 300, 19450625=>  600, 19500828=>-700, 19550308=>2700,
    19560524=>1900, 19581027=> 3000, 19810617=> 500, 19990501=>-900,
    20211021=>-300, 20261125=>-1100, 20280210=> 700, 20320425=> 600,
    );

    $utime = (func_num_args()<2) ? $_y : calendar::_mktime(23,59,59,$_m,$_d,$_y);

    $td = lunar::_gettm($utime);
    $ta = abs($td);

    while($ta > 0.000177) // 1/$unit
    {
        //echo $td.' '.date('Y-m-d H:i:s',$utime)."\n"; // debug
        $ota = $ta;
        $ptime = $utime + ($td * $unit); // pre-test time
        $td = lunar::_gettm($ptime);
        $ta = abs($td);

        if($ta > $ota) break;
        $utime = $ptime;
    }

    $utime += (int)$ffx[calendar::_date('Ymd',$utime)];

    return (float)floor($utime);
  }

  ## private, get solar eclipse idx name
  ##
  ## Solar Eclipses: T(Total), A(Annular), H(Hybrid(Annular/Total)), P(Partial)
  ## A 104 0.010582273616323 1.06751983653440(1.067743)
  ## T  91 0.014674627401046 1.04166992331860
  ## H   6 0.210833791736160 0.96121766199154(0.964180)
  ## P 106 0.829402137331320 1.59530999819400
  ##
  function &_solareclipse($utime)
  {
    list(,,,$T) = lunar::_getjd($utime);
    $e = lunar::_moone($T); // is beta, Moon's ecliptic
    $e = abs($e);

    if($e < 0.014674) $r = 'A';
    else if($e < 0.210833) $r = 'AT';
    else if($e < 0.829402) $r = 'ATH';
    else if($e < 0.964180) $r = 'PATH';
    else if($e < 1.041670) $r = 'PAT';
    else if($e < 1.067743) $r = 'PA';
    else if($e < 1.595310) $r = 'P';
    else $r = 'N';

    return $r;
  }

  ## public, get solar eclipse exists at new moon
  ##
  function &solareclipse($y, $m, $d)
  {
    list($ymd0,$y,$m,$d) = explode(' ',calendar::_date('Ymd Y n j',calendar::_mktime(12,0,0,$m,$d,$y))); // refixed

    $utime = lunar::_newmoon($y,$m,$d);
    $ymd1 = calendar::_date('Ymd',$utime);

    if($ymd0 == $ymd1) $r = lunar::_solareclipse($utime);
    else $r = 'N';

    return $r;
  }

  ## private, get lunar eclipse idx name
  ##
  ## Lunar Eclipses: t(Total), p(Umbral(Partial)), n(Penumbral)
  ## t 114 0.010630181434784 0.52538271749109(0.527908)
  ## p  85 0.294681791935650 1.14183526374750
  ## n 111 0.832900661071600 1.57436507046800(1.578244)
  ##
  function &_lunareclipse($utime)
  {
    list(,,,$T) = lunar::_getjd($utime);
    $e = lunar::_moone($T); // is beta, Moon's ecliptic
    $e = abs($e);

    if($e < 0.294681) $r = 't';
    else if($e < 0.527908) $r = 'tp';
    else if($e < 0.832900) $r = 'p';
    else if($e < 1.141836) $r = 'np';
    else if($e < 1.578244) $r = 'n';
    else $r = 'N';

    return $r;
  }

  ## public, get lunar eclipse exists at full moon
  ##
  function &lunareclipse($y, $m, $d)
  {
    //list($ymd0,$y,$m,$d) = explode(' ',calendar::_date('Ymd Y n j',calendar::_mktime(12,0,0,$m,$d,$y))); // refixed
    list($ymd0,$y,$m,$d) = explode(' ',calendar::date('Ymd Y n j',calendar::mkjd(12,0,0,$m,$d,$y))); // refixed

    $utime = lunar::_fullmoon($y,$m,$d);
    $ymd1 = calendar::_date('Ymd',$utime);

    if($ymd0 == $ymd1) $r = lunar::_lunareclipse($utime);
    else $r = 'N';

    return $r;
  }

  ## public, from solar date to lunar date
  ##
  function &tolunar($_y, $_m, $_d, $_timezone=NULL)
  {
    static $notminus = array(19651024=>1,2033923=>1,20331023=>1,20331122=>1); // do not minus,  // patch san2@2011.04.11
    static $notleap = array(1965925=>1,1985121=>1,1985220=>1,2033825=>1,2034219=>1); // patch san2@2011.04.11
    static $dominus = array(1985121=>1,1985220=>1,2034219=>1); // patch san2@2011.04.11

    $_y = (int)$_y; // refixed
    $_m = (int)$_m; // refixed
    $_d = (int)$_d; // refixed

    if($_timezone === NULL) $_timezone = __TIMEZONE__; // add san2@2013.08.22

    /***
    if($_y<1902 || $_y>2038)
    {
        echo "\nerror: tolunar() invalid input solar arguments, 1902-01-10 <= solar date <= 2038-01-18\n";
        return -1;
    }
    ***/

    ## check lunar or solar eclipse of current date
    ##
    $eclipse['c'] = lunar::lunareclipse($_y,$_m,$_d);
    if($eclipse['c'] == 'N') $eclipse['c'] = lunar::solareclipse($_y,$_m,$_d);

    ## get current new moon
    ##
    $utime = lunar::_newmoon($_y,$_m,$_d,$ctime);
    list($y,$m,$j,$z,$t,$nd,$ymd0,$his0) = explode(' ',calendar::_date('Y n j z t nd Y-m-d H:i:s',$utime));
    $eclipse['u'] = lunar::_solareclipse($utime);

    ## get lunar days
    ##
    $tmp = $ctime - $utime;
    $age = sprintf('%.2f',($tmp-43200+($_timezone*3600))/86400); // age of Moon at UTC 12:00:00
    $d = ceil($tmp/86400);
    $leap = 0; // leap month

    ## get current full moon
    ##
    $ftime = lunar::_fullmoon($utime+1382400); // 1382400 = 86400 * 16, patch san2@2007.09.27
    list($ymd1,$his1) = explode(' ',calendar::_date('Y-m-d H:i:s Y n j',$ftime));
    $eclipse['f'] = lunar::_lunareclipse($ftime);

    ## get next new moon
    ##
    $ntime = lunar::_newmoon($_y,$_m,$_d+31-$d); // 86400*31
    list($y2,$m2,$z2,$nd2,$ymd2,$his2) = explode(' ',calendar::_date('Y n z nd Y-m-d H:i:s',$ntime));
    $eclipse['n'] = lunar::_solareclipse($ntime);

    ## get solar term(tt) and day term(dt)
    ##
    if($y < $y2) // difference year
    {
        $tt = array_merge(lunar::_terms($y,$m,0),lunar::_terms($y2,$m2,0));
        $dt = $t - $j + $z2 + 1; // day term
    } else // same year
    {
        $tt = lunar::_terms($y,$m,$m2-$m);
        $dt = $z2 - $z;
    }

    //echo " $nd, $tt[1], $nd2, $tt[3], $tt[5] "; // debug
    //if($nd<=$tt[1] && !$notminus[$y.$nd]) $m--;
    if($nd <= $tt[1]) { if(!$notminus[$y.$nd]) $m--; } // patch san2@2011.04.11
    else
    {
        $k = sizeof($tt) - 1; // 1 or 3 or 5 but this case 3 or 5
        if($nd2-1<$tt[$k] && $k==3 && !$notleap[$y.$nd])
        { $leap = 1; $m--; }
        else if($dominus[$y.$nd]) $m--;
        # else do not minus
    }

    if($m < 1) { $m += 12; $y--; } //date('Y',$utime-3456000)

    return array
    (
    sprintf('%d-%02d-%02d',$y,$m,$d),        // YYYY-MM-DD
    array($y,$m,$d,$leap,$dt,$age,$eclipse['c']),    // YYYY,M,D,leap,term,age,eclipse
    array($ymd0,$his0,$utime,$eclipse['u']),    // current new moon
    array($ymd1,$his1,$ftime,$eclipse['f']),    // current full moon
    array($ymd2,$his2,$ntime,$eclipse['n']),    // next new moon
    );
  }

  ## private, get new moon informations, use at tosolar()
  ##
  function &_newmooninfo($_y, $_o, $_m, $_leap, $_stop=0, $_d=15)
  {
    //static $_d = 15; // good idea, patch san2@2010.05.17 disabled

    if($_m > 12) { $_m -= 12; $_y++; }
    else if($_m < 1) { $_m += 12; $_y--; }

    $_l = (int)(boolean)$_leap;
    $_p = $_stop ? $_m : $_m + 1 + $_l; // pre test month

    if($_p > 12) { $_p -= 12; $_y++; }
    list(,list($y,$m,,$leap,$t),$newmoon,,$nextmoon) = lunar::tolunar($_y,$_p,$_d);

    if($leap==$_l && $m==$_o) $newmoon[] = $t; // add term
    else if(!$_stop)
    {
        $output = $y.sprintf('%02d',$m);
        $input = $_y.sprintf('%02d',$_y,$_m);
        if($output < $input) { $ymd = $nextmoon[0]; $j = 1; } // patch san2@2010.05.17
        else { $ymd = $newmoon[0]; $j = -2; }

        list($_y,$_m,$_d) = explode('-',$ymd);
        $_d += $j; // change static $_d
        $newmoon = lunar::_newmooninfo($_y,$_o,$_m,$_leap,1,$_d);
    }

    return $newmoon; // array
  }

  ## public, from lunar date to solar date
  ##
  function &tosolar($_y, $_m, $_d, $_leap=0)
  {
    $_y = (int)$_y; // refixed
    $_m = (int)$_m; // refixed
    $_d = (int)$_d; // refixed

    /***
    if($_y<1901 || $_y>2037)
    {
        echo "\nerror: tosolar() invalid input lunar arguments, 1901-12-01 <= lunar date <= 2037-12-14\n";
        return -1;
    }
    ***/

    if(!$newmoon = lunar::_newmooninfo($_y,$_m,$_m,$_leap)) return; // false

    list(,,$utime,,$term) = $newmoon;

    ## check input day
    ##
    if($_d > 29)
    {
        if($term)
        {
            if($_d > $term) $ck = 0; // is false
            else $ck = 1; // ture valid input day
        } else
        {
            //$_g = getdate($utime);
            //$_t = lunar::tolunar($_g['year'],$_g['mon'],$_g['mday']);
            list($_gy,$_gn,$_gd) = explode(' ',calendar::_date('Y n d',$utime));
            $_t = lunar::tolunar($_gy,$_gn,$_gd);
            if($_d > $_t[1][2]) $ck = 0; // is false
            else $ck = 1; // true
        }
    } else $ck = 1;

    $utime += 86400 * ($_d-1);
    list($ymd,$y,$n,$j,$w) = explode(' ',calendar::_date('Y-m-d Y n j w',$utime));

    return array
    (
    $ymd,        // string YYYY-MM-DD
    $ck,        // check input day is valid ?
    $y,$n,$j,    // YYYY,M,D
    $w,        // weekday idx,0(Sunday) through 6(Saturday)
    $newmoon,    // new moon infomation
    );
  }

  ## public, get a Constellation of zodiac
  ##
  function &zodiac($y, $m, $d, $lunar=0, $leap=0)
  {
    if($lunar) list(,,$y,$m,$d) = lunar::tosolar($y,$m,$d,$leap);

    return lunar::_constellation($y,$m,$d);
  }

  ## public, get easter day
  ##
  function &easter($y, &$debug=array())
  {
    $p = _solar::_terms($y,3,0);
    $m = substr($p[1],0,1);
    $d = substr($p[1],-2);

    list(,,,$f,$n) = lunar::tolunar($y,$m,$d); // lunar
    $full = str_replace('-','',$f[0]);
    $curr = sprintf('%d%02d%02d',$y,$m,$d);

    if($curr >= $full)
    {
        list($ty,$tm,$td) = explode('-',$n[0]);
        list(,,,$f) = lunar::tolunar($ty,(int)$tm,$td); // lunar
    }

    list($ty,$tm,$td) = explode('-',$f[0]);
    $jd = calendar::mkjd(21,0,0,(int)$tm,$td,$ty);
    $w = 7 - calendar::date('w',$jd);
    $r = calendar::date('m/d',$jd+$w);

    if(func_num_args() > 1)
    {
        list(,$fm,$fd) = explode('-',$f[0]);
        $debug[0] = "$m/$d";
        $debug[1] = "$fm/$fd";
    }

    return $r;
  }
} // end of class

return; // do not any print at below this line