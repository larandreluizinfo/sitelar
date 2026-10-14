<?php
/**
 * Compara o post_content guardado no banco com o HTML renderizado.
 *
 * Serve para detectar filtros do WordPress (principalmente wpautop) que
 * inserem marcação no meio do layout: </p> depois de <img>, <br />,
 * <p> em volta de <div>/<section>, aspas curvas, etc.
 *
 * Uso: php tools/comparar-conteudo.php
 *
 * @package LarAndreLuiz
 */

if ( 'cli' !== PHP_SAPI ) {
	exit( 'Execute somente via CLI.' );
}

define( 'WP_USE_THEMES', false );
require_once dirname( __DIR__ ) . '/wp-load.php';

/**
 * Padrões de dano estrutural.
 */
$danos = array(
	'</p> depois de <img>'  => '~<img[^>]*>\s*</p>~i',
	'</p> depois de <input>' => '~<input[^>]*>\s*</p>~i',
	'<br /> inserido'       => '~<br\s*/?>~i',
	'<p> envolvendo <div>'  => '~<p[^>]*>\s*<div~i',
	'<p> envolvendo <sec>'  => '~<p[^>]*>\s*<section~i',
	'<div> envolvendo <p>'  => '~<div[^>]*>\s*<p~i',
	'&nbsp;'                => '~&nbsp;~',
);

echo str_repeat( '=', 72 ) . "\n";
echo "Integridade do conteúdo das páginas\n";
echo str_repeat( '=', 72 ) . "\n\n";

$paginas = get_posts(
	array(
		'post_type'      => 'page',
		'posts_per_page' => -1,
		'orderby'        => 'menu_order',
		'order'          => 'ASC',
	)
);

$total_problemas = 0;

foreach ( $paginas as $pagina ) {
	$slug  = $pagina->post_name;
	$salvo = $pagina->post_content;

	if ( '' === trim( wp_strip_all_tags( $salvo ) ) && false === strpos( $salvo, '[' ) ) {
		continue;
	}

	/*
	 * Reproduz o contexto do frontend: monta a query da página e dispara
	 * do_action('wp'), que é onde o tema desliga o wpautop. Sem isso o
	 * comparador acusaria danos que não acontecem no navegador.
	 */
	$GLOBALS['wp_query'] = new WP_Query(
		array(
			'p'         => $pagina->ID,
			'post_type' => 'page',
		)
	);
	$GLOBALS['post'] = isset( $GLOBALS['wp_query']->post ) ? $GLOBALS['wp_query']->post : $pagina;

	setup_postdata( $GLOBALS['post'] );

	do_action( 'wp' );

	$renderizado = apply_filters( 'the_content', $salvo );

	wp_reset_postdata();

	$achados = array();

	foreach ( $danos as $rotulo => $padrao ) {
		$antes  = preg_match_all( $padrao, $salvo );
		$depois = preg_match_all( $padrao, $renderizado );
		$delta  = $depois - $antes;

		if ( $delta > 0 ) {
			$achados[] = sprintf( '%s (+%d)', $rotulo, $delta );
		}
	}

	if ( $achados ) {
		printf( "  [DANO] /%s/\n", $slug );
		foreach ( $achados as $a ) {
			printf( "           %s\n", $a );
		}
		$total_problemas += count( $achados );
	} else {
		printf( "  [ok]   /%s/\n", $slug );
	}
}

echo "\n" . str_repeat( '-', 72 ) . "\n";
printf( "Páginas com dano estrutural: %d\n", $total_problemas );