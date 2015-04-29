<?php
class scheduleController extends schedule
{
	function init()
	{
	}

	function procScheduleInsertSchedule()
	{
		// check grant
		if($this->module_info->module != "schedule")
		{
			return new Object(-1, "msg_invalid_request");
		}
		if(!$this->grant->write_schedule)
		{
			return new Object(-1, 'msg_not_permitted');
		}

		$obj = Context::getRequestVars();
		$schedule_srl = Context::get('schedule_srl');
		$logged_info = Context::get('logged_info');

		$args = new stdClass();
		$args->selected_date = $obj->selected_date;
		$args->schedule_name = $obj->schedule_name;
		$args->schedule_content = $obj->content;
		$args->member_srl = $logged_info->member_srl;
		$args->regdate = date('YmdHis');

		$oScheduleModel = getModel('schedule');
		$schedule = $oScheduleModel->getSchedule($schedule_srl);

		if(!$schedule->schedule_srl)
		{
			if(!$schedule_srl)
			{
				$schedule_srl = getNextSequence();
			}
			$args->schedule_srl = $schedule_srl;
			$output = executeQuery('schedule.insertSchedule', $args);

			if($output->toBool())
			{
				$tai_output = ModuleHandler::triggerCall('schedule.insertSchedule', 'after', $args);
				if(!$tai_output->toBool())
				{
					$oDB->rollback();
					return $tai_output;
				}
				$this->AttachFiles($args);
			}
		}
		else
		{
			$args->schedule_srl = $schedule_srl;
			$output = executeQuery('schedule.updateSchedule', $args);

			if($output->toBool())
			{
				$tau_output = ModuleHandler::triggerCall('schedule.updateSchedule', 'after', $args);
				if(!$tau_output->toBool())
				{
					$oDB->rollback();
					return $tau_output;
				}
				$this->AttachFiles($args);
			}

		}


		if(!in_array(Context::getRequestMethod(), array('XMLRPC', 'JSON')))
		{
			if($config->viewconfig != 'Y')
			{
				$redirectUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'mid', $this->module_info->mid);
			}
			else
			{
				$redirectUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'mid', $this->module_info->mid, 'act', 'dispScheduleSchedule', 'schedule_srl', $args->schedule_srl);
			}

			$this->setRedirectUrl($redirectUrl);
		}
	}

	function procScheduleDeleteSchedule()
	{
		$logged_info = Context::get('logged_info');
		$obj = Context::getRequestVars();

		if($logged_info->is_admin != 'Y')
		{
			return new Object(-1, '등록 및 삭제는 관리자만 가능합니다.');
		}

		$oScheduleModel = getModel('schedule');
		$schedule = $oScheduleModel->getSchedule($obj->schedule_srl);
		if($logged_info->member_srl != $schedule->member_srl && $logged_info->is_admin != 'Y') return new Object(-1, '삭제 할 권한이 없습니다.');

		if(!$obj->schedule_srl) return new Object(-1, '스케줄 번호는 필수 입니다.');

		$args = new stdClass();
		$args->schedule_srl = $obj->schedule_srl;
		$output = executeQuery('schedule.deleteSchedule', $args);

		if(!$output->toBool())
		{
			$oDB->rollback();
			return $output;
		}
		$oFileController = getController('file');
		$oFileController->deleteFiles($obj->schedule_srl);

		$this->setMessage('삭제 완료요~~');

		if(!in_array(Context::getRequestMethod(), array('XMLRPC', 'JSON')))
		{
			$redirectUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'mid', $this->module_info->mid);
			$this->setRedirectUrl($redirectUrl);
		}
	}

	function AttachFiles($obj)
	{
		$schedule_srl = $obj->schedule_srl;
		if(!$schedule_srl) return new Object();

		$oFileController = getController('file');
		$output = $oFileController->setFilesValid($schedule_srl);
		if(!$output->toBool()) return $output;

		return new Object();
	}
}
