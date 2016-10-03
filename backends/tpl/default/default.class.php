<?php

if(!defined('QUIGON_ABS'))
	die('meh.');

/**
 * Default class for templates. Using plain text files.
 */
class Tpl_default implements Tpl_basic
{
	/** Holder of the GeSHi object */
	protected $geshi;

	/** Current paste if not null */
	protected $curr_paste;

	/** Paste to display */
	protected $code;

	/**
	 * Constructor to initialize things
	 */
	function __construct()
	{
		global $ID, $NS, $auth, $lang, $msg, $storage;

		$this->curr_paste = null;
		$this->code       = '';

		$data = array();
		$res = $storage->get_value($ID, $NS, $data);
		if($res !== ERR_SUCCESS)
		{
			$msg->addMessage(sprintf($lang['tpl_debug_storage'], $ID, $NS, $res), Msg::DEBUG);
			if(!empty($ID))
				$this->curr_paste =
					'<p class="pasteerror">'.$lang['tpl_not_found'].'</p>';
			return;
		}

		if($auth->authorize($data['acl'], $ID, $NS) !== ERR_SUCCESS)
		{
			$this->curr_paste =
				'<p class="pasteerror">'.$lang['tpl_denied'].'</p>';
			return;
		}

		$this->curr_paste = $data;
		$hl = $this->get_highlight_lines($this->curr_paste['content']);

		if(!empty($data['type']))
		{
			$this->geshi = new GeSHi($data['content'], $data['type']);
			$this->geshi->enable_classes();
			$this->geshi->enable_line_numbers(GESHI_FANCY_LINE_NUMBERS);
			$this->geshi->set_header_type(GESHI_HEADER_DIV);
			$this->geshi->set_source($this->curr_paste['content']);
			$this->geshi->highlight_lines_extra($hl);
			$this->code = $this->geshi->parse_code();
		}
		else
		{
			$content = htmlentities($this->curr_paste['content'],
							ENT_QUOTES, 'UTF-8');
			$content = explode("\n", $content);
			$max = count($content);
			$this->code .= "<ol id='code_content'>\n";
			for($i = 0; $i < $max; $i++)
			{
				$l = trim($content[$i], "\n\r\0\x0B");
				if(empty($l))
					$l = '&nbsp;';
				else
					$l = $this->indent($l);
				$class = (($i+1)%5) == 0 ? ' class="li2' : ' class="li1';
				if(in_array($i+1, $hl))
					$class .= ' highlight';
				$class .= '"';
				$this->code .= "	<li$class><pre>$l</pre></li>\n";
			}
			$this->code .= "</ol>\n";
		}

		if(is_array($this->curr_paste) && in_array('once', $this->curr_paste))
		{
			$id = $this->curr_paste['id'];
			$ns = $this->curr_paste['ns'];
			$storage->delete_value($id, $ns);
			$msg->addMessage($lang['tpl_deleted'], Msg::INFO);
		}
	}

	/**
	 * Just give the hand to the template manager
	 */
	public function display()
	{
		global $lang;
		require(QUIGON_TPL . 'main.php');
	}

	/**
	 * Display the title used between \<title\> tag
	 */
	public function displayTitle()
	{
		global $conf;
		echo $conf['title'];
	}

	/**
	 * Display the registered messages
	 */
	public function displayMessages()
	{
		global $conf, $msg;

		$debug_c   = 'msg_debug';
		$error_c   = 'msg_error';
		$warn_c    = 'msg_warn';
		$info_c    = 'msg_info';
		$success_c = 'msg_success';

		$a = array(
			Msg::ERROR   => $error_c,
			Msg::WARNING => $warn_c,
			Msg::INFO    => $info_c,
			Msg::SUCCESS => $success_c
		);

		$messages = $msg->getMessages(null, true);

		if($conf['debug'])
		{
			foreach($messages[Msg::DEBUG] as $m)
				echo "<div class='$debug_c'>$m</div>\n";

		}

		unset($messages[Msg::DEBUG]);

		foreach($messages as $key => $lm)
		{
			$class = 'msg_unknown';
			if(isset($a[$key]))
				$class = $a[$key];

			foreach($lm as $m)
				echo "<div class='$class'>$m</div>\n";
		}
	}

	/**
	 * Display the login form or id
	 */
	public function displayLogin()
	{
		global $ID, $NS, $auth, $lang;

		$fields = $auth->getFormFields();
		if(is_null($fields))
			return;

		$url = getUrl($ID, $NS);
		echo "<form action='$url' method='POST' class='auth'>";

		foreach($fields as $field)
		{
			echo '<input type="'.$field['type'].'" ';
			echo 'id="'.$field['id'].'" ';
			echo 'name="'.$field['name'].'" ';
			echo 'value="'.$field['value'].'" ';

			if($field['type'] === 'submit')
				echo 'class="button auth_entry" />';
			else
				echo 'class="auth_entry" />';
		}

		if($auth->isAuthenticated())
		{
			$auth_ns = $auth->getAuthorizedNS();
			if(empty($auth_ns))
				$auth_ns = array($lang['tpl_ns_example']);
			$ex_url = getUrl('', $auth_ns[0]);
			$instr = sprintf($lang['tpl_ns_instruction'], $ex_url);
			echo "<span>$instr</span>";
		}
		echo "</form>\n";

		return;
	}

	/**
	 * Function to display GeSHi style sheet
	 */
	protected function displayGeSHiStyle()
	{
		if(is_array($this->curr_paste) && !empty($this->curr_paste['type']))
			echo $this->geshi->get_stylesheet();
	}

	/**
	 * Display the last public-visible pastes
	 */
	public function displayLastPastes()
	{
		global $lang, $storage;

		$lasts = $storage->get_lasts();

		if(count($lasts) === 0)
		{
			echo '<p class="pasteerror">'.$lang['tpl_no_last_paste'].'</p>';
			return;
		}

		foreach($lasts as $last)
		{
			$l = $last['link'];
			$n = $last['name'];
			$d = $last['delay'];

			$long_n = $n;
			if(strlen($n) > 35)
			{
				$n = substr($n, 0, 35);
				$n .= '...';
			}

			$n      = htmlentities($n,      ENT_QUOTES, 'UTF-8');
			$long_n = htmlentities($long_n, ENT_QUOTES, 'UTF-8');

			if(empty($n))
				$n = $lang['tpl_empty'];

			echo "			<p class='lastpaste'>\n";
			echo "			<a title='$long_n' href='$l'>\n";
			echo "				<span class='lastpasteName'>$n</span><br>\n";
			echo "				<span class='lastpasteDelay'>";
			echo $lang['tpl_expirelast_paste'] . ' ';
			echo secondToText((int)$d) . "</span>\n";
			echo "			</a>\n";
			echo "			</p>\n";
		}
	}

	/**
	 * Display various information when the user is logged-in
	 */
	public function displayInfoWhenLoggedIn()
	{
		global $auth, $lang;

		if($auth->isAuthenticated() !== ERR_SUCCESS)
			return;

		$auth_ns = $auth->getAuthorizedNS();
		if(count($auth_ns) === 0)
			return;

		echo '<h4>' . $lang['tpl_available_ns'] . '</h4>';
		foreach($auth_ns as $ns)
		{
			$ns_url = getUrl('', $ns);
			echo '<p class="urltons"><a href="' . $ns_url;
			echo '" title="' . sprintf($lang['tpl_ns_url_title'], $ns) . '">- ';
			echo $ns . '</a</p><br />';
		}
	}

	/**
	 * Display the paste seen
	 */
	public function displayPaste()
	{
		global $ID, $NS, $auth, $conf, $lang;

		if(empty($this->curr_paste))
			return;

		if(is_string($this->curr_paste))
		{
			echo $this->curr_paste;
			return;
		}

		$title = htmlentities($this->curr_paste['title'], ENT_QUOTES, 'UTF-8');
		$type  = htmlentities($this->curr_paste['type'],  ENT_QUOTES, 'UTF-8');

		if(empty($title))
			$title = $lang['tpl_empty'];

		if(!empty($type))
			$type = "|&nbsp;$type&nbsp;&nbsp;";

		echo "\n<h4>$title&nbsp;&nbsp;$type ";

		if(in_array('export_raw', $conf['actions']))
		{
			echo '<a href="' . getUrl($ID, $NS) . '&act=export_raw" title="';
			echo $lang['export_as_raw'] . '">(raw)</a> ';
		}

		if(in_array('delete', $conf['actions']) &&
			!is_null($this->curr_paste['owner']) &&
			$auth->identifier() === $this->curr_paste['owner'])
		{


			echo '&nbsp;<a href="' . getUrl($ID, $NS) . '&act=delete&vscsrf=';
			echo get_token() . '" title="';
			echo $lang['delete'] . '">(' . $lang['tpl_delete'] . ')</a> ';
		}

		echo "<span class='lastpasteDelay'>";
		echo $lang['tpl_expirelast_paste'] . ' ';
		echo secondToText((int)$this->curr_paste['delay']) . "</span>\n";

		echo "</h4>\n";
		echo "<div class='coloredPaste'>\n";

		echo $this->code;

		echo "</div>\n";
		echo "<div><hr></div>";
	}

	/**
	 * Display the form used to create a new paste
	 */
	public function displayForm()
	{
		global $NS, $auth, $lang;

		/* Prepare for echo-ing */
		$a = $auth->getACLs();
		$multi  = $a[0]['multi'] ? ' size="4"' : '';
		$delays = getDelays();
		$addval = $lang['tpl_add'];
		$geshil = getGeSHiLanguages();
		$url    = getUrl('', $NS);

		$token  = get_token();

		$lang_title   = $lang['tpl_title'];
		$lang_content = $lang['tpl_content'];
		$lang_tip     = $lang['tpl_hl_tip'];

		$lang_opt     = $lang['tpl_options'];
		$lang_addonce = $lang['tpl_add_once'];

		$title   = $lang_title;
		$content = $lang_content;
		$type    = '';
		$acl     = '';

		if(is_array($this->curr_paste))
		{
			$title   = htmlentities($this->curr_paste['title'],
				ENT_QUOTES, 'UTF-8');
			$content = htmlentities($this->curr_paste['content'],
				ENT_QUOTES, 'UTF-8');
			$type    = $this->curr_paste['type'];
			$acl     = $this->curr_paste['acl'];

			if(substr($title, 0, 4) != 'Re: ')
				$title = 'Re: ' . $title;
		}

		/* And actually echo-ing */
		echo <<<DFORM
<form id='add_form' method="POST" action="$url">
	<div>$lang_tip</div>
<!--	<textarea id="content" name="content" rows="20"
		onkeydown="return insertTab(event,this);"
		onkeyup="return insertTab(event,this);"
		onkeypress="return insertTab(event,this);"> -->
		<textarea id="content" name="content" rows="20">
$content</textarea>
	<br>
	<input type="text" id="title" name="title" value="$title" />
DFORM;

		if(!empty($geshil))
		{
			echo '<span>' . $lang['tpl_language'] . '</span>';
			echo '	<select id="type" name="type">';
			echo "		<option value=''>--</option>\n";
			foreach($geshil as $l)
			{
				if($l === $type)
					echo "		<option value='$l' selected='selected'>$l</option>\n";
				else
					echo "		<option value='$l'>$l</option>\n";
			}
			echo '	</select>';
		}

		echo '<span>' . $lang['tpl_access'] . '</span>';
		echo "	<select id='acl' name='acl'$multi>";

		$cnt = count($a);
		for($i = 1; $i < $cnt; $i++)
		{
			echo '		<option value="'.$a[$i]['value'];
			if($a[$i]['value'] === $acl)
				echo '" selected="selected';
			echo '">'.$a[$i]['text']."</option>\n";
		}
		echo '	</select>';

		echo '<span>' . $lang['tpl_expire'] . '</span>';
		echo '	<select id="delay" name="delay">';
		foreach($delays['values'] as $key => $val)
		{
			echo '		<option value="'.$key;
			if($key === $delays['default'])
				echo '" selected="selected';
			echo '">'.$val."</option>\n";
		}

		echo <<<DFORM
	</select>

	<br>
	<br>

	<input type="hidden" name="act" value="add" />
	<input type="hidden" name="vscsrf" value="$token" />
	<input type="submit" name="add" value="$addval" class="button" id="btn_submit" /><br />
	<fieldset id="add_opt_list">
		<legend>$lang_opt</legend>
		<div class="opt_desc">
			<label>
				<input type="checkbox" name="readonce" value="1" />
				$lang_addonce
			</label>
		</div>
	</fieldset>
</form>
DFORM;
	}

	/**
	 * Indent a line, replacing \t with $conf['tabwidth'] &nbsp; and a space by
	 * one of them
	 *
	 * @param $string The string to indent
	 * @return The indented string
	 */
	protected function indent($string)
	{
		global $conf;

		$max = strlen($string);
		$charlist = array(" ", "\t", "\n", "\r", "\0", "\x0b");

		$new = '';

		for($i = 0; $i < $max; $i++)
		{
			if(!in_array($string[$i], $charlist))
				break;

			switch($string[$i])
			{
				case "\t":
					for($j = 0; $j < $conf['tabwidth']; $j++)
						$new .= '&nbsp;';
					break;
				case " ":
					$new .= '&nbsp;';
					break;
				default:
					break;
			}
		}

		$new .= substr($string, $i);

		return $new;
	}

	/**
	 * Get the lines the user wants to highlight
	 *
	 * @param $content
	 * @return
	 */
	protected function get_highlight_lines(&$content)
	{
		global $conf;

		$lines  = explode("\n", $content);
		$hl     = array();
		$normal = '';
		$length = strlen($conf['hl_sign']);

		foreach($lines as $i => $l)
		{
			if(strlen($l) >= $length &&
			   substr($l, 0, $length) === $conf['hl_sign'])
			{
				$hl[] = $i+1;
				$l = substr($l, $length);
			}
			$normal .= $l."\n";
		}

		$content = $normal;

		return $hl;
	}
}

//Setup VIM: ex: ts=4 noet :
