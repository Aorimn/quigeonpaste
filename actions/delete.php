<?php

global $ACT, $TITLE, $CONTENT, $TYPE, $DELAY, $ACL, $VSCSRF, $ID, $NS, $auth,
	$lang, $msg, $storage;

/* Check consistency */
if(!defined('QUIGON_ABS'))
	die('meh.');

if($ACT !== 'delete')
	act_fallback($default_act);

/* Check the security token */
if(check_token($VSCSRF) !== ERR_SUCCESS)
{
	$msg->addMessage($lang['csrf'], Msg::ERROR);
	act_fallback($default_act);
}

/* Deletion is only authorized if the user is logged in */
if($auth->isAuthenticated() !== ERR_SUCCESS)
	act_fallback($default_act);

$user = $auth->identifier();
$paste = array();

/* Try to get the paste to be deleted */
if($storage->get_value($ID, $NS, $paste) === ERR_NOT_FOUND)
{
	$msg->addMessage($lang['wrong_id'], Msg::ERROR);
	act_fallback($default_act);
}

/* Check the paste belongs to the currently logged-in user */
if(is_null($paste['owner']) || $paste['owner'] !== $user)
{
	$msg->addMessage($lang['delete_wrong_owner'], Msg::ERROR);
	act_fallback($default_act);
}

/* Delete the paste */
$storage->delete_value($ID, $NS);

/*
 * Give the next page the messages
 */
$msg->addMessage('----- Redirecting -----', Msg::DEBUG);
$msg->addMessage($lang['paste_deleted'], Msg::SUCCESS);
$messages = $msg->getMessages(null, true);

$_SESSION['MSG_STATUS'] = $messages;

/* Redirect the user to the application's root */
$url = getUrl('', '');
header("HTTP/1.1 303 See Other");
header("Location: $url");

//Setup VIM: ex: ts=4 noet :
