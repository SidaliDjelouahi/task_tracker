<?php
// ajouter_tache.php
session_start();
require_once 'connexion.php';

// Vérification session
if (!isset($_SESSION['user']['username']) && !isset($_SESSION['username'])) {
    header('Location: login.php');
    exit;
}

$error = '';
$success = '';

// Calcul prochain numéro
$r = $pdo->query("SELECT MAX(num_tache) AS last_num FROM taches")->fetch(PDO::FETCH_ASSOC);
$nextNumTache = ($r && $r['last_num']) ? (intval($r['last_num']) + 1) : 1;

// Récupération partenaires
$users = $pdo->query("SELECT id, username FROM users ORDER BY username ASC")->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom_tache = trim($_POST['nom_tache'] ?? '');
    $detail_tache = trim($_POST['detail_tache'] ?? '');
    $date_tache = $_POST['date_tache'] ?? date('Y-m-d');
    $montant = floatval($_POST['montant'] ?? 0);
    $partenaire_id = $_POST['partenaire_id'] ?? null;
    $versement = floatval($_POST['versement'] ?? 0);

    // Déterminer automatiquement l'état du paiement
    if ($versement <= 0) {
        $payement_client = "non paye";
    } elseif ($versement < $montant) {
        $payement_client = "partiel";
    } else {
        $payement_client = "paye";
    }

    if ($nom_tache === '') {
        $error = "Le nom de la tâche est requis.";
    } elseif (empty($partenaire_id)) {
        $error = "Veuillez sélectionner un partenaire.";
    } else {
        try {
            $pdo->beginTransaction();

            // Insertion dans taches
            $sql1 = "INSERT INTO taches (num_tache, nom_tache, `date`, montant_tache, versement, payement_client) 
                     VALUES (:num_tache, :nom_tache, :date_tache, :montant, :versement, :payement_client)";
            $stmt1 = $pdo->prepare($sql1);
            $stmt1->execute([
                ':num_tache' => $nextNumTache,
                ':nom_tache' => $nom_tache,
                ':date_tache' => $date_tache,
                ':montant' => $montant,
                ':versement' => $versement,
                ':payement_client' => $payement_client
            ]);
            $id_tache = $pdo->lastInsertId();

            // Insertion partenaire choisi
            $stmt2 = $pdo->prepare("INSERT INTO partenaires_tache (id_tache, id_partenaire, pourcentage_partenaire) 
                                    VALUES (:id_tache, :id_partenaire, :pourcentage)");
            $stmt2->execute([
                ':id_tache' => $id_tache,
                ':id_partenaire' => $partenaire_id,
                ':pourcentage' => 50
            ]);

            // Ajout du partenaire "local"
            $localUser = $pdo->query("SELECT id FROM users WHERE username = 'local' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
            if ($localUser) {
                $stmtLocal = $pdo->prepare("INSERT INTO partenaires_tache (id_tache, id_partenaire, pourcentage_partenaire) 
                                            VALUES (:id_tache, :id_partenaire, :pourcentage)");
                $stmtLocal->execute([
                    ':id_tache' => $id_tache,
                    ':id_partenaire' => $localUser['id'],
                    ':pourcentage' => 50
                ]);
            }

            // Insertion initiale dans detail_tache
            $stmt3 = $pdo->prepare("INSERT INTO detail_tache (id_tache, date, etat, detail_tache) 
                                    VALUES (:id_tache, :date_tache, :etat, :detail)");
            $stmt3->execute([
                ':id_tache' => $id_tache,
                ':date_tache' => $date_tache,
                ':etat' => 'non accompli',
                ':detail' => $detail_tache
            ]);

           $pdo->commit();
            $_SESSION['success'] = "Tâche ajoutée avec succès (Numéro : {$nextNumTache}).";
            header("Location: liste_taches.php");
            exit;


        } catch (PDOException $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
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
  <meta name="viewport" content="width=device-width, initial-scale=1"> <!-- important pour mobile -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    /* Texte plus grand sur petits écrans */
    @media (max-width: 576px) {
      body { font-size: 1.1rem; }   /* légèrement + grand que la base */
      label, input, textarea, select { font-size: 1.05rem; }
      h1 { font-size: 1.8rem; }
      .btn-lg { font-size: 1.2rem; }
    }
  </style>
</head>
<body class="bg-light p-3">

<div class="container">
  <h1 class="text-center mb-4 fw-bold">➕ Ajouter une tâche</h1>

  <?php if ($error): ?>
    <div class="alert alert-danger fs-5"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>
  <?php if ($success): ?>
    <div class="alert alert-success text-center fw-bold fs-5"><?= htmlspecialchars($success) ?></div>
  <?php endif; ?>

  <?php if (!$success): ?>
  <form method="post" class="row g-3">

    <!-- Carte numéro -->
    <div class="col-12">
      <div class="card shadow-sm">
        <div class="card-body">
          <label class="form-label fw-semibold fs-5">Numéro de tâche (prévu)</label>
          <input class="form-control fs-5" value="<?= htmlspecialchars($nextNumTache) ?>" readonly>
        </div>
      </div>
    </div>

    <!-- Carte nom & détails -->
    <div class="col-12">
      <div class="card shadow-sm">
        <div class="card-body">
          <label class="form-label fw-semibold fs-5">Nom de la tâche</label>
          <input id="nom_tache" name="nom_tache" class="form-control fs-5 mb-3" required autocomplete="off" autofocus>
          <label class="form-label fw-semibold fs-5">Détail</label>
          <textarea name="detail_tache" class="form-control fs-5" rows="3" placeholder="Description, client, téléphone, numéro de série..."></textarea>
        </div>
      </div>
    </div>

    <!-- Carte date & montant -->
    <div class="col-md-6 col-12">
      <div class="card shadow-sm">
        <div class="card-body">
          <label class="form-label fw-semibold fs-5">Date</label>
          <input type="date" name="date_tache" class="form-control fs-5" value="<?= date('Y-m-d') ?>" required>
        </div>
      </div>
    </div>
    <div class="col-md-6 col-12">
      <div class="card shadow-sm">
        <div class="card-body">
          <label class="form-label fw-semibold fs-5">Montant</label>
          <input type="number" step="0.01" name="montant" class="form-control fs-5" value="0">
        </div>
      </div>
    </div>

    <!-- Carte partenaire & versement -->
    <div class="col-md-6 col-12">
      <div class="card shadow-sm">
        <div class="card-body">
          <label class="form-label fw-semibold fs-5">Partenaire</label>
          <select id="partenaire_id" name="partenaire_id" class="form-select fs-5" required>
            <option value="">-- Choisir --</option>
            <?php foreach ($users as $u): ?>
              <option value="<?= (int)$u['id'] ?>"><?= htmlspecialchars($u['username']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
    </div>
    <div class="col-md-6 col-12">
      <div class="card shadow-sm">
        <div class="card-body">
          <label class="form-label fw-semibold fs-5">Versement</label>
          <input type="number" step="0.01" name="versement" class="form-control fs-5" value="<?= htmlspecialchars($_POST['versement'] ?? 0) ?>">
        </div>
      </div>
    </div>

    <!-- Bouton -->
    <div class="col-12 text-center">
      <button class="btn btn-primary btn-lg px-5 fs-5">✅ Ajouter la tâche</button>
    </div>
  </form>
  <?php endif; ?>
</div>

</body>
</html>

