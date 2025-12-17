// Gestion du sidebar mobile
document.addEventListener('DOMContentLoaded', function() {
    const sidebarToggle = document.getElementById('sidebarToggle');
    const mobileSidebar = document.querySelector('.mobile-sidebar');
    const sidebarOverlay = document.querySelector('.sidebar-overlay');
    const closeSidebar = document.querySelector('.close-sidebar');
    
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            mobileSidebar.classList.add('show');
            sidebarOverlay.classList.add('show');
        });
    }
    
    if (closeSidebar) {
        closeSidebar.addEventListener('click', function() {
            mobileSidebar.classList.remove('show');
            sidebarOverlay.classList.remove('show');
        });
    }
    
    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', function() {
            mobileSidebar.classList.remove('show');
            sidebarOverlay.classList.remove('show');
        });
    }
    
    // Gestion du modal d'édition de tâche
    const editTaskModal = document.getElementById('editTaskModal');
    if (editTaskModal) {
        editTaskModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const taskId = button.getAttribute('data-id');
            const title = button.getAttribute('data-title');
            const description = button.getAttribute('data-description');
            const dueDate = button.getAttribute('data-due-date');
            const priority = button.getAttribute('data-priority');
            const status = button.getAttribute('data-status');
            
            const modalBody = editTaskModal.querySelector('.modal-body');
            modalBody.innerHTML = `
                <form action="update_task.php" method="POST">
                    <input type="hidden" name="task_id" value="${taskId}">
                    
                    <div class="mb-3">
                        <label for="edit_title" class="form-label">Titre</label>
                        <input type="text" class="form-control" id="edit_title" 
                               name="title" value="${title}" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_description" class="form-label">Description</label>
                        <textarea class="form-control" id="edit_description" 
                                  name="description" rows="3">${description}</textarea>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="edit_due_date" class="form-label">Date d'échéance</label>
                            <input type="datetime-local" class="form-control" 
                                   id="edit_due_date" name="due_date" value="${dueDate}">
                        </div>
                        <div class="col-md-6">
                            <label for="edit_priority" class="form-label">Priorité</label>
                            <select class="form-select" id="edit_priority" name="priority">
                                <option value="low" ${priority === 'low' ? 'selected' : ''}>Basse</option>
                                <option value="medium" ${priority === 'medium' ? 'selected' : ''}>Moyenne</option>
                                <option value="high" ${priority === 'high' ? 'selected' : ''}>Haute</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_status" class="form-label">Statut</label>
                        <select class="form-select" id="edit_status" name="status"><option value="pending" ${status === 'pending' ? 'selected' : ''}>En attente</option>
                            <option value="in_progress" ${status === 'in_progress' ? 'selected' : ''}>En cours</option>
                            <option value="completed" ${status === 'completed' ? 'selected' : ''}>Terminé</option>
                        </select>
                    </div>
                    
                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Enregistrer</button>
                    </div>
                </form>
            `;
        });
    }
    
    // Notification système
    if ('Notification' in window && Notification.permission === 'default') {
        Notification.requestPermission();
    }
    
    // Vérifier et afficher les notifications
    function showNotification(title, message) {
        if (Notification.permission === 'granted') {
            new Notification(title, {
                body: message,
                icon: '/assets/images/notification-icon.png'
            });
        }
    }
    
    // Fonction pour marquer une notification comme lue
    function markNotificationAsRead(notificationId) {
        fetch('/api/notifications/mark-read.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ id: notificationId })
        });
    }
    
    // Gestion du thème (clair/sombre)
    const themeToggle = document.getElementById('themeToggle');
    if (themeToggle) {
        themeToggle.addEventListener('click', function() {
            const currentTheme = document.documentElement.getAttribute('data-bs-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-bs-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            this.innerHTML = newTheme === 'dark' ? '<i class="fas fa-sun"></i>' : '<i class="fas fa-moon"></i>';
        });
        
        // Charger le thème sauvegardé
        const savedTheme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-bs-theme', savedTheme);
        themeToggle.innerHTML = savedTheme === 'dark' ? '<i class="fas fa-sun"></i>' : '<i class="fas fa-moon"></i>';
    }
    
    // Auto-hide des alertes après 5 secondes
    const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });
    
    // Confirmation avant suppression
    const deleteButtons = document.querySelectorAll('.btn-delete');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            if (!confirm('Êtes-vous sûr de vouloir supprimer cet élément ?')) {
                e.preventDefault();
            }
        });
    });
});

// Gestion de la sidebar mobile
document.addEventListener('DOMContentLoaded', function() {
    const sidebarToggle = document.getElementById('sidebarToggle');
    const closeSidebar = document.querySelector('.close-sidebar');
    const sidebarOverlay = document.querySelector('.sidebar-overlay');
    const mobileSidebar = document.querySelector('.mobile-sidebar');
    
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            mobileSidebar.classList.add('show');
            sidebarOverlay.classList.add('show');
            document.body.style.overflow = 'hidden';
        });
    }
    
    if (closeSidebar) {
        closeSidebar.addEventListener('click', function() {
            mobileSidebar.classList.remove('show');
            sidebarOverlay.classList.remove('show');
            document.body.style.overflow = '';
        });
    }
    
    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', function() {
            mobileSidebar.classList.remove('show');
            sidebarOverlay.classList.remove('show');
            document.body.style.overflow = '';
        });
    }
    
    // Fermer la sidebar mobile quand on clique sur un lien (sauf pour les dropdowns)
    const mobileLinks = document.querySelectorAll('.mobile-sidebar-nav a');
    mobileLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            if (!this.classList.contains('dropdown-toggle')) {
                mobileSidebar.classList.remove('show');
                sidebarOverlay.classList.remove('show');
                document.body.style.overflow = '';
            }
        });
    });
    
    // Mettre à jour le badge de notification
    function updateNotificationBadge() {
        fetch('/api/notifications/unread-count.php')
            .then(response => response.json())
            .then(data => {
                const badge = document.getElementById('notificationCount');
                if (badge && data.count > 0) {
                    badge.textContent = data.count;
                    badge.style.display = 'inline-block';
                }
            })
            .catch(error => console.error('Erreur:', error));
    }
    
    // Vérifier les notifications toutes les 30 secondes
    setInterval(updateNotificationBadge, 30000);
    updateNotificationBadge();
    
    // Gérer le resize de la fenêtre
    window.addEventListener('resize', function() {
        if (window.innerWidth > 992) {
            mobileSidebar.classList.remove('show');
            sidebarOverlay.classList.remove('show');
            document.body.style.overflow = '';
        }
    });
});