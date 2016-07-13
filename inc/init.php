<?php

if(!defined('QUIGON_ABS'))
	die('meh.');


if(!defined('QUIGON_INC'))
	define('QUIGON_INC', dirname(__FILE__).DIRECTORY_SEPARATOR);

if(!defined('QUIGON_CONF'))
	define('QUIGON_CONF', QUIGON_ABS.'conf'.DIRECTORY_SEPARATOR);

if(!defined('QUIGON_GESHI'))
	define('QUIGON_GESHI', QUIGON_ABS.'geshi'.DIRECTORY_SEPARATOR);


/* Init some useful arrays */
$conf = array();
$lang = array();


/* Include everything useful */
require(QUIGON_INC.'includes.php');


if(!defined('QUIGON_TPL'))
	define('QUIGON_TPL', $conf['backends'] . 'tpl' . DIRECTORY_SEPARATOR
		. $conf['tpl'] . DIRECTORY_SEPARATOR);

delquotes();


/* Init session */
$lifetime = 3 * 24 * 60 * 60;
$path     = QUIGON_REL;
$domain   = substr($_SERVER['SERVER_NAME'], 0, strpos($_SERVER['SERVER_NAME'], ','));
$secure   = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
$httponly = true;
session_set_cookie_params($lifetime, $path, $domain, $secure ,$httponly);

if(session_start() === FALSE)
	die('Unable to start session, aborting (check your disk space).');


/* Init a class for passing messages */
$msg = new Msg();


/* Damn timing things */
date_default_timezone_set($conf['timezone']);


/* Get potential parameters */
$ID  = empty($_REQUEST['id'])  ? '' : $_REQUEST['id'];
$ACT = empty($_REQUEST['act']) ? '' : $_REQUEST['act'];


$TITLE   = empty($_REQUEST['title'])   ? '' : $_REQUEST['title'];
$CONTENT = empty($_REQUEST['content']) ? '' : $_REQUEST['content'];
$TYPE    = empty($_REQUEST['type'])    ? '' : $_REQUEST['type'];
$DELAY   = empty($_REQUEST['delay'])   ? '' : $_REQUEST['delay'];
$ACL     = empty($_REQUEST['acl'])     ? '' : $_REQUEST['acl'];
$VSCSRF  = empty($_REQUEST['vscsrf'])  ? '' : $_REQUEST['vscsrf'];


/* Clean first inputs */
list($ID, $NS) = cleanID($ID);
     $ACT      = cleanAction($ACT);

$msg->addMessage("(ID|NS|ACT) after cleaning: ($ID|$NS|$ACT)", Msg::DEBUG);


/* Do little validation */
if(!is_string($TITLE))
	$TITLE   = '';
if(!is_string($CONTENT))
	$CONTENT = '';
if(!is_string($TYPE))
	$TYPE    = '';
if(!is_string($DELAY))
	$DELAY   = '';
if(!is_string($ACL))
	$ACL     = '';
if(!is_string($VSCSRF))
	$VSCSRF  = '';


//Setup VIM: ex: ts=4 noet :
