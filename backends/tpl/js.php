<?php


if(!defined('QUIGON_ABS'))
	define('QUIGON_ABS', dirname(__FILE__) . DIRECTORY_SEPARATOR
		. '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR);



$matches = array();
preg_match('#^(.*/).*/#', $_SERVER['SCRIPT_NAME'], $matches);
define('QUIGON_REL', $matches[1]);


require(QUIGON_ABS.'inc/init.php');

define('QUIGON_JS', QUIGON_REL.'tpl/'.$conf['tpl'].'/');


header('Content-Type: text/javascript; charset=utf-8');

// js_checkID($store);

out();


/* ------------------------------------------------------------------------- */

/**
 * Basic function to get and display CSS files
 */
function out()
{
	global $ID, $NS, $conf;

	$js = file_get_contents(QUIGON_TPL . 'script.js');

	/* replace placeholders */
	$js = preg_replace('#@@QUIGON_JS@@#', QUIGON_JS, $js);

	$ID = htmlentities($ID, ENT_QUOTES);
	$NS = htmlentities($NS, ENT_QUOTES);

	echo "var NS='$NS';";
	echo "var ID='$ID';";
	if($conf['debug'])
		echo $js;
	else
		echo js_compress($js);
}



function js_checkID($store)
{
	global $ID, $NS;

	$store_c = "Storage_$store";
	$storage = new $store_c();

	// Does not work with readable-once pastes...
	if($storage->get_value($ID, $NS, $a) === ERR_NOT_FOUND)
		$ID = '';
}


/**
 * Strip comments and whitespaces from given JavaScript Code
 *
 * This is a port of Nick Galbreath's python tool jsstrip.py which is
 * released under BSD license. See link for original code.
 *
 * This file is part of the DokuWiki project and get from it.
 *
 * @author Nick Galbreath <nickg@modp.com>
 * @author Andreas Gohr <andi@splitbrain.org>
 * @link   http://code.google.com/p/jsstrip/
 * @link   http://www.dokuwiki.org/
 */
function js_compress($s){
    $s = ltrim($s);     // strip all initial whitespace
    $s .= "\n";
    $i = 0;             // char index for input string
    $j = 0;             // char forward index for input string
    $line = 0;          // line number of file (close to it anyways)
    $slen = strlen($s); // size of input string
    $lch  = '';         // last char added
    $result = '';       // we store the final result here

    // items that don't need spaces next to them
    $chars = "^&|!+\-*\/%=\?:;,{}()<>% \t\n\r'\"[]";

    $regex_starters = array("(", "=", "[", "," , ":", "!");

    $whitespaces_chars = array(" ", "\t", "\n", "\r", "\0", "\x0B");

    while($i < $slen){
        // skip all "boring" characters.  This is either
        // reserved word (e.g. "for", "else", "if") or a
        // variable/object/method (e.g. "foo.color")
        while ($i < $slen && (strpos($chars,$s[$i]) === false) ){
            $result .= $s{$i};
            $i = $i + 1;
        }

        $ch = $s{$i};
		// multiline comments (keeping IE conditionals)
        if($ch == '/' && $s{$i+1} == '*' && $s{$i+2} != '@'){
            $endC = strpos($s,'*/',$i+2);
            if($endC === false) trigger_error('Found invalid /*..*/ comment', E_USER_ERROR);
            $i = $endC + 2;
            continue;
        }

        // singleline
        if($ch == '/' && $s{$i+1} == '/'){
            $endC = strpos($s,"\n",$i+2);
            if($endC === false) trigger_error('Invalid comment', E_USER_ERROR);
            $i = $endC;
            continue;
        }

        // tricky.  might be an RE
        if($ch == '/'){
            // rewind, skip white space
            $j = 1;
            while(in_array($s{$i-$j}, $whitespaces_chars)){
                $j = $j + 1;
            }
            if( in_array($s{$i-$j}, $regex_starters) ){
                // yes, this is an re
                // now move forward and find the end of it
                $j = 1;
                while($s{$i+$j} != '/'){
                    while( ($s{$i+$j} != '\\') && ($s{$i+$j} != '/')){
                        $j = $j + 1;
                    }
                    if($s{$i+$j} == '\\') $j = $j + 2;
                }
                $result .= substr($s,$i,$j+1);
                $i = $i + $j + 1;
                continue;
            }
        }
        // double quote strings
        if($ch == '"'){
            $j = 1;
            while( $s{$i+$j} != '"' && ($i+$j < $slen)){
                if( $s{$i+$j} == '\\' && ($s{$i+$j+1} == '"' || $s{$i+$j+1} == '\\') ){
                    $j += 2;
                }else{
                    $j += 1;
                }
            }
            $result .= substr($s,$i,$j+1);
            $i = $i + $j + 1;
            continue;
        }

        // single quote strings
        if($ch == "'"){
            $j = 1;
            while( $s{$i+$j} != "'" && ($i+$j < $slen)){
                if( $s{$i+$j} == '\\' && ($s{$i+$j+1} == "'" || $s{$i+$j+1} == '\\') ){
                    $j += 2;
                }else{
                    $j += 1;
                }
            }
            $result .= substr($s,$i,$j+1);
            $i = $i + $j + 1;
            continue;
        }

        // whitespaces
        if( $ch == ' ' || $ch == "\r" || $ch == "\n" || $ch == "\t" ){
            // leading spaces
            if($i+1 < $slen && (strpos($chars,$s[$i+1]) !== false)){
                $i = $i + 1;
                continue;
            }
            // trailing spaces
            //  if this ch is space AND the last char processed
            //  is special, then skip the space
            $lch = substr($result,-1);
            if($lch && (strpos($chars,$lch) !== false)){
                $i = $i + 1;
                continue;
            }
            // else after all of this convert the "whitespace" to
            // a single space.  It will get appended below
            $ch = ' ';
        }

        // other chars
        $result .= $ch;
        $i = $i + 1;
    }

    return trim($result);
}



//Setup VIM: ex: ts=4 noet :
