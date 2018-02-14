<?php

if(!defined('QUIGON_ABS'))
	die('meh.');

/**
 * Check that the configuration is good regarding the hash algorithm to use
 */
if(!in_array($conf['hash'][0], hash_algos()))
	die('Algorithm ' . $conf['hash'][0] . ' not supported.');

$algo_len = strlen(hash($conf['hash'][0], ""));
if($algo_len != $conf['hash'][1])
	die('Wrong algorithm length in configuration. It should be ' . $algo_len);



/**
 * Create the hash used to store and get a paste
 *
 * @param $data Array containing any number of pair (key, value)
 * @return Return the hash of the parameter concatenated with some random values
 */
function getHash($data)
{
	global $conf;

	$tohash = '';
	foreach($data as $key => $value)
		$tohash .= $key . $value;

	$tohash .= openssl_random_pseudo_bytes(42);

	return hash($conf['hash'][0], uniqid($tohash, true));
}


/**
 * Generate the ID used to store and get pastes
 *
 * @return Return a random string, suitable to put in the URL
 */
function generateID()
{
	global $conf;

	if(function_exists('random_int'))
	{
		$id = '';
		$alpha = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
		$max = strlen($alpha) - 1;

		for($i = 0; $i < $conf['id_length']; $i++)
			$id .= $alpha[random_int(0, $max)];

		return $id;
	}

	$bytes = base64_encode(openssl_random_pseudo_bytes($conf['id_length']));
	return str_replace(        // Replace
		array('+', '/', '='),  // characters of this array
		chr(mt_rand(97, 122)), // by a random lowercase ascii character
		$bytes
	);
}


/**
 * Check the id when it's without any namespace
 *
 * @param $id The ID to check
 * @return Return one of the following code (from inc/errors.php):
 *  - ERR_SUCCESS
 *  - ERR_LENGTH_MISMATCH
 *  - ERR_UNSUCCESSFUL
 *  - ERR_EMPTY
 */
function _checkID($id)
{
	global $conf;

	if(empty($id) || !is_string($id))
		return ERR_EMPTY;

	/* Check the length, it should be greater or equals than the number of random chars */
	if(strlen($id) < $conf['id_length'])
		return ERR_LENGTH_MISMATCH;

	/* Now check the format, it should be a 'base64 string minus /+=' */
	$m = preg_match('/^[0-9a-z]+$/iD', $id);
	if($m === FALSE || $m === 0)
		return ERR_UNSUCCESSFUL;

	return ERR_SUCCESS;
}


/**
 * Check the namespace validity
 *
 * @param $ns The namespace to check
 * @return Return one of the following code (from inc/errors.php):
 *  - ERR_SUCCESS
 *  - ERR_UNSUCCESSFUL
 *  - ERR_EMPTY
 */
function _checkNS($ns)
{
	if(empty($ns) || !is_string($ns))
		return ERR_EMPTY;

	/*
	 * Check the format, it should be a valid string
	 * (do not authorize the . caracter at any cost)
	 */
	$m = preg_match('/^[0-9a-z:_-]+$/i', $ns, $matches);
	if($m === FALSE || $m === 0)
		return ERR_UNSUCCESSFUL;

	return ERR_SUCCESS;
}


/**
 * Clean the raw ID of a paste
 *
 * @param $id The raw ID getted from user input (maybe including namespace)
 * @return Array containing the following members:
 *  - [0] => the cleaned ID (or empty if it wasn't clean before)
 *  - [1] => the namespace of the paste (can be an empty string)
 */
function cleanID($id)
{
	global $conf, $lang, $msg;
	$ret = array('', '');

	$msg->addMessage('ID before cleaning: ['.$id.']', Msg::DEBUG);
	if(empty($id))
		return $ret;

	if(strpos($id, '/') !== false)
	{
		/* If the ID seems to have a namespace */
		list($ns, $id) = explode('/', $id, 2);

		switch(_checkNS($ns))
		{
			case ERR_SUCCESS:
				$ret[1] = $ns;
				break;
			case ERR_UNSUCCESSFUL:
				$msg->addMessage($lang['wrong_ns'], Msg::DEBUG);
				break;
			case ERR_EMPTY:
				$msg->addMessage($lang['no_ns'], Msg::DEBUG);
				break;
		}
	}

	switch(_checkID($id))
	{
		case ERR_SUCCESS:
			$ret[0] = $id;
			break;
		case ERR_EMPTY:
			$msg->addMessage($lang['no_id'], Msg::DEBUG);
			break;
		case ERR_UNSUCCESSFUL:
			$msg->addMessage($lang['wrong_id'], Msg::DEBUG);
			$msg->addMessage($lang['wrong_id'], Msg::ERROR);
			break;
		case ERR_LENGTH_MISMATCH:
			$msg->addMessage($lang['invalid_length_id'], Msg::DEBUG);
			$msg->addMessage($lang['wrong_id'], Msg::ERROR);
			break;
	}

	return $ret;
}


/**
 * Clean the raw action
 *
 * @param $act The raw action provided by the user
 * @return The action to perform (falling back to 'show' if not recognized)
 */
function cleanAction($act)
{
	global $conf;

	if(!is_string($act))
		return $conf['default_act'];

	$act = strtolower($act);
	if(in_array($act, $conf['actions']))
		return $act;

	return $conf['default_act'];
}


/**
 * Return an array of acceptable delays
 *
 * @return cf the text describing this function and don't play stupid
 */
function getDelays()
{
	global $lang, $conf;
	return array(
		'default' => $conf['default_delay'],
		'values' => array(
			'1h' => $lang['time_1h'], // 1 hour
			'1d' => $lang['time_1d'], // 1 day
			'3d' => $lang['time_3d'], // 3 days
			'1w' => $lang['time_1w'], // 1 week
			'1m' => $lang['time_1m'], // 1 month
			'3m' => $lang['time_3m'], // 3 months
			'1y' => $lang['time_1y'], // 1 year
		)
	);
}


/**
 * Tranform the given text into seconds
 *
 * @param $text The text to tranform into seconds (taking reference of time())
 * @return The number of seconds since the Unix Epoch
 * @note Unix Epoch is January 1 1970 00:00:00 GMT
 */
function textToSecond($text)
{
	$delay = time();

	switch($text)
	{
		case '1h':
			$delay += 60 * 60;
			break;
		case '1d':
			$delay += 60 * 60 * 24;
			break;
		case '3d':
			$delay += 60 * 60 * 24 * 3;
			break;
		case '1w':
			$delay += 60 * 60 * 24 * 7;
			break;
		case '1m':
			$delay += 60 * 60 * 24 * 30;
			break;
		case '3m':
			$delay += 60 * 60 * 24 * 30 * 3;
			break;
		case '1y':
			$delay += 60 * 60 * 24 * 30 * 12;
			break;
	}

	return $delay;
}


/**
 * Transform given seconds into text
 *
 * @param $seconds The seconds to transform
 * @return The text defined by $conf['date_format']
 */
function secondToText($seconds)
{
	global $conf;
	return strftime($conf['date_format'], $seconds);
}


/**
 * Get GeSHi supported languages
 *
 * @return Array of supported languages by GeSHi
 */
function getGeSHiLanguages()
{
	static $ret = array();

	if(!empty($ret))
		return $ret;

	$dir = opendir(QUIGON_GESHI.'geshi');
	if($dir === FALSE)
		return $ret;

	$entry = readdir($dir);
	while($entry !== false)
	{
		if(substr($entry, -4) === '.php')
			$ret[] = substr($entry, 0, -4);

		$entry = readdir($dir);
	}

	closedir($dir);
	sort($ret, SORT_STRING);
	return $ret;
}


/**
 * Get a URL from an ID and a namespace
 *
 * @param $id The ID
 * @param $ns The namespace
 * @return A string containing the absolute URL formed with $id and $ns
 */
function getUrl($id, $ns)
{
	$dir = QUIGON_REL;

	$dir = str_replace('\\','/',$dir);             // bugfix for weird WIN behaviour
	$dir = preg_replace('#//+#','/',"/$dir/");     // ensure leading and trailing slashes

	$tail = $id;
	if(!empty($ns))
		$tail = $ns.'/'.$id;


	/* split hostheader into host and port */
	$port = null;
	if(isset($_SERVER['HTTP_HOST']))
	{
		$parsed_host = parse_url('http://'.$_SERVER['HTTP_HOST']);
		$host = $parsed_host['host'];
		if(isset($parsed_host['port']))
			$port = $parsed_host['port'];
	}
	elseif(isset($_SERVER['SERVER_NAME']))
	{
		$parsed_host = parse_url('http://'.$_SERVER['SERVER_NAME']);
		$host = $parsed_host['host'];
		if(isset($parsed_host['port']))
			$port = $parsed_host['port'];
	}
	else
	{
		$host = php_uname('n');
	}

	if(is_null($port) && isset($_SERVER['SERVER_PORT']))
		$port = $_SERVER['SERVER_PORT'];

	if(is_null($port))
		$port = '';

	if(!is_ssl())
	{
		$proto = 'http://';
		if($port == '80')
			$port = '';
	}
	else
	{
		$proto = 'https://';
		if($port == '443')
			$port = '';
	}

	if($port !== '')
		$port = ':'.$port;

    return $proto.$host.$port.$dir.$tail;
}


/**
 * Check if accessed via HTTPS
 *
 * Apache leaves $_SERVER['HTTPS'] empty when not available, IIS sets it to
 * 'off'. 'false' and 'disabled' are just guessing.
 *
 * @returns bool true when SSL is active
 */
function is_ssl(){
	if(!isset($_SERVER['HTTPS']) ||
			preg_match('/^(|off|false|disabled)$/i', $_SERVER['HTTPS']))
		return false;
	else
		return true;
}


/**
 * Return a token to be used for CSRF attack prevention
 * (call check_token() before this function)
 *
 * @return A string representing the token
 */
function get_token()
{
	global $msg;

	static $token = null;

	if(!is_null($token))
	{
		return $token;
	}

	$_SESSION['token'] = getHash(array(
		uniqid($_SERVER['REMOTE_ADDR'].$_SERVER['REMOTE_PORT'], true) =>
			session_id()
	));

	$msg->addMessage(sprintf('Anti CSRF token: %s', $_SESSION['token']),
		Msg::DEBUG);

	$token = $_SESSION['token'];
	return $_SESSION['token'];
}


/**
 * Check the token according to the one in memory
 *
 * @return ERR_SUCCESS or ERR_UNSUCCESSFUL depending on the token validity
 */
function check_token($token)
{
	global $msg;
	$msg->addMessage(sprintf('Anti CSRF, comparing tokens: %s | %s',
		$_SESSION['token'], $token), Msg::DEBUG);

	if($token === $_SESSION['token'])
		return ERR_SUCCESS;

	return ERR_UNSUCCESSFUL;
}


/**
 * This piece of code deletes escapes inserted by PHP when magic_quotes_gpc is
 * enabled.
 * This functionnality is deprecated since PHP 5.3.0
 *
 * @source http://php.net/manual/en/security.magicquotes.disabling.php
 */
function delquotes()
{
	if(function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc())
	{
		$process = array(&$_GET, &$_POST, &$_COOKIE, &$_REQUEST);
		while(list($key, $val) = each($process))
		{
			foreach($val as $k => $v)
			{
				unset($process[$key][$k]);
				if (is_array($v))
				{
					$process[$key][stripslashes($k)] = $v;
					$process[] = &$process[$key][stripslashes($k)];
				}
				else
				{
					$process[$key][stripslashes($k)] = stripslashes($v);
				}
			}
		}
		unset($process);
	}
}



//Setup VIM: ex: ts=4 noet :
