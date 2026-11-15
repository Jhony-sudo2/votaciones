# Credenciales y correo de aprobación

Las nuevas aprobaciones generan un usuario `votante_<número de registro>`: por ejemplo `votante_1`, `votante_2` o `votante_25`. El número es el ID único que MySQL asignó al registrarse; no depende del orden de aprobación. Puede haber saltos si hay registros rechazados o pendientes. No se usa `MAX + 1`, por lo que dos operadores aprobando personas distintas no compiten por el mismo número. Las cuentas ya aprobadas conservan su usuario y contraseña; no se renombra ni se reenvía ninguna cuenta existente.

Las contraseñas nuevas tienen 16 caracteres aleatorios elegidos con `random_int`, solo mayúsculas y números. No incluyen I, O, 0 ni 1. El alfabeto tiene 32 caracteres: 16 elecciones independientes proporcionan 80 bits de aleatoriedad. Se conserva `password_hash` para almacenarlas y `password_verify` para comprobarlas. El usuario es predecible; la contraseña es independiente del número de registro, el nombre y el DPI.

El correo incluye el nombre de la persona, usuario, contraseña, botón de acceso y la apertura de votación cuando está configurada. Tiene versiones HTML y texto plano. La contraseña no aparece en el asunto ni en la URL y no se envían datos del DPI.

El botón apunta a `/login_votacion.php` en el sitio oficial:

```ini
APP_URL=https://votacioneslibertad-dehje4gfd6crc2e9.mexicocentral-01.azurewebsites.net
```

Este sitio se usa también como valor predeterminado si no se configura `APP_URL`. Para cambiarlo, establece una URL base HTTPS sin parámetros ni fragmentos. En Azure configura el valor sin comillas. Los scripts de instalación incorporan la variable a los archivos `.env` nuevos. Se conserva el comportamiento SMTP y la visualización de las credenciales una sola vez en el panel del operador si el correo no se puede enviar.

Para aplicar el código con Docker:

```sh
docker compose up -d --build web
```

Pruebas locales sin enviar correos ni acceder a MySQL:

```sh
php tests/credentials_email.php
php tests/registration_window.php
```
