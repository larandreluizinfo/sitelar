<?php
/**
 * Formulários do site.
 *
 * Antes os formulários usavam alert() e não enviavam nada. Agora cada
 * envio vira um registro do post type "lar_mensagem" (visível no admin)
 * e é enviado por e-mail ao endereço configurado em Aparência → Contato.
 *
 * @package LarAndreLuiz
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registra o post type das mensagens recebidas.
 */
add_action(
	'init',
	function () {
		register_post_type(
			'lar_mensagem',
			array(
				'labels'          => array(
					'name'          => __( 'Mensagens recebidas', 'lar-andre-luiz' ),
					'singular_name' => __( 'Mensagem', 'lar-andre-luiz' ),
					'menu_name'     => __( 'Mensagens', 'lar-andre-luiz' ),
					'all_items'     => __( 'Todas as mensagens', 'lar-andre-luiz' ),
					'edit_item'     => __( 'Ver mensagem', 'lar-andre-luiz' ),
					'not_found'     => __( 'Nenhuma mensagem recebida.', 'lar-andre-luiz' ),
				),
				'public'          => false,
				'show_ui'         => true,
				'show_in_menu'    => true,
				'menu_icon'       => 'dashicons-email-alt',
				'menu_position'   => 26,
				'capability_type' => 'post',
				'capabilities'    => array(
					'create_posts' => 'do_not_allow',
				),
				'map_meta_cap'    => true,
				'supports'        => array( 'title', 'editor' ),
				'has_archive'     => false,
				'rewrite'         => false,
			)
		);
	}
);

/**
 * Formulários disponíveis.
 *
 * @return array<string,array{title:string,fields:array<string,string>}>
 */
function lar_formularios() {
	return array(
		'contato'   => array(
			'title'  => __( 'Fale Conosco', 'lar-andre-luiz' ),
			'fields' => array(
				'nome'    => __( 'Nome', 'lar-andre-luiz' ),
				'email'   => __( 'E-mail', 'lar-andre-luiz' ),
				'telefone' => __( 'Telefone', 'lar-andre-luiz' ),
				'assunto' => __( 'Assunto', 'lar-andre-luiz' ),
				'mensagem' => __( 'Mensagem', 'lar-andre-luiz' ),
			),
		),
		'curriculo' => array(
			'title'  => __( 'Trabalhe Conosco', 'lar-andre-luiz' ),
			'fields' => array(
				'nome'      => __( 'Nome completo', 'lar-andre-luiz' ),
				'email'     => __( 'E-mail', 'lar-andre-luiz' ),
				'telefone'  => __( 'Telefone', 'lar-andre-luiz' ),
				'area'      => __( 'Área de interesse', 'lar-andre-luiz' ),
				'mensagem'  => __( 'Apresentação', 'lar-andre-luiz' ),
			),
		),
		'inscricao' => array(
			'title'  => __( 'Pré-inscrição', 'lar-andre-luiz' ),
			'fields' => array(
				'responsavel' => __( 'Nome do responsável', 'lar-andre-luiz' ),
				'email'       => __( 'E-mail', 'lar-andre-luiz' ),
				'telefone'    => __( 'Telefone', 'lar-andre-luiz' ),
				'criancas'    => __( 'Crianças / adolescentes (idade e escola de cada um)', 'lar-andre-luiz' ),
				'mensagem'    => __( 'Observações', 'lar-andre-luiz' ),
			),
		),
	);
}

/**
 * Valida e grava um envio.
 *
 * @param string $form_id Identificador do formulário.
 * @param array  $dados   Dados já filtrados.
 * @return true|WP_Error
 */
function lar_processar_formulario( $form_id, $dados ) {
	$formularios = lar_formularios();

	if ( ! isset( $formularios[ $form_id ] ) ) {
		return new WP_Error( 'formulario_desconhecido', __( 'Formulário não encontrado.', 'lar-andre-luiz' ) );
	}

	$erros = array();

	$email = isset( $dados['email'] ) ? sanitize_email( $dados['email'] ) : '';

	if ( ! $email || ! is_email( $email ) ) {
		$erros[] = __( 'Informe um e-mail válido.', 'lar-andre-luiz' );
	}

	// Nome: cada formulário tem um campo de identificação do remetente.
	$identifica = ( 'inscricao' === $form_id ) ? 'responsavel' : 'nome';
	$valor      = isset( $dados[ $identifica ] ) ? sanitize_text_field( $dados[ $identifica ] ) : '';

	if ( '' === trim( $valor ) ) {
		$erros[] = 'responsavel' === $identifica
			? __( 'Informe o nome do responsável.', 'lar-andre-luiz' )
			: __( 'Informe seu nome.', 'lar-andre-luiz' );
	}

	$inscricao = ( 'inscricao' === $form_id );
	$criancas  = isset( $dados['criancas'] ) ? sanitize_textarea_field( $dados['criancas'] ) : '';

	if ( $inscricao && '' === trim( $criancas ) ) {
		$erros[] = __( 'Informe ao menos uma criança ou adolescente.', 'lar-andre-luiz' );
	}

	if ( $erros ) {
		return new WP_Error( 'campos_invalidos', implode( ' ', $erros ) );
	}

	$linhas = array();

	foreach ( $formularios[ $form_id ]['fields'] as $campo => $rotulo ) {
		$valor = isset( $dados[ $campo ] ) ? sanitize_textarea_field( $dados[ $campo ] ) : '';
		if ( '' !== trim( $valor ) ) {
			$linhas[] = '**' . $rotulo . ':** ' . $valor;
		}
	}

	$post_id = wp_insert_post(
		array(
			'post_type'    => 'lar_mensagem',
			'post_status'  => 'private',
			/* translators: 1: tipo do formulário, 2: nome do remetente */
			'post_title'   => sprintf( __( '%1$s — %2$s', 'lar-andre-luiz' ), $formularios[ $form_id ]['title'], $dados['nome'] ?? ( $dados['responsavel'] ?? __( 'Sem nome', 'lar-andre-luiz' ) ) ),
			'post_content' => implode( "\n\n", $linhas ),
		),
		true
	);

	if ( is_wp_error( $post_id ) ) {
		return $post_id;
	}

	update_post_meta( $post_id, 'lar_formulario', $form_id );
	update_post_meta( $post_id, 'lar_email', $email );
	update_post_meta( $post_id, 'lar_recebido_em', current_time( 'mysql' ) );

	$destino = get_option( 'lar_contato_email', get_option( 'admin_email' ) );

	wp_mail(
		$destino,
		/* translators: %s: nome do site */
		sprintf( __( '[%s] Nova mensagem pelo site', 'lar-andre-luiz' ), get_bloginfo( 'name' ) ),
		implode( "\n", $linhas ),
		array( 'Reply-To: ' . $email )
	);

	return true;
}

/**
 * Recebe o POST do formulário (admin_post).
 */
add_action(
	'admin_post_nopriv_lar_form',
	function () {
		lar_redirect_after_submit();
	}
);
add_action(
	'admin_post_lar_form',
	function () {
		lar_redirect_after_submit();
	}
);

/**
 * Processa e redireciona com mensagem na query.
 */
function lar_redirect_after_submit() {
	$form_id = isset( $_POST['lar_form_id'] ) ? sanitize_key( wp_unslash( $_POST['lar_form_id'] ) ) : '';

	check_admin_referer( 'lar_form_' . $form_id, 'lar_form_nonce' );

	$dados = array();

	$campos = isset( lar_formularios()[ $form_id ] ) ? array_keys( lar_formularios()[ $form_id ]['fields'] ) : array();
	$campos = array_merge( $campos, array( 'nome', 'responsavel', 'email', 'telefone', 'criancas', 'mensagem' ) );

	foreach ( array_unique( $campos ) as $campo ) {
		if ( isset( $_POST[ $campo ] ) && is_string( $_POST[ $campo ] ) ) {
			$dados[ $campo ] = sanitize_textarea_field( wp_unslash( $_POST[ $campo ] ) );
		}
	}

	$resultado = lar_processar_formulario( $form_id, $dados );

	// $resultado é WP_Error no caso de falha — e WP_Error é um objeto,
	// portanto truthy. Comparar com true evita marcar falha como sucesso.
	$sucesso = ( true === $resultado );

	$redirect = wp_get_referer() ? wp_get_referer() : home_url( '/' );

	$redirect = add_query_arg( array( 'lar_enviado' => $sucesso ? '1' : '0' ), $redirect );

	if ( is_wp_error( $resultado ) ) {
		$redirect = add_query_arg( 'lar_erro', rawurlencode( $resultado->get_error_message() ), $redirect );
	}

	wp_safe_redirect( $redirect );
	exit;
}

/**
 * Exibe o aviso de retorno do formulário.
 *
 * @return string|null
 */
function lar_form_notice() {
	if ( ! isset( $_GET['lar_enviado'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return null;
	}

	$ok = '1' === $_GET['lar_enviado']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	if ( $ok ) {
		return '<div class="lar-notice lar-notice-ok" role="status">' . esc_html__( 'Recebemos sua mensagem! Em breve entraremos em contato.', 'lar-andre-luiz' ) . '</div>';
	}

	$erro = isset( $_GET['lar_erro'] ) ? sanitize_text_field( wp_unslash( $_GET['lar_erro'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	return '<div class="lar-notice lar-notice-erro" role="alert">' . esc_html( $erro ) . '</div>';
}

/* -------------------------------------------------------------------------
 * Opções de contato / PIX / redes sociais
 * ---------------------------------------------------------------------- */

add_action(
	'customize_register',
	function ( $wp_customize ) {
		$wp_customize->add_section(
			'lar_contato',
			array(
				'title'       => __( 'Contato e Doações', 'lar-andre-luiz' ),
				'priority'    => 30,
				'description' => __( 'Dados exibidos no rodapé, na modal de doação e nos links sociais.', 'lar-andre-luiz' ),
			)
		);

		$campos = array(
			'lar_contato_email'      => array( __( 'E-mail de contato', 'lar-andre-luiz' ), 'contato@larandreluiz.org.br', 'sanitize_email' ),
			'lar_contato_tel'        => array( __( 'Telefone', 'lar-andre-luiz' ), '(00) 0000-0000', 'sanitize_text_field' ),
			'lar_contato_endereco'   => array( __( 'Endereço', 'lar-andre-luiz' ), 'R. Alan Kardec, 412 - Vila Industrial, Pirassununga - SP, 13631-300', 'sanitize_text_field' ),
			'lar_pix_chave'          => array( __( 'Chave PIX', 'lar-andre-luiz' ), '', 'sanitize_text_field' ),
			'lar_doacao_banco'       => array( __( 'Banco', 'lar-andre-luiz' ), '', 'sanitize_text_field' ),
			'lar_doacao_agencia'     => array( __( 'Agência', 'lar-andre-luiz' ), '', 'sanitize_text_field' ),
			'lar_doacao_conta'       => array( __( 'Conta', 'lar-andre-luiz' ), '', 'sanitize_text_field' ),
			'lar_social_instagram'   => array( __( 'Instagram (URL)', 'lar-andre-luiz' ), '', 'esc_url_raw' ),
			'lar_social_facebook'    => array( __( 'Facebook (URL)', 'lar-andre-luiz' ), '', 'esc_url_raw' ),
			'lar_social_youtube'     => array( __( 'YouTube (URL)', 'lar-andre-luiz' ), '', 'esc_url_raw' ),
			'lar_social_whatsapp'    => array( __( 'WhatsApp (URL)', 'lar-andre-luiz' ), '', 'esc_url_raw' ),
		);

		foreach ( $campos as $id => $config ) {
			$wp_customize->add_setting(
				$id,
				array(
					'default'           => $config[1],
					'sanitize_callback' => $config[2],
				)
			);

			$wp_customize->add_control(
				$id,
				array(
					'label'   => $config[0],
					'section' => 'lar_contato',
					'type'    => 'text',
				)
			);
		}
	}
);

/**
 * Mostra as colunas de resumo das mensagens no admin.
 *
 * @param array $columns Colunas.
 * @return array
 */
add_filter(
	'manage_lar_mensagem_posts_columns',
	function ( $columns ) {
		return array(
			'cb'          => isset( $columns['cb'] ) ? $columns['cb'] : '',
			'title'       => __( 'Remetente / assunto', 'lar-andre-luiz' ),
			'lar_form'    => __( 'Formulário', 'lar-andre-luiz' ),
			'lar_email'   => __( 'E-mail', 'lar-andre-luiz' ),
			'date'        => __( 'Recebido em', 'lar-andre-luiz' ),
		);
	}
);

add_action(
	'manage_lar_mensagem_posts_custom_column',
	function ( $col, $post_id ) {
		if ( 'lar_form' === $col ) {
			$formularios = lar_formularios();
			$form_id     = get_post_meta( $post_id, 'lar_formulario', true );
			echo esc_html( isset( $formularios[ $form_id ] ) ? $formularios[ $form_id ]['title'] : $form_id );
		}

		if ( 'lar_email' === $col ) {
			$email = get_post_meta( $post_id, 'lar_email', true );
			echo esc_html( $email );
		}
	},
	10,
	2
);