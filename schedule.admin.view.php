<?php
class scheduleAdminView extends schedule
{
	function init()
	{
		$module_srl = Context::get('module_srl');

		if ( !$module_srl && $this->module_srl )
		{
			$module_srl = $this->module_srl;
			Context::set('module_srl', $module_srl);
		}

		if ( $module_srl )
		{
			$module_info = ModuleModel::getModuleInfoByModuleSrl($module_srl);
			if ( !$module_info )
			{
				Context::set('module_srl', '');
			}
			else
			{
				ModuleModel::syncModuleToSite($module_info);
				$module_info->mid_list = explode('|@|', $module_info->mid_list);
				$module_info->notify_list = explode('|@|', $module_info->notify_list);
				$this->module_info = $module_info;
				Context::set('module_info', $module_info);
			}
		}
		if ( $module_info && $module_info->module != 'schedule' )
		{
			return $this->stop('msg_invalid_request');
		}

		$schedule_config = ScheduleModel::getScheduleConfig();
		Context::set('schedule_config', $schedule_config);
		
		$module_categories = ModuleModel::getModuleCategories();
		Context::set('module_categories', $module_categories);

		$order_target = array();
		if ( is_array($this->order_target) )
		{
			foreach ( $this->order_target as $key )
			{
				$order_target[$key] = lang($key);
			}
		}
		$order_target['list_order'] = lang('schedule_srl');
		Context::set('order_target', $order_target);

		$security = new Security();
		$security->encodeHTML('module_info.');
		$security->encodeHTML('schedule_config.');
		$security->encodeHTML('module_categories.');

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
		
		switch ( $search_target )
		{
			case 'browser_title':
				$args->s_browser_title = $search_keyword;
				break;
			case 'mid':
				$args->s_mid = $search_keyword;
				break;
		}

		$output = executeQueryArray('schedule.getScheduleMidList', $args);
		ModuleModel::syncModuleToSite($output->data);

		Context::set('page', $output->page);
		Context::set('total_page', $output->total_page);
		Context::set('total_count', $output->total_count);
		Context::set('module_list', $output->data);
		Context::set('page_navigation', $output->page_navigation);

		$oModuleAdminModel = getAdminModel('module');
		$selected_manage_content = $oModuleAdminModel->getSelectedManageHTML($this->xml_info->grant, array('tab1'=>1, 'tab3'=>1));
		Context::set('selected_manage_content', $selected_manage_content);

		$security = new Security();
		$security->encodeHTML('module_list..s_browser_title', 'module_list..s_mid');
	}

	function dispScheduleAdminScheduleList()
	{
		// option to get a list
		$args = new stdClass();
		$args->page = Context::get('page'); // /< Page
		$args->list_count = 30; // /< the number of posts to display on a single page
		$args->page_count = 5; // /< the number of pages that appear in the page navigation
		
		$args->search_target = Context::get('search_target'); // /< search (title, contents ...)
		$args->search_keyword = Context::get('search_keyword'); // /< keyword to search
		if ( $args->search_target === 'member_srl' )
		{
			$logged_info = Context::get('logged_info');
			if ( $logged_info->is_admin === 'Y' || intval($logged_info->member_srl) === intval($args->search_keyword) )
			{
				$args->member_srl = intval($args->search_keyword);
				unset($args->search_target, $args->search_keyword);
			}
		}

		$args->sort_index = 'list_order'; // /< sorting value
		$args->module_srl = Context::get('module_srl');
		$args->status = Context::get('status');

		// get a list
		$columnList = array('schedule_srl', 'module_srl', 'category_srl', 'title', 'regdate', 'status', 'uploaded_count', 'start_date', 'end_date', 'selected_date', 'is_allday', 'place', 'is_recurrence', 'nick_name', 'member_srl', 'email_address');
		$output = ScheduleModel::getScheduleList($args, $columnList);

		// get Status name list
		$status_list = ScheduleModel::getScheduleStatusList();

		// Set values of schedule_model::getScheduleList() objects for a template
		Context::set('total_count', $output->total_count);
		Context::set('total_page', $output->total_page);
		Context::set('page', $output->page);
		Context::set('schedule_list', $output->data);
		Context::set('status_list', $status_list);
		Context::set('page_navigation', $output->page_navigation);

		// set a search option used in the template
		$count_search_option = count($this->search_option);
		for ( $i = 0; $i < $count_search_option; $i++ )
		{
			$search_option[$this->search_option[$i]] = lang($this->search_option[$i]);
		}
		Context::set('search_option', $search_option);

		// Module List
		$module_list = array();
		$mod_srls = array();
		foreach ( $output->data as $oSchedule )
		{
			$mod_srls[] = $oSchedule->get('module_srl');
		}
		$mod_srls = array_unique($mod_srls);
		$mod_srls_count = count($mod_srls);
		if ( $mod_srls_count )
		{
			$columnList = array('module_srl', 'mid', 'browser_title');
			$module_output = ModuleModel::getModulesInfo($mod_srls, $columnList);
			if ( $module_output && is_array($module_output) )
			{
				foreach ( $module_output as $module )
				{
					$module_list[$module->module_srl] = $module;
				}
			}
		}
		Context::set('module_list', $module_list);

		$security = new Security();
		$security->encodeHTML('search_target', 'search_keyword');
	}

	function dispScheduleAdminInsertModule()
	{
		if ( !in_array($this->module_info->module, array('admin', 'schedule')) )
		{
			return $this->alertMessage('msg_invalid_request');
		}

		// get the skins list
		$skin_list = ModuleModel::getSkins($this->module_path);
		Context::set('skin_list',$skin_list);

		$mskin_list = ModuleModel::getSkins($this->module_path, 'm.skins');
		Context::set('mskin_list', $mskin_list);

		// get the layouts list
		$oLayoutModel = getModel('layout');
		$layout_list = $oLayoutModel->getLayoutList();
		Context::set('layout_list', $layout_list);

		$mobile_layout_list = $oLayoutModel->getLayoutList(0, 'M');
		Context::set('mlayout_list', $mobile_layout_list);

		$security = new Security();
		$security->encodeHTML('skin_list..title','mskin_list..title');
		$security->encodeHTML('layout_list..title','layout_list..layout');
		$security->encodeHTML('mlayout_list..title','mlayout_list..layout');
	}

	function dispScheduleAdminCategoryInfo()
	{
		$oDocumentModel = getModel('document');
		$category_content = $oDocumentModel->getCategoryHTML(Context::get('module_srl'));
		Context::set('category_content', $category_content);
		Context::set('module_info', $this->module_info);
	}

	function dispScheduleAdminDeleteMid()
	{
		$module_srl = Context::get('module_srl');
		
		if ( !$module_srl )
		{
			return $this->setRedirectUrl(getNotEncodedUrl('', 'module', 'admin', 'act', 'dispScheduleAdminDashboard'));
		}
		
		$security = new Security();
		$security->encodeHTML('module_info..module', 'module_info..mid');
		
	}

	function dispScheduleAdminAdditionSetup()
	{
		// setup the list config (install the default value if there is no list config)
		if ( $this->module_info->use_list == 'Y' )
		{
			// setup the extra vaiables
			Context::set('default_list_config', ScheduleModel::getDefaultListConfig($this->module_info->module_srl));
			Context::set('list_config', ScheduleModel::getListConfig($this->module_info->module_srl));
		}

		$content = '';

		$oCommentView = getView('comment');
		$oCommentView->triggerDispCommentAdditionSetup($content);

		$oEditorView = getView('editor');
		$oEditorView->triggerDispEditorAdditionSetup($content);

		$oFileView = getView('file');
		$oFileView->triggerDispFileAdditionSetup($content);

		Context::set('setup_content', $content);

		$security = new Security();
		$security->encodeHTML('additionsetup..');
		$security->encodeHTML('list_config..name');
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
	}

	/**
	 * @brief display the module skin information
	 **/
	function dispScheduleAdminSkinInfo() {
		 // get the grant infotmation from admin module
		$oModuleAdminModel = getAdminModel('module');
		$skin_content = $oModuleAdminModel->getModuleSkinHTML($this->module_info->module_srl);
		Context::set('skin_content', $skin_content);
	}

	/**
	 * Display the module mobile skin information
	 **/
	function dispScheduleAdminMobileSkinInfo() {
		 // get the grant infotmation from admin module
		$oModuleAdminModel = getAdminModel('module');
		$skin_content = $oModuleAdminModel->getModuleMobileSkinHTML($this->module_info->module_srl);
		Context::set('skin_content', $skin_content);
	}

}
/* End of file */
