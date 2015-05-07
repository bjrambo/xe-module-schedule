<?php
class schedule extends ModuleObject
{
	var $triggers = array(
		//array('schedule.insertSchedule', 'schedule', 'controller', 'triggerAttachFiles', 'after'),
	);


	function moduleInstall()
	{
		return new Object();
	}

	function checkUpdate()
	{
		$oModuleModel = getModel('module');
		$oDB = &DB::getInstance();

		foreach($this->triggers as $trigger)
		{
			if(!$oModuleModel->getTrigger($trigger[0], $trigger[1], $trigger[2], $trigger[3], $trigger[4])) return true;
		}

		if(!$oDB->isColumnExists('schedule_list', 'first_time'))
		{
			return true;
		}

		if(!$oDB->isColumnExists('schedule_list', 'last_time'))
		{
			return true;
		}

		if(!$oDB->isColumnExists('schedule_list', 'module_srl'))
		{
			return true;
		}
		return false;
	}

	function moduleUpdate()
	{
		$oModuleModel = getModel('module');
		$oModuleController = getController('module');
		$oDB = &DB::getInstance();

		foreach($this->triggers as $trigger)
		{
			if(!$oModuleModel->getTrigger($trigger[0], $trigger[1], $trigger[2], $trigger[3], $trigger[4]))
			{
				$oModuleController->updateTrigger($trigger[0], $trigger[1], $trigger[2], $trigger[3], $trigger[4]);
			}
		}

		if(!$oDB->isColumnExists('schedule_list', 'first_time'))
		{
			$oDB->addColumn('schedule_list', 'first_time', 'varchar', 4, true);
		}

		if(!$oDB->isColumnExists('schedule_list', 'last_time'))
		{
			$oDB->addColumn('schedule_list', 'last_time', 'varchar', 4, true);
		}

		if(!$oDB->isColumnExists('schedule_list', 'module_srl'))
		{
			$oDB->addColumn('schedule_list', 'module_srl', 'varchar', 11, true);
		}
		return new Object(0, 'success_updated');
	}
}
/* End of file */
