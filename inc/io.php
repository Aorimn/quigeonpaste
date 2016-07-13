<?php

if(!defined('QUIGON_ABS'))
	die('meh.');

/**
 * Create or override a file with a specific content
 * 
 * @param $file The path and the name of the file to save
 * @param $content The content to put into the file
 * @return Return one of the following code (from inc/errors.php):
 *  - ERR_SUCCESS
 *  - ERR_UNSUCCESSFUL
 */
function io_saveFile($file, $content)
{
	global $conf, $msg;
	
	$fh = fopen($file, 'w', false);
	if($fh === false)
	{
		$msg->addMessage('Cannot open the file "'.$file.'".', Msg::DEBUG);
		return ERR_UNSUCCESSFUL;
	}
	
	/* Lock the file */
	if(flock($fh, LOCK_EX) === false)
	{
		fclose($fh);
		@unlink($file);
		
		$msg->addMessage('Cannot lock the file "'.$file.'".', Msg::DEBUG);
		return ERR_UNSUCCESSFUL;
	}
	
	/* Delete all its previous content */
	ftruncate($fh, 0);
	
	/* And put the new one into */
	$nb_bytes = fwrite($fh, $content);
	if($nb_bytes === FALSE)
	{
		flock($fh, LOCK_UN);
		fclose($fh);
		@unlink($file);
		
		$msg->addMessage('Cannot write to the file "'.$file.'".', Msg::DEBUG);
		return ERR_UNSUCCESSFUL;
	}
	
	if($nb_bytes !== strlen($content))
	{
		flock($fh, LOCK_UN);
		fclose($fh);
		@unlink($file);
		
		$msg->addMessage('Cannot write the entire message to the file "'
			. $file . '".', Msg::DEBUG);
		return ERR_UNSUCCESSFUL;
	}
	
	/* Now unlock and close */
	flock($fh, LOCK_UN);
	fclose($fh);
	@chmod($file, $conf['fmode']);
	
	return ERR_SUCCESS;
}


/**
 * Delete a file onto the filesystem
 */
function io_delFile($file)
{
	@unlink($file);
}


/**
 * Create a directory on the file system if it doesn't exist yet
 * 
 * @param $dir The directory to create
 * @return Return one of the following code (from inc/errors.php):
 *  - ERR_SUCCESS
 *  - ERR_UNSUCCESSFUL
 */
function io_createDir($dir)
{
	if(is_dir($dir))
		return ERR_SUCCESS;
	
	global $conf;
	if(mkdir($dir, $conf['dmode'], true))
		return ERR_SUCCESS;
	
	global $msg;
	$msg->addMessage('Cannot create the directory "'.$dir.'".', Msg::DEBUG);
	
	return ERR_UNSUCCESSFUL;
}


//Setup VIM: ex: ts=4 noet :
