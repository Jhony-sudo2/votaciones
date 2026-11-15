# Horario de registro

Define en `.env` (o en las variables de entorno del servicio web) la apertura y el cierre del registro. Ambos valores usan la hora de **America/Guatemala** y el formato `AAAA-MM-DD HH:MM:SS`.

Ejemplo ilustrativo; sustituye las fechas por las de tu jornada:

```ini
REGISTRO_APERTURA="2027-01-10 08:00:00"
REGISTRO_CIERRE="2027-01-10 18:00:00"
```

Con Docker, reconstruye la imagen para incorporar el código y recrea el servicio después de configurar los horarios:

```sh
docker compose up -d --build web
```

Si solo cambias los horarios posteriormente, basta con `docker compose up -d --force-recreate web`. En un despliegue con variables de entorno, actualízalas y reinicia el servicio según la plataforma.

- Antes de la apertura, el registro muestra «Pendiente» y no acepta solicitudes.
- Desde la apertura exacta, muestra «Abierto» y permite registrarse.
- Desde el cierre exacto, muestra «Cerrado» y rechaza nuevos registros.
- Si faltan valores, tienen formato inválido o el cierre no es posterior a la apertura, muestra «No disponible» y no acepta registros.

El inicio muestra el estado y los horarios, y ofrece el enlace de registro únicamente cuando está abierto. `registro.php` comprueba el horario también en el servidor: enviar un formulario antiguo o acceder directamente a la ruta no permite saltarse el cierre. Los POST fuera de horario responden con HTTP 403.

La aceptación depende de la hora del servidor en la última comprobación previa al INSERT. Una solicitud aceptada antes del cierre puede terminar de guardarse y confirmarse después. Si una carga o una conexión demorada cruza el cierre antes de esa comprobación, se rechaza el registro y se elimina la imagen que se haya movido al almacenamiento.

La votación tiene una apertura independiente y no tiene cierre automático. Configura `VOTACION_APERTURA` en `.env` o en el entorno del servicio, con el mismo formato y zona horaria. Si no está configurada o es inválida, no se permite votar. Desde el instante de apertura se permite votar, incluso después del cierre del registro. El inicio muestra también esta apertura.

El acceso de votantes y los paneles de operadores y resultados siguen disponibles. Antes de la apertura, un votante puede iniciar sesión, pero no ve la papeleta y los POST a `votacion.php` son rechazados con HTTP 403. Los registros pendientes se pueden seguir aprobando o rechazando después del cierre.

Pruebas del horario y de las páginas, sin MySQL ni envíos de formularios reales:

```sh
php tests/registration_window.php
```
