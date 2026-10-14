<?php
/**
 * Metabox de capa (hero) das páginas.
 *
 * Os campos alimentam lar_render_hero(). Tudo é salvo como post meta,
 * então o cliente edita a capa da página pelo admin do WordPress.
 *
 * @package LarAndreLuiz
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Campos de capa de uma página.
 *
 * @return array<string,string> meta_key => rótulo
 */
function lar_hero_fields() {
	return array(
		'lar_hero_title'    => __( 'Título do hero (deixe vazio para usar o título da página)', 'lar-andre-luiz' ),
		'lar_hero_eyebrow'  => __( 'Selo acima do título (ex.: "Desde 1959")', 'lar-andre-luiz' ),
		'lar_hero_subtitle' => __( 'Texto abaixo do título', 'lar-andre-luiz' ),
		'lar_hero_extra'    => __( 'Conteúdo extra abaixo do subtítulo (botões, estatísticas, etc.)', 'lar-andre-luiz' ),
		'lar_hero_video'    => __( 'Vídeo de fundo (URL, ex.: assets/videos/hero_bg.mp4)', 'lar-andre-luiz' ),
		'lar_hero_poster'   => __( 'Imagem de fundo/poster (URL ou use a imagem destacada)', 'lar-andre-luiz' ),
	);
}

/**
 * Lê os metadados da capa.
 *
 * @param int $post_id ID da página.
 * @return array
 */
function lar_get_hero( $post_id ) {
	$hero = array();

	foreach ( array_keys( lar_hero_fields() ) as $key ) {
		$hero[ str_replace( 'lar_hero_', '', $key ) ] = (string) get_post_meta( $post_id, $key, true );
	}

	if ( '' === $hero['poster'] ) {
		$thumb_id = get_post_thumbnail_id( $post_id );
		if ( $thumb_id ) {
			$hero['poster'] = (string) wp_get_attachment_image_url( $thumb_id, 'full' );
		}
	}

	return $hero;
}

/**
 * Registra a metabox.
 */
add_action(
	'add_meta_boxes',
	function () {
		foreach ( get_post_types( array( 'public' => true ) ) as $type ) {
			add_meta_box(
				'lar-hero',
				__( 'Capa da página (hero)', 'lar-andre-luiz' ),
				'lar_render_hero_metabox',
				$type,
				'normal',
				'high'
			);
		}
	}
);

/**
 * HTML da metabox.
 *
 * @param WP_Post $post Post atual.
 */
function lar_render_hero_metabox( $post ) {
	wp_nonce_field( 'lar_save_hero', 'lar_hero_nonce' );

	$hero  = lar_get_hero( $post->ID );
	$items = array(
		'title'    => array( 'label' => __( 'Título do hero', 'lar-andre-luiz' ), 'type' => 'text' ),
		'eyebrow'  => array( 'label' => __( 'Selo', 'lar-andre-luiz' ), 'type' => 'text' ),
		'subtitle' => array( 'label' => __( 'Subtítulo', 'lar-andre-luiz' ), 'type' => 'textarea' ),
		'extra'    => array( 'label' => __( 'Conteúdo extra (botões, estatísticas, etc.)', 'lar-andre-luiz' ), 'type' => 'textarea' ),
		'video'    => array( 'label' => __( 'Vídeo de fundo (URL ou caminho em assets/)', 'lar-andre-luiz' ), 'type' => 'text' ),
		'poster'   => array( 'label' => __( 'Imagem de fundo (URL, caminho em assets/ ou imagem destacada)', 'lar-andre-luiz' ), 'type' => 'text' ),
	);

	echo '<div class="lar-hero-fields">';

	foreach ( $items as $key => $field ) {
		$value = isset( $hero[ $key ] ) ? $hero[ $key ] : '';

		echo '<label for="lar-hero-' . esc_attr( $key ) . '">' . esc_html( $field['label'] ) . '</label>';

		if ( 'textarea' === $field['type'] ) {
			printf(
				'<textarea id="lar-hero-%1$s" name="lar_hero_%1$s" rows="3">%2$s</textarea>',
				esc_attr( $key ),
				esc_textarea( $value )
			);
		} else {
			printf(
				'<input type="text" id="lar-hero-%1$s" name="lar_hero_%1$s" value="%2$s">',
				esc_attr( $key ),
				esc_attr( $value )
			);
		}
	}

	echo '<p class="description">' . esc_html__( 'Caminhos relativos (assets/...) são resolvidos automaticamente para a pasta do tema.', 'lar-andre-luiz' ) . '</p>';
	echo '</div>';
}

/**
 * Salva os metadados da capa.
 *
 * @param int $post_id ID do post.
 */
add_action(
	'save_post',
	function ( $post_id ) {
		if ( ! isset( $_POST['lar_hero_nonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['lar_hero_nonce'] ) ), 'lar_save_hero' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$campos_sanitizers = array(
			'lar_hero_title'    => 'wp_kses_post',
			'lar_hero_eyebrow'  => 'sanitize_text_field',
			'lar_hero_subtitle' => 'wp_kses_post',
			'lar_hero_extra'    => 'wp_kses_post',
			'lar_hero_video'    => 'esc_url_raw',
			'lar_hero_poster'   => 'esc_url_raw',
		);

		foreach ( array_keys( lar_hero_fields() ) as $key ) {
			if ( ! isset( $_POST[ $key ] ) ) {
				continue;
			}

			$raw = wp_unslash( $_POST[ $key ] );

			if ( ! is_string( $raw ) ) {
				continue;
			}

			// URLs relativas em assets/ são resolvidas na renderização.
			$value = call_user_func( $campos_sanitizers[ $key ], $raw );

			if ( '' === $value ) {
				delete_post_meta( $post_id, $key );
			} else {
				update_post_meta( $post_id, $key, $value );
			}
		}
	}
);

/* -------------------------------------------------------------------------
 * Resolver "assets/..." dentro dos metadados da capa
 * ---------------------------------------------------------------------- */

/**
 * Converte caminhos relativos de asset em URL absoluta.
 *
 * @param string $value Valor do campo.
 * @return string
 */
function lar_asset_meta_url( $value ) {
	$value = trim( (string) $value );

	if ( '' === $value ) {
		return '';
	}

	if ( preg_match( '#^(https?:)?//#i', $value ) || 0 === strpos( $value, '/' ) ) {
		return $value;
	}

	if ( 0 === strpos( $value, 'assets/' ) ) {
		return get_template_directory_uri() . '/' . $value;
	}

	return $value;
}