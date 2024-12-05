<?php
// Connexion à la base de données
$bdd_fichier = 'cluedo.db';
$sqlite = new SQLite3($bdd_fichier);

// Initialisation des variables
$piece = "Hall"; // Nom initial de la pièce
$personnage = $arme2 = $piecem = '';

// Traitement des choix envoyés par le formulaire ou AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['choix_personnage'], $_POST['choix_arme'], $_POST['choix_piece'])) {
        $choix_personnage = $_POST['choix_personnage'];
        $choix_arme = $_POST['choix_arme'];
        $choix_piece = $_POST['choix_piece'];

        $response = [];

        // Vérification des choix
        if ($choix_personnage === $personnage && $choix_arme === $arme2 && $choix_piece === $piecem) {
            $response = [
                'success' => true,
                'message' => "Bravo ! Vous avez trouvé la bonne combinaison : $personnage avec $arme2 dans $piecem !"
            ];
        } else {
            $response = [
                'success' => false,
                'message' => "Mauvaise combinaison. Ce n'est pas $choix_personnage avec $choix_arme dans $choix_piece. Essayez encore !"
            ];
        }

        // Retour AJAX
        echo json_encode($response);
        exit;
    }

    // Gestion AJAX des pièces adjacentes
    if (isset($_POST['nom_piece'])) {
        $piece = $_POST['nom_piece'];
        $sql = 'SELECT adj.nom_piece FROM pieces 
                INNER JOIN portes ON portes.id_piece1=pieces.id_piece OR portes.id_piece2=pieces.id_piece 
                INNER JOIN pieces AS adj ON portes.id_piece1=adj.id_piece OR portes.id_piece2=adj.id_piece 
                WHERE adj.nom_piece != pieces.nom_piece AND pieces.nom_piece LIKE :piece';

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
}

// Sélection aléatoire des éléments pour le mystère
$alea1 = rand(1, 6);
$personnage = $sqlite->querySingle("SELECT nom_personnage FROM personnages WHERE id_personnage = $alea1");

$alea2 = rand(1, 6);
$arme2 = $sqlite->querySingle("SELECT nom_arme FROM armes WHERE id_arme = $alea2");

$alea3 = rand(1, 8);
$piecem = $sqlite->querySingle("SELECT nom_piece FROM pieces WHERE id_piece = $alea3");

// Génération du HTML
echo "<!DOCTYPE html>";
echo "<html lang=\"fr\">";
echo "<head>";
echo "<meta charset=\"UTF-8\">";
echo "<meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">";
echo "<title>Cluedo - $piece</title>";
echo "<link rel=\"stylesheet\" href=\"salles.css\">";
echo "<script src=\"https://code.jquery.com/jquery-3.6.0.min.js\"></script>";
echo "</head>";
echo "<body>";

echo "<h1 id=\"piece-titre\">" . htmlspecialchars($piece) . "</h1>";
echo "<img id=\"piece-image\" src=\"cluedo image/$piece.jpg\" alt=\"Image de la pièce\">";

echo "<div id=\"boutons-pieces\">";
// Boutons des pièces adjacentes
$sql = 'SELECT adj.nom_piece FROM pieces 
        INNER JOIN portes ON portes.id_piece1=pieces.id_piece OR portes.id_piece2=pieces.id_piece 
        INNER JOIN pieces AS adj ON portes.id_piece1=adj.id_piece OR portes.id_piece2=adj.id_piece 
        WHERE adj.nom_piece != pieces.nom_piece AND pieces.nom_piece LIKE :piece';

$requete = $sqlite->prepare($sql);
$requete->bindValue(':piece', $piece, SQLITE3_TEXT);
$result = $requete->execute();

while ($adj = $result->fetchArray(SQLITE3_ASSOC)) {
    echo "<button class=\"piece-button\" data-name=\"" . htmlspecialchars($adj['nom_piece']) . "\">" . htmlspecialchars($adj['nom_piece']) . "</button>";
}
echo "</div>";

echo <<<HTML
<h2>Faites votre choix :</h2>
<form id="form-choix">
    <label for="choix_personnage">Personnage :</label>
    <select name="choix_personnage" id="choix_personnage">
HTML;

// Liste des personnages
$perso2 = $sqlite->query('SELECT nom_personnage FROM personnages');
while ($row = $perso2->fetchArray(SQLITE3_ASSOC)) {
    echo "<option value=\"" . htmlspecialchars($row['nom_personnage']) . "\">" . htmlspecialchars($row['nom_personnage']) . "</option>";
}

echo <<<HTML
    </select><br>

    <label for="choix_arme">Arme :</label>
    <select name="choix_arme" id="choix_arme">
HTML;

// Liste des armes
$arme3 = $sqlite->query('SELECT nom_arme FROM armes');
while ($row = $arme3->fetchArray(SQLITE3_ASSOC)) {
    echo "<option value=\"" . htmlspecialchars($row['nom_arme']) . "\">" . htmlspecialchars($row['nom_arme']) . "</option>";
}

echo <<<HTML
    </select><br>
    <button type="submit" id="valider">Valider</button>
</form>
<div id="resultat-message"></div>
<script>
    $(document).on('click', '.piece-button', function () {
        var nomPiece = $(this).data('name');

        // Efface le message d'erreur ou de réussite
        $('#resultat-message').empty();

        // Met à jour la pièce où se trouve le joueur
        $.post('', { nom_piece: nomPiece }, function (data) {
            var res = JSON.parse(data);
            $('#piece-titre').text(res.piece);
            $('#piece-image').attr('src', res.image);
            $('#boutons-pieces').empty();
            res.adjacentes.forEach(function (adj) {
                $('#boutons-pieces').append('<button class="piece-button" data-name="' + adj.nom_piece + '">' + adj.nom_piece + '</button>');
            });

            // Mise à jour de la variable JS pour la pièce actuelle
            window.currentPiece = res.piece;
        });
    });

    $('#form-choix').on('submit', function (e) {
        e.preventDefault();

        // Ajoute la pièce actuelle au formulaire pour la soumission
        var dataToSend = $(this).serialize() + '&choix_piece=' + encodeURIComponent(window.currentPiece);

        // Envoie la requête AJAX
        $.post('', dataToSend, function (data) {
            var res = JSON.parse(data);
            $('#resultat-message')
                .text(res.message)
                .css('color', res.success ? 'green' : 'red');
        });
    });

    // Initialisation de la pièce actuelle
    window.currentPiece = '<?php echo $piece; ?>';
</script>
</body>
</html>
HTML;
?>
