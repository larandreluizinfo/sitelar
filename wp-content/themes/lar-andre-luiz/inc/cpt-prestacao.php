<?php
/**
 * Post type "Prestação de Contas".
 *
 * Antes os valores eram gerados com Math.random() a cada visita, o que
 * publicava números diferentes a cada carregamento e apontava para PDFs
 * inexistentes. Agora cada balancete é um registro real do admin, com
 * arquivo anexado pelo próprio WordPress.
 *
 * @package LarAndreLuiz
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registra o post type.
 */
add_action(
	'init',
	function () {
		register_post_type(
			'prestacao',
			array(
				'labels'              => array(
					'name'               => __( 'Prestação de contas', 'lar-andre-luiz' ),
					'singular_name'      => __( 'Balancete', 'lar-andre-luiz' ),
					'add_new'            => __( 'Adicionar balancete', 'lar-andre-luiz' ),
					'add_new_item'       => __( 'Adicionar novo balancete', 'lar-andre-luiz' ),
					'edit_item'          => __( 'Editar balancete', 'lar-andre-luiz' ),
					'new_item'           => __( 'Novo balancete', 'lar-andre-luiz' ),
					'view_item'          => __( 'Ver balancete', 'lar-andre-luiz' ),
					'search_items'       => __( 'Buscar balancetes', 'lar-andre-luiz' ),
					'not_found'          => __( 'Nenhum balancete encontrado.', 'lar-andre-luiz' ),
					'not_found_in_trash' => __( 'Nenhum balancete na lixeira.', 'lar-andre-luiz' ),
					'menu_name'          => __( 'Prestação de contas', 'lar-andre-luiz' ),
				),
				'public'              => true,
				'has_archive'         => false,
				'hierarchical'        => false,
				'menu_icon'           => 'dashicons-media-spreadsheet',
				'menu_position'       => 25,
				'show_in_rest'        => true,
				'supports'            => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions' ),
				'show_in_menu'        => true,
			)
		);

		register_post_meta(
			'prestacao',
			'lar_categoria',
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'sanitize_text_field',
				'auth_callback'     => function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);

		register_post_meta(
			'prestacao',
			'lar_ano',
			array(
				'type'              => 'integer',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'absint',
				'auth_callback'     => function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);

		register_post_meta(
			'prestacao',
			'lar_entradas',
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'sanitize_text_field',
				'auth_callback'     => function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);

		register_post_meta(
			'prestacao',
			'lar_saidas',
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'sanitize_text_field',
				'auth_callback'     => function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);

		register_post_meta(
			'prestacao',
			'lar_entradas_itens',
			array(
				'type'              => 'string',
				'single'            => true,
				'sanitize_callback' => 'sanitize_textarea_field',
				'show_in_rest'      => true,
				'auth_callback'     => function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);

		register_post_meta(
			'prestacao',
			'lar_saidas_itens',
			array(
				'type'              => 'string',
				'single'            => true,
				'sanitize_callback' => 'sanitize_textarea_field',
				'show_in_rest'      => true,
				'auth_callback'     => function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);
	}
);

/**
 * Categorias disponíveis na prestação de contas.
 *
 * @return array<string,string>
 */
function lar_transparencia_categorias() {
	return array(
		'institucional' => __( 'Institucional', 'lar-andre-luiz' ),
		'cmdca'          => 'CMDCA',
		'smdhcj'         => 'SMDHCJ',
		'emendas'        => __( 'Emendas Parlamentares', 'lar-andre-luiz' ),
	);
}

/**
 * Anos disponíveis (usa os anos realmente cadastrados).
 *
 * @return int[]
 */
function lar_transparencia_anos() {
	global $wpdb;

	$rows = $wpdb->get_col(
		"SELECT DISTINCT pm.meta_value
		 FROM {$wpdb->postmeta} pm
		 INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
		 WHERE pm.meta_key = 'lar_ano'
		   AND p.post_type = 'prestacao'
		   AND p.post_status = 'publish'
		 ORDER BY pm.meta_value DESC"
	);

	return array_map( 'absint', $rows );
}

/**
 * Busca os balancetes de uma categoria/ano.
 *
 * @param string $categoria Slug da categoria.
 * @param int    $ano       Ano.
 * @return WP_Post[]
 */
function lar_transparencia_balancetes( $categoria, $ano ) {
	$query = new WP_Query(
		array(
			'post_type'      => 'prestacao',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'meta_key'       => 'lar_ano', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'orderby'        => 'meta_value_num',
			'order'          => 'DESC',
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				'relation' => 'AND',
				array(
					'key'   => 'lar_ano',
					'value' => (int) $ano,
				),
				array(
					'key'   => 'lar_categoria',
					'value' => $categoria,
				),
			),
		)
	);

	return $query->posts;
}

/**
 * Formata um valor em reais a partir de "12345.67" ou "12.345,67".
 *
 * @param string $value Valor.
 * @return string
 */
function lar_moeda( $value ) {
	$value = preg_replace( '/[^0-9,.\-]/', '', (string) $value );

	if ( '' === $value ) {
		return '';
	}

	if ( false !== strpos( $value, ',' ) ) {
		$value = str_replace( '.', '', $value );
		$value = str_replace( ',', '.', $value );
	}

	return (float) $value;
}

/**
 * Converte "Nome: 1.234,56" em par label/valor.
 *
 * @param string $linha Linha.
 * @return array{0:string,1:string}|null
 */
function lar_parse_linha_dinheiro( $linha ) {
	$linha = trim( (string) $linha );

	if ( '' === $linha ) {
		return null;
	}

	$pos = strpos( $linha, ':' );

	if ( false === $pos ) {
		return array( $linha, '' );
	}

	return array( trim( substr( $linha, 0, $pos ) ), trim( substr( $linha, $pos + 1 ) ) );
}