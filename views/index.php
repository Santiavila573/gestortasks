<?php
// Incluir el archivo de configuración de la base de datos
require_once('C:\xampp\htdocs\gestortasks\app\config\db.php');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GestorTasks - Dashboard</title>
<style>
        /* Define CSS custom properties (variables) for consistent theming */
        :root {
            --primary-color: #3498db; /* Main brand color */
            --secondary-color: #2c3e50; /* Dark color for text and accents */
            --accent-color: #e74c3c; /* Highlight color */
            --light-color: #ecf0f1; /* Background color for body */
            --dark-color: #2c3e50; /* Text color */
        }

        /* Set global styles for the page */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; /* Clean, modern font stack */
            background-color: var(--light-color); /* Light background for contrast */
            margin: 0; /* Remove default margins */
            padding: 0; /* Remove default padding */
            color: var(--dark-color); /* Default text color */
            line-height: 1.6; /* Improve text readability */
        }

        /* Style the header to contain the video and text */
        header {
            position: relative; /* Allows absolute positioning of child elements (video and content) */
            width: 100%; /* Full width of the viewport */
            height: 100vh; /* Full viewport height to create a full-screen effect */
            overflow: hidden; /* Prevent content from spilling outside the header */
            display: flex; /* Center content vertically and horizontally */
            flex-direction: column; /* Stack content vertically */
            justify-content: center; /* Center content vertically */
            align-items: center; /* Center content horizontally */
            text-align: center; /* Center text alignment */
            color: #fff; /* White text for contrast against the video */
        }

        /* Style the video element to act as a background */
        header video {
            position: absolute; /* Position video relative to the header */
            top: 0; /* Align to top of header */
            left: 0; /* Align to left of header */
            width: 100%; /* Full width of the header */
            height: 100%; /* Full height of the header */
            object-fit: cover; /* Scale video to cover the header, cropping if necessary */
            z-index: -1; /* Place video behind other header content */
        }

        /* Style the header content (text) to sit above the video */
        header .header-content {
            position: relative; /* Ensure content is positioned above the video */
            z-index: 1; /* Place content above the video (z-index -1) */
            background: rgba(0, 0, 0, 0.5); /* Semi-transparent black overlay for text readability */
            padding: 1rem; /* Add padding to the content */
            border-radius: 8px; /* Rounded corners for a polished look */
        }

        /* Style the header title */
        header h1 {
            margin: 0; /* Remove default margin */
            font-size: 2.5rem; /* Large font size for prominence */
        }

        /* Style the header subtitle */
        header p {
            margin: 0.5rem 0 0; /* Small top margin for spacing */
            font-size: 1.2rem; /* Slightly smaller than the title */
        }

        /* Style the main content section */
        main {
            max-width: 800px; /* Limit width for readability */
            margin: 2rem auto; /* Center horizontally with top/bottom margin */
            background: #fff; /* White background for contrast */
            padding: 2rem; /* Internal spacing */
            border-radius: 12px; /* Rounded corners */
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1); /* Subtle shadow for depth */
            text-align: center; /* Center text */
        }

        /* Style the main section title */
        main h2 {
            color: var(--secondary-color); /* Use secondary color for consistency */
        }

        /* Style buttons for interactivity */
        .button {
            display: inline-block; /* Allow padding and margin */
            padding: 12px 24px; /* Comfortable button size */
            background-color: var(--primary-color); /* Brand color */
            color: white; /* White text for contrast */
            text-decoration: none; /* Remove underline for links */
            font-weight: bold; /* Bold text for emphasis */
            border-radius: 8px; /* Rounded corners */
            transition: all 0.3s ease; /* Smooth transitions for hover/active effects */
            border: none; /* No border */
            cursor: pointer; /* Pointer cursor on hover */
            font-size: 1rem; /* Standard font size */
            margin-top: 1rem; /* Spacing above button */
        }

        /* Hover effect for buttons */
        .button:hover {
            background-color: #2980b9; /* Slightly darker shade of primary color */
            transform: translateY(-2px); /* Slight lift effect */
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2); /* Add shadow for depth */
        }

        /* Active effect for buttons */
        .button:active {
            transform: translateY(0); /* Return to original position */
        }
    </style>
</head>
<body>
   <!-- Header section containing the video and text -->
    <header>
        <!-- Video element for the auto-playing background -->
        <video autoplay loop playsinline muted id="headerVideo">
            <!-- Autoplay: attempts to start video automatically -->
            <!-- Loop: makes the video repeat indefinitely -->
            <!-- Playsinline: ensures video plays inline on mobile devices -->
            <!-- Muted: silences the audio to avoid auto-playing audio -->
            <source src="../assets/videos/video_portada.mp4" type="video/mp4">
            <!-- Verify the path is correct relative to the HTML file location -->
            <!-- Ensure video has a valid audio track (e.g., AAC codec) -->
            Your browser does not support the video tag.
            <!-- Fallback text if the browser doesn't support video -->
        </video>
        <!-- Container for header text to ensure readability over video -->
        <div class="header-content">
            <h1>GestorTasks</h1>
            <p>Gestor de tareas colaborativo con enfoque Scrum</p>
        </div>
    </header>
    <!-- Main content section -->
    <main>
        <h2>Bienvenido al sistema</h2>
        <p>Desde aquí puedes gestionar tus tareas</p>
    <!-- Botón para ver los proyectos -->
    <a class="button" href="http://localhost:3000/app/controllers/negocio/proyectos.php">📁 Ver Tareas</a>
</main>
    <script>
        // Attempt to play video with audio on page load
        document.addEventListener('DOMContentLoaded', function() {
            const video = document.getElementById('headerVideo');
            video.volume = 0.5; // Ensure video has some volume
            video.play().then(() => {
                console.log('Video playback started successfully with audio.');
            }).catch(error => {
                console.error('Autoplay with audio failed:', error);
            });
        });
    </script>
</body>
</html>


