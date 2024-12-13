<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Démarrer la session pour sauvegarder les éléments
session_start();

// Connexion à la base de données
$bdd_fichier = 'cluedo.db';
$sqlite = new SQLite3($bdd_fichier);

// Initialisation des variables
$piece = "Hall"; // Nom initial de la pièce
$personnage = $arme2 = $piecem = '';


// Vérifier si les données sont déjà présentes dans la session
if (!isset($_SESSION['personnage']) || !isset($_SESSION['arme2']) || !isset($_SESSION['piecem'])) {
    // Si elles ne sont pas définies, on les génère aléatoirement
    $alea1 = rand(1, 6);
    $_SESSION['personnage']  = ($sqlite->querySingle("SELECT nom_personnage FROM personnages WHERE id_personnage = $alea1"));

    $alea2 = rand(1, 6);
    $_SESSION['arme2'] = ($sqlite->querySingle("SELECT nom_arme FROM armes WHERE id_arme = $alea2"));

    $alea3 = rand(1, 8);
    $_SESSION['piecem'] = ($sqlite->querySingle("SELECT nom_piece FROM pieces WHERE id_piece = $alea3"));
}

// Récupérer les valeurs de session
$personnage = $_SESSION['personnage'];
$arme2 = $_SESSION['arme2'];
$piecem = $_SESSION['piecem'];

// Traitement des choix envoyés par le formulaire ou AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Si une hypothèse est faite, vérifier si les choix correspondent
    if (isset($_POST['choix_personnage'], $_POST['choix_arme'], $_POST['piece'])) {
        $choix_personnage = trim($_POST['choix_personnage']);
        $choix_arme = trim($_POST['choix_arme']);
        $piece = trim($_POST['piece']); // Cette pièce provient de la requête AJAX

        // Logs pour le débogage
        error_log("Personnage attendu : $personnage, reçu : $choix_personnage");
        error_log("Arme attendue : $arme2, reçue : $choix_arme");
        error_log("Pièce attendue : $piecem, reçue : $piece");

        // Vérification des choix
        if ($choix_personnage == $personnage && 
        $choix_arme == $arme2 && 
        $piece == $piecem) {
        // Si la réponse est correcte, réinitialiser la session pour une nouvelle partie
        session_unset(); // Réinitialiser toutes les variables de session
        session_destroy(); // Détruire la session pour forcer la création d'une nouvelle session
        session_start(); // Démarrer une nouvelle session
    
        // Générer de nouvelles valeurs pour la session
        $alea1 = rand(1, 6);
        $_SESSION['personnage'] = trim($sqlite->querySingle("SELECT nom_personnage FROM personnages WHERE id_personnage = $alea1"));
    
        $alea2 = rand(1, 6);
        $_SESSION['arme2'] = trim($sqlite->querySingle("SELECT nom_arme FROM armes WHERE id_arme = $alea2"));
    
        $alea3 = rand(1, 8);
        $_SESSION['piecem'] = trim($sqlite->querySingle("SELECT nom_piece FROM pieces WHERE id_piece = $alea3"));
    
        // Réponse JSON de succès
        echo json_encode([
            session_reset(),
            'success' => true,
            'redirect' => 'fin.php' // Redirection vers la page de fin
        ]);
        exit;
    } else {
        $errors = [];
    
        // Vérification des erreurs
        if ($choix_personnage != $personnage) {
            $errors[] = "Ce n'est pas $choix_personnage.";
        }
    
        if ($choix_arme != $arme2) {
            $errors[] = "Ce n'est pas $choix_arme.";
        }
    
        if ($piece != $piecem) {
            $errors[] = "Ce n'est pas $piece.";
        }
    
        // Si des erreurs existent, on en choisit une au hasard et on renvoie l'erreur dans la réponse JSON
        if (!empty($errors)) {
            $error_message = $errors[array_rand($errors)];
    
            // Réponse JSON avec l'erreur
            echo json_encode([
                'success' => false,
                'message' => $error_message
            ]);
            exit;
        }
    }
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

        if (!$result) {
            echo json_encode([ 
                'success' => false,
                'message' => 'Erreur de base de données.'
            ]);
            exit;
        }

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
echo "<p> Personnage : $personnage, Arme : $arme2, Pièce : $piecem";
echo "<img id=\"piece-image\" src=\"cluedo image/$piece.jpg\" alt=\"Image de la pièce\">";

echo "<div id=\"boutons-pieces\">";
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
            try {
                var res = JSON.parse(data);
                $('#piece-titre').text(res.piece);
                $('#piece-image').attr('src', res.image);
                $('#boutons-pieces').empty();
                res.adjacentes.forEach(function (adj) {
                    $('#boutons-pieces').append('<button class="piece-button" data-name="' + adj.nom_piece + '">' + adj.nom_piece + '</button>');
                });

                // Mise à jour de la variable JS pour la pièce actuelle
                window.currentPiece = res.piece;
            } catch (error) {
                console.error("Erreur lors du traitement de la réponse JSON :", error);
                $('#resultat-message').text("Une erreur s'est produite. Veuillez réessayer.")
                    .css('color', 'red');
            }
        }).fail(function(jqXHR, textStatus, errorThrown) {
            console.error("Erreur AJAX : " + textStatus + ", " + errorThrown);
            $('#resultat-message').text("Erreur lors de la récupération des données.")
                .css('color', 'red');
        });
    });

    $('#form-choix').on('submit', function (e) {
        e.preventDefault();

        // Ajoute la pièce actuelle au formulaire pour la soumission
        var dataToSend = $(this).serialize() + '&piece=' + encodeURIComponent(window.currentPiece);

        // Envoie la requête AJAX
        $.post('', dataToSend, function (data) {
            try {
                var res = JSON.parse(data);
                if (res.success && res.redirect) {
                    // Redirection vers la page spécifiée
                    window.location.href = res.redirect;
                } else if (res.message) {
                    $('#resultat-message')
                        .text(res.message)
                        .css('color', 'red');
                }
            } catch (error) {
                console.error("Erreur lors du traitement de la réponse JSON :", error);
                $('#resultat-message')
                    .text("Une erreur s'est produite. Veuillez réessayer.")
                    .css('color', 'red');
            }
        }).fail(function(jqXHR, textStatus, errorThrown) {
            console.error("Erreur AJAX : " + textStatus + ", " + errorThrown);
            $('#resultat-message')
                .text("Une erreur s'est produite avec la requête AJAX.")
                .css('color', 'red');
        });
    });

    // Initialisation de la pièce actuelle
    window.currentPiece = '$piece';
</script>

</body>
</html>
HTML;
?>
