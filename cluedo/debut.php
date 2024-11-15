<?php 
$bdd_fichier = 'cluedo.db';
$sqlite = new SQLite3($bdd_fichier);

// Requête SQL pour récupérer les noms des personnages
$sql = 'SELECT nom_personnage FROM personnages';
$result = $sqlite->query($sql);

// Début du document HTML
echo "<!DOCTYPE html>\n"; 
echo "<html lang=\"fr\"><head><meta charset=\"UTF-8\">\n"; 
echo "<meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">";
echo "<title>Choix du personnage</title>";
echo "<link rel=\"stylesheet\" href=\"debut.css\">"; // Fichier CSS externe
echo "</head>";
echo "<body>";

// Vérification et récupération de la sélection si le formulaire est soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['choix'])) {
    $choix_personnage = $_POST['choix'];
    echo "<p>Personnage sélectionné : " . htmlspecialchars($choix_personnage) . "</p>";
}

echo "<h1>Choix du personnage</h1>";

// Création de la listbox
echo "<form action=\"hall.php\" method=\"post\">"; // Méthode POST
echo "<select name=\"choix\" id=\"choix\">";

// Boucle pour ajouter chaque personnage en tant qu'option dans la listbox
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    echo "<option value=\"" . htmlspecialchars($row['nom_personnage']) . "\">" . htmlspecialchars($row['nom_personnage']) . "</option>";
}

echo "</select>";
echo "<button type=\"submit\">Commencez la partie</button>";
echo "</form>";

echo "</body>";
echo "</html>";
?>
