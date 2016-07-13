<?php

if(!defined('QUIGON_ABS'))
	die('meh.');


/**
 * SQLite storage class.
 */
class Storage_sqlite implements Storage_basic
{
	protected $dbh = NULL;

	protected $schema_version = 0;

	/**
	 * Constructor to initialize things
	 */
	function __construct()
	{
		global $conf;

		$this->dbh = new SQLite3($conf['storage_data_file']);
		$this->check_schema_version();
	}

	function __destruct()
	{
		if($this->dbh)
			$this->dbh->close();
	}

	protected function check_schema_version()
	{
		$stm = 'SELECT current FROM version;';
		$db_version = $this->dbh->querySingle($stm);
		if($db_version === false)
		{
			$this->init_schema();
			return;
		}

		if($this->schema_version < $db_version)
		{
			die('Update the code: your database version is newer than the one supported by this code.');
		}
		elseif($this->schema_version > $db_version)
		{
			$this->update_schema($db_version);
		}
	}

	protected function init_schema()
	{
		$this->update_schema(-1);

		// Init the types table
		foreach(getGeSHiLanguages() as $geshilang)
		{
			$stm = 'INSERT INTO types VALUES (:type)';
			$pstmt = $this->dbh->prepare($stm);
			$pstmt->bindValue(':type', $geshilang, SQLITE3_TEXT);
			if($pstmt->execute() === false)
				die("Cannot add type '$geshilang' into database.");
		}
	}

	protected function update_schema($curr_db_version)
	{
		for($i = $curr_db_version + 1; $i <= $this->schema_version; $i++)
		{
			$db_file = "backends/storage/sqlite/db/$i.sql";
			$stm = file_get_contents($db_file);
			if($stm === false)
			{
				die("The file $db_file doesn't exist, which suggests a broken installation.");
			}

			$res = $this->dbh->exec($stm);
			if($res === false)
				die("Cannot execute query $stm.");
		}
	}

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
	public function store_value($id, $ns, $data)
	{
		$ns_id = $this->get_ns_id_from_name($ns);
		$type_id = $this->get_type_id_from_name($data['type']);

		$delay = $data['delay'];
		if($data['once'] === true)
			$delay = 0;

		$stm = 'INSERT INTO pastes VALUES (:id, :ns, :title, :content, :type, '
			. ' :delay, :acl, :once, :owner)';
		$pstmt = $this->dbh->prepare($stm);
		$pstmt->bindValue(':id',      $id,              SQLITE3_TEXT);
		$pstmt->bindValue(':ns',      $ns_id,           SQLITE3_INTEGER);
		$pstmt->bindValue(':title',   $data['title'],   SQLITE3_TEXT);
		$pstmt->bindValue(':content', $data['content'], SQLITE3_TEXT);
		$pstmt->bindValue(':type',    $type_id,         SQLITE3_INTEGER);
		$pstmt->bindValue(':delay',   $data['delay'],   SQLITE3_INTEGER);
		$pstmt->bindValue(':acl',     $data['acl'],     SQLITE3_TEXT);
		$pstmt->bindValue(':once',    $data['once'],    SQLITE3_INTEGER);
		$pstmt->bindValue(':owner',   $data['owner'],   SQLITE3_TEXT);

		if($pstmt->execute() === false)
		{
			$msg->addMessage('Cannot save paste into the database', Msg::DEBUG);
			return ERR_UNSUCCESSFUL;
		}

		return ERR_SUCCESS;
	}

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
	public function get_value($id, $ns, &$out_data)
	{
		global $msg;

		if(empty($id))
		{
			$msg->addMessage('Empty ID given, not looking for a file.',
				Msg::DEBUG);
			return ERR_NOT_FOUND;
		}

		$ns_id = $this->get_ns_id_from_name($ns);

		$stm = 'SELECT * FROM pastes WHERE ns=:ns AND id=:id';
		$pstmt = $this->dbh->prepare($stm);
		$pstmt->bindValue(':ns', $ns_id, SQLITE3_INTEGER);
		$pstmt->bindValue(':id', $id,    SQLITE3_TEXT);

		$res = $pstmt->execute();
		if($res === false)
		{
			$msg->addMessage('Select query to the database returned FALSE.',
				Msg::DEBUG);
			return ERR_NOT_FOUND;
		}
		$out_data = $res->fetchArray(SQLITE3_ASSOC);
		if(empty($out_data))
		{
			$msg->addMessage('Found empty result in database.', Msg::DEBUG);
			return ERR_NOT_FOUND;
		}

		if(time() >= $out_data['delay'])
		{
			/* When the paste as expired, don't give it */
			$msg->addMessage("The paste of ID \"$ns/$id\" has expired since "
				. secondToText($out_data['delay']),
				Msg::DEBUG);

			$out_data = array();
			return ERR_EXPIRED;
		}

		if($data['type'] === 0)
			$type_name =  '';
		else
			$type_name = $this->get_type_name_from_id($data['type']);

		$out_data['type'] = $type_name;
		$out_data['once'] = $out_data['once'] === 1 ? true : false;
		$out_data['ns'] = $this->get_ns_name_from_id($data['ns']);

		return ERR_SUCCESS;
	}


	/**
	 * Method to remove a specific paste, callable from other backends
	 *
	 * @param $id Id of the paste to get
	 * @param $ns Paste namespace
	 * @return Return one of the following values (from inc/errors.php):
	 *  - ERR_SUCCESS
	 *  - ERR_NOT_FOUND
	 */
	public function delete_value($id, $ns)
	{
		global $msg;

		$ns_id = $this->get_ns_id_from_name($ns);

		$stm = 'DELETE FROM pastes WHERE id=:id AND ns=:ns';
		$pstmt = $this->dbh->prepare($stm);
		$pstmt->bindValue(':id', $id,    SQLITE3_TEXT);
		$pstmt->bindValue(':ns', $ns_id, SQLITE3_INTEGER);
		if($pstmt->execute() === false)
		{
			$msg->addMessage('Delete query to the database returned FALSE.',
				Msg::DEBUG);
			return ERR_NOT_FOUND;
		}

		return ERR_SUCCESS;
	}

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
	public function get_lasts()
	{
		global $auth, $conf, $msg;

		$ret = array();

		$stm = 'SELECT * FROM pastes JOIN namespaces '
			. 'ON pastes.ns = namespaces.rowid ORDER BY delay ASC';
		$res = $this->dbh->query($stm);
		if($res === false)
		{
			$msg->addMessage(
				'Select query for last pastes to the database returned FALSE.',
				Msg::DEBUG
				);
			return ERR_NOT_FOUND;
		}

		$max = $conf['storage_nb_last'];
		$cnt = 0;
		$curr_time = time();

		while(($data = $res->fetchArray(SQLITE3_ASSOC)) !== false)
		{
			/* If the paste has expired, do not show it */
			if($curr_time >= $data['delay'])
				continue;

			if($auth->authorize(
				$data['acl'],
				$data['id'],
				$data['name'], // namespace name
				$data['type']) === ERR_SUCCESS)
			{
				$ret[] = array(
					'link'  => getUrl($data['id'], $data['name']),
					'name'  => $data['title'],
					'delay' => $data['delay']
					);
				$cnt++;

				if($cnt >= $max)
					break;
			}
		}

		return $ret;
	}

	/**
	 * Method to clean storage from old pastes
	 */
	public function clean_old()
	{
		// FIXME shouldn't be run if a transaction for clean_old is already running
		$this->dbh->query('BEGIN TRANSACTION');
		$stm = 'SELECT id, ns FROM pastes WHERE delay < ' . time();
		$res = $this->dbh->query($stm);

		while(($data = $res->fetchArray(SQLITE3_ASSOC)) !== FALSE)
		{
			$stm = 'DELETE FROM pastes WHERE id=:id AND ns=:ns';
			$pstmt = $this->dbh->prepare($stm);
			$pstmt->bindValue(':id', $data['id'], SQLITE3_TEXT);
			$pstmt->bindValue(':ns', $data['ns'], SQLITE3_INTEGER);
			$pstmt->execute();
		}
		$this->dbh->query('COMMIT TRANSACTION');

		return;
	}

	/** Note that it also add the ns name into the table if not encountered */
	protected function get_ns_id_from_name($ns_name)
	{
		$stm = 'SELECT rowid FROM namespaces WHERE name=:nsname';
		$pstmt = $this->dbh->prepare($stm);
		$pstmt->bindValue(':nsname', $ns_name, SQLITE3_TEXT);

		$ns_id = $pstmt->execute()->fetchArray(SQLITE3_ASSOC);
		$rowid = (int) $ns_id['rowid'];
		if($rowid === 0)
		{
			// If the namespace doesn't exist yet, insert it
			$stm = 'INSERT INTO namespaces VALUES (:nsname)';
			$pstmt = $this->dbh->prepare($stm);
			$pstmt->bindValue(':nsname', $ns_name, SQLITE3_TEXT);
			$pstmt->execute();

			$stm = 'SELECT rowid FROM namespaces WHERE name=:nsname';
			$pstmt = $this->dbh->prepare($stm);
			$pstmt->bindValue(':nsname', $ns_name, SQLITE3_TEXT);

			$ns_id = $pstmt->execute()->fetchArray(SQLITE3_ASSOC);
			$rowid = (int) $ns_id['rowid'];
		}

		return $rowid;
	}

	protected function get_ns_name_from_id($ns_id)
	{
		$stm = 'SELECT name FROM namespaces WHERE rowid=:nsid';
		$pstmt = $this->dbh->prepare($stm);
		$pstmt->bindValue(':nsid', $ns_id, SQLITE3_INTEGER);

		$ns_name = $pstmt->execute()->fetchArray(SQLITE3_ASSOC);
		return (string) $ns_name['name'];
	}

	protected function get_type_id_from_name($type_name)
	{
		$stm = 'SELECT rowid FROM types WHERE name=:typename';
		$pstmt = $this->dbh->prepare($stm);
		$pstmt->bindValue(':typename', $type_name, SQLITE3_TEXT);

		$type_id = $pstmt->execute()->fetchArray(SQLITE3_ASSOC);
		return (int) $type_id['rowid'];
	}

	protected function get_type_name_from_id($type_id)
	{
		$stm = 'SELECT name FROM types WHERE rowid=:typeid';
		$pstmt = $this->dbh->prepare($stm);
		$pstmt->bindValue(':typeid', $type_id, SQLITE3_INTEGER);

		$type_name = $pstmt->execute()->fetchArray(SQLITE3_ASSOC);
		return $type_name['name'];
	}
}

//Setup VIM: ex: ts=4 noet :
