<?php
// ajouter_tache.php
session_start();
require_once 'connexion.php';

// Détection connexion
if (isset($pdo) && $pdo instanceof PDO) {
    $db = $pdo;
} elseif (isset($conn) && $conn instanceof PDO) {
    $db = $conn;
} else {
    die('Erreur : connexion DB introuvable (vérifie connexion.php).');
}

// Vérification session
if (!isset($_SESSION['user']['username']) && !isset($_SESSION['username'])) {
    header('Location: login.php');
    exit;
}

$error = '';
$success = '';

// Calcul prochain numéro
$r = $db->query("SELECT MAX(num_tache) AS last_num FROM taches")->fetch(PDO::FETCH_ASSOC);
$nextNumTache = ($r && $r['last_num']) ? (intval($r['last_num']) + 1) : 1;

// Récupération partenaires
$users = [];
try {
    $users = $db->query("SELECT id, username FROM users ORDER BY username ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // ignore
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom_tache = trim($_POST['nom_tache'] ?? '');
    $date_tache = $_POST['date_tache'] ?? date('Y-m-d');
    $montant = $_POST['montant'] ?? 0;
    $partenaire_id = $_POST['partenaire_id'] ?? null;

    if ($nom_tache === '') {
        $error = "Le nom de la tâche est requis.";
    } elseif (empty($partenaire_id)) {
        $error = "Veuillez sélectionner un partenaire.";
    } else {
        try {
            $db->beginTransaction();

            // Insertion dans taches
            $sql1 = "INSERT INTO taches (num_tache, nom_tache, `date`, montant_tache) 
                     VALUES (:num_tache, :nom_tache, :date_tache, :montant)";
            $stm1 = $db->prepare($sql1);
            $stm1->bindValue(':num_tache', $nextNumTache, PDO::PARAM_INT);
            $stm1->bindValue(':nom_tache', $nom_tache, PDO::PARAM_STR);
            $stm1->bindValue(':date_tache', $date_tache, PDO::PARAM_STR);
            $stm1->bindValue(':montant', $montant, PDO::PARAM_STR);
            $stm1->execute();

            $id_tache = $db->lastInsertId();

            // Insertion dans partenaires_tache
            $sql2 = "INSERT INTO partenaires_tache (id_tache, id_partenaire) VALUES (:id_tache, :id_partenaire)";
            $stm2 = $db->prepare($sql2);
            $stm2->bindValue(':id_tache', $id_tache, PDO::PARAM_INT);
            $stm2->bindValue(':id_partenaire', $partenaire_id, PDO::PARAM_INT);
            $stm2->execute();

            // Insertion initiale dans detail_tache
            $sql3 = "INSERT INTO detail_tache (id_tache, date, etat) VALUES (:id_tache, :date_tache, :etat)";
            $stm3 = $db->prepare($sql3);
            $stm3->bindValue(':id_tache', $id_tache, PDO::PARAM_INT);
            $stm3->bindValue(':date_tache', $date_tache, PDO::PARAM_STR);
            $stm3->bindValue(':etat', 'non accompli', PDO::PARAM_STR);
            $stm3->execute();

            $db->commit();

            // Message de succès et redirection après 2 secondes
            $success = "Tâche ajoutée avec succès (Numéro : {$nextNumTache}) et partenaire associé.";
            echo "<meta http-equiv='refresh' content='2;URL=liste_taches.php'>";

            // Vider POST pour reset formulaire
            $_POST = [];

        } catch (PDOException $e) {
            if ($db->inTransaction()) $db->rollBack();
            $error = "Erreur lors de l'ajout : " . $e->getMessage();
        }
    }
}
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <title>Ajouter une tâche</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <script>
    let allOptions = [];
    window.addEventListener('DOMContentLoaded', () => {
      document.getElementById('nom_tache')?.focus();
      const s = document.getElementById('partenaire_id');
      if (s) allOptions = Array.from(s.options);
    });

    function searchPartenaire() {
      const q = document.getElementById('searchPartenaire').value.toLowerCase();
      const s = document.getElementById('partenaire_id');
      if (!s) return;
      s.innerHTML = '';
      allOptions.forEach(opt => {
        if (opt.value === '' || opt.text.toLowerCase().includes(q)) s.appendChild(opt);
      });
    }
  </script>
</head>
<body class="bg-light p-4">
<div class="container">
  <h1 class="mb-4">Ajouter une tâche</h1>

  <?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>
  <?php if ($success): ?>
    <div class="alert alert-success text-center fw-bold"><?= htmlspecialchars($success) ?></div>
  <?php endif; ?>

  <?php if (!$success): ?>
  <form method="post" class="mb-5">
    <div class="mb-3">
      <label class="form-label">Numéro de tâche (prévu)</label>
      <input class="form-control" value="<?= htmlspecialchars($nextNumTache) ?>" readonly>
    </div>

    <div class="mb-3">
      <label class="form-label">Nom de la tâche</label>
      <input id="nom_tache" name="nom_tache" class="form-control" required>
    </div>

    <div class="mb-3">
      <label class="form-label">Date</label>
      <input type="date" name="date_tache" class="form-control" value="<?= date('Y-m-d') ?>" required>
    </div>

    <div class="mb-3">
      <label class="form-label">Montant</label>
      <input type="number" step="0.01" name="montant" class="form-control" value="0">
    </div>

    <div class="mb-3">
      <label class="form-label">Rechercher un partenaire</label>
      <input id="searchPartenaire" onkeyup="searchPartenaire()" class="form-control" placeholder="Tapez pour filtrer...">
    </div>

    <div class="mb-3">
      <label class="form-label">Sélectionner un partenaire</label>
      <select id="partenaire_id" name="partenaire_id" class="form-select" required>
        <option value="">-- Choisir --</option>
        <?php foreach ($users as $u): ?>
          <option value="<?= (int)$u['id'] ?>"><?= htmlspecialchars($u['username']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="mb-3">
      <a href="ajouter_partenaire.php" class="btn btn-outline-secondary">➕ Nouveau partenaire</a>
    </div>

    <button class="btn btn-primary">Ajouter la tâche</button>
  </form>
  <?php endif; ?>
</div>
</body>
</html>
