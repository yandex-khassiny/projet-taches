<?php if (isAuthenticated()): ?>
</div> <!-- Fermeture de .main-content -->
<?php endif; ?>

<!-- Bootstrap JS -->
<script src="/bootstrap/js/bootstrap.bundle.min.js"></script>

<!-- JavaScript Personnalisé -->
<script src="/assets/js/app.js"></script>

<?php if (isAuthenticated()): ?>
<script>
// Notification système
function checkNotifications() {
    fetch('/api/notifications/unread-count.php')
        .then(response => response.json())
        .then(data => {
            if (data.count > 0) {
                document.getElementById('notificationCount').textContent = data.count;
                document.getElementById('notificationCount').classList.remove('d-none');
            }
        });
}

// Vérifier les notifications toutes les 30 secondes
setInterval(checkNotifications, 30000);
checkNotifications();
</script>
<?php endif; ?>

</body>
</html>