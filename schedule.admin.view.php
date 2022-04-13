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
			$module_info = moduleModel::getModuleInfoByModuleSrl($module_srl);
			if ( !$module_info )
			{
				Context::set('module_srl', '');
			}
			else
			{
				moduleModel::syncModuleToSite($module_info);
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

		$schedule_config = scheduleModel::getScheduleConfig();
		Context::set('schedule_config', $schedule_config);
		
		$module_categories = moduleModel::getModuleCategories();
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
		moduleModel::syncModuleToSite($output->data);

		Context::set('page', $output->page);
		Context::set('total_page', $output->total_page);
		Context::set('total_count', $output->total_count);
		Context::set('module_list', $output->data);
		Context::set('page_navigation', $output->page_navigation);

		$oModuleAdminModel = getAdminModel('module');
		$selected_manage_content = $oModuleAdminModel->getSelectedManageHTML($this->xml_info->grant, array('tab1'=>1, 'tab3'=>1), $this->module_path);
		Context::set('selected_manage_content', $selected_manage_content);

		$security = new Security();
		$security->encodeHTML('module_list..s_browser_title', 'module_list..s_mid');
	}

	function dispScheduleAdminScheduleList()
	{
		$args = new stdClass();
		$args->page = Context::get('page');
		$args->list_count = 30;
		$args->page_count = 5;
		
		$args->search_target = Context::get('search_target');
		$args->search_keyword = Context::get('search_keyword');
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

		$columnList = array('schedule_srl', 'module_srl', 'category_srl', 'title', 'regdate', 'status', 'uploaded_count', 'start_date', 'end_date', 'selected_date', 'is_allday', 'place', 'is_recurrence', 'nick_name', 'member_srl', 'email_address');
		// TODO is it not static.
		$output = scheduleModel::getScheduleList($args, $columnList);

		$status_list = scheduleModel::getScheduleStatusList();

		Context::set('total_count', $output->total_count);
		Context::set('total_page', $output->total_page);
		Context::set('page', $output->page);
		Context::set('schedule_list', $output->data);
		Context::set('status_list', $status_list);
		Context::set('page_navigation', $output->page_navigation);

		// check again. change to foreach
		$count_search_option = count($this->search_option);
		for ( $i = 0; $i < $count_search_option; $i++ )
		{
			$search_option[$this->search_option[$i]] = lang($this->search_option[$i]);
		}
		
		Context::set('search_option', $search_option);

		$module_list = array();
		$module_srls = array();
		foreach ( $output->data as $oSchedule )
		{
			$module_srls[] = $oSchedule->get('module_srl');
		}
		$module_srls = array_unique($module_srls);
		$module_srls_count = count($module_srls);
		if ( $module_srls_count )
		{
			$columnList = array('module_srl', 'mid', 'browser_title');
			$module_output = moduleModel::getModulesInfo($module_srls, $columnList);
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
		$skin_list = moduleModel::getSkins($this->module_path);
		Context::set('skin_list',$skin_list);

		$mskin_list = moduleModel::getSkins($this->module_path, 'm.skins');
		Context::set('mskin_list', $mskin_list);

		// get the layouts list
		$oLayoutModel = layoutModel::getInstance();
		$layout_list = $oLayoutModel->getLayoutInstanceList();
		Context::set('layout_list', $layout_list);

		$mobile_layout_list = $oLayoutModel->getLayoutInstanceList(0, 'M');
		Context::set('mlayout_list', $mobile_layout_list);

		$security = new Security();
		$security->encodeHTML('skin_list..title','mskin_list..title');
		$security->encodeHTML('layout_list..title','layout_list..layout');
		$security->encodeHTML('mlayout_list..title','mlayout_list..layout');
	}

	function dispScheduleAdminCategoryInfo()
	{
		$category_content = documentModel::getInstance()->getCategoryHTML(Context::get('module_srl'));
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
			Context::set('default_list_config', scheduleModel::getDefaultListConfig($this->module_info->module_srl));
			Context::set('list_config', scheduleModel::getListConfig($this->module_info->module_srl));
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
		$oModuleAdminModel = getAdminModel('module');
		$grant_content = $oModuleAdminModel->getModuleGrantHTML($this->module_info->module_srl, $this->xml_info->grant);
		Context::set('grant_content', $grant_content);
	}

	/**
	 * @brief display the module skin information
	 **/
	function dispScheduleAdminSkinInfo() {
		$oModuleAdminModel = getAdminModel('module');
		$skin_content = $oModuleAdminModel->getModuleSkinHTML($this->module_info->module_srl);
		Context::set('skin_content', $skin_content);
	}

	/**
	 * Display the module mobile skin information
	 **/
	function dispScheduleAdminMobileSkinInfo() {
		$oModuleAdminModel = getAdminModel('module');
		$skin_content = $oModuleAdminModel->getModuleMobileSkinHTML($this->module_info->module_srl);
		Context::set('skin_content', $skin_content);
	}

}
/* End of file */
