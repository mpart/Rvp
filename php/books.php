<?php

	/*
	 * Copyright jounilaa 24.9.2014 - 19.10.2014, 22.10.2014, 7.11.2014, 15.11.2014 @ 
	 *
	 * PHP and extensions needed:
	 * php55-5.5.16                   "PHP Scripting Language"
	 * php55-openssl-5.5.16           SSL, "The openssl shared extension for php"
	 *
	 * File to use HttpRvp -proxy copying cookies in between client and proxied
	 * server (client browser stores the cookies bypassed through the proxy). 
	 *
	 * ! If cookie - environment variables are set.
	 *
	 * In example proxy-code uses cURL library with default cookie transport
	 * mechanism (not working in environment 18.10.2014), 30.9.2014 (environment
	 * faulty/broken?).
	 */

	error_reporting(E_ALL);

	/*
	 * If defined, HttpRvp removes chunks and returns only one responce instead."); 
	 */
	/* Clearly faster without this setting: */
	//define("REMOVECHUNKS", 1); 

	/*
	 * If "true", output debug html instead of headers (garbled output).
	 */
	//define("DEBUG", 1);
	//define("DEBUGFILE", "file.log"); // <-- logfile option is not working yet 15.11.2014

	include 'HttpRvp.php';				// any path or "." in path to search php-files

	/*
	 * If defined and output is not chunked, counts Content-length: again and
	 * returns the corrent length. STILL IN TEST: 22.10.2014, 15.11.2014: count should be checked .
	 */
	define("REWRITECONTENTLENGTH", 0);

	/*
	 * Do not print any headers (unless id CGIENABLE is set, only "Content-type:" ). 
	 */
	//define( "PRINTNOHEADERS", 0 );
	
	/*
	 * Do not print all headers other than related to MIME (and other HTTP-relevant).
	 */
	//define("CGIENABLE", 0);

	/* 
	 * Example responce from worldcat.org 09/2014: 
	 */
 	/*
	 * HTTP/1.1 200 OK
	 * Date: Wed, 24 Sep 2014 17:59:28 GMT
	 * Server: Apache
	 * Set-Cookie: owcLocRedirectSession=_nr.no_inst; Path=/
	 * Set-Cookie: JSESSIONID=4123BF81566408EA06B2A41C126B5670; Path=/
	 * Content-Length: 67051
	 * P3P: CP="OCLC"
	 * Vary: Accept-Encoding
	 * Connection: close
	 * Content-Type: text/html;charset=UTF-8
 	 */
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

	if( true ){
		// Worldcat
		$hoststring = 'www.worldcat.org'; 				// String to use to open the connection with fsockopen (ssl:, tls or none)
		$hostname = "www.worldcat.org"; 				// Hostname to attach to HTTP GET/POST requests 
		$urlpath = "/webservices/catalog/search/worldcat/sru";
		$urlextra = "";	// Extra GET variables.
		$hostport = getservbyname('http', 'tcp');			// Port to establish the connection to
		/*
		 * Include headers in request from client, not here. These are not replaced. */
		$httpextra .= "Accept-Charset: charset=iso-8859-1\r\n";		// Extra HTTP headers (chunks may garble UTF, any full one byte to request).
		$httpextra .= "Via: Rvp.php\r\n";
		$httppostextra = ""; // GET is used not POST, extra POST variables
		$appendnametourl = "oclcid"; // GET variable whos value is appended to the URL -part before "?" 
		/*
		 * Example to proxy and from proxy url:s :
		 *
		 * http://proxyhostname/proxyurl?{oclcid=appendnametourl}{other GET variables} 	===> 
		 *
		 * {http|https|...}://{hostname}{|:{port}}/{urlpath}{/appendnametourl}?{other GET-variables}{urlextra}
		 *
		 * Request is made with: $httpextra $httppostextra and with sent POST-variables (POST is not tested 15.11.2014)
		 */
	}else{
		// Any other cite to test
		$hoststring = ''; 				// String to use to open the connection with fsockopen
		$hostname = ""; 				// Hostname to attach to HTTP GET/POST requests 
		$urlpath = "/";
		$hostport = getservbyname('http', 'tcp');
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
	 * Close connection (not necessary, the same in the destructor).
	 */		
	$rvpproxy->close_socket();

	unset($rvpproxy);	// Unnecessary before exit.	
?>
