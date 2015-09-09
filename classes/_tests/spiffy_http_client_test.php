<?php
/**
 * Teste unitário da classe spiffy_http_client.
 *
 * @package spiffy-framework
 * @author  Elton Moura <elton.moura@grupofolha.com.br>
 * @since   2015-09-01
 */

/**
 * Teste unitário da classe spiffy_http_client.
 *
 * @package spiffy-framework
 * @author  Elton Moura <elton.moura@grupofolha.com.br>
 * @since   2015-09-01
 */
class spiffy_http_client_test extends PHPUnit_Framework_TestCase {

	/**
	 * Serviço de teste.
	 * Esta pagina responde em formato JSON o conteúdo de _SERVER, _GET e _POST. A intenção é apliar
	 * para que ele possa ser usada para testar autenticação entre outras funcionalidades.
	 * @todo Migrar para um ambiente mais estável que este.
	 **/
	const MOCK_SERVICE = "http://dev.mxzypkt.corp.folha.com.br/esantos/mock_http_service.php" ;

	/**
	 * Testa uma requisição com GET
	 *
	 * @return void
	 */
	public function test_successful_get() {
		$http_client = new spiffy_http_client() ;

		$parameters = array( "param1" => "A" , "param2" => "B" , "param3" => "" ) ;
		$http_code = $http_client->request( "GET" , self::MOCK_SERVICE . "?" . http_build_query( $parameters ) ) ;
		$server_info = json_decode( $http_client->get_body() , true ) ;

		$this->assertEquals( $http_code , "200" ) ;
		$this->assertEquals( $server_info{"_SERVER"}{"REQUEST_METHOD"} , "GET" ) ;
		$this->assertEquals( $server_info{"_GET"} , $parameters ) ;
	}


	/**
	 * Testa uma requisição com POST
	 *
	 * @return void
	 */
	public function test_successful_post() {
		$http_client = new spiffy_http_client() ;

		$parameters = array( "param1" => "A" , "param2" => "B" , "param3" => "" ) ;
		$http_code = $http_client->request( "POST" , self::MOCK_SERVICE , $parameters ) ;
		$server_info = json_decode( $http_client->get_body() , true ) ;

		$this->assertEquals( $http_code , "200" ) ;
		$this->assertEquals( $server_info{"_SERVER"}{"REQUEST_METHOD"} , "POST" ) ;
		$this->assertEquals( $server_info{"_POST"} , $parameters ) ;
	}

	/**
	 * Testa autenticação
	 *
	 * @return void
	 */
	public function test_authentication() {
		$http_client = new spiffy_http_client() ;

		$user = "myusername" ;
		$pass = "mypassword" ;
		$http_client->set_authentication( $user , $pass ) ;
		$http_code = $http_client->request( "POST" , self::MOCK_SERVICE ) ;
		$server_info = json_decode( $http_client->get_body() , true ) ;

		$this->assertEquals( $server_info{"_SERVER"}{"PHP_AUTH_USER"} , $user ) ;
		$this->assertEquals( $server_info{"_SERVER"}{"PHP_AUTH_PW"} , $pass ) ;
	}

	/**
	 * Testa a busca de endereço.
	 * Este serviço é mantido pela equipe de Java. Se houver alguma mudança no serviço
	 * será necessário atualizar este teste.
	 *
	 * @return void
	 */
	public function test_address_search() {
		$http_client = new spiffy_http_client() ;

		$url = "http://endereco.folha.com.br" ;
		$port = 80 ;
		$username = "folhaonline" ;
		$password = "f01H@0n1!N3" ;
		$time = time() ;
		$method = "Endereco.getEndereco" ;
		$zipcode = intval( "04117040" ) ;

 		$content = array(
 			array(
				"timestamp" => intval( $time ) ,
				"usuario" => $username  ,
				"senha" => md5( $time . $password ) ,
			) ,
			$zipcode ,
		) ;

		$xmlrpc = xmlrpc_encode_request( $method , $content ) ;

		//print_r($xmlrpc); exit;
		$header = array() ;
		$header[] = "Content-type: text/xml; charset=iso-8859-1" ;
		$header[] = "Content-length: " . strlen( $xmlrpc ) . "\r\n" ;
		$header[] = $xmlrpc ;

		$custom_options = array(
			CURLOPT_CUSTOMREQUEST => "POST" ,
			CURLOPT_HTTPHEADER => $header ,
			CURLOPT_SSL_VERIFYPEER => true ,
			CURLOPT_SSL_VERIFYHOST => 0 ,
			CURLOPT_CAINFO => "" ,
		);

		#print_r( $custom_options ) ;

		$http_client->set_custom_options( $custom_options ) ;
		$http_client->set_port( $port ) ;
		$http_code = $http_client->request( "POST" , $url ) ;
		
		$http_client->show_info() ;

		#$this->assertEquals( $server_info{"_SERVER"}{"PHP_AUTH_USER"} , $user ) ;
		#$this->assertEquals( $server_info{"_SERVER"}{"PHP_AUTH_PW"} , $pass ) ;
	}	
}
