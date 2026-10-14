@echo off
rem Instalador do ambiente local — Lar André Luiz.
rem Uso: setup.bat [--fresh] [--skip-import] [--skip-verify] [--dry-run]
setlocal

rem Localiza o PHP: primeiro no PATH, depois nas pastas do Laragon.
where php >nul 2>nul
if %errorlevel%==0 (
  set "PHPBIN=php"
) else (
  set "PHPBIN="
  for /d %%D in ("C:\laragon\bin\php\php-*") do set "PHPBIN=%%D\php.exe"
)

if not defined PHPBIN (
  echo [setup] PHP nao encontrado. Instale o Laragon ou ajuste o PATH.
  exit /b 1
)

"%PHPBIN%" "%~dp0tools\setup.php" %*
