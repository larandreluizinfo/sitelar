<?php
/**
 * Verificador de links e recursos do site.
 *
 * Percorre as páginas renderizadas e reporta:
 *  - links/recursos internos que não respondem 200
 *  - resíduos de navegação por hash (showPage, href="#", onclick)
 *  - avisos de PHP na saída das páginas
 *
 * Uso: php tools/verificar-links.php
 *
 * @package LarAndreLuiz
 */

if ( 'cli' !== PHP_SAPI ) {
	exit( 'Execute somente via CLI.' );
}

$base = getenv( 'LAR_URL' ) ?: 'http://sitelar.test';

$paginas = array(
	'/',
	'/quem-somos/',
	'/atividades/',
	'/esportes-de-base/',
	'/galeria/',
	'/noticias/',
	'/eventos/',
	'/transparencia/',
	'/contato/',
	'/fale-conosco/',
	'/trabalhe-conosco/',
	'/pre-inscricao/',
	'/quadra-dos-sonhos/',
	'/instituto-aces/',
);

/**
 * Padrões que não podem mais existir no HTML gerado.
 */
$proibidos = array(
	'showPage'                => 'navegação por hash (showPage)',
	'href="#"'                => 'link vazio',
	'onclick='                => 'manipulador inline',
	'location.hash'           => 'roteamento por hash',
	'replaceState'            => 'manipulação de histórico',
	'goSport('                => 'navegação por hash (goSport)',
	'sendForm('               => 'submit via JS sem backend',
	'assets/prestacao-contas' => 'PDFs inexistentes',
);

$avisos_php = array( 'Warning:', 'Notice:', 'Deprecated:', 'Fatal error', 'Parse error', 'There has been a critical error' );

/**
 * Baixa uma URL e devolve o código de status.
 *
 * @param string $url URL.
 * @return array{status:int,body:string,erro:string}
 */
function buscar( $url ) {
	$ch = curl_init( $url );

	curl_setopt_array(
		$ch,
		array(
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_MAXREDIRS      => 5,
			CURLOPT_TIMEOUT        => 30,
			CURLOPT_CONNECTTIMEOUT => 10,
		)
	);

	$body   = curl_exec( $ch );
	$status = (int) curl_getinfo( $ch, CURLINFO_RESPONSE_CODE );
	$erro   = curl_error( $ch );

	curl_close( $ch );

	return array(
		'status' => $status,
		'body'   => (string) $body,
		'erro'   => $erro,
	);
}

echo str_repeat( '=', 66 ) . "\n";
echo "Verificador de links — {$base}\n";
echo str_repeat( '=', 66 ) . "\n\n";

$erros      = array();
$links      = array();
$paginas_ok = 0;

foreach ( $paginas as $rota ) {
	$r = buscar( $base . $rota );

	if ( 200 !== $r['status'] ) {
		$erros[] = sprintf( '%-24s respondeu HTTP %d %s', $rota, $r['status'], $r['erro'] );
		continue;
	}

	$paginas_ok++;
	$html = $r['body'];

	printf( "  %-24s 200  %6d bytes\n", $rota, strlen( $html ) );

	foreach ( $proibidos as $chave => $descricao ) {
		$n = substr_count( $html, $chave );
		if ( $n > 0 ) {
			$erros[] = sprintf( '%s ainda contém %d x "%s" (%s)', $rota, $n, $chave, $descricao );
		}
	}

	foreach ( $avisos_php as $needle ) {
		if ( false !== strpos( $html, $needle ) ) {
			$erros[] = sprintf( '%s contém "%s" na saída do PHP', $rota, $needle );
		}
	}

	// Links e recursos.
	preg_match_all( '~(?:href|src|poster)="([^"]+)"~', $html, $m );

	foreach ( $m[1] as $u ) {
		// Decodifica entidades HTML antes de testar o esquema.
		$u = html_entity_decode( $u, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		$u = trim( $u );

		// Delimitador "~": o padrão contém "#", que não pode ser delimitador.
		// O ":" fica FORA do grupo: dentro dele a alternância exigiria "mailto::".
		if ( preg_match( '~^(mailto|tel|sms|data|javascript):~i', $u ) ) {
			continue;
		}

		if ( '' === $u || '#' === $u ) {
			continue;
		}

		if ( preg_match( '~^(https?:)?//~i', $u ) ) {
			if ( 0 === strpos( $u, $base ) ) {
				$u = substr( $u, strlen( $base ) );
			} else {
				continue; // recurso externo
			}
		}

		if ( '' === $u ) {
			$u = '/';
		}

		// Normaliza \ para / (barras invertidas em URLs).
		$u = str_replace( '\\', '/', $u );

		$u = explode( '?', $u )[0];
		$u = explode( '#', $u )[0];

		if ( '' === $u ) {
			continue;
		}

		// Endpoints de API exigem query string própria e não são links de página.
		if ( preg_match( '~^/wp-(json|admin)/~', $u ) ) {
			continue;
		}

		$links[ $u ] = true;
	}
}

echo "\n";
printf( "Páginas testadas.......: %d de %d\n", $paginas_ok, count( $paginas ) );
printf( "Links/recursos internos.: %d\n\n", count( $links ) );

// Verifica cada link.
$quebrados = array();
$verificados = 0;

foreach ( array_keys( $links ) as $u ) {
	$r    = buscar( $base . $u );
	$verificados++;

	if ( $r['status'] < 400 ) {
		continue;
	}

	$quebrados[] = sprintf( '%-60s HTTP %d', $u, $r['status'] );
}

printf( "Links verificados.......: %d\n\n", $verificados );

if ( $quebrados ) {
	echo 'LINKS QUEBRADOS (' . count( $quebrados ) . "):\n";
	foreach ( $quebrados as $q ) {
		echo "  - $q\n";
	}
} else {
	echo "LINKS QUEBRADOS: nenhum\n";
}

echo "\n";

if ( $erros ) {
	echo 'PROBLEMAS (' . count( $erros ) . "):\n";
	foreach ( $erros as $e ) {
		echo "  - $e\n";
	}
} else {
	echo "PROBLEMAS: nenhum (sem hash, sem onclick, sem aviso de PHP)\n";
}

echo "\n";

exit( ( $quebrados || $erros ) ? 1 : 0 );