<?php
/**
 * Bloco da prestação de contas (filtros + cards + modal).
 *
 * Renderizado no servidor: o conteúdo fica no HTML e funciona mesmo
 * com JavaScript desativado. O JS só adiciona a navegação do modal.
 *
 * @package LarAndreLuiz
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$lar_cats      = lar_transparencia_categorias();
$lar_anos      = lar_transparencia_anos();
$lar_categoria = isset( $_GET['categoria'] ) ? sanitize_key( wp_unslash( $_GET['categoria'] ) ) : 'institucional'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$lar_ano       = isset( $_GET['ano'] ) ? absint( $_GET['ano'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

if ( ! isset( $lar_cats[ $lar_categoria ] ) ) {
	$lar_categoria = 'institucional';
}

if ( ! $lar_ano || ! in_array( $lar_ano, $lar_anos, true ) ) {
	$lar_ano = $lar_anos ? $lar_anos[0] : 0;
}

$lar_balancetes = $lar_ano ? lar_transparencia_balancetes( $lar_categoria, $lar_ano ) : array();
?>

<div class="lar-transparencia">

	<?php if ( $lar_anos ) : ?>
		<form class="trans-dropdowns lar-trans-filters" method="get" action="<?php echo esc_url( get_permalink() ); ?>">
			<?php if ( $lar_ano && count( $lar_anos ) > 1 ) : ?>
				<label class="screen-reader-text" for="lar-ano"><?php esc_html_e( 'Ano', 'lar-andre-luiz' ); ?></label>
				<select class="trans-select" id="lar-ano" name="ano">
					<?php foreach ( $lar_anos as $ano ) : ?>
						<option value="<?php echo esc_attr( $ano ); ?>" <?php selected( $ano, $lar_ano ); ?>>
							<?php echo esc_html( $ano ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			<?php endif; ?>

			<label class="screen-reader-text" for="lar-categoria"><?php esc_html_e( 'Categoria', 'lar-andre-luiz' ); ?></label>
			<select class="trans-select" id="lar-categoria" name="categoria">
				<?php foreach ( $lar_cats as $slug => $label ) : ?>
					<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $slug, $lar_categoria ); ?>>
						<?php echo esc_html( $label ); ?>
					</option>
				<?php endforeach; ?>
			</select>

			<noscript>
				<button type="submit" class="cta"><?php esc_html_e( 'Filtrar', 'lar-andre-luiz' ); ?></button>
			</noscript>
		</form>
	<?php endif; ?>

	<?php if ( ! $lar_balancetes ) : ?>
		<div class="lar-empty">
			<strong><?php esc_html_e( 'Dados em construção', 'lar-andre-luiz' ); ?></strong>
			<?php
			printf(
				/* translators: %s: área de administration do WordPress */
				esc_html__( 'Nenhum balancete publicado para %s ainda. Cadastre-os em Prestação de contas no painel do WordPress.', 'lar-andre-luiz' ),
				esc_html( $lar_cats[ $lar_categoria ] )
			);
			?>
		</div>
	<?php else : ?>
		<div class="trans-months">
			<?php
			foreach ( $lar_balancetes as $balancete ) :
				$id          = $balancete->ID;
				$mes         = get_post_meta( $id, 'lar_mes', true );
				$rotulo      = $mes ? $mes : get_the_title( $id );
				$entradas    = (float) lar_moeda( get_post_meta( $id, 'lar_entradas', true ) );
				$saidas      = (float) lar_moeda( get_post_meta( $id, 'lar_saidas', true ) );
				$saldo       = $entradas - $saidas;
				$in_itens    = array_filter( array_map( 'trim', explode( "\n", (string) get_post_meta( $id, 'lar_entradas_itens', true ) ) ) );
				$out_itens   = array_filter( array_map( 'trim', explode( "\n", (string) get_post_meta( $id, 'lar_saidas_itens', true ) ) ) );
				$arquivo_id  = (int) get_post_meta( $id, 'lar_arquivo', true );
				$arquivo_url = $arquivo_id ? wp_get_attachment_url( $arquivo_id ) : '';
				$badges      = array(
					'institucional' => 'var(--green-primary)',
					'cmdca'          => 'var(--blue)',
					'smdhcj'         => 'var(--red)',
					'emendas'        => 'var(--yellow)',
				);
				$badge       = isset( $badges[ $lar_categoria ] ) ? $badges[ $lar_categoria ] : 'var(--green-primary)';
				?>
				<article class="card trans-card"
					data-lar-balancete
					data-titulo="<?php echo esc_attr( get_the_title( $id ) ); ?>"
					data-mes="<?php echo esc_attr( $rotulo ); ?>"
					data-entradas="<?php echo esc_attr( $entradas ); ?>"
					data-saidas="<?php echo esc_attr( $saidas ); ?>"
					data-in-itens="<?php echo esc_attr( wp_json_encode( array_values( $in_itens ) ) ); ?>"
					data-out-itens="<?php echo esc_attr( wp_json_encode( array_values( $out_itens ) ) ); ?>"
					data-arquivo="<?php echo esc_attr( $arquivo_url ); ?>">

					<span class="badge" style="background:<?php echo esc_attr( $badge ); ?>;color:<?php echo 'var(--yellow)' === $badge ? 'var(--ink)' : '#fff'; ?>;">
						<?php echo esc_html( $rotulo ); ?>
					</span>

					<h3><?php echo esc_html( get_the_title( $id ) ); ?></h3>

					<div class="trans-meta">
						<span><?php esc_html_e( 'Saldo:', 'lar-andre-luiz' ); ?></span>
						<strong><?php echo esc_html( ( $saldo >= 0 ? '+ ' : '− ' ) . 'R$ ' . number_format( abs( $saldo ), 2, ',', '.' ) ); ?></strong>
					</div>

					<?php if ( $arquivo_url ) : ?>
						<div class="trans-meta">
							<span><?php esc_html_e( 'Arquivo:', 'lar-andre-luiz' ); ?></span>
							<a class="trans-file-link" href="<?php echo esc_url( $arquivo_url ); ?>" download>
								<i class="fas fa-file-pdf" aria-hidden="true"></i>
								<?php echo esc_html( basename( $arquivo_url ) ); ?>
							</a>
						</div>
					<?php endif; ?>

					<p class="trans-ver-mais">
						<?php esc_html_e( 'Ver detalhes', 'lar-andre-luiz' ); ?>
						<i class="fas fa-arrow-right" aria-hidden="true"></i>
					</p>
				</article>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</div>