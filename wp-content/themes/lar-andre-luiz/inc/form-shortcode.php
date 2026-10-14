<?php
/**
 * Shortcode [lar_form].
 *
 * Por que shortcode e não HTML no editor: o WordPress aplica wp_kses_post
 * no post_content para visitantes sem o recurso "unfiltered_html", e <form>
 * não faz parte da lista permitida — o formulário simplesmente sumia da
 * página. Um shortcode passa pelo kses e tem a saída renderizada pelo PHP,
 * então o formulário aparece igual para administradores e visitantes.
 *
 * @package LarAndreLuiz
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Definição dos campos de cada formulário.
 *
 * @return array<string,array>
 */
function lar_form_campos() {
	return array(
		'contato'   => array(
			'rotulo'  => 'Fale Conosco',
			'legenda' => 'Preencha os campos e retornaremos pelo e-mail informado.',
			'botao'   => 'Enviar mensagem',
			'campos'  => array(
				'nome'     => array(
					'label'    => 'Seu nome',
					'type'     => 'text',
					'icon'     => 'fa-user',
					'required' => true,
					'ph'       => 'Como podemos te chamar?',
				),
				'email'    => array(
					'label'    => 'Seu e-mail',
					'type'     => 'email',
					'icon'     => 'fa-envelope',
					'required' => true,
					'ph'       => 'voce@email.com',
				),
				'assunto'  => array(
					'label' => 'Telefone / WhatsApp',
					'type'  => 'tel',
					'icon'  => 'fa-tag',
					'ph'    => '(00) 00000-0000',
				),
				'mensagem' => array(
					'label'    => 'Mensagem',
					'type'     => 'textarea',
					'icon'     => 'fa-comment',
					'required' => true,
					'ph'       => 'Escreva sua mensagem aqui...',
				),
			),
		),

		'curriculo' => array(
			'rotulo'  => 'Trabalhe Conosco',
			'legenda' => 'Envie sua candidatura e o currículo. Retornaremos por e-mail.',
			'botao'   => 'Enviar candidatura',
			'campos'  => array(
				'nome'     => array(
					'label'    => 'Nome completo',
					'type'     => 'text',
					'icon'     => 'fa-user',
					'required' => true,
					'ph'       => 'Seu nome completo',
				),
				'email'    => array(
					'label'    => 'E-mail',
					'type'     => 'email',
					'icon'     => 'fa-envelope',
					'required' => true,
					'ph'       => 'voce@email.com',
				),
				'telefone' => array(
					'label'    => 'Telefone',
					'type'     => 'tel',
					'icon'     => 'fa-phone',
					'required' => true,
					'ph'       => '(00) 00000-0000',
				),
				'area'     => array(
					'label'   => 'Área de atuação',
					'type'    => 'select',
					'icon'    => 'fa-briefcase',
					'options' => array(
						''                => 'Selecione a área',
						'Educação e Pedagogia' => 'Educação e Pedagogia',
						'Psicologia'      => 'Psicologia',
						'Esportes e Recreação' => 'Esportes e Recreação',
						'Tecnologia e Informática' => 'Tecnologia e Informática',
						'Artes e Cultura' => 'Artes e Cultura',
						'Administrativo'  => 'Administrativo',
						'Serviços Gerais' => 'Serviços Gerais',
					),
					'required' => true,
				),
				'mensagem' => array(
					'label'    => 'Conte um pouco sobre você',
					'type'     => 'textarea',
					'icon'     => 'fa-comment',
					'required' => true,
					'ph'       => 'Fale sobre sua experiência, formação e por que quer trabalhar no Lar André Luiz...',
				),
			),
			'arquivo'  => array(
				'label' => 'Currículo (PDF, DOC ou DOCX)',
				'icone' => 'fa-file-arrow-up',
				'ph'    => 'Clique para anexar seu currículo',
				'accept' => '.pdf,.doc,.docx',
			),
		),

		'inscricao' => array(
			'rotulo'  => 'Pré-inscrição',
			'legenda' => 'Adicione as crianças que deseja inscrever.',
			'botao'   => 'Enviar pré-inscrição',
			'campos'  => array(
				'responsavel' => array(
					'label'    => 'Nome do responsável',
					'type'     => 'text',
					'icon'     => 'fa-user',
					'required' => true,
					'ph'       => 'Nome completo do responsável',
				),
				'email'       => array(
					'label'    => 'E-mail',
					'type'     => 'email',
					'icon'     => 'fa-envelope',
					'required' => true,
					'ph'       => 'voce@email.com',
				),
				'telefone'    => array(
					'label'    => 'Telefone',
					'type'     => 'tel',
					'icon'     => 'fa-phone',
					'required' => true,
					'ph'       => '(00) 00000-0000',
				),
				'origem'      => array(
					'label'   => 'Como chegou até nós?',
					'type'    => 'select',
					'icon'    => 'fa-sign-in-alt',
					'options' => array(
						''                    => 'Selecione uma opção',
						'Demanda espontânea'  => 'Demanda espontânea',
						'CRAS'                => 'CRAS',
						'CREAS'               => 'CREAS',
						'Conselho Tutelar'    => 'Conselho Tutelar',
					),
					'required' => true,
				),
				'criancas'    => array(
					'label'    => 'Crianças / adolescentes',
					'type'     => 'textarea',
					'icon'     => 'fa-child',
					'required' => true,
					'ph'       => 'Informe nome, idade e escola de cada criança (um por linha)',
				),
			),
		),
	);
}

/**
 * Renderiza um campo.
 *
 * @param string $name     Nome do campo.
 * @param array  $campo    Definição.
 * @param string $form_id  ID do formulário (para agrupar linhas).
 * @return string
 */
function lar_render_campo( $name, $campo, $form_id ) {
	$id    = 'lar-' . $form_id . '-' . $name;
	$req   = ! empty( $campo['required'] ) ? ' required' : '';
	$icon  = ! empty( $campo['icon'] ) ? '<i class="fas ' . esc_attr( $campo['icon'] ) . '"></i>' : '';
	$inner = '';

	switch ( $campo['type'] ) {
		case 'textarea':
			$inner = sprintf(
				'<textarea id="%1$s" name="%2$s" placeholder="%3$s"%4$s></textarea>',
				esc_attr( $id ),
				esc_attr( $name ),
				esc_attr( $campo['ph'] ?? '' ),
				$req // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			);
			break;

		case 'select':
			$opcoes = '';
			foreach ( (array) ( $campo['options'] ?? array() ) as $valor => $texto ) {
				$opcoes .= sprintf(
					'<option value="%s">%s</option>',
					esc_attr( $valor ),
					esc_html( $texto )
				);
			}
			$inner = sprintf(
				'<select id="%1$s" name="%2$s"%3$s>%4$s</select>',
				esc_attr( $id ),
				esc_attr( $name ),
				$req, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				$opcoes // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			);
			break;

		default:
			$inner = sprintf(
				'<input type="%1$s" id="%2$s" name="%3$s" placeholder="%4$s"%5$s>',
				esc_attr( $campo['type'] ),
				esc_attr( $id ),
				esc_attr( $name ),
				esc_attr( $campo['ph'] ?? '' ),
				$req // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			);
	}

	return sprintf(
		'<label for="%1$s"><span>%2$s</span><div class="field">%3$s%4$s</div></label>',
		esc_attr( $id ),
		esc_html( $campo['label'] ),
		$icon, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		$inner // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	);
}

/**
 * Shortcode: [lar_form id="contato"].
 *
 * @param array $atts Atributos.
 * @return string
 */
function lar_form_shortcode( $atts ) {
	$atts = shortcode_atts( array( 'id' => 'contato' ), $atts, 'lar_form' );

	$form_id = sanitize_key( $atts['id'] );
	$todos   = lar_form_campos();

	if ( ! isset( $todos[ $form_id ] ) ) {
		return '';
	}

	$form = $todos[ $form_id ];

	// E-mail e telefone ficam lado a lado, como no template original.
	$par = array();
	foreach ( array( 'email', 'telefone' ) as $nome ) {
		if ( isset( $form['campos'][ $nome ] ) ) {
			$par[] = lar_render_campo( $nome, $form['campos'][ $nome ], $form_id );
		}
	}

	$linhas = array();

	if ( count( $par ) === 2 ) {
		$linhas[] = '<div class="form-row">' . implode( '', $par ) . '</div>';
		$par      = array();
	}

	foreach ( $par as $html_campo ) {
		$linhas[] = $html_campo;
	}

	foreach ( $form['campos'] as $nome => $campo ) {
		if ( 'email' === $nome || 'telefone' === $nome ) {
			continue;
		}

		$linhas[] = lar_render_campo( $nome, $campo, $form_id );
	}

	$html  = lar_form_notice();
	$html .= sprintf(
		'<form class="modern-form" method="post" action="%s" enctype="multipart/form-data" novalidate>',
		esc_url( admin_url( 'admin-post.php' ) )
	);

	$html .= wp_nonce_field( 'lar_form_' . $form_id, 'lar_form_nonce', true, false );
	$html .= sprintf(
		'<input type="hidden" name="action" value="lar_form"><input type="hidden" name="lar_form_id" value="%s">',
		esc_attr( $form_id )
	);

	if ( 'inscricao' === $form_id ) {
		$html .= sprintf(
			'<div class="section-head" style="margin:20px 0 16px"><h3 style="font-size:1.15rem;margin:0">%s</h3><p class="sub" style="font-size:.9rem">%s</p></div>',
			esc_html__( 'Crianças / adolescentes', 'lar-andre-luiz' ),
			esc_html( $form['legenda'] )
		);

		$html .= '<div class="children-list" id="childrenList"></div>';
		$html .= sprintf(
			'<button type="button" class="btn-add-child" data-lar-add-child><i class="fas fa-plus"></i> %s</button>',
			esc_html__( 'Adicionar criança', 'lar-andre-luiz' )
		);
	}

	$html .= implode( '', $linhas );

	if ( ! empty( $form['arquivo'] ) ) {
		$html .= sprintf(
			'<label><span>%1$s</span><div class="file-input"><input type="file" name="lar_arquivo" accept="%2$s" required><span class="file-label"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8l-6-6zm-1 7V3.5L18.5 9H13z"/></svg><span class="file-text" data-placeholder="%3$s">%3$s</span></span></div></label>',
			esc_html( $form['arquivo']['label'] ),
			esc_attr( $form['arquivo']['accept'] ),
			esc_attr( $form['arquivo']['ph'] )
		);
	}

	$html .= sprintf(
		'<button type="submit" class="primary" style="margin-top:22px"><i class="fas fa-paper-plane"></i> %s</button>',
		esc_html( $form['botao'] )
	);

	$html .= '</form>';

	return $html;
}
add_shortcode( 'lar_form', 'lar_form_shortcode' );