<?php

if(!defined('QUIGON_ABS'))
	die('meh.');


/**
 * Base class for authentication. Inherit this class for all authentication
 * means.
 */
interface Auth_basic
{
	/**
	 * Constructor to initialize things
	 */
	function __construct();

	/**
	 * Authenticate user according to credentials (or none)
	 */
	public function authenticate();

	/**
	 * Return the fields of the login/logout form
	 *
	 * @return Array of input fields (this need to include the submit button):
	 *  - [0] => 'id'    => ID of the first field
	 *           'name'  => name of this field
	 *           'type'  => type of this field
	 *           'value' => value of this field
	 *  - [1] => ...
	 * Return null if nothing is to be displayed
	 */
	public function getFormFields();

	/**
	 * Authorize (or not) the access (view or addition) of pastes regarding the
	 * acl
	 *
	 * @param $acl The control set when archiving
	 * @param $id The ID of the paste the user want to view
	 * @param $ns The namespace used for this paste
	 * @param $context Visibility for everyone if set - @see storage::get_lasts
	 * @return Return one of the following code (from inc/errors.php):
	 *  - ERR_SUCCESS
	 *  - ERR_DENIED
	 */
	public function authorize($acl, $id, $ns, $context = null);

	/**
	 * Check if the user is authenticated
	 *
	 * @return Return one of the following code (from inc/errors.php):
	 *  - ERR_SUCCESS
	 *  - ERR_ERROR
	 */
	public function isAuthenticated();

	/**
	 * If the user is authenticated, return its identifier
	 *
	 * @return Return the user's identifier if s/he's authenticated, null
	 * otherwise
	 */
	public function identifier();

	/**
	 * Get an array describing ACLs to propose to the user
	 *
	 * @return Array composed of:
	 *  - [0] => 'multi' => boolean, set multi choices or not
	 *
	 *  - [1] => 'value' => value to set
	 *           'text'  => text to set
	 *           'title' => basic explication for each entry
	 *  - [2] => ... (same as 1, and other entries also)
	 *
	 * Note that 0 is used for configuration, others are used to display
	 * choices to the user
	 */
	public function getACLs();

	/**
	 * Check if the given choice is within the ACL "range"
	 *
	 * @param $acl The ACL choice to evaluate
	 * @return Return one of the following code (from inc/errors.php):
	 *  - ERR_SUCCESS
	 *  - ERR_UNKNOWN
	 */
	public function checkACL($acl);

	/**
	 * Return the authorized namespaces for the user
	 *
	 * @return An array of strings containing valid namespaces
	 */
	public function getAuthorizedNS();
}

//Setup VIM: ex: ts=4 noet :
