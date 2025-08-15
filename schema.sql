CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  rank ENUM('admin','user') NOT NULL DEFAULT 'user',
  telephone VARCHAR(20)
);
CREATE TABLE IF NOT EXISTS etat_tache (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nom_etat_tache VARCHAR(50) NOT NULL
);
CREATE TABLE IF NOT EXISTS taches (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nom_tache VARCHAR(100) NOT NULL,
  date DATE NOT NULL,
  montant_tache DECIMAL(10,2) NOT NULL DEFAULT 0,
  caisse DECIMAL(10,2) NOT NULL DEFAULT 0,
  id_etat_tache INT NOT NULL,
  FOREIGN KEY (id_etat_tache) REFERENCES etat_tache(id)
);
CREATE TABLE IF NOT EXISTS partenaires_tache (
  id INT AUTO_INCREMENT PRIMARY KEY,
  id_tache INT NOT NULL,
  id_partenaire INT NOT NULL,
  pourcentage_partenaire DECIMAL(5,2) NOT NULL DEFAULT 0,
  etat_payement ENUM('non payé','partiel','payé') DEFAULT 'non payé',
  FOREIGN KEY (id_tache) REFERENCES taches(id) ON DELETE CASCADE,
  FOREIGN KEY (id_partenaire) REFERENCES users(id) ON DELETE CASCADE
);
CREATE TABLE IF NOT EXISTS detail_tache (
  id INT AUTO_INCREMENT PRIMARY KEY,
  id_tache INT NOT NULL,
  date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  detail_tache TEXT,
  FOREIGN KEY (id_tache) REFERENCES taches(id) ON DELETE CASCADE
);
INSERT INTO users (username,password,rank,telephone) VALUES
('admin', '$2y$10$z6r8bHc1wphmM8HfVDrpQeVf6Rk6o4e4bWkzYF1t7a3gk9oG0x8x2', 'admin', '')
ON DUPLICATE KEY UPDATE username=VALUES(username);
INSERT INTO etat_tache (nom_etat_tache) VALUES ('En attente'), ('En cours'), ('Terminée')
ON DUPLICATE KEY UPDATE nom_etat_tache=VALUES(nom_etat_tache);
