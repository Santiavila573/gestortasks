self.addEventListener('push', function(event) {
    const data = event.data.json();
    self.registration.showNotification(data.title, {
        body: data.body,
        icon: '../assets/images/notification-text.ico' // Reemplaza con la ruta de un ícono
    });
});

self.addEventListener('notificationclick', function(event) {
    event.notification.close();
    clients.openWindow('http://localhost:3000/app/controllers/negocio/proyectos.php');
});
