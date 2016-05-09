<?php

class scheduleItem extends Object
{
	var $schdule_srl = 0;
	function scheduleGetThumbnail($schedule_srl, $width = 80, $height = 0, $thumbnail_type = 'ratio')
	{

		// Return false if the document doesn't exist
		if(!$schedule_srl) return;


		// If not specify its height, create a square

		$output = getModel('schedule')->getSchedule($schedule_srl);
		$content = $output->content;


		// Define thumbnail information
		$thumbnail_path = sprintf('files/cache/schdule/thumbnails/%s',getNumberingPath($schedule_srl, 3));
		$thumbnail_file = sprintf('%s%dx%d.%s.jpg', $thumbnail_path, $width, $height, $thumbnail_type);
		$thumbnail_url  = Context::getRequestUri().$thumbnail_file;

		// Return false if thumbnail file exists and its size is 0. Otherwise, return its path
		if(file_exists($thumbnail_file))
		{
			if(filesize($thumbnail_file) < 1)
			{
				return FALSE;
			}
			else
			{
				return $thumbnail_url . '?' . date('YmdHis', filemtime($thumbnail_file));
			}
		}

		// Target File
		$source_file = null;
		$is_tmp_file = false;
		$upload_count = getModel('file')->getFilesCount($schedule_srl);
		// Find an iamge file among attached files if exists
		if($upload_count)
		{
			$file_list = getModel('file')->getFiles($schedule_srl, array(), 'file_srl', true);
			debugPrint($file_list);

			$first_image = null;
			foreach($file_list as $file)
			{
				if($file->direct_download !== 'Y') continue;

				if($file->cover_image === 'Y' && file_exists($file->uploaded_filename))
				{
					$source_file = $file->uploaded_filename;
					break;
				}

				if($first_image) continue;

				if(preg_match("/\.(jpe?g|png|gif|bmp)$/i", $file->source_filename))
				{
					if(file_exists($file->uploaded_filename))
					{
						$first_image = $file->uploaded_filename;
					}
				}
			}

			if(!$source_file && $first_image)
			{
				$source_file = $first_image;
			}
		}

		if($source_file)
		{
			$output = FileHandler::createImageFile($source_file, $thumbnail_file, $width, $height, 'jpg', $thumbnail_type);

		}

		// Remove source file if it was temporary
		if($is_tmp_file)
		{
			FileHandler::removeFile($source_file);
		}

		// Return the thumbnail path if it was successfully generated
		if($output)
		{
			return $thumbnail_url . '?' . date('YmdHis');
		}
		// Create an empty file if thumbnail generation failed
		else
		{
			FileHandler::writeFile($thumbnail_file, '','w');
		}

		return;
	}
}