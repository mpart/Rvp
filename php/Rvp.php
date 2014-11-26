<?php

	/*
	 * jounilaa 24.9.2014   
	 *
	 * PHP and extensions needed:
	 * php55-5.5.16                   "PHP Scripting Language"
	 * php55-openssl-5.5.16           SSL, "The openssl shared extension for php"
	 *
	 * Reverse proxy (Rvp) to predefined hosts. Sends requeststring to a proxyed
	 * server and outputs servers responce back to the client.
	 *
	 * Multipart or chunked http- messages can not be received.
	 * (http://www.w3.org/Protocols/rfc2616/rfc2616-sec4.html#sec4.3)
	 *
	 * Proxy uses ordinary connection bypassing CGI (RFC 3875). This is why
	 * a library (cURL or other) should be used to simplify the program code. 
	 * Possibilities to read client sent HTTP is listen in this RFC.
	 * - HTTP_ACCEPT 
	 */


	class Rvp {

		protected $socketfd;

		public function __construct1(){
			//if (!extension_loaded('sockets')) {
			//	echo "\n<!-- Extension: The sockets extension is not loaded. -->";
			//}
			//if (!extension_loaded('openssl')) {
			//	echo "\n<!-- Extension: Extension OpenSSL is not installed, SSL support is missing. -->";
			//}
			//if (!function_exists('fsockopen')) { //  built-in
			//	echo "\n<!-- Function fsockopen does not exist (needed in socket creation). -->";
			//}
			// if (!extension_loaded('php_curl')) {
			//	echo "\n<!-- Extension: Extension php_curl does not exist (needed in multipart messages). -->";
			// }
		}
		public function __construct($hoststring, $hostport){
			$argc=0; $ret=true;
                        $argc = func_num_args();
			if( ! $this ){
				//echo "\n<!-- Rvp __construct1: object this does not exist. -->";
			}
                        if( $argc >= 2 ){
				$ret = $this->init_socket($hoststring, $hostport);
				if( ! $this->socketfd ){
					//echo "\n<!-- Rvp __construct1: this->socketfd does not exist. -->";
				}
			}
			$this->__construct1();
			return $ret;
		}
		public function __destruct(){
			$this->close_socket();
		}
		protected function proxy_request( $httpstring ){
			$ret="";
			if( ! $this ){
				echo "\n<!-- Error: object this does not exist. (proxy_by_string) -->";
				return false;
			}
			if( ! $this->socketfd ){
				echo "\n<!-- Error: socketfd does not exist, exit. (proxy_by_string) -->";
				return false;
			}
			//echo "\n<H4>To server:</H4>";
			$ret = fwrite( $this->socketfd, $httpstring, strlen($httpstring) );	
			//echo "\n<H4>From server: [ </H4>";
			//echo "\n<STRONG><PRE> wrote $ret bytes. </PRE> ] </STRONG> ";
			return $ret;
		}
		/*
		 * Simple request-responce to any port and any tcp-transferred protocol (HTTP:only HEAD -request).
		 */
		public function proxy_by_string( $httpstring ){
			$out = ""; $err=0;

			//echo "Proxying by string: [$httpstring] <BR>\n";
			
			if( ! $this ){
				echo "\n<!-- Error: object this does not exist. (proxy_by_string) -->";
				if( ! $this->socketfd ){
					echo "\n<!-- Error: socketfd does not exist, exit. (proxy_by_string) -->";
					exit();
				}
			}
			//echo "Sending HTTP HEAD request...<BR>\n";
			if( $this->socketfd == false ){
				echo "<!-- Error, socketfd is missing. --> \n";
				return;
			}
			$err = $this->proxy_request( $httpstring );
			if( $err == false ){
				echo "\n<!-- Error: error $err returned from proxy_request. (proxy_by_string) -->";
			}
			//echo "OK.<BR>\n";
		
			//echo "Reading response:<BR>\n";

			/*
			 * Single request/responce. (Read length of message). */
			$err = $this->read_to_output(4096);
			if( $err == false ){
				echo "\n<!-- Error: error $err returned from read. (proxy_by_string) -->";
			}
			return $err;
		} 
		public function init_socket( $remotehost, $remoteport ){
			$errno=0; $errstr="";
			/* Get the port for the WWW service. */

			if( ! $remotehost || ! $remoteport){
				if( defined('DEBUG') )
					echo "\n<!-- Error: remotehost or remoteport uninitialized ( -1 ) (init_socket), exit. -->";
				exit();
			}

			/* Create a SSL TCP/IP socket. */
			$this->socketfd = fsockopen( $remotehost, $remoteport, $errno, $errstr, 10); /* 10 seconds timeout */
			if($this->socketfd == false){
				if( defined('DEBUG') ){
					echo "<!-- Failed to create socket, $errstr ($errno). \n";
					echo "     socket_strerror: [" . socket_strerror(socket_last_error()) . "] -->\n";
				}
				return false;
			}
			stream_set_timeout( $this->socketfd, 10 ); /* 10 seconds timeout */
			return true;
		} 
		/*
		 * Returns bytes read (to count them).
		 */
		public function read_until_EOF( $maxlength ){
			$retstr=""; $out="";
			while ( ( $out = fgets($this->socketfd, $maxlength) ) != false ) {
				if( ! $out )
					break;
				$retstr .= $out; 
				if( feof($this->socketfd) )
					break;
				if( strlen( $out ) < 1 )
					break;
			}
			return $retstr;
		}
		/*
	 	 * Writes directly to output.
		 */
		public function read_to_output( $maxlength ){
			/*
			 * Returns: bytes read and -1 if failed
			 */
			$bytecount=-1;
			$out="";
			while ( ( $out = fgets($this->socketfd, $maxlength) ) != false ) {
				if( ! $out )
					break;
				echo $out; 
				$bytecount = $bytecount + strlen( $out );
				if( feof($this->socketfd) )
					break;
				if( strlen( $out ) < 1 )
					break;
			}
			return $bytecount;
			
		}
		/*
		 * Is CRLF after LWSP (as in 822) 
		 */
		protected function is_linear_whitespace( $chr, $prev1, $prev2 ){
			if( $chr=="\n" && $prev1=="\r" && ($prev2=="\t" || $prev2==" ") )
				return true;
			else
				return false;
		}
		protected function is_lwsp( $chr ){
			if( $chr==" " || $chr=="\t" )
				return true;
			else
				return false;
		}
		protected function is_lwsp_pair( $chr1, $chr2 ){
			if( ( $chr1==" " || $chr1=="\t" ) && ( $chr2==" " || $chr2=="\t" ) )
				return true;
			else
				return false;
		}
		/*
		 * Return unfolded header line if success,
 		 *       returns -1 on error 
		 *               -2 if line was "\r\n" once (end of header) . 
		 *               -3 if EOF
		 * (fgets gets a character, not a one byte octet)
		 */
		public function read_header_line( $maxlength ){

// TAHAN KOHTAAN ON PAKKO TEHDA "FOLDING" 29.10.2014 2 krt: \r\n\r\n -> end of headers

/*	RFC 822 3.1.1:
             " The process of moving  from  this  folded   multiple-line
        representation  of a header field to its single line represen-
        tation is called "unfolding".  Unfolding  is  accomplished  by
        regarding   CRLF   immediately  followed  by  a  LWSP-char  as
        equivalent to the LWSP-char. "
 */

			$tline = "";	$chr=""; $prev1=""; $prev2="";
			$count = 3;
			if( ! $this ){
				echo "\n<!-- Error: object this is missing (read_header_line). -->";
				return -1;
			}
			if( ! $this->socketfd ){
				echo "\n<!-- Error: object this->socketfd is missing (read_header_line). -->";
				return -1;
			}
			// $wline = $this->read_line( $maxlength ); POISTO 29.10.2014
			$chr = fgetc( $this->socketfd ); // "Returns false on EOF."
			while( $chr!=false || ! feof($this->socketfd) ){
				if($count==0){
					if( ! $this->is_linear_whitespace( $chr, $prev1, $prev2 ) ){
						if(  $prev1=="\r" && $chr=="\n" )	
							return $tline; // END OF LINE
						if( ! $this->is_lwsp_pair( $chr, $prev1 ) && $chr!="\r" )
							$tline .= $chr;
					}else{
						// nothing
					}
				}else{	// read to buffer
					if( $count==2 ){
						if( $prev1=="\r" && $chr=="\n" )
							return -2; // END OF HEADER
					}
					if($count==1){
						if( $this->is_linear_whitespace( $chr, $prev1, $prev2 ) )
							continue;
						if( $prev2=="\r" && $prev1=="\n" )
							return -2; // can't occur here again, but just in case
						if( $prev1=="\r" && $chr=="\n" )
							return $tline; // END OF ONE CHARACTER LINE
						if( ! $this->is_lwsp_pair( $prev1, $prev2 ) ){
							if( $prev2!="\r" )
								$tline .= $prev2;
							if( $prev1!="\r" )
								$tline .= $prev1;
						}else if( $prev2!="\r" ){
							$tline .= $prev2;
						}
						if( ! $this->is_lwsp_pair( $chr, $prev1 ) ){
							if( $chr!="\r" )
								$tline .= $chr;
						}
					}
					$count -= 1;
				}
				$prev2=$prev1; $prev1=$chr;
				$chr = fgetc( $this->socketfd );
			}
			if( $chr === EOF )
				return -3;

			//$tline = trim( $wline, ' \r\n\t'); 
			//if( ! $tline ){
			//	echo "<!-- Error: tline was null, (read_header_line). -->";
			//	return -1;
			//}
			//if( substr( $wline, 0, 2) == "\r\n" ){
				// Ei koskaan tulostu (kokeiltu lighttpd:lla)
				//echo "ENDOFHEADERSENDOFHEADERSENDOFHEADERSENDOFHEADERSENDOFHEADERSENDOFHEADERSENDOFHEADERSENDOFHEADERS";
			//	return -2; // VIKA 29.10.2014, APACHE TEKEE FOLDING-OPERAATION ERI TAVALLA, HttpRvp LUULTAVASTI TULOSTI OTSIKON PAATTYMISEN
			//}else
			//	return $tline;
		}
		public function read_line( $maxlength ){
			$tline = "";
			$wline = "";
			if( ! $maxlength ){
				echo "\n<!-- Error: maxlegth is missing (read_line). -->";
				$maxlength = 1024;
			}
			if( ! $this ){
				echo "\n<!-- Error: object this is missing (read_line). -->";
				return -1;
			}
			if( ! $this->socketfd ){
				echo "\n<!-- Error: this->socketfd is missing (read_line). -->";
				return -1;
			}
			if( $maxlength <= 0 ){
				//echo "\n<!-- Error: fgets maxlength was $maxlength . -->";
				return -1;
			}
			if( $maxlength >= 134217727 || $maxlength >= ( PHP_INT_MAX-1 ) ){
				//echo "\n<!-- Error: fgets maxlength $maxlength was over the maximum limit. -->";
				return -1;
			}
			$wline = fgets( $this->socketfd , $maxlength );

			if(!$wline){
				//echo "<!-- Error: wline was null, (read_line). -->";
				//exit();
				return -1;
			}

			return $wline;

			//stream_get_line needs fseek to work.
		}
		public function close_socket(){
			$ret = false;
			if( $this->socketfd != false ){
				$ret = fclose($this->socketfd);
				if( $ret == false ){
					//echo "Error " . $ret . " fclose ( close_socket ).";
				}
				$this->socketfd = false;
			}
		}
	} 
	
?>
