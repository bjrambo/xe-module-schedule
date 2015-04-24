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

	function getScheduleInfo()
	{
		$output = executeQuery('schedule.getScheduleInfo');
		if(!$output->data->module_srl)
		{
			return;
		}

		$oModuleModel = getModel('module');
		$module_info = $oModuleModel->getModuleInfoByModuleSrl($output->data->module_srl);

		return $module_info;
	}

	function getScheduleList($selected_date)
	{
		$args = new stdClass();
		$args->selected_date = $selected_date;
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
}
/* End of file */
