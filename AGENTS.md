# AGENTS — Regras do projeto (Lar André Luiz)

## 1. Pasta `layout/` — SOMENTE LEITURA (referência)

- A pasta `layout/` contém o layout original de referência (HTML/CSS/JS estático).
- **NUNCA altere, crie, mova ou exclua nada dentro de `layout/`.**
- Use `layout/` apenas como **referência visual e de conteúdo** para conferir
  se o tema WordPress está fiel ao desenho original
  (ex.: os dois cards após o hero — "Quadra dos sonhos" e "Instituto ACES").
- Qualquer correção ou evolução deve ser feita **fora** de `layout/`:
  - código do tema: `wp-content/themes/lar-andre-luiz/`
  - conteúdo das páginas: via admin do WordPress ou `tools/importar-paginas.php`
    (o importador lê o HTML de origem indicado pela variável `LAR_ORIGEM`,
    nunca escreve em `layout/`).

## 2. Onde mexer

- Tema ativo: `wp-content/themes/lar-andre-luiz/`
  (`header.php`, `front-page.php`, `functions.php`, `assets/css/style.css`,
  `assets/js/main.js`, `template-parts/`, `inc/`).
- Scripts utilitários: `tools/` (importação, verificação, diagnóstico).
- Não versionar núcleo do WP nem uploads (ver `.gitignore`).

## 3. Conversões obrigatórias do layout para o WordPress

- `onclick="showPage('x')"` vira `<a href="permalink">` real (nunca `href="#"`).
- Botões de ação (ex.: "Doe aqui") abrem modais via `data-lar-open`,
  sem `onclick` inline.
- `href="#"` sem destino vira texto/`<span>`, não link.

## 4. Ambiente local em 1 comando

- Windows: `setup.bat` (localiza o PHP do Laragon sozinho).
- Linux / hospedagem (só PHP + composer, sem npm): `./setup.sh`
  ou direto `php tools/setup.php`.
- Lógica: `tools/setup.php` — idempotente (só faz o que falta).
  Baixa o WP-CLI e o núcleo se preciso, cria `wp-config.php` + banco,
  instala o WP, ativa o tema, importa as páginas de `layout/index.html`
  (leitura!), configura o ícone e verifica o resultado.
- `--nativo`: PHP puro, sem WP-CLI e sem `exec()` — para hospedagem
  compartilhada com funções desabilitadas. Ativado sozinho se `exec()`
  estiver indisponível.
- Opções: `--fresh` (APAGA o banco e reinstala — nunca em produção),
  `--skip-import`, `--skip-verify`, `--dry-run`, `--help`.
- Config por ambiente: `LAR_DB_NAME`, `LAR_DB_USER`, `LAR_DB_PASS`,
  `LAR_DB_HOST`, `LAR_URL`, `LAR_TITLE`, `LAR_ADMIN_USER`,
  `LAR_ADMIN_PASSWORD`, `LAR_ADMIN_EMAIL`, `LAR_ORIGEM`, `LAR_LOCALE`.
- Gerado e ignorado pelo git: `tools/wp-cli.phar`.
