<?php
class scheduleAdminView extends schedule
{
	function init()
	{
		$oModuleModel = getModel('module');
		$oScheduleModel = getModel('schedule');
		$this->module_info = $oScheduleModel->getScheduleInfo();

		$this->module_config = $oModuleModel->getModuleConfig('schedule');

		Context::set('module_config', $this->module_config);
		Context::set('module_info', $this->module_info);

		$this->setTemplatePath($this->module_path . 'tpl');
		$this->setTemplateFile(strtolower(str_replace('dispScheduleAdmin', '', $this->act)));
	}

	function dispScheduleAdminInsertModule()
	{
		// get the skins list
		$oModuleModel = getModel('module');
		$skin_list = $oModuleModel->getSkins($this->module_path);
		Context::set('skin_list',$skin_list);

		$mskin_list = $oModuleModel->getSkins($this->module_path, "m.skins");
		Context::set('mskin_list', $mskin_list);

		// get the layouts list
		$oLayoutModel = getModel('layout');
		$layout_list = $oLayoutModel->getLayoutList();
		Context::set('layout_list', $layout_list);

		$mobile_layout_list = $oLayoutModel->getLayoutList(0,"M");
		Context::set('mlayout_list', $mobile_layout_list);

		// Menu Get a list
		$output = executeQueryArray('menu.getMenus');
		Context::set('menu_list', $output->data);

		$security = new Security();
		$security->encodeHTML('skin_list..title','mskin_list..title');
		$security->encodeHTML('layout_list..title','layout_list..layout');
		$security->encodeHTML('mlayout_list..title','mlayout_list..layout');

		$security = new Security();
		$security->encodeHTML('extra_vars..name','list_config..name');
	}

	function dispScheduleAdminConfig()
	{
		$oScheduleModel = getModel('schedule');
		$config = $oScheduleModel->getConfig();

		Context::set('config', $config);

		$security = new Security();
		$security->encodeHTML('config..');
	}

	function dispScheduleAdminEditorSetting()
	{
		$module_srl = Context::get('module_srl');
		$content = '';

		$output = ModuleHandler::triggerCall('module.dispAdditionSetup', 'before', $content);
		
		Context::set('setup_content', $content);
		$this->setTemplateFile('editorsetting');
	}

	/**
	 * @brief display the grant information
	 **/
	function dispScheduleAdminGrantInfo()
	{
		// get the grant infotmation from admin module
		$oModuleAdminModel = getAdminModel('module');
		$grant_content = $oModuleAdminModel->getModuleGrantHTML($this->module_info->module_srl, $this->xml_info->grant);
		Context::set('grant_content', $grant_content);

		$this->setTemplateFile('grant_list');
	}

}
/* End of file */
