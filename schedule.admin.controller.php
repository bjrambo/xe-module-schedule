<?php
class scheduleAdminController extends schedule
{
	function init()
	{
	}

	function procScheduleAdminInsertMid()
	{
		$args = Context::getRequestVars();
		$module_info = moduleModel::getModuleInfoByModuleSrl($args->module_srl);
		$oModuleController = moduleController::getInstance();

		$args->order_target = $module_info->order_target ? : 'list_order';
		$args->order_type = $module_info->order_type ? : 'asc';
		$args->list_count = $module_info->list_count ? : 20;
		$args->search_list_count = $module_info->search_list_count ? : 20;
		$args->page_count = $module_info->page_count ? : 10;
		$args->mobile_list_count = $module_info->mobile_list_count ? : 10;
		$args->mobile_search_list_count = $module_info->mobile_search_list_count ? : 10;
		$args->mobile_page_count = $module_info->mobile_page_count ? : 5;

		if ( $args->module_srl )
		{
			$output = $oModuleController->updateModule($args);
			$msg_code = 'success_updated';
		}
		else
		{
			$output = $oModuleController->insertModule($args);
			$msg_code = 'success_registed';
		}

		if ( !$output->toBool() )
		{
			return $output;
		}

		$this->setMessage($msg_code);
		if ( $args->is_admin_module )
		{
			$this->setRedirectUrl(getNotEncodedUrl('', 'module', 'admin', 'act', 'dispScheduleAdminInsertModule', 'module_srl', $output->get('module_srl')));
		}
		else
		{
			$this->setRedirectUrl(getNotEncodedUrl('', 'mid', Context::get('mid'), 'act', 'dispScheduleAdminInsertModule'));
		}
	}

	function procScheduleAdminInsertConfig()
	{
		$schedule_config = scheduleModel::getScheduleConfig();
		$schedule_config->api = Context::get('api');

		$oModuleController = moduleController::getInstance();
		$oModuleController->updateModuleConfig('schedule', $schedule_config);

		$this->setMessage('success_updated');
		//TODO change to setRedirectUri.
		if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON')))
		{
			$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispScheduleAdminDashboard');
			header('location: ' . $returnUrl);
			return;
		}
	}

	/**
	 * @brief set schedule list configuration
	 **/
	function procScheduleAdminSetList($args = null)
	{
		$args = Context::getRequestVars();
		$module_info = moduleModel::getModuleInfoByModuleSrl($args->module_srl);
		$oModuleController = moduleController::getInstance();

		$module_info->order_target = $args->order_target;
		$module_info->order_type = $args->order_type;
		if ( !in_array($module_info->order_target, $this->order_target) )
		{
			$module_info->order_target = 'list_order';
		}
		if ( !in_array($module_info->order_type, array('asc', 'desc')) )
		{
			$module_info->order_type = 'asc';
		}
		$module_info->list_count = $args->list_count ? : 20;
		$module_info->search_list_count = $args->search_list_count ? : 20;
		$module_info->page_count = $args->page_count ? : 10;
		$module_info->mobile_list_count = $args->mobile_list_count ? : 10;
		$module_info->mobile_search_list_count = $args->mobile_search_list_count ? : 10;
		$module_info->mobile_page_count = $args->mobile_page_count ? : 5;

		$output = $oModuleController->updateModule($module_info);
		$msg_code = 'success_updated';

		if ( !$output->toBool() )
		{
			return $output;
		}

		$list = explode(',', $args->list);
		if ( count($list) )
		{
			$oModuleController->insertModulePartConfig('schedule', $output->get('module_srl'), $list);
		}

		$this->setMessage($msg_code);
		
		if ( Context::get('success_return_url') )
		{
			$this->setRedirectUrl(Context::get('success_return_url'));
		}
		else
		{
			$this->setRedirectUrl(getNotEncodedUrl('', 'module', 'admin', 'act', 'dispScheduleAdminAdditionSetup', 'module_srl', $output->get('module_srl')));
		}
	}

	function procScheduleAdminDeleteMid()
	{
		$module_srl = Context::get('module_srl');
		$oModuleController = getController('module');
		$output = $oModuleController->deleteModule($module_srl);

		if(!$output->toBool())
		{
			return $output;
		}

		$this->setMessage('success_deleted');
		$this->setRedirectUrl(getNotEncodedUrl('', 'module', 'admin', 'act', 'dispScheduleAdminDashboard'));
	}

	function procScheduleAdminSaveCategorySettings()
	{
		$module_srl = Context::get('module_srl');
		$module_info = moduleModel::getModuleInfoByModuleSrl($module_srl);

		$mid = Context::get('mid');
		if ( $module_info->mid != $mid )
		{
			throw new Rhymix\Framework\Exceptions\InvalidRequest;
		}

		$module_info->hide_category = Context::get('hide_category') == 'Y' ? 'Y' : 'N';
		$module_info->allow_no_category = Context::get('allow_no_category') == 'Y' ? 'Y' : 'N';

		$oModuleController = moduleController::getInstance();
		$output = $oModuleController->updateModule($module_info);
		if ( !$output->toBool() )
		{
			return $output;
		}

		$this->setMessage('success_updated');
		if ( Context::get('success_return_url') )
		{
			$this->setRedirectUrl(Context::get('success_return_url'));
		}
		else
		{
			$this->setRedirectUrl(getNotEncodedUrl('', 'module', 'admin', 'act', 'dispScheduleAdminCategoryInfo', 'module_srl', $output->get('module_srl')));
		}
	}
	
	// TODO: what is this? WT?
	function deleteModuleSchedule($module_srl)
	{
		$args = new stdClass;
		$args->list_count = 0;
		$args->module_srl = intval($module_srl);
		$schedule_list = executeQueryArray('schedule.getScheduleList', $args, array('schedule_srl'))->data;

		// delete documents
		$output = executeQuery('schedule.deleteModuleSchedule', $args);
		if ( !$output->toBool() )
		{
			return $output;
		}

		// remove from cache
		foreach ( $schedule_list as $schedule )
		{
			scheduleController::clearScheduleCache($schedule->document_srl);
		}

		return new BaseObject();
	}
}
/* End of file */
