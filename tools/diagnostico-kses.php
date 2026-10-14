<?php
/**
 * Diagnóstico: mostra o que foi removido do post_content na hora de salvar.
 *
 * Uso: php tools/diagnostico-kses.php
 *
 * @package LarAndreLuiz
 */

if ( 'cli' !== PHP_SAPI ) {
	exit( 'Execute somente via CLI.' );
}

define( 'WP_USE_THEMES', false );
require_once dirname( __DIR__ ) . '/wp-load.php';

$paginas = get_posts(
	array(
		'post_type'      => 'page',
		'posts_per_page' => -1,
	)
);

/**
 * Tags que o design system usa e o kses bloqueia para quem não tem
 * "unfiltered_html".
 */
$alvo = array(
	'svg'         => '~<svg~i',
	'path'        => '~<path~i',
	'circle'      => '~<circle~i',
	'iframe'      => '~<iframe~i',
	'form'        => '~<form~i',
	'video'       => '~<video~i',
	'style attr'  => '~style="~i',
	'data-lar-*'  => '~data-lar-~i',
);

echo str_repeat( '=', 72 ) . "\n";
echo "Tags de layout preservadas no banco\n";
echo str_repeat( '=', 72 ) . "\n\n";

printf( "%-22s %s\n", 'PAGINA', implode( ' ', array_keys( $alvo ) ) );

foreach ( $paginas as $p ) {
	$linha = array();

	foreach ( $alvo as $rotulo => $padrao ) {
		$linha[] = sprintf( '%3d', preg_match_all( $padrao, $p->post_content ) );
	}

	printf( "%-22s %s\n", '/' . $p->post_name, implode( '  ', $linha ) );
}

echo "\n";
echo 'current_user_can( unfiltered_html ): ' . ( current_user_can( 'unfiltered_html' ) ? 'sim' : 'NAO' ) . "\n";
echo 'usuario atual: ' . ( get_current_user_id() ? get_current_user_id() . ' (admin)' : 'ninguem (WP-CLI)') . "\n";

echo "\n";
echo "Tags permitidas pelo kses em 'post' que o design system usa:\n";

$permitidas = wp_kses_allowed_html( 'post' );

foreach ( array( 'svg', 'path', 'circle', 'iframe', 'form', 'video', 'input' ) as $tag ) {
	printf( "  %-8s %s\n", $tag, isset( $permitidas[ $tag ] ) ? 'permitida' : 'BLOQUEADA' );
}