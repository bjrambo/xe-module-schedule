<?php
class scheduleView extends schedule
{
	function init()
	{
		$oScheduleModel = getModel('schedule');
		$template_path = sprintf("%sskins/%s/",$this->module_path, $this->module_info->skin);
		if(!is_dir($template_path)||!$this->module_info->skin)
		{
			$this->module_info->skin = 'default';
			$template_path = sprintf("%sskins/%s/",$this->module_path, $this->module_info->skin);
		}
		$this->setTemplatePath($template_path);

		$oLayoutModel = getModel('layout');
		$module_info = $oScheduleModel->getScheduleInfo();
		$layout_info = $oLayoutModel->getLayout($module_info->layout_srl);
		if($layout_info)
		{
			$this->module_info->layout_srl = $module_info->layout_srl;
			$this->setLayoutPath($layout_info->path);
		}
	}

	function dispScheduleList()
	{
		$selected_date = Context::get('selected_date');
		$oScheduleModel = getModel('schedule');
		$logged_info = Context::get('logged_info');
		if(!$selected_date)
		{
			$selected_date = zDate(date('YmdHis'),"Ymd");
		}

		$date_info = new stdClass();
		$date_info->_year = substr($selected_date,0,4);
		$date_info->_month = substr($selected_date,4,2);
		$date_info->_day = substr($selected_date,6,2);
		$date_info->day_max = date("t",mktime(0,0,0,$date_info->_month,1,$date_info->_year));
		$date_info->week_start = date("w",mktime(0,0,0,$date_info->_month,1,$date_info->_year));

		// get the Config list
		$oScheduleModel = getModel('schedule');
		$config = $oScheduleModel->getConfig();

		Context::set('config', $config);
		Context::set('selected_date', $selected_date);
		Context::set('admin_date_info', $date_info);
		Context::set('getmodel', $oScheduleModel);

		$this->setTemplateFile('index');
	}

	function dispScheduleInsert()
	{
		$logged_info = Context::get('logged_info');

		if($logged_info->is_admin != 'Y')
		{
			return new Object(-1, '등록 및 삭제는 관리자만 가능합니다.');
		}
		$schedule_srl = Context::get('schedule_srl');
		$oEditorModel = getModel('editor');

		$oScheduleModel = getModel('schedule');
		$schedules = $oScheduleModel->getSchedule($schedule_srl);

		$selected_date = Context::get('selected_date');

		$oEditorModel = getModel('editor');

		$editor = $oEditorModel->getModuleEditor('document', $this->module_info->module_srl, $schedule_srl, 'schedule_srl', 'content');

		// get the Config list
		$config = $oScheduleModel->getConfig();

		Context::set('config', $config);
		Context::set('editor', $editor);
		if($schedules)
		{
			Context::set('user_schedule', $schedules);
		}

		$this->setTemplateFile('insert');
	}

	function dispScheduleSchedule()
	{
		$oScheduleModel = getModel('schedule');
		$config = $oScheduleModel->getConfig();

		if($config->viewconfig != 'Y')
		{
			return new Object(-1, '잘못된 요청입니다.');
		}

		$schedule_srl = Context::get('schedule_srl');

		$schedules = $oScheduleModel->getSchedule($schedule_srl);

		Context::set('user_schedule', $schedules);
		$this->setTemplateFile('myschedule');
	}

	function dispScheduleDelete()
	{
		$logged_info = Context::get('logged_info');
		if($logged_info->is_admin != 'Y')
		{
			return new Object(-1, '등록 및 삭제는 관리자만 가능합니다.');
		}
		$schedule_srl = Context::get('schedule_srl');
		$oScheduleModel = getModel('schedule');
		$schedules = $oScheduleModel->getSchedule($schedule_srl);

		Context::set('user_schedule', $schedules);
		$this->setTemplateFile('delete');
	}
}
