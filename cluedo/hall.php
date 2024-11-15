<?php
$bdd_fichier = 'cluedo.db';
$sqlite = new SQLite3($bdd_fichier);
$piece = "hall";
// Requête SQL pour récupérer les noms des personnages
$sql = 'SELECT adj.id_piece, adj.nom_piece ';
$sql .= 'FROM pieces INNER JOIN portes ON portes.id_piece1=pieces.id_piece OR portes.id_piece2=pieces.id_piece ';
$sql .= 'INNER JOIN pieces AS adj ON portes.id_piece1=adj.id_piece OR portes.id_piece2=adj.id_piece ';
$sql .= 'WHERE adj.id_piece!=pieces.id_piece AND pieces.nom_piece LIKE :piece';

	/* Préparation de la requete et de ses paramètres */
$requete = $sqlite -> prepare($sql);	
$requete -> bindValue(':piece', $piece, SQLITE3_TEXT);
	
$result = $requete -> execute();	//Execution de la requête et récupération du résultat

// Vérifie si un choix a été effectué dans l'un des formulaires
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['choix'])) {
    $choix = $_POST['choix'];
}
// Début du contenu HTML
echo "<!DOCTYPE html>";
echo "<html lang=\"fr\">";
echo "<head>";
echo "<meta charset=\"UTF-8\">";
echo "<meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">";
echo "<title>$piece</title>";
echo "<link rel=\"stylesheet\" href=\"salles.css\">";
echo "</head>";
echo "<body>";

echo "<h1>Hall</h1>";
echo "<img src=\"cluedo image/$piece.jpg\" alt=\"Description de l'image\">";


while ($adj = $result->fetchArray(SQLITE3_ASSOC)) {
    echo "<form action=\"destination.php\" method=\"post\">"; // Destination de traitement
    echo "<input type=\"hidden\" name=\"id_piece\" value=\"{$adj['id_piece']}\">"; // Champ caché pour l'ID
    echo "<button type=\"submit\">" . htmlspecialchars($adj['nom_piece']);
    echo "</form>";
}



echo "</body>";
echo "</html>";
?>
