<?php

if(!defined('QUIGON_ABS'))
	die('meh.');

/**
 * Authentication using AriseID (OAuth)
 *
 * This class only requires the user's id
 * This also check for associations, for namespaces, in:
 *  owner
 *  master
 *  member
 */
class Auth_ariseid extends Auth_classic
{
	/** The consumer used for the authentication */
	protected $consumer;

	/** User's login and associations, if connected */
	protected $attributes;


	/**
	 * Constructor to initialize things
	 */
	function __construct()
	{
		global $msg;
		$msg->addMessage('AriseID auth constructed.', Msg::DEBUG);

		unset($this->consumer);
		$this->getConsumer();

		/* Will call the authenticate() method below */
		parent::__construct();
	}

	/**
	 * Authenticate user according to AriseID credentials
	 */
	public function authenticate()
	{
		global $ID, $NS, $lang, $msg;

		/*
		 * Called by parent's __construct() method, but we also want to call
		 * the parent's authenticate() method, so do it here
		 */
		parent::authenticate();

		$consumer = $this->getConsumer();

		$consumer->set_callback(OAuthAriseClient::getScriptURL());

		if(isset($_REQUEST['auth_logout']))
		{
			$consumer->logout();
			$this->attributes = array();
			unset($_SESSION['auth']['attributes']);
			$msg->addMessage($lang['auth_logout_ok'], Msg::SUCCESS);
		}

		if (isset($_POST['auth_login']))
		{
			$consumer->authenticate();
		}

		if($consumer->has_just_authenticated())
		{
			session_regenerate_id();
			$consumer->session_id_changed();

			if($this->populateAttributes())
			{
				$msg->addMessage($lang['auth_login_ok'], Msg::SUCCESS);
				$_SESSION['auth']['attributes'] = $this->attributes;
			}
			else
				$msg->addMessage($lang['auth_miss'], Msg::ERROR);

		}

		if($consumer->is_authenticated())
		{
			if(isset($_SESSION['auth']['attributes']))
				$this->attributes = $_SESSION['auth']['attributes'];
			else
				$this->populateAttributes();
		}
	}

	/**
	 * Return the fields
	 *
	 * @return Array of input fields (this need to include the submit button):
	 *  - [0] => 'id'    => ID of the first field
	 *           'name'  => name of this field
	 *           'type'  => type of this field
	 *           'value' => value of this field
	 *  - [1] => ...
	 * Return null if nothing is to be displayed
	 */
	public function getFormFields()
	{
		global $lang;
		$ret = array();

		global $msg;
		if(empty($this->attributes))
		{
			$msg->addMessage('No association for current user', Msg::DEBUG);
			$ret[] = array(
				'id'    => 'auth_login',
				'name'  => 'auth_login',
				'type'  => 'submit',
				'value' => $lang['auth_valid']
			);
		}
		else
		{
			$msg->addMessage("User's associations: '"
				. implode("', '", $this->attributes['assoces']) . "'",
				Msg::DEBUG);
			$ret[] = array(
				'id'    => 'auth_logout',
				'name'  => 'auth_logout',
				'type'  => 'submit',
				'value' => $lang['auth_logout']
			);
		}

		return $ret;
	}

	/**
	 * Authorize (or not) the access (view or addition) of pastes regarding the
	 * acl set when connected
	 *
	 * @param $acl The control set when archiving
	 * @param $id The ID of the paste the user want to view
	 * @param $ns The namespace used for this paste
	 * @param $context Visibility for everyone if set - @see storage::get_lasts
	 * @return Return one of the following code (from inc/errors.php):
	 *  - ERR_SUCCESS
	 *  - ERR_DENIED
	 */
	public function authorize($acl, $id, $ns, $context = null)
	{
		global $msg;

		/*
		 * Autorization from Auth_classic: if it's already refused, no need to
		 * do any further check
		 */
		if(($ret = parent::authorize($acl, $id, $ns, $context)) !== ERR_SUCCESS)
			return $ret;

		$msg->addMessage("User's associations: '"
			. implode("', '", $this->attributes['assoces']) . "'", Msg::DEBUG);

		/* Default namespace (empty namespace) is authorized */
		if(empty($ns))
			return ERR_SUCCESS;

		/* If requested NS is in the associations of the user, it's ok */
		if(in_array($ns, $this->attributes['assoces']))
		{
			if(is_null($context))
				return ERR_SUCCESS;
			else
				return ($acl === 'private') ? ERR_DENIED : ERR_SUCCESS;
		}
		else
			return ERR_DENIED;
	}

	/**
	 * Check if the user is authenticated
	 *
	 * @return Return one of the following code (from inc/errors.php):
	 *  - ERR_SUCCESS
	 *  - ERR_ERROR
	 */
	public function isAuthenticated()
	{
		if(isset($_SESSION['auth']['attributes']))
			return ERR_SUCCESS;
		return ERR_ERROR;
	}

	/**
	 * If the user is authenticated, return its identifier
	 *
	 * @return Return the user's identifier if s/he's authenticated, null
	 * otherwise
	 */
	public function identifier()
	{
		if(isset($_SESSION['auth'],
			$_SESSION['auth']['attributes'],
			$_SESSION['auth']['attributes']['ident'])
			)
			return $_SESSION['auth']['attributes']['ident'];
		return null;
	}

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
	// public function getACLs() | Use parent's

	/**
	 * Check if the given choice is within the ACL "range"
	 *
	 * @param $acl The ACL choice to evaluate
	 * @return Return one of the following code (from inc/errors.php):
	 *  - ERR_SUCCESS
	 *  - ERR_UNKNOWN
	 */
	// public function checkACL($acl) | Use parent's

	/**
	 * Return an example of a valid namespace for the user
	 *
	 * @return A string containing a valid namespace
	 */
	public function getAuthorizedNS()
	{
		if(empty($this->attributes))
			return array();
		else
			return $this->attributes['assoces'];
	}

	/**
	 * Return the consumer
	 *
	 * @return The consumer Object
	 */
	protected function getConsumer()
	{
		if(isset($this->consumer))
			return $this->consumer;

		global $conf;

		$consumer = \OAuthAriseClient::getInstance(
			$conf['auth_ariseid']['consumer_key'],
			$conf['auth_ariseid']['consumer_secret'],
			$conf['auth_ariseid']['consumer_private_key']
		);
		$this->consumer = $consumer;

		return $this->consumer;
	}

	/**
	 * Populate $this->attributes as per the user's given information
	 *
	 * @return True when the user's identifier has been retrieved, false
	 * otherwise
	 */
	protected function populateAttributes()
	{
		$consumer = $this->getConsumer();
		$login_ok = true;

		$results = $consumer->api()->begin()
			->get_identifiant()
			->get_assoce_member()
			->get_assoce_master()
			->get_assoce_owner()
			->done();

		$ident = '';
		$member = array();
		$master = array();
		$owner = array();

		/* Getting the user's login */
		try
		{
			$this->attributes['ident'] = $results[0]();
		}
		catch(\OAuthAPIException $e)
		{
			$login_ok = false;
		}

		/* Associations where the user is a member of */
		try
		{
			$member = $results[1]();
		}
		catch(\OAuthAPIException $e) { /* Do nothing */ }

		/* Associations where the user is a master of */
		try
		{
			$master = $results[2]();
		}
		catch(\OAuthAPIException $e) { /* Do nothing */ }

		/* Associations where the user is the owner */
		try
		{
			$owner = $results[3]();
		}
		catch(\OAuthAPIException $e) { /* Do nothing */ }

		$assoces = array_merge($member, $master, $owner);
		$this->attributes['assoces'] = array_unique($assoces);

		return $login_ok;
	}
}

//Setup VIM: ex: ts=4 noet :
