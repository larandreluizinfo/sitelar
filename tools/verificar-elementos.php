<?php
/**
 * Confere se os elementos interativos do tema existem no HTML renderizado.
 *
 * Cada item é algo que o JS do tema procura; se faltar, a funcionalidade
 * silenciosamente não funciona no navegador.
 *
 * Uso: php tools/verificar-elementos.php
 *
 * @package LarAndreLuiz
 */

if ( 'cli' !== PHP_SAPI ) {
	exit( 'Execute somente via CLI.' );
}

$base = getenv( 'LAR_URL' ) ?: 'http://sitelar.test';

/**
 * Baixa uma URL.
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
 * Verificações: página => lista de [rótulo, ...padrões].
 */
$verificacoes = array(
	'/' => array(
		'logo do site'          => '/<a class="logo"[^>]+href="http:\/\/sitelar\.test\/"/',
		'botão burger'          => '/data-lar-burger/',
		'CTA de doação'          => '/class="cta-doe/',
		'galeria do mosaico'    => '/class="mosaic"/',
		'carrossel de tecnologia' => '/class="tech-track"/',
		'modal de doação'        => '/id="donation"/',
		'modal da galeria'      => '/id="imgModal"/',
		'rodapé'                => '/<footer>/',
		'stylesheet do tema'     => '/wp-content\/themes\/lar-andre-luiz\/assets\/css\/style\.css/',
		'main.js'               => '/wp-content\/themes\/lar-andre-luiz\/assets\/js\/main\.js/',
		'video da capa'         => '/<video[^>]+hero_bg\.mp4/',
	),

	'/quem-somos/' => array(
		'carrossel de parceiros' => '/class="logo-track"/',
		'15 logos'               => '/class="logo-item"/',
		'diretoria'              => '/class="dir-pyramid"/',
	),

	'/atividades/' => array(
		'container das abas'  => '/class="tabs" id="sportTabs"/',
		'6 abas de esporte'   => '/data-lar-sport="/',
		'painel futebol'      => '/id="esporte-futebol"/',
		'painel handebol'     => '/id="esporte-handebol"/',
		'painel vôlei'        => '/id="esporte-volei"/',
		'painel beach tênis'  => '/id="esporte-beachtenis"/',
		'painel tênis'       => '/id="esporte-tenis"/',
		'painel atletismo'    => '/id="esporte-atletismo"/',
		'galeria de registros' => '/class="gallery-mosaic"/',
		'carrossel de vídeos' => '/class="video-track"/',
	),

'/transparencia/' => array(
		'bloco de transparência' => '/class="lar-transparencia"/',
		'aviso de dados vazios'  => '/class="lar-empty"/',
	),

'/fale-conosco/' => array(
		'formulário'        => '/<form[^>]+class="modern-form"[^>]*>/',
		'form_id = contato' => '/name="lar_form_id" value="contato"/',
		'campo nonce'       => '/lar_form_nonce/',
		'endpoint do envio' => '/admin-post\.php/',
	),

	'/trabalhe-conosco/' => array(
		'formulário'         => '/<form[^>]+class="modern-form"[^>]*>/',
		'form_id = curriculo' => '/name="lar_form_id" value="curriculo"/',
		'campo de upload'    => '/<input type="file" name="lar_arquivo"/',
		'endpoint do envio'  => '/admin-post\.php/',
	),

	'/pre-inscricao/' => array(
		'formulário'         => '/<form[^>]+class="modern-form"[^>]*>/',
		'form_id = inscricao' => '/name="lar_form_id" value="inscricao"/',
		'botão add child'    => '/data-lar-add-child/',
		'endpoint do envio'  => '/admin-post\.php/',
	),

	'/galeria/' => array(
		'mosaico da galeria' => '/class="gallery-mosaic"/',
	),

	'/noticias/' => array(
		'destaque'   => '/class="news-hero"/',
		'grid'       => '/class="news-grid"/',
		'12 cartões' => '/class="news-card"/',
	),

	'/quadra-dos-sonhos/' => array(
		'modalidades' => '/class="card activity"/',
	),

	'/instituto-aces/' => array(
		'modalidades de raquete' => '/class="card activity"/',
	),
);

$falhas = 0;
$total  = 0;

echo str_repeat( '=', 66 ) . "\n";
echo "Verificação de elementos — {$base}\n";
echo str_repeat( '=', 66 ) . "\n\n";

foreach ( $verificacoes as $rota => $itens ) {
	$html = html_de( $base . $rota );

	echo "{$rota}\n";

	foreach ( $itens as $rotulo => $padrao ) {
		$total++;

		// Padrões "vazios" (^\s*$) são checagens negativas via placeholder.
		if ( '/^\s*$/s' === $padrao ) {
			continue;
		}

		if ( preg_match( $padrao, $html, $m ) ) {
			printf( "  [ok]   %-28s %d ocorrência(s)\n", $rotulo, substr_count( $html, $m[0] ) );
		} else {
			printf( "  [FALHA] %s\n", $rotulo );
			$falhas++;
		}
	}

	echo "\n";
}

// Checagens negativas específicas.
echo "Checagens negativas\n\n";

$negativas = array(
	array( '/', '/Math\.random/', 'nenhum Math.random (dados fictícios)' ),
	array( '/transparencia/', '/transTabs/', 'nenhum container JS de transparência' ),
	array( '/transparencia/', '/assets\/prestacao-contas/', 'nenhum link de PDF inexistente' ),
	array( '/pre-inscricao/', '/onsubmit=/', 'nenhum onsubmit inline' ),
	array( '/fale-conosco/', '/onsubmit=/', 'nenhum onsubmit inline' ),
	array( '/trabalhe-conosco/', '/onsubmit=/', 'nenhum onsubmit inline' ),
	array( '/', '/class="page active"/', 'nenhuma seção com "page active" de SPA' ),
	array( '/', '/\.page\{display:none\}/', 'nenhuma regra .page{display:none}' ),
);

foreach ( $negativas as $neg ) {
	list( $rota, $padrao, $rotulo ) = $neg;
	$total++;
	$html = html_de( $base . $rota );

	if ( preg_match( $padrao, $html ) ) {
		printf( "  [FALHA] %s\n", $rotulo );
		$falhas++;
	} else {
		printf( "  [ok]    %s\n", $rotulo );
	}
}

echo "\n" . str_repeat( '-', 66 ) . "\n";
printf( "Checagens: %d | Falhas: %d\n", $total, $falhas );

exit( $falhas ? 1 : 0 );
