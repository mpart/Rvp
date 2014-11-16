<?php

	/*
	 * jounilaa 24.9.2014   
	 *
	 * PHP and extensions needed:
	 * php55-5.5.16                   "PHP Scripting Language"
	 * php55-openssl-5.5.16           SSL, "The openssl shared extension for php"
	 *
	 * Reverse proxy (HttpRvp) to predefined hosts. Copies cookies from client to a proxyed
	 * server and outputs servers responce back to the client. URL is copied in every case,
	 * not just in GET requests. POST key-value -pairs are copied if POST-method was used.
	 *
	 */

	include 'Rvp.php';

	class HttpRvp extends Rvp {

		protected $hostname;
		
		public function __construct($hoststring, $hostport){
			$argc=0;
        		$argc = func_num_args();
			if( $argc == 0){
				//echo "\n<!-- HttpRvp, calling parent constructor, Parent::__construct1(); --> ";
				Parent::__construct1();
			}else{
				//echo "\n<!-- HttpRvp, calling parent constructor, Parent::__construct( $hoststring, $hostport ); --> ";
				Parent::__construct( $hoststring, $hostport );
			}
			if( defined('DEBUG') ){
				echo "Content-Type: text/html \r\n\r\n<DOCTYPE html><HTML><HEAD><TITLE>Debug</TITLE></HEAD><BODY>";
			}
		}
		public function __destruct(){
			if( defined('DEBUG') ){
				echo "</BODY></HTML>";
			}
			Parent::__destruct();
		}
		/*
		 * Decode query from client sent URL.
		 */
		public function decode_GET_url_query( $urlextra, $appendnametourl ){
			$querystring = ""; $uriarray = ["", ""]; $ret = "";
			$vararray = ["", ""];
			$keyarray = ["", ""];
			$indx=0;

			if( isset( $_SERVER ) ){  
				//echo "<!-- ISSET _SERVER -->";
		                $ret = $_SERVER[ 'REQUEST_URI' ];
		                $uriarray = explode( "?", $ret, 2 );
		                if( count( $uriarray ) > 1 ){
					$querystring = "?";
		                        //$querystring += $uriarray[1] . $urlextra; // old 12.11.2014, new ->
					$vararray = explode( "&", $uriarray[1], 10 );
					for( $indx=0; $indx < count( $vararray ); $indx++){	// foreach ei toimi tassa
						$keyarray = explode( "=", $vararray[$indx], 2 );
						if( count( $keyarray ) > 1 ){
							if( $keyarray[0] == $appendnametourl ){
								if( $appendnametourl!="" )
									$querystring = "/" . $keyarray[1] . $querystring;
							}else{
								$querystring .= $keyarray[0] . "=" . $keyarray[1] ."&";
							}
						}
					}
				}
			}else if( isset( $_GET ) ){
				//echo "<!-- ISSET _GET -->";
				if( count( $_GET ) > 1 )
					$querystring = "?";
				foreach ($_GET as $key => $value) {
					if( $key == $appendnametourl ){ // new 12.11.2014
						if( $appendnametourl!="" )
							$querystring = "/" . $value . $querystring;
					}else{
						$querystring .= $key . "=" . $value ."&";
					}
				}
			}else{
				//echo "<!-- NO GET VARIABLES. -->";
				return "" . $urlextra; // no '?' , lisays 12.11.2014: urlextra
			}
			$querystring .= $urlextra;
			return $querystring;
		}

		/*
		 * Decode POST-string from client.
		 */
		public function decode_POST_string( $httpextra ){
			$poststring = "";
			$poststring .= $httpextra; 
			if( $_POST ){
				//echo "<!-- ISSET _POST -->";
				foreach ($_POST as $key => $value) {
					$this->debug_text( "Debug post.<BR>");
					$poststring .= $key . "=" . $value ."&";
				}
			}
			return $poststring;
		}

		/*
		 * Determine called method and make a request string "requeststring".
		 */
		public function copy_by_method( $hostnamestring, $querystring, $poststring, $extrahttpheaders ){
			/*
			 * Copy GET and POST to server.
			 * 
			 * (  A better solution would be to copy almost all client headers and change:
			 *    GET querystring, 'Host:', "Cookies:" and copy POST, SOAP, 
			 *    JSON, XML, ... data from the message .  )
			 *
			 * Proxy:
			 *  x Same HTTP version must be copied.
			 *  x URI is recommended to be shorter than 255 bytes
			 *  - If same name header-fields are present, the order
			 *    has to be the same
			 *  - 14.10 Connection: "Connection:"
			 *		- Client sent connection has to be checked
			 *		- "Connection: close" if not persistent connection
			 *  - If proxy receives a request with an expectation that it cannot meet:
			 *     417 (Expectation Failed) must be returned (HTTP 1.1).
			 *  - Max-Forwards 14.31 (TRACE, OPTIONS)
			 *  x Proxy-Authenticate not to the clients
			 *  x Proxy-Authorization
			 *  - Transparent proxy <--> proxy differences
			 *  - If ranges (multipart data), the range should be forwarded to client,
			 *    not stored if caching is prohibited.
			 *  - "Vary:" only from clients, not from proxy
			 *    - transparent proxy copies "Vary:" as a client?
			 *  - "Via:" should be set
			 *  - "Warning:" can be set
			 *  - Security considerations (proxies are men-in-the-middle, SSL, ... )
			 */
			$requeststring = "";
			// PHP known allowed headers
			$allowheaders = array ( 
				'User-Agent:' => 'HTTP_USER_AGENT', 
				'Accept:' => 'HTTP_ACCEPT', 
				'Accept-Language:' => 'HTTP_ACCEPT_LANGUAGE', 
				'Accept-Encoding:' => 'HTTP_ACCEPT_ENCODING', 
				'Connection:' => 'HTTP_CONNECTION'
			);
			// lighttpd, pois:
			// 'Authorization:' => 'PHP_AUTH_DIGEST' 
			// 'Accept-Charset:' => 'HTTP_ACCEPT_CHARSET', 
			if( ! $_SERVER ){
				//echo "\n<!-- Error: Variable _SERVER does not exist (copy_by_method). -->";
				$requeststring .= "HEAD"; // dummy
			}
			if( $_SERVER && $_SERVER['REQUEST_METHOD'] == 'GET'){ 
				$requeststring .= "GET";
			}else if( $_SERVER ){
				$requeststring .= $_SERVER['REQUEST_METHOD'];
			}
			if( $querystring === "" )
				$requeststring .= " /";
			else
				$requeststring .= " ";
			$requeststring .= $querystring;
			if( strlen( $querystring ) > 255 ){
				//echo "\n<!-- Warning: query string is over the recommended URI size 255 octets. -->";
			}
			if($_SERVER['SERVER_PROTOCOL'])
				$requeststring .= " " . $_SERVER['SERVER_PROTOCOL'] . "\r\n";
			else
				$requeststring .= " HTTP/1.1\r\n";
			$requeststring .= "Host: ";
			$requeststring .= $hostnamestring;
			$requeststring .= "\r\n";
			// Accept: has to be present to read the responce:
			if( $_SERVER['HTTP_ACCEPT'] ){ // In HTTP receive, 5), Accept: has to be copied to read multibyte responces
				$requeststring .= "Accept: " . $_SERVER['HTTP_ACCEPT'] . "\r\n";
			}
			// Other allowed header fields:
			foreach($allowheaders as $hdr => $hdrval){
				if( $hdr !== "Accept:" ) // && $hdr !== "Connection:" )
					if( $_SERVER[$hdrval] )
						$requeststring .= $hdr . " " . $_SERVER[$hdrval] . "\r\n";
			}
			//$requeststring .= "Connection: close \r\n"; // non persistent connection, will be closed after responce.

			$requeststring .= $extrahttpheaders;

			// Other HTTP keys here
			$this->copy_cookies( $requeststring ); // Ends the HTTP header

			if ($_SERVER['REQUEST_METHOD'] == 'POST'){ // final
				$requeststring .= $poststring; // HTTP-POST -key-value pairs in place of HTML
			}
			//$this->debug_text( "\ncopy_by_method: REQUESTSTRING: [ <PRE>$requeststring</PRE> ] \n");

			return $requeststring;
		}

		/*
		 * Set all client sent cookies to the server request and end the requests header part.
		 *
		 * RFC 6265 "HTTP State Management Mechanism": 
		 */
		public function copy_cookies( $requeststring ){
			$indx=0; $clen=0;
			if( ! $requeststring ){
				//echo "\n<!-- Error: requeststring was uninitialized (copy_cookies). -->";
			}
			if( isset( $_SERVER['HTTP_COOKIE'] ) ){ // Apache does not have this
				//echo "<!-- ISSET HTTP_COOKIE -->";
				$clen = count($_SERVER['HTTP_COOKIE'] ) ;
				for($indx=0; $indx<$clen; ++$indx){
					$requeststring .= "Cookie:";
					$requeststring .= $_SERVER['HTTP_COOKIE'][$indx];
					$requeststring .= "\r\n"; // last #1
				}
				//$requeststring .= "\r\n"; // last #1.2 28.10.2014
			}else if( isset( $_COOKIE ) ){ // Apache has this. Lighttp does not. PROBLEM 28.10.2014
				$clen = count($_COOKIE);
				//echo "<!-- ISSET _COOKIE -->";
				for($indx=0; $indx<$clen; ++$indx){
					$requeststring .= "Cookie:";
					$requeststring .= $_COOKIE[$indx];
					$requeststring .= "\r\n"; // last #1
				}
				//$requeststring .= "\r\n"; // last #1.2 28.10.2014
			}else{
				//echo "<!-- NO COOKIES. -->\n";
				$requeststring .= "\r\n"; // last #1
			}
			$requeststring .= " 	\r\n\r\n"; // twice, #2, end of header
			//$requeststring .= "\r\n"; // third time, debug, #3, end of header 28.10.2014
		}
		/*
		 * Copies by method GET and POST to a request and add client sent cookies.
		 * Send the request and outputs the reply.
		 *
		 * Accept: MUST NOT contain any multipart/byteranges the proxy can't read.
		 */
		public function http_proxy_by_string( $hostnamestring, $httppostextra, $httpextra, $urlpath, $urlextra, $appendnametouri ){
			$poststring=""; $querystring=""; $req=""; $err="";

			$hostname = $hostnamestring;
			
			$querystring = $this->decode_GET_url_query( $urlextra , $appendnametouri );

			//if( $querystring!="" )
			//	$querystring = "?" . $querystring;

			$poststring = $this->decode_POST_string( $httppostextra );
			
			$querystring = $urlpath . "" . $querystring;

			$req = $this->copy_by_method( $hostnamestring, $querystring, $poststring, $httpextra );
			$req .= " 	\r\n\r\n"; // end of message/end of headers?? , needed

			$this->debug_text( "<STRONG> 3. Request <PRE>[$req]</PRE></STRONG>");

			if($_SERVER['REQUEST_METHOD'] === 'HEAD'){ 
				$this->debug_text( "Proxy by string: HEAD, calling parent::proxy_by_string .\n" );
				return parent::proxy_by_string( $req );
			}else{
				$this->debug_text( "Proxy by string: calling this->http_proxy_receive() . ");
				if( parent::proxy_request( $req ) === false ){
					//echo "\n<!-- Error: error $err returned from proxy_request. (http_proxy_by_string) -->\n";
				}
				return $this->http_proxy_receive();
			}
			
		}
		public function debug_text( $dtext ){
			if( defined('DEBUG') )
				echo "<STRONG><PRE> $dtext </PRE></STRONG>\n";
			if( defined('DEBUGFILE') ) // not working 29.10.2014
				file_put_contents("/usr/local/www/DOCROOT/cgi/HttpRvpDebug.log", $dtext);
		}

		protected function print_header_text( $htext ){
			if( ! defined('PRINTNOHEADERS') ){
				if( ! defined('ECHOHEADERS') )
					header($htext, true); // Apache 3.11.2014
				else
					echo $htext . "\r\n"; // Lighttpd
			}
		}
		protected function output_message_and_count_contentlen( $maxlength, $extraheaders ){
			$err=0; $bcount=0;
			$this->debug_text("GOING TO REWRITECONTENTLENGTH");
			$err = $this->read_until_EOF( $maxlength );
			$bcount = strlen( $err ); // zero counts (false=0)
			$this->print_header_text( "Content-length: $bcount " );
			if( $extraheaders!="" )
				$this->print_header_text( $extraheaders ); // Cookies
			$this->print_header_text( " 	\r\n" ); // end of headers
			echo $err; // message
			return $bcount;
		}
		/*
	 	 * Reads HTTP responce and outputs the result.
	 	 */
		protected function http_proxy_receive(){
			/*
			 * HTTP message ends after:
			 * 1) Header, if message is not included (responce-methods: HEAD, (propably: TRACE, CONNECT))
			 * 2) if Transfer-encoding is not identity and the message is not terminated using the connection,
			 *    it is chunked.
			 * 3) If "Content-Length" is present without Transfer-encoding (being "identity"), it's size is both entity and transfer 				 *    lengths in octets.
			 * 4) If multipart/byteranges is and transfer-length is not specified, media-type defines the transfer length.
			 *    The server must be sure the client understaends the message: Content-Type and Accept, "14.1 Accept"
			 * 5) Server closes the connection.
			 *
			 * IANA: "identity" is withdrawn in errata:
			 *   http://www.iana.org/assignments/http-parameters/http-parameters.xml#transfer-coding
			 *
			 * cURL implements these. Implementation:
			 */
			$readline=""; $transencoding=""; 
			$contentlen=""; 
			$contentlennumber=0;
			$savedheadlinecount=0;
			$headerline="";
			$headerarray=["",""];
			$hline="";
			$err=1;
			$bytesoutput=0; 
			$chunksize=0; $chunktext="inittext";
			$chunkedoutput="";
			$chreadlength = 0;
			$extraheaders = "";

			if( ! $this ){
				//echo "\n<!-- Error: object this does not exist. (http_proxy_receive) -->";
				return false;
			}
			if( ! $this->socketfd ){
				//echo "\n<!-- Error: socketfd does not exist, exit. (http_proxy_receive) -->";
				return false;
			}


			/*
			 * Read line until "Transfer-encoding" and "Content-Length" is read. */
			// stream_get_line jaa jumiin, PHP: be able to fseek (<- joten streams:ia ei voi kayttaa)
			

			while( $err!=-1 && $err!=-2 ){
				$headerline = $this->read_header_line( 4096 );
				if($headerline==-1){
					//echo "\n<!-- Error: $headerline. (http_proxy_receive) -->";
					$err=-1;
					break;
				}
				if( $headerline==-3 ){ // end of file
					//echo "\n<!-- Error, EOF: $headerline. (http_proxy_receive) -->";
					$err=-3;
				}
				if( $headerline==-2 ){ // end of header
					$err=-2;
					break;
				}
				$headerarray = explode(":", $headerline );
				// hTtP cAsE inSeNsItIVe
				if( $headerarray ){ // KORJAUS 29.10.2014
				   if( count( $headerarray ) != 0 ){ // KORJAUS 29.10.2014
					//echo "<PRE>READING HEADER $headerline </PRE>";
					switch( strtolower( trim( $headerarray[0], " \t\n\r\x0B" ) ) ){ // trim off characters used in folding
						case "transfer-encoding":
							$transencoding = trim( $headerarray[1], " \t\n\r\x0B" ); 
							$savedheadlinecount++;
							if( ! defined("REMOVECHUNKS") ){
								if( ! defined('ECHOHEADERS') )
									header( $headerline , true ); // Apache
								else
									echo( $headerline . "\r\n" ); // Lighttpd
							}
							break;
						case "content-length": // strcasecmp (
							$contentlen = trim( $headerarray[1], " \t\n\r\x0B" );
							$contentlennumber = intval( $contentlen, 10);
							$savedheadlinecount++;
							break;
						case "proxy-authenticate": 	// removed
							break;
						case "proxy-authorization": 	// removed
							break;
						case "set-cookie":
							$extraheaders = trim( $headerarray[1], " \t\n\r\x0B" );
							break;
						case "content-type":		// Specially in all CGI programs, MIME has to be present ( Apache, not Lighttpd )
							if( ! defined('PRINTNOHEADERS') || defined('CGIENABLE') ){ 
								if( ! defined('ECHOHEADERS') )
									header( $headerline, true ); // Apache 3.11.2014
								else
									echo( $headerline . "\r\n" ); 	// Print to headers if CGI was chosen, Lighttpd <3.11.2014
							}
							break;
						case "": 	// extra "\r\n" ? end of headers, Apache?
							break;	// removed KORJAUS 29.10. tulos: myos XML otsikot puuttuvat
						default:	// Removes "Transfer-encoding:" and "Content-Length"
							if( ! defined('CGIENABLE') )
								$this->print_header_text( "$headerline" ); 
							// HTTP/1.1 400 org.oclc.wskey.api.WSKeyException: WsKeyParam(wskey) not found in request
							continue;
					}
					//echo "<PRE>END READING HEADER $headerline </PRE>";
				   }
				}
				$this->debug_text( "HERE: $headerline");
			}


			/*
			 * 0) Headers were printed without "Content-length:" and "Transfer-encoding:". */
			unset( $headerarray );
			unset( $headerline );

			$this->debug_text( "AFTER HEADERS, Content-Length contentlen (int: $contentlennumber): $contentlen");
			
			/*
			 * 1) */
			if( $_SERVER )
				if( $_SERVER['REQUEST_METHOD'] === 'TRACE' || $_SERVER['REQUEST_METHOD'] === 'CONNECT' || 
						$_SERVER['REQUEST_METHOD'] === 'HEAD'){ 
					if( ! defined('ECHOHEADERS') )
						header( " 	\r\n\r\n", true ); // End of headers, Apache
					else
						echo " 	\r\n\r\n"; // End of headers, Lighttpd
					return true;
				}
			
			/*
			 * 3) and 2) (and 5)) */
			if( ( $contentlen == "" && $savedheadlinecount<=1 ) || \
				strpos($transencoding, "chunked") !== false ){
				/*
				 * chunked or terminated closing the connection
				 * 4.4 3)
				 */
				/*
				 * Cached version, removes chunks, reads the contents first to memory and
				 * sets "Content-length:" prior to sending. 
				 */

				if( defined('REMOVECHUNKS') )
					$this->debug_text( "GOING TO CHUNKED->UNCHUNKED RESPONCE");
				else
					$this->debug_text( "GOING TO CHUNKED RESPONCE");
					

				//if( $contentlen !== "" && $savedheadlinecount>1 ) // Read first contentlen and then chunks ?
				$chunktext = $this->read_line( 128 );				// read chunk size
				if( ! defined('REMOVECHUNKS') ){ // TESTI 7.11.2014: poisto ei vaikuta
					$this->print_header_text(" 	\r\n"); // end of headers section
					//$this->print_header_text(""); // TESTI 2 7.11.2014: lisays ei vaikuta
				} // molempien poisto ei vaikuta
				while( $chunktext!=false ){ 
					if( ! defined('REMOVECHUNKS') ){
						echo $chunktext;
						$this->debug_text( "Output chunk size text[".$chunktext."]" );
					}
					$this->debug_text( "Read chunk size text:" . $chunktext );
					$chunktext .= " ";
					$chunksize = hexdec( strtok($chunktext," ") );
					if( $chunksize === 0 ){ // Last chunk
						$chunktext=false; 
						break;
					}
					$this->debug_text( "Read chunk size:" . $chunksize );
					if( $chunksize > 0 ){ // Read until next chunk
						$chreadlength = 0;
						while( $chreadlength < ($chunksize+2) ){ // \r\n is included in fgets but not in chunksize
							if( $chreadlength >= $chunksize && $chreadlength < ($chunksize+2) ){ // read the last CRLF
								$this->debug_text( "<!-- chunk 4 (last CRLF) -->" );
								$chunktext = $this->read_line( 2 );
								if( ! defined('REMOVECHUNKS') ){
									echo $chunktext;
									$bytesoutput += strlen( $chunktext );
								}
							}else{
								if( ($chunksize-$chreadlength) < $chunksize && ($chunksize-$chreadlength) > 0 ){
									$this->debug_text( "<!-- chunk 3 (rest of the chunk) -->" );
									$chunktext = $this->read_line( ($chunksize-$chreadlength+1) ); // read the rest of the chunk + CR + NL
								}else{
									$this->debug_text( "<!-- chunk 2 (one chunk) -->" );
									$chunktext = $this->read_line( $chunksize ); // read chunk (new line is included [fgets] )
								}
								if( defined('REMOVECHUNKS') ){
									$chunkedoutput .= $chunktext;
								}else{
									echo $chunktext;
									$bytesoutput += strlen( $chunktext );
								}
							}
							if( $chunktext == false ){
								//echo "\n<!-- Error reading chunks, err $chunktext. -->";
							}
							$chreadlength += strlen( $chunktext );
							$this->debug_text( "Read chunk text: [ $chunktext ] $chreadlength/$chunksize " );
						}
					}
					$chunktext = $this->read_line( 128 );	// read chunk size
				}
				
				if( defined('REMOVECHUNKS') ){
					$bytesoutput = strlen( $chunkedoutput );
					$this->print_header_text( "Content-length: $bytesoutput " );
					if( $extraheaders!="" )
						$this->print_header_text( $extraheaders );
					$this->print_header_text(" 	\r\n"); // end of headers section
					echo $chunkedoutput;
				}
				//if( defined('REMOVECHUNKS') )
					//echo "<STRONG> READ AND REMOVED CHUNKS. </STRONG>";
				//else
					//echo "<STRONG> READ CHUNKS. </STRONG>";
				return true;

			}else if( $contentlen != "" && strpos($transencoding, "chunked") === false ){
				/*
				 * not chunked (can be terminated closing the connection)
				 * #1 by content-length
				 */

				$this->debug_text( "GOING TO UNCHUNKED RESPONCE");
				if( defined('REWRITECONTENTLENGTH') ){ // Slow and resource consuming
					$this->output_message_and_count_contentlen( $contentlennumber, $extraheaders );
					return true;
				}else{ // Fast
					if( $contentlen != "" )
						$this->print_header_text( "Content-length: $contentlen " ); // Decimal number text
					$this->print_header_text(" 	\r\n"); // end of headers section
					$err = $this->read_to_output( $contentlennumber ); // integer number
					if( $err )
						$bytesoutput += $err;
					//echo "<STRONG> READ NOT CHUNKED. </STRONG>";
					return true;
				}
			}else{ 
				/* #2 by closing connection and a shorter timeout if nothing happens */
				if( $transencoding !== "" )
					$this->print_header_text("Transfer-encoding: $transencoding ");
				while( $err !== -1 ){
					if( $contentlen !== "" ){
						if( defined('REWRITECONTENTLENGTH') ){
							$err = $this->output_message_and_count_contentlen( $contentlennumber, $extraheaders );
						}else{
							if( $contentlen != "" )
								$this->print_header_text("Content-length: $contentlen ");
							$this->print_header_text(" 	\r\n");
							$err = $this->read_to_output( $contentlennumber );
						}
					}else{
						set_time_limit(10); // script hang timeout in seconds
						if( defined('REWRITECONTENTLENGTH') ){
							$err = $this->output_message_and_count_contentlen( 65536, $extraheaders );
						}else{
							$this->print_header_text(" 	\r\n");
							$err = $this->read_to_output( 65536 ); // 4 x 16384
						}
					}
					if( $err )
						$bytesoutput += $err;
					debug_text( "Bytesoutput $bytesoutput .");
				}
				//echo "<STRONG> READ NOT CHUNKED, V2, MISSING HEADERS. </STRONG>";
				return true;
			}

			/*
			 * 5) Client sent "Accept:" has to be copied to the server in request. */
				
		     	/*	
				Pseudo-code of chunks, RFC 2616 HTTP:
				
				length := 0
		     		read chunk-size, chunk-extension (if any) and CRLF
		     		while (chunk-size > 0) {
		      		read chunk-data and CRLF
		          		append chunk-data to entity-body
		          		length := length + chunk-size
		          		read chunk-size and CRLF
		       	}
		       	read entity-header
		       	while (entity-header not empty) {
		       		append entity-header to existing header fields
		          		read entity-header
		       	}
		       	Content-Length := length
		       	Remove "chunked" from Transfer-Encoding
			*/

		}
	}
	
?>
