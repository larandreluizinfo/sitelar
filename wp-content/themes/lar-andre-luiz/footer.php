<?php
/**
 * Rodapé, modais e scripts.
 *
 * @package LarAndreLuiz
 */

$lar_contato_email = get_option( 'lar_contato_email', 'contato@larandreluiz.org.br' );
$lar_contato_tel   = get_option( 'lar_contato_tel', '(00) 0000-0000' );
$lar_contato_end   = get_option( 'lar_contato_endereco', 'R. Alan Kardec, 412 - Vila Industrial, Pirassununga - SP, 13631-300' );

$lar_social = array(
	'instagram' => get_option( 'lar_social_instagram', '' ),
	'facebook'  => get_option( 'lar_social_facebook', '' ),
	'youtube'   => get_option( 'lar_social_youtube', '' ),
	'whatsapp'  => get_option( 'lar_social_whatsapp', '' ),
);

$lar_social_icons = array(
	'instagram' => 'M12 2.2c3.2 0 3.6 0 4.9.1 1.2.1 1.8.3 2.2.4.6.2 1 .5 1.4.9.4.4.7.9.9 1.4.2.4.4 1 .4 2.2.1 1.3.1 1.7.1 4.9s0 3.6-.1 4.9c-.1 1.2-.3 1.8-.4 2.2-.2.6-.5 1-.9 1.4-.4.4-.9.7-1.4.9-.4.2-1 .4-2.2.4-1.3.1-1.7.1-4.9.1s-3.6 0-4.9-.1c-1.2-.1-1.8-.3-2.2-.4-.6-.2-1-.5-1.4-.9-.4-.4-.7-.9-.9-1.4-.2-.4-.4-1-.4-2.2C2.2 15.6 2.2 15.2 2.2 12s0-3.6.1-4.9c.1-1.2.3-1.8.4-2.2.2-.6.5-1 .9-1.4.4-.4.9-.7 1.4-.9.4-.2 1-.4 2.2-.4C8.4 2.2 8.8 2.2 12 2.2zm0 3.6a6.2 6.2 0 100 12.4 6.2 6.2 0 000-12.4zm0 10.2a4 4 0 110-8 4 4 0 010 8zm6.4-11.8a1.4 1.4 0 11-2.9 0 1.4 1.4 0 012.9 0z',
	'facebook'  => 'M22 12a10 10 0 10-11.6 9.9v-7H7.9V12h2.5V9.8c0-2.5 1.5-3.9 3.8-3.9 1.1 0 2.2.2 2.2.2v2.5h-1.3c-1.2 0-1.6.8-1.6 1.6V12h2.8l-.4 2.9h-2.4v7A10 10 0 0022 12z',
	'youtube'   => 'M23.5 6.2a3 3 0 00-2.1-2.2C19.5 3.5 12 3.5 12 3.5s-7.5 0-9.4.5A3 3 0 00.5 6.2 31 31 0 000 12a31 31 0 00.5 5.8 3 3 0 002.1 2.2c1.9.5 9.4.5 9.4.5s7.5 0 9.4-.5a3 3 0 002.1-2.2A31 31 0 0024 12a31 31 0 00-.5-5.8zM9.6 15.6V8.4L15.8 12l-6.2 3.6z',
	'whatsapp'  => 'M12 2a10 10 0 00-8.6 15.1L2 22l5-1.3A10 10 0 1012 2zm0 18.2c-1.6 0-3.1-.4-4.4-1.2l-.3-.2-3 .8.8-2.9-.2-.3A8.2 8.2 0 1112 20.2zm4.5-6.1c-.2-.1-1.5-.7-1.7-.8-.2-.1-.4-.1-.6.1-.2.2-.6.8-.8 1-.1.2-.3.2-.5.1a6.7 6.7 0 01-2-1.2 7.6 7.6 0 01-1.4-1.7c-.1-.3 0-.4.1-.6l.4-.5c.1-.2.2-.3.3-.5.1-.2 0-.4 0-.5l-.8-1.9c-.2-.5-.4-.4-.6-.4h-.5c-.2 0-.5.1-.7.3-.2.3-.9.9-.9 2.2s.9 2.5 1.1 2.7c.1.2 1.9 2.9 4.5 4 1.6.6 2.3.7 3.1.6.5-.1 1.5-.6 1.7-1.2.2-.6.2-1.1.2-1.2l-.6-.4z',
);
?>
<?php
/*
 * O CTA de doação fica no fim de <main> (e não depois dele) para que o
 * <section> continue sendo conteúdo principal da página.
 */
get_template_part( 'template-parts/cta-doe' );
?>
</main>

<footer>
	<div class="foot">
		<div class="foot-grid">
			<div class="brand">
				<h4><?php bloginfo( 'name' ); ?></h4>
				<small><?php bloginfo( 'description' ); ?></small>
				<p><?php esc_html_e( 'Há mais de seis décadas acolhendo crianças e adolescentes no contraturno escolar, com educação, esporte, cultura e tecnologia.', 'lar-andre-luiz' ); ?></p>

				<?php if ( array_filter( $lar_social ) ) : ?>
					<div class="social">
						<?php foreach ( $lar_social as $rede => $url ) : ?>
							<?php if ( ! $url ) { continue; } ?>
							<a href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php echo esc_attr( ucfirst( $rede ) ); ?>">
								<svg viewBox="0 0 24 24" aria-hidden="true"><path d="<?php echo esc_attr( $lar_social_icons[ $rede ] ); ?>"/></svg>
							</a>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>

			<div>
				<h4><?php esc_html_e( 'Institucional', 'lar-andre-luiz' ); ?></h4>
				<div class="links">
					<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Início', 'lar-andre-luiz' ); ?></a>
					<a href="<?php echo esc_url( home_url( '/quem-somos/' ) ); ?>"><?php esc_html_e( 'Quem somos', 'lar-andre-luiz' ); ?></a>
					<a href="<?php echo esc_url( home_url( '/transparencia/' ) ); ?>"><?php esc_html_e( 'Transparência', 'lar-andre-luiz' ); ?></a>
					<a href="<?php echo esc_url( home_url( '/quadra-dos-sonhos/' ) ); ?>"><?php esc_html_e( 'Quadra dos Sonhos', 'lar-andre-luiz' ); ?></a>
					<a href="<?php echo esc_url( home_url( '/instituto-aces/' ) ); ?>"><?php esc_html_e( 'Instituto ACES', 'lar-andre-luiz' ); ?></a>
				</div>
			</div>

			<div>
				<h4><?php esc_html_e( 'O que fazemos', 'lar-andre-luiz' ); ?></h4>
				<div class="links">
					<a href="<?php echo esc_url( home_url( '/atividades/' ) ); ?>"><?php esc_html_e( 'Atividades', 'lar-andre-luiz' ); ?></a>
					<a href="<?php echo esc_url( home_url( '/galeria/' ) ); ?>"><?php esc_html_e( 'Galeria', 'lar-andre-luiz' ); ?></a>
					<a href="<?php echo esc_url( home_url( '/noticias/' ) ); ?>"><?php esc_html_e( 'Notícias', 'lar-andre-luiz' ); ?></a>
					<a href="<?php echo esc_url( home_url( '/eventos/' ) ); ?>"><?php esc_html_e( 'Eventos', 'lar-andre-luiz' ); ?></a>
					<a href="<?php echo esc_url( home_url( '/trabalhe-conosco/' ) ); ?>"><?php esc_html_e( 'Trabalhe Conosco', 'lar-andre-luiz' ); ?></a>
					<a href="<?php echo esc_url( home_url( '/pre-inscricao/' ) ); ?>"><?php esc_html_e( 'Pré Inscrição', 'lar-andre-luiz' ); ?></a>
				</div>
			</div>

			<div>
				<h4><?php esc_html_e( 'Contato', 'lar-andre-luiz' ); ?></h4>
				<div class="links">
					<a href="mailto:<?php echo esc_attr( $lar_contato_email ); ?>"><?php echo esc_html( $lar_contato_email ); ?></a>
					<a href="tel:<?php echo esc_attr( preg_replace( '/\D+/', '', $lar_contato_tel ) ); ?>"><?php echo esc_html( $lar_contato_tel ); ?></a>
					<span><?php echo esc_html( $lar_contato_end ); ?></span>
				</div>
			</div>
		</div>

<div class="foot-bottom">
		<div><strong><?php bloginfo( 'name' ); ?></strong> — <?php esc_html_e( 'Educação, esporte e futuro', 'lar-andre-luiz' ); ?></div>
		<div>
			<?php
			printf(
				/* translators: 1: ano atual, 2: nome do site */
				esc_html__( '© %1$s %2$s. Todos os direitos reservados.', 'lar-andre-luiz' ),
				esc_html( gmdate( 'Y' ) ),
				esc_html( get_bloginfo( 'name' ) )
			);
			?>
		</div>
	</div>
</div>
</footer>

<?php get_template_part( 'template-parts/modals' ); ?>

<?php wp_footer(); ?>
</body>
</html>

<?php wp_footer(); ?>
</body>
</html>