<?php
/**
 * Campos do balancete (post type "prestacao").
 *
 * @package LarAndreLuiz
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registra os post meta restantes.
 */
add_action(
	'init',
	function () {
		foreach ( array( 'lar_mes', 'lar_arquivo' ) as $key ) {
			register_post_meta(
				'prestacao',
				$key,
				array(
					'type'              => $key === 'lar_arquivo' ? 'integer' : 'string',
					'single'            => true,
					'show_in_rest'      => true,
					'sanitize_callback' => 'lar_arquivo' === $key ? 'absint' : 'sanitize_text_field',
					'auth_callback'     => function () {
						return current_user_can( 'edit_posts' );
					},
				)
			);
		}
	}
);

/**
 * Adiciona a metabox de valores.
 */
add_action(
	'add_meta_boxes',
	function () {
		add_meta_box(
			'lar-balancete',
			__( 'Valores do balancete', 'lar-andre-luiz' ),
			'lar_render_balancete_metabox',
			'prestacao',
			'normal',
			'high'
		);
	}
);

/**
 * HTML da metabox do balancete.
 *
 * @param WP_Post $post Balancete.
 */
function lar_render_balancete_metabox( $post ) {
	wp_nonce_field( 'lar_save_balancete', 'lar_balancete_nonce' );

	$cats = lar_transparencia_categorias();
	?>
	<style>
		.lar-balancete-fields label{display:block;margin:14px 0 4px;font-weight:600}
		.lar-balancete-fields input,.lar-balancete-fields textarea{width:100%;max-width:640px}
		.lar-balancete-fields .description{margin-top:6px}
	</style>

	<div class="lar-balancete-fields">
		<label for="lar-mes"><?php esc_html_e( 'Mês/ano exibido no card (ex.: "Janeiro de 2025")', 'lar-andre-luiz' ); ?></label>
		<input type="text" id="lar-mes" name="lar_mes" value="<?php echo esc_attr( get_post_meta( $post->ID, 'lar_mes', true ) ); ?>">

		<label for="lar-categoria"><?php esc_html_e( 'Categoria', 'lar-andre-luiz' ); ?></label>
		<select id="lar-categoria" name="lar_categoria">
			<?php foreach ( $cats as $slug => $label ) : ?>
				<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( get_post_meta( $post->ID, 'lar_categoria', true ), $slug ); ?>>
					<?php echo esc_html( $label ); ?>
				</option>
			<?php endforeach; ?>
		</select>

		<label for="lar-ano"><?php esc_html_e( 'Ano', 'lar-andre-luiz' ); ?></label>
		<input type="number" id="lar-ano" name="lar_ano" min="2000" max="2100" value="<?php echo esc_attr( get_post_meta( $post->ID, 'lar_ano', true ) ); ?>">

		<label for="lar-entradas"><?php esc_html_e( 'Entradas totais (R$)', 'lar-andre-luiz' ); ?></label>
		<input type="text" id="lar-entradas" name="lar_entradas" value="<?php echo esc_attr( get_post_meta( $post->ID, 'lar_entradas', true ) ); ?>">

		<label for="lar-saidas"><?php esc_html_e( 'Saídas totais (R$)', 'lar-andre-luiz' ); ?></label>
		<input type="text" id="lar-saidas" name="lar_saidas" value="<?php echo esc_attr( get_post_meta( $post->ID, 'lar_saidas', true ) ); ?>">

		<label for="lar-entradas-itens"><?php esc_html_e( 'Itens de entrada (um por linha, no formato "Nome: valor")', 'lar-andre-luiz' ); ?></label>
		<textarea id="lar-entradas-itens" name="lar_entradas_itens" rows="5"><?php echo esc_textarea( get_post_meta( $post->ID, 'lar_entradas_itens', true ) ); ?></textarea>

		<label for="lar-saidas-itens"><?php esc_html_e( 'Itens de saída (um por linha, no formato "Nome: valor")', 'lar-andre-luiz' ); ?></label>
		<textarea id="lar-saidas-itens" name="lar_saidas_itens" rows="5"><?php echo esc_textarea( get_post_meta( $post->ID, 'lar_saidas_itens', true ) ); ?></textarea>

		<label for="lar-arquivo"><?php esc_html_e( 'ID do anexo PDF (use a biblioteca de mídia; opcional)', 'lar-andre-luiz' ); ?></label>
		<input type="number" id="lar-arquivo" name="lar_arquivo" value="<?php echo esc_attr( get_post_meta( $post->ID, 'lar_arquivo', true ) ); ?>">
		<p class="description"><?php esc_html_e( 'Não use caminho de arquivo: anexe o PDF pela mídia do WordPress para que o download funcione.', 'lar-andre-luiz' ); ?></p>
	</div>
	<?php
}

/**
 * Salva os campos do balancete.
 *
 * @param int $post_id ID do post.
 */
add_action(
	'save_post_prestacao',
	function ( $post_id ) {
		if ( ! isset( $_POST['lar_balancete_nonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['lar_balancete_nonce'] ) ), 'lar_save_balancete' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$campos = array(
			'lar_mes'           => 'sanitize_text_field',
			'lar_categoria'     => 'sanitize_key',
			'lar_ano'           => 'absint',
			'lar_entradas'      => 'sanitize_text_field',
			'lar_saidas'        => 'sanitize_text_field',
			'lar_entradas_itens' => 'sanitize_textarea_field',
			'lar_saidas_itens'   => 'sanitize_textarea_field',
			'lar_arquivo'       => 'absint',
		);

		foreach ( $campos as $key => $sanitize ) {
			if ( ! isset( $_POST[ $key ] ) ) {
				continue;
			}

			$value = call_user_func( $sanitize, wp_unslash( $_POST[ $key ] ) );

			if ( '' === $value || 0 === $value ) {
				delete_post_meta( $post_id, $key );
			} else {
				update_post_meta( $post_id, $key, $value );
			}
		}
	}
);