<?php

class scheduleModel extends schedule
{

	private static $config = NULL;

	function getConfig()
	{
		if(self::$config === NULL)
		{
			$oModuleModel = getModel('module');
			$config = $oModuleModel->getModuleConfig('schedule');

			if(!$config->viewconfig)
			{
				$config->viewconfig = 'Y';
			}

			self::$config = $config;
		}

		return self::$config;
	}

	function getScheduleInfo($module_srl)
	{
		$args = new stdClass();
		$args->module_srl = $module_srl;
		$output = executeQuery('schedule.getScheduleInfo', $args);
		if(!$output->data->module_srl)
		{
			return;
		}

		$oModuleModel = getModel('module');
		$module_info = $oModuleModel->getModuleInfoByModuleSrl($output->data->module_srl);

		return $module_info;
	}

	function getScheduleList($selected_date, $module_srl)
	{
		$args = new stdClass();
		$args->selected_date = $selected_date;
		$args->module_srl = $module_srl;
		$output = executeQueryArray('schedule.getScheduleList', $args);
		return $output->data;
	}

	function getSchedule($schedule_srl)
	{
		if(!$schedule_srl)
		{
			return new Object();
		}
		$args = new stdClasS();
		$args->schedule_srl = $schedule_srl;
		$output = executeQuery('schedule.getSchedule', $args);

		return $output->data;
	}

	function sunlunar_data()
	{
		return "1212122322121-1212121221220-1121121222120-2112132122122-2112112121220-2121211212120-2212321121212-2122121121210-2122121212120-1232122121212-1212121221220-1121123221222-1121121212220-1212112121220-2121231212121-2221211212120-1221212121210-2123221212121-2121212212120-1211212232212-1211212122210-2121121212220-1212132112212-2212112112210-2212211212120-1221412121212-1212122121210-2112212122120-1231212122212-1211212122210-2121123122122-2121121122120-2212112112120-2212231212112-2122121212120-1212122121210-2132122122121-2112121222120-1211212322122-1211211221220-2121121121220-2122132112122-1221212121120-2121221212110-2122321221212-1121212212210-2112121221220-1231211221222-1211211212220-1221123121221-2221121121210-2221212112120-1221241212112-1212212212120-1121212212210-2114121212221-2112112122210-2211211412212-2211211212120-2212121121210-2212214112121-2122122121120-1212122122120-1121412122122-1121121222120-2112112122120-2231211212122-2121211212120-2212121321212-2122121121210-2122121212120-1212142121212-1211221221220-1121121221220-2114112121222-1212112121220-2121211232122-1221211212120-1221212121210-2121223212121-2121212212120-1211212212210-2121321212221-2121121212220-1212112112210-2223211211221-2212211212120-1221212321212-1212122121210-2112212122120-1211232122212-1211212122210-2121121122210-2212312112212-2212112112120-2212121232112-2122121212110-2212122121210-2112124122121-2112121221220-1211211221220-2121321122122-2121121121220-2122112112322-1221212112120-1221221212110-2122123221212-1121212212210-2112121221220-1211231212222-1211211212220-1221121121220-1223212112121-2221212112120-1221221232112-1212212122120-1121212212210-2112132212221-2112112122210-2211211212210-2221321121212-2212121121210-2212212112120-1232212122112-1212122122120-1121212322122-1121121222120-2112112122120-2211231212122-2121211212120-2122121121210-2124212112121-2122121212120-1212121223212-1211212221220-1121121221220-2112132121222-1212112121220-2121211212120-2122321121212-1221212121210-2121221212120-1232121221212-1211212212210-2121123212221-2121121212220-1212112112220-1221231211221-2212211211220-1212212121210-2123212212121-2112122122120-1211212322212-1211212122210-2121121122120-2212114112122-2212112112120-2212121211210-2212232121211-2122122121210-2112122122120-1231212122212-1211211221220-2121121321222-2121121121220-2122112112120-2122141211212-1221221212110-2121221221210-2114121221221";
	}

	function solaToLunar($yyyymmdd)
	{

		$getYEAR = substr($yyyymmdd, 0, 4);
		$getMONTH = substr($yyyymmdd, 4, 2);
		$getDAY = substr($yyyymmdd, 6, 2);

		$arrayDATASTR = $this->sunlunar_data();
		$arrayDATA = explode("-", $arrayDATASTR);
		$arrayLDAYSTR = "31-0-31-30-31-30-31-31-30-31-30-31";
		$arrayLDAY = explode("-", $arrayLDAYSTR);
		$dt = $arrayDATA;

		for($i = 0; $i <= 168; $i++)
		{
			$dt[$i] = 0;
			for($j = 0; $j < 12; $j++)
			{
				switch(substr($arrayDATA[$i], $j, 1))
				{

					case 1:
						$dt[$i] += 29;
						break;

					case 3:
						$dt[$i] += 29;
						break;

					case 2:
						$dt[$i] += 30;
						break;

					case 4:
						$dt[$i] += 30;
						break;
				}
			}

			switch(substr($arrayDATA[$i], 12, 1))
			{

				case 0:
					break;

				case 1:
					$dt[$i] += 29;
					break;

				case 3:
					$dt[$i] += 29;
					break;

				case 2:
					$dt[$i] += 30;
					break;

				case 4:
					$dt[$i] += 30;
					break;
			}
		}

		$td1 = 1880 * 365 + (int)(1880 / 4) - (int)(1880 / 100) + (int)(1880 / 400) + 30;
		$k11 = $getYEAR - 1;
		$td2 = $k11 * 365 + (int)($k11 / 4) - (int)($k11 / 100) + (int)($k11 / 400);

		if($getYEAR % 400 == 0 || $getYEAR % 100 != 0 && $getYEAR % 4 == 0)
		{
			$arrayLDAY[1] = 29;

		}
		else
		{
			$arrayLDAY[1] = 28;
		}

		if($getMONTH > 13)
		{
			$gf_sol2lun = 0;
		}

		if($getDAY > $arrayLDAY[$getMONTH - 1])
		{
			$gf_sol2lun = 0;
		}

		for($i = 0; $i <= $getMONTH - 2; $i++)
		{
			$td2 += $arrayLDAY[$i];
		}

		$td2 += $getDAY;
		$td = $td2 - $td1 + 1;
		$td0 = $dt[0];

		for($i = 0; $i <= 168; $i++)
		{
			if($td <= $td0)
			{
				break;
			}
			$td0 += $dt[$i + 1];
		}

		$ryear = $i + 1881;
		$td0 -= $dt[$i];
		$td -= $td0;

		if(substr($arrayDATA[$i], 12, 1) == 0)
		{
			$jcount = 11;

		}
		else
		{
			$jcount = 12;
		}

		$m2 = 0;

		for($j = 0; $j <= $jcount; $j++)
		{ // 달수 check, 윤달 > 2 (by harcoon)
			if(substr($arrayDATA[$i], $j, 1) <= 2)
			{
				$m2++;
				$m1 = substr($arrayDATA[$i], $j, 1) + 28;
				$gf_yun = 0;
			}
			else
			{
				$m1 = substr($arrayDATA[$i], $j, 1) + 26;
				$gf_yun = 1;
			}
			if($td <= $m1)
			{
				break;
			}
			$td = $td - $m1;
		}
		$arrayYUK = array();
		$arrayGAP = array();
		$arrayDDI = array();
		$k1 = ($ryear + 6) % 10;
		$syuk = $arrayYUK[$k1];
		$k2 = ($ryear + 8) % 12;
		$sgap = $arrayGAP[$k2];
		$sddi = $arrayDDI[$k2];
		$gf_sol2lun = 1;

		if($m2 < 10)
		{
			$m2 = "0" . $m2;
		}
		if($sday < 10)
		{
			$td = "0" . $td;
		}

		$Ary[year] = $ryear;
		$Ary[month] = $m2;
		$Ary[day] = $td;
		$Ary[time] = mktime(0, 0, 0, $Ary[month], $Ary[day], $Ary[year]);

		return $Ary;

	}
}
/* End of file */
