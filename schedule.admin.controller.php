<?php
class scheduleAdminController extends schedule
{
	function init()
	{
	}

	function procScheduleAdminInsertMid()
	{
		$oModuleModel = getModel('module');
		$oModuleController = getController('module');

		$args = Context::getRequestVars();
		$args->module = 'schedule';
		if($args->module_srl)
		{
			$module_info = $oModuleModel->getModuleInfoByModuleSrl($args->module_srl);
			if($module_info->module_srl != $args->module_srl)
			{
				unset($args->module_srl);
			}
		}
		if($args->module_srl)
		{
			$output = $oModuleController->updateModule($args);
			$msg_code = 'success_updated';
		}
		else
		{
			$output = $oModuleController->insertModule($args);
			$msg_code = 'success_registed';
		}

		if(!$output->toBool())
		{
			return $output;
		}

		$this->setMessage($msg_code);

		if(!in_array(Context::getRequestMethod(), array('XMLRPC', 'JSON')))
		{
			$redirectUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispScheduleAdminInsertModule', 'module_srl', $module_srl);
			$this->setRedirectUrl($redirectUrl);
		}
	}

	function procScheduleAdminInsertConfig()
	{
		$oModuleController = getController('module');
		$config->viewconfig = Context::get('viewconfig');

		$this->setMessage('success_updated');

		$oModuleController->updateModuleConfig('schedule', $config);

		if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON')))
		{
			$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispScheduleAdminConfig');
			header('location: ' . $returnUrl);
			return;
		}
	}

	function procScheduleAdminDeleteMid()
	{
		$module_srl = Context::get('module_srl');

		$oModuleModel = getModel('module');
		$module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl);

		$oModuleController = getController('module');
		$output = $oModuleController->deleteModule($module_srl);

		if(!$output->toBool())
		{
			return $output;
		}

		$this->setMessage('success_deleted');
		$this->setRedirectUrl(getNotEncodedUrl('', 'module', 'admin', 'act', 'dispScheduleAdminDashboard'));
	}

	function procScheduleAdminDeleteNoModuleSrlSchedule()
	{
		$args = new stdClass();
		$args->module_srl = 1;
		$output = executeQuery('schedule.deleteNoModuleSrlDeleted', $args);

		$this->setMessage('success_deleted');
		$this->setRedirectUrl(getNotEncodedUrl('', 'module', 'admin', 'act', 'dispScheduleAdminDeleteNoModuleSrlSchedule'));
	}
}
/* End of file */
