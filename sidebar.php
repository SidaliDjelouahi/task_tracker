<style>
/* Sidebar */
#mySidebar {
    height: 100%;
    width: 250px;
    position: fixed;
    z-index: 1050;
    top: 0;
    left: -250px; /* caché par défaut à gauche */
    background-color: #343a40;
    overflow-x: hidden;
    transition: 0.3s;
    padding-top: 60px;
}

#mySidebar a {
    padding: 10px 20px;
    text-decoration: none;
    font-size: 18px;
    color: #f8f9fa;
    display: block;
    transition: 0.2s;
}

#mySidebar a:hover {
    background-color: #495057;
}

#sidebarBtn {
    position: fixed;
    bottom: 20px;
    right: 20px;
    z-index: 1060;
    background-color: #0d6efd;
    color: white;
    border: none;
    border-radius: 50%;
    width: 55px;
    height: 55px;
    font-size: 24px;
    cursor: pointer;
}

/* Bouton de fermeture dans le sidebar */
#sidebarCloseBtn {
    position: absolute;
    top: 10px;
    right: 15px;
    font-size: 25px;
    color: white;
    cursor: pointer;
}
</style>

<!-- Sidebar HTML -->
<div id="mySidebar">
    <span id="sidebarCloseBtn">&times;</span>
    <a href="liste_taches.php">🏠 Home</a>
    
    <?php if (isset($_SESSION['user']['rank']) && $_SESSION['user']['rank'] === 'admin'): ?>
        <a href="liste_users.php">👤 Users</a>
        
    <?php endif; ?>
    <a href="control.php?id=<?= (int)$_SESSION['user']['id'] ?>">🛠️ Control</a>
    <a href="caisse.php">💰 Caisse</a>
    <a href="caisse_partenaire.php">💰 Caisse partenaire</a>
    <a href="logout.php">🔓 Logout</a>
</div>

<!-- Bouton flottant pour ouvrir -->
<button id="sidebarBtn">☰</button>

<script>
const sidebar = document.getElementById('mySidebar');
const openBtn = document.getElementById('sidebarBtn');
const closeBtn = document.getElementById('sidebarCloseBtn');

// Fonction pour ouvrir/fermer le sidebar
openBtn.onclick = () => {
    if (sidebar.style.left === "0px") {
        sidebar.style.left = "-250px";
    } else {
        sidebar.style.left = "0";
    }
};

closeBtn.onclick = () => sidebar.style.left = "-250px";
</script>
