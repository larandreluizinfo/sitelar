<?php
/**
 * Testa o envio de um formulário de ponta a ponta (nonce, validação,
 * gravação da mensagem e redirecionamento).
 *
 * Uso: php tools/testar-formulario.php
 *
 * @package LarAndreLuiz
 */

if ( 'cli' !== PHP_SAPI ) {
	exit( 'Execute somente via CLI.' );
}

$base = getenv( 'LAR_URL' ) ?: 'http://sitelar.test';

/**
 * Baixa uma página.
 *
 * @param string $url URL.
 * @return string
 */
function html_de( $url ) {
	$ch = curl_init( $url );
	curl_setopt_array(
		$ch,
		array(
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_TIMEOUT        => 30,
		)
	);
	$body = curl_exec( $ch );
	curl_close( $ch );

	return (string) $body;
}

/**
 * Envia o formulário.
 *
 * @param string $url_forms  URL da página.
 * @param array  $campos    Campos do formulário.
 * @return array{status:int,url:string}
 */
function enviar( $url_forms, array $campos ) {
	$html    = html_de( $url_forms );
	$nonce   = preg_match( '~name="lar_form_nonce" value="([^"]+)"~', $html, $m ) ? $m[1] : '';
	$form_id = preg_match( '~name="lar_form_id" value="([^"]+)"~', $html, $m ) ? $m[1] : '';

	// O destino é o action do <form>, não a URL da página.
	$destino = preg_match( '~<form[^>]+action="([^"]+)"~', $html, $m )
		? html_entity_decode( $m[1], ENT_QUOTES, 'UTF-8' )
		: '';

	if ( ! $destino ) {
		return array(
			'status' => 0,
			'url'    => '',
			'nonce'  => $nonce,
			'form'   => $form_id,
			'erro'   => 'action do formulário não encontrado',
		);
	}

	$campos['lar_form_nonce'] = $nonce;
	$campos['action']         = 'lar_form';
	$campos['lar_form_id']    = $form_id;

	$ch = curl_init( $destino );
	curl_setopt_array(
		$ch,
		array(
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_POST           => true,
			CURLOPT_POSTFIELDS     => http_build_query( $campos ),
			CURLOPT_FOLLOWLOCATION => false,
			// Navegadores enviam Referer; o WordPress usa wp_get_referer()
			// para voltar à página do formulário.
			CURLOPT_REFERER        => $url_forms,
			CURLOPT_TIMEOUT        => 30,
		)
	);

	curl_exec( $ch );
	$status = (int) curl_getinfo( $ch, CURLINFO_RESPONSE_CODE );
	$loc    = (string) curl_getinfo( $ch, CURLINFO_REDIRECT_URL );
	curl_close( $ch );

	return array(
		'status' => $status,
		'url'    => $loc,
		'nonce'  => $nonce,
		'form'   => $form_id,
	);
}

$falhas = 0;

echo str_repeat( '=', 66 ) . "\n";
echo "Teste de envio de formulários — {$base}\n";
echo str_repeat( '=', 66 ) . "\n\n";

/**
 * Verifica uma resposta.
 *
 * @param string $rotulo Rótulo.
 * @param bool   $ok     Passou?
 * @param string $detalhe Detalhe.
 */
function checar( $rotulo, $ok, $detalhe = '' ) {
	global $falhas;

	if ( $ok ) {
		printf( "  [ok]    %-46s %s\n", $rotulo, $detalhe );
	} else {
		printf( "  [FALHA] %-46s %s\n", $rotulo, $detalhe );
		$falhas++;
	}
}

/* 1. Envio válido do formulário de contato. */
$r = enviar(
	$base . '/fale-conosco/',
	array(
		'nome'     => 'Teste Automatizado',
		'email'    => 'teste@exemplo.com.br',
		'assunto'  => 'Mensagem de teste',
		'mensagem' => 'Enviado pelo verificador de formulários.',
	)
);

echo "Contato — envio válido\n";
checar( 'respondeu 302 (redirecionamento)', 302 === $r['status'], 'HTTP ' . $r['status'] );
checar( 'voltou para a página do formulário', false !== strpos( $r['url'], 'fale-conosco' ), $r['url'] );
checar( 'indicou sucesso (lar_enviado=1)', false !== strpos( $r['url'], 'lar_enviado=1' ), '' );

/* 2. Envio sem e-mail deve falhar com mensagem de erro. */
$r = enviar(
	$base . '/fale-conosco/',
	array(
		'nome'     => 'Sem Email',
		'email'    => 'nao-e-email',
		'mensagem' => 'x',
	)
);

echo "\nContato — envio inválido (e-mail malformado)\n";
checar( 'respondeu 302', 302 === $r['status'], 'HTTP ' . $r['status'] );
checar( 'indicou falha (lar_enviado=0)', false !== strpos( $r['url'], 'lar_enviado=0' ), '' );
checar( 'trouxe lar_erro', false !== strpos( $r['url'], 'lar_erro' ), '' );

/* 3. Pré-inscrição exige as crianças. */
$r = enviar(
	$base . '/pre-inscricao/',
	array(
		'responsavel' => 'Responsavel Teste',
		'email'       => 'responsavel@exemplo.com.br',
	)
);

echo "\nPré-inscrição — envio sem crianças\n";
checar( 'respondeu 302', 302 === $r['status'], 'HTTP ' . $r['status'] );
checar( 'indicou falha (lar_enviado=0)', false !== strpos( $r['url'], 'lar_enviado=0' ), '' );

/* 4. Currículo válido. */
$r = enviar(
	$base . '/trabalhe-conosco/',
	array(
		'nome'     => 'Candidata Teste',
		'email'    => 'candidata@exemplo.com.br',
		'telefone' => '(19) 99999-0000',
		'area'     => 'Educação e Pedagogia',
		'mensagem' => 'Currículo de teste.',
	)
);

echo "\nTrabalhe — envio válido\n";
checar( 'respondeu 302', 302 === $r['status'], 'HTTP ' . $r['status'] );
checar( 'indicou sucesso (lar_enviado=1)', false !== strpos( $r['url'], 'lar_enviado=1' ), '' );

/* 5. Nonce inválido deve ser rejeitado (segurança). */
$html   = html_de( $base . '/fale-conosco/' );
$destino = preg_match( '~<form[^>]+action="([^"]+)"~', $html, $m )
	? html_entity_decode( $m[1], ENT_QUOTES, 'UTF-8' )
	: $base . '/wp-admin/admin-post.php';

$ch = curl_init( $destino );
curl_setopt_array(
	$ch,
	array(
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_POST           => true,
		CURLOPT_POSTFIELDS     => http_build_query(
			array(
				'action'      => 'lar_form',
				'lar_form_id' => 'contato',
				'lar_form_nonce' => 'nonce-invalido',
				'nome'        => 'Hack',
				'email'       => 'hack@exemplo.com',
				'mensagem'    => 'x',
			)
		),
		CURLOPT_FOLLOWLOCATION => false,
		CURLOPT_TIMEOUT        => 30,
	)
);

curl_exec( $ch );
$status = (int) curl_getinfo( $ch, CURLINFO_RESPONSE_CODE );
$loc    = (string) curl_getinfo( $ch, CURLINFO_REDIRECT_URL );
curl_close( $ch );

echo "\nSegurança — nonce inválido\n";
checar( 'não aceitou o envio (não redireciona com sucesso)', false === strpos( $loc, 'lar_enviado=1' ), 'HTTP ' . $status );

echo "\n" . str_repeat( '-', 66 ) . "\n";
printf( "Falhas: %d\n", $falhas );

exit( $falhas ? 1 : 0 );