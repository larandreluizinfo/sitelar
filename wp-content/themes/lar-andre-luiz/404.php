<?php
/**
 * Página 404.
 *
 * @package LarAndreLuiz
 */

get_header();
?>

<div class="page-wrap">
	<div class="sec" style="padding-top:96px">
		<div class="section-head">
			<div class="eyebrow"><?php esc_html_e( 'Erro 404', 'lar-andre-luiz' ); ?></div>
			<h1 class="section-title"><?php esc_html_e( 'Não encontramos esta página', 'lar-andre-luiz' ); ?></h1>
			<p class="sub"><?php esc_html_e( 'O endereço pode ter mudado ou não existir. Use o menu abaixo para continuar navegando.', 'lar-andre-luiz' ); ?></p>
		</div>

		<div class="grid">
			<?php foreach ( lar_page_slugs() as $lar_id => $lar_slug ) : ?>
				<?php
				$lar_pagina = '' === $lar_slug ? null : get_page_by_path( $lar_slug );
				if ( ! $lar_pagina ) {
					continue;
				}
				?>
				<div class="card">
					<h3>
						<a href="<?php echo esc_url( get_permalink( $lar_pagina ) ); ?>">
							<?php echo esc_html( get_the_title( $lar_pagina ) ); ?>
						</a>
					</h3>
					<?php if ( has_excerpt( $lar_pagina ) ) : ?>
						<p><?php echo esc_html( wp_trim_words( get_the_excerpt( $lar_pagina ), 24 ) ); ?></p>
					<?php endif; ?>
					<a class="activity-link" href="<?php echo esc_url( get_permalink( $lar_pagina ) ); ?>"><?php esc_html_e( 'Ir para a página', 'lar-andre-luiz' ); ?></a>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
</div>

<?php
get_footer();