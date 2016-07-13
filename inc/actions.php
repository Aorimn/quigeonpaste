<?php

if(!defined('QUIGON_ABS'))
	die('meh.');

/**
 * Dispatch the action
 *
 * @param $act Cleaned action to execute
 */
function act_dispatch($act)
{
	global $conf, $msg;

	/**
	 * $act is clean here due to cleanAction() defined in utils.php and called
	 * in init.php
	 */
	$path    = QUIGON_ABS . 'actions/' . $act . '.php';
	$default_act = QUIGON_ABS . 'actions/' . $conf['default_act'] . '.php';

	$msg->addMessage("Trying to load action $act...", Msg::DEBUG);

	if(file_exists($path))
		require($path);
	else
		require($default_act);
}


/**
 * Fallback wrapper function for actions
 *
 * @param $req Default require to perform
 * @param $exit (optional) Shall we exit in this function? (default: true)
 */
function act_fallback($req, $exit = true)
{
	// Fall back through the default action
	require($req);
	if($exit === true)
		exit(0);
}
