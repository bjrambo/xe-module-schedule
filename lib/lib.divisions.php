<?php
##
## this file name is 'class.solar.php'
##
## solar object -- get sun position or 24 solar terms
##
## [author]
##  - Chilbong Kim, <san2(at)linuxchannel.net>
##  - http://linuxchannel.net/
##
## [changes]
##  - 2018.08.11 : (int) to (float)
##  - 2014.09.26 : support PHP/5.4 (calltime reference)
##  - 2011.11.12 : rewrite sunrise_sunset(), added location_of_sun()
##               : mixed/moved to class.calendar.php deg2solartime(), deg2valid(), moon2valid(), ...
##  - 2011.09.30 : tune of sunrise_sunset()
##  - 2011.09.19 : tune of time2stime()
##  - 2010.10.15 : bug fixed of _constellation()
##  - 2010.07.29 : add sambok()
##  - 2009.06.08 : extended, date() to calendar::_date() of class.calendar.php
##  - 2005.04.17 : add sunrise_sunset()
##  - 2005.02.06 : rebuid deg2valid(), add zodiac()
##  - 2005.01.24 : rebuild time2stime(), atan2()
##  - 2005.01.18 : bug fixed:$RA
##  - 2003.09.08 : bug fixed
##  - 2003.09.06 : new build
##
## [근사식에 대한 신뢰]
##  - 표준편차 : 1289.7736 = 21.5 minutes (standard deviation)
##  - 평균오차 : 817.57409541246 = +13.6 minutes
##  - 최대오차 : +4102.7340(68.4 minutes), -4347.2395(72.5 minutes)
##
## [근사식으로 계산한 24절기 실제 오차] 1902 ~ 2037 년
##  - 표준편차 : 1122.1921 = 18.7 분
##  - 평균오차 : +686.08382175161 = +11.4 분
##  - 최대오차 : +4297.252300024 = +71.6 분, -4278.048699975 = -71.3 분
##  - 최소오차 : +0.16999998688698 = 0 초
##
## [근사식 + 년도 보정으로 계산한 24절기 실제 오차] 1902 ~ 2037 년
##  - 표준편차 : 450.8534 = 7.5 분
##  - 평균오차 : +305.38638890903 = +5.0 분
##  - 최대오차 : +3028.2343000174 = +50.5 분, -1982.9391000271 = -33.1 분
##  - 최소오차 : +0.0085000991821289 = 0 초
##
## [valid date]
##  - 1902.01.01 00:00:00 <= utime <= 2037.12.31 23:59:59
##
## [support date]
##  - unix timestamp base: 1902-01-01 00:00:00 <= date <= 2037-12-31 23:59:59 (guess)
##  - JD(Julian Day) base: BC 4713-01-01 12:00 UTC <= Gregorian date <= AD 9999 (guess)
##
## [download & online source view]
##  - http://ftp.linuxchannel.net/devel/php_solar/
##  - http://ftp.linuxchannel.net/devel/php_calendar/
##
## [demo]
##  - http://linuxchannel.net/gaggle/solar.php
##
## [docs]
##  - http://linuxchannel.net/docs/solar-24terms.txt
##
## [references]
##  - http://cgi.chollian.net/~kohyc/
##  - http://user.chollian.net/~kimdbin/
##  - http://user.chollian.net/~kimdbin/re/calendar.html
##  - http://user.chollian.net/~kimdbin/re/suncoord.html
##  - http://user.chollian.net/~kimdbin/qna/al138.html
##  - http://ruby.kisti.re.kr/~manse/contents-3.html
##  - http://ruby.kisti.re.kr/~anastro/sub_index.htm
##  - http://www-ph.postech.ac.kr/~obs/lecture/lec1/elementary/nakedeyb.htm
##  - http://ruby.kisti.re.kr/~anastro/calendar/etime/ETime.html
##  - http://www.sundu.co.kr/5-information/5-3/5f3-3-5-04earth-1.htm
##  - http://www-ph.postech.ac.kr/~obs/lecture/lec1/elementary/nakedeya.htm
##  - http://upgradename.com/calm.php
##  - http://aa.usno.navy.mil/faq/docs/SunApprox.html
##  - http://aa.usno.navy.mil/data/docs/JulianDate.html
##  - http://williams.best.vwh.net/sunrise_sunset_example.htm    // sunrise sunset
##  - http://www.stargazing.net/kepler/sunrise.html    // sunrise sunset
##  - http://eclipse.gsfc.nasa.gov/SEhelp/deltatpoly2004.html    // delta T
##  - http://eclipse.gsfc.nasa.gov/SEhelp/deltat2004.html    // delta T
##  - http://star-www.st-and.ac.uk/~fv/webnotes/index.html  // Positional Astronomy
##  - http://stjarnhimlen.se/comp/ppcomp.html // How to compute planetary positions
##  - http://stjarnhimlen.se/comp/tutorial.html // a tutorial with worked examples
##  - http://stjarnhimlen.se/comp/riset.html // rise/set times and altitude above horizon
##  - http://stjarnhimlen.se/comp/time.html // time scales
##
## [usage]
##
## [example]
##  require_once 'class.calendar.php';
##  require_once 'class.solar.php';
##  $sun = array();
##  $terms = solar::terms(date('Y'),1,12,$sun);
##  print_r($terms);
##  print_r($sun);
##  print_r(solar::sun(time()));
##

@error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED);
if(!defined('__TIMEZONE__')) define('__TIMEZONE__',date('Z')/3600); // system timezone for hours

class solar
{
  ## check solar terms in today or tomorrow
  ##
  function &solar($utime=0, $GMT=FALSE)
  {
    return solar::today($utime,$GMT);
  }

  function &today($utime=0, $GMT=FALSE)
  {
    if($utime=='' || $utime===NULL) $utime = time();
    if($GMT) $utime -= 32400;

    list($year,$moon,$moonday) = explode(' ',date('Y n nd',$utime));
    $tomorrow = date('nd',$utime+86400);

    $terms = solar::terms($year,$moon,0);
    $samboks = solar::sambok($year);
    $sambok1 = $samboks[$moonday];
    $sambok2 = $samboks[$tomorrow];

    if($term = $terms[$moonday])
    {
        if($sambok1) $term .= '/'.$sambok1;
        $str = '오늘은 <B>'.$term.'</B>입니다.';
    }
    else if($term = $terms[$tomorrow])
    {
        if($sambok2) $term .= '/'.$sambok2;
        $str = '내일은 <B>'.$term.'</B>입니다.';
    }
    else if($sambok1) $str = '오늘은 <B>'.$sambok1.'</B>입니다.';
    else if($sambok2) $str = '내일은 <B>'.$sambok2.'</B>입니다.';

    return $str;
  }

  ## get sun position at unix timestamp
  ##
  ## [limit]
  ##  - mktime(0,0,0,1,1,1902) < $utime < mktime(23,59,59,12,31,2037)
  ##
  ## [study]
  ##  - w = 23.436
  ##  - tan RA = (sin L * cos w - tan e * sin w ) / cos L
  ##  - sin d = (sin e * cos w) + (cos e * sin w * sin L)
  ##
  ## [example]
  ##  - print_r(solar::sun(mktime(  10,0,0,3,21,2003)  ));
  ##  - print_r(solar::sun(mktime(10-9,0,0,3,21,2003),1)); // same as
  ##
  function &sun($utime, $GMT=FALSE)
  {
    $L = $D = $JD = 0; $J = '';
    $deg2rad = array();

    /***
    if($utime<-2145947400 || $utime>2145884399)
    {
        echo "\nerror: invalid input $utime, 1902.01.01 00:00:00 <= utime <= 2037.12.31 23:59:59\n";
        return -1;
    }
    ***/

    list($L,$atime) = solar::sunl($utime,$GMT,$D,$JD,$J,$deg2rad);

    ## Sun's ecliptic, in degress
    ## http://aa.usno.navy.mil/faq/docs/SunApprox.php
    ##
    $e = sprintf('%.10f',23.439 - (0.00000036*$D)); // degress

    $cosg = cos($deg2rad['g']); // degress
    $cos2g = cos($deg2rad['2g']); // degress

    ## R == AU (sun ~ earth)
    ## The distance of the Sun from the Earth, R, in astronomical units (AU)
    ## http://aa.usno.navy.mil/faq/docs/SunApprox.php
    ##
    $R = sprintf('%.10f',1.00014 - (0.01671*$cosg) - (0.00014*$cos2g));

    ## convert
    ##
    $deg2rad['e'] = deg2rad($e); // radian
    $deg2rad['L'] = deg2rad($L); // radian

    $cose = cos($deg2rad['e']); // degress
    $sinL = sin($deg2rad['L']); // degress
    $cosL = cos($deg2rad['L']); // degress
    $sine = sin($deg2rad['e']); // degress

    ## the Sun's right ascension(RA)
     ##
    //$tanRA = sprintf('%.10f',$cose * $sinL / $cosL); // degress
    //$RA = sprintf('%.10f',rad2deg(atan($tanRA)));
    //$RA = $cosL<0 ? $RA+180 : ($sinL<0 ? $RA+360 : $RA); // patch 2005.01.18
    $RA = rad2deg(atan2($cose*$sinL,$cosL));
    $RA = calendar::deg2valid($RA);

    ## the Sun's declination(d)
    ##
    $sind = sprintf('%.8f',$sine * $sinL); // degress
    $d = sprintf('%.8f',rad2deg(asin($sind))); // Sun's declination, degress

    $solartime = calendar::deg2solartime($L);
    $daytime = $RA * 240; // to seconds

    ## all base degress or decimal
    ##
     return array
    (
    'JD' => $JD,    /*** Julian Day ***/
    'J'  => 'J'.$J, // Jxxxx.xxxx format
    'L'  => $L,    /*** Sun's geocentric apparent ecliptic longitude ***/
    'e'  => $e,    /*** Sun's ecliptic ***/
    'R'  => $R,    /*** Sun from the Earth, astronomical units (AU) ***/
    'RA' => $RA,    /*** Sun's right ascension ***/
    'd'  => $d,    /*** Sun's declination ***/
    'stime'  => $solartime,        /*** solar time ***/
    'dtime'  => $daytime,        /*** day time ***/
    'atime'  => $atime,        /*** append time for integer degress **/
    'utime'  => $utime,        /*** unix timestamp ***/
    'date'   => calendar::_date('D, d M Y H:i:s T',$utime),    /*** KST date ***/
    'gmdate' => calendar::_date('D, d M Y H:i:s ',$utime-date('Z')).'GMT',    /*** GMT date ***/
    '_L'  => calendar::deg2dms($L),
    '_e'  => calendar::deg2dms($e,1),
    '_RA' => sprintf('%.8f',$RA/15),
    '_d'  => calendar::deg2dms($d,1),
    '_stime' => gmdate('z H i s',$solartime),
    '_dtime' => calendar::deg2hms($RA),
    '_atime' => calendar::deg2hms($atime/240,1),
    );
  }

  ## http://aa.usno.navy.mil/faq/docs/SunApprox.php
  ##
  function &sunl($utime, $GMT=FALSE, &$D=0, &$JD=0, &$J='', &$deg2rad=array())
  {
    if($GMT) $utime += 32400; // force GMT to static KST, see 946727936

    ## D -- get the number of days from base JD
    ## D = JD(Julian Day) - 2451545.0, base JD(J2000.0)
    ##
    ## base position (J2000.0), 2000-01-01 12:00:00, UT
    ## as   mktime(12,0,0-64,1,1,2000) == 946695536 unix timestamp at KST
    ## as gmmktime(12,0,0-64,1,1,2000) == 946727936 unix timestamp at GMT
    ##
    $D = $utime - 946727936; // number of time
    $D = sprintf('%.8f',$D/86400); // float, number of days
    $JD = sprintf('%.8f',$D+2451545.0); // float, Julian Day
    $J = sprintf('%.4f',2000.0 + ($JD-2451545.0)/365.25); // Jxxxx.xxxx format

    $g = 357.529 + (0.98560028*$D);
    $q = 280.459 + (0.98564736*$D);

    ## fixed
    ##
    $g = calendar::deg2valid($g); // to valid degress
    $q = calendar::deg2valid($q); // to valid degress

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
    $L = calendar::deg2valid($L); // degress
    $atime = calendar::deg2solartime(round($L)-$L); // float

    return array($L,$atime); // array, float degress, float seconds
  }

  function &gterms()
  {
    static $hterms = array
    (
    '소한','대한','입춘','우수','경칩','춘분','청명','곡우',
    '입하','소만','망종','하지','소서','대서','입추','처서',
    '백로','추분','한로','상강','입동','소설','대설','동지'
    );

    static $tterms = array
    (
    -6418939, -5146737, -3871136, -2589569, -1299777,        0,
     1310827,  2633103,  3966413,  5309605,  6660762,  8017383,
     9376511, 10735018, 12089855, 13438199, 14777792, 16107008,
    17424841, 18731368, 20027093, 21313452, 22592403, 23866369
    );

    ## mktime(7+9,36,19-64,3,20,2000), 2000-03-20 16:35:15(KST)
    ##
    if(!defined('__SOLAR_START__'))
    {
    define('__SOLAR_START__',953537715); // start base unix timestamp
    define('__SOLAR_TYEAR__',31556940); // tropicalyear to seconds
    define('__SOLAR_BYEAR__',2000); // start base year
    }

    return array($hterms,$tterms);
  }

  function &tterms($year)
  {
    static $addstime = array
    (
    1902=> 1545, 1903=> 1734, 1904=> 1740, 1906=>  475, 1907=>  432,
    1908=>  480, 1909=>  462, 1915=> -370, 1916=> -332, 1918=> -335,
    1919=> -263, 1925=>  340, 1927=>  344, 1928=> 2133, 1929=> 2112,
    1930=> 2100, 1931=> 1858, 1936=> -400, 1937=> -400, 1938=> -342,
    1939=> -300, 1944=>  365, 1945=>  380, 1946=>  400, 1947=>  200,
    1948=>  244, 1953=> -266, 1954=> 2600, 1955=> 3168, 1956=> 3218,
    1957=> 3366, 1958=> 3300, 1959=> 3483, 1960=> 2386, 1961=> 3015,
    1962=> 2090, 1963=> 2090, 1964=> 2264, 1965=> 2370, 1966=> 2185,
    1967=> 2144, 1968=> 1526, 1971=> -393, 1972=> -430, 1973=> -445,
    1974=> -543, 1975=> -393, 1980=>  300, 1981=>  490, 1982=>  400,
    1983=>  445, 1984=>  393, 1987=>-1530, 1988=>-1600, 1990=> -362,
    1991=> -366, 1992=> -400, 1993=> -449, 1994=> -321, 1995=> -344,
    1999=>  356, 2000=>  480, 2001=>  483, 2002=>  504, 2003=>  294,
    2007=> -206, 2008=> -314, 2009=> -466, 2010=> -416, 2011=> -457,
    2012=> -313, 2018=>  347, 2020=>  257, 2021=>  351, 2022=>  159,
    2023=>  177, 2026=> -134, 2027=> -340, 2028=> -382, 2029=> -320,
    2030=> -470, 2031=> -370, 2032=> -373, 2036=>  349, 2037=>  523,
    );

    static $addttime = array
    (
    1919=> array(14=>-160), 1939=> array(10=> -508),
    1953=> array( 0=> 220), 1954=> array( 1=>-2973),
    1982=> array(18=> 241), 1988=> array(13=>-2455),
    2013=> array( 6=> 356), 2031=> array(20=>  411),
    2023=> array( 0=>  399, 11=>-571),
    );

    return array($addstime[$year],$addttime[$year]);
  }

  ## get the 24 solar terms, 1902 ~ 2037
  ##
  ## [usage]
  ##  - array solar::terms(int year [, int smoon [, int length [, array &sun]]] )
  ##
  function &terms($year=0, $smoon=1, $length=12, &$sun=array())
  {
    $year  = (int)$year;
    $sun = array();
    $smoon = (int)$smoon;
    $length = (int)$length;
    $times = array();

    if(!$year) $year = date('Y');

    /***
    if($year<1902 || $year>2037)
    {
        echo "\nerror: invalid input $year, 1902 <= year <= 2037\n";
        return -1;
    }
    ***/

    list($hterms,$tterms) = solar::gterms();
    list($addstime,$addttime) = solar::tterms($year);

    ## mktime(7+9,36,19-64,3,20,2000), 2000-03-20 16:35:15(KST)
    ##
    $start = __SOLAR_START__; // start base unix timestamp
    $tyear = __SOLAR_TYEAR__; // tropicalyear to seconds
    $byear = __SOLAR_BYEAR__; // start base year

    $start += ($year - $byear) * $tyear;

    if($length < -12) $length = -12;
    else if($length > 12) $length = 12;

    $smoon = calendar::moon2valid($smoon);
    $emoon = calendar::moon2valid($smoon+$length);

    $sidx =  (min($smoon,$emoon) - 1) * 2;
    $eidx = ((max($smoon,$emoon) - 1) * 2) + 1;

    for($i=$sidx; $i<=$eidx; $i++)
    {
        $time = $start + $tterms[$i];
        list(,$atime) = solar::sunl($time,FALSE);
        $time += $atime + $addstime + $addttime[$i]; // re-fixed
        $terms[calendar::_date('nd',$time)] = &$hterms[$i];
        $times[] = $time; // fixed utime
    }

    ## for detail information
    ##
    if(func_num_args() > 3)
    {
        $i = $sidx;
        foreach($times AS $time)
        {
            $sun[$i] = solar::sun($time,FALSE);
            $sun[$i]['_avgdate'] = calendar::_date('D, d M Y H:i:s ',$start+$tterms[$i]-date('Z')).'GMT';
            $sun[$i]['_name'] = &$hterms[$i];
            $i++;
        }
    }

    unset($times);

    return $terms; // array
  }

  ## public, get a Constellation of zodiac
  ##
  function &zodiac($y, $m, $d)
  {
    static $ffd = array // patch day
    (
    19030622 => -24, 19221222 =>   4, 19540420 => -61, 19550723 => -48,
    19551222 => -57, 19560320 => -48, 19580823 => -52, 19600219 => -55,
    19610221 => -59, 19620823 => -37, 19651023 => -42, 19870525 =>  64,
    19880722 =>  61, 20230621 =>   4, 20300218 =>   7
    );

    $horoscope = array
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
    list($L) = solar::sunl(calendar::_mktime(23,59+(int)$ffd[$fk],59,$m,$d,$y));

    return $horoscope[floor($L/30)]; // string
  }

  ## public, get sunrise, sunset on Korea
  ##
  ## same as PHP5 `date_sunrise()', `date_sunset()'
  ## http://williams.best.vwh.net/sunrise_sunset_example.htm
  ## or
  ## Positional Astronomy: Sunrise, sunset and twilight
  ## http://star-www.st-and.ac.uk/~fv/webnotes/chapt12.htm 
  ## or
  ## http://kr.php.net/manual/en/function.date-sunrise.php
  ##
  ## [zeniths]
  ##  offical      = 90 degrees 50'
  ##  civil        = 96 degrees
  ##  nautical     = 102 degrees
  ##  astronomical = 108 degrees
  ##
  function &sunrise_sunset($_y=0, $_m=0, $_d=0, $_location='', $_zenith=3)
  {
    static $_zeniths = array(90.8333, 96.0, 102.0, 108.0);

    $_timezone = date('Z')/3600; //$_timezone = 9.0; // KST +9H

    ## check arguments
    ##
    $argc = func_num_args();
    if($argc < 3)
    {
        list($y,$m,$_d) = explode(' ',date('Y n j'));
        if($argc < 2) $_m = $m;
        if($argc < 1) $_y = $y;
    }
    if(!preg_match('/^[0-3]$/',$_zenith)) $_zenith = 0;

    ## inital configurations
    ##
    $location  = calendar::location_kr(!$_location?0:$_location); // force '' to 0
    $longitude = $location[0];
    $latitude  = deg2rad($location[1]);
    $zeniths   = array_slice($_zeniths,0,$_zenith+1); // rewrite san2@2011.11.08

    ## 1. first calculate the day of the year
    ##
    $N = floor(275*$_m/9) - (floor(($_m+9)/12) * (1+floor(($_y-4*floor($_y/4)+2)/3))) + $_d - 30;

    ## 2. convert the longitude to hour value and calculate an approximate time
    ##
    $lhour = $longitude / 15;
    $t['r'] = sprintf('%.8f',$N+((6-$lhour)/24.0)); // sunrise
    $t['s'] = sprintf('%.8f',$N+((18-$lhour)/24.0)); // sunset

    ## 3. calculate the Sun's mean anomaly
    ##
    $M['r'] = (0.9856*$t['r']) - 3.289;
    $M['s'] = (0.9856*$t['s']) - 3.289;

    ## 4. calculate the Sun's true longitude
    ## to be adjusted into the range [0,360) by adding/subtracting 360
    ##
    $L['r'] = $M['r'] + (1.916*sin(deg2rad($M['r']))) + (0.020*sin(deg2rad(2*$M['r']))) + 282.634;
    $L['s'] = $M['s'] + (1.916*sin(deg2rad($M['s']))) + (0.020*sin(deg2rad(2*$M['s']))) + 282.634;
    $L['r'] = ($L['r']>=0) ? fmod($L['r'],360) : fmod($L['r'],360)+360.0;
    $L['s'] = ($L['s']>=0) ? fmod($L['s'],360) : fmod($L['s'],360)+360.0;
    $l['r'] = deg2rad($L['r']);
    $l['s'] = deg2rad($L['s']);

    ## 5a. calculate the Sun's right ascension
    ## to be adjusted into the range [0,360) by adding/subtracting 360
    ##
    $RA['r'] = rad2deg(atan(0.91764*tan($l['r'])));
    $RA['s'] = rad2deg(atan(0.91764*tan($l['s'])));
    $RA['r'] = ($RA['r']>=0) ? fmod($RA['r'],360) : fmod($RA['r'],360)+360.0;
    $RA['s'] = ($RA['s']>=0) ? fmod($RA['s'],360) : fmod($RA['s'],360)+360.0;

    ## 5b. right ascension value needs to be in the same quadrant as L
    ##
    $RA['r'] += (floor($L['r']/90.0)*90.0) - (floor($RA['r']/90.0)*90.0);
    $RA['s'] += (floor($L['s']/90.0)*90.0) - (floor($RA['s']/90.0)*90.0);

    ## 5c. right ascension value needs to be converted into hours
    ##
    $RA['r'] /= 15;
    $RA['s'] /= 15;

    ## 6. calculate the Sun's declination
    ##
    $sindec['r'] = 0.39782 * sin($l['r']);
    $sindec['s'] = 0.39782 * sin($l['s']);
    $cosdec['r'] = cos(asin($sindec['r']));
    $cosdec['s'] = cos(asin($sindec['s']));

    $r = $has = array();
    foreach($zeniths AS $zenith)
    {
        $zenith = deg2rad($zenith);

        ## 7a. calculate the Sun's local hour angle
        ## (cosH> 1) the sun never rises on this location (on the specified date)
        ## (cosH<-1) the sun never sets on this location (on the specified date)
        ##
        $cosH['r'] = (cos($zenith) - ($sindec['r']*sin($latitude))) / ($cosdec['r']*cos($latitude));
        $cosH['s'] = (cos($zenith) - ($sindec['s']*sin($latitude))) / ($cosdec['s']*cos($latitude));

        if($cosH['r']>1.0 || $cos['s']>1.0) // always setting
        {
            $has[] = -1;
            $r[] = array('---','---');
            continue;
        }
        else if($cosH['r']<-1.0 || $cosH['s']<-1.0) // always rising
        {
            $has[] = -2;
            $r[] = array('***','***');
            continue;
        }

        ## 7b. finish calculating H and convert into hours
        ##
        $H['r'] = 360.0 - rad2deg(acos($cosH['r'])); $has[] = $H['r'];; // misc
        $H['s'] = rad2deg(acos($cosH['s']));
        $H['r'] /= 15;
        $H['s'] /= 15;

        ## 8. calculate local mean time of rising/setting
        ##
        $T['r'] = $H['r'] + $RA['r'] - (0.06571*$t['r']) - 6.622;
        $T['s'] = $H['s'] + $RA['s'] - (0.06571*$t['s']) - 6.622;

        ## 9. adjust back to UTC
        ## to be adjusted into the range [0,24) by adding/subtracting 24
        ##
        $UT['r'] = $T['r'] - $lhour;
        $UT['s'] = $T['s'] - $lhour;
        $UT['r'] = ($UT['r']>=0) ? fmod($UT['r'],24.0) : fmod($UT['r'],24.0) + 24.0;
        $UT['s'] = ($UT['s']>=0) ? fmod($UT['s'],24.0) : fmod($UT['s'],24.0) + 24.0;

        ## 10. convert UT value to local time zone of latitude/longitude
        ##
        $localT['r'] = fmod($UT['r']+$_timezone,24.0);
        $localT['s'] = fmod($UT['s']+$_timezone,24.0);

        ## last convert localT to human time
        ##
        /***
        $rise['H'] = floor($localT['r']);
        $rise['m'] = (int)(($localT['r']-$rise['H'])*60);
        $set['H'] = floor($localT['s']);
        $set['m'] = (int)(($localT['s']-$set['H'])*60);

        $r[] = array
        (
        sprintf('%02d',$rise['H']).':'.sprintf('%02d',$rise['m']), // sunrise HH:MM
        sprintf('%02d',$set['H']).':'.sprintf('%02d',$set['m']) // sunset HH:MM
        );
        ***/

        // good idea
        $r[] = array
        (
        gmdate('H:i',$localT['r']*3600), // rise HH:MM
        gmdate('H:i',$localT['s']*3600) // set HH:MM
        );
    }

    ## meridian time, daytimes, Azimuth at sunrise
    ##
    if($has[0] < -1.0) $meridian = $daytimes = $AZ = '---';
    else if($has[0] < 0) $meridian = $daytimes = $AZ = '***';
    else
    {
        $meridian = gmdate('H:i',($localT['r']+$localT['s'])*1800); // *(1/2)*3600, same as all of zeniths, to seconds
        $daytimes = gmdate('H:i',(360-(($has[0]-180)*2))*240); // 3600/15; // to seconds

        ## Azimuth at sunrise
        ## tan(A) = -cos(dec)*sin(Ha) / (sin(dec)*cos(lat) - cos(dec)*sin(lat)*cos(Ha))
        ##
        $cosha = cos(deg2rad($has[0]));
        $sinha = sin(deg2rad($has[0]));
        $x = -$cosdec['r']*$sinha;
        $y = $sindec['r']*cos($latitude) - $cosdec['r']*sin($latitude)*$cosha;
        $AZ = sprintf('%.2f',calendar::compass($x,$y)); // degrees
    }

    ## Altitude at meridian time
    ## h = (90 - lat) + dec
    ## or
    ## sin(h) = sin(dec)*sin(lat) + cos(dec)*cos(lat)*cos(Ha=0)
    ##
    $ALT = 90.0 - $location[1] + rad2deg(asin($sindec['r'])+asin($sindec['s']))/2;
    list($ALT,$altpos) = calendar::alt2valid($ALT);

    $r[] = array
    (
    $meridian, // meridian time
    $daytimes, // daytimes
    $AZ, // Azimuth at sunrise
    sprintf('%.2f',$ALT).$altpos, // Altitude at meridian time
    );

    return $r;
  }

  ## add san2@2011.11.10
  ##
  function &location_of_sun($_location=0, $y=0, $m=0, $d=0, $h=0, $i=0, $s=0)
  {
    $argc = func_num_args();
    if($argc < 7)
    {
        list($_y,$_m,$_d,$_h,$_i,$s) = explode(' ',date('Y n j G i s'));
        if($argc < 6) $i = $_i;
        if($argc < 5) $h = $_h;
        if($argc < 4) $d = $_d;
        if($argc < 3) $m = $_m;
        if($argc < 2) $y = $_y;
    }

    list($lon,$lat) = calendar::location_kr($_location);
    list(,$arr) = solar::sunrise_sunset($y,$m,$d,$_location,0);
    list($noonh,$noonm) = explode(':',$arr[0]);
    $noon = calendar::_mktime($noonh,$noonm,0,$m,$d,$y);

    $t = $r = array();
    if($argc == 4) for($k=0;$k<24;$k++) $z[] = calendar::_mktime($k,0,0,$m,$d,$y);
    else $z[0] = calendar::_mktime($h,$i,$s,$m,$d,$y);

    foreach($z AS $utime)
    {
        $sun = solar::sun($utime);
        $dec = $sun['d'];
        $ha = ($utime-$noon) * (15/3600); // hour angle (degrees)

        $ALT = calendar::eq2alt($dec,$lat,$ha);
        $AZ = calendar::eq2az($dec,$lat,$ha);
        $pos = calendar::az2polar($AZ);

        $r[] = array
        (
            calendar::deg2hms($sun['RA']),
            calendar::deg2dms($sun['d']),
            calendar::deg2dms($AZ), // Azimuth
            calendar::deg2dms($ALT), // Altitude
            $pos // polar for AZ
        );
    }

    return $r;
  }

  ## add san2@2010.07.29
  ##
  function &_get_basejd_of_sambok($_y, $_m, $_d)
  {
    static $basejd = 2451547.0; // 2000.01.03 12:00:00 UTC, base of kanji, idx 0

    list($bjd) = calendar::_getjd($_y,$_m,$_d,12,0,0,0);
    $addterm = (floor($bjd)-$basejd) % 10;

    if($addterm > 0) $addterm = 10 - $addterm;
    else if($addterm < 0) $addterm = abs($addterm);

    return $bjd + $addterm; // JD
  }

  ## add san2@2010.07.29
  ##
  function &sambok($_y=NULL)
  {
    if($_y === NULL) $_y = date('Y');

    $terms = solar::terms($_y,6,2); // solar 24's terms of Jun ~ Oct
    $terms = array_keys($terms);
    $h[0] = substr($terms[1],0,1);
    $h[1] = substr($terms[1],-2);
    $l[0] = substr($terms[4],0,1);
    $l[1] = substr($terms[4],-2);

    ## JD
    ##
    $chobok = solar::_get_basejd_of_sambok($_y,$h[0],$h[1]) + 20;
    $malbok = solar::_get_basejd_of_sambok($_y,$l[0],$l[1]);
    $jungbok = $chobok + 10;

    ## JD to date
    ##
    $c = calendar::_todate($chobok);
    $j = calendar::_todate($jungbok);
    $m = calendar::_todate($malbok);

    $c = $c[1].sprintf('%02d',$c[2]);
    $j = $j[1].sprintf('%02d',$j[2]);
    $m = $m[1].sprintf('%02d',$m[2]);

    ## add korean name
    ##
    $kname['c'] = chr(195).chr(202).chr(186).chr(185);
    $kname['j'] = chr(193).chr(223).chr(186).chr(185);
    $kname['m'] = chr(184).chr(187).chr(186).chr(185);

    return array($c=>$kname['c'], $j=>$kname['j'], $m=>$kname['m']);
  }
} // end of class

return; // do not any print at below this line

/*** example ***
require_once 'class.calendar.php';
require_once 'class.solar.php';
$sun = array();
$terms = solar::terms(date('Y'),1,12,$sun);
print_r($terms);
print_r($sun);
print_r(solar::sun(time()));
echo solar::today()."\n";
echo solar::solar(mktime(0,0,0,3,20))."\n";
echo solar::solar(mktime(0,0,0,3,21))."\n";
echo solar::solar(mktime(0,0,0,3,22))."\n";
echo "\n\n";
print_r(solar::terms(2023));
***/

