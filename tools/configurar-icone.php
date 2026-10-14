<?php
/**
 * Importa o favicon do tema como ícone do site do WordPress.
 *
 * Sem isso o navegador pediria /favicon.ico e receberia 404, porque o
 * arquivo original foi movido para dentro da pasta do tema.
 *
 * Uso: php tools/configurar-icone.php
 *
 * @package LarAndreLuiz
 */

if ( 'cli' !== PHP_SAPI ) {
	exit( 'Execute somente via CLI.' );
}

define( 'WP_USE_THEMES', false );
require_once dirname( __DIR__ ) . '/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

$origem = get_template_directory() . '/favicon.png';

if ( ! file_exists( $origem ) ) {
	exit( "favicon.png não encontrado em {$origem}\n" );
}

echo "Importando favicon do tema como ícone do site...\n";

// Remove um ícone anterior para não duplicar a cada execução.
$anterior = (int) get_option( 'site_icon' );

if ( $anterior && (int) get_option( 'site_icon', 0 ) !== 0 ) {
	$arquivo_anterior = get_attached_file( $anterior );

	if ( $arquivo_anterior && strpos( $arquivo_anterior, 'lar-icone-site' ) !== false ) {
		wp_delete_attachment( $anterior, true );
	}

	update_option( 'site_icon', 0 );
}

// Copia para a biblioteca de mídia.
$destino_dir = wp_upload_dir();
$arquivo     = $destino_dir['path'] . '/lar-icone-site.png';

copy( $origem, $arquivo );

$anexo_id = wp_insert_attachment(
	array(
		'guid'           => wp_upload_dir()['url'] . '/lar-icone-site.png',
		'post_mime_type' => 'image/png',
		'post_title'     => 'Ícone do site',
		'post_content'   => '',
		'post_status'    => 'inherit',
	),
	$arquivo
);

if ( is_wp_error( $anexo_id ) ) {
	exit( 'Erro ao criar o anexo: ' . $anexo_id->get_error_message() . "\n" );
}

$metadados = wp_generate_attachment_metadata( $anexo_id, $arquivo );

if ( $metadados ) {
	wp_update_attachment_metadata( $anexo_id, $metadados );
}

update_option( 'site_icon', $anexo_id );

echo "  anexo criado: {$anexo_id}\n";
echo '  site_icon definido: ' . get_option( 'site_icon' ) . "\n";
echo '  URL: ' . wp_get_attachment_image_url( $anexo_id, 'full' ) . "\n";