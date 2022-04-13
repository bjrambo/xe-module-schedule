<?php

class scheduleItem extends BaseObject
{
	var $schedule_srl = 0;

	/**
	 * allow script access list
	 * @var array
	 */
	var $allowscriptaccessList = array();
	/**
	 * allow script access key
	 * @var int
	 */
	var $allowscriptaccessKey = 0;

	function setAttribute($attribute)
	{
		if ( !$attribute->schedule_srl )
		{
			$this->schedule_srl = null;
			return;
		}

		$this->schedule_srl = $attribute->schedule_srl;
		$this->module_srl = $attribute->module_srl;
		$this->category_srl = $attribute->category_srl;
		$this->member_srl = $attribute->member_srl;
		$this->status = $attribute->status;
		$this->alt_title = ($attribute->is_allday != 'Y')
				? '[' . zdate($attribute->start_date.$attribute->start_time, "H:i") . '~' . zdate($attribute->end_date.$attribute->end_time, "H:i") . '] ' . $attribute->title
				: $attribute->title;
		$this->adds($attribute);

		if ( $this->getDisplay() != 'SHOW' )
		{
			unset($this->alt_title);
		}

		// set RX_SCHEDULE_LIST
		return $GLOBALS['RX_SCHEDULE_LIST'][$this->schedule_srl] = $this;
	}

	function isContinued()
	{
		return (bool) ($this->schedule_srl);
	}

	function isExists()
	{
		return (bool) ($this->schedule_srl);
	}

	function getScheduleMid()
	{
		return ModuleModel::getMidByModuleSrl($this->get('module_srl'));
	}

	function getDisplay()
	{
		$grant_standby = Context::get('grant')->standby;
		$standby_display = Context::get('module_info')->standby_display;
		if ( !$grant_standby && !$this->isGranted() && $standby_display && $standby_display !== 'SHOW' && $this->get('status') !== 'PUBLIC' )
		{
			return $standby_display;
		}
		else
		{
			return 'SHOW';
		}
	}

	function getTitleText($cut_size = 0, $tail = '...')
	{
		if ( !$this->isExists() )
		{
			return;
		}

		$display = $this->getDisplay();
		if ( $display != 'SHOW' )
		{
			return lang('not_public');
		}
		else
		{
			return $cut_size ? cut_str($this->get('title'), $cut_size, $tail) : $this->get('title');
		}
	}

	function getTitle($cut_size = 0, $tail = '...')
	{
		if ( !$this->isExists() )
		{
			return false;
		}

		$title = escape($this->getTitleText($cut_size, $tail), false);
		$this->add('title_color', trim($this->get('title_color')));

		$title_color = '';
		if ( $this->get('title_color') && $this->get('title_color') != 'N' )
		{
			$title_color = 'color:#' . ltrim($this->get('title_color'), '#');
		}
		if ( $title_color )
		{
			return sprintf('<span style="%s">%s</span>', $title_color, $title);
		}

		return $title;
	}

	function getContent()
	{
		$display = $this->getDisplay();
		return ( $display != 'SHOW' ) ? lang('msg_not_public') : $this->get('content');
	}

	function getContentText($strlen = 0)
	{
		if ( !$this->isExists() )
		{
			return;
		}
		
		$content = $this->get('content');
		$content = preg_replace_callback('/<(object|param|embed)[^>]*/is', array($this, '_checkAllowScriptAccess'), $content);
		$content = preg_replace_callback('/<object[^>]*>/is', array($this, '_addAllowScriptAccess'), $content);
		if ( $strlen )
		{
			$content = trim(utf8_normalize_spaces(html_entity_decode(strip_tags($content))));
			$content = cut_str($content, $strlen, '...');
		}
		
		return escape($content);
	}

	function doCheckSameYear($date, $compared)
	{
		$yearstamp1 = strtotime($date);
		$yearstamp2 = strtotime($compared);
		return date('Y', $yearstamp1) == date('Y', $yearstamp2);
	}

	function doCheckSameMonth($date, $compared)
	{
		$yearstamp1 = strtotime($date);
		$yearstamp2 = strtotime($compared);
		return date('Ym', $yearstamp1) == date('Ym', $yearstamp2);
	}

	function doCheckConsecutiveDay($date, $compared)
	{
		$date = new DateTime($date);
		$compared = new DateTime($compared);
		$date_diff = date_diff($date, $compared);
		return ($date_diff->days == 1) ? true : false;
	}

	function getStartDate($format = 'Y.m.d H:i', $conversion = true)
	{
		if ( !$this->isExists() )
		{
			return;
		}
		if ( $this->getDisplay() != 'SHOW' )
		{
			return '-';
		}
		$date = $this->get('start_date');
		$time = $this->get('start_time');
		if ( $this->doCheckSameYear($date, date('Ymd')) )
		{
			$format = preg_replace('/([^a-zA-Zㄱ-힣]+)?[oYy]+[^a-zA-Z]+/i', '', $format);
		}
		return zdate($date.$time, $format, $conversion);
	}

	function getEndDate($format = 'Y.m.d', $conversion = true)
	{
		if ( !$this->isExists() )
		{
			return;
		}
		if ( $this->getDisplay() != 'SHOW' )
		{
			return '-';
		}
		$date = $this->get('end_date');
		$time = $this->get('end_time');
		if ( $this->doCheckSameYear($date, $this->get('start_date')) )
		{
			$format = preg_replace('/([^a-zA-Zㄱ-힣]+)?[oYy]+([^a-zA-Z]+)?/i', '', $format);
		}
		return zdate($date.$time, $format, $conversion);
	}

	function getSelectedDate($format = 'Y.m.d', $conversion = true)
	{
		if ( !$this->isExists() )
		{
			return;
		}
		if ( $this->getDisplay() != 'SHOW' )
		{
			return '-';
		}

		$selected_date = $this->get('selected_date');
		$selected_dates = explode(',', $selected_date);
		if ( count($selected_dates) < 1 )
		{
			return '-';
		}

		$dates = '';
		foreach ( $selected_dates as $key => $date )
		{
			$_prev = $selected_dates[$key-1];
			$_next = $selected_dates[$key+1];

			// 첫 번째 일정인 경우 현재 년도와 같은 일정이거나, 첫 번째 이후 일정의 경우 바로 앞 일정과 같은 년도의 일정이면
			if ( (!$key && $this->doCheckSameYear($date, date('Ymd'))) || ($_prev && $this->doCheckSameYear($date, $_prev)) )
			{
				// 출력 형식에서 년도 표시 제외
				$_format = preg_replace('/([^a-zA-Zㄱ-힣]+)?[oYy]+([^a-zA-Z]+)?/i', '', $format);

				// 바로 앞 일정과 같은 월의 일정이면
				if ( $selected_dates[$key-1] && $this->doCheckSameMonth($date, $_prev) )
				{
					// 출력 형식에서 년도와 월 표시 제외
					$_format = preg_replace('/([^a-zA-Zㄱ-힣]+)?[oYyFmMn]+([^a-zA-Z]+)?/i', '', $format);

					// 바로 앞 일정과 연속되는 날짜의 일정이면
					if ( $this->doCheckConsecutiveDay($date, $_prev) )
					{
						// 바로 다음 일정이 있고, 현재 일정과 계속되는 날짜의 일정이면
						if ( $_next && $this->doCheckConsecutiveDay($_next, $date) )
						{
							// 아무 것도 출력하지 않음
							$dates .= '';
						}
						// 바로 다음 일정이 없거나, 있더라도 현재 일정과 연속되는 날짜의 일정이 아니라면
						else
						{
							// (첫 일정이 아닌 경우에만) 물결 표시와 함께 '년도와 월을 제외한' 일자만 출력
							$dates .= $key ? '~' : '';
							$dates .= zdate($date, $_format, $conversion);
						}
					}
					// 바로 앞 일정과 연속되는 날짜의 일정이 아니라면
					else
					{
						// (첫 일정이 아닌 경우에만 쉼표 표시와 함께) '년도와 월을 제외한' 일자만 출력
						$dates .= $key ? ', ' : '';
						$dates .= zdate($date, $_format, $conversion);
					}
				}
				// 바로 앞 일정이 없거나, 같은 월의 일정이 아니라면
				else
				{
					// (첫 일정이 아닌 경우에만 구분자( | )를 넣고) '년도'를 제외한 월과 일자만 출력
					$dates .= $key ? ' | ' : '';
					$dates .= zdate($date, $_format, $conversion);
				}
			}
			// 첫 번째 일정인 경우 현재 년도와 다른 일정이고, 첫 번째 이후 일정의 경우 바로 앞 일정과 다른 년도의 일정이면
			else
			{
				// (첫 일정이 아닌 경우에만 구분자( | )를 넣고) 년도와 월과 일자 모두 출력
				$dates .= $key ? ' | ' : '';
				$dates .= zdate($date, $format, $conversion);
			}
		}
		return $dates;
	}

	function getRegdate($format = 'Y.m.d H:i:s', $conversion = true)
	{
		if ( !$this->isExists() )
		{
			return;
		}
		if ( $this->getDisplay() != 'SHOW' )
		{
			return '-';
		}
		return zdate($this->get('regdate'), $format, $conversion);
	}

	function getCategoryList($module_srl = null)
	{
		if ( !isset($module_srl) )
		{
			$module_srl = Context::get('module_info')->module_srl;
		}
		return DocumentModel::getCategoryList($module_srl);
	}

	function getCurrentCategoryHTML($category_list = array())
	{
		if ( empty($category_list) )
		{
			$category_list = $this->getCategoryList();
		}

		if ( !$this->isExists() )
		{
			return;
		}

		if ( $this->getDisplay() != 'SHOW' )
		{
			return '-';
		}
		$current_category_name = '';
		$category_srl = $this->get('category_srl');
		if ( !$category_srl )
		{
			return $current_category_name;
		}

		$mid = Context::get('mid');
		$list_style = Context::get('list_style') ? : '';
		$category_info = $category_list[$category_srl];
		if ( $category_info->depth )
		{
			$parent_info = $category_list[$category_info->parent_srl];
			$current_category_name .= '<a href="';
			$current_category_name .= getNotEncodedUrl('', 'mid', $mid, 'category', $parent_info->category_srl, 'list_style', $list_style);
			if ( $parent_info->color && $parent_info->color != 'transparent' )
			{
				$current_category_name .= '" style="color:' . $parent_info->color;
			}
			$current_category_name .= '">' . $parent_info->title .'</a>&nbsp;&gt;&nbsp;';
		}
		$current_category_name .= '<a href="';
		$current_category_name .= getNotEncodedUrl('', 'mid', $mid, 'category', $category_info->category_srl, 'list_style', $list_style);
		if ( $category_info->color && $category_info->color != 'transparent' )
		{
			$current_category_name .= '" style="color:' . $category_info->color;
		}
		$current_category_name .= '">' . $category_info->title .'</a>';

		return $current_category_name;
	}

	function getCurrentCategoryText($category_list = array())
	{
		if ( empty($category_list) )
		{
			$category_list = $this->getCategoryList();
		}

		$current_category_name = '';
		$category_srl = Context::get('category');

		if ( !$category_srl )
		{
			$current_category_name = lang('category');
		}
		else
		{
			$current_category = $category_list[$category_srl];
			if ( !$current_category->parent_srl )
			{
				if ( $current_category->color && $current_category->color != 'transparent' )
				{
					$current_category_name = '<span style="color: '. $current_category->color .'">' . $current_category->title . '</span>';
				}
				else
				{
					$current_category_name = $current_category->title;
				}
			}
			else
			{
				$parent_category_name  = '';
				$parent_category = $category_list[$current_category->parent_srl];
				if ( $parent_category->color && $parent_category->color != 'transparent' )
				{
					$parent_category_name = '<span style="color: '. $parent_category->color .'">' . $parent_category->title . '</span>';
				}
				else
				{
					$parent_category_name = $parent_category->title;
				}
				if ( $current_category->color && $current_category->color != 'transparent' )
				{
					$current_category_name = '<span style="color: '. $current_category->color .'">' . $current_category->title . '</span>';
				}
				else
				{
					$current_category_name = $current_category->title;
				}
				$current_category_name = $parent_category_name . ' &gt; ' . $current_category_name;
			}
		}

		return $current_category_name;
	}

	function getMemberSrl()
	{
		if ( !$this->isExists() || $this->getDisplay() != 'SHOW' )
		{
			return;
		}
		return $this->get('member_srl');
	}

	function getNickName()
	{
		if ( !$this->isExists() )
		{
			return;
		}
		$display = $this->getDisplay();
		return ( $display == 'SHOW' ) ? $this->get('nick_name') : '-';
	}

	function getPlace()
	{
		if ( !$this->isExists() )
		{
			return;
		}
		$display = $this->getDisplay();
		return ( $display == 'SHOW' ) ? $this->get('place') : '-';
	}

	function getStatus()
	{
		if ( !$this->isExists() )
		{
			return;
		}
		return $this->get('status');
	}

	function getProfileImage()
	{
		if ( !$this->isExists() || $this->get('member_srl') <= 0 )
		{
			return;
		}

		$profile_info = MemberModel::getProfileImage($this->get('member_srl'));
		if ( !$profile_info )
		{
			return;
		}

		return $profile_info->src;
	}

	function thumbnailExists($width = 80, $height = 0, $type = '')
	{
		if ( !$this->isExists() )
		{
			return false;
		}
		if ( !$this->getThumbnail($width, $height, $type) )
		{
			return false;
		}
		return true;
	}

	function getThumbnail($width = 80, $height = 0, $thumbnail_type = '')
	{
		if ( !$this->isExists() )
		{
			return;
		}
		$schedule_srl = $this->schedule_srl;

		// Get thumbnail type information from document module's configuration
		$config = DocumentModel::getDocumentConfig();
		if ( $config->thumbnail_target === 'none' || $config->thumbnail_type === 'none' )
		{
			return;
		}
		if ( !in_array($thumbnail_type, array('crop', 'ratio', 'fill', 'stretch', 'center')) )
		{
			$thumbnail_type = $config->thumbnail_type ?: 'fill';
		}
		if ( !$config->thumbnail_quality )
		{
			$config->thumbnail_quality = 75;
		}

		// If not specify its height, create a square
		if ( !$height )
		{
			$height = $width;
		}

		// Define thumbnail information
		$thumbnail_path = sprintf('files/thumbnails/%s',getNumberingPath($schedule_srl, 3));
		$thumbnail_file = sprintf('%s%dx%d.%s.jpg', $thumbnail_path, $width, $height, $thumbnail_type);
		$thumbnail_url  = Context::getRequestUri().$thumbnail_file;
		$thumbnail_file = RX_BASEDIR . $thumbnail_file;

		// Return false if thumbnail file exists and its size is 0. Otherwise, return its path
		if ( file_exists($thumbnail_file) )
		{
			if ( filesize($thumbnail_file) < 1 )
			{
				return FALSE;
			}
			else
			{
				return $thumbnail_url . '?' . date('YmdHis', filemtime($thumbnail_file));
			}
		}

		// Call trigger for custom thumbnails.
		$trigger_obj = (object)[
			'schedule_srl' => $schedule_srl, 'width' => $width, 'height' => $height,
			'image_type' => 'jpg', 'type' => $thumbnail_type, 'quality' => $config->thumbnail_quality,
			'filename' => $thumbnail_file, 'url' => $thumbnail_url,
		];
		clearstatcache(true, $thumbnail_file);
		if ( file_exists($thumbnail_file) && filesize($thumbnail_file) > 0 )
		{
			return $thumbnail_url . '?' . date('YmdHis', filemtime($thumbnail_file));
		}

		// Get content if it does not exist.
		if ( $this->get('content') )
		{
			$content = $this->get('content');
		}
		elseif ( $config->thumbnail_target !== 'attachment' )
		{
			$args = new stdClass();
			$args->schedule_srl = $schedule_srl;
			$output = executeQuery('schedule.getSchedule', $args);
			$content = $output->data->content;
		}

		// Target File
		$source_file = null;
		$is_tmp_file = false;
		$uploaded_count = $this->get('uploaded_count');

		// Return false if neither attachement nor image files in the document
		if ( !$uploaded_count && !preg_match("!<img!is", $content) )
		{
			return;
		}

		// Find an iamge file among attached files if exists
		if ( $uploaded_count )
		{
			$file_list = FileModel::getFiles($schedule_srl, array(), 'file_srl', true);
			$first_image = null;

			foreach ( $file_list as $file )
			{
				if ( $file->thumbnail_filename && file_exists($file->thumbnail_filename) )
				{
					$file->uploaded_filename = $file->thumbnail_filename;
				}
				else
				{
					if ( $file->direct_download !== 'Y' || !preg_match('/\.(jpe?g|png|gif|webp|bmp)$/i', $file->source_filename) )
					{
						continue;
					}
					if ( !file_exists($file->uploaded_filename) )
					{
						continue;
					}
				}
				if ( $file->cover_image === 'Y' )
				{
					$source_file = $file->uploaded_filename;
					break;
				}
				if ( !$first_image )
				{
					$first_image = $file->uploaded_filename;
				}
			}
			if ( !$source_file && $first_image )
			{
				$source_file = $first_image;
			}
		}

		// If not exists, file an image file from the content
		if ( !$source_file && $config->thumbnail_target !== 'attachment' )
		{
			preg_match_all("!<img\s[^>]*?src=(\"|')([^\"' ]*?)(\"|')!is", $content, $matches, PREG_SET_ORDER);
			foreach($matches as $match)
			{
				$target_src = htmlspecialchars_decode(trim($match[2]));
				if ( preg_match('/\/(common|modules|widgets|addons|layouts)\//i', $target_src) )
				{
					continue;
				}
				else
				{
					if ( !preg_match('/^https?:\/\//i',$target_src) )
					{
						$target_src = Context::getRequestUri().$target_src;
					}

					$tmp_file = sprintf('./files/cache/tmp/%d', md5(rand(111111,999999).$schedule_srl));
					if ( !is_dir('./files/cache/tmp') )
					{
						FileHandler::makeDir('./files/cache/tmp');
					}
					FileHandler::getRemoteFile($target_src, $tmp_file);
					if ( !file_exists($tmp_file) )
					{
						continue;
					}
					else
					{
						if ( $is_img = @getimagesize($tmp_file) )
						{
							list($_w, $_h, $_t, $_a) = $is_img;
							if ( $_w < ($width * 0.3) && $_h < ($height * 0.3) )
							{
								continue;
							}
						}
						else
						{
							continue;
						}
						$source_file = $tmp_file;
						$is_tmp_file = true;
						break;
					}
				}
			}
		}

		if ( $source_file )
		{
			$output_file = FileHandler::createImageFile($source_file, $thumbnail_file, $trigger_obj->width, $trigger_obj->height, $trigger_obj->image_type, $trigger_obj->type, $trigger_obj->quality);
		}

		// Remove source file if it was temporary
		if ( $is_tmp_file )
		{
			FileHandler::removeFile($source_file);
		}

		// Return the thumbnail path if it was successfully generated
		if ( $output_file )
		{
			return $thumbnail_url . '?' . date('YmdHis');
		}
		// Create an empty file if thumbnail generation failed
		else
		{
			FileHandler::writeFile($thumbnail_file, '','w');
		}
	}

	function getLunardayValue($date)
	{
		if ( strlen($date) != 8 )
		{
			return;
		}
		$lunar_date = ScheduleModel::getLunarFromSolar($date);

		$lunarday_value = '';
		$lunarday_value .= ($lunar_date->is_leap) ? '(윤)' : '';
		$lunarday_value .= $lunar_date->month . '월' . $lunar_date->day .'일';

		return $lunarday_value;
	}

	function getPrevMonth($date)
	{
		if ( strlen($date) != 8 )
		{
			$date = substr($date, 0, 6) . '01';
		}
		return date('Ym', strtotime('-1 month', strtotime($date)));
	}

	function getNextMonth($date)
	{
		if ( strlen($date) != 8 )
		{
			$date = substr($date, 0, 6) . '01';
		}
		return date('Ym', strtotime('+1 month', strtotime($date)));
	}

	function getPrevDay($date)
	{
		return date('Ymd', strtotime('-1 day', strtotime($date)));
	}

	function getNextDay($date)
	{
		return date('Ymd', strtotime('+1 day', strtotime($date)));
	}

	function getDayGap($date1, $date2)
	{
		$diff = date_diff(date_create($date1), date_create($date2));
		return $diff->days;
	}

	function getWeekdayKey($date)
	{
		return date('w', strtotime($date));
	}

	function getWeekdayName($date)
	{
		return date('l', strtotime($date));
	}

	function setBasicScheduleInfo($format = 'Y-m-d H:i')
	{
		$basic_info = '<span>';
		if ( $this->get('is_allday') == 'Y' )
		{
			$format = preg_replace('/([^a-zA-Zㄱ-힣]+)?[aAgGhHis]+[^a-zA-Z]+/i', '', $format);
			$basic_info .= $this->getStartDate($format) . ' ' . Context::getLang('allday');
		}
		else
		{
			$start_date = $this->get('start_date');
			$start_time = $this->get('start_time');
			$end_date = $this->get('end_date');
			$end_time = $this->get('end_time');

			$basic_info .= $this->getStartDate($format) . ' ~ ';
			if ( $start_date == $end_date )
			{
				$format = preg_replace('/([^a-zA-Zㄱ-힣]+)?[oYyFmMndj]+[^a-zA-Z]+/i', '', $format);
			}
			else if ( substr($start_date, 0, 4) == substr($start_date, 0, 4) )
			{
				$format = preg_replace('/([^a-zA-Zㄱ-힣]+)?[oYy]+[^a-zA-Z]+/i', '', $format);
			}
			$basic_info .= $this->getEndDate($format);
		}
		$basic_info .= '</span>';

		$cache_key = 'schedule_item:basic_info' . getNumberingPath($this->schedule_srl) . $this->schedule_srl;
		if ( Rhymix\Framework\Cache::get($cache_key) )
		{
			Rhymix\Framework\Cache::delete($cache_key);
		}
		Rhymix\Framework\Cache::set($cache_key, $basic_info);
	}

	function getBasicScheduleInfo($format = 'Y-m-d H:i')
	{
		if ( $this->getDisplay() != 'SHOW' )
		{
			return '-';
		}

		$cache_key = 'schedule_item:basic_info' . getNumberingPath($this->schedule_srl) . $this->schedule_srl;
		if ( Rhymix\Framework\Cache::get($cache_key) )
		{
			return Rhymix\Framework\Cache::get($cache_key);
		}

		$this->setBasicScheduleInfo($format);

		return Rhymix\Framework\Cache::get($cache_key);
	}

	function getRecurInfo()
	{
		if ( $this->get('is_recurrence') != 'Y' )
		{
			return array();
		}
		return scheduleModel::getScheduleRecurInfo($this->schedule_srl, $this->get('start_date'), $this->get('end_date'));
	}

	function setScheduleRecurDesc()
	{
		$schedule_srl = $this->schedule_srl;
		$start_date = $this->get('start_date');
		$end_date = $this->get('end_date');
		$recur_info = $this->getRecurInfo();

		$vars = ( is_array($recur_info) ) ? (object)$recur_info : $recur_info;
		$lang = Context::get('lang');

		if ( $vars->recur_type == 'weekday' )
		{
			$orders = '';
			$weeks = '';
			$week_order = is_array($vars->week_order) ? $vars->week_order : explode('|@|', $vars->week_order);
			$weekdays = is_array($vars->weekdays) ? $vars->weekdays : explode('|@|', $vars->weekdays);
			foreach ( $week_order as $i => $key )
			{
				$orders .= ($i == 0) ? '' : '/';
				$orders .= $lang->week_order[$key];
			}
			foreach ( $weekdays as $i => $key )
			{
				$weeks .= ($i == 0) ? '' : '/';
				$weeks .= $lang->weekdays[$key];
			}
			$weekdays_value = $orders . ' ' . $weeks;
		}
		else if ( $vars->recur_type == 'lunar' )
		{
			$lunarday_value = $lang->regular_type['yearly'] . ' ' . $lang->recur_type['lunar'] . ' ';
			$s_month = substr($start_date, 4, 2); $e_month = substr($end_date, 4, 2);
			$lunar_start_date = scheduleModel::getLunarFromSolar($start_date);

			$lunarday_value .= ($lunar_start_date->is_leap) ? '(윤)' : '';
			$lunarday_value .= $lunar_start_date->month/1 . '월' . $lunar_start_date->day/1 .'일';

			if ( $start_date != $end_date )
			{
				$lunar_end_date = scheduleModel::getLunarFromSolar($end_date);
				$lunarday_value .= '~';
				if ( $s_month != $e_month )
				{
					$lunarday_value .= $lunar_end_date->month/1 . '월' . $lunar_end_date->day/1 .'일';
				}
				else
				{
					$lunarday_value .= $lunar_end_date->day/1 .'일';
				}
			}
			$lunarday_value;
		}

		if ( $vars->exception_type )
		{
			$exception_type = $lang->exception_type;
			$exception_types = !is_array($vars->exception_type) ? $vars->exception_type : implode('|@|', $vars->exception_type);
			$exception_type_value = preg_replace_callback(
				'/[a-z]+/',
				function($m) use($exception_type) {
					return $exception_type[$m[0]];
				},
				str_replace('|@|', '/', $exception_types)
			);
		}
		
		foreach ( $lang->recur_type as $key => $val )
		{
			$desc .= '<span id="disp_recur_type_' . $key . '"';
			$desc .= ( $key != $vars->recur_type ) ? ' style="display: none;"' : '';
			$desc .= '>';
			switch ( $key ) {
				case 'regular':
					$desc .= '<span id="disp_recur_' . $key . '">'. $lang->regular_type[$vars->recur_regular] .'</span>';
					break;
				case 'weekday':
					$desc .= '<span id="disp_recur_' . $key . '">'. $weekdays_value .'</span>';
					break;
				case 'lunar':
					$desc .= '<span id="disp_recur_' . $key . '">'. $lunarday_value .'</span>';
					break;
				default:
					$desc .= '<span id="disp_recur_cycle">'. $vars->recur_cycle .'</span><span id="disp_recur_units">'. $lang->recur_units[$vars->recur_unit] .'</span><span id="disp_recur_per">'. $lang->per .'</span>';
			}
			$desc .= '</span>';
		}

		$desc .= '<span id="disp_recur_freq"';
		$desc .= ( !$vars->recur_freq ) ? ' style="display: none;"' : '';
		$desc .= '>';
		$desc .= '&nbsp;&nbsp;|&nbsp;&nbsp;<span id="disp_recur_freq_num">'. $vars->recur_freq .'</span>'. $lang->unit_count .' '. $lang->recur .'</span>';

		$desc .= '<span id="disp_stop_date">';
		if ( $vars->stop_date )
		{
			$desc .= '&nbsp;&nbsp;|&nbsp;&nbsp;' . sprintf($lang->stopped_on, zdate($vars->stop_date, 'Y년 n월 j일'));
		}
		$desc .= '</span>';

		$desc .= '<span id="disp_exception_type">';
		if ( $vars->exception_type )
		{
			$desc .= '&nbsp;&nbsp;|&nbsp;&nbsp;' . sprintf($lang->exception_type_value, $exception_type_value) . '&nbsp;';
		}
		$desc .= '</span>';

		$desc .= '<span id="disp_exception_option">';
		if ( $vars->exception_type && $vars->exception_option )
		{
			$desc .= $lang->exception_option[$vars->exception_option];
		}
		$desc .= '</span>';

		$cache_key = 'schedule_item:recur_info' . getNumberingPath($schedule_srl) . $schedule_srl;
		if ( Rhymix\Framework\Cache::get($cache_key) )
		{
			Rhymix\Framework\Cache::delete($cache_key);
		}
		Rhymix\Framework\Cache::set($cache_key, $desc);
	}

	function getRecurDescHTML()
	{
		if ( $this->getDisplay() != 'SHOW' )
		{
			return '-';
		}
		if ( !$this->schedule_srl || $this->get('is_recurrence') != 'Y' )
		{
			$desc = '
				<span id="disp_recur_type_regular"><span id="disp_recur_regular"></span></span>' .
				'<span id="disp_recur_type_weekday"><span id="disp_recur_weekday"></span></span>' .
				'<span id="disp_recur_type_lunar"><span id="disp_recur_lunar"></span></span>' .
				'<span id="disp_recur_type_manual"><span id="disp_recur_cycle"></span><span id="disp_recur_units"></span><span id="disp_recur_per">'. Context::getLang('per') .'</span></span>' .
				'<span id="disp_recur_freq">&nbsp;&nbsp;|&nbsp;&nbsp;<span id="disp_recur_freq_num"></span>'. Context::getLang('unit_count') .' '. Context::getLang('recur') .'</span>' .
				'<span id="disp_stop_date"></span>' .
				'<span id="disp_exception_type"></span><span id="disp_exception_option"></span>
			';
			return $desc;
		}

		$cache_key = 'schedule_item:recur_info' . getNumberingPath($this->schedule_srl) . $this->schedule_srl;
		if ( Rhymix\Framework\Cache::get($cache_key) )
		{
			return Rhymix\Framework\Cache::get($cache_key);
		}

		$this->setScheduleRecurDesc();
		return Rhymix\Framework\Cache::get($cache_key);
	}

	function getEditor()
	{
		$module_srl = $this->module_srl;
		if ( !$module_srl )
		{
			$module_srl = Context::get('module_info')->module_srl;
		}

		return EditorModel::getModuleEditor('document', $module_srl, $this->schedule_srl, 'schedule_srl', 'content');
	}

	function getStatusList()
	{
		$logged_info = Context::get('logged_info');
		$module_info = Context::get('module_info');
		if ( $logged_info->is_admin === 'Y' || $module_info->set_status === 'OPTION' )
		{
			return lang('status_list');
		}

		$status_list = array();
		foreach ( lang('status_list') as $key => $val )
		{
			if ( $module_info->set_status && $key !== $module_info->set_status )
			{
				continue;
			}
			$status_list[$key] = $val;
		}
		return $status_list;
	}

	function isEditable()
	{
		$is_editable = false;
		if ( $this->isGranted() || (!$this->isGranted() && !$this->get('member_srl') && !Context::get('is_logged')) )
		{
			$is_editable = true;
		}
		return $is_editable;
	}

	function isGranted()
	{
		if ( !$this->isExists() )
		{
			return false;
		}

		if ( isset($_SESSION['granted_schedule'][$this->schedule_srl]) )
		{
			return true;
		}

		$logged_info = Context::get('logged_info');
		if ( !$logged_info->member_srl )
		{
			return false;
		}
		if ( $logged_info->is_admin == 'Y' )
		{
			return true;
		}
		if ( $this->get('member_srl') && abs($this->get('member_srl')) == $logged_info->member_srl )
		{
			return true;
		}

		$grant = ModuleModel::getGrant(ModuleModel::getModuleInfoByModuleSrl($this->get('module_srl')), $logged_info);
		if ( $grant->manager )
		{
			return true;
		}

		return false;
	}

	function _addAllowScriptAccess($m)
	{
		if ( $this->allowscriptaccessList[$this->allowscriptaccessKey] == 1 )
		{
			$m[0] = $m[0].'<param name="allowscriptaccess" value="never"></param>';
		}
		$this->allowscriptaccessKey++;
		return $m[0];
	}

	function _checkAllowScriptAccess($m)
	{
		if ( $m[1] == 'object' )
		{
			$this->allowscriptaccessList[] = 1;
		}

		if ( $m[1] == 'param' )
		{
			if ( stripos($m[0], 'allowscriptaccess') )
			{
				$m[0] = '<param name="allowscriptaccess" value="never"';
				if ( substr($m[0], -1) == '/' )
				{
					$m[0] .= '/';
				}
				$this->allowscriptaccessList[count($this->allowscriptaccessList)-1]--;
			}
		}
		else if ( $m[1] == 'embed' )
		{
			if ( stripos($m[0], 'allowscriptaccess') )
			{
				$m[0] = preg_replace('/always|samedomain/i', 'never', $m[0]);
			}
			else
			{
				$m[0] = preg_replace('/\<embed/i', '<embed allowscriptaccess="never"', $m[0]);
			}
		}
		return $m[0];
	}

	/**
	 * Add OpenGraph metadata tags.
	 * 
	 * @return void
	 */
	function _addOpenGraphMetadata()
	{
		// Get information about the current request.
		$page_type = 'website';
		$current_module_info = Context::get('current_module_info');
		$site_module_info = Context::get('site_module_info');
		$schedule_srl = Context::get('schedule_srl');
		$grant = Context::get('grant');
		$permitted = $grant->access;
		if ( isset($grant->view) && !$grant->view )
		{
			$permitted = false;
		}
		if ( $schedule_srl && $permitted )
		{
			if ( isset($grant->private) && !$grant->private && $current_module_info->use_private === 'Y' )
			{
				$permitted = false;
			}
			else
			{
				if ( is_object($this) && $this->schedule_srl == $schedule_srl )
				{
					$page_type = 'article';
					if ( $this->get('status') == 'STANDBY' && $current_module_info->standby_display != 'SHOW' )
					{
						$permitted = false;
					}
				}
			}
		}
		
		// Add basic metadata.
		// Context::addOpenGraphData('og:title', $permitted ? Context::getBrowserTitle() : lang('msg_not_permitted'));
		// Context::addOpenGraphData('og:site_name', Context::getSiteTitle());
		if ( $page_type === 'article' && $permitted && config('seo.og_extract_description') )
		{
			$description = trim(utf8_normalize_spaces($this->getContentText(200)));
		}
		else
		{
			$description = Context::getMetaTag('description');
		}
		Context::addOpenGraphData('og:description', $description);
		Context::addMetaTag('description', $description);
		
		// Add metadata about this page.
		Context::addOpenGraphData('og:type', $page_type);
		if ( $page_type === 'article' )
		{
			$canonical_url = getFullUrl('', 'mid', $current_module_info->mid, 'schedule_srl', $schedule_srl);
		}
		elseif ( ($page = Context::get('page')) > 1 )
		{
			$canonical_url = getFullUrl('', 'mid', $current_module_info->mid, 'page', $page);
		}
		elseif ( $current_module_info->module_srl == $site_module_info->module_srl )
		{
			$canonical_url = getFullUrl('');
		}
		else
		{
			$canonical_url = getFullUrl('', 'mid', $current_module_info->mid);
		}
		Context::addOpenGraphData('og:url', $canonical_url);
		Context::setCanonicalURL($canonical_url);
		
		// Add metadata about the locale.
		$lang_type = Context::getLangType();
		$locales = (include \RX_BASEDIR . 'common/defaults/locales.php');
		if ( isset($locales[$lang_type]) )
		{
			Context::addOpenGraphData('og:locale', $locales[$lang_type]['locale']);
		}

		// Add image.
		if ( $document_images = Context::getMetaImages() )
		{
			// pass
		}
		elseif ( $page_type === 'article' && $permitted && config('seo.og_extract_images') )
		{
			if ( ($document_images = Rhymix\Framework\Cache::get("seo:document_images:$schedule_srl")) === null )
			{
				// Target File
				$source_file = null;
				$is_tmp_file = false;
				$uploaded_count = $this->get('uploaded_count');
				$content = $this->get('content');

				// Find an image file among attached files if exists
				if ( $uploaded_count )
				{
					$file_list = FileModel::getFiles($schedule_srl, array(), 'file_srl', true);
					$first_image = null;

					foreach ( $file_list as $file )
					{
						if ( $file->thumbnail_filename && file_exists($file->thumbnail_filename) )
						{
							$file->uploaded_filename = $file->thumbnail_filename;
						}
						else
						{
							if ( $file->direct_download !== 'Y' || !preg_match('/\.(jpe?g|png|gif|webp|bmp)$/i', $file->source_filename) )
							{
								continue;
							}
							if ( !file_exists($file->uploaded_filename) )
							{
								continue;
							}
						}
						if ( $file->cover_image === 'Y' )
						{
							$source_file = $file->uploaded_filename;
							break;
						}
						if ( !$first_image )
						{
							$first_image = $file->uploaded_filename;
						}
					}
					if ( !$source_file && $first_image )
					{
						$source_file = $first_image;
					}
					if ( $source_file )
					{
						list($width, $height) = @getimagesize($source_file);
						if ($width < 100 && $height < 100)
						{
							$source_file = null;
						}
					}
				}

				// If not exists, file an image file from the content
				$config = documentModel::getDocumentConfig();
				if ( !$source_file && $config->thumbnail_target !== 'attachment' )
				{
					preg_match_all("!<img\s[^>]*?src=(\"|')([^\"' ]*?)(\"|')!is", $content, $matches, PREG_SET_ORDER);
					foreach ( $matches as $match )
					{
						$target_src = htmlspecialchars_decode(trim($match[2]));
						if ( preg_match('/\/(common|modules|widgets|addons|layouts)\//i', $target_src) )
						{
							continue;
						}
						else
						{
							if ( !preg_match('/^https?:\/\//i',$target_src) )
							{
								$target_src = Context::getRequestUri().$target_src;
							}

							$tmp_file = sprintf('./files/cache/tmp/%d', md5(rand(111111,999999).$schedule_srl));
							if ( !is_dir('./files/cache/tmp') )
							{
								FileHandler::makeDir('./files/cache/tmp');
							}
							FileHandler::getRemoteFile($target_src, $tmp_file);
							if ( !file_exists($tmp_file) )
							{
								continue;
							}
							else
							{
								if ( $is_img = @getimagesize($tmp_file) )
								{
									list($width, $height) = $is_img;
									if ($width < 100 && $height < 100)
									{
										continue;
									}
								}
								else
								{
									continue;
								}
								$source_file = $target_src;
								$is_tmp_file = true;
								break;
							}
						}
					}

					// Remove source file if it was temporary
					if ( $is_tmp_file )
					{
						FileHandler::removeFile($tmp_file);
					}
				}

				if ( $source_file )
				{
					$document_images[] = array('filepath' => $source_file, 'width' => $width, 'height' => $height);
				}
				Rhymix\Framework\Cache::set("seo:document_images:$schedule_srl", $document_images);
			}
		}
		else
		{
			$document_images = null;
		}
		
		if ( $document_images )
		{
			$first_image = array_first($document_images);
			if ( !preg_match('/(^http?:\/\/)|(^https?:\/\/)/i', $first_image['filepath']) )
			{
				$first_image['filepath'] = str_replace('./', '', Context::getRequestUri() . $first_image['filepath']);
			}
			$first_image['filepath'] = preg_replace('/^.\\/files\\//', \RX_BASEURL . 'files/', $first_image['filepath']);
			Context::addOpenGraphData('og:image', $first_image['filepath']);
			Context::addOpenGraphData('og:image:width', $first_image['width']);
			Context::addOpenGraphData('og:image:height', $first_image['height']);
			$this->_image_type = 'document';
		}
		elseif ($default_image = getAdminModel('admin')->getSiteDefaultImageUrl($site_module_info->domain_srl, $width, $height))
		{
			Context::addOpenGraphData('og:image', Rhymix\Framework\URL::getCurrentDomainURL($default_image));
			if ( $width && $height )
			{
				Context::addOpenGraphData('og:image:width', $width);
				Context::addOpenGraphData('og:image:height', $height);
			}
			$this->_image_type = 'site';
		}
		else
		{
			$this->_image_type = 'none';
		}
		
		// Add datetime for articles.
		if ( $page_type === 'article' && $permitted && config('seo.og_use_timestamps') )
		{
			Context::addOpenGraphData('og:article:published_time', $this->getRegdate('c'));
		}
	}
	
	/**
	 * Add Twitter metadata tags.
	 * 
	 * @return void
	 */
	function _addTwitterMetadata()
	{
		$card_type = $this->_image_type === 'document' ? 'summary_large_image' : 'summary';
		Context::addMetaTag('twitter:card', $card_type);
		
		foreach(Context::getOpenGraphData() as $val)
		{
			if ($val['property'] === 'og:title')
			{
				Context::addMetaTag('twitter:title', $val['content']);
			}
			if ($val['property'] === 'og:description')
			{
				Context::addMetaTag('twitter:description', $val['content']);
			}
			if ($val['property'] === 'og:image' && $this->_image_type === 'document')
			{
				Context::addMetaTag('twitter:image', $val['content']);
			}
		}
	}
}
