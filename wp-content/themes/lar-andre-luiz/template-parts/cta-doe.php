<?php
/**
 * Chamada para doação exibida no rodapé de todas as páginas.
 *
 * Antigo script escondia este bloco via JS em "trabalhe" e "inscricao".
 * Agora a exclusão é feita no servidor.
 *
 * @package LarAndreLuiz
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$lar_ocultar_cta = array( 'trabalhe-conosco', 'pre-inscricao' );

if ( is_singular( 'page' ) && in_array( get_post_field( 'post_name' ), $lar_ocultar_cta, true ) ) {
	return;
}
?>

<section class="cta-doe fade-up">
	<div class="cta-card">
		<div class="cta-blob b1"></div>
		<div class="cta-blob b2"></div>
		<div class="cta-text">
			<div class="cta-eyebrow" style="background:var(--yellow-light);color:var(--ink)"><?php esc_html_e( 'Faça a diferença', 'lar-andre-luiz' ); ?></div>
			<h2><?php esc_html_e( 'Sua doação transforma vidas', 'lar-andre-luiz' ); ?></h2>
			<p><?php esc_html_e( 'Ajude a manter as atividades, os materiais e as oportunidades para as crianças e adolescentes atendidos pelo Lar André Luiz.', 'lar-andre-luiz' ); ?></p>
			<?php echo lar_donate_button( array( 'class' => 'cta-btn' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</div>
		<div class="cta-img">
			<img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/menino.png' ); ?>" alt="<?php esc_attr_e( 'Criança atendida pelo Lar André Luiz', 'lar-andre-luiz' ); ?>">
		</div>
	</div>
</section>