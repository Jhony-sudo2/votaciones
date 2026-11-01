$ErrorActionPreference = 'Stop'
Set-Location (Resolve-Path (Join-Path $PSScriptRoot '..'))
function New-Secret {
  $bytes = New-Object byte[] 24
  $rng = [System.Security.Cryptography.RandomNumberGenerator]::Create()
  try { $rng.GetBytes($bytes) } finally { $rng.Dispose() }
  return [Convert]::ToBase64String($bytes).TrimEnd('=').Replace('+', '-').Replace('/', '_')
}
if (-not (Test-Path '.env')) {
  $dbPwd = New-Secret
  $rootPwd = New-Secret
  $adminPwd = New-Secret
  $envText = @"
DB_HOST=db
DB_PORT=3306
DB_NAME=sistema_votacion
DB_USER=votacion_user
DB_PASSWORD=$dbPwd
DB_ROOT_PASSWORD=$rootPwd
ADMIN_USER=admin
ADMIN_PASSWORD=$adminPwd
SMTP_HOST=
SMTP_PORT=587
SMTP_ENCRYPTION=tls
SMTP_USER=
SMTP_PASSWORD=
SMTP_FROM=
"@
  [System.IO.File]::WriteAllText((Join-Path (Get-Location).Path '.env'), $envText + "`n", (New-Object System.Text.UTF8Encoding($false)))
  Write-Host 'Credenciales iniciales (guárdalas de forma privada):'
  Write-Host "Operador: admin"
  Write-Host "Contraseña: $adminPwd"
} else {
  Write-Host 'El archivo .env ya existe. No se modifican las credenciales actuales.'
}
docker compose up --build -d
if ($LASTEXITCODE -ne 0) { throw 'No se pudo levantar Docker Compose.' }
Write-Host 'Abre http://127.0.0.1:8080/'
