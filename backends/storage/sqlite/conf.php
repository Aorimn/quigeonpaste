<?php

if(!defined('QUIGON_ABS'))
	die('meh.');


/*
 * DO NOT EDIT!!!
 * As this file will be overwritten at each update, it's unnecessary and
 * stupid to change it.
 * Instead, create a file named `conf/local.conf.php'. This last
 * file won't be overridden by any update so your parameters will be saved.
 */


/* In which file we'll store things */
$conf['storage_data_file'] = QUIGON_ABS . 'data' . DIRECTORY_SEPARATOR . 'pastes.db';

$conf['storage_data_perm'] = 0600;

/* How many last pastes you want to be shown, at max */
$conf['storage_nb_last']   = 10;


//Setup VIM: ex: ts=4 noet :
