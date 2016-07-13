<?php

global $ACT, $ID, $NS, $auth, $lang, $msg, $storage;


if(!defined('QUIGON_ABS'))
	die('meh.');

if($ACT !== 'export_raw')
	act_fallback($default_act);


$data = array();
if($storage->get_value($ID, $NS, $data) !== ERR_SUCCESS)
{
	$msg->addMessage($lang['not_found'], Msg::ERROR);
	act_fallback($default_act);
}

if($auth->authorize($data['acl'], $ID, $NS) !== ERR_SUCCESS)
{
	$msg->addMessage($lang['denied'], Msg::ERROR);
	act_fallback($default_act);
}


$title = preg_replace('#""#', '', $data['title']);


/*
 * Table looted from the GeSHi plugin, with some addition from my part
 * (function get_language_name_from_extension())
 */
$lookup = array(
	'actionscript' => array('as'),
	'ada' => array('a', 'ada', 'adb', 'ads'),
	'apache' => array('conf'),
	'asm' => array('ash', 'asm'),
	'asp' => array('asp'),
	'bash' => array('sh'),
	'c' => array('c', 'h'),
	'c_mac' => array('c', 'h'),
	'caddcl' => array(),
	'cadlisp' => array(),
	'cdfg' => array('cdfg'),
	'cpp' => array('cpp', 'h', 'hpp'),
	'csharp' => array(),
	'css' => array('css'),
	'delphi' => array('dpk', 'dpr'),
	'html4strict' => array('html', 'htm'),
	'java' => array('java'),
	'javascript' => array('js'),
	'lisp' => array('lisp'),
	'lua' => array('lua'),
	'mpasm' => array(),
	'mysql' => array('sql'),
	'nsis' => array(),
	'objc' => array(),
	'oobas' => array(),
	'oracle8' => array(),
	'pascal' => array('pas'),
	'perl' => array('pl', 'pm'),
	'php' => array('php', 'php5', 'phtml', 'phps'),
	'python' => array('py'),
	'qbasic' => array('bi'),
	'sas' => array('sas'),
	'smarty' => array(),
	'vb' => array('bas'),
	'vbnet' => array(),
	'visualfoxpro' => array(),
	'xml' => array('xml')
);


$ext = (empty($data['type']) || empty($lookup[$data['type']])) ?
	'.txt' : '.'.$lookup[$data['type']][0];

header('Content-Type: text/plain; charset=utf-8');
header('Content-Disposition: filename="' . $title . $ext . '"');

echo $data['content'];


//Setup VIM: ex: ts=4 noet :
