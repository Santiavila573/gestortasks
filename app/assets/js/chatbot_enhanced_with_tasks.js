// Funcionalidad del chatbot asistente virtual mejorada con soporte para tareas

function handleChatbotInput(event) {
    if (event.keyCode === 13) {
        const userInput = document.getElementById('chatbot-input').value;
        
        // Agregar mensaje del usuario al chat
        addUserMessage(userInput);
        
        getChatbotResponse(userInput).then(response => {
            addChatbotMessage(response);
            document.getElementById('chatbot-input').value = '';
        });
    }
}

function addUserMessage(message) {
    const chatbotMessages = document.getElementById('chatbot-messages');
    const messageElement = document.createElement('p');
    messageElement.innerHTML = `<strong>Usuario:</strong> ${message}`;
    chatbotMessages.appendChild(messageElement);
    chatbotMessages.scrollTop = chatbotMessages.scrollHeight;
}

function addChatbotMessage(message) {
    const chatbotMessages = document.getElementById('chatbot-messages');
    const messageElement = document.createElement('p');
    messageElement.innerHTML = `<strong>Chatbot:</strong> ${message}`;
    chatbotMessages.appendChild(messageElement);
    chatbotMessages.scrollTop = chatbotMessages.scrollHeight;
    
    // Agregar estilos CSS para el mensaje del chatbot
    messageElement.classList.add('chatbot-message');
    
    // Verificar si el mensaje contiene una tabla de sprints
    if (message.includes('<table') && message.includes('Nombre') && message.includes('Estado') && message.includes('Inicio') && message.includes('Fin')) {
        // Agregar estilos CSS para la tabla
        addTableStyles();
        
        // Programar la ocultación de la tabla y mostrar retroalimentación
        setTimeout(() => {
            hideTableAndShowFeedback(messageElement, 'sprints');
        }, 5000); // Ocultar después de 5 segundos
    }
    // Verificar si el mensaje contiene una tabla de tareas
    else if (message.includes('<table') && message.includes('Título') && message.includes('Proyecto') && message.includes('Prioridad')) {
        // Agregar estilos CSS para la tabla
        addTableStyles();
        
        // Programar la ocultación de la tabla y mostrar retroalimentación
        setTimeout(() => {
            hideTableAndShowFeedback(messageElement, 'tareas');
        }, 5000); // Ocultar después de 5 segundos
    }
    
    document.getElementById('chatbot-input').focus();
}

function addTableStyles() {
    // Verificar si los estilos ya existen
    if (!document.getElementById('chatbot-table-styles')) {
        const style = document.createElement('style');
        style.id = 'chatbot-table-styles';
        style.textContent = `
            .chatbot-messages table {
                border-collapse: collapse;
                width: 100%;
                margin: 10px 0;
                font-size: 12px;
            }
            .chatbot-messages table th {
                border: 1px solid #ddd;
                padding: 8px;
                text-align: left;
                color: #ffffff;
                background-color: #3498db;
                font-weight: bold;
            }
            .chatbot-messages table td {
                border: 1px solid #ddd;
                padding: 8px;
                text-align: left;
                background-color: #ffffff;
            }
            .chatbot-messages table tr:nth-child(even) td {
                background-color: #f9f9f9;
            }
            .fade-in {
                opacity: 1;
                transition: opacity 1s ease-in;
            }
            .fade-out {
                opacity: 0;
                transition: opacity 1s ease-out;
            }
        `;
        document.head.appendChild(style);
    }
}

function hideTableAndShowFeedback(messageElement, type) {
    // Agregar clase de fade-out a la tabla
    const table = messageElement.querySelector('table');
    if (table) {
        table.classList.add('fade-out');
        
        // Después de la animación, ocultar completamente y mostrar retroalimentación
        setTimeout(() => {
            table.style.display = 'none';
            if (type === 'sprints') {
                showSprintFeedback();
            } else if (type === 'tareas') {
                showTaskFeedback();
            }
        }, 1000);
    }
}

function showSprintFeedback() {
    const feedbackMessages = [
        "He revisado tus sprints. ¿Te gustaría que analice algún sprint específico?",
        "Veo que tienes sprints en diferentes estados. ¿Necesitas ayuda con la gestión de alguno?",
        "¿Hay algún sprint que te preocupe o en el que necesites asistencia?",
        "Puedo ayudarte a analizar el progreso de tus sprints. ¿Qué te gustaría saber?"
    ];
    
    const randomFeedback = feedbackMessages[Math.floor(Math.random() * feedbackMessages.length)];
    
    setTimeout(() => {
        addChatbotMessage(randomFeedback);
    }, 500);
    
    setTimeout(() => {
        addChatbotMessage("Escribe 'Sprints' para ver el estado de tus sprints nuevamente o 'Tareas' para ver tus tareas.");
    }, 3000);
}

function showTaskFeedback() {
    const feedbackMessages = [
        "He revisado tus tareas. ¿Necesitas ayuda con alguna tarea específica?",
        "Veo que tienes tareas con diferentes prioridades. ¿Te gustaría que te ayude a priorizarlas?",
        "¿Hay alguna tarea bloqueada o en la que necesites asistencia?",
        "Puedo ayudarte a analizar el estado de tus tareas. ¿Qué te gustaría saber?",
        "¿Te gustaría que revise las tareas por fecha de vencimiento?"
    ];
    
    const randomFeedback = feedbackMessages[Math.floor(Math.random() * feedbackMessages.length)];
    
    setTimeout(() => {
        addChatbotMessage(randomFeedback);
    }, 500);
    
    setTimeout(() => {
        addChatbotMessage("Escribe 'Tareas' para ver tus tareas nuevamente o 'Sprints' para ver tus sprints.");
    }, 3000);
}

function getChatbotResponse(userInput) {
    const input = userInput.toLowerCase();
    
    // Determinar qué endpoint usar basado en la entrada del usuario
    let endpoint = '/app/controllers/logic/sprints.php'; // Por defecto para sprints
    
    if (input === 'tareas') {
        endpoint = '/app/controllers/logic/tareas_chatbot.php';
    }
    
    return fetch(endpoint, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ user_input: userInput })
    }).then(response => response.text());
}

function checkReminders() {
    const today = new Date();
    const reminders = document.querySelectorAll('.reminder');
    reminders.forEach(reminder => {
        const reminderDate = new Date(reminder.getAttribute('data-reminder-date'));
        if (reminderDate.getDate() === today.getDate() && 
            reminderDate.getMonth() === today.getMonth() && 
            reminderDate.getFullYear() === today.getFullYear()) {
            const reminderText = reminder.getAttribute('data-reminder-text');
            addChatbotMessage(`Recordatorio: ${reminderText}`);
        }
    });
}

function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    sidebar.classList.toggle('active');
}

function toggleChatbot() {
    const chatbot = document.getElementById('chatbot-panel');
    chatbot.classList.toggle('active');
    if (chatbot.classList.contains('active')) {
        checkReminders();
        fetchAPIRecommendations();
    }
}

function requestNotificationPermission() {
    if (Notification.permission === 'default') {
        Notification.requestPermission().then(permission => {
            if (permission === 'granted') {
                console.log('Notificaciones permitidas');
            }
        });
    }
}

// Función adicional para manejar diferentes tipos de consultas
function handleAdvancedChatbotQueries(userInput) {
    const input = userInput.toLowerCase();
    
    if (input.includes('ayuda') || input.includes('help')) {
        return "Puedo ayudarte con: consultar sprints (escribe 'sprints'), consultar tareas (escribe 'tareas'), revisar proyectos. ¿Qué necesitas?";
    }
    
    if (input.includes('estado') && input.includes('sprint')) {
        return "Para ver el estado de tus sprints, escribe 'sprints' y te mostraré una tabla detallada.";
    }
    
    if (input.includes('estado') && input.includes('tarea')) {
        return "Para ver el estado de tus tareas, escribe 'tareas' y te mostraré una tabla detallada.";
    }
    
    if (input.includes('crear') && input.includes('sprint')) {
        return "Para crear un nuevo sprint, ve a la sección de gestión de sprints en el panel principal.";
    }
    
    if (input.includes('crear') && input.includes('tarea')) {
        return "Para crear una nueva tarea, ve a la sección de gestión de tareas en el panel principal.";
    }
    
    return null; // Retorna null si no hay respuesta específica
}