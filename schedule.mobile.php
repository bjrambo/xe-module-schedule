<?php

class scheduleMobile extends scheduleView
{
	function init()
	{
		$skin = $this->module_info->skin;
		$mskin = $this->module_info->mskin;
		if ( $mskin === '/USE_RESPONSIVE/' )
		{
			$template_path = sprintf('%sskins/%s/', $this->module_path, $skin);
			if ( !is_dir($template_path) || !$skin )
			{
				$template_path = sprintf('%sskins/%s/',$this->module_path, 'default');
			}
		}
		else
		{
			$template_path = sprintf('%sm.skins/%s/', $this->module_path, $mskin);
			if ( !is_dir($template_path) || !$mskin )
			{
				$template_path = sprintf('%sm.skins/%s/', $this->module_path, 'default');
			}
		}

		$this->setTemplatePath($template_path);
	}
}