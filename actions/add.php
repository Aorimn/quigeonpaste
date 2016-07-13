<?php

global $ACT, $TITLE, $CONTENT, $TYPE, $DELAY, $ACL, $VSCSRF, $ID, $NS, $auth,
	$lang, $msg, $storage;

/* Check consistency */
if(!defined('QUIGON_ABS'))
	die('meh.');

if($ACT !== 'add')
	act_fallback($default_act);

if($TITLE === $lang['tpl_title'])
{
	$TITLE = '- -';
}

if(empty($CONTENT) or $CONTENT === $lang['tpl_content'])
{
	$msg->addMessage(sprintf($lang['add_miss_smthg'], 'content'), Msg::ERROR);
	act_fallback($default_act);
}

if(empty($DELAY))
{
	$msg->addMessage(sprintf($lang['add_miss_smthg'], 'delay'), Msg::ERROR);
	act_fallback($default_act);
}

if(empty($ACL))
{
	$msg->addMessage(sprintf($lang['add_miss_smthg'], 'acl'), Msg::ERROR);
	act_fallback($default_act);
}


/* Escape and convert to clean up inputs */
$content = $CONTENT;
// WARNING htmlentities() was used here, but GeSHi also does this job
$title = $TITLE;
// we could escape $title here, but we let the template do that as for $content


$type = '';
$geshi_lang = getGeSHiLanguages();
if(empty($TYPE) || in_array($TYPE, $geshi_lang))
	$type = $TYPE;
else
	$msg->addMessage(sprintf($lang['add_default_type'],
		htmlentities($TYPE, ENT_QUOTES, 'UTF-8')), Msg::WARNING);

$delay = textToSecond($DELAY);
if($delay <= time())
{
	$msg->addMessage(sprintf($lang['add_wrong_delay'],
		htmlentities($DELAY, ENT_QUOTES, 'UTF-8')), Msg::ERROR);
	act_fallback($default_act);
}

if($auth->checkACL($ACL) !== ERR_SUCCESS)
{
	$msg->addMessage(sprintf($lang['add_wrong_acl'],
		htmlentities($ACL, ENT_QUOTES, 'UTF-8')), Msg::ERROR);
	act_fallback($default_act);
}
$acl = $ACL;


/* Check the security token */
if(check_token($VSCSRF) !== ERR_SUCCESS)
{
	$msg->addMessage($lang['csrf'], Msg::ERROR);
	act_fallback($default_act);
}


/* Check whether the user has the right to post there */
if($auth->authorize($acl, $ID, $NS) !== ERR_SUCCESS)
{
	$msg->addMessage($lang['post_denied'], Msg::ERROR);
	act_fallback($default_act);
}

/* Check if the user is currently logged in */
$owner = null;
if($auth->isAuthenticated() === ERR_SUCCESS)
{
	/* If so, mark it as the owner */
	$owner = $auth->identifier();
}

/* Should the post be read only once or not? */
$once = false;
if(!empty($_POST['readonce']))
	$once = true;

/* Generate the ID */
$id = generateID();
$msg->addMessage("Generated ID: $id", Msg::DEBUG);

/* Check that the ID hasn't been generated before */
$_dummy = array();
while($storage->get_value($id, $NS, $_dummy) != ERR_NOT_FOUND)
{
	$id = generateID();
	$msg->addMessage("Generated ID: $id", Msg::DEBUG);
}


/* Create the paste's data */
$data = array(
	'id'      => $id,
	'ns'      => $NS,
	'title'   => $title,
	'content' => $content,
	'type'    => $type,
	'delay'   => $delay,
	'acl'     => $acl,
	'once'    => $once,
	'owner'   => $owner
);


/* Store data */
if($storage->store_value($id, $NS, $data) !== ERR_SUCCESS)
{
	$msg->addMessage($lang['add_store_error'], Msg::ERROR);
	act_fallback($default_act);
}

$url = getUrl($id, $NS);


/*
 * Give the next page the messages
 */
$msg->addMessage('----- Redirecting -----', Msg::DEBUG);
$msg->addMessage(sprintf($lang['paste_added'], $url), Msg::SUCCESS);
if($once)
	$msg->addMessage($lang['added_once'], Msg::SUCCESS);
$messages = $msg->getMessages(null, true);

$_SESSION['MSG_STATUS'] = $messages;


/*
 * Redirect the user to the new location
 */
if($once)
{
	$url = getUrl('', '');
	header("HTTP/1.1 303 See Other");
	header("Location: $url");
}
else
{
	/* The new location here is the new paste */
	header("HTTP/1.1 303 See Other");
	header("Location: $url");
}


//Setup VIM: ex: ts=4 noet :
