<?php
// Incluir el archivo de configuración de la base de datos
require_once('C:\xampp\htdocs\gestortasks\app\config\db.php');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GestorTasks - Panel de control</title>
<style>
        /* Estilizar el contenido de la página */
        :root {
            --color-primario: #3498db; /* Color principal de la marca */
            --color-secundario: #2c3e50; /* Color oscuro para texto y acentos */
            --color-acento: #e74c3c; /* Color de resaltado */
            --color-claro: #ecf0f1; /* Color de fondo claro */
            --color-oscuro: #2c3e50; /* Color de texto */
        }

        /* Estilizar el header para contener el video y texto */
        header {
            position: relative; /* Permite la posición absoluta de los elementos hijo (video y contenido) */
            width: 100%; /* Ancho completo de la pantalla */
            height: 100vh; /* Alto completo de la pantalla para crear un efecto de pantalla completa */
            overflow: hidden; /* Oculta el contenido que sobresale del header */
            display: flex; /* Centra el contenido vertical y horizontalmente */
            flex-direction: column; /* Coloca el contenido en una columna */
            justify-content: center; /* Centra el contenido verticalmente */
            align-items: center; /* Centra el contenido horizontalmente */
            text-align: center; /* Centra el texto */
            color: #fff; /* Color de texto blanco para contraste con el video */
        }

        /* Estilizar el video para que actúe como fondo */
        header video {
            position: absolute; /* Posiciona el video relativo al header */
            top: 0; /* Alinea el video a la parte superior del header */
            left: 0; /* Alinea el video a la izquierda del header */
            width: 100%; /* Ancho completo del header */
            height: 100%; /* Alto completo del header */
            object-fit: cover; /* Escala el video para cubrir el header, recortando si es necesario */
            z-index: -1; /* Coloca el video detrás de otros elementos del header */
        }

        /* Estilizar el contenido del header (texto) para que se sitúe encima del video */
        header .header-content {
            position: relative; /* Asegura que el contenido esté posicionado encima del video */
            z-index: 1; /* Coloca el contenido encima del video (z-index -1) */
            background: rgba(0, 0, 0, 0.5); /* Fondo semi-transparente para mejorar la legibilidad del texto */
            padding: 1rem; /* Agrega padding al contenido */
            border-radius: 8px; /* Asigna bordes redondeados para un aspecto pulido */
        }

        /* Estilizar el título del header */
        header h1 {
            margin: 0; /* Elimina el margen predeterminado */
            font-size: 2.5rem; /* Tamaño de fuente grande para resaltar */
        }

        /* Estilizar el subtítulo del header */
        header p {
            margin: 0.5rem 0 0; /* Pequeño margen superior para espaciado */
            font-size: 1.2rem; /* Tamaño de fuente ligeramente menor que el título */
        }

        /* Estilizar la sección principal */
        main {
            max-width: 800px; /* Límite de ancho para mejorar la legibilidad */
            margin: 2rem auto; /* Centra horizontalmente con margen superior e inferior */
            background: #fff; /* Fondo blanco para contraste */
            padding: 2rem; /* Agrega padding interno */
            border-radius: 12px; /* Asigna bordes redondeados para un aspecto pulido */
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1); /* Agrega una sombra para profundidad */
            text-align: center; /* Centra el texto */
        }

        /* Estilizar el título de la sección principal */
        main h2 {
            color: var(--color-secundario); /* Utiliza el color secundario para coherencia */
        }

        /* Estilizar los botones para la interactividad */
        .button {
            display: inline-block; /* Permite agregar padding y margen */
            padding: 12px 24px; /* Tamaño de botón cómodo */
            background-color: var(--color-primario); /* Color principal de la marca */
            color: white; /* Color de texto blanco para contraste */
            text-decoration: none; /* Elimina el subrayado para enlaces */
            font-weight: bold; /* Texto en negrita para resaltar */
            border-radius: 8px; /* Asigna bordes redondeados para un aspecto pulido */
            transition: all 0.3s ease; /* Transiciones suaves para los efectos de hover y active */
            border: none; /* No borde */
            cursor: pointer; /* Cursor de puntero para indicar interactividad */
            font-size: 1rem; /* Tamaño de fuente estándar */
            margin-top: 1rem; /* Espaciado superior */
        }

        /* Efecto de hover para los botones */
        .button:hover {
            background-color: #2980b9; /* Sombra ligeramente más oscura del color principal */
            transform: translateY(-2px); /* Efecto de elevación para resaltar */
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2); /* Agrega una sombra para profundidad */
        }

        /* Efecto de active para los botones */
        .button:active {
            transform: translateY(0); /* Vuelve a la posición original */
        }
    </style>
</head>
<body>
    <!-- Header con video y texto -->
    <header>
        <!-- Video para el fondo que se reproduce automáticamente -->
        <video autoplay loop playsinline muted id="headerVideo">
            <!-- Autoplay: intenta iniciar el video automáticamente -->
            <!-- Loop: hace que el video se reproduzca indefinidamente -->
            <!-- Playsinline: asegura que el video se reproduzca en línea en dispositivos móviles -->
            <!-- Muted: silencia el audio para evitar que se reproduzca automáticamente -->
            <source src="../assets/videos/video_portada.mp4" type="video/mp4">
            <!-- Verificar que la ruta sea correcta relativa al archivo HTML -->
            <!-- Asegurarse de que el video tenga una pista de audio válida (por ejemplo, AAC) -->
            Tu navegador no admite el elemento de video.
            <!-- Texto de reserva si el navegador no admite el elemento de video -->
        </video>
        <!-- Contenedor para el contenido del header para asegurar la legibilidad sobre el video -->
        <div class="header-content">
            <h1>GestorTasks</h1>
            <p>Gestor de tareas colaborativo con enfoque Scrum</p>
        </div>
    </header>
    <!-- Sección principal -->
    <main>
        <h2>Bienvenido al sistema</h2>
        <p>Desde aquí puedes gestionar tus tareas</p>
        <!-- Botón para ver los proyectos -->
        <a class="button" href="http://localhost:3000/app/controllers/negocio/proyectos.php">Ver Tareas</a>
    </main>
    <script>
        // Intenta reproducir el video con audio en la carga de la página
        document.addEventListener('DOMContentLoaded', function() {
            const video = document.getElementById('headerVideo');
            video.volume = 0.5; // Asegura que el video tenga algún volumen
            video.play().then(() => {
                console.log('Reproducción de video con audio iniciada con éxito.');
            }).catch(error => {
                console.error('Error al intentar reproducir el video con audio:', error);
            });
        });
    </script>
</body>
</html>


