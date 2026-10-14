#!/bin/sh
# Instalador do ambiente local — Lar André Luiz (Linux / hospedagem compartilhada).
# Uso: ./setup.sh [--fresh] [--skip-import] [--skip-verify] [--dry-run] [--nativo]
# Só precisa de PHP 7.4+ com mysqli, curl e zip. Sem npm, sem WP-CLI, sem composer.
cd "$(dirname "$0")" || exit 1
exec php tools/setup.php "$@"
