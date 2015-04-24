<?php

require_once(_XE_PATH_ . 'modules/schedule/schedule.view.php');

class scheduleMobile extends scheduleView
{
	function init()
	{
		$oScheduleModel = getModel('schedule');

		$module_config = $oScheduleModel->getConfig();

		Context::set('module_config', $module_config);

		$template_path = sprintf('%sm.skins/%s/', $this->module_path, $this->module_info->mskin);
		if (!(is_dir($template_path) && $this->module_info->mskin))
		{
			$this->module_info->mskin = 'default';
			$template_path = sprintf('%sm.skins/%s/', $this->module_path, $this->module_info->mskin);
		}

		$this->setTemplatePath($template_path);
	}
}
