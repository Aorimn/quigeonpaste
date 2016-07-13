<?php

if(!defined('QUIGON_ABS'))
	die('meh.');


/**
 * Base class for storage. Inherit this class for all storage means.
 */
interface Storage_basic
{
	/**
	 * Constructor to initialize things
	 */
	function __construct();


	// TODO add parents pastes in the schema (no need to hurry for that)
	/**
	 * Method to store pastes
	 *
	 * @param $id Id of the new paste to store
	 * @param $data Array containing the following members, if that matters:
	 *  - title   => title of the paste
	 *  - content => content of the paste
	 *  - type    => content type, as for the geshi plugin to highlight things
	 *  - delay   => delay before expiration (timestamp in second)
	 *  - acl     => access defined by the auth used
	 *  - once    => true or false, whether the paste is readable only once
	 *  - owner   => the paste owner's identifier, or null
	 * @return Return one of the following values (from inc/errors.php):
	 *  - ERR_SUCCESS
	 *  - ERR_UNSUCCESSFUL
	 */
	public function store_value($id, $ns, $data);

	/**
	 * Method to get pastes
	 *
	 * @param $id Id of the paste to get
	 * @param $ns Paste namespace
	 * @param $out_data Array to be filled inside the function if the result
	 * is not an error. Keys are the ones described above, with the same
	 * signification
	 * @return Return one of the following values (from inc/errors.php):
	 *  - ERR_SUCCESS
	 *  - ERR_NOT_FOUND
	 *  - ERR_EXPIRED
	 */
	public function get_value($id, $ns, &$out_data);

	/**
	 * Method to remove a specific paste, callable from other backends
	 *
	 * @param $id Id of the paste to get
	 * @param $ns Paste namespace
	 * @return Return one of the following values (from inc/errors.php):
	 *  - ERR_SUCCESS
	 *  - ERR_NOT_FOUND
	 */
	public function delete_value($id, $ns);

	/**
	 * Method to get last pastes, which can be viewed by *anyone*
	 * (i.e.: ACL are checked)
	 *
	 * @return Array of last pastes (empty if nothing):
	 *  - [0] => name  => name of the paste (= title)
	 *           delay => the date when the paste will expire (in seconds since
	 * Unix Epoch)
	 *  - [1] => ...
	 */
	public function get_lasts();

	/**
	 * Method to clean storage from old pastes
	 */
	public function clean_old();
}

//Setup VIM: ex: ts=4 noet :
