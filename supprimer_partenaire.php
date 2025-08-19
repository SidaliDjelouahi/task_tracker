<?php
require_once 'connexion.php';

if (isset($_GET['id']) && is_numeric($_GET['id']) && isset($_GET['id_tache']) && is_numeric($_GET['id_tache'])) {
    $id = intval($_GET['id']);          // ID de la relation partenaires_tache
    $id_tache = intval($_GET['id_tache']); // ID de la tâche

    // Suppression du partenaire de la tâche
    $stmt = $pdo->prepare("DELETE FROM partenaires_tache WHERE id = ?");
    $stmt->execute([$id]);

    // Affichage message Bootstrap + redirection JS vers modifier_pourcentage_partenaire.php
    echo '
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <div class="container mt-3">
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            ✅ Partenaire supprimé avec succès.
        </div>
    </div>
    <script>
        setTimeout(function() {
            window.location.href = "modifier_pourcentage_partenaire.php?id=' . $id_tache . '";
        }, 2000);
    </script>
    ';
} else {
    echo '
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <div class="container mt-3">
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            ❌ ID invalide.
        </div>
    </div>
    <script>
        setTimeout(function() {
            window.location.href = "liste_taches.php";
        }, 2000);
    </script>
    ';
}
