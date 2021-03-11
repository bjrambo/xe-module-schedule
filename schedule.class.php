<?php
class schedule extends ModuleObject
{
	/**
	 * Search option to use in admin page
	 * @var array
	 */
	var $search_option = array('title', 'content', 'title_content', 'nick_name', 'start_date', 'end_date', 'selected_date', 'place'); // 검색 옵션
	var $order_target = array('list_order', 'category_srl', 'title', 'selected_date', 'nick_name', 'place', 'regdate'); // 정렬 옵션

	var $triggers = array(
		array('module.deleteModule', 'schedule', 'controller', 'triggerDeleteModuleSchedules', 'after'),
		//array('schedule.insertSchedule', 'schedule', 'controller', 'triggerAttachFiles', 'after'),
	);


	function moduleInstall()
	{
		// Register action forward (to use in administrator mode)
		$oModuleController = getController('module');

		$oDB = &DB::getInstance();
		$oDB->addIndex("schedules", "idx_uploaded_count", array("module_srl", "uploaded_count"));

		$oModuleController->insertTrigger($trigger[0], $trigger[1], $trigger[2], $trigger[3], $trigger[4]);

		FileHandler::makeDir('./files/cache/schedule');
		return new BaseObject();
	}

	function checkUpdate()
	{
		$oModuleModel = getModel('module');
		$oDB = &DB::getInstance();

		// 2021.02.20 Add a column(schedules) for getting thumbnails
		if(!$oDB->isColumnExists('schedules', 'uploaded_count')) return true;

		// 2021.02.20 Add a column(uploaded_count)
		if(!$oDB->isIndexExists("schedules", "idx_uploaded_count")) return true;

		foreach($this->triggers as $trigger)
		{
			if(!$oModuleModel->getTrigger($trigger[0], $trigger[1], $trigger[2], $trigger[3], $trigger[4])) return true;
		}

		return false;
	}

	function moduleUpdate()
	{
		$oModuleModel = getModel('module');
		$oModuleController = getController('module');
		$oDB = &DB::getInstance();

		// 2021.02.20 Add a column(schedules) for getting thumbnails
		if(!$oDB->isColumnExists('schedules', 'uploaded_count'))
		{
			$oDB->addColumn('schedules', 'uploaded_count', 'number', 11, 0);
		}

		// 2021.02.20 Add a column(uploaded_count)
		if(!$oDB->isIndexExists("schedules", "idx_uploaded_count"))
		{
			$oDB->addIndex('schedules', 'idx_uploaded_count', array('module_srl', 'uploaded_count'));
			$args = new stdClass();
			$args->list_count = 999999;
			$output = ScheduleModel::getScheduleList($args);
			if ( $output->toBool() &&  $result = $output->data )
			{
				foreach ( $result as $oSchedule )
				{
					$oScheduleController = getController('schedule');
					$oScheduleController->updateUploadedCount($oSchedule->schedule_srl);
				}
			}
		}

		foreach($this->triggers as $trigger)
		{
			if(!$oModuleModel->getTrigger($trigger[0], $trigger[1], $trigger[2], $trigger[3], $trigger[4]))
			{
				$oModuleController->insertTrigger($trigger[0], $trigger[1], $trigger[2], $trigger[3], $trigger[4]);
			}
		}

		return new BaseObject(0, 'success_updated');
	}
}
/* End of file */
