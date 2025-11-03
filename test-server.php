<?php
// Test simple pour vérifier que PHP fonctionne
echo "PHP fonctionne correctement!\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n";
echo "Version PHP: " . phpversion() . "\n";

// Test de connexion à la base de données
try {
    $pdo = new PDO('sqlite:database/database.sqlite');
    echo "Connexion à la base de données: OK\n";
} catch (Exception $e) {
    echo "Erreur base de données: " . $e->getMessage() . "\n";
}
?>
