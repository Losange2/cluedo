<?php
$bdd_fichier = 'cluedo.db';
$sqlite = new SQLite3($bdd_fichier);
$piece = "hall";

// Vérifie si une requête AJAX a été reçue
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_piece'])) {
    // Mise à jour de la pièce en fonction de la sélection
    $piece = $_POST['id_piece'];

    // Requête SQL pour récupérer les noms des pièces adjacentes
    $sql = 'SELECT adj.id_piece, adj.nom_piece ';
    $sql .= 'FROM pieces INNER JOIN portes ON portes.id_piece1=pieces.id_piece OR portes.id_piece2=pieces.id_piece ';
    $sql .= 'INNER JOIN pieces AS adj ON portes.id_piece1=adj.id_piece OR portes.id_piece2=adj.id_piece ';
    $sql .= 'WHERE adj.id_piece!=pieces.id_piece AND pieces.nom_piece LIKE :piece';

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
echo "<title>$piece</title>";
echo "<link rel=\"stylesheet\" href=\"salles.css\">";
echo "<script src=\"https://code.jquery.com/jquery-3.6.0.min.js\"></script>"; // Inclure jQuery pour simplifier AJAX
echo "</head>";
echo "<body>";

echo "<h1 id=\"piece-titre\">$piece</h1>";
echo "<img id=\"piece-image\" src=\"cluedo image/$piece.jpg\" alt=\"Description de l'image\">";

// Section pour les boutons des pièces adjacentes
echo "<div id=\"boutons-pieces\">";
$sql = 'SELECT adj.id_piece, adj.nom_piece ';
$sql .= 'FROM pieces INNER JOIN portes ON portes.id_piece1=pieces.id_piece OR portes.id_piece2=pieces.id_piece ';
$sql .= 'INNER JOIN pieces AS adj ON portes.id_piece1=adj.id_piece OR portes.id_piece2=adj.id_piece ';
$sql .= 'WHERE adj.id_piece!=pieces.id_piece AND pieces.nom_piece LIKE :piece';

$requete = $sqlite->prepare($sql);
$requete->bindValue(':piece', $piece, SQLITE3_TEXT);
$result = $requete->execute();

while ($adj = $result->fetchArray(SQLITE3_ASSOC)) {
    echo "<button class=\"piece-button\" data-id=\"{$adj['id_piece']}\">" . htmlspecialchars($adj['nom_piece']) . "</button>";
}
echo "</div>";

// Script JavaScript pour gérer les clics et charger dynamiquement les données
echo <<<JS
<script>
$(document).on('click', '.piece-button', function () {
    var idPiece = $(this).data('id'); // Récupère l'ID de la pièce depuis le bouton
    $.ajax({
        type: 'POST',
        url: '', // La même page
        data: { id_piece: idPiece },
        success: function (response) {
            var data = JSON.parse(response); // Parse la réponse JSON
            $('#piece-titre').text(data.piece); // Met à jour le titre
            $('#piece-image').attr('src', data.image); // Met à jour l'image
            $('#boutons-pieces').empty(); // Vide les anciens boutons
            // Ajoute les nouveaux boutons
            data.adjacentes.forEach(function (adj) {
                $('#boutons-pieces').append(
                    '<button class="piece-button" data-id="' + adj.id_piece + '">' + adj.nom_piece + '</button>'
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
