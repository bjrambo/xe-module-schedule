<?php
class scheduleAdminView extends schedule
{
	function init()
	{
		$oModuleModel = getModel('module');
		$oScheduleModel = getModel('schedule');
		$module_srl = Context::get('module_srl');

		if(!$module_srl && $this->module_srl)
		{
			$module_srl = $this->module_srl;
			Context::set('module_srl', $module_srl);
		}

		if($module_srl)
		{
			$module_info = $oScheduleModel->getScheduleInfo($module_srl);
			if(!$module_info)
			{
				Context::set('module_srl', '');
			}
			else
			{
				$oModuleModel->syncModuleToSite($module_info);
				$module_info->mid_list = explode('|@|', $module_info->mid_list);
				$module_info->notify_list = explode('|@|', $module_info->notify_list);
				$this->module_info = $module_info;
				Context::set('module_info', $module_info);
			}
		}
		if($module_info && $module_info->module != 'schedule')
		{
			return $this->stop('msg_invalid_request');
		}

		$module_config = $oScheduleModel->getConfig();
		$module_category = $oModuleModel->getModuleCategories();

		$order_target = array();
		if(is_array($this->order_target))
		{
			foreach($this->order_target as $key)
			{
				$order_target[$key] = Context::getLang($key);
			}
		}

		$order_target['list_order'] = Context::getLang('regdate');
		$order_target['update_order'] = Context::getLang('last_update');
		
		Context::set('module_config', $module_config);
		Context::set('module_category', $module_category);
		Context::set('order_target', $order_target);

		$security = new Security();
		$security->encodeHTML('module_info.');
		$security->encodeHTML('module_config.');
		$security->encodeHTML('module_category..');

		$this->setTemplatePath($this->module_path . 'tpl');
		$this->setTemplateFile(strtolower(str_replace('dispScheduleAdmin', '', $this->act)));
	}

	function dispScheduleAdminDashboard()
	{
		$args = new stdClass();
		$args->sort_index = 'module_srl';
		$args->list_count = 20;
		$args->page_count = 10;
		$args->page = Context::get('page');
		$args->module_category_srl = Context::get('module_category_srl');

		$search_target = Context::get('search_target');
		$search_keyword = Context::get('search_keyword');
		
		switch($search_target)
		{
			case 'mid':
				$args->mid = $search_keyword;
				break;
			case 'browser_title':
				$args->browser_title = $search_keyword;
				break;
		}

		$oModuleModel = getModel('module');

		$output = executeQueryArray('schedule.getScheduleMidList', $args);
		$oModuleModel->syncModuleToSite($output->data);

		$skin_list = $oModuleModel->getSkins($this->module_path);
		$mskin_list = $oModuleModel->getSkins($this->module_path, 'm.skins');

		$oLayoutModel = getModel('layout');
		$layout_list = $oLayoutModel->getLayoutList();
		$mlayout_list = $oLayoutModel->getLayoutList(0, 'M');

		Context::set('page', $output->page);
		Context::set('total_page', $output->total_page);
		Context::set('total_count', $output->total_count);
		Context::set('skin_list', $skin_list);
		Context::set('mskin_list', $mskin_list);
		Context::set('layout_list', $layout_list);
		Context::set('mlayout_list', $mlayout_list);
		Context::set('module_list', $output->data);
		Context::set('page_navigation', $output->page_navigation);

		$oModuleAdminModel = getAdminModel('module');
		$selected_manage_content = $oModuleAdminModel->getSelectedManageHTML($this->xml_info->grant, array('tab1'=>1, 'tab3'=>1));
		Context::set('selected_manage_content', $selected_manage_content);

		$security = new Security();
		$security->encodeHTML('module_list..browser_title', 'module_list..mid');
		$security->encodeHTML('skin_list..title', 'mskin_list..title');
		$security->encodeHTML('layout_list..title', 'layout_list..layout');
		$security->encodeHTML('mlayout_list..title', 'mlayout_list..layout');
	}

	function dispScheduleAdminInsertModule()
	{
		if(!in_array($this->module_info->module, array('admin', 'schedule', 'blog','guestbook')))
		{
			return $this->alertMessage('msg_invalid_request');
		}

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

		// get document status list
		$oDocumentModel = getModel('document');
		$documentStatusList = $oDocumentModel->getStatusNameList();
		Context::set('document_status_list', $documentStatusList);

		$oBoardModel = getModel('board');

		// setup the extra vaiables
		$extra_vars = $oBoardModel->getDefaultListConfig($this->module_info->module_srl);
		Context::set('extra_vars', $extra_vars);

		// setup the list config (install the default value if there is no list config)
		Context::set('list_config', $oBoardModel->getListConfig($this->module_info->module_srl));

		// setup extra_order_target
		$module_extra_vars = $oDocumentModel->getExtraKeys($this->module_info->module_srl);
		$extra_order_target = array();
		foreach($module_extra_vars as $oExtraItem)
		{
			$extra_order_target[$oExtraItem->eid] = $oExtraItem->name;
		}
		Context::set('extra_order_target', $extra_order_target);

		$security = new Security();
		$security->encodeHTML('extra_vars..name','list_config..name');
	}

	function dispScheduleAdminDeleteMid()
	{
		$module_srl = Context::get('module_srl');
		
		if(!$module_srl)
		{
			return $this->setRedirectUrl(getNotEncodedUrl('', 'module', 'admin', 'act', 'dispScheduleAdminDashboard'));
		}
		
		$security = new Security();
		$security->encodeHTML('module_info..module', 'module_info..mid');
		
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

	function dispScheduleAdminDeleteNoModuleSrlSchedule()
	{

	}

}
/* End of file */
