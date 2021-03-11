<?php
class scheduleController extends schedule
{
	function init()
	{
	}

	function procScheduleInsertSchedule()
	{
		// check grant
		if ( $this->module_info->module != 'schedule' )
		{
			throw new Rhymix\Framework\Exception('msg_invalid_request');
		}
		if ( !$this->grant->write )
		{
			throw new Rhymix\Framework\Exception('msg_not_permitted_to_write');
		}

		$schedule_srl = Context::get('schedule_srl');
		$logged_info = Context::get('logged_info');

		$obj = Context::getRequestVars();
		$obj->module_srl = $this->module_info->module_srl;
		$obj->content = utf8_clean($obj->content);

		if ( $this->module_info->use_captcha ==='Y' )
		{
			$spamfilter_config = ModuleModel::getModuleConfig('spamfilter');
			if (
				isset($spamfilter_config) && isset($spamfilter_config->captcha)
				&& $spamfilter_config->captcha->type === 'recaptcha'
				&& $spamfilter_config->captcha->target_actions['document']
				&& $logged_info->is_admin !== 'Y'
				&& ( $spamfilter_config->captcha->target_users === 'everyone' || !$logged_info->member_srl )
				&& ( $spamfilter_config->captcha->target_frequency === 'every_time' || !isset($_SESSION['recaptcha_authenticated']) || !$_SESSION['recaptcha_authenticated'] )
				&& ( $spamfilter_config->captcha->target_devices[Mobile::isFromMobilePhone() ? 'mobile' : 'pc'] )
			)
			{
				include_once RX_BASEDIR . 'modules/spamfilter/spamfilter.lib.php';
				spamfilter_reCAPTCHA::init($spamfilter_config->captcha);
				spamfilter_reCAPTCHA::check();
			}
		}

		$args = new stdClass();
		$args->module_srl = $obj->module_srl;
		$args->category_srl = $obj->category_srl;

		$args->title = utf8_clean($obj->title);
		$args->title_color = ($obj->title_color == 'transparent') ? '' : $obj->title_color;
		$args->content = $obj->content;
		$args->status = $obj->status;

		$args->start_date = preg_replace('/[^0-9]*/s', '', $obj->start_date);
		$args->start_time = ($obj->is_allday != 'Y') ? preg_replace('/[^0-9]*/s', '', $obj->start_time) : '0000';
		$args->end_date = preg_replace('/[^0-9]*/s', '', $obj->end_date);
		$args->end_time = ($obj->is_allday != 'Y') ? preg_replace('/[^0-9]*/s', '', $obj->end_time) : '2359';
		$args->is_allday = ($obj->is_allday != 'Y') ? '' : 'Y';
		$args->place = $obj->place ? utf8_clean($obj->place) : '';
		$args->is_recurrence = ($obj->is_recurrence != 'Y') ? '' : 'Y';

		if ( $args->is_recurrence == 'Y'  )
		{
			$week_order = !is_array($obj->week_order) ? $obj->week_order : implode('|@|', $obj->week_order);
			$weekdays = !is_array($obj->weekdays) ? $obj->weekdays : implode('|@|', $obj->weekdays);
			$exception_type = !is_array($obj->exception_type) ? $obj->exception_type : implode('|@|', $obj->exception_type);
			$recur_option = array(
				'recur_type' => $obj->recur_type ? $obj->recur_type : 'regular',
				'recur_regular' => $obj->recur_regular ? $obj->recur_regular : '',
				'week_order' => $week_order ? : '',
				'weekdays' => $weekdays ? : '',
				'recur_cycle' => ((int)$obj->recur_cycle > 0) ? $obj->recur_cycle : 1,
				'recur_unit' => $obj->recur_unit ? $obj->recur_unit : 'day',
				'recur_freq' => ((int)$obj->recur_freq > 0) ? $obj->recur_freq : 0,
				'stop_date' => $obj->stop_date ? preg_replace('/[^0-9]*/s', '', $obj->stop_date) : '',
				'exception_type' => $exception_type ? : null,
				'exception_option' => ($obj->exception_type && $obj->exception_option) ? $obj->exception_option : 'skip',
			);
			if ( !$recur_option['recur_freq'] && !$recur_option['stop_date'] )
			{
				$recur_option['recur_freq'] = 1;
			}
			$args->selected_date = $this->transSelectedDate($args->start_date, $args->end_date, $recur_option);
		}
		else
		{
			$args->selected_date = $this->transSelectedDate($args->start_date, $args->end_date);
		}

		$oDB = DB::getInstance();
		$oDB->begin();

		$oSchedule = ScheduleModel::getSchedule($schedule_srl);
		if ( !$oSchedule )
		{
			$args->schedule_srl = $schedule_srl ? : getNextSequence();
			$args->list_order = $args->schedule_srl * -1;
			$args->regdate = date('YmdHis');
			if ( $logged_info )
			{
				$args->nick_name = htmlspecialchars_decode($logged_info->nick_name);
				$args->member_srl = $logged_info->member_srl;
				$args->email_address = $logged_info->email_address;
			}
			else
			{
				$args->nick_name = htmlspecialchars_decode($obj->nick_name);
				$args->member_srl = 0;
				$args->email_address = $obj->email_address;
				if ( $obj->password && !$obj->password_is_hashed )
				{
					$oMemberModel = getModel('member');
					$args->password = $oMemberModel->hashPassword($obj->password);
				}
			}

			$output = executeQuery('schedule.insertSchedule', $args);
			if ( $args->is_recurrence == 'Y' )
			{
				$this->insertScheduleRecur($args->schedule_srl, $recur_option);
			}

			if ( !$output->toBool() )
			{
				$oDB->rollback();
				return $output;
			}

			$_SESSION['granted_schedule'][$args->schedule_srl] = true;

			// send an email to admin user
			if ( $this->module_info->admin_mail && config('mail.default_from') )
			{
				$browser_title = Context::replaceUserLang($this->module_info->browser_title);
				$mail_title = sprintf(lang('msg_schedule_notify_mail'), $browser_title, cut_str($args->title, 20, '...'));
				$mail_content = sprintf("From : <a href=\"%s\">%s</a>", getFullUrl('', 'mid', $this->module_info->mid, 'schedule_srl', $args->schedule_srl), $args->title);
				
				$oMail = new \Rhymix\Framework\Mail();
				$oMail->setSubject($mail_title);
				$oMail->setBody($mail_content);
				foreach ( array_map('trim', explode(',', $this->module_info->admin_mail)) as $email_address )
				{
					if ( $email_address )
					{
						$oMail->addTo($email_address);
					}
				}
				$oMail->send();
			}
		}
		else
		{
			if( !$oSchedule->isGranted() )
			{
				throw new Rhymix\Framework\Exception('msg_not_permitted_to_write');
			}

			// Preserve module_srl if the document belongs to a module that is included in this board
			if ( $oSchedule->module_srl != $args->module_srl )
			{
				$args->module_srl = $oSchedule->module_srl;
				$args->category_srl = $oSchedule->category_srl;
			}

			$args->schedule_srl = $oSchedule->get('schedule_srl');
			$args->list_order = $args->schedule_srl * -1;
			$args->regdate = $oSchedule->get('regdate');
			if ( $this->grant->manager )
			{
				$args->nick_name = $oSchedule->get('nick_name');
				$args->member_srl = $oSchedule->member_srl;
				$args->email_address = $oSchedule->get('email_address');
			}
			else
			{
				if ( $logged_info )
				{
					$args->nick_name = htmlspecialchars_decode($logged_info->nick_name);
					$args->member_srl = $logged_info->member_srl;
					$args->email_address = $logged_info->email_address;
				}
				else
				{
					$args->nick_name = htmlspecialchars_decode($obj->nick_name);
					$args->member_srl = 0;
					$args->email_address = $obj->email_address;
					if ( $obj->password && !$obj->password_is_hashed )
					{
						$oMemberModel = getModel('member');
						$args->password = $oMemberModel->hashPassword($obj->password);
					}
				}
			}

			$output = executeQuery('schedule.updateSchedule', $args);
			$this->deleteScheduleRecur($args->schedule_srl);
			if ( $args->is_recurrence == 'Y' )
			{
				$this->insertScheduleRecur($args->schedule_srl, $recur_option);
			}

			if ( !$output->toBool() )
			{
				$oDB->rollback();
				return $output;
			}

			// Remove the thumbnail file
			FileHandler::removeDir(sprintf('files/thumbnails/%s',getNumberingPath($args->schedule_srl, 3)));

			self::clearScheduleCache($args->schedule_srl);
		}

		$this->deleteSavedDoc();
		$this->AttachFiles($args->schedule_srl);
		$this->updateUploadedCount($args->schedule_srl);

		// commit
		$oDB->commit();

		if ( $obj->list_style )
		{
			$redirectUrl = getNotEncodedUrl('', 'mid', $obj->mid, 'list_style', $obj->list_style, 'schedule_srl', $args->schedule_srl);
		}
		else
		{
			$redirectUrl = getNotEncodedUrl('', 'mid', $obj->mid, 'selected_month', substr($args->start_date, 0, 6), 'schedule_srl', $args->schedule_srl);
		}
		$this->add('redirect_url', $redirectUrl);
		$this->add('mid', $obj->mid);
		$this->add('schedule_srl', $args->schedule_srl);
		$this->add('category_srl', $args->category_srl);

		if ( !in_array(Context::getRequestMethod(), array('XMLRPC', 'JSON')) )
		{
			$this->setRedirectUrl($redirectUrl);
		}
	}

	function deleteSavedDoc()
	{
		$args = new stdClass();
		if ( Context::get('is_logged') )
		{
			$logged_info = Context::get('logged_info');
			$args->member_srl = $logged_info->member_srl;
		}
		else
		{
			$args->ipaddress = $_SERVER['REMOTE_ADDR'];
		}
		$args->module_srl = Context::get('module_srl');
		if ( !$args->module_srl )
		{
			$current_module_info = Context::get('current_module_info');
			$args->module_srl = $current_module_info->module_srl;
		}

		// Check if the auto-saved document already exists
		$output = executeQuery('editor.getSavedDocument', $args);
		$saved_doc = $output->data;
		if ( !$saved_doc )
		{
			return;
		}

		$oSaved = ScheduleModel::getSchedule($saved_doc->document_srl);
		return executeQuery('editor.deleteSavedDoc', $args);
	}

	function clearScheduleCache($schedule_srl)
	{
		Rhymix\Framework\Cache::delete('schedule_item:recur_info' . getNumberingPath($schedule_srl) . $schedule_srl);
		Rhymix\Framework\Cache::delete('schedule_item:basic_info' . getNumberingPath($schedule_srl) . $schedule_srl);
		Rhymix\Framework\Cache::delete('seo:document_images:' . $schedule_srl);
		unset($GLOBALS['RX_SCHEDULE_LIST'][$schedule_srl]);
	}

	function updateUploadedCount($schedule_srl_list)
	{
		if ( !is_array($schedule_srl_list) )
		{
			$schedule_srl_list = array($schedule_srl_list);
		}
		
		if ( empty($schedule_srl_list) )
		{
			return;
		}
		
		$schedule_srl_list = array_unique($schedule_srl_list);
		
		foreach ( $schedule_srl_list as $schedule_srl )
		{
			if ( !$schedule_srl = (int) $schedule_srl )
			{
				continue;
			}
			
			$args = new stdClass;
			$args->schedule_srl = $schedule_srl;
			$args->uploaded_count = FileModel::getFilesCount($schedule_srl);
			executeQuery('schedule.updateUploadedCount', $args);
		}
	}

	function transSelectedDate($start_date, $end_date, $recur_option = array(), $output_format = 'Ymd')
	{
		$dates = array();
		$candidates = array();

		$start = strtotime(substr($start_date, 0, 8));
		$end = strtotime(substr($end_date, 0, 8));

		if ( empty($recur_option) )
		{
			$recur_freq = 1;
			$step = '+1 day';
		}
		else
		{
			$recur_type = $recur_option['recur_type'];
			$recur_regular = $recur_option['recur_regular'];
			$week_order = $recur_option['week_order'];
			$weekdays = $recur_option['weekdays'];

			if ( $recur_type == 'regular' )
			{
				$recur_cycle = 1;
				$recur_unit = ( $recur_regular == 'daily' ) ? 'day' : substr($recur_regular, 0, -2);
			}
			else
			{
				$recur_cycle = $recur_option['recur_cycle'];
				$recur_unit = $recur_option['recur_unit'];
			}

			$recur_freq = $recur_option['recur_freq'];
			$stop_date = $recur_option['stop_date'];
			$exception_type = $recur_option['exception_type'];
			$exception_option = $recur_option['exception_option'];

			$step = '+' . $recur_cycle . ' ' . $recur_unit;
			$stop = strtotime(substr($stop_date, 0, 8));
		}

		// 시작일이 종료일보다 뒤면 빈 배열 반환
		if ( $start > $end )
		{
			return $dates;
		}
		// 시작일이 종료일보다 앞이면 시작일부터 종료일까지를 후보 배열로 담음
		else if ( $start < $end )
		{
			while( $start <= $end )
			{
				$candidates[] = $start;
				$start = strtotime('+1 day', $start);
			}
		}
		// 시작일과 종료일이 같으면 시작일만 후보 배열에 담음
		else
		{
			$candidates[] = $start;
		}

		/**************************************************/
		/* 후보 일자를 반복 횟수만큼 반복해서 배열로 담되, 예외/중지일은 넘기지 않음 */
		/**************************************************/

		$types = is_array($exception_type) ? $exception_type : explode('|@|', $exception_type);
		$has_holiday = in_array('holiday', $types);
		$has_saturday = in_array('saturday', $types);
		$has_sunday = in_array('sunday', $types);

		// 요일 설정의 경우
		if ( $recur_type == 'weekday' )
		{
			// 지정된 요일 설정을 가져오고 정렬함
			$orders = explode('|@|', $week_order);
			$week_names = explode('|@|', $weekdays);
			$week_step = [];
			foreach ( $orders as $key => $order )
			{
				foreach ( $week_names as $k => $week_name )
				{
					$week_step[] = $order . ' ' . $week_name;
				}
			}
			// 월간 요일 설정의 갯수를 구함
			$c = count($week_step);

			$new_month = false;
			foreach ( $candidates as $candidate )
			{
				// 반복 횟수가 지정되어 있으면
				if ( $recur_freq )
				{
					for ( $i = 0; $i < $recur_freq; $i++ )
					{
						// 1회차이거나 새로운 달일 때 요일 설정을 월별 날짜 순으로 재정렬 : 월마다 설정된 요일의 순서가 다를 수 있기 때문
						if ( $i == 0 || $new_month )
						{
							$selected_weekdays = [];
							foreach ( $week_step as $_weekday )
							{
								if ( !$new_month )
								{
									$_weekday_format = strtotime($_weekday . ' of this month', $candidate);
								}
								else
								{
									$_weekday_format = strtotime($_weekday . ' of +1 month', $candidate);
								}
								$selected_weekdays[date('Ymd', $_weekday_format)] = $_weekday;
							}
							// 날짜 순으로 요일 목록을 재정렬
							ksort($selected_weekdays);
							// 해당 월의 1회차 요일 설정이 적용됐으므로, $new_month 값은 false로 반환
							$new_month = false;
						}

						// 반복 횟수를 요일 설정 갯수로 나누고 나머지값을 구함 => 0으로 시작하는 월별 키값 배당
						$key = $i % $c;
						// 키값에 따라 해당 월의 요일 설정을 가져옴
						$_week_step = array_values($selected_weekdays)[$key];

						// 전체 1회차이거나 해당 월의 2회차 이상인 경우
						if ( $i == 0 || $key != 0 )
						{
							$candidate = strtotime($_week_step . ' of this month', $candidate);
							// 해당 월의 마지막 순번이면 $new_month 값을 true로 반환
							if ( $key == $c - 1 )
							{
								$new_month = true;
							}
						}
						// 전체 1회차가 아닌 한에서, 해당 월의 1회차인 경우
						else
						{
							$candidate = strtotime($_week_step . ' of +1 month', $candidate);
						}

						// 현재 일자가 종료일을 지난 경우 break하고 다음 후보일($candidate)로 넘어감
						if ( $stop_date && $candidate > $stop )
						{
							break 1;
						}
						// 현재 일자가 시작일보다 앞선 경우 빈도수 1 증가시키고 continue
						if ( $candidate < $start )
						{
							$recur_freq++;
							continue;
						}

						$date = date($output_format, $candidate);
						if ( in_array($date, $dates) )
						{
							continue;
						}

						// 제외 설정이 있을 경우 가져와서 적용
						if ( $exception_type )
						{
							$is_holiday = ScheduleModel::isHoliday($date);
							if ( $has_holiday && $is_holiday )
							{
								switch ( $exception_option ) {
									case 'prev_week':
										$_step = '-1 week';
										break;
									case 'next_week':
										$_step = '+1 week';
										break;
									default:
										$candidate = strtotime($step, $candidate);
										continue 2;
								}
								$_date = date($output_format, strtotime($_step, $candidate));

								while ( ScheduleModel::isHoliday($_date) )
								{
									$_date = date($output_format, strtotime($_date . ' ' . $_step));
								}

								if ( !in_array($_date, $dates) && (!$stop_date || $_date <= $stop_date) )
								{
									$dates[] = $_date;
								}

								continue;
							}
						}
						$dates[] = $date;
					}
				}
				// 반복 횟수 지정 업이 종료일만 지정되어 있으면
				else
				{
					$i = 0;
					while ( $candidate <= $stop )
					{
						// 1회차이거나 새로운 달일 때 요일 설정을 월별 날짜 순으로 재정렬 : 월마다 설정된 요일의 순서가 다를 수 있기 때문
						if ( $i == 0 || $new_month )
						{
							$selected_weekdays = [];
							foreach ( $week_step as $_weekday )
							{
								if ( !$new_month )
								{
									$_weekday_format = strtotime($_weekday . ' of this month', $candidate);
								}
								else
								{
									$_weekday_format = strtotime($_weekday . ' of +1 month', $candidate);
								}
								$selected_weekdays[date('Ymd', $_weekday_format)] = $_weekday;
							}
							// 날짜 순으로 요일 목록을 재정렬
							ksort($selected_weekdays);
							// 해당 월의 1회차 요일 설정이 적용됐으므로, $new_month 값은 false로 반환
							$new_month = false;
						}

						// 반복 횟수를 요일 설정 갯수로 나누고 나머지값을 구함 => 0으로 시작하는 월별 키값 배당
						$key = $i % $c;
						// 키값에 따라 해당 월의 요일 설정을 가져옴
						$_week_step = array_values($selected_weekdays)[$key];

						// 전체 1회차이거나 해당 월의 2회차 이상인 경우
						if ( $i == 0 || $key != 0 )
						{
							$candidate = strtotime($_week_step . ' of this month', $candidate);
							// 해당 월의 마지막 순번이면 $new_month 값을 true로 반환
							if ( $key == $c - 1 )
							{
								$new_month = true;
							}
						}
						// 전체 1회차가 아닌 한에서, 해당 월의 1회차인 경우
						else
						{
							$candidate = strtotime($_week_step . ' of +1 month', $candidate);
						}

						// 현재 일자가 종료일을 지난 경우 => 연번 증가시키고 + break하고 다음 후보일($candidate)로 넘어감
						if ( $stop_date && $candidate > $stop )
						{
							$i++;
							break 1;
						}
						// 현재 일자가 시작일보다 앞선 경우 continue
						if ( $candidate < $start )
						{
							$i++;
							continue;
						}

						$date = date($output_format, $candidate);
						if ( in_array($date, $dates) )
						{
							$i++;
							continue;
						}

						// 제외 설정이 있을 경우 가져와서 적용
						if ( $exception_type )
						{
							$is_holiday = ScheduleModel::isHoliday($date);
							if ( $has_holiday && $is_holiday )
							{
								switch ( $exception_option ) {
									case 'prev_week':
										$_step = '-1 week';
										break;
									case 'next_week':
										$_step = '+1 week';
										break;
									default:
										$candidate = strtotime($step, $candidate);
										$i++;
										continue 2;
								}
								$_date = date($output_format, strtotime($_step, $candidate));

								while ( ScheduleModel::isHoliday($_date) )
								{
									$_date = date($output_format, strtotime($_date . ' ' . $_step));
								}

								if ( !in_array($_date, $dates) && (!$stop_date || $_date <= $stop_date) )
								{
									$dates[] = $_date;
								}

								$i++;
								continue;
							}
						}
						$i++;
						$dates[] = $date;
					}
				}
			}
		}
		// 음력 설정의 경우
		else if ( $recur_type == 'lunar' )
		{
			foreach ( $candidates as $candidate )
			{
				// 반복 횟수가 지정되어 있으면
				if ( $recur_freq )
				{
					for ( $i = 0; $i < $recur_freq; $i++ )
					{
						$date = date($output_format, $candidate);

						$lunar_date = ScheduleModel::getLunarFromSolar($date);
						$_lunar_date = (int)$lunar_date->year + 1 . $lunar_date->month . $lunar_date->day;
						$next_solar_date = ScheduleModel::getSolarFromLunar($_lunar_date);

						if ( in_array($date, $dates) )
						{
							$candidate = strtotime($next_solar_date);
							continue;
						}
						// 제외 설정이 있을 경우 가져와서 적용
						if ( $exception_type )
						{
							$is_holiday = ScheduleModel::isHoliday($date);
							$is_saturday = ScheduleModel::isSaturday($date);
							$is_sunday = ScheduleModel::isSunday($date);

							if ( ($has_holiday && $is_holiday) || ($has_saturday && $is_saturday) || ($has_sunday && $is_sunday) )
							{
								switch ( $exception_option ) {
									case 'prev_day':
										$_step = '-1 day';
										break;
									case 'next_day':
										$_step = '+1 day';
										break;
									default:
										$candidate = strtotime($next_solar_date);
										continue 2;
								}
								$_date = date($output_format, strtotime($_step, $candidate));
								while ( in_array($_date, $dates) )
								{
									$_date = date($output_format, strtotime($_date . ' ' . $_step));
								}

								if ( $has_holiday && !$has_saturday && !$has_sunday )
								{
									while ( ScheduleModel::isHoliday($_date) )
									{
										$_date = date($output_format, strtotime($_date . ' ' . $_step));
									}
								}
								else if ( $has_holiday && $has_saturday && !$has_sunday )
								{
									while ( ScheduleModel::isHoliday($_date) || ScheduleModel::isSaturday($_date) )
									{
										$_date = date($output_format, strtotime($_date . ' ' . $_step));
									}
								}
								else if ( $has_holiday && !$has_saturday && $has_sunday )
								{
									while ( ScheduleModel::isHoliday($_date) || ScheduleModel::isSunday($_date) )
									{
										$_date = date($output_format, strtotime($_date . ' ' . $_step));
									}
								}
								else if ( !$has_holiday && $has_saturday && !$has_sunday )
								{
									while ( ScheduleModel::isSaturday($_date) )
									{
										$_date = date($output_format, strtotime($_date . ' ' . $_step));
									}
								}
								else if ( !$has_holiday && $has_saturday && $has_sunday )
								{
									while ( ScheduleModel::isSaturday($_date) || ScheduleModel::isSunday($_date) )
									{
										$_date = date($output_format, strtotime($_date . ' ' . $_step));
									}
								}
								else if ( !$has_holiday && !$has_saturday && $has_sunday )
								{
									while ( ScheduleModel::isSunday($_date) )
									{
										$_date = date($output_format, strtotime($_date . ' ' . $_step));
									}
								}
								else if ( $has_holiday && $has_saturday && $has_sunday )
								{
									while ( ScheduleModel::isHoliday($_date) || ScheduleModel::isSaturday($_date) || ScheduleModel::isSunday($_date) )
									{
										$_date = date($output_format, strtotime($_date . ' ' . $_step));
									}
								}
								while ( in_array($_date, $dates) )
								{
									$_date = date($output_format, strtotime($_date . ' ' . $_step));
								}

								if ( !in_array($_date, $dates) && (!$stop_date || $_date <= $stop_date) )
								{
									$dates[] = $_date;
								}

								$candidate = strtotime($next_solar_date);
								continue;
							}
						}

						$dates[] = $date;
						$candidate = strtotime($next_solar_date);

						if ( $stop_date && $candidate > $stop )
						{
							break 1;
						}
					}
				}
				// 반복 횟수 지정 업이 종료일만 지정되어 있으면
				else
				{
					while ( $candidate <= $stop )
					{
						$date = date($output_format, $candidate);

						$lunar_date = ScheduleModel::getLunarFromSolar($date);
						$_lunar_date = (int)$lunar_date->year + 1 . $lunar_date->month . $lunar_date->day;
						$next_solar_date = ScheduleModel::getSolarFromLunar($_lunar_date);

						if ( in_array($date, $dates) )
						{
							$candidate = strtotime($next_solar_date);
							continue;
						}
						// 제외 설정이 있을 경우 가져와서 적용
						if ( $exception_type )
						{
							$is_holiday = ScheduleModel::isHoliday($date);
							$is_saturday = ScheduleModel::isSaturday($date);
							$is_sunday = ScheduleModel::isSunday($date);

							if ( ($has_holiday && $is_holiday) || ($has_saturday && $is_saturday) || ($has_sunday && $is_sunday) )
							{
								switch ( $exception_option ) {
									case 'prev_day':
										$_step = '-1 day';
										break;
									case 'next_day':
										$_step = '+1 day';
										break;
									default:
										$candidate = strtotime($next_solar_date);
										continue 2;
								}
								$_date = date($output_format, strtotime($_step, $candidate));
								while ( in_array($_date, $dates) )
								{
									$_date = date($output_format, strtotime($_date . ' ' . $_step));
								}

								if ( $has_holiday && !$has_saturday && !$has_sunday )
								{
									while ( ScheduleModel::isHoliday($_date) )
									{
										$_date = date($output_format, strtotime($_date . ' ' . $_step));
									}
								}
								else if ( $has_holiday && $has_saturday && !$has_sunday )
								{
									while ( ScheduleModel::isHoliday($_date) || ScheduleModel::isSaturday($_date) )
									{
										$_date = date($output_format, strtotime($_date . ' ' . $_step));
									}
								}
								else if ( $has_holiday && !$has_saturday && $has_sunday )
								{
									while ( ScheduleModel::isHoliday($_date) || ScheduleModel::isSunday($_date) )
									{
										$_date = date($output_format, strtotime($_date . ' ' . $_step));
									}
								}
								else if ( !$has_holiday && $has_saturday && !$has_sunday )
								{
									while ( ScheduleModel::isSaturday($_date) )
									{
										$_date = date($output_format, strtotime($_date . ' ' . $_step));
									}
								}
								else if ( !$has_holiday && $has_saturday && $has_sunday )
								{
									while ( ScheduleModel::isSaturday($_date) || ScheduleModel::isSunday($_date) )
									{
										$_date = date($output_format, strtotime($_date . ' ' . $_step));
									}
								}
								else if ( !$has_holiday && !$has_saturday && $has_sunday )
								{
									while ( ScheduleModel::isSunday($_date) )
									{
										$_date = date($output_format, strtotime($_date . ' ' . $_step));
									}
								}
								else if ( $has_holiday && $has_saturday && $has_sunday )
								{
									while ( ScheduleModel::isHoliday($_date) || ScheduleModel::isSaturday($_date) || ScheduleModel::isSunday($_date) )
									{
										$_date = date($output_format, strtotime($_date . ' ' . $_step));
									}
								}
								while ( in_array($_date, $dates) )
								{
									$_date = date($output_format, strtotime($_date . ' ' . $_step));
								}

								if ( !in_array($_date, $dates) && (!$stop_date || $_date <= $stop_date) )
								{
									$dates[] = $_date;
								}

								$candidate = strtotime($next_solar_date);
								continue;
							}
						}

						$dates[] = $date;
						$candidate = strtotime($next_solar_date);
					}
				}
			}
		}
		// 정기 일정, 직접 설정 등의 경우
		else
		{
			foreach ( $candidates as $candidate )
			{
				// 반복 횟수가 지정되어 있으면
				if ( $recur_freq )
				{
					for ( $i = 0; $i < $recur_freq; $i++ )
					{
						$date = date($output_format, $candidate);
						if ( in_array($date, $dates) )
						{
							$candidate = strtotime($step, $candidate);
							continue;
						}
						// 제외 설정이 있을 경우 가져와서 적용
						if ( $exception_type )
						{
							$is_holiday = ScheduleModel::isHoliday($date);
							$is_saturday = ScheduleModel::isSaturday($date);
							$is_sunday = ScheduleModel::isSunday($date);

							if ( ($has_holiday && $is_holiday) || ($has_saturday && $is_saturday) || ($has_sunday && $is_sunday) )
							{
								switch ( $exception_option ) {
									case 'prev_day':
										$_step = '-1 day';
										break;
									case 'next_day':
										$_step = '+1 day';
										break;
									default:
										$candidate = strtotime($step, $candidate);
										continue 2;
								}
								$_date = date($output_format, strtotime($_step, $candidate));

								while ( in_array($_date, $dates) )
								{
									$_date = date($output_format, strtotime($_date . ' ' . $_step));
								}

								if ( $has_holiday && !$has_saturday && !$has_sunday )
								{
									while ( ScheduleModel::isHoliday($_date) )
									{
										$_date = date($output_format, strtotime($_date . ' ' . $_step));
									}
								}
								else if ( $has_holiday && $has_saturday && !$has_sunday )
								{
									while ( ScheduleModel::isHoliday($_date) || ScheduleModel::isSaturday($_date) )
									{
										$_date = date($output_format, strtotime($_date . ' ' . $_step));
									}
								}
								else if ( $has_holiday && !$has_saturday && $has_sunday )
								{
									while ( ScheduleModel::isHoliday($_date) || ScheduleModel::isSunday($_date) )
									{
										$_date = date($output_format, strtotime($_date . ' ' . $_step));
									}
								}
								else if ( !$has_holiday && $has_saturday && !$has_sunday )
								{
									while ( ScheduleModel::isSaturday($_date) )
									{
										$_date = date($output_format, strtotime($_date . ' ' . $_step));
									}
								}
								else if ( !$has_holiday && $has_saturday && $has_sunday )
								{
									while ( ScheduleModel::isSaturday($_date) || ScheduleModel::isSunday($_date) )
									{
										$_date = date($output_format, strtotime($_date . ' ' . $_step));
									}
								}
								else if ( !$has_holiday && !$has_saturday && $has_sunday )
								{
									while ( ScheduleModel::isSunday($_date) )
									{
										$_date = date($output_format, strtotime($_date . ' ' . $_step));
									}
								}
								else if ( $has_holiday && $has_saturday && $has_sunday )
								{
									while ( ScheduleModel::isHoliday($_date) || ScheduleModel::isSaturday($_date) || ScheduleModel::isSunday($_date) )
									{
										$_date = date($output_format, strtotime($_date . ' ' . $_step));
									}
								}

								while ( in_array($_date, $dates) )
								{
									$_date = date($output_format, strtotime($_date . ' ' . $_step));
								}
								if ( !in_array($_date, $dates) && (!$stop_date || $_date <= $stop_date) )
								{
									$dates[] = $_date;
								}

								$candidate = strtotime($step, $candidate);
								continue;
							}
						}

						$dates[] = $date;
						$candidate = strtotime($step, $candidate);
					}
				}
				// 반복 횟수 지정 업이 종료일만 지정되어 있으면
				else
				{
					while ( $candidate <= $stop )
					{
						$date = date($output_format, $candidate);
						if ( in_array($date, $dates) )
						{
							$candidate = strtotime($step, $candidate);
							continue;
						}
						// 제외 설정이 있을 경우 가져와서 적용
						if ( $exception_type )
						{
							$is_holiday = ScheduleModel::isHoliday($date);
							$is_saturday = ScheduleModel::isSaturday($date);
							$is_sunday = ScheduleModel::isSunday($date);

							if ( ($has_holiday && $is_holiday) || ($has_saturday && $is_saturday) || ($has_sunday && $is_sunday) )
							{
								switch ( $exception_option ) {
									case 'prev_day':
										$_step = '-1 day';
										break;
									case 'next_day':
										$_step = '+1 day';
										break;
									default:
										$candidate = strtotime($step, $candidate);
										continue 2;
								}
								$_date = date($output_format, strtotime($_step, $candidate));

								while ( in_array($_date, $dates) )
								{
									$_date = date($output_format, strtotime($_date . ' ' . $_step));
								}

								if ( $has_holiday && !$has_saturday && !$has_sunday )
								{
									while ( ScheduleModel::isHoliday($_date) )
									{
										$_date = date($output_format, strtotime($_date . ' ' . $_step));
									}
								}
								else if ( $has_holiday && $has_saturday && !$has_sunday )
								{
									while ( ScheduleModel::isHoliday($_date) || ScheduleModel::isSaturday($_date) )
									{
										$_date = date($output_format, strtotime($_date . ' ' . $_step));
									}
								}
								else if ( $has_holiday && !$has_saturday && $has_sunday )
								{
									while ( ScheduleModel::isHoliday($_date) || ScheduleModel::isSunday($_date) )
									{
										$_date = date($output_format, strtotime($_date . ' ' . $_step));
									}
								}
								else if ( !$has_holiday && $has_saturday && !$has_sunday )
								{
									while ( ScheduleModel::isSaturday($_date) )
									{
										$_date = date($output_format, strtotime($_date . ' ' . $_step));
									}
								}
								else if ( !$has_holiday && $has_saturday && $has_sunday )
								{
									while ( ScheduleModel::isSaturday($_date) || ScheduleModel::isSunday($_date) )
									{
										$_date = date($output_format, strtotime($_date . ' ' . $_step));
									}
								}
								else if ( !$has_holiday && !$has_saturday && $has_sunday )
								{
									while ( ScheduleModel::isSunday($_date) )
									{
										$_date = date($output_format, strtotime($_date . ' ' . $_step));
									}
								}
								else if ( $has_holiday && $has_saturday && $has_sunday )
								{
									while ( ScheduleModel::isHoliday($_date) || ScheduleModel::isSaturday($_date) || ScheduleModel::isSunday($_date) )
									{
										$_date = date($output_format, strtotime($_date . ' ' . $_step));
									}
								}

								while ( in_array($_date, $dates) )
								{
									$_date = date($output_format, strtotime($_date . ' ' . $_step));
								}
								if ( !in_array($_date, $dates) && (!$stop_date || $_date <= $stop_date) )
								{
									$dates[] = $_date;
								}

								$candidate = strtotime($step, $candidate);
								continue;
							}
						}

						$dates[] = $date;
						$candidate = strtotime($step, $candidate);

						if ( $stop_date && $candidate > $stop )
						{
							break 1;
						}
					}
				}
			}
		}

		sort($dates);
		return implode(',', $dates);
	}

	// Recurrence Option Insert
	function insertScheduleRecur($schedule_srl, $recur_option = array())
	{
		if ( !$schedule_srl || !$recur_option['recur_cycle'] || !isset($recur_option['recur_unit']) )
		{
			throw new Rhymix\Framework\Exception('msg_invalid_request');
		}

		$args = new stdClass();
		$args->schedule_srl = $schedule_srl;
		foreach ( $recur_option as $key => $val )
		{
			if ( in_array($key, array('recur_cycle', 'recur_freq', 'stop_date')) )
			{
				$val = preg_replace('/[^0-9]*/s', '', $val);
			}
			$args->$key = $val;
		}

		executeQuery('schedule.insertScheduleRecur', $args);
	}

	// Recurrence Option Delete
	function deleteScheduleRecur($schedule_srl)
	{
		if ( !$schedule_srl )
		{
			throw new Rhymix\Framework\Exception('msg_invalid_request');
		}

		$args = new stdClass();
		$args->schedule_srl = $schedule_srl;

		executeQuery('schedule.deleteScheduleRecur', $args);
	}

	/**
	 * A trigger to delete all posts together when the module is deleted
	 * @param object $obj
	 * @return Object
	 */
	function triggerDeleteModuleSchedules(&$obj)
	{
		$module_srl = $obj->module_srl;
		if ( !$module_srl )
		{
			return;
		}

		// Delete the schedules
		$oScheduleAdminController = getAdminController('schedule');
		$output = $oScheduleAdminController->deleteModuleSchedule($module_srl);
		if ( !$output->toBool() )
		{
			return $output;
		}

		// Delete the category
		$oDocumentController = getController('document');
		$output = $oDocumentController->deleteModuleCategory($module_srl);
		if ( !$output->toBool() )
		{
			return $output;
		}
	}

	function deleteSchedule($schedule_srl)
	{
		if ( !$schedule_srl )
		{
			throw new Rhymix\Framework\Exception('msg_invalid_schedule');
		}

		$oDB = DB::getInstance();
		$oDB->begin();

		$oSchedule = ScheduleModel::getSchedule($schedule_srl);
		if ( !$oSchedule->isGranted() )
		{
			throw new Rhymix\Framework\Exception('msg_not_permitted_to_delete');
		}

		$args = new stdClass();
		$args->schedule_srl = $schedule_srl;
		$output = executeQuery('schedule.deleteSchedule', $args);
		if ( !$output->toBool() )
		{
			$oDB->rollback();
			return $output;
		}
		$this->deleteScheduleRecur($schedule_srl);

		$oFileController = getController('file');
		$oFileController->deleteFiles($schedule_srl);

		// Remove the thumbnail file
		Rhymix\Framework\Storage::deleteEmptyDirectory(RX_BASEDIR . sprintf('files/thumbnails/%s', getNumberingPath($schedule_srl, 3)), true);

		// commit
		$oDB->commit();

		//remove from cache
		self::clearScheduleCache($schedule_srl);
		return $output;
	}

	function procScheduleDeleteSchedule()
	{
		// check grant
		if ( $this->module_info->module != 'schedule' )
		{
			throw new Rhymix\Framework\Exception('msg_invalid_request');
		}
		if ( !$this->grant->write )
		{
			throw new Rhymix\Framework\Exception('msg_not_permitted_to_delete');
		}

		$obj = Context::getRequestVars();

		$this->deleteSchedule($obj->schedule_srl);
		$this->setMessage('success_deleted');

		if ( !in_array(Context::getRequestMethod(), array('XMLRPC', 'JSON')) )
		{
			if ( $obj->list_style )
			{
				$redirectUrl = getNotEncodedUrl('', 'mid', $obj->mid, 'category', $obj->category_srl, 'list_style', $obj->list_style, 'schedule_srl', '');
			}
			else
			{
				$redirectUrl = getNotEncodedUrl('', 'mid', $obj->mid, 'category', $obj->category_srl, 'selected_month', $obj->selected_month, 'schedule_srl', '');
			}
			$this->setRedirectUrl($redirectUrl);
		}
	}

	function AttachFiles($schedule_srl)
	{
		if ( !$schedule_srl )
		{
			return new BaseObject();
		}

		$oFileController = getController('file');
		$output = $oFileController->setFilesValid($schedule_srl);
		if ( !$output->toBool() )
		{
			return $output;
		}

		return new BaseObject();
	}

	function procScheduleVerificationPassword()
	{
		// get the id number of the schedule
		$password = Context::get('password');
		$schedule_srl = Context::get('schedule_srl');

		 // get the schedule information
		$oSchedule = ScheduleModel::getSchedule($schedule_srl);
		if ( !$oSchedule->schedule_srl )
		{
			throw new Rhymix\Framework\Exception('msg_not_founded');
		}

		// compare the schedule password and the user input password
		$oMemberModel = getModel('member');
		if ( !$oMemberModel->isValidPassword($oSchedule->get('password'), $password) )
		{
			throw new Rhymix\Framework\Exception('msg_invalid_password');
		}

		$_SESSION['granted_schedule'][$oSchedule->schedule_srl] = true;
	}

	function updateScheduleStatus($schedule_srl, $status = '')
	{
		$args = new stdClass();
		$args->schedule_srl = $schedule_srl;
		$args->status = $status ? : 'STANDBY';

		// begin transaction
		$oDB = &DB::getInstance();
		$oDB->begin();

		$output = executeQuery('schedule.updateScheduleStatus', $args);
		if ( !$output->toBool() )
		{
			$oDB->rollback();
			throw new Rhymix\Framework\Exception('msg_invalid_request');
		}

		// commit
		$oDB->commit();

		return $output;
	}

	function procScheduleStatusUpdate()
	{
		if ( !Context::get('is_logged') )
		{
			throw new Rhymix\Framework\Exception('msg_not_login');
		}
		if ( !$this->grant->write )
		{
			throw new Rhymix\Framework\Exception('msg_not_permitted');
		}

		$schedule_srl = Context::get('target_srl');
		if ( !$schedule_srl )
		{
			throw new Rhymix\Framework\Exception('msg_invalid_request');
		}

		$oSchedule = ScheduleModel::getSchedule($schedule_srl);
		$module_srl = $oSchedule->module_srl;
		if ( !$module_srl )
		{
			throw new Rhymix\Framework\Exception('msg_invalid_request');
		}

		$status = '';
		if ( $oSchedule->status == 'STANDBY' )
		{
			$status = 'PUBLIC';
		}
		else
		{
			$status = 'STANDBY';
		}

		$output = $this->updateScheduleStatus($schedule_srl, $status);
		$this->add('status', $output->get('status'));

		return $output;
	}

	/**
	 * Return Schedule List for exec_xml
	 * @return void|Object
	 */
	function procScheduleGetList()
	{
		if ( !Context::get('is_logged') )
		{
			throw new Rhymix\Framework\Exceptions\NotPermitted;
		}

		$schedule_srls = Context::get('schedule_srls');
		if ( $schedule_srls )
		{
			$schedule_srl_list = explode(',', $schedule_srls);
		}

		if ( count($schedule_srl_list) > 0 )
		{
			$schedule_list = scheduleModel::getSchedules($schedule_srl_list);
		}
		else
		{
			$schedule_list = array();
			$this->setMessage(lang('msg_not_selected_schedule'));
		}
		$oSecurity = new Security($schedule_list);
		$oSecurity->encodeHTML('..variables.');
		$this->add('schedule_list', array_values($schedule_list));
	}

	/**
	 * Delete/Transform the schedule in the session
	 * @return void|Object
	 */
	function procScheduleManageCheckedSchedule()
	{
		@set_time_limit(0);

		$type = Context::get('type');
		$cart = Context::get('cart');
		$module_srl = Context::get('module_srl');

		$schedule_srl_list = array();
		$return_message = '';

		if ( !is_array($cart) )
		{
			$cart = explode('|@|', $cart);
		}
		$schedule_srl_list = array_unique(array_map('intval', $cart));

		if ( in_array($type, array('delete', 'STANDBY', 'PUBLIC')) )
		{
			foreach ( $schedule_srl_list as $schedule_srl )
			{
				if ( $type == 'delete' )
				{
					$output = $this->deleteSchedule($schedule_srl);
				}
				else if ( $type == 'STANDBY' || $type == 'PUBLIC' )
				{
					$output = $this->updateScheduleStatus($schedule_srl, $type);
				}
				if ( !$output->toBool() )
				{
					$return_message = $output->getMessage();
				}
				else
				{
					$return_message = ( $type == 'delete' ) ? 'success_deleted' : 'success_updated';
				}
			}
		}
		else
		{
			throw new Rhymix\Framework\Exceptions\InvalidRequest;
		}

		$this->setMessage($return_message);
		$redirectUrl = getNotEncodedUrl('', 'module', 'admin', 'act', 'dispScheduleAdminScheduleList', 'module_srl', $module_srl);
		$this->setRedirectUrl($redirectUrl);
	}

	function procScheduleMoveMonth()
	{
		// check grant
		if ( $this->module_info->module != 'schedule' )
		{
			throw new Rhymix\Framework\Exception('msg_invalid_request');
		}
		if ( !$this->grant->list )
		{
			throw new Rhymix\Framework\Exception('msg_not_permitted_to_view_list');
		}

		$obj = Context::getRequestVars();
		$year = preg_replace('/[^0-9]*/s', '', $obj->s_year);
		$month = preg_replace('/[^0-9]*/s', '', $obj->s_month);
		if ( strlen($year) != 4 || strlen($month) != 2 )
		{
			throw new Rhymix\Framework\Exception('msg_invalid_request');
		}

		if ( !in_array(Context::getRequestMethod(), array('XMLRPC', 'JSON')) )
		{
			$redirectUrl = Context::get('success_return_url') ? : getNotEncodedUrl('', 'mid', $obj->mid, 'category', $obj->category, 'selected_month', $year.$month);
			$this->setRedirectUrl($redirectUrl);
		}
	}

	function procScheduleSearchSchedule()
	{
		// check grant
		if ( $this->module_info->module != 'schedule' )
		{
			throw new Rhymix\Framework\Exception('msg_invalid_request');
		}
		if ( !$this->grant->list )
		{
			throw new Rhymix\Framework\Exception('msg_not_permitted_to_view_list');
		}

		$obj = Context::getRequestVars();

		if ( !in_array(Context::getRequestMethod(), array('XMLRPC', 'JSON')) )
		{
			$redirectUrl = Context::get('success_return_url') ? : getNotEncodedUrl('', 'mid', $obj->mid, 'category', $obj->category, 'list_style', $obj->list_style, 'status', $obj->status, 'search_target', $obj->search_target, 'search_keyword', $obj->search_keyword);
			$this->setRedirectUrl($redirectUrl);
		}
	}
}