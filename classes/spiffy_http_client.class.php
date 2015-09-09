<?php
/**
 * Cliente HTTP
 *
 * @package		 spiffy
 * @subpackage	 classes
 * @author		 Rodrigo Silva <rodrigo.silva@grupofolha.com.br>
 * @since		 2014-10-22
 */

/**
 * Cliente HTTP
 *
 * @author  Rodrigo Silva <rodrigo.silva@grupofolha.com.br>
 * @since   2014-10-22
 */
class spiffy_http_client {

	/**
	 * Guarda o objeto de sess�o cURL
	 *
	 * @var	resource
	 */
	protected $curl_session ;

	/**
	 * Path do arquivo de cookie. Pode ser setado um valor pelo usu�rio ou assumir� um nome default.
	 *
	 * @var	string
	 */
	protected $cookiejar ;

	/**
	 * Guarda as informa��es da ultima requisi��o ("url" , "content_type" , "http_code" , "header_size",
	 * "request_header",  etc.)
	 *
	 * @var	array
	 */
	protected $info ;

	/**
	 * Guarda o cabe�alho da resposta da ultima requisi��o.
	 *
	 * @var	string
	 */
	protected $header ;

	/**
	 * User-Agent utilizado na requisi��o. Como default coloquei o do Firefox do meu Desktop. Pode setar um diferente.
	 *
	 * @var	string
	 */
	protected $useragent = "Mozilla/5.0 (X11; Ubuntu; Linux i686; rv:23.0) Gecko/20100101 Firefox/23.0" ;

	/**
	 * Tempo m�ximo em segundos permitido que o cURL execute. Setando em '0' (zero), n�o expira.
	 *
	 * @var	int
	 */
	protected $timeout = 10 ;

	/**
	 * N�mero m�ximo de vezes em que um "Location: " recebido no cabe�alho da resposta ser� seguido.
	 * � bom limitar para evitar redirect infinito.
	 *
	 * @var	int
	 */
	protected $max_redirects = 30 ;

	/**
	 * Habilita o modo de debug para dar manuten��o.
	 *
	 * @var	bool
	 */
	public $debug = false ;

	/**
	 * Inicia o cURL
	 *
	 * @return  void
	 */
	public function init_session() {
		if ( ! isset( $this->curl_session ) ) {
			$this->curl_session = curl_init() ;
		}
	}

	/**
	 * Executa a requisi��o por GET ou POST
	 *
	 * @param 	string	$method Metodo do request (GET ou POST)
	 * @param 	string	$url URL da requisi��o
	 * @param 	array	$parameters lista de par�metros (chave => valor) que devem ser passados no POST
	 * @param 	string	$referer URL de origem da requisi��o
	 *
	 * @return  string	http_code retornado pela requisi��o ('200' em caso de sucesso)
	 */
	public function request( $method , $url , $parameters = array() , $referer = "" ) {
		$response = null ;
		$this->init_session() ;
		$this->set_default_options() ;

		switch ( $method ) {
			case "GET" :
				curl_setopt( $this->curl_session , CURLOPT_HTTPGET , true ) ;
				break ;

			case "POST" :
				curl_setopt( $this->curl_session , CURLOPT_POST , true ) ;

				$query_string = false ;
				if ( is_array( $parameters ) ) {
					$query_string = http_build_query( $parameters ) ;
				}

				curl_setopt( $this->curl_session , CURLOPT_POSTFIELDS , $query_string ) ;
				break ;

			default :
			throw new Exception( "O metodo " . $method . " n�o � suportado." ) ;
		}

		curl_setopt( $this->curl_session , CURLOPT_URL , $url ) ;

		if ( $referer != "" ) {
			curl_setopt( $this->curl_session , CURLOPT_REFERER , $referer ) ;
		}

		$this->apply_custom_options() ;

		// Resposta da requisi��o (Header + Body)
		$response = curl_exec( $this->curl_session ) ;
		// Guarda o array com as informa��es da requisi��o e sua resposta
		$this->info = curl_getinfo( $this->curl_session ) ;
		// Separa o header do body
		$header_len = $this->info{"header_size"} ;
		$this->header = substr( $response , 0 , $header_len ) ;
		$this->body = substr( $response , $header_len ) ;

		if ( $this->debug ) {
			$this->show_info() ;
		}

		if ( ! isset( $this->info{"http_code"} ) ) {
			return false ;
		}

		return $this->info{"http_code"} ;
	}

	/**
	 * Seta as configura��es b�sicas do cURL
	 *
	 * @return  void
	 */
	protected function set_default_options() {
		curl_setopt( $this->curl_session , CURLOPT_ENCODING , "" ) ;
		curl_setopt( $this->curl_session , CURLOPT_FOLLOWLOCATION , true ) ;
		curl_setopt( $this->curl_session , CURLOPT_HEADER , true ) ;
		curl_setopt( $this->curl_session , CURLOPT_RETURNTRANSFER , true ) ;
		curl_setopt( $this->curl_session , CURLOPT_VERBOSE , $this->debug ) ;
		curl_setopt( $this->curl_session , CURLINFO_HEADER_OUT , true ) ;
		//curl_setopt( $this->curl_session , CURLOPT_SSL_VERIFYPEER , false ) ;

		$this->create_cookiejar() ;
		curl_setopt( $this->curl_session , CURLOPT_COOKIEJAR , $this->cookiejar ) ;
		curl_setopt( $this->curl_session , CURLOPT_COOKIEFILE , $this->cookiejar ) ;

		if ( $this->useragent ) {
			curl_setopt( $this->curl_session , CURLOPT_USERAGENT , $this->useragent ) ;
		}

		if ( $this->timeout !== null ) {
			// Default timeout is 0 (zero) which means it never times out during transfer.
			curl_setopt( $this->curl_session , CURLOPT_TIMEOUT , $this->timeout ) ;
		}

		if ( $this->max_redirects !== null ) {
			// Default -1, unlimited
			curl_setopt( $this->curl_session , CURLOPT_MAXREDIRS , $this->max_redirects ) ;
		}

		if ( isset( $this->auth_user ) ) {
			curl_setopt( $this->curl_session , CURLOPT_HTTPAUTH , $this->auth_type ) ;
			curl_setopt( $this->curl_session , CURLOPT_USERPWD , sprintf( "%s:%s" , $this->auth_user , $this->auth_pass ) ) ;
		}

		if ( isset( $this->port ) ) {
			curl_setopt( $this->curl_session , CURLOPT_PORT , $this->port ) ;
		}
	}

	/**
	 * Seta op��es customizadas ao cURL. Pode ser setado qualquer um das op��es descritas em
	 * http://php.net/manual/en/function.curl-setopt.php e n�o podemos garantir o comportamento.
	 * Portanto, use com cuidado.
	 *
	 * @param 	array	$options Lista de op��es a serem setadas
	 *
	 * @return  void
	 */
	public function set_custom_options( array $custom_options ) {
		$this->custom_options = $custom_options ;
	}

	private function apply_custom_options() {
		if ( isset( $this->custom_options ) && is_array( $this->custom_options ) ) {
			foreach ( $this->custom_options as $key => $value ) {
				if ( ! curl_setopt( $this->curl_session , $key , $value ) ) {
					throw new Exception( "N�o foi poss�vel setar o par�metro '" . $key . "' com o valor '" . $value ."'." ) ;
				}
			}
		}
	}

	/**
	 * Seta as credenciais de autentica��o. 
	 *
	 * @param 	string	$user Nome do usu�rio
	 * @param 	string	$pass Senha
	 * @param 	int		$type Tipo de conex�o (CURLAUTH_BASIC, CURLAUTH_DIGEST, CURLAUTH_GSSNEGOTIATE,
	 *			CURLAUTH_NTLM, CURLAUTH_ANY e CURLAUTH_ANYSAFE)
	 *
	 * @return  void
	 */
	public function set_authentication( $user , $pass , $type = CURLAUTH_BASIC ) {
		$this->auth_user = $user ;
		$this->auth_pass = $pass ;
		$this->auth_type = $type ;
	}

	/**
	 * Retorna o cabe�alho
	 *
	 * @return  string
	 */
	public function get_header() {
		return $this->header ;
	}

	/**
	 * Retorna o corpo da p�gina
	 *
	 * @return  string
	 */
	public function get_body() {
		return $this->body ;
	}

	/**
	 * Retorna as informa��es da requisi��o
	 *
	 * @return  array
	 */
	public function get_info() {
		return $this->info ;
	}

	/**
	 * Retorna o path do arquivo de cookie
	 *
	 * @return  string 
	 */
	public function get_cookiejar() {
		return $this->cookiejar ;
	}

	/**
	 * Define o path do arquivo do cookie. O arquivo precisa ter permiss�o de escrita.
	 *
	 * @param	string 	$cookiejar path do arquivo do cookie
	 *
	 * @return  boolean
	 */
	public function set_cookiejar( $cookiejar ) {
		if ( $cookiejar !== false && ! is_writable( $cookiejar ) ) {
			return false ;
		}

		$this->cookiejar = $cookiejar ;
		return true ;
	}

	/**
	 * Define o User-Agent
	 *
	 * @param	string 	$useragent User-Agent
	 *
	 * @return  void
	 */
	public function set_useragent( $useragent ) {
		$this->useragent = $useragent ;
	}

	/**
	 * Setter para o timeout
	 *
	 * @param	int 	$timeout Tempo em segundos
	 *
	 * @return  void
	 */
	public function set_timeout( $timeout ) {
		$this->timeout = $timeout ;
	}

	/**
	 * Setter para o max_redirects
	 *
	 * @param	int 	$max_redirects n�mero de redirecionamentos m�ximo permitido
	 *
	 * @return  void
	 */
	public function set_max_redirects( $max_redirects ) {
		$this->max_redirects = $max_redirects ;
	}

	/**
	 * Setter para a porta do Servi�o
	 *
	 * @param	int 	$port Porta do servi�o
	 *
	 * @return  void
	 */
	public function set_port( $port ) {
		$this->port = $port ;
	}

	/**
	 * Caso n�o seja definido um arquivo de cookiejar ser� criado um rand�mico
	 *
	 * @return  void
	 */
	public function create_cookiejar() {
		if ( ! $this->cookiejar ) {
			$this->set_cookiejar( tempnam( sys_get_temp_dir() , "cookiejar-" ) ) ;
		}
	}

	/**
	 * Fecha sess�o
	 *
	 * @return	  void
	 */
	public function close() {
		if ( is_resource( $this->curl_session ) ) {
			curl_close( $this->curl_session ) ;
		}

		$this->curl_session = null ;
		// Estou apagado o arquivo de cookie ao fechar a sess�o. N�o tenho certeza se devo sempre fazer isso.
		unlink( $this->cookiejar ) ;
	}

	/**
	 * Faz print da informa��o
	 *
	 * @return  void
	 */
	public function show_info() {
		print( "\n=====================================================================================\n" ) ;
		print( ">>> Request Header:\n" ) ;
		print_r( $this->info ) ;
		print( "-------------------------------------------------------------------------------------\n" ) ;
		print( ">>> Response Header:\n" ) ;
		print( $this->header ) ;
		print( "\n-------------------------------------------------------------------------------------\n" ) ;
		print( ">>> Body:\n" ) ;
		print( $this->body ) ;
	}
}
?>