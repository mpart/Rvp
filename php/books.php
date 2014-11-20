<?php

	/*
	 * Copyright jounilaa 24.9.2014 - 19.10.2014, 22.10.2014, 7.11.2014, 20.11.2014 @ 
	 *
	 * PHP and extensions needed:
	 * php55-5.5.16                   "PHP Scripting Language"
	 * php55-openssl-5.5.16           SSL, "The openssl shared extension for php"
	 *
	 * File to use HttpRvp -proxy copying cookies in between client and proxyed
	 * server (client browser stores the cookies bypassed through the proxy). 
	 *
	 * ! If cookie - environment variables are set.
	 *
	 */

	//error_reporting(E_ALL);

	/*
	 * If defined, HttpRvp removes chunks and returns only one responce instead."); 
	 */
	/* Clearly faster without this setting: */
	define("REMOVECHUNKS", 1); 

	/*
	 * Do not use header(), instead echo "\r\n":s (lighttpd, broken environment?)
	 */
	//define( "ECHOHEADERS", 1 );

	/*
	 * If "true", output debug html instead of headers (garbled output).
	 */
	//define("DEBUG", 1);
	//define("DEBUGFILE", "file.log"); // not working 29.10.2014

	include 'HttpRvp.php';				// ":.:" in path to search php-files
							// unknown error: '/home1-3/j/jounilaa/public_html/php/

	/*
	 * If defined and output is not chunked, counts Content-length: again and
	 * returns the corrent length. STILL IN TEST: 22.10.2014 .
	 */
	//define("REWRITECONTENTLENGTH", 0);
	
	define( "PRINTNOHEADERS", 0 );
	
	/*
	 * Do not print all headers other than related to MIME (and other HTTP-relevant).
	 */
	define("CGIENABLE", 0);

	/* http://en.wikipedia.org/wiki/List_of_HTTP_header_fields */

	/*
	 * Variables.
 	 *
	 * Do not forget to end the line with \r\n or the connection will be endless.
	 */
	$urlextra = ""; $urlpath=""; $httpextra = ""; $httppostextra = "";
	$appendnametourl = "";
	$uriarray = ["",""];
	$ret = false;

	// to fsockopen: ssl (tls) or none

	if( true ){
		// Worldcat
		//$hoststring = 'ssl://www.worldcat.org'; 			// String to use to open the connection with fsockopen
		$hoststring = 'www.worldcat.org'; 				// String to use to open the connection with fsockopen
		$hostname = "www.worldcat.org"; 				// Hostname to attach to HTTP GET/POST requests 
		//$hostport = getservbyname('https', 'tcp');			// Port to establish the connection to
		$urlpath = "/webservices/catalog/search/worldcat/sru";
		$urlextra = "";	// Extra GET variables.
		$hostport = getservbyname('http', 'tcp');			// Port to establish the connection to
		// XML, chunks may garble UTF, any full one byte only charset to request
		//$httpextra = "Accept: text/xml; text/html\r\n";		// Extra HTTP headers.
		/*
		 * Include headers in request from client, not here. These are not replaced. */
		$httpextra .= "Accept-Charset: charset=iso-8859-1\r\n";		// Extra HTTP headers.
		$httpextra .= "Via: Rvp.php\r\n";
		$httppostextra = ""; // GET is used not POST, extra POST variables
		$appendnametourl = "oclcid"; // GET variable whos value is appended to the URL -part before "?"
	}else{
		$hoststring = ''; 				// String to use to open the connection with fsockopen
		$hostname = ""; 				// Hostname to attach to HTTP GET/POST requests 
		$urlpath = "/";
		$hostport = getservbyname('http', 'tcp');
		//$httpextra = "Content-Type: text/html;charset=UTF-8\r\n";	// Extra HTTP headers.
	}
	/*
	 * Init socket to host.
	 */
	$rvpproxy = new HttpRvp($hoststring, $hostport);

	if( ! $rvpproxy ){
		echo "<!-- Error: new HttpRvp failed. -->";
		exit();
	}

	/*
	 * Proxy request to the remote server and output the result to the client.
	 */
	$ret = $rvpproxy->http_proxy_by_string( $hostname, $httppostextra, $httpextra, $urlpath, $urlextra, $appendnametourl );
	if( ! $ret ){
		echo "<!-- Error: http_proxy_by_string returned $ret . -->";
	}

	/*
	 * Close connection (not necessary, the same in the constructor).
	 */		
	$rvpproxy->close_socket();

	unset($rvpproxy);	// Unnecessary before exit.
	
?>
