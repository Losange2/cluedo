<?php
$bdd_fichier = 'cluedo.db';
$sqlite = new SQLite3($bdd_fichier);
$piece = "hall"; // Nom initial de la pièce
$alea1 = rand(1,6);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $choix = $_POST["choix"];  // Récupère la valeur sélectionnée

    echo "Vous avez choisi : " . htmlspecialchars($choix);
}
$verif = $sqlite->query('SELECT id_personnage from personnages where nom_personnage = $result');
while ($alea1 == $verif)
{
    $alea1 = rand(1,6);
}

$perso = $sqlite->query('SELECT nom_personnage FROM personnages WHERE id_personnage = ' . $alea1);

// Parcours des résultats
if ($row = $perso->fetchArray(SQLITE3_ASSOC)) {
    // Vérifie si le nom du personnage existe
    if (!empty($row['nom_personnage'])) {
        $personnage = $row['nom_personnage']; // Stocke le nom dans une variable
    } else {
        $personnage = "Personnage introuvable"; // Valeur par défaut
    }
}



$alea2 = rand(1,6);
$arme = $sqlite->query('SELECT nom_arme from armes where id_arme = ' . $alea2);
if ($row = $arme->fetchArray(SQLITE3_ASSOC)) {
    // Condition pour vérifier si le nom du personnage existe
    if (!empty($row['nom_arme'])) {
        $arme2 = $row['nom_arme'];
    } else {
        echo  $arme2 = 'arme introuvable';
    }
}
$alea3 = rand(1,8);
$piecem = $sqlite->query('SELECT nom_piece from pieces where id_piece = ' . $alea3);

if ($row = $piecem->fetchArray(SQLITE3_ASSOC)) {
    // Condition pour vérifier si le nom du personnage existe
    if (!empty($row['nom_piece'])) {
        $piece2 = $row['nom_piece'];
    } else {
        $piece2 = 'piece introuvable';
    }
}

// Vérifie si une requête AJAX a été reçue
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nom_piece'])) {
    // Mise à jour de la pièce en fonction de la sélection
    $piece = $_POST['nom_piece'];

    // Requête SQL pour récupérer les noms des pièces adjacentes
    $sql = 'SELECT adj.nom_piece ';
    $sql .= 'FROM pieces INNER JOIN portes ON portes.id_piece1=pieces.id_piece OR portes.id_piece2=pieces.id_piece ';
    $sql .= 'INNER JOIN pieces AS adj ON portes.id_piece1=adj.id_piece OR portes.id_piece2=adj.id_piece ';
    $sql .= 'WHERE adj.nom_piece != pieces.nom_piece AND pieces.nom_piece LIKE :piece';

    $requete = $sqlite->prepare($sql);
    $requete->bindValue(':piece', $piece, SQLITE3_TEXT);
    $result = $requete->execute();

    // Générer la réponse en JSON
    $data = [];
    while ($adj = $result->fetchArray(SQLITE3_ASSOC)) {
        $data[] = $adj;
    }

    // Envoie une réponse JSON
    echo json_encode([
        'piece' => $piece,
        'image' => "cluedo image/$piece.jpg",
        'adjacentes' => $data
    ]);
    exit; // Fin de l'exécution pour ne pas inclure le reste du code
}

// Début du contenu HTML pour le chargement initial
echo "<!DOCTYPE html>";
echo "<html lang=\"fr\">";
echo "<head>";
echo "<meta charset=\"UTF-8\">";
echo "<meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">";
echo "<title>" . htmlspecialchars($piece) . "</title>";
echo "<link rel=\"stylesheet\" href=\"salles.css\">";
echo "<script src=\"https://code.jquery.com/jquery-3.6.0.min.js\"></script>"; // Inclure jQuery pour simplifier AJAX
echo "</head>";
echo "<body>";
echo "<h1 id=\"piece-titre\">" . htmlspecialchars($piece) . "</h1>";
echo "<img id=\"piece-image\" src=\"cluedo image/$piece.jpg\" alt=\"Description de l'image\">";

// Section pour les boutons des pièces adjacentes
echo "<div id=\"boutons-pieces\">";
$sql = 'SELECT adj.nom_piece ';
$sql .= 'FROM pieces INNER JOIN portes ON portes.id_piece1=pieces.id_piece OR portes.id_piece2=pieces.id_piece ';
$sql .= 'INNER JOIN pieces AS adj ON portes.id_piece1=adj.id_piece OR portes.id_piece2=adj.id_piece ';
$sql .= 'WHERE adj.nom_piece != pieces.nom_piece AND pieces.nom_piece LIKE :piece';

$requete = $sqlite->prepare($sql);
$requete->bindValue(':piece', $piece, SQLITE3_TEXT);
$result = $requete->execute();

while ($adj = $result->fetchArray(SQLITE3_ASSOC)) {
    echo "<button class=\"piece-button\" data-name=\"" . htmlspecialchars($adj['nom_piece']) . "\">" . htmlspecialchars($adj['nom_piece']) . "</button>";
}
echo "</div>";

// Script JavaScript pour gérer les clics et charger dynamiquement les données
echo <<<JS
<script>
$(document).on('click', '.piece-button', function () {
    var nomPiece = $(this).data('name'); // Récupère le nom de la pièce depuis le bouton
    $.ajax({
        type: 'POST',
        url: '', // La même page
        data: { nom_piece: nomPiece },
        success: function (response) {
            var data = JSON.parse(response); // Parse la réponse JSON
            $('#piece-titre').text(data.piece); // Met à jour le titre
            $('#piece-image').attr('src', data.image); // Met à jour l'image
            $('#boutons-pieces').empty(); // Vide les anciens boutons
            // Ajoute les nouveaux boutons
            data.adjacentes.forEach(function (adj) {
                $('#boutons-pieces').append(
                    '<button class="piece-button" data-name="' + adj.nom_piece + '">' + adj.nom_piece + '</button>'
                );
            });
        }
    });
});
</script>
JS;

echo "</body>";
echo "</html>";
?>