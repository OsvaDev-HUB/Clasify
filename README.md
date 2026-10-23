# Clasify

Avance de desarrollo, semanas 1 y 2 (12 al 23 de octubre).

## 1. Qué se necesita

- XAMPP con PHP 8.2 o superior (el código usa `mysqli_execute_query`, que existe desde PHP 8.2).
- Un navegador (Chrome, Edge o Firefox).

## 2. Cómo instalarlo

1. Copiar la carpeta `clasify` dentro de `C:\xampp\htdocs\`
   (debe quedar `C:\xampp\htdocs\clasify\index.php`).
2. Abrir el panel de XAMPP y encender Apache y MySQL.
3. Entrar a http://localhost/phpmyadmin, ir a la pestaña "Importar",
   elegir el archivo `clasify\bd\clasify.sql` y presionar "Importar".
   (El script crea la base de datos `clasify` con todas las tablas y datos de prueba.
   Si la base ya existía, la borra y la vuelve a crear.)
4. Abrir http://localhost/clasify/

Si MySQL tiene contraseña o la carpeta tiene otro nombre, se cambia en `config\conexion.php`.

## 3. Usuarios de prueba

La contraseña de todos es: `clasify123`

| Rol            | RUT        | Nombre                                           |
|----------------|------------|--------------------------------------------------|
| Administrativo | 11111111-1 | Marcela Rojas Pinto                              |
| Profesor       | 15234567-4 | Jorge Paredes Soto (jefe de 1° Medio A)          |
| Profesor       | 13876543-1 | Carolina Soto Fuentes (jefa de 2° Medio A)       |
| Profesor       | 16456789-3 | Luis Vera Cárcamo                                |
| Apoderado      | 12987654-9 | Patricia Muñoz Leal (apoderada de Tomás y Sofía) |
| Apoderado      | 14345678-1 | Ricardo Álvarez Mella                            |
| Apoderado      | 17654321-3 | Claudia Herrera Ruiz (apoderada de Martina y Diego) |
| Apoderado      | 18234567-9 | Andrea Vargas Toledo                             |
| Alumno         | 22123456-1 | Sofía Fuentes Muñoz (1° Medio A, tiene notas de 2025) |
| Alumno         | 21345678-4 | Tomás Fuentes Muñoz (2° Medio A, tiene notas de 2025) |

El RUT se puede escribir con o sin puntos.

## 4. Organización de las carpetas

```
clasify/
  index.php           Inicio de sesión
  inicio.php          Página de bienvenida según el rol
  cambiar_clave.php   Cambiar la contraseña propia
  logout.php          Cerrar sesión
  config/             Conexión a la base de datos
  includes/           Partes que se repiten: sesión, funciones, cabecera, pie y tabla de notas
  css/                Estilos
  admin/              Páginas del administrativo
  profesor/           Páginas del profesor
  alumno/             Páginas del alumno
  apoderado/          Páginas del apoderado
  bd/                 Script SQL y modelo de la base de datos (draw.io y PNG)
```

## 5. Qué se hizo en cada semana

### Semana 1 (12 al 16 de octubre)

- Base de datos: modelo en draw.io (`bd/07_base_de_datos.drawio`) y script
  `bd/clasify.sql` con las 14 tablas del sistema y datos de prueba.
  Se crearon todas las tablas desde el principio, aunque algunas
  (asistencia, cuotas, pagos, etc.) se usan en las semanas siguientes.
- Estructura del proyecto (carpetas por rol, cabecera y menú comunes).
- Inicio de sesión con RUT y contraseña (RF-01).
- Menú y permisos según el rol, revisados en el servidor (RF-02, RNF-02).
- Cambio de contraseña (parte de RF-03; la recuperación por correo va en la semana 3).
- Contraseñas encriptadas con `password_hash` (RNF-01).
- Cierre de sesión a los 30 minutos sin actividad (RNF-03).
- Crear, modificar, desactivar y activar usuarios (RF-04), con validación del RUT.

### Semana 2 (19 al 23 de octubre)

- Años académicos y cursos con profesor jefe (RF-05).
- Asignaturas, indicando si influyen en la promoción (RF-06).
- Profesores de cada asignatura por curso (RF-07).
- Matrícula de alumnos en un curso, uno por año (RF-08, RN-08).
- Apoderado de cada alumno, un apoderado puede tener varios hijos (RF-09).
- Ver el curso del alumno y los cursos del profesor (RF-10).
- Notas: el profesor ingresa una evaluación para todo el curso en una sola
  pantalla (RF-11, RNF-06) y puede modificar una nota (RF-12).
  Solo acepta notas entre 1,0 y 7,0 y avisa si hay un error (RNF-07).
- Promedios por semestre, anual y general (RF-13). El promedio general
  no cuenta Religión ni Orientación (RN-06).
- El alumno ve sus notas de este año y de años anteriores (RF-14).
- El apoderado ve las notas de sus hijos y no puede ver las de otros
  alumnos (RF-15, RNF-02).

## 6. Pruebas realizadas

Se probó cada pantalla con los cuatro roles. Entre otras cosas se revisó que:

- No se pueda entrar con una contraseña incorrecta ni con una cuenta desactivada.
- Un profesor, alumno o apoderado no pueda abrir páginas de otro rol.
- No se acepte un RUT con dígito verificador incorrecto ni un RUT repetido.
- No se pueda crear un alumno sin apoderado.
- No se pueda crear dos veces el mismo curso en un año.
- No se pueda dejar una asignatura sin profesor si ya tiene notas.
- No se acepten notas fuera del rango 1,0 a 7,0.
- Un profesor no pueda modificar notas de asignaturas que no hace.
- Un apoderado no pueda ver las notas de un alumno que no es su hijo.

## 7. Lo que sigue (semana 3)

Asistencia, mensualidades, anotaciones y el inicio de los avisos por correo.
