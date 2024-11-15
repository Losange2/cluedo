<?php
$bdd_fichier = 'cluedo.db';
$sqlite = new SQLite3($bdd_fichier);
$piece = "hall"; // Nom initial de la pièce

// Vérifie si le formulaire a été soumis et récupère la valeur choisie
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['choix'])) {
    $choix_personnage = $_POST['choix'];  // Récupère la valeur envoyée depuis le formulaire
    echo "Vous avez choisi le personnage : " . htmlspecialchars($choix_personnage);
}

// Assurez-vous que le choix est bien défini
if (isset($choix_personnage)) {
    $choix = $sqlite->escapeString($choix_personnage); // Échappe le contenu pour éviter les injections SQL
    $verif = $sqlite->query("SELECT id_personnage FROM personnages WHERE nom_personnage = '$choix'");

    if ($row = $verif->fetchArray(SQLITE3_ASSOC)) {
        if (!empty($row['id_personnage'])) {
            $verif2 = $row['id_personnage']; // L'ID du personnage est récupéré ici
        } else {
            echo 'Personnage introuvable'; // Message si le personnage n'est pas trouvé
        }
    }
}

$alea1 = rand(1, 6);

// Regénérer $alea1 si c'est le même que l'ID du personnage choisi
while ($alea1 == $verif2) {
    $alea1 = rand(1, 6);
}

// Utiliser $alea1 pour récupérer un personnage aléatoire
$perso = $sqlite->query('SELECT nom_personnage FROM personnages WHERE id_personnage = ' . $alea1);

if ($row = $perso->fetchArray(SQLITE3_ASSOC)) {
    if (!empty($row['nom_personnage'])) {
        $personnage = $row['nom_personnage'];
    } else {
        $personnage = "Personnage introuvable";
    }
}

$alea2 = rand(1, 6);
$arme = $sqlite->query('SELECT nom_arme from armes where id_arme = ' . $alea2);

if ($row = $arme->fetchArray(SQLITE3_ASSOC)) {
    if (!empty($row['nom_arme'])) {
        $arme2 = $row['nom_arme'];
    } else {
        echo $arme2 = 'arme introuvable';
    }
}

$alea3 = rand(1, 8);
$piecem = $sqlite->query('SELECT nom_piece from pieces where id_piece = ' . $alea3);

if ($row = $piecem->fetchArray(SQLITE3_ASSOC)) {
    if (!empty($row['nom_piece'])) {
        $piece2 = $row['nom_piece'];
    } else {
        $piece2 = 'piece introuvable';
    }
}

// Réponse AJAX pour les pièces adjacentes
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nom_piece'])) {
    $piece = $_POST['nom_piece'];
    $sql = 'SELECT adj.nom_piece FROM pieces INNER JOIN portes ON portes.id_piece1=pieces.id_piece OR portes.id_piece2=pieces.id_piece ';
    $sql .= 'INNER JOIN pieces AS adj ON portes.id_piece1=adj.id_piece OR portes.id_piece2=adj.id_piece ';
    $sql .= 'WHERE adj.nom_piece != pieces.nom_piece AND pieces.nom_piece LIKE :piece';

    $requete = $sqlite->prepare($sql);
    $requete->bindValue(':piece', $piece, SQLITE3_TEXT);
    $result = $requete->execute();

    $data = [];
    while ($adj = $result->fetchArray(SQLITE3_ASSOC)) {
        $data[] = $adj;
    }

    echo json_encode([
        'piece' => $piece,
        'image' => "cluedo image/$piece.jpg",
        'adjacentes' => $data
    ]);
    exit;
}

echo "<!DOCTYPE html>";
echo "<html lang=\"fr\">";
echo "<head>";
echo "<meta charset=\"UTF-8\">";
echo "<meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">";
echo "<title>" . htmlspecialchars($piece) . "</title>";
echo "<link rel=\"stylesheet\" href=\"salles.css\">";
echo "<script src=\"https://code.jquery.com/jquery-3.6.0.min.js\"></script>";
echo "</head>";
echo "<body>";
echo "<p>Personnage choisi: $choix_personnage</p>";
echo "<p>Personnage généré: $personnage</p>";
echo "<h1 id=\"piece-titre\">" . htmlspecialchars($piece) . "</h1>";
echo "<img id=\"piece-image\" src=\"cluedo image/$piece.jpg\" alt=\"Description de l'image\">";

echo "<div id=\"boutons-pieces\">";
$sql = 'SELECT adj.nom_piece FROM pieces INNER JOIN portes ON portes.id_piece1=pieces.id_piece OR portes.id_piece2=pieces.id_piece ';
$sql .= 'INNER JOIN pieces AS adj ON portes.id_piece1=adj.id_piece OR portes.id_piece2=adj.id_piece ';
$sql .= 'WHERE adj.nom_piece != pieces.nom_piece AND pieces.nom_piece LIKE :piece';

$requete = $sqlite->prepare($sql);
$requete->bindValue(':piece', $piece, SQLITE3_TEXT);
$result = $requete->execute();

while ($adj = $result->fetchArray(SQLITE3_ASSOC)) {
    echo "<button class=\"piece-button\" data-name=\"" . htmlspecialchars($adj['nom_piece']) . "\">" . htmlspecialchars($adj['nom_piece']) . "</button>";
}
echo "</div>";

echo <<<JS
<script>
$(document).on('click', '.piece-button', function (event) {
    event.preventDefault();
    var nomPiece = $(this).data('name');
    $.ajax({
        type: 'POST',
        url: '',
        data: { nom_piece: nomPiece },
        success: function (response) {
            var data = JSON.parse(response);
            $('#piece-titre').text(data.piece);
            $('#piece-image').attr('src', data.image);
            $('#boutons-pieces').empty();
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
