<?php
/**
 * Modais do site (doação, galeria, balancete).
 *
 * Os modais são acionados por data-attributes e os botões de navegação
 * são <button> de verdade — nada de onclick inline.
 *
 * @package LarAndreLuiz
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$lar_email  = get_option( 'lar_contato_email', 'contato@larandreluiz.org.br' );
$lar_tel    = get_option( 'lar_contato_tel', '(00) 0000-0000' );
$lar_end    = get_option( 'lar_contato_endereco', 'R. Alan Kardec, 412 - Vila Industrial, Pirassununga - SP, 13631-300' );
$lar_pix    = get_option( 'lar_pix_chave', '' );
$lar_banco  = get_option( 'lar_doacao_banco', '' );
$lar_agencia = get_option( 'lar_doacao_agencia', '' );
$lar_conta  = get_option( 'lar_doacao_conta', '' );
?>

<div class="modal" id="donation" role="dialog" aria-modal="true" aria-labelledby="donation-title" hidden>
	<div class="modalbox donbox">
		<button class="close" type="button" data-lar-close="donation" aria-label="<?php esc_attr_e( 'Fechar', 'lar-andre-luiz' ); ?>">&times;</button>

		<img class="don-bg" src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/menino2.png' ); ?>" alt="<?php esc_attr_e( 'Criança atendida pelo Lar André Luiz', 'lar-andre-luiz' ); ?>">

		<div class="don-grid">
			<div class="don-left">
				<div class="eyebrow" style="background:var(--yellow-light);color:var(--ink)"><?php esc_html_e( 'Faça parte', 'lar-andre-luiz' ); ?></div>
				<h2 class="section-title" style="font-size:2.2rem" id="donation-title"><?php esc_html_e( 'Doe aqui', 'lar-andre-luiz' ); ?></h2>
				<p><?php esc_html_e( 'Sua contribuição ajuda a manter as atividades, os materiais e as oportunidades para as crianças e adolescentes atendidos pelo Lar André Luiz.', 'lar-andre-luiz' ); ?></p>

				<?php if ( $lar_banco || $lar_agencia || $lar_conta ) : ?>
					<div class="bank-card">
						<h3><?php esc_html_e( 'Depósito bancário', 'lar-andre-luiz' ); ?></h3>
						<?php if ( $lar_banco ) : ?>
							<p><strong><?php esc_html_e( 'Banco:', 'lar-andre-luiz' ); ?></strong> <?php echo esc_html( $lar_banco ); ?></p>
						<?php endif; ?>
						<?php if ( $lar_agencia ) : ?>
							<p><strong><?php esc_html_e( 'Agência:', 'lar-andre-luiz' ); ?></strong> <?php echo esc_html( $lar_agencia ); ?></p>
						<?php endif; ?>
						<?php if ( $lar_conta ) : ?>
							<p><strong><?php esc_html_e( 'Conta:', 'lar-andre-luiz' ); ?></strong> <?php echo esc_html( $lar_conta ); ?></p>
						<?php endif; ?>
					</div>
				<?php endif; ?>

				<div class="don-contact">
					<h3><?php esc_html_e( 'Contato', 'lar-andre-luiz' ); ?></h3>
					<p><a href="mailto:<?php echo esc_attr( $lar_email ); ?>"><?php echo esc_html( $lar_email ); ?></a></p>
					<p><a href="tel:<?php echo esc_attr( preg_replace( '/\D+/', '', $lar_tel ) ); ?>"><?php echo esc_html( $lar_tel ); ?></a></p>
					<p><?php echo esc_html( $lar_end ); ?></p>
				</div>
			</div>

			<div class="don-mid"></div>

			<div class="don-right">
				<div class="eyebrow" style="background:var(--yellow-light);color:var(--ink)">Pix</div>
				<p><?php esc_html_e( 'Doe em segundos, de onde estiver, escaneando o QR code abaixo.', 'lar-andre-luiz' ); ?></p>

				<?php if ( $lar_pix ) : ?>
					<p class="pix-key"><?php esc_html_e( 'Chave PIX:', 'lar-andre-luiz' ); ?> <strong><?php echo esc_html( $lar_pix ); ?></strong></p>
				<?php endif; ?>

				<div class="qr">
					<img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/pix-qr.svg' ); ?>" alt="<?php esc_attr_e( 'QR Code PIX', 'lar-andre-luiz' ); ?>">
					<?php if ( ! $lar_pix ) : ?>
						<p class="qr-placeholder"><?php esc_html_e( 'Cadastre a chave PIX em Aparência → Contato e Doações.', 'lar-andre-luiz' ); ?></p>
					<?php endif; ?>
				</div>
			</div>
		</div>

		<div class="don-note">
			<i class="fas fa-info-circle" aria-hidden="true"></i>
			<?php esc_html_e( 'Caso precise de recibo, entre em contato.', 'lar-andre-luiz' ); ?>
		</div>
	</div>
</div>

<div class="modal" id="imgModal" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Galeria de imagens', 'lar-andre-luiz' ); ?>" hidden>
	<div class="modalbox imgbox">
		<button class="close" type="button" data-lar-close="imgModal" aria-label="<?php esc_attr_e( 'Fechar', 'lar-andre-luiz' ); ?>">&times;</button>

		<div class="gal-imgwrap">
			<button class="gal-nav gal-prev" type="button" data-lar-gal="prev" aria-label="<?php esc_attr_e( 'Anterior', 'lar-andre-luiz' ); ?>">
				<i class="fas fa-chevron-left" aria-hidden="true"></i>
			</button>

			<img id="imgModalImg" src="" alt="<?php esc_attr_e( 'Imagem do mosaico', 'lar-andre-luiz' ); ?>">

			<button class="gal-nav gal-next" type="button" data-lar-gal="next" aria-label="<?php esc_attr_e( 'Próximo', 'lar-andre-luiz' ); ?>">
				<i class="fas fa-chevron-right" aria-hidden="true"></i>
			</button>
		</div>

		<p id="imgModalCap"></p>
		<div class="gal-dots" id="galDots"></div>
	</div>
</div>

<div class="modal" id="transModal" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Detalhes do balancete', 'lar-andre-luiz' ); ?>" hidden>
	<div class="modalbox">
		<button class="close" type="button" data-lar-close="transModal" aria-label="<?php esc_attr_e( 'Fechar', 'lar-andre-luiz' ); ?>">&times;</button>

		<div class="eyebrow" style="background:var(--yellow-light);color:var(--ink)"><?php esc_html_e( 'Prestação de contas', 'lar-andre-luiz' ); ?></div>

		<div class="trans-meta"><span><?php esc_html_e( 'Título:', 'lar-andre-luiz' ); ?></span><strong id="transTitle"></strong></div>
		<div class="trans-meta"><span><?php esc_html_e( 'Mês:', 'lar-andre-luiz' ); ?></span><strong class="section-title" style="font-size:1.9rem" id="transMonth"></strong></div>

		<div class="trans-detail-lines">
			<div class="trans-line"><span><?php esc_html_e( 'Entradas', 'lar-andre-luiz' ); ?></span><strong class="up" id="transIn"></strong></div>
			<div class="trans-line"><span><?php esc_html_e( 'Saídas', 'lar-andre-luiz' ); ?></span><strong class="down" id="transOut"></strong></div>
		</div>

		<div class="trans-cols">
			<div class="trans-col">
				<h4><i class="fas fa-arrow-down" aria-hidden="true"></i> <?php esc_html_e( 'Entradas', 'lar-andre-luiz' ); ?></h4>
				<ul id="transInItems"></ul>
			</div>
			<div class="trans-col">
				<h4><i class="fas fa-arrow-up" aria-hidden="true"></i> <?php esc_html_e( 'Saídas', 'lar-andre-luiz' ); ?></h4>
				<ul id="transOutItems"></ul>
			</div>
		</div>

		<h4 class="trans-files-title"><?php esc_html_e( 'Arquivo:', 'lar-andre-luiz' ); ?></h4>
		<ul class="trans-files" id="transFiles"></ul>
	</div>
</div>