<?php
require("./classes/http_client.class.php");

$url1 = "http://www.receita.fazenda.gov.br/pessoajuridica/cnpj/cnpjreva/cnpjreva_solicitacao.asp";
$url2 = "http://www.receita.fazenda.gov.br/pessoajuridica/cnpj/cnpjreva/cnpjreva_solicitacao2.asp";
$url3 = "http://www.receita.fazenda.gov.br/pessoajuridica/cnpj/cnpjreva/captcha/gerarCaptcha.asp";
$url4 = "http://www.receita.fazenda.gov.br/pessoajuridica/cnpj/cnpjreva/captcha/gerarSom.asp";

$useragents	= file( "./classes/useragents.txt" );


$erros = array(2,15,44,46,52,57,84,97);


//for ($i=59; $i <= 59; $i++) {
foreach ($erros as $i) {

	$http_client = new http_client();
	$useragent = trim($useragents[array_rand($useragents)]);
	
	$http_client->set_useragent($useragent);
	$file_name = str_pad( $i, 3, '0', STR_PAD_LEFT);
	print(">>>> Obtendo: $file_name\n");

	$http_client->request( "GET" , $url1 );
	$http_client->request( "GET" , $url2 );
	$http_client->request( "GET" , $url3 );
	file_put_contents( "./images/$file_name.png", $http_client->get_body() );
	$http_client->request( "GET" , $url4 );
	file_put_contents( "./sounds/$file_name.wav", $http_client->get_body() );
	$http_client->close();

}

die("Done.\n");