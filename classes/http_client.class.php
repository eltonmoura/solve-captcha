<?php
/**
 * Classe http_cliente.
 *
 * @package		mananciais
 * @subpackage	classes
 * @author		Nэkolas Fernandes (nikolas.fernandes@grupofolha.com.br)
 * @since		2014-11-27 16:38
 */

/**
 * Classe utilizada para buscar o conteњdo do site do TSE
 *
 * @package		mananciais
 * @subpackage	classes
 * @author		Nэkolas Fernandes (nikolas.fernandes@grupofolha.com.br)
 * @since		2014-11-27 16:38
 */
class http_client {

	protected $curl_session = null ;
	private $_cookiejar = "/tmp/_cookiejar.txt" ;
	private $_info = null ;
	private $_header = null ;
	
	private $_useragent = "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2272.118 Safari/537.36";
	private $_useragent_array = array();
	
	private $_timeout = 100 ;

	/**
	 * Inciando o curl
	 *
	 * @return	void
	 */
	public function __construct() {

		if ( ! function_exists("curl_init") ) {
			throw new Exception("A extensуo CURL щ requerida e nуo estс habilitada.");
		}
		$this->curl_session = curl_init() ;
	}

	/**
	* Executa a requisiчуo por GET ou POST
	* retorna o _http_code ou false caso nуo consiga recuperar
	*
	**/
	public function request( $method , $url , $parameters = array() , $referer = "" ) {
		$response = null ;
		if ( $method == "GET" ) {
			curl_setopt( $this->curl_session , CURLOPT_HTTPGET , true ) ;
		}
		else {
			curl_setopt( $this->curl_session , CURLOPT_POST , true ) ;
			$fields_string = "" ;
			foreach ( $parameters as $key => $value ) {
				$fields_string .= $key . "=" . urlencode( $value ) . "&" ;
			}

			rtrim( $fields_string , "&" ) ;
			curl_setopt( $this->curl_session , CURLOPT_POSTFIELDS , $fields_string ) ;
		}

		// Configuraчѕes bсsicas
		curl_setopt( $this->curl_session , CURLOPT_URL , $url ) ;
		curl_setopt( $this->curl_session , CURLOPT_ENCODING , "" ) ;
		curl_setopt( $this->curl_session , CURLOPT_FOLLOWLOCATION , true ) ;
		curl_setopt( $this->curl_session , CURLOPT_HEADER , true ) ;
		curl_setopt( $this->curl_session , CURLOPT_RETURNTRANSFER , true ) ;
		curl_setopt( $this->curl_session , CURLOPT_VERBOSE , true ) ;
		curl_setopt( $this->curl_session , CURLINFO_HEADER_OUT , true ) ;
		curl_setopt( $this->curl_session , CURLOPT_SSL_VERIFYPEER , false ) ;

		if ( $this->_cookiejar ) {
			curl_setopt( $this->curl_session , CURLOPT_COOKIEJAR , $this->_cookiejar ) ;
			curl_setopt( $this->curl_session , CURLOPT_COOKIEFILE , $this->_cookiejar ) ;
		}

		if ( $this->_useragent ) {
			curl_setopt( $this->curl_session , CURLOPT_USERAGENT , $this->_useragent ) ;
		}

		if ( $this->_timeout !== null ) {
			curl_setopt( $this->curl_session , CURLOPT_TIMEOUT , $this->_timeout ) ;
		}

		if ( $referer != "" ) {
			curl_setopt( $this->curl_session , CURLOPT_REFERER , $referer ) ;
		}

		// Garda a resposta da requisiчуo (_header + Body)
		$response = curl_exec( $this->curl_session ) ;
		$this->_parse_response( $response ) ;

		// Guarda o array com as _informaчѕes da requisiчуo e sua resposta
		$this->_info = $this->get_info( $this->curl_session ) ;

		if ( ! isset( $this->_info["_http_code"] ) ) {
			return false ;
		}

		return $this->_info["_http_code"] ;
	}

	/**
	* Faz o parse do conteњdo da pсgina
	*
	* @return	void
	*/
	private function _parse_response( $response ) {
		$this->_header = null ;
		$this->body = null ;
		$pattern = "#^((?:HTTP/1\.[01].*?[(?:\r)\n]{3,})+)#is" ;
		if ( preg_match( $pattern , $response , $matches ) ) {
			$this->_header = $matches[1];
			$this->body = substr_replace( $response , "" , 0 , strlen( $matches[1] ) ) ;
			return true ;
		}

		return false ;
	}

	/**
	* Retorna o corpo do conteњdo resgatado pelo curl
	*
	* @return	void
	*/
	public function get_body() {
		return $this->body ;
	}

	/**
	* Resgata as informaчѕes do curl
	*
	* @return	void
	*/
	public function get_info() {
		return $this->_info ;
	}

	/**
	* Resgata as informaчѕes do curl
	*
	* @return	void
	*/
	public function get_header() {
		return $this->_header ;
	}	

	public function set_useragent( $useragent ) {
		$this->_useragent = $useragent ;
	}

	/**
	* Fecha a sessуo do curl
	*
	* @return	void
	*/
	public function close() {
		if ( is_resource( $this->curl_session ) ) {
			curl_close( $this->curl_session ) ;
		}

		$this->curl_session = null ;
		unlink( $this->_cookiejar ) ;
	}

}
?>