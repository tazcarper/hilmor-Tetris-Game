<?php

$db_host = 'localhost';
$db_name = 'hilmor';
$db_user = 'root';
$db_pass = 'root';

function db_connect() {
	global $dbh, $db_host, $db_name, $db_user, $db_pass;
	if(empty($dbh)) {
		$dbh = new PDO('mysql:host='.$db_host.';dbname='.$db_name, $db_user, $db_pass, array(
			PDO::ATTR_PERSISTENT => true
		));
	}
}

function get_ip_address(){
    foreach (array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR') as $key){
        if (array_key_exists($key, $_SERVER) === true){
            foreach (explode(',', $_SERVER[$key]) as $ip){
                $ip = trim($ip); // just to be safe

                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false){
                    return $ip;
                }
            }
        }
    }
}

function is_naughty($input) {
	global $naughty;
	if(is_array($input)) {
		foreach($input as $k => $v) {
			if(is_naughty($v))
				return true;
		}
		return false;
	}

	foreach ( $naughty as $phrase ) {
		if ( preg_match( "/\b$phrase\b/si", $v ) ) {
			$naughty_phrase = $input;
			return true;
		}
	}
	return false;
}
function un_naughty($input) {
	global $naughty;
	if(is_array($input)) {
		foreach($input as $k => $v) {
			$input[$k] = un_naughty($v);
		}
		return $input;
	}
	foreach ( $naughty as $phrase ) {
		$input = trim(preg_replace( array("/\b$phrase\b/si",'/\s+/'),' ', $input ));
	}
	return $input;
}


define("SANI_EMAIL", 1);	//sanitize_email

define("SANI_FLOAT",2);	//sanitize_floatingpt
define("SANI_INT", 4);		//sanitize_digits
define("SANI_DIGITS", 4);		//sanitize_digits

define("SANI_AVERAGE", 8);	//sanitize_alphanum_punctuation
define("SANI_ALNUM", 16);	//sanitize_alphanum
define("SANI_NAME", 1024);  //sanitize a proper noun

define("SANI_XSS", 32);   	//sanitize_XSS 
define("SANI_HTML", 64);		//sanitize_html
define("SANI_SQL", 128);		//sanitize_sql_value

define("SANI_SPACE",256);
define("SANI_ALPHA",512);

define("SANI_STD", SANI_SQL + SANI_HTML); //standard for most input

//If you are processing a public form, consider utilizing /includes/dirtyHackers.php as well

function sanitize_standard($input)
{
	return sanitize($input,SANI_STD);
}

///////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////

function sanitize($input, $flags = SANI_ALNUM)
{
	if(is_array($input))
		return sanitize_array($input, $flags);
	
	if($flags & SANI_EMAIL) $input = sanitize_email_valid($input);

	if($flags & SANI_FLOAT) $input = sanitize_floatingpt($input);
	if($flags & SANI_DIGITS) $input = sanitize_digits($input);
	if($flags & SANI_AVERAGE) $input = sanitize_alphanum_punctuation($input);
	if($flags & SANI_ALNUM) $input = sanitize_alphanum($input);
	if($flags & SANI_ALPHA) $input = sanitize_alpha($input);
	if($flags & SANI_NAME) $input = sanitize_proper_noun($input);

	if($flags & SANI_XSS) $input = sanitize_XSS($input);
	if($flags & SANI_HTML) $input = sanitize_html($input);
	if($flags & SANI_SQL) $input = sanitize_sql_value($input);
	
	if($flags & SANI_SPACE) $input = sanitize_whitespce($input);

	$input = trim($input);

	return $input;
}


///////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////

//FOR: Cleaning a bunch all at once
function sanitize_array($arr, $flags) {
	foreach($arr as $k => $v) {
		$arr[$k] = sanitize($v, $flags);
	}
	return $arr;
}

function sanitizePostGet($flags = SANI_STD)
{
	$_POST = sanitize_array($_POST, $flags);
	$_GET = sanitize_array($_GET, $flags);
	$_REQUEST = sanitize_array($_REQUEST, $flags);
}

//FOR: All string data about to be used in SQL
function sanitize_sql_value($string)
{
	//http://dev.mysql.com/doc/refman/5.0/en/mysql-real-escape-string.html
	//http://www.php.net/manual/en/function.mysql-real-escape-string.php
	$replace = array(
		"\x00"  => '\x00',
		"\n"    => '\n',
		"\r"    => '\r',
		'\\'    => '\\\\',
		"'"     => "\'",
		'"'     => '\"',
		"\x1a"  => '\x1a'
	);
	return strtr($string, $replace);
	//handy:  list() = sanitize_sql_value(array());
}


//FOR: Checking if email addresses appear to be valid
//Beware, apostrophes are allowed in emails.  SQL escape this as well.
function sanitize_email_valid($string)
{	
	$string = str_replace(' ','',$string);
	$string = strtolower($string);
	//old pattern:
	//http://fightingforalostcause.net/misc/2006/compare-email-regex.php
	//"/^[-_a-z0-9\'+*$^&%=~!?{}]++(?:\.[-_a-z0-9\'+*$^&%=~!?{}]+)*+@(?:(?![-.])[-a-z0-9.]+(?<![-.])\.[a-z]{2,6}|\d{1,3}(?:\.\d{1,3}){3})(?::\d++)?$/iD"
	

	//Note that FILTER_VALIDATE_EMAIL used in isolation is not enough; It will happily pronounce "yourname" as valid because presumably the "@localhost" is implied, so you still have to check that the domain portion of the address exists.
	//http://www.php.net/manual/en/function.filter-var.php#95208

	//http://www.php.net/manual/en/function.filter-var.php#94790
	//http://svn.php.net/viewvc/php/php-src/trunk/ext/filter/logical_filters.c?view=markup
	$pattern = "/^(?!(?:(?:\\x22?\\x5C[\\x00-\\x7E]\\x22?)|(?:\\x22?[^\\x5C\\x22]\\x22?)){255,})(?!(?:(?:\\x22?\\x5C[\\x00-\\x7E]\\x22?)|(?:\\x22?[^\\x5C\\x22]\\x22?)){65,}@)(?:(?:[\\x21\\x23-\\x27\\x2A\\x2B\\x2D\\x2F-\\x39\\x3D\\x3F\\x5E-\\x7E]+)|(?:\\x22(?:[\\x01-\\x08\\x0B\\x0C\\x0E-\\x1F\\x21\\x23-\\x5B\\x5D-\\x7F]|(?:\\x5C[\\x00-\\x7F]))*\\x22))(?:\\.(?:(?:[\\x21\\x23-\\x27\\x2A\\x2B\\x2D\\x2F-\\x39\\x3D\\x3F\\x5E-\\x7E]+)|(?:\\x22(?:[\\x01-\\x08\\x0B\\x0C\\x0E-\\x1F\\x21\\x23-\\x5B\\x5D-\\x7F]|(?:\\x5C[\\x00-\\x7F]))*\\x22)))*@(?:(?:(?!.*[^.]{64,})(?:(?:(?:xn--)?[a-z0-9]+(?:-[a-z0-9]+)*\\.){1,126}){1,}(?:(?:[a-z][a-z0-9]*)|(?:(?:xn--)[a-z0-9]+))(?:-[a-z0-9]+)*)|(?:\\[(?:(?:IPv6:(?:(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){7})|(?:(?!(?:.*[a-f0-9][:\\]]){7,})(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,5})?::(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,5})?)))|(?:(?:IPv6:(?:(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){5}:)|(?:(?!(?:.*[a-f0-9]:){5,})(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,3})?::(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,3}:)?)))?(?:(?:25[0-5])|(?:2[0-4][0-9])|(?:1[0-9]{2})|(?:[1-9]?[0-9]))(?:\\.(?:(?:25[0-5])|(?:2[0-4][0-9])|(?:1[0-9]{2})|(?:[1-9]?[0-9]))){3}))\\]))$/iD";
	
	$valid = preg_match($pattern, trim($string));
	if($valid)
		return $string;
	return '';
}

//FOR: All string data that will eventually be displayed
function sanitize_html($string)
{
	return htmlentities( $string, ENT_QUOTES);
	//consider also using nl2br in your code
}

//FOR: Stripping out numeric data from a string input.  Ex: phone numbers, zip codes
function sanitize_digits($integer)
{
	return preg_replace('/[^0-9]/','',$integer);
}

function sanitize_floatingpt($float)
{
	if(!$float)
		return '';
	$regex = "/[-+]?[0-9]*\.?[0-9]+([eE][-+]?[0-9]+)?/";
	preg_match($regex,$float,$matches);
	if(count($matches))
		return $matches[0];
}

//FOR: Stripping out infrequent or troublesome characters
function sanitize_alphanum_punctuation($string)
{
	return preg_replace("/[^a-zA-Z0-9_@#! \.\?-]/", "", $string);
}

function sanitize_proper_noun($string)
{
	return preg_replace("/[^a-zA-Z \.'-]/", "", $string); //Alert: allows apostrophe (O'Brien)
}

//FOR: Stripping it down to basics.
function sanitize_alphanum($string)
{
	return preg_replace("/[^a-zA-Z0-9 ]/", "", $string);
}

//FOR: Stripping it down to basics.
function sanitize_alpha($string)
{
	return preg_replace("/[^a-zA-Z ]/", "", $string);
}

//FOR: Removing excess whitespace
function sanitize_whitespce($string)
{
	$string = preg_replace('/\s/',' ',$string); //first, make all into nice clean spaces, no tabs, linebreaks or odd things
	return preg_replace('/\s\s+/', ' ', $string);  //one space is OK (\s) but any more (\s+) get nixed
}

//FOR: Keeping our site safe
function sanitize_XSS($str) {
/*
   // remove all non-printable characters. CR(0a) and LF(0b) and TAB(9) are allowed
   // this prevents some character re-spacing such as <java\0script>
   // note that you have to handle splits with \n, \r, and \t later since they *are* allowed in some inputs
   $val = preg_replace('/([\x00-\x08,\x0b-\x0c,\x0e-\x19])/', '', $val);
   
   // straight replacements, the user should never need these since they're normal characters
   // this prevents like <IMG SRC=&#X40&#X61&#X76&#X61&#X73&#X63&#X72&#X69&#X70&#X74&#X3A&#X61&#X6C&#X65&#X72&#X74&#X28&#X27&#X58&#X53&#X53&#X27&#X29>
   $search = 'abcdefghijklmnopqrstuvwxyz';
   $search .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
   $search .= '1234567890!@#$%^&*()';
   $search .= '~`";:?+/={}[]-_|\'\\';
   for ($i = 0; $i < strlen($search); $i++) {
      // ;? matches the ;, which is optional
      // 0{0,7} matches any padded zeros, which are optional and go up to 8 chars
   
      // &#x0040 @ search for the hex values
      $val = preg_replace('/(&#[xX]0{0,8}'.dechex(ord($search[$i])).';?)/i', $search[$i], $val); // with a ;
      // &#00064 @ 0{0,7} matches '0' zero to seven times
      $val = preg_replace('/(&#0{0,8}'.ord($search[$i]).';?)/', $search[$i], $val); // with a ;
   }
   
   // now the only remaining whitespace attacks are \t, \n, and \r
   $ra1 = Array('javascript', 'vbscript', 'expression', 'applet', 'meta', 'xml', 'blink', 'link', 'style', 'script', 'embed', 'object', 'iframe', 'frame', 'frameset', 'ilayer', 'layer', 'bgsound', 'title', 'base'); 
   $ra2 = Array('onabort', 'onactivate', 'onafterprint', 'onafterupdate', 'onbeforeactivate', 'onbeforecopy', 'onbeforecut', 'onbeforedeactivate', 'onbeforeeditfocus', 'onbeforepaste', 'onbeforeprint', 'onbeforeunload', 'onbeforeupdate', 'onblur', 'onbounce', 'oncellchange', 'onchange', 'onclick', 'oncontextmenu', 'oncontrolselect', 'oncopy', 'oncut', 'ondataavailable', 'ondatasetchanged', 'ondatasetcomplete', 'ondblclick', 'ondeactivate', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onerror', 'onerrorupdate', 'onfilterchange', 'onfinish', 'onfocus', 'onfocusin', 'onfocusout', 'onhelp', 'onkeydown', 'onkeypress', 'onkeyup', 'onlayoutcomplete', 'onload', 'onlosecapture', 'onmousedown', 'onmouseenter', 'onmouseleave', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onmove', 'onmoveend', 'onmovestart', 'onpaste', 'onpropertychange', 'onreadystatechange', 'onreset', 'onresize', 'onresizeend', 'onresizestart', 'onrowenter', 'onrowexit', 'onrowsdelete', 'onrowsinserted', 'onscroll', 'onselect', 'onselectionchange', 'onselectstart', 'onstart', 'onstop', 'onsubmit', 'onunload');
   $ra = array_merge($ra1, $ra2);
   
   $found = true; // keep replacing as long as the previous round replaced something
   while ($found == true) 
   {
      $val_before = $val;
      for ($i = 0; $i < sizeof($ra); $i++) 
      {
         $pattern = '/';
         for ($j = 0; $j < strlen($ra[$i]); $j++) 
         {
            if ($j > 0) 
            {
               $pattern .= '(';
               $pattern .= '(&#[xX]0{0,8}([9ab]);)';
               $pattern .= '|';
               $pattern .= '|(&#0{0,8}([9|10|13]);)';
               $pattern .= ')*';
            }
            $pattern .= $ra[$i][$j];
         }
         $pattern .= '/i';
         //$replacement = substr($ra[$i], 0, 2).'<x>'.substr($ra[$i], 2); // add in <> to nerf the tag
         $replacement = '<xss attempt="'.$ra[$i].'" />';
         $val = preg_replace($pattern, $replacement, $val); // filter out the hex tags
         if ($val_before == $val) 
         {
            // no replacements were made, so exit the loop
            $found = false;
         }
      }
   }
   return $val;
*/
	/* Remove Null Characters
    */
    $str = preg_replace('/\0+/', '', $str);
    $str = preg_replace('/(\\\\0)+/', '', $str);

    /* Validate standard character entities
     */
    $str = preg_replace('#(&\#?[0-9a-z]+)[\x00-\x20]*;?#i', "\\1;", $str);
    
    /* Validate UTF16 two byte encoding (x00) 
     */
    $str = preg_replace('#(&\#x?)([0-9A-F]+);?#i',"\\1\\2;",$str);

    /* URL Decode
     */	
    $str = preg_replace("/(%20)+/", '9u3iovBnRThju941s89rKozm', $str);
    $str = preg_replace("/%u0([a-z0-9]{3})/i", "&#x\\1;", $str);
    $str = preg_replace("/%([a-z0-9]{2})/i", "&#x\\1;", $str); 
    $str = str_replace('9u3iovBnRThju941s89rKozm', "%20", $str);	
    		
    /* Convert character entities to ASCII 
     */
    $str = preg_replace_callback("/[a-z]+=([\'\"]).*?\\1/si", array($this, '_attribute_conversion'), $str);
    $str = preg_replace_callback("/<([\w]+)[^>]*>/si", array($this, '_html_entity_decode_callback'), $str);

    
    /* Convert all tabs to spaces
     */
     
    $str = str_replace("\t", " ", $str);

    /* Not Allowed Under Any Conditions
     */	
    $bad = array(
    				'document.cookie'	=> '[removed]',
    				'document.write'	=> '[removed]',
    				'.parentNode'		=> '[removed]',
    				'.innerHTML'		=> '[removed]',
    				'window.location'	=> '[removed]',
    				'-moz-binding'		=> '[removed]',
    				'<!--'				=> '&lt;!--',
    				'-->'				=> '--&gt;',
    				'<!CDATA['			=> '&lt;![CDATA['
    			);
    foreach ($bad as $key => $val) {
    	$str = str_replace($key, $val, $str);   
    }
    $bad = array(
    				"javascript\s*:"	=> '[removed]',
    				"expression\s*\("	=> '[removed]', // CSS and IE
    				"Redirect\s+302"	=> '[removed]'
    			);
    foreach ($bad as $key => $val) {
    	$str = preg_replace("#".$key."#i", $val, $str);   
    }

    /* Makes PHP tags safe
     */		
    $str = str_replace(array('<?php', '<?PHP', '<?', '?'.'>'),  array('&lt;?php', '&lt;?PHP', '&lt;?', '?&gt;'), $str);

    /* Compact any exploded words
     */		
    $words = array('javascript', 'expression', 'vbscript', 'script', 'applet', 'alert', 'document', 'write', 'cookie', 'window');
    foreach ($words as $word) {
    	$temp = '';
    	for ($i = 0; $i < strlen($word); $i++) {
    		$temp .= substr($word, $i, 1)."\s*";
    	}
    	// We only want to do this when it is followed by a non-word character
    	$str = preg_replace('#('.substr($temp, 0, -3).')(\W)#ise', "preg_replace('/\s+/s', '', '\\1').'\\2'", $str);
    }

    /* Remove disallowed Javascript in links or img tags
     */
    do {
    	$original = $str;
    	
    	if ((version_compare(PHP_VERSION, '5.0', '>=') === TRUE && stripos($str, '</a>') !== FALSE) OR 
    		 preg_match("/<\/a>/i", $str)) {
    		$str = preg_replace_callback("#<a.*?</a>#si", array($this, '_js_link_removal'), $str);
    	}
    	
    	if ((version_compare(PHP_VERSION, '5.0', '>=') === TRUE && stripos($str, '<img') !== FALSE) OR 
    		 preg_match("/img/i", $str)) {
    		$str = preg_replace_callback("#<img.*?".">#si", array($this, '_js_img_removal'), $str);
    	}
    	
    	if ((version_compare(PHP_VERSION, '5.0', '>=') === TRUE && (stripos($str, 'script') !== FALSE OR stripos($str, 'xss') !== FALSE)) OR preg_match("/(script|xss)/i", $str)) {
    		$str = preg_replace("#</*(script|xss).*?\>#si", "", $str);
    	}
    }
    while($original != $str);
    
    unset($original);

    /* Remove JavaScript Event Handlers
     */		
    $event_handlers = array('onblur','onchange','onclick','onfocus','onload','onmouseover','onmouseup','onmousedown','onselect','onsubmit','onunload','onkeypress','onkeydown','onkeyup','onresize', 'xmlns');
    $str = preg_replace("#<([^>]+)(".implode('|', $event_handlers).")([^>]*)>#iU", "&lt;\\1\\2\\3&gt;", $str);

    /* Sanitize naughty HTML elements
     */		
    $str = preg_replace('#<(/*\s*)(alert|applet|basefont|base|behavior|bgsound|blink|body|embed|expression|form|frameset|frame|head|html|ilayer|iframe|input|layer|link|meta|object|plaintext|style|script|textarea|title|xml|xss)([^>]*)>#is', "&lt;\\1\\2\\3&gt;", $str);
    
    /* Sanitize naughty scripting elements
     */
    $str = preg_replace('#(alert|cmd|passthru|eval|exec|expression|system|fopen|fsockopen|file|file_get_contents|readfile|unlink)(\s*)\((.*?)\)#si', "\\1\\2&#40;\\3&#41;", $str);
    				
    /* Final clean up
     */	
    $bad = array(
    				'document.cookie'	=> '[removed]',
    				'document.write'	=> '[removed]',
    				'.parentNode'		=> '[removed]',
    				'.innerHTML'		=> '[removed]',
    				'window.location'	=> '[removed]',
    				'-moz-binding'		=> '[removed]',
    				'<!--'				=> '&lt;!--',
    				'-->'				=> '--&gt;',
    				'<!CDATA['			=> '&lt;![CDATA['
    			);

    foreach ($bad as $key => $val) {
    	$str = str_replace($key, $val, $str);   
    }

    $bad = array(
    				"javascript\s*:"	=> '[removed]',
    				"expression\s*\("	=> '[removed]', // CSS and IE
    				"Redirect\s+302"	=> '[removed]'
    			);
    			
    foreach ($bad as $key => $val) {
    	$str = preg_replace("#".$key."#i", $val, $str);   
    }
    
    return $str;
}




//FOR: Keeps errant values from making it into your program.  
//Call this after you pull your explicit needs from your superglobals
//Essentially the opposite of extract($_REQUEST);
function unRegisterGlobals($allowedVars=array())
{
	$allowedVars[]='excludeList';
	//Have to approach these individually because superglobals cannot be accessed by dynamic variables
	foreach($_GET as $key => $val)
	{
		if(!in_array($key,$allowedVars))
			unset($_GET[$key]);
		else
			$$key = $val;
	}
	if(!count($_GET))
    	unset($_GET);
				
	foreach($_POST as $key => $val)
	{
		if(!in_array($key,$allowedVars))
			unset($_POST);
		else
			$$key = $val;
	}
	if(!count($_POST))
		unset($_POST);
			
	foreach($_COOKIE as $key => $val)
	{
		if(!in_array($key,$allowedVars))
			unset($_COOKIE);
		else
			$$key = $val;
	}
	if(!count($_COOKIE))
		unset($_COOKIE);
	
	foreach($_FILES as $key => $val)
	{
		if(!in_array($key,$allowedVars))
			unset($_FILES);
		else
			$$key = $val;
	}
	if(!count($_FILES))
		unset($_FILES);
	
	foreach($_SERVER as $key => $val)
	{
		if(!in_array($key,$allowedVars))
			unset($_SERVER);
		else
			$$key = $val;
	}
	if(!count($_SERVER))
		unset($_SERVER);
}

$naughty = array(
	'skanks',
	'f\*\*k',
	'a\$\$',
	'ni99a',
	"nude",
	"shemale",
	"pusssy",
	"pussie",
	"phorno",
	"tranny",
	"masturbat",
	"footjobs",
	"sexy",
	"titkiboo",
	"seks",
	"goodeg",
	"boomsa",
	"brazilian-ass",
	"weebly",
	"fuck",
	"naked",
	"facial",
	"porno",
	"sex",
	"ass",
	"labia",
	"cunt",
	"arse",
	"assbag",
	"assbandit",
	"assbanger",
	"assbite",
	"assclown",
	"asscock",
	"assface",
	"assfuck",
	"asshat",
	"asshead",
	"asshole",
	"asshopper",
	"assjacker",
	"asslicker",
	"assmunch",
	"assshole",
	"asswipe",
	"bampot",
	"bastard",
	"beaner",
	"bitch",
	"blow job",
	"blowjob",
	"boner",
	"brotherfucker",
	"bullshit",
	"butt plug",
	"butt-pirate",
	"buttfucka",
	"buttfucker",
	"camel toe",
	"carpetmuncher",
	"chinc",
	"chink",
	"chode",
	"clit",
	"cock",
	"cockbite",
	"cockface",
	"cockfucker",
	"cockmaster",
	"cockmuncher",
	"cocksmoker",
	"cocksucker",
	"coon",
	"cooter",
	"cracker",
	"cum",
	"cumtart",
	"cunnilingus",
	"cunt",
	"cunthole",
	"damn",
	"deggo",
	"dick",
	"dickhead",
	"dickhole",
	"dicks",
	"dickweed",
	"dickwod",
	"dildo",
	"dipshit",
	"dookie",
	"douche",
	"douchebag",
	"douchewaffle",
	"dumass",
	"dumb ass",
	"dumbass",
	"dumbfuck",
	"dumbshit",
	"dyke",
	"fag",
	"fagbag",
	"fagfucker",
	"faggit",
	"faggot",
	"fagtard",
	"fatass",
	"fellatio",
	"fuck",
	"f.u.",
	"fu",
	"fuckass",
	"fucked",
	"fucker",
	"fuckface",
	"fuckhead",
	"fuckhole",
	"fuckin",
	"fucking",
	"fucks",
	"fuckstick",
	"fucktard",
	"fuckup",
	"fuckwad",
	"fuckwit",
	"fudgepacker",
	"gay",
	"gaytard",
	"gaywad",
	"goddamn",
	"goddamnit",
	"gooch",
	"gook",
	"gringo",
	"guido",
	"hard on",
	"hell",
	"homo",
	"honkey",
	"humping",
	"jackass",
	"jap",
	"jerk off",
	"jigaboo",
	"jizz",
	"jungle bunny",
	"kike",
	"kooch",
	"kootch",
	"kyke",
	"lesbian",
	"lesbo",
	"lezzie",
	"mick",
	"minge",
	"mothafucka",
	"motherfucker",
	"motherfucking",
	"muff",
	"negro",
	"nigga",
	"nigger",
	"niglet",
	"nut sack",
	"nutsack",
	"panooch",
	"pecker",
	"peckerhead",
	"piss",
	"pissed",
	"pissed off",
	"pollock",
	"poon",
	"poonani",
	"poonany",
	"porch monkey",
	"porchmonkey",
	"prick",
	"punta",
	"pussy",
	"pussylicking",
	"puto",
	"queef",
	"queer",
	"queerbait",
	"renob",
	"rimjob",
	"sand nigger",
	"sandnigger",
	"schlong",
	"scrote",
	"shit",
	"shitcunt",
	"shitface",
	"shitfaced",
	"shithead",
	"shitter",
	"shittiest",
	"shitting",
	"shitty",
	"skank",
	"skeet",
	"slut",
	"slutbag",
	"snatch",
	"spic",
	"spick",
	"tard",
	"testicle",
	"thundercunt",
	"tit",
	"titfuck",
	"tits",
	"twat",
	"twatlips",
	"twats",
	"va-j-j",
	"vag",
	"vjayjay",
	"wank",
	"wetback",
	"whore",
	"wop",
	"anal",
	"biatch",
	"biotch",
	"idiot",
	"retard",
	"freak",
	"airhead",
	"ugly",
	"bimbo",
	"boob",
	"buffoon",
	"bugaboo",
	"bugly",
	"fugly",
);
