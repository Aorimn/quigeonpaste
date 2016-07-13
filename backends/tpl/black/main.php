<?php

if(!defined('QUIGON_ABS'))
	die('meh.');

global $ID;

?>
<!DOCTYPE html PUBLIC '-//W3C//DTD HTML 4.01//EN' 'http://www.w3.org/TR/html4/strict.dtd'>
<html>
<head>
	<meta http-equiv='Content-Type' content='text/html; charset=utf-8'>
	<title><?php $this->displayTitle(); ?></title>
	<meta name='author' content='TC'>
	<link rel='stylesheet' href='<?php echo QUIGON_URLTPL."css.php"; ?>' type='text/css' media='screen' charset='utf-8'>
	<style type="text/css"><?php $this->displayGeSHiStyle(); ?></style>
	<script src='<?php echo QUIGON_URLTPL."default/jquery-2.2.3.min.js"; ?>'></script>
	<script src='<?php echo QUIGON_URLTPL."js.php?id=".$ID; ?>'></script>
</head>
<body>
	<div id="mainwrapper">
		<div class="loginbar">
			<?php $this->displayLogin() ?>
		</div>
		<div class="messages">
			<?php $this->displayMessages() ?>
		</div>
		<div id="leftsidebar">
			<div id="lastpastesbar">
				<h4><?php echo $lang['tpl_last_pastes']; ?></h4>
				<div class="lastpastesparagraph">
					<?php $this->displayLastPastes(); ?>
				</div>
				<p class="currentime" title="<?php echo $lang['tpl_current_time']; ?>"><?php echo secondToText(time()) ?></p>
			</div>
			<div id="infologgedinbar">
				<?php $this->displayInfoWhenLoggedIn(); ?>
			</div>
		</div>
		<div id="maincontent">
			<div class="logo">
				<a href="<?php echo getUrl('', ''); ?>"><?php $this->displayTitle(); ?></a>
			</div>
			<div id="displayedPaste">
				<?php $this->displayPaste(); ?>
			</div>
			<h4><?php echo $lang['tpl_instructions']; ?></h4>
			<div id="newPaste">
				<?php $this->displayForm(); ?>
			</div>
		</div>
		<div class="clearer"></div>
		<div class="messages"><?php $this->displayMessages() ?></div>
	</div>
</body>
</html>

<?php

//Setup VIM: ex: ts=4 noet : ?>
