<?php

if(!defined('QUIGON_ABS'))
	die('meh.');


/**
 * Definition of constant for reporting statuses returned by functions
 */
define('ERR_SUCCESS', 0x00000000);
define('ERR_ERROR',   0x80000000);


define('ERR_CONVENTION',      ERR_ERROR | 0x1);
define('ERR_NOT_FOUND',       ERR_ERROR | 0x2);
define('ERR_DENIED',          ERR_ERROR | 0x4);
define('ERR_UNSUCCESSFUL',    ERR_ERROR | 0x8);
define('ERR_UNKNOWN',         ERR_ERROR | 0x10);
define('ERR_NOT_IMPLEMENTED', ERR_ERROR | 0x20);
define('ERR_LENGTH_MISMATCH', ERR_ERROR | 0x40);
define('ERR_NOT_IN_RANGE',    ERR_ERROR | 0x80);
define('ERR_EMPTY',           ERR_ERROR | 0x100);
define('ERR_EXPIRED',         ERR_ERROR | 0x200);



//Setup VIM: ex: ts=4 noet :
