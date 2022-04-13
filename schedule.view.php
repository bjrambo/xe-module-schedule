<?php
class scheduleView extends schedule
{
	function init()
	{
		if ( !$this->grant->access )
		{
			throw new Rhymix\Framework\Exception('msg_not_permitted_to_access');
		}

		/**
		 * check the private function, if the user is admin then swich off private function
		 * if the user is not logged, then disppear schedule insert/view
		 **/
		if ( $this->module_info->use_private == 'Y' && !$this->grant->manager && !$this->grant->private )
		{
			$this->private = TRUE;
			if (!\Rhymix\Framework\Session::getMemberSrl())
			{
				$this->grant->list = FALSE;
				$this->grant->view = FALSE;
				$this->grant->standby = FALSE;
				$this->grant->write = FALSE;
				$this->grant->comment = FALSE;
			}
		}
		else
		{
			$this->private = FALSE;
		}

		/**
		 * Mobile Check
		 **/
		$this->is_mobile = Mobile::isMobileCheckByAgent();
		$this->is_mobile_enabled = Mobile::isMobileEnabled();
		Context::set('is_mobile', $this->is_mobile);
		Context::set('is_mobile_enabled', $this->is_mobile_enabled);

		/**
		 * load javascript, JS filters
		 **/
		Context::addJsFilter($this->module_path.'tpl/filter', 'input_password.xml');
		Context::addJsFile($this->module_path.'tpl/js/schedule.js');
	}

	function dispScheduleContent()
	{
		$schedule_srl = Context::get('schedule_srl');
		if ( !$schedule_srl )
		{
			$list_style = Context::get('list_style');
			if ( !$list_style || $list_style != 'list' )
			{
				$this->dispScheduleContentCalendar();
			}
			else
			{
				$this->dispScheduleContentList();
			}
		}
		else
		{
			$this->dispScheduleContentView();
		}
	}

	function dispScheduleContentCalendar()
	{
		$module_info = $this->module_info;

		$selected_month = Context::get('selected_month');
		if ( $selected_month && strlen(preg_replace('/[^0-9]/', '', $selected_month)) != 6 )
		{
			throw new Rhymix\Framework\Exception('msg_invalid_request');
		}
		if ( !$selected_month )
		{
			$selected_month = date('Ym');
		}
		$category_srl = Context::get('category') ? : 0;

		$index = new scheduleItem();
		$category_list = $index->getCategoryList($module_info->module_srl);
		
		Context::set('index', $index);
		Context::set('category_list', $category_list);

		// 하위 카테고리가 있을 경우 스케줄 수집시 하위 카테고리의 스케줄도 가져옴
		if ( $category_srl && $category_list[$category_srl]->child_count )
		{
			$category_srls = $category_list[$category_srl]->childs;
			array_unshift($category_srls, $category_srl);
			$category_srl = $category_srls;
		}

		$year = substr($selected_month, 0, 4);
		$month = substr($selected_month, 4, 2);
		
		Context::set('year', $year);
		Context::set('month', $month);

		$date_info = new stdClass();
		$date_info->day_max = date('t', mktime(0, 0, 0, $month, 1, $year));
		$date_info->week_start = date('w', mktime(0, 0, 0, $month, 1, $year));
		$date_info->weeks = ceil((intval($date_info->day_max) + intval($date_info->week_start)) / 7);
		Context::set('date_info', $date_info);

		// if the private function is enabled,  the get the logged user information
		$member_srl = null;
		if ( $this->private )
		{
			$logged_info = Context::get('logged_info');
			$member_srl = $logged_info->member_srl;
		}

		// 정렬 순서 확인!!
		$info_list = array('holiday', 'sundry', 'custom');
		Context::set('info_list', $info_list);

		if ( $module_info->use_lunarday == 'Y' )
		{
			$lunarday_list = scheduleModel::getLunardayList($year, $month);
			Context::set('lunarday_list', $lunarday_list);
		}
		if ( $module_info->use_divisions == 'Y' )
		{
			$divisions_list = scheduleModel::getDivisionsList($year, $month);
			Context::set('divisions_list', $divisions_list);
		}

		$specialday_list = scheduleModel::getSpecialdayList($year, $month, $module_info->use_holiday, $module_info->use_sundry, $module_info->custom_day);
		Context::set('specialday_list', $specialday_list);

		if ( $this->grant->list )
		{
			$schedule_list = scheduleModel::getSchedulesBySelectedDate($year.$month, $module_info->module_srl, $category_srl, $member_srl);
		}
		else
		{
			$schedule_list = array();
		}
		Context::set('schedule_list', $schedule_list);

		$this->setTemplateFile('index');
	}

	function dispScheduleContentList()
	{
		$module_info = $this->module_info;
		if ( $module_info->use_list != 'Y' )
		{
			throw new Rhymix\Framework\Exception('msg_not_permitted_to_read');
		}

		// list config setting
		$list_config = scheduleModel::getListConfig($module_info->module_srl);
		if ( !$list_config )
		{
			$list_config = scheduleModel::getDefaultListConfig($module_info->module_srl);
		}
		Context::set('list_config', $list_config);

		Context::set('status_list', scheduleModel::getScheduleStatusList());

		// set a search option used in the template
		$search_option = array();
		if ( count($this->search_option) )
		{
			foreach ( $this->search_option as $opt )
			{
				$search_option[$opt] = lang($opt);
			}

			// remove a search option that is not public in member config
			$memberConfig = ModuleModel::getModuleConfig('member');
			foreach ( $memberConfig->signupForm as $signupFormElement )
			{
				if ( in_array($signupFormElement->title, $search_option) )
				{
					if ( $signupFormElement->isPublic == 'N' )
					{
						unset($search_option[$signupFormElement->name]);
					}
				}
			}
		}
		Context::set('search_option', $search_option);

		$index = new scheduleItem();
		Context::set('index', $index);

		// option to get a list
		$args = new stdClass();
		$args->module_srl = $module_info->module_srl;
		$args->list_count = $this->is_mobile ? $module_info->mobile_list_count : $module_info->list_count;
		$args->page_count = $this->is_mobile ? $module_info->mobile_page_count : $module_info->page_count;
		$args->status = Context::get('status');
		$args->page = Context::get('page');

		// get the search target and keyword
		if ( $this->grant->view )
		{
			$args->search_target = Context::get('search_target');
			$args->search_keyword = Context::get('search_keyword');
		}

		// if the category is enabled, then get the category
		if ( $module_info->hide_category != 'Y' )
		{
			$args->category_srl = $category_srl = Context::get('category');

			$category_list = $index->getCategoryList($module_info->module_srl);
			Context::set('category_list', $category_list);
		}

		// setup the sort index and order index
		$args->sort_index = Context::get('sort_index');
		$args->order_type = Context::get('order_type');
		if ( !in_array($args->sort_index, $this->order_target) )
		{
			if ( $args->sort_index == 'no' )
			{
				$args->sort_index = 'schedule_srl';
			}
			else
			{
				$args->sort_index = $module_info->order_target ? : 'list_order';
			}
		}
		if ( !in_array($args->order_type, array('asc', 'desc')) )
		{
			$args->order_type = $module_info->order_type ? : 'asc';
		}
		Context::set('sort_index', $args->sort_index);
		Context::set('order_type', $args->order_type);
		Context::set('order_target', $this->order_target);

		// setup the list count to be serach list count, if the category or search keyword has been set
		if ( $args->category_srl ?? null || $args->search_keyword ?? null )
		{
			$args->list_count = $this->is_mobile ? $module_info->mobile_search_list_count : $module_info->search_list_count;
		}

		// check the grant
		if ( $this->grant->list )
		{
			if ( $module_info->standby_display == 'HIDE' && !$this->grant->manager )
			{
				$args->status = 'PUBLIC';
			}

			// if the private function is enabled,  the get the logged user information
			if ( $this->private )
			{
				$args->member_srl = Context::get('logged_info')->member_srl;
			}

			// get a list
			$columnList = array('schedule_srl', 'module_srl', 'category_srl', 'title', 'title_color', 'content', 'regdate', 'status', 'uploaded_count', 'start_date', 'end_date', 'selected_date', 'is_allday', 'place', 'is_recurrence', 'nick_name', 'member_srl', 'email_address');
			$output = scheduleModel::getScheduleList($args, $columnList);

			// Set values of schedule_model::getScheduleList() objects for a template
			Context::set('schedule_list', $output->data);
			Context::set('total_count', $output->total_count);
			Context::set('total_page', $output->total_page);
			Context::set('page', $output->page);
			Context::set('page_navigation', $output->page_navigation);
		}
		else
		{
			Context::set('schedule_list', array());
			Context::set('total_count', 0);
			Context::set('total_page', 1);
			Context::set('page', 1);
			Context::set('page_navigation', new PageHandler(0, 0, 1, 10));
		}

		Context::addJsFilter($this->module_path.'tpl/filter', 'search.xml');

		$oSecurity = new Security();
		$oSecurity->encodeHTML('search_option.');

		$this->setTemplateFile('list');
	}

	function dispScheduleContentView()
	{
		if ( !$this->grant->view )
		{
			throw new Rhymix\Framework\Exception('msg_not_permitted_to_view_content');
		}

		$schedule_srl = Context::get('schedule_srl');
		if ( !$schedule_srl )
		{
			throw new Rhymix\Framework\Exception('msg_not_founded');
		}

		$oSchedule = scheduleModel::getSchedule($schedule_srl);

		if ( $oSchedule->module_srl != $this->module_info->module_srl )
		{
			throw new Rhymix\Framework\Exception('msg_not_founded');
		}

		if ( $oSchedule->getDisplay() == 'HIDE' )
		{
			throw new Rhymix\Framework\Exception('msg_not_permitted_to_view_content');
		}

		// add the schedule title to the browser
		$seo_title = config('seo.document_title') ? config('seo.document_title') : '$SITE_TITLE - $DOCUMENT_TITLE';

		Context::setCanonicalURL(getFullUrl('', 'mid', $this->module_info->mid, 'schedule_srl', $oSchedule->schedule_srl));
		Context::setBrowserTitle($seo_title, array(
			'site_title' => Context::getSiteTitle(),
			'site_subtitle' => Context::getSiteSubtitle(),
			'subpage_title' => $this->module_info->browser_title,
			'document_title' => $oSchedule->get('title'),
		));

		// Add OpenGraph and Twitter metadata
		if ( config('seo.og_enabled') )
		{
			$oSchedule->_addOpenGraphMetadata();
			if ( config('seo.twitter_enabled') )
			{
				$oSchedule->_addTwitterMetadata();
			}
		}

		// if the private function is enabled
		if ( $this->private )
		{
			if ( abs($oSchedule->get('member_srl')) != $this->user->member_srl && !$this->grant->manager )
			{
				$oSchedule = scheduleModel::getSchedule(0);
			}
		}

		Context::set('is_public', $oSchedule->getDisplay() == 'SHOW');
		Context::set('oSchedule', $oSchedule);
		$this->setTemplateFile('schedule');
	}

	function dispScheduleInsert()
	{
		// check grant for the module
		if ( !$this->grant->write )
		{
			throw new Rhymix\Framework\Exception('msg_not_permitted_to_write');
		}
		if ( $this->module_info->module != 'schedule' )
		{
			throw new Rhymix\Framework\Exception('msg_invalid_request');
		}

		$schedule_srl = Context::get('schedule_srl');
		$selected_month = Context::get('selected_month');
		$selected_day = Context::get('selected_day');

		// check grant for the schedule and get variables
		if ( $schedule_srl )
		{
			$oSchedule = scheduleModel::getSchedule($schedule_srl);

			// if the schedule is not granted, then back to the password input form
			if ( !$oSchedule->isGranted() )
			{
				if ( !$oSchedule->member_srl && !Context::get('is_logged') )
				{
					return $this->setTemplateFile('input_password');
				}
				else
				{
					throw new Rhymix\Framework\Exception('msg_not_permitted_to_write');
				}
			}

			// get the recurrence variables
			if ( $oSchedule->get('is_recurrence') == 'Y' )
			{
				$recur_info = $oSchedule->getRecurInfo();
				Context::set('recur_info', $recur_info);
			}
		}

		/**
		 * get Language
		 **/
		$recur_type = lang('recur_type');
		Context::set('recur_type', $recur_type);

		$regular_type = lang('regular_type');
		Context::set('regular_type', $regular_type);

		$week_order = lang('week_order');
		Context::set('week_order', $week_order);

		$weekdays = lang('weekdays');
		Context::set('weekdays', $weekdays);

		$units = lang('recur_units');
		$recur_unit = array(
			'day' => array(
				'max_length' => 365,
				'unit_name' => $units->day
			),
			'week' => array(
				'max_length' => 52,
				'unit_name' => $units->week
			),
			'month' => array(
				'max_length' => 12,
				'unit_name' => $units->month
			),
			'year' => array(
				'max_length' => 4,
				'unit_name' => $units->year
			)
		);
		Context::set('recur_unit', $recur_unit);

		$exception_type = lang('exception_type');
		Context::set('exception_type', $exception_type);

		$exception_option = lang('exception_option');
		Context::set('exception_option', $exception_option);

		/**
		 * check if the category option is enabled not not
		 **/
		if ( $this->module_info->hide_category !='Y' )
		{
			// get the user group information
			if ( Context::get('is_logged') )
			{
				$group_srls = array_keys($this->user->group_list);
			}
			else
			{
				$group_srls = array();
			}
			$group_srls_count = count($group_srls);

			// check the grant after obtained the category list
			$category_list = array();
			$normal_category_list = DocumentModel::getCategoryList($this->module_srl);
			if ( count($normal_category_list) )
			{
				foreach ( $normal_category_list as $category_srl => $category )
				{
					$is_granted = TRUE;
					if ( $category->group_srls )
					{
						$category_group_srls = explode(',', $category->group_srls);
						$is_granted = FALSE;
						if ( count(array_intersect($group_srls, $category_group_srls)) ) $is_granted = TRUE;

					}
					if ( $is_granted ) $category_list[$category_srl] = $category;
				}
			}

			// check if at least one category is granted
			$grant_exists = false;
			foreach ( $category_list as $category )
			{
				if ( $category->grant )
				{
					$grant_exists = true;
				}
			}
			if ( $grant_exists )
			{
				Context::set('category_list', $category_list);
			}
			else
			{
				$this->module_info->hide_category = 'Y';
				Context::set('category_list', array());
			}
		}

		/**
		 * set date and time information
		 **/
		if ( $schedule_srl )
		{
			Context::set('start_date', $oSchedule->get('start_date'));
			Context::set('start_time', $oSchedule->get('start_time'));
			Context::set('end_date', $oSchedule->get('end_date'));
			Context::set('end_time', $oSchedule->get('end_time'));
		}
		else
		{
			if ( $selected_month.$selected_day != date('Ymd') )
			{
				Context::set('start_date', $selected_month.$selected_day);
				Context::set('start_time', '0000');
				Context::set('end_date', $selected_month.$selected_day);
				Context::set('end_time', '2300');
			}
			else
			{
				Context::set('start_date', date('Ymd'));
				Context::set('start_time', date('Hi'));
				Context::set('end_date', date('Ymd'));
				Context::set('end_time', '2300');
			}
			$oSchedule = new scheduleItem();
		}

		Context::set('status_list', $oSchedule->getStatusList());
		Context::set('oSchedule', $oSchedule);

		if ( $this->module_info->use_captcha ==='Y' )
		{
			$spamfilter_config = ModuleModel::getModuleConfig('spamfilter');
			if (
				isset($spamfilter_config) && isset($spamfilter_config->captcha)
				&& $spamfilter_config->captcha->type === 'recaptcha'
				&& $spamfilter_config->captcha->target_actions['document']
				&& $logged_info->is_admin !== 'Y'
				&& ( $spamfilter_config->captcha->target_users === 'everyone' || !$this->user->member_srl )
				&& ( $spamfilter_config->captcha->target_frequency === 'every_time' || !isset($_SESSION['recaptcha_authenticated']) || !$_SESSION['recaptcha_authenticated'] )
				&& ( $spamfilter_config->captcha->target_devices[Mobile::isFromMobilePhone() ? 'mobile' : 'pc'] )
			)
			{
				include_once RX_BASEDIR . 'modules/spamfilter/spamfilter.lib.php';
				spamfilter_reCAPTCHA::init($spamfilter_config->captcha);
				Context::set('captcha', new spamfilter_reCAPTCHA());
			}
		}

		/**
		 * add JS filters
		 **/
		if ( Context::get('logged_info')->is_admin == 'Y' || $this->module_info->allow_no_category == 'Y' )
		{
			Context::addJsFilter($this->module_path.'tpl/filter', 'insert_admin.xml');
		}
		else
		{
			Context::addJsFilter($this->module_path.'tpl/filter', 'insert.xml');
		}

		$this->setTemplateFile('insert');
	}

	function dispScheduleDelete()
	{
		if ( !$this->grant->view )
		{
			throw new Rhymix\Framework\Exception('msg_not_permitted_to_view_content');
		}
		if ( !$this->grant->write )
		{
			throw new Rhymix\Framework\Exception('msg_not_permitted_to_delete');
		}

		$schedule_srl = Context::get('schedule_srl');
		$oSchedule = scheduleModel::getSchedule($schedule_srl);

		// if the schedule is not existed, then back to the scheduler content page
		if ( !$oSchedule->isExists() )
		{
			//TODO check again
			return $this->dispScheduleContentView();
		}

		// if the schedule is not granted, then back to the password input form
		if ( !$oSchedule->isGranted() )
		{
			if ( !$oSchedule->member_srl && !Context::get('is_logged') )
			{
				return $this->setTemplateFile('input_password');
			}
			else
			{
				throw new Rhymix\Framework\Exception('msg_not_permitted_to_delete');
			}
		}

		Context::set('oSchedule', $oSchedule);
		$this->setTemplateFile('delete');
	}
}
