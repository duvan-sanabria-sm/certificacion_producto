#!/bin/sh
set -eu

echo '=== uname -a ==='
uname -a

echo '=== php -v ==='
php -v

echo '=== php -m ==='
php -m

echo '=== php runtime limits ==='
php -i | grep -E 'disable_functions|open_basedir|max_execution_time|max_input_vars|max_file_uploads|upload_tmp_dir|date.timezone|session.save_path'

echo '=== python ==='
"${SERVIMETERS_PYTHON_PATH:-/opt/servimeters/venv/bin/python}" --version

echo '=== pip freeze ==='
"${SERVIMETERS_PYTHON_PATH:-/opt/servimeters/venv/bin/python}" -m pip freeze

echo '=== psql ==='
if command -v psql >/dev/null 2>&1; then psql --version; else echo 'No instalado en el contenedor; la aplicación usa PDO.'; fi

echo '=== composer ==='
if command -v composer >/dev/null 2>&1; then composer --version; else echo 'No instalado; el proyecto no usa dependencias Composer.'; fi
