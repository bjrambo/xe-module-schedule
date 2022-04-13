<?php

class scheduleModel extends schedule
{

	public static $schedule_config = NULL;

	public static function getScheduleConfig()
	{
		if ( self::$schedule_config === NULL )
		{
			self::$schedule_config = moduleModel::getModuleConfig('schedule') ?: new stdClass;
		}

		return self::$schedule_config;
	}

	/**
	 * @brief get the list configuration
	 **/
	public static function getListConfig($module_srl)
	{
		// get the list config value, if it is not exitsted then setup the default value
		$list = array();
		$list_config = moduleModel::getModulePartConfig('schedule', $module_srl);
		if ( !is_array($list_config) || count($list_config) <= 0 || !$list_config[0] )
		{
			$virtual_config = array('no', 'title', 'selected_date');
			foreach ( $virtual_config as $key )
			{
				$list[$key] = lang($key);
			}
		}
		else
		{
			foreach ( $list_config as $key )
			{
				$list[$key] = lang($key);
			}
		}
		return $list;
	}

	/**
	 * @brief return the default list configration value
	 **/
	public static function getDefaultListConfig($module_srl)
	{
		// add virtual srl, title, registered date, nickname, etc.
		$virtual_vars = array('no', 'title', 'category_srl', 'selected_date', 'recur', 'place', 'nick_name', 'regdate', 'status', 'thumbnail');
		foreach ( $virtual_vars as $key )
		{
			$extra_vars[$key] = lang($key);
		}
		return $extra_vars;
	}

	public static function getScheduleInfo($module_srl)
	{
		return moduleModel::getModuleInfoByModuleSrl($module_srl);
	}

	public static function getScheduleInfoByMid($mid)
	{
		return moduleModel::getModuleInfoByMid($mid);
	}

	public static function getScheduleStatusList()
	{
		return lang('status_list');
	}

	public static function _setSortIndex($obj)
	{
		$args = new stdClass;
		$args->sort_index = $obj->sort_index ?? null;

		// check it's default sort
		$default_sort = array('list_order', 'regdate', 'title', 'category_srl');
		if ( in_array($args->sort_index, $default_sort) )
		{
			return $args;
		}

		return $args;
	}

	public static function _setSearchOption($searchOpt, &$args, &$query_id, &$use_division)
	{
		$args = new stdClass;
		$args->module_srl = $searchOpt->module_srl ?? null;
		$args->exclude_module_srl = $searchOpt->exclude_module_srl ?? null;
		$args->category_srl = $searchOpt->category_srl ?? null;
		if ( isset($searchOpt->member_srl) && $searchOpt->member_srl )
		{
			$args->member_srl = $searchOpt->member_srl;
		}
		elseif ( isset($searchOpt->member_srls) && $searchOpt->member_srls )
		{
			$args->member_srl = $searchOpt->member_srls;
		}
		$args->order_type = ( isset($searchOpt->order_type) && $searchOpt->order_type === 'desc' ) ? 'desc' : 'asc';
		$args->sort_index = $searchOpt->sort_index;
		$args->page = $searchOpt->page ?? 1;
		$args->list_count = $searchOpt->list_count ?? 20;
		$args->page_count = $searchOpt->page_count ?? 10;
		$args->regdate = $searchOpt->regdate ?? null;
		$args->start_date = $searchOpt->start_date ?? null;
		$args->end_date = $searchOpt->end_date ?? null;
		$args->status = $searchOpt->status ?? null;
		$args->columnList = $searchOpt->columnList ?? array();

		// get directly module_srl by mid
		if ( isset($searchOpt->mid) && $searchOpt->mid )
		{
			$args->module_srl = moduleModel::getModuleSrlByMid($searchOpt->mid);
		}

		// add subcategories
		if ( isset($args->category_srl) && $args->category_srl )
		{
			$category_list = DocumentModel::getCategoryList($args->module_srl);
			if ( isset($category_list[$args->category_srl]) )
			{
				$categories = $category_list[$args->category_srl]->childs;
				$categories[] = $args->category_srl;
				$args->category_srl = $categories;
			}
		}

		// default
		$query_id = null;
		$use_division = false;
		$search_target = $searchOpt->search_target ?? null;
		$search_keyword = trim($searchOpt->search_keyword ?? null) ?: null;

		// search
		if ( $search_target && $search_keyword )
		{
			switch ( $search_target )
			{
				case 'title' :
				case 'content' :
				case 'comment' :
				case 'title_content' :
					$use_division = true;
					$search_keyword = trim(utf8_normalize_spaces($search_keyword));
					if ( $search_target == 'title_content' )
					{
						$args->s_title = $search_keyword;
						$args->s_content = $search_keyword;
					}
					else
					{
						$args->{'s_' . $search_target} = $search_keyword;
					}
					break;
				case 'nick_name' :
				case 'email_address' :
				case 'regdate' :
				case 'member_srl' :
				default :
					break;
			}

			// exclude secret documents in searching if current user does not have privilege
			if ( !isset($args->member_srl) || !$args->member_srl || !Context::get('is_logged') || $args->member_srl !== Context::get('logged_info')->member_srl )
			{
				$module_info = moduleModel::getModuleInfoByModuleSrl($args->module_srl);
				if ( !moduleModel::getGrant($module_info, Context::get('logged_info'))->manager )
				{
					$args->status = 'PUBLIC';
				}
			}
		}

		// set query
		if ( !$query_id )
		{
			$query_id = 'schedule.getScheduleList';
		}

		// division search by 5,000
		if ( $use_division )
		{
			$args->order_type = 'asc';
			$args->sort_index = 'list_order';
			$args->division = (int)Context::get('division');
			$args->last_division = (int)Context::get('last_division');

			$division_args = new stdClass;
			$division_args->module_srl = $args->module_srl;
			$division_args->exclude_module_srl = $args->exclude_module_srl;

			// get start point of first division
			if ( Context::get('division') === null )
			{
				$division_output = executeQuery('schedule.getSchedule', $division_args)->data;
				$args->division = $division_output ? $division_output->list_order : 0;
			}

			// get end point of the division
			if ( Context::get('last_division') === null && $args->division )
			{
				$division_args->offset = 5000;
				$division_args->list_order = $args->division;
				$division_output = executeQuery('schedule.getScheduleDivision', $division_args)->data;
				$args->last_division = $division_output ? $division_output->list_order : 0;
			}

			Context::set('division', $args->division);
			Context::set('last_division', $args->last_division);
		}

		// add default prefix
		if ( $args->sort_index && strpos($args->sort_index, '.') === false )
		{
			$args->sort_index = 'schedules.' . $args->sort_index;
		}
		foreach ( $args->columnList as $key => $column )
		{
			if ( strpos($column, '.') !== false )
			{
				continue;
			}
			$args->columnList[$key] = 'schedules.' . $column;
		}
	}

	public static function getSchedule($schedule_srl)
	{
		if ( !$schedule_srl )
		{
			return;
		}

		$args = new stdClass();
		$args->schedule_srl = $schedule_srl;
		$output = executeQuery('schedule.getSchedule', $args);

		// Return if no result or an error occurs
		if ( !$output->toBool() || !$output->data )
		{
			return;
		}

		if ( !isset($GLOBALS['RX_SCHEDULE_LIST'][$schedule_srl]) )
		{
			$oSchedule = new scheduleItem();
			$oSchedule->setAttribute($output->data);
		}

		return $GLOBALS['RX_SCHEDULE_LIST'][$schedule_srl];
	}

	public static function getSchedules($schedule_srls)
	{
		$schedule_list = array();

		$args = new stdClass();
		$args->schedule_srls = $schedule_srls;

		$output = executeQueryArray('schedule.getSchedules', $args);

		// Return if no result or an error occurs
		if ( !$output->toBool() || !$output->data )
		{
			return $schedule_list;
		}

		foreach ( $output->data as $key => $attribute )
		{
			$schedule_srl = $attribute->schedule_srl;
			if ( !isset($GLOBALS['RX_SCHEDULE_LIST'][$schedule_srl]) )
			{
				$oSchedule = new scheduleItem();
				$oSchedule->setAttribute($attribute);
			}
			$schedule_list[$key] = $GLOBALS['RX_SCHEDULE_LIST'][$schedule_srl];
		}

		return $schedule_list;
	}

	public static function getSchedulesBySelectedDate($selected_date = null, $module_srl = null, $category_srl = 0, $member_srl = null)
	{
		$args = new stdClass();
		$args->selected_date = $selected_date;
		$args->module_srl = $module_srl;
		$args->category_srl = $category_srl ? $category_srl : null;
		$args->member_srl = $member_srl;

		$output = executeQueryArray('schedule.getSchedules', $args);

		// Return if no result or an error occurs
		if ( !$output->toBool() || !$output->data )
		{
			return array();
		}

		$schedule_list = array();
		foreach ( $output->data as $key => $attribute )
		{
			$dates = explode(',', $attribute->selected_date);
			$schedule_srl = $attribute->schedule_srl;
			foreach ( $dates as $date )
			{
				if ( $selected_date && substr($date, 0, 6) == substr($selected_date, 0, 6) )
				{
					$day = (int)substr($date, 6, 2);
					if ( !isset($GLOBALS['RX_SCHEDULE_LIST'][$attribute->schedule_srl]) )
					{
						$oSchedule = new scheduleItem();
						$oSchedule->setAttribute($attribute);
					}
					$schedule = $GLOBALS['RX_SCHEDULE_LIST'][$attribute->schedule_srl];
					if ( $schedule->getDisplay() != 'HIDE' )
					{
						$schedule_list[$day][$schedule_srl] = $schedule;
					}
				}
			}
		}

		foreach ( $schedule_list as $day => $schedules )
		{
			if ( count($schedules) > 1 )
			{
				usort($schedules, function($a, $b) {
					return ($a->get('start_time') < $b->get('start_time')) ? -1 : 1;
				});
			}
			$schedule_list[$day] = $schedules;
		}

		return $schedule_list;
	}

	public static function getScheduleList($obj, $columnList = array())
	{
		$sort_check = self::_setSortIndex($obj);
		$obj->sort_index = $sort_check->sort_index;
		$obj->columnList = $columnList;

		// execute query
		self::_setSearchOption($obj, $args, $query_id, $use_division);
		$output = executeQueryArray($query_id, $args, $args->columnList);

		// Return if no result or an error occurs
		if ( !$output->toBool() || !$result = $output->data )
		{
			return $output;
		}

		$output->data = array();
		foreach ( $result as $key => $attribute )
		{
			if ( !isset($GLOBALS['RX_SCHEDULE_LIST'][$attribute->schedule_srl]) )
			{
				$oSchedule = new scheduleItem();
				$oSchedule->setAttribute($attribute);
			}
			$output->data[$key] = $GLOBALS['RX_SCHEDULE_LIST'][$attribute->schedule_srl];
		}

		return $output;
	}

	public static function getAPIInfo($lunar_mode = false)
	{
		$api_info = array();
		$schedule_config = self::getScheduleConfig();
		
		if ( !isset($schedule_config->api) || !$schedule_config->api )
		{
			return $api_info;
		}

		$api_info['key'] = $schedule_config->api;
		$api_info['url'] = $lunar_mode
			? 'http://apis.data.go.kr/B090041/openapi/service/LrsrCldInfoService/'
			: 'http://apis.data.go.kr/B090041/openapi/service/SpcdeInfoService/';

		return $api_info;
	}

	public static function getHolidayList($year, $month)
	{
		$holidays = array();

		$default_holidays = array(
			'01' => array('01' => '1월1일'),
			'03' => array('01' => '삼일절'),
			'05' => array('05' => '어린이날'),
			'06' => array('06' => '현충일'),
			'07' => array('17' => '제헌절'),
			'08' => array('15' => '광복절'),
			'10' => array('03' => '개천절', '09' => '한글날'),
			'12' => array('25' => '기독탄신일'),
		);
		if ( !empty($default_holidays[$month]) )
		{
			foreach ( $default_holidays[$month] as $day => $name )
			{
				$_day = (int)$day;
				$holidays[$_day]['holiday'][0] = new stdClass();
				$holidays[$_day]['holiday'][0]->title = $name;
				$holidays[$_day]['holiday'][0]->category = 'holiday';
				$holidays[$_day]['holiday'][0]->schedule_srl = '';
				$holidays[$_day]['holiday'][0]->is_sequence = 0;
			}
		}

		if ( !in_array($month, array('03', '06', '07', '11', '12')) )
		{
			include_once __DIR__ . '/lib/lib.calendar.php';
			include_once __DIR__ . '/lib/lib.lunarday.php';

			$lunar_holidays = array(
				'0101' => '설날',
				'0408' => '석가탄신일',
				'0815' => '추석',
			);
			foreach ( $lunar_holidays as $date => $name )
			{
				$_month = substr($date, 0, 2);
				$day = substr($date, -2);
				$_result = lunar::tosolar($year, $_month, $day);
				$solar_month = sprintf('%02d', $_result[3]);
				$solar_day = sprintf($_result[4]);

				if ( $date == '0101' || $date == '0815' )
				{
					$current_date = $year . '-' . $solar_month . '-' . $solar_day;

					for ( $i = 0; $i < 2; $i++ )
					{
						$step = ( $i == 0 ) ? ' -1 day' : ' +1 day';
						$side = explode('-', date('m-d', strtotime($current_date . $step)));
						$side_date_month = $side[0];
						$side_date_day = (int)$side[1];

						if ( $side_date_month != $month )
						{
							continue;
						}

						$side_key = ( empty($holidays[$side_date_day]['holiday']) ) ? 0 : 1;

						$holidays[$side_date_day]['holiday'][$side_key] = new stdClass();
						$holidays[$side_date_day]['holiday'][$side_key]->title = $name;
						$holidays[$side_date_day]['holiday'][$side_key]->category = 'holiday';	
						$holidays[$side_date_day]['holiday'][$side_key]->schedule_srl = '';
						$holidays[$side_date_day]['holiday'][$side_key]->is_sequence = $i;
					}
				}

				if ( $solar_month != $month )
				{
					continue;
				}

				$_day = (int)$solar_day;
				$key = ( empty($holidays[$_day]['holiday']) ) ? 0 : 1;

				$holidays[$_day]['holiday'][$key] = new stdClass();
				$holidays[$_day]['holiday'][$key]->title = $name;
				$holidays[$_day]['holiday'][$key]->category = 'holiday';
				$holidays[$_day]['holiday'][$key]->schedule_srl = '';
				$holidays[$_day]['holiday'][$key]->is_sequence = ( $date == '0101' || $date == '0815' ) ? 1 : 0;
			}
		}

		if ( !empty($holidays) )
		{
			ksort($holidays);
		}
		return $holidays;
	}

	public static function getSundryList($year, $month)
	{
		$sundry_days = array();

		if ( !in_array($month, array('03', '04', '09', '10', '11', '12')) )
		{
			include_once __DIR__ . '/lib/lib.lunarday.php';
			$sundry_lunar = array(
				'0115' => '정월대보름',
				'0505' => '단오',
				'0707' => '칠석',
			);

			foreach ( $sundry_lunar as $date => $name )
			{
				$_month = substr($date, 0, 2);
				$day = substr($date, -2);
				$_result = lunar::tosolar($year, $_month, $day);
				$solar_month = sprintf('%02d', $_result[3]);
				$solar_day = sprintf($_result[4]);

				if ( $solar_month != $month )
				{
					continue;
				}

				$_day = (int)$solar_day;
				$sundry_days[$_day]['sundry'][0] = new stdClass();
				$sundry_days[$_day]['sundry'][0]->title = $name;
				$sundry_days[$_day]['sundry'][0]->category = 'sundry';
				$sundry_days[$_day]['sundry'][0]->schedule_srl = '';
				$sundry_days[$_day]['sundry'][0]->is_sequence = 0;
			}
		}

		if ( in_array($month, array('04', '07', '08')) )
		{
			include_once __DIR__ . '/lib/lib.calendar.php';
			include_once __DIR__ . '/lib/lib.divisions.php';

			$year = (int)$year;
			$month = (int)$month;

			if ( $month == 4 )
			{
				$terms = solar::terms($year-1, 12, 0);
				$i = 0;
				foreach ( $terms as $date => $name )
				{
					if ( $i == 0 )
					{
						$i++;
						continue;
					}

					$dongji = $year-1 . '-' . substr($date, 0, 2) . '-' . substr($date, -2);
					$hansik = (int)date('d', strtotime($dongji . ' +105 days'));

					$sundry_days[$hansik]['sundry'][0] = new stdClass();
					$sundry_days[$hansik]['sundry'][0]->title = '한식';
					$sundry_days[$hansik]['sundry'][0]->category = 'sundry';
					$sundry_days[$hansik]['sundry'][0]->schedule_srl = '';
					$sundry_days[$hansik]['sundry'][0]->is_sequence = 0;
				}
			}
			elseif ( $month == 7 || $month == 8 )
			{
				$sambok7 = solar::sambok($year);
				$sambok8 = array_slice($sambok7, -1, 1, true);
				array_pop($sambok7);

				foreach ( ${'sambok'.$month} as $date => $name )
				{
					$_day = (int)substr($date, -2);
					$key = ( empty($sundry_days[$_day]['sundry']) ) ? 0 : 1;

					$sundry_days[$_day]['sundry'][$key] = new stdClass();
					$sundry_days[$_day]['sundry'][$key]->title = iconv('EUC-KR', 'UTF-8', $name);
					$sundry_days[$_day]['sundry'][$key]->category = 'sundry';
					$sundry_days[$_day]['sundry'][$key]->schedule_srl = '';
					$sundry_days[$_day]['sundry'][$key]->is_sequence = 0;
				}
			}
		}

		return $sundry_days;
	}
	
	public static function getSpecialdayListBySelf($year, $month, $use_holiday, $use_sundry)
	{
		$container = array();

		// get holiday
		if ( $use_holiday == 'Y' )
		{
			$container = self::getHolidayList($year, $month);
		}
		// get sundry
		if ( $use_sundry == 'Y' )
		{
			$container = array_replace_recursive($container, self::getSundryList($year, $month, $container));
		}
		return $container;
	}

	public static function getSpecialdayList($year, $month, $use_holiday, $use_sundry, $customs)
	{
		$container = array();

		// get custom
		if ( $customs )
		{
			foreach ( explode("\r\n", $customs) as $key => $custom )
			{
				$c = array_map('trim', explode(',', $custom));
				if ( substr($c[1], 0, 2) == $month )
				{
					$day = (int)substr($c[1], 2);
					$container[$day]['custom'][$key] = new stdClass();
					$container[$day]['custom'][$key]->title = $c[0];
					$container[$day]['custom'][$key]->category = 'custom';
					$container[$day]['custom'][$key]->schedule_srl = '';
					$container[$day]['custom'][$key]->is_sequence = 0;
				}
			}
		}

		$api_info = self::getAPIInfo(false);
		if ( empty($api_info) )
		{
			$special_days = self::getSpecialdayListBySelf($year, $month, $use_holiday, $use_sundry);
			return array_replace_recursive($special_days, $container);
		}
		$month = sprintf('%02d', $month);

		// get holiday
		if ( $use_holiday == 'Y' )
		{
			$xml_file = sprintf(RX_BASEDIR . "files/cache/schedule/%d-holiday.xml", $year.$month);
			if ( file_exists($xml_file) )
			{
				$content = FileHandler::readFile($xml_file);
			}
			else
			{
				$path = $content = $day = '';
				$xml = $json = $list = array();

				$path = $api_info['url'].'getHoliDeInfo?serviceKey='.$api_info['key'].'&solYear='.$year.'&solMonth='.$month;
				$content = file_get_contents($path);

				FileHandler::writeFile($xml_file, $content);
			}

			$xml = simplexml_load_string($content, null, LIBXML_NOCDATA);
			$json = json_encode($xml);
			$list = json_decode($json, TRUE);

			if ( $list['body']['totalCount'] == 1 )
			{
				$day = (int)mb_substr($list['body']['items']['item']['locdate'],6,2,'UTF-8');
				$container[$day]['holiday'][0] = new stdClass();
				$container[$day]['holiday'][0]->title = $list['body']['items']['item']['dateName'];
				$container[$day]['holiday'][0]->category = 'holiday';
				$container[$day]['holiday'][0]->schedule_srl = '';
				$container[$day]['holiday'][0]->is_sequence = 0;
			}
			else if ( $list['body']['totalCount'] > 0 )
			{
				foreach ( $list['body']['items']['item'] as $key => $row )
				{
					$day = (int)mb_substr($row['locdate'], 6, 2, 'UTF-8');
					$container[$day]['holiday'][$key] = new stdClass();
					if ( $row['dateName'] == $list['body']['items']['item'][$key-1]['dateName'] )
					{
						$container[$day]['holiday'][$key]->title = $row['dateName'];
						$container[$day]['holiday'][$key]->category = 'holiday';
						$container[$day]['holiday'][$key]->schedule_srl = '';
						$container[$day]['holiday'][$key]->is_sequence = 1;
					}
					else
					{
						$container[$day]['holiday'][$key]->title = $row['dateName'];
						$container[$day]['holiday'][$key]->category = 'holiday';
						$container[$day]['holiday'][$key]->schedule_srl = '';
						$container[$day]['holiday'][$key]->is_sequence = 0;
					}
				}
			}
		}

		// get sundry
		if ( $use_sundry == 'Y' )
		{
			$xml_file = sprintf(RX_BASEDIR . "files/cache/schedule/%d-sundry.xml", $year.$month);
			if ( file_exists($xml_file) )
			{
				$content = FileHandler::readFile($xml_file);
			}
			else
			{
				$path = $content = $day = '';
				$xml = $json = $list = array();

				$path = $api_info['url'].'getSundryDayInfo?serviceKey='.$api_info['key'].'&solYear='.$year.'&solMonth='.$month;
				$content = file_get_contents($path);

				FileHandler::writeFile($xml_file, $content);
			}

			$xml = simplexml_load_string($content, null, LIBXML_NOCDATA);
			$json = json_encode($xml);
			$list = json_decode($json, TRUE);

			if ( $list['body']['totalCount'] == 1 )
			{
				$day = (int)mb_substr($list['body']['items']['item']['locdate'],6,2,'UTF-8');
				$container[$day]['sundry'][0] = new stdClass();
				$container[$day]['sundry'][0]->title = $list['body']['items']['item']['dateName'];
				$container[$day]['sundry'][0]->category = 'sundry';
				$container[$day]['sundry'][0]->schedule_srl = '';
				$container[$day]['sundry'][0]->is_sequence = 0;
			}
			else if ( $list['body']['totalCount'] > 0 )
			{
				foreach ( $list['body']['items']['item'] as $key => $row )
				{
					$day = (int)mb_substr($row['locdate'], 6, 2, 'UTF-8');
					$container[$day]['sundry'][$key] = new stdClass();
					$container[$day]['sundry'][$key]->title = $row['dateName'];
					$container[$day]['sundry'][$key]->category = 'sundry';
					$container[$day]['sundry'][$key]->schedule_srl = '';
					$container[$day]['sundry'][$key]->is_sequence = 0;
				}
			}
		}

		return $container;
	}

	public static function getLunardayListBySelf($year, $month)
	{
		include_once __DIR__ . '/lib/lib.calendar.php';
		include_once __DIR__ . '/lib/lib.lunarday.php';

		$lunarday_list = array();
		$last_day = date('t', strtotime($year . sprintf('%02d', $month) . '01'));

		for ( $day = 1; $day <= $last_day; $day++ )
		{
			$_result = lunar::tolunar($year, $month,  sprintf('%02d', $day));
			$lunarday_list[$day] = new stdClass();
			$lunarday_list[$day]->lunar_month = $_result[1][1];
			$lunarday_list[$day]->lunar_day = $_result[1][2];
		}

		return $lunarday_list;
	}

	public static function getLunardayList($year, $month)
	{
		$lunarday_list = array();

		$api_info = self::getAPIInfo(true);
		if ( empty($api_info) )
		{
			return self::getLunardayListBySelf($year, $month);
		}
		$month = sprintf('%02d', $month);

		// get lunarday
		$xml_file = sprintf(RX_BASEDIR . "files/cache/schedule/%d-lunarday.xml", $year.$month);

		if ( file_exists($xml_file) )
		{
			$content = FileHandler::readFile($xml_file);
		}
		else
		{
			$path = $content = $day = '';
			$xml = $json = $list = array();

			$day_max = date("t", mktime(0, 0, 0, $month, 1, $year));
			$path = $api_info['url'].'getLunCalInfo?serviceKey='.$api_info['key'].'&solYear='.$year.'&solMonth='.$month.'&numOfRows='.$day_max;
			$content = file_get_contents($path);

			FileHandler::writeFile($xml_file, $content);
		}

		$xml = simplexml_load_string($content, null, LIBXML_NOCDATA);
		$json = json_encode($xml);
		$list = json_decode($json, TRUE);

		foreach ( $list['body']['items']['item'] as $key => $row )
		{
			$day = (int)$row['solDay'];
			$lunarday_list[$day] = new stdClass();
			$lunarday_list[$day]->lunar_month = $row['lunMonth'];
			$lunarday_list[$day]->lunar_day = $row['lunDay'];
		}

		return $lunarday_list;
	}

	public static function getDivisionsListBySelf($year, $month)
	{
		include_once __DIR__ . '/lib/lib.calendar.php';
		include_once __DIR__ . '/lib/lib.divisions.php';

		$year = (int)$year;
		$month = (int)$month;
		$terms = solar::terms($year, $month, 0);

		$divisions_list = array();
		foreach ( $terms as $date => $name )
		{
			$day = (int)substr($date, -2);
			$divisions_list[$day] = new stdClass();
			$divisions_list[$day]->title = $name;
			$divisions_list[$day]->category = 'divisions';
			$divisions_list[$day]->schedule_srl = '';
			$divisions_list[$day]->is_sequence = 0;
		}

		return $divisions_list;
	}

	public static function getDivisionsList($year, $month)
	{
		$divisions_list = array();

		$api_info = self::getAPIInfo(false);
		if ( empty($api_info) )
		{
			return self::getDivisionsListBySelf($year, $month);
		}
		$month = sprintf('%02d', $month);

		// get 24 divisions
		$xml_file = sprintf(RX_BASEDIR . "files/cache/schedule/%d-divisions.xml", $year.$month);

		if ( file_exists($xml_file) )
		{
			$content = FileHandler::readFile($xml_file);
		}
		else
		{
			$path = $content = $day = '';
			$xml = $json = $list = array();

			$path = $api_info['url'].'get24DivisionsInfo?serviceKey='.$api_info['key'].'&solYear='.$year.'&solMonth='.$month;
			$content = file_get_contents($path);

			FileHandler::writeFile($xml_file, $content);
		}

		$xml = simplexml_load_string($content, null, LIBXML_NOCDATA);
		$json = json_encode($xml);
		$list = json_decode($json, TRUE);

		if ( $list['body']['totalCount'] == 1 )
		{
			$day = (int)mb_substr($list['body']['items']['item']['locdate'], 6, 2, 'UTF-8');
			$divisions_list[$day] = new stdClass();
			$divisions_list[$day]->title = $list['body']['items']['item']['dateName'];
			$divisions_list[$day]->category = 'divisions';
			$divisions_list[$day]->schedule_srl = '';
			$divisions_list[$day]->is_sequence = 0;
		}
		else if ( $list['body']['totalCount'] > 0 )
		{
			foreach ( $list['body']['items']['item'] as $key => $row )
			{
				$day = (int)mb_substr($row['locdate'], 6, 2, 'UTF-8');
				$divisions_list[$day] = new stdClass();
				$divisions_list[$day]->title = $row['dateName'];
				$divisions_list[$day]->category = 'divisions';
				$divisions_list[$day]->schedule_srl = '';
				$divisions_list[$day]->is_sequence = 0;
			}
		}

		return $divisions_list;
	}

	public static function getScheduleRecurInfo($schedule_srl, $start_date, $end_date)
	{
		if ( !$schedule_srl )
		{
			return;
		}

		$args = new stdClass();
		$args->schedule_srl = $schedule_srl;
		$output = executeQuery('schedule.getScheduleRecurInfo', $args);

		if ( !$output->toBool() )
		{
			return $output;
		}

		if ( $output->data->recur_type == 'weekday' )
		{
			$orders = ''; $weeks = '';
			foreach ( explode('|@|', $output->data->week_order) as $i => $key )
			{
				$orders .= ($i == 0) ? '' : '/';
				$orders .= lang('week_order')[$key];
			}
			foreach ( explode('|@|', $output->data->weekdays) as $i => $key )
			{
				$weeks .= ($i == 0) ? '' : '/';
				$weeks .= lang('weekdays')[$key];
			}
			$output->data->weekdays_value = $orders . ' ' . $weeks;
		}
		else if ( $output->data->recur_type == 'lunar' )
		{
			$lunarday_value = lang('regular_type')['yearly'] . ' ' . lang('recur_type')['lunar'] . ' ';
			$s_month = substr($start_date, 4, 2); $e_month = substr($end_date, 4, 2);
			$lunar_start_date = self::getLunarFromSolar($start_date);

			$lunarday_value .= ($lunar_start_date->is_leap) ? '(윤)' : '';
			$lunarday_value .= $lunar_start_date->month . '월' . $lunar_start_date->day .'일';

			if ( $start_date != $end_date )
			{
				$lunar_end_date = self::getLunarFromSolar($end_date);
				$lunarday_value .= '~';
				if ( $s_month != $e_month )
				{
					$lunarday_value .= $lunar_end_date->month . '월' . $lunar_end_date->day .'일';
				}
				else
				{
					$lunarday_value .= $lunar_end_date->day .'일';
				}
			}
			$output->data->lunarday_value = $lunarday_value;
		}

		if ( $output->data->exception_type )
		{
			$exception_type = lang('exception_type');
			$exception_type_value = preg_replace_callback(
				'/[a-z]+/',
				function($m) use($exception_type) {
					return $exception_type[$m[0]];
				},
				str_replace('|@|', '/', $output->data->exception_type)
			);
			$output->data->exception_type_value = $exception_type_value;
		}

		return $output->data;
	}

	public static function isHoliday($date)
	{
		$schedule_config = self::getScheduleConfig();
		$module_info = self::getScheduleInfoByMid(Context::get('mid'));

		$year = substr($date, 0, 4);
		$month = substr($date, 4, 2);
		$day = substr($date, 6, 2);
		$api_info = self::getAPIInfo(false);
		if ( empty($api_info) )
		{
			return;
		}

		if ( $module_info->use_holiday == 'Y' )
		{
			$xml_file = sprintf(RX_BASEDIR . "files/cache/schedule/%d-holiday.xml", $year.$month);
			if ( file_exists($xml_file) )
			{
				$content = FileHandler::readFile($xml_file);
			}
			else
			{
				$path = $content = $day = '';
				$xml = $json = $list = array();

				$path = $api_info['url'].'getHoliDeInfo?serviceKey='.$api_info['key'].'&solYear='.$year.'&solMonth='.$month;
				$content = file_get_contents($path);

				FileHandler::writeFile($xml_file, $content);
			}

			$xml = simplexml_load_string($content, null, LIBXML_NOCDATA);
			$json = json_encode($xml);
			$list = json_decode($json, TRUE);

			if ( $list['body']['totalCount'] == 1 )
			{
				return ( $day == substr($list['body']['items']['item']['locdate'], 6, 2) && $list['body']['items']['item']['isHoliday'] == 'Y' );
			}
			else if ( $list['body']['totalCount'] > 0 )
			{
				foreach ( $list['body']['items']['item'] as $key => $row )
				{
					if ( $day == substr($row['locdate'], 6, 2) && $row['isHoliday'] == 'Y' )
					{
						return true;
					}
				}
				return false;
			}
		}
	}

	public static function isSaturday($date)
	{
		return (date('N', strtotime($date)) == 6);
	}

	public static function isSunday($date)
	{
		return (date('N', strtotime($date)) == 7);
	}

	public static function getLunarFromSolar($date)
	{
		$year = substr($date, 0, 4);
		$month = substr($date, 4, 2);
		$day = substr($date, 6, 2);
		$_day = (int)$day - 1;

		$api_info = self::getAPIInfo(true);
		if ( empty($api_info) )
		{
			include_once __DIR__ . '/lib/lib.calendar.php';
			include_once __DIR__ . '/lib/lib.lunarday.php';

			$_result = lunar::tolunar($year, $month, $day);
			$lunar_date = new stdClass();
			$lunar_date->sol_year = $year;
			$lunar_date->year = $_result[1][0];
			$lunar_date->month = $_result[1][1];
			$lunar_date->day = $_result[1][2];
			$lunar_date->is_leap = ($_result[1][6] == 'N') ? 0 : 1;

			return $lunar_date;
		}

		// get lunar_date
		$xml_file = sprintf(RX_BASEDIR . "files/cache/schedule/%d-lunarday.xml", $year.$month);

		if ( file_exists($xml_file) )
		{
			$content = FileHandler::readFile($xml_file);
		}
		else
		{
			$path = $content = '';
			$xml = $json = $list = array();

			$day_max = date('t', mktime(0, 0, 0, $month/1, 1, $year/1));
			$path = $api_info['url'].'getLunCalInfo?serviceKey='.$api_info['key'].'&solYear='.$year.'&solMonth='.$month.'&numOfRows='.$day_max;
			$content = file_get_contents($path);

			FileHandler::writeFile($xml_file, $content);
		}

		$xml = simplexml_load_string($content, null, LIBXML_NOCDATA);
		$json = json_encode($xml);
		$list = json_decode($json, TRUE);

		$lunar_date = new stdClass();
		$lunar_date->sol_year = $list['body']['items']['item'][$_day]['solYear'];
		$lunar_date->year = $list['body']['items']['item'][$_day]['lunYear'];
		$lunar_date->month = $list['body']['items']['item'][$_day]['lunMonth'];
		$lunar_date->day = $list['body']['items']['item'][$_day]['lunDay'];
		$lunar_date->is_leap = ($list['body']['items']['item'][$_day]['lunLeapmonth'] == '윤') ? 1 : 0;

		return $lunar_date;
	}

	public static function getSolarFromLunar($date)
	{
		$year = substr($date, 0, 4);
		$month = substr($date, 4, 2);
		$day = substr($date, 6, 2);
		$_day = (int)$day - 1;

		$api_info = self::getAPIInfo(true);
		if ( empty($api_info) )
		{
			include_once __DIR__ . '/lib/lib.calendar.php';
			include_once __DIR__ . '/lib/lib.lunarday.php';

			$_result = lunar::tosolar($year, $month, $day);
			$solar_date = new stdClass();
			$solar_date->year = $_result[2];
			$solar_date->month = sprintf('%02d', $_result[3]);
			$solar_date->day = sprintf('%02d', $_result[4]);

			return $solar_date->year . $solar_date->month . $solar_date->day;
		}

		// get solar_date
		$xml_file = sprintf(RX_BASEDIR . "files/cache/schedule/%d-solarday.xml", $year.$month);

		if ( file_exists($xml_file) )
		{
			$content = FileHandler::readFile($xml_file);
		}
		else
		{
			$path = $content = '';
			$xml = $json = $list = array();

			$path = $api_info['url'].'getSolCalInfo?serviceKey='.$api_info['key'].'&lunYear='.$year.'&lunMonth='.$month.'&numOfRows=30';
			$content = file_get_contents($path);

			FileHandler::writeFile($xml_file, $content);
		}

		$xml = simplexml_load_string($content, null, LIBXML_NOCDATA);
		$json = json_encode($xml);
		$list = json_decode($json, TRUE);

		$solar_date = new stdClass();
		$solar_date->year = $list['body']['items']['item'][$_day]['solYear'];
		$solar_date->month = $list['body']['items']['item'][$_day]['solMonth'];
		$solar_date->day = $list['body']['items']['item'][$_day]['solDay'];

		return $solar_date->year . $solar_date->month . $solar_date->day;
	}

}
/* End of file */
