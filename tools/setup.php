<?php
/**
 * Instalador do ambiente local — Lar André Luiz.
 *
 * Um comando, do zero ao site rodando:
 *
 *   setup.bat
 *
 * O que ele faz, nesta ordem (cada etapa já pronta é pulada):
 *  1. confere PHP e extensões necessárias;
 *  2. baixa o WP-CLI (tools/wp-cli.phar) se ainda não existir;
 *  3. baixa o núcleo do WordPress (--locale=pt_BR, sem conteúdo padrão);
 *  4. cria o wp-config.php e o banco de dados;
 *  5. instala o WordPress;
 *  6. ativa o tema lar-andre-luiz;
 *  7. importa as páginas a partir de layout/index.html (somente leitura);
 *  8. configura o ícone do site;
 *  9. verifica os elementos renderizados.
 *
 * Variáveis de ambiente (todas opcionais, com padrão do Laragon):
 *   LAR_DB_NAME, LAR_DB_USER, LAR_DB_PASS, LAR_DB_HOST, LAR_TABLE_PREFIX,
 *   LAR_URL, LAR_TITLE, LAR_ADMIN_USER, LAR_ADMIN_PASSWORD, LAR_ADMIN_EMAIL,
 *   LAR_ORIGEM (padrão: layout/index.html — NÃO aponte para escrita),
 *   LAR_LOCALE, LAR_SKIP_TLS (1 para ignorar SSL no download, se preciso).
 *
 * Opções:
 *   --fresh         apaga o banco e reinstala tudo do zero (DESTRUTIVO);
 *   --skip-import   pula a importação das páginas;
 *   --skip-verify   pula a verificação final;
 *   --dry-run       só mostra o que seria executado, sem executar;
 *   --help          mostra esta ajuda.
 *
 * Nunca rode --fresh em produção: ele apaga páginas e balancetes.
 *
 * @package LarAndreLuiz
 */

if ( 'cli' !== PHP_SAPI ) {
	exit( 'Execute somente via CLI.' );
}

define( 'LAR_SETUP_ROOT', dirname( __DIR__ ) );
define( 'LAR_SETUP_WPCLI', LAR_SETUP_ROOT . '/tools/wp-cli.phar' );

$opcoes = array(
	'fresh'       => false,
	'skip-import' => false,
	'skip-verify' => false,
	'dry-run'     => false,
	'nativo'      => false,
);

foreach ( $argv as $arg ) {
	if ( '--fresh' === $arg ) {
		$opcoes['fresh'] = true;
	} elseif ( '--skip-import' === $arg ) {
		$opcoes['skip-import'] = true;
	} elseif ( '--skip-verify' === $arg ) {
		$opcoes['skip-verify'] = true;
	} elseif ( '--dry-run' === $arg ) {
		$opcoes['dry-run'] = true;
	} elseif ( '--nativo' === $arg ) {
		$opcoes['nativo'] = true;
	} elseif ( '--help' === $arg || '-h' === $arg ) {
		echo "Uso (Linux):  php tools/setup.php [--fresh] [--skip-import] [--skip-verify] [--dry-run] [--nativo]\n";
		echo "Uso (Windows): setup.bat [mesmas opções]\n\n";
		echo "Instala o WordPress e configura o tema lar-andre-luiz.\n";
		echo "Sem opções, o instalador é idempotente: só faz o que falta.\n\n";
		echo "  --fresh         APAGA o banco e reinstala tudo do zero.\n";
		echo "  --skip-import   pula tools/importar-paginas.php.\n";
		echo "  --skip-verify   pula tools/verificar-elementos.php.\n";
		echo "  --dry-run       mostra os comandos sem executar.\n";
		echo "  --nativo        usa só PHP puro (sem WP-CLI/exec).\n";
		echo "                  Ativado sozinho se o exec() estiver desativado.\n\n";
		echo "Configuração por variáveis de ambiente (padrões do Laragon):\n";
		echo "  LAR_DB_NAME=lar_andre_luiz LAR_DB_USER=root LAR_DB_PASS= LAR_DB_HOST=127.0.0.1\n";
		echo "  LAR_URL=http://sitelar.test LAR_ADMIN_USER=admin\n";
		exit( 0 );
	}
}

/**
 * Verifica se é possível executar subprocessos (WP-CLI).
 *
 * Hospedagens compartilhadas costumam desativar exec().
 *
 * @return bool
 */
function lar_setup_exec_ok() {
	if ( ! function_exists( 'exec' ) ) {
		return false;
	}

	$desativadas = (string) ini_get( 'disable_functions' );

	return false === stripos( $desativadas, 'exec' );
}

$nativo = $opcoes['nativo'] || ! lar_setup_exec_ok();

$cfg = array(
	'db_name'        => getenv( 'LAR_DB_NAME' ) ?: 'lar_andre_luiz',
	'db_user'        => getenv( 'LAR_DB_USER' ) ?: 'root',
	'db_pass'        => getenv( 'LAR_DB_PASS' ) ?: '',
	'db_host'        => getenv( 'LAR_DB_HOST' ) ?: '127.0.0.1',
	'table_prefix'   => getenv( 'LAR_TABLE_PREFIX' ) ?: 'wp_',
	'url'            => rtrim( getenv( 'LAR_URL' ) ?: 'http://sitelar.test', '/' ),
	'title'          => getenv( 'LAR_TITLE' ) ?: 'Lar André Luiz',
	'admin_user'     => getenv( 'LAR_ADMIN_USER' ) ?: 'admin',
	'admin_password' => getenv( 'LAR_ADMIN_PASSWORD' ) ?: '',
	'admin_email'    => getenv( 'LAR_ADMIN_EMAIL' ) ?: 'admin@example.com',
	'origem'         => getenv( 'LAR_ORIGEM' ) ?: LAR_SETUP_ROOT . DIRECTORY_SEPARATOR . 'layout' . DIRECTORY_SEPARATOR . 'index.html',
	'locale'         => getenv( 'LAR_LOCALE' ) ?: 'pt_BR',
);

if ( '' === $cfg['admin_password'] && ! $opcoes['dry-run'] ) {
	$cfg['admin_password'] = substr( bin2hex( random_bytes( 12 ) ), 0, 16 );
	$senha_gerada          = true;
} else {
	$senha_gerada = false;
}

echo "Instalador do ambiente — Lar André Luiz\n";
echo str_repeat( '-', 60 ) . "\n";
echo 'URL:   ' . $cfg['url'] . "\n";
echo 'Banco: ' . $cfg['db_name'] . ' @ ' . $cfg['db_host'] . "\n";
echo 'Origem: ' . $cfg['origem'] . "\n";

if ( $nativo && ! $opcoes['nativo'] ) {
	echo "(exec() desativado: usando modo nativo, só PHP puro)\n";
} elseif ( $nativo ) {
	echo "(modo nativo: só PHP puro, sem WP-CLI)\n";
}

if ( $opcoes['dry-run'] ) {
	echo "(modo dry-run: nada será executado)\n";
}

echo str_repeat( '-', 60 ) . "\n\n";

/**
 * Executa um comando do shell.
 *
 * @param string $cmd Comando.
 * @return array{0:int,1:string} Código de saída e saída capturada.
 */
function lar_setup_run( $cmd ) {
	global $opcoes;

	if ( $opcoes['dry-run'] ) {
		echo "  [dry-run] {$cmd}\n";
		return array( 0, '' );
	}

	$saida = array();
	$codigo = 0;
	exec( $cmd . ' 2>&1', $saida, $codigo );

	return array( $codigo, implode( "\n", $saida ) );
}

/**
 * Monta um comando do WP-CLI.
 *
 * @param string $subcomando Subcomando e argumentos (já escapados).
 * @return string
 */
function lar_setup_wp( $subcomando ) {
	return escapeshellarg( PHP_BINARY )
		. ' ' . escapeshellarg( LAR_SETUP_WPCLI )
		. ' --path=' . escapeshellarg( LAR_SETUP_ROOT )
		. ' ' . $subcomando;
}

/**
 * Baixa uma URL para um arquivo (modo nativo, sem depender de exec).
 *
 * Usa cURL com escrita direta em disco; cai para file_get_contents.
 *
 * @param string $url     URL.
 * @param string $destino Caminho do arquivo de destino.
 * @return bool
 */
function lar_setup_baixar( $url, $destino ) {
	if ( function_exists( 'curl_init' ) ) {
		$fp = fopen( $destino, 'w' );

		if ( ! $fp ) {
			return false;
		}

		$ch = curl_init( $url );
		curl_setopt_array(
			$ch,
			array(
				CURLOPT_FILE           => $fp,
				CURLOPT_FOLLOWLOCATION => true,
				CURLOPT_TIMEOUT        => 300,
				CURLOPT_SSL_VERIFYPEER => ! getenv( 'LAR_SKIP_TLS' ),
			)
		);
		$ok   = curl_exec( $ch );
		$http = (int) curl_getinfo( $ch, CURLINFO_HTTP_CODE );
		curl_close( $ch );
		fclose( $fp );

		return $ok && $http < 400;
	}

	$ctx   = stream_context_create(
		array(
			'http' => array( 'timeout' => 300 ),
			'ssl'  => array( 'verify_peer' => ! getenv( 'LAR_SKIP_TLS' ) ),
		)
	);
	$dados = @file_get_contents( $url, false, $ctx );

	if ( ! $dados ) {
		return false;
	}

	return false !== file_put_contents( $destino, $dados );
}

/**
 * Move mesclando diretórios (a extração não apaga o tema próprio).
 *
 * @param string $de Origem.
 * @param string $para Destino.
 */
function lar_setup_mover_mesclando( $de, $para ) {
	if ( is_dir( $de ) ) {
		if ( ! is_dir( $para ) ) {
			mkdir( $para, 0755, true );
		}

		foreach ( scandir( $de ) as $item ) {
			if ( '.' === $item || '..' === $item ) {
				continue;
			}

			lar_setup_mover_mesclando( $de . '/' . $item, $para . '/' . $item );
		}

		rmdir( $de );
		return;
	}

	$dir = dirname( $para );

	if ( ! is_dir( $dir ) ) {
		mkdir( $dir, 0755, true );
	}

	rename( $de, $para );
}

/**
 * Descobre a URL do zip do WordPress no idioma pedido.
 *
 * @param string $locale Idioma (ex.: pt_BR).
 * @return string
 */
function lar_setup_url_nucleo( $locale ) {
	$api = 'https://api.wordpress.org/core/version-check/1.7/?locale=' . rawurlencode( $locale );
	$tmp = sys_get_temp_dir() . '/lar-versao.json';

	if ( lar_setup_baixar( $api, $tmp ) ) {
		$dados = json_decode( (string) file_get_contents( $tmp ), true );
		@unlink( $tmp );

		if ( isset( $dados['offers'][0]['download'] ) ) {
			return $dados['offers'][0]['download'];
		}
	}

	return 'pt_BR' === $locale
		? 'https://br.wordpress.org/latest-pt_BR.zip'
		: 'https://wordpress.org/latest.zip';
}

/**
 * Baixa e extrai o núcleo do WordPress com PHP puro.
 *
 * @param string $locale Idioma.
 * @return bool
 */
function lar_setup_nucleo_nativo( $locale ) {
	if ( ! class_exists( 'ZipArchive' ) ) {
		echo "  Extensão php-zip ausente: peça ao provedor para ativar.\n";
		return false;
	}

	$url = lar_setup_url_nucleo( $locale );
	echo "  Baixando {$url}...\n";

	$zip = sys_get_temp_dir() . '/lar-wordpress.zip';
	$dir = sys_get_temp_dir() . '/lar-wordpress';

	if ( ! lar_setup_baixar( $url, $zip ) || filesize( $zip ) < 10000000 ) {
		echo "  Download falhou. Confira a conexão com wordpress.org.\n";
		return false;
	}

	$za = new ZipArchive();

	if ( true !== $za->open( $zip ) ) {
		echo "  Zip corrompido.\n";
		return false;
	}

	@mkdir( $dir, 0755, true );
	$za->extractTo( $dir );
	$za->close();
	@unlink( $zip );

	// O zip contém uma pasta "wordpress/"; move o conteúdo para a raiz.
	lar_setup_mover_mesclando( $dir . '/wordpress', LAR_SETUP_ROOT );
	@rmdir( $dir );

	return file_exists( LAR_SETUP_ROOT . '/wp-includes/version.php' );
}

/**
 * Gera o wp-config.php com PHP puro.
 *
 * @param array $cfg Configuração.
 * @return bool
 */
function lar_setup_config_nativo( $cfg ) {
	$salts = @file_get_contents( 'https://api.wordpress.org/secret-key/1.1/salt/' );

	if ( ! $salts || false === strpos( $salts, 'AUTH_KEY' ) ) {
		$salts = '';

		foreach ( array( 'AUTH_KEY', 'SECURE_AUTH_KEY', 'LOGGED_IN_KEY', 'NONCE_KEY', 'AUTH_SALT', 'SECURE_AUTH_SALT', 'LOGGED_IN_SALT', 'NONCE_SALT' ) as $chave ) {
			$salts .= "define( '{$chave}', '" . bin2hex( random_bytes( 32 ) ) . "' );\n";
		}
	}

	$modelo = <<<'PHP'
<?php
define( 'DB_NAME', '__DB_NAME__' );
define( 'DB_USER', '__DB_USER__' );
define( 'DB_PASSWORD', '__DB_PASS__' );
define( 'DB_HOST', '__DB_HOST__' );
define( 'DB_CHARSET', 'utf8mb4' );
define( 'DB_COLLATE', '' );

__SALTS__

$table_prefix = '__PREFIX__';

define( 'WP_DEBUG', false );

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

require_once ABSPATH . 'wp-settings.php';
PHP;

	$conteudo = str_replace(
		array( '__DB_NAME__', '__DB_USER__', '__DB_PASS__', '__DB_HOST__', '__SALTS__', '__PREFIX__' ),
		array(
			addcslashes( $cfg['db_name'], "'\\" ),
			addcslashes( $cfg['db_user'], "'\\" ),
			addcslashes( $cfg['db_pass'], "'\\" ),
			addcslashes( $cfg['db_host'], "'\\" ),
			$salts,
			preg_replace( '/[^0-9A-Za-z_]/', '', $cfg['table_prefix'] ),
		),
		$modelo
	);

	if ( ! file_put_contents( LAR_SETUP_ROOT . '/wp-config.php', $conteudo ) ) {
		echo "  Sem permissão de escrita para wp-config.php.\n";
		return false;
	}

	return true;
}

/**
 * Cria o banco com PHP puro (se ainda não existir).
 *
 * @param array $cfg Configuração.
 * @return bool
 */
function lar_setup_banco_nativo( $cfg ) {
	$link = @mysqli_connect( $cfg['db_host'], $cfg['db_user'], $cfg['db_pass'] );

	if ( ! $link ) {
		return false;
	}

	$nome = str_replace( '`', '', $cfg['db_name'] );
	$ok   = mysqli_query( $link, "CREATE DATABASE IF NOT EXISTS `{$nome}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci" );
	mysqli_close( $link );

	return (bool) $ok;
}

/**
 * Carrega o WordPress no próprio processo (modo nativo).
 */
function lar_setup_wp_load() {
	if ( ! defined( 'ABSPATH' ) ) {
		define( 'WP_USE_THEMES', false );
		require_once LAR_SETUP_ROOT . '/wp-load.php';
	}
}

/**
 * Verifica se o WordPress já está instalado (modo nativo).
 *
 * @param array $cfg Configuração.
 * @return bool
 */
function lar_setup_instalado_nativo( $cfg ) {
	if ( ! file_exists( LAR_SETUP_ROOT . '/wp-config.php' ) ) {
		return false;
	}

	$link = @mysqli_connect( $cfg['db_host'], $cfg['db_user'], $cfg['db_pass'], $cfg['db_name'] );

	if ( ! $link ) {
		return false;
	}

	$prefix = str_replace( '`', '', preg_replace( '/[^0-9A-Za-z_]/', '', $cfg['table_prefix'] ) );
	$res    = @mysqli_query( $link, "SELECT option_value FROM `{$prefix}options` WHERE option_name = 'siteurl' LIMIT 1" );
	$row    = $res ? mysqli_fetch_row( $res ) : false;
	mysqli_close( $link );

	return $row && '' !== $row[0];
}

/**
 * Apaga as tabelas do WordPress (modo nativo, --fresh).
 *
 * @param array $cfg Configuração.
 * @return bool
 */
function lar_setup_limpar_banco( $cfg ) {
	$link = @mysqli_connect( $cfg['db_host'], $cfg['db_user'], $cfg['db_pass'], $cfg['db_name'] );

	if ( ! $link ) {
		return false;
	}

	$prefix = str_replace( '`', '', preg_replace( '/[^0-9A-Za-z_]/', '', $cfg['table_prefix'] ) );
	$res    = mysqli_query( $link, "SHOW TABLES LIKE '{$prefix}%'" );

	if ( $res ) {
		mysqli_query( $link, 'SET FOREIGN_KEY_CHECKS = 0' );

		while ( $row = mysqli_fetch_row( $res ) ) {
			mysqli_query( $link, "DROP TABLE `{$row[0]}`" );
		}

		mysqli_query( $link, 'SET FOREIGN_KEY_CHECKS = 1' );
	}

	mysqli_close( $link );
	return true;
}

/**
 * Instala o WordPress com PHP puro.
 *
 * @param array $cfg Configuração.
 * @return bool
 */
function lar_setup_install_nativo( $cfg ) {
	define( 'WP_INSTALLING', true );
	lar_setup_wp_load();

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	$resultado = wp_install(
		$cfg['title'],
		$cfg['admin_user'],
		$cfg['admin_email'],
		true,
		'',
		$cfg['admin_password'],
		$cfg['locale']
	);

	if ( $resultado && ! is_wp_error( $resultado ) ) {
		update_option( 'WPLANG', $cfg['locale'] );
		return true;
	}

	return false;
}

/**
 * Imprime o resumo final com acessos.
 *
 * @param array $cfg Configuração.
 * @param bool  $senha_gerada Se a senha foi gerada.
 */
function lar_setup_resumo( $cfg, $senha_gerada ) {
	echo str_repeat( '=', 60 ) . "\n";
	echo "Ambiente pronto!\n";
	echo '  Site:  ' . $cfg['url'] . "/\n";
	echo '  Admin: ' . $cfg['url'] . "/wp-admin/\n";
	echo '  Usuário: ' . $cfg['admin_user'] . "\n";

	if ( $senha_gerada ) {
		echo '  Senha gerada: ' . $cfg['admin_password'] . " (troque no perfil)\n";
	}

	echo str_repeat( '=', 60 ) . "\n";
}

/**
 * Etapa com título; aborta em caso de erro.
 *
 * @param string   $titulo Título.
 * @param callable $fn     Função que devolve true em caso de sucesso.
 */
function lar_setup_etapa( $titulo, $fn ) {	echo "== {$titulo} ==\n";
	$ok = $fn();
	echo ( $ok ? "  [ok]\n\n" : "  [FALHA]\n\n" );

	if ( ! $ok ) {
		exit( 1 );
	}
}

/* -------------------------------------------------------------------------
 * 1. Pré-requisitos
 * ---------------------------------------------------------------------- */

lar_setup_etapa(
	'1/9 Pré-requisitos',
	function () use ( $cfg ) {
		if ( version_compare( PHP_VERSION, '7.4.0', '<' ) ) {
			echo '  PHP 7.4+ necessário (atual: ' . PHP_VERSION . ").\n";
			return false;
		}

		foreach ( array( 'mysqli', 'curl', 'zip' ) as $ext ) {
			if ( ! extension_loaded( $ext ) ) {
				echo "  Extensão PHP ausente: {$ext}.\n";
				return false;
			}
		}

		if ( ! file_exists( $cfg['origem'] ) ) {
			echo '  Arquivo de origem não encontrado: ' . $cfg['origem'] . "\n";
			return false;
		}

		echo '  PHP ' . PHP_VERSION . " + origem OK.\n";
		return true;
	}
);

/* -------------------------------------------------------------------------
 * 2. Banco de dados acessível
 * ---------------------------------------------------------------------- */

lar_setup_etapa(
	'2/9 Banco de dados',
	function () use ( $cfg, $opcoes ) {
		if ( $opcoes['dry-run'] ) {
			echo "  [dry-run] mysqli_connect({$cfg['db_host']}, {$cfg['db_user']}, ***).\n";
			return true;
		}

		$link = @mysqli_connect( $cfg['db_host'], $cfg['db_user'], $cfg['db_pass'] );

		if ( ! $link ) {
			echo '  Não conectou ao MySQL: ' . mysqli_connect_error() . "\n";
			echo "  Suba o MySQL (Laragon ou painel da hospedagem) e confira LAR_DB_*.\n";
			return false;
		}

		mysqli_close( $link );
		echo "  MySQL acessível.\n";
		return true;
	}
);

/* -------------------------------------------------------------------------
 * 3. WP-CLI (dispensado no modo nativo)
 * ---------------------------------------------------------------------- */

if ( $nativo ) {
	echo "== 3/9 WP-CLI ==\n  (modo nativo: dispensado)\n\n";
} else {
	lar_setup_etapa(
		'3/9 WP-CLI',
	function () use ( $opcoes ) {
		if ( file_exists( LAR_SETUP_WPCLI ) ) {
			echo "  Já existe: tools/wp-cli.phar.\n";
			return true;
		}

		$url = 'https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar';
		echo "  Baixando WP-CLI...\n";

		if ( $opcoes['dry-run'] ) {
			echo "  [dry-run] download de {$url}.\n";
			return true;
		}

		$ctx  = stream_context_create(
			array(
				'http' => array( 'timeout' => 120 ),
				'ssl'  => array( 'verify_peer' => ! getenv( 'LAR_SKIP_TLS' ) ),
			)
		);
		$phar = @file_get_contents( $url, false, $ctx );

		if ( ! $phar || strlen( $phar ) < 1000000 ) {
			echo "  Download falhou. Baixe manualmente em:\n  {$url}\n  e salve como tools/wp-cli.phar.\n";
			return false;
		}

		file_put_contents( LAR_SETUP_WPCLI, $phar );
		echo '  Salvo (' . number_format( strlen( $phar ) / 1024, 0, ',', '.' ) . " KB).\n";
		return true;
		}
	);
}

/* -------------------------------------------------------------------------
 * 4. Núcleo do WordPress
 * ---------------------------------------------------------------------- */

lar_setup_etapa(
	'4/9 Núcleo do WordPress',
	function () use ( $cfg, $nativo, $opcoes ) {
		if ( file_exists( LAR_SETUP_ROOT . '/wp-includes/version.php' ) ) {
			echo "  Núcleo já presente.\n";
			return true;
		}

		if ( $nativo ) {
			if ( $opcoes['dry-run'] ) {
				echo "  [dry-run] download + extração do zip pt_BR com PHP puro.\n";
				return true;
			}

			return lar_setup_nucleo_nativo( $cfg['locale'] );
		}

		list( $codigo, $saida ) = lar_setup_run(
			lar_setup_wp(
				'core download --locale=' . escapeshellarg( $cfg['locale'] ) . ' --skip-content'
			)
		);

		if ( 0 !== $codigo ) {
			echo "  {$saida}\n";
			return false;
		}

		return true;
	}
);

/* -------------------------------------------------------------------------
 * 5. wp-config.php + banco
 * ---------------------------------------------------------------------- */

lar_setup_etapa(
	'5/9 Configuração e banco',
	function () use ( $cfg, $nativo, $opcoes ) {
		if ( $nativo ) {
			if ( ! file_exists( LAR_SETUP_ROOT . '/wp-config.php' ) ) {
				if ( $opcoes['dry-run'] ) {
					echo "  [dry-run] gerar wp-config.php + CREATE DATABASE.\n";
					return true;
				}

				if ( ! lar_setup_config_nativo( $cfg ) ) {
					return false;
				}
			} else {
				echo "  wp-config.php já existe (mantido).\n";
			}

			if ( $opcoes['dry-run'] ) {
				return true;
			}

			echo lar_setup_banco_nativo( $cfg )
				? "  Banco '{$cfg['db_name']}' pronto.\n"
				: "  (aviso: não foi possível criar o banco — confira LAR_DB_*)\n";

			return true;
		}

		if ( ! file_exists( LAR_SETUP_ROOT . '/wp-config.php' ) ) {
			list( $codigo, $saida ) = lar_setup_run(
				lar_setup_wp(
					'config create'
					. ' --dbname=' . escapeshellarg( $cfg['db_name'] )
					. ' --dbuser=' . escapeshellarg( $cfg['db_user'] )
					. ' --dbpass=' . escapeshellarg( $cfg['db_pass'] )
					. ' --dbhost=' . escapeshellarg( $cfg['db_host'] )
					. ' --dbprefix=' . escapeshellarg( $cfg['table_prefix'] )
					. ' --skip-check'
				)
			);

			if ( 0 !== $codigo ) {
				echo "  {$saida}\n";
				return false;
			}
		} else {
			echo "  wp-config.php já existe (mantido).\n";
		}

		list( $codigo ) = lar_setup_run( lar_setup_wp( 'db create' ) );
		// Código 0 = criou; outro código = já existia. Ambos servem.
		echo "  Banco '{$cfg['db_name']}' pronto.\n";
		return true;
	}
);

/* -------------------------------------------------------------------------
 * 6. Instalação
 * ---------------------------------------------------------------------- */

lar_setup_etapa(
	'6/9 Instalação do WordPress',
	function () use ( $cfg, $opcoes, $nativo ) {
		if ( $nativo ) {
			if ( $opcoes['dry-run'] ) {
				echo "  [dry-run] wp_install() no próprio processo.\n";
				return true;
			}

			if ( lar_setup_instalado_nativo( $cfg ) && ! $opcoes['fresh'] ) {
				echo "  WordPress já instalado.\n";
				return true;
			}

			if ( $opcoes['fresh'] ) {
				echo "  --fresh: limpando o banco...\n";

				if ( ! lar_setup_limpar_banco( $cfg ) ) {
					return false;
				}
			}

			return lar_setup_install_nativo( $cfg );
		}

		list( $instalado ) = lar_setup_run( lar_setup_wp( 'core is-installed' ) );

		if ( 0 === $instalado && ! $opcoes['fresh'] ) {
			echo "  WordPress já instalado.\n";
			return true;
		}

		if ( $opcoes['fresh'] ) {
			echo "  --fresh: limpando o banco...\n";
			list( $codigo, $saida ) = lar_setup_run( lar_setup_wp( 'db reset --yes' ) );

			if ( 0 !== $codigo ) {
				echo "  {$saida}\n";
				return false;
			}
		}

		list( $codigo, $saida ) = lar_setup_run(
			lar_setup_wp(
				'core install'
				. ' --url=' . escapeshellarg( $cfg['url'] )
				. ' --title=' . escapeshellarg( $cfg['title'] )
				. ' --admin_user=' . escapeshellarg( $cfg['admin_user'] )
				. ' --admin_password=' . escapeshellarg( $cfg['admin_password'] )
				. ' --admin_email=' . escapeshellarg( $cfg['admin_email'] )
				. ' --skip-email'
			)
		);

		if ( 0 !== $codigo ) {
			echo "  {$saida}\n";
			return false;
		}

		return true;
	}
);

/* -------------------------------------------------------------------------
 * 7. Tema
 * ---------------------------------------------------------------------- */

lar_setup_etapa(
	'7/9 Tema lar-andre-luiz',
	function () use ( $nativo, $opcoes ) {
		if ( ! is_dir( LAR_SETUP_ROOT . '/wp-content/themes/lar-andre-luiz' ) ) {
			echo "  Tema não encontrado em wp-content/themes/lar-andre-luiz.\n";
			return false;
		}

		if ( $nativo ) {
			if ( $opcoes['dry-run'] ) {
				echo "  [dry-run] switch_theme('lar-andre-luiz').\n";
				return true;
			}

			lar_setup_wp_load();
			switch_theme( 'lar-andre-luiz' );

			if ( 'lar-andre-luiz' === get_stylesheet() ) {
				echo "  Tema ativado.\n";
				return true;
			}

			echo "  Falha ao ativar o tema.\n";
			return false;
		}

		list( $codigo, $saida ) = lar_setup_run( lar_setup_wp( 'theme activate lar-andre-luiz' ) );

		if ( 0 !== $codigo ) {
			echo "  {$saida}\n";
			return false;
		}

		return true;
	}
);

/* -------------------------------------------------------------------------
 * 8. Conteúdo (páginas + ícone)
 * ---------------------------------------------------------------------- */

if ( ! $opcoes['skip-import'] ) {
	lar_setup_etapa(
		'8/9 Importação das páginas',
		function () use ( $cfg, $nativo, $opcoes ) {
			putenv( 'LAR_ORIGEM=' . $cfg['origem'] );
			putenv( 'LAR_URL=' . $cfg['url'] );

			if ( $nativo ) {
				if ( $opcoes['dry-run'] ) {
					echo "  [dry-run] incluir tools/importar-paginas.php + configurar-icone.php.\n";
					return true;
				}

				// Roda no próprio processo: sem exec(), sem subprocesso.
				require LAR_SETUP_ROOT . '/tools/importar-paginas.php';
				require LAR_SETUP_ROOT . '/tools/configurar-icone.php';
				return true;
			}

			list( $codigo, $saida ) = lar_setup_run(
				escapeshellarg( PHP_BINARY )
				. ' ' . escapeshellarg( LAR_SETUP_ROOT . '/tools/importar-paginas.php' )
			);

			echo $saida ? "  {$saida}\n" : '';

			if ( 0 !== $codigo ) {
				return false;
			}

			list( $codigo2, $saida2 ) = lar_setup_run(
				escapeshellarg( PHP_BINARY )
				. ' ' . escapeshellarg( LAR_SETUP_ROOT . '/tools/configurar-icone.php' )
			);

			if ( 0 !== $codigo2 ) {
				echo "  (aviso: ícone não configurado)\n  {$saida2}\n";
			}

			return true;
		}
	);
} else {
	echo "== 8/9 Importação das páginas ==\n  (pulada por --skip-import)\n\n";
}

/* -------------------------------------------------------------------------
 * 9. Verificação
 * ---------------------------------------------------------------------- */

if ( ! $opcoes['skip-verify'] ) {
	echo "== 9/9 Verificação ==\n";
	putenv( 'LAR_URL=' . $cfg['url'] );

	if ( $nativo ) {
		if ( $opcoes['dry-run'] ) {
			echo "  [dry-run] incluir tools/verificar-elementos.php.\n";
		} else {
			// O resumo sai antes: a verificação termina com exit().
			lar_setup_resumo( $cfg, $senha_gerada );
			require LAR_SETUP_ROOT . '/tools/verificar-elementos.php';
		}
	} else {
		list( $codigo, $saida ) = lar_setup_run(
			escapeshellarg( PHP_BINARY )
			. ' ' . escapeshellarg( LAR_SETUP_ROOT . '/tools/verificar-elementos.php' )
		);

		echo $saida ? "  {$saida}\n" : '';
		echo ( 0 === $codigo ? "  [ok]\n\n" : "  [aviso: há falhas acima — veja o relatório]\n\n" );
	}
} else {
	echo "== 9/9 Verificação ==\n  (pulada por --skip-verify)\n\n";
}

if ( ! ( $nativo && ! $opcoes['skip-verify'] && ! $opcoes['dry-run'] ) ) {
	lar_setup_resumo( $cfg, $senha_gerada );
}
