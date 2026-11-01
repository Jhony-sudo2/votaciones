#!/usr/bin/env sh
set -eu
cd "$(dirname "$0")/.."
if [ ! -f .env ]; then
  if ! command -v openssl >/dev/null 2>&1; then
    printf 'Necesitas openssl o crear manualmente .env desde .env.example.\n' >&2
    exit 1
  fi
  DBPASS=$(openssl rand -hex 24)
  ROOTPASS=$(openssl rand -hex 24)
  ADMINPASS=$(openssl rand -hex 16)
  umask 077
  cat > .env <<EOF
DB_HOST=db
DB_PORT=3306
DB_NAME=sistema_votacion
DB_USER=votacion_user
DB_PASSWORD=$DBPASS
DB_ROOT_PASSWORD=$ROOTPASS
ADMIN_USER=admin
ADMIN_PASSWORD=$ADMINPASS
SMTP_HOST=
SMTP_PORT=587
SMTP_ENCRYPTION=tls
SMTP_USER=
SMTP_PASSWORD=
SMTP_FROM=
EOF
  printf 'Credenciales iniciales (guárdalas de forma privada):\n'
  printf 'Operador: admin\nContraseña: %s\n' "$ADMINPASS"
else
  printf 'Se conserva el archivo .env existente. Busca ADMIN_USER/ADMIN_PASSWORD en ese archivo.\n'
fi
if ! command -v docker >/dev/null 2>&1; then
  printf 'Docker no está disponible. Configura Docker Desktop / Docker Engine.\n' >&2
  exit 1
fi
docker compose up --build -d
printf '\nAbre http://127.0.0.1:8080/\n'
