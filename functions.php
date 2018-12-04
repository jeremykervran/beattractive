<?php

// ------------------------------------------------------------------------------------------------
// DEBUT API REST BEATTRACTIVE
// ------------------------------------------------------------------------------------------------

/**

Enregistrer les routes de l'API REST pour BeAttractive (Nanteuil les Meaux)

 */
add_action('rest_api_init', 'creasit_create_api_routes_beattractive');
function creasit_create_api_routes_beattractive()
{
    // l'adresse du endpoint sera www.nomdusite.fr/wp-json/beattractive/plugin_concerné/v2

    // route pour les posts des actualités
    register_rest_route('beattractive/actualites', '/v2', array(
        'methods' => 'GET',
        'callback' => 'creasit_requete_posts_beattractive',
    ));

    // route pour les posts de l'agenda
    register_rest_route('beattractive/agenda', '/v2', array(
        'methods' => 'GET',
        'callback' => 'creasit_requete_posts_beattractive',
    ));

    // route pour les posts de l'annuaire
    register_rest_route('beattractive/contacts', '/v2', array(
        'methods' => 'GET',
        'callback' => 'creasit_requete_posts_beattractive',
    ));

    // route pour les posts de la base doc
    register_rest_route('beattractive/base_documentaire', '/v2', array(
        'methods' => 'GET',
        'callback' => 'creasit_requete_posts_beattractive',
    ));

}

/**

Requête à envoyer au json de l'API REST pour BeAttractive (Nanteuil les Meaux)

- renvoie un post_type spécifique selon la route (définie dans le switch)
- post_types : agenda, base_documentaire, contacts, actualites (= post_type "post")
- post_status : publish, miseenavant, miseenavantfuture

 */

function creasit_requete_posts_beattractive($request_data)
{
    // Appel des variables globales wpdb
    global $wpdb;
    $prefix = $wpdb->prefix;

    // Récupération de l'url courante puis switch pour récupérer les données selon le post_status attendu
    $url_requete = $_SERVER['REQUEST_URI'];

    switch ($url_requete) {
        case '/wp-json/beattractive/actualites/v2':
            $post_type_a_recuperer = 'post';
            break;

        case '/wp-json/beattractive/agenda/v2':
            $post_type_a_recuperer = 'agenda';
            break;

        case '/wp-json/beattractive/contacts/v2':
            $post_type_a_recuperer = 'contacts';
            break;

        case '/wp-json/beattractive/base_documentaire/v2':
            $post_type_a_recuperer = 'base_documentaire';
            break;
    }

    // Construction des arguments de la requête, puis get_posts avec ces arguments
    $array_posts = $wpdb->get_results(
        "SELECT *
        FROM {$wpdb->prefix}posts
        WHERE post_type = '$post_type_a_recuperer'
        AND post_status IN ('publish', 'miseenavant', 'miseenavantfuture')
        ORDER BY post_date DESC"
    );

    // Si la requête renvoie quelque chose, on return les posts + les post_metas
    if (!empty($array_posts)) {

        // On initilaise un array vide qui va stocker nos posts et leurs metas
        $data = array();

        // On boucle sur les posts pour créer un array de datas
        foreach ($array_posts as $post) {

            // On récupère les infos du post et des metas liées

            // ID du post
            $postid = !empty($post->ID) ? $post->ID : '';

            // Titre
            $titre = get_the_title($postid);

            // Contenu
            $contenu = get_post($postid)->post_content;

            // Date de début de publication
            $publicationDebutRaw = $post->post_date;
            $publicationDebutToTime = strtotime($publicationDebutRaw);
            $publicationDebut = date("d/m/Y H:i", $publicationDebutToTime);

            // Date de fin de publication
            $publicationFinRaw = get_post_meta($postid, 'date_archiver_publication', true);
            $publicationFinToTime = strtotime($publicationFinRaw);
            if($publicationFinToTime != 0){
                $publicationFin = date("d/m/Y H:i", $publicationFinToTime);
            } else {
                $publicationFin = '';
            }

            // URL de l'image
            $attachement_id = get_post_thumbnail_id($postid);
            if(!empty($attachement_id)){
                $imageUrl = wp_get_attachment_url($attachement_id);
            } else {
                $imageUrl = '';
            }

            // Description
            $descriptionMeta = get_post_meta($postid, 'meta_description', true);
            $description = !empty($descriptionMeta) ? $descriptionMeta : '';

            // Adresse
            $adresseMeta = get_post_meta($postid, 'lieu_agenda', true);
            $adresse = !empty($adresseMeta) ? $adresseMeta : '';
            
            // Contact
            $contactMeta = get_post_meta($postid, 'contact_principal', true);
            $contact = !empty($contactMeta) ? $contactMeta : '';

            // Telephone
            $telephoneMeta = get_post_meta($postid, 'telephone', true);
            $telephone = !empty($telephoneMeta) ? $telephoneMeta : '';

            // Date de début
            $dateDebutString = get_post_meta($postid, 'date_de_debut_agenda', true);
            $dateDebutToTime = strtotime($dateDebutString);
            if($dateDebutToTime != 0){
                $dateDebut = date('d/m/Y',$dateDebutToTime);
            } else {
                $dateDebut = '';
            }

            // Date de fin
            $dateFinString = get_post_meta($postid, 'date_de_fin_agenda', true);
            $dateFinToTime = strtotime($dateFinString);
            if($dateFinToTime != 0) {
                $dateFin = date('d/m/Y', $dateFinToTime);
            } else {
                $dateFin = '';
            }

            // Heures de début et fin
            $heureDebut = str_replace('h', ':', get_post_meta($postid, 'heure_de_debut_timepicker_agenda', true));
            $heureFin = str_replace('h', ':', get_post_meta($postid, 'heure_de_fin_timepicker_agenda', true));

            // Horaires
            $horaire = get_post_meta($postid, 'horaires', true);

            // INFOS DU PLAN INTERACTIF LIE

            $id_point_plan = get_post_meta($postid, 'checkbox-point-cree-plan-interactif-publication', false);

            $coordonnees = get_post_meta($id_point_plan, 'coordonnee_du_point', true);
            if(!empty($coordonnees)) {
                $array_coordonnees = explode(',', $coordonnees);
                $latitude = $array_coordonnees[0];
                $longitude = $array_coordonnees[1];
            } else {
                $latitude = '';
                $longitude = '';
            }

            // Code postal
            $codePostalMeta = get_post_meta($id_point_plan[0], 'codepostal_du_point', true);
            $codePostal = !empty($codePostalMeta) ? $codePostalMeta : '';

            // Ville
            $villeMeta = get_post_meta($id_point_plan[0], 'ville_du_point_publication_', true);
            $ville = !empty($villeMeta) ? $villeMeta : '';

            // SI ON RENVOIE LES INFOS POUR L'ANNUAIRE DE CONTACTS
            if($post_type_a_recuperer == 'contacts') {
                $array_single_post = array(
                    'array_plan'     => $id_point_plan,
                    'typeAnnuaire'   => 'contacts',
                    'codeAnnuaire'   => $postid,
                    'annuaire'       => 'Annuaire de contacts',
                    'titre'          => $titre,
                    'contenu'        => $description,
                    // 'description'    => $description,
                    'adresse'        => $adresse,
                    'codePostal'     => $codePostal,
                    'ville'          => $ville,
                    'latitude'       => $latitude,
                    'longitude'      => $longitude,
                    'contact'        => $contact,
                    'imageUrl'       => $imageUrl,
                    
                    //? Non applicable à notre annuaire ?
                    // 'horaireFixe'    => array(
                    //         'debut'      => $dateDebut,
                    //         'fin'        => $dateFin,
                    //         'heureDebut' => $heureDebut,
                    //         'heureFin'   => $heureFin,
                    //     ),
                    'horaire'        => $horaire,
                );
            // SI ON RENVOIE LES INFOS POUR LES ACTUALITES
            } elseif ($post_type_a_recuperer == 'post') {
                $array_single_post = array(
                    'codeActualite'    => $postid,
                    'titre'            => html_entity_decode($titre),
                    'contenu'          => $contenu,
                    'description'      => $description,
                    'publicationDebut' => $publicationDebut,
                    'publicationFin'   => $publicationFin,
                    'imageUrl'         => $imageUrl
                );
            // SI ON RENVOIE LES INFOS POUR L'AGENDA
            } elseif ($post_type_a_recuperer == 'agenda') {
                $array_single_post = array(
                    'array_plan'     => $id_point_plan,
                    'codeAnnuaire'   => $postid,
                    'titre'          => $titre,
                    'contenu'        => $contenu,
                    'description'    => $description,
                    'typeAnnuaire'   => '', // TODO : récupérer catégorie
                    'adresse'        => $adresse,
                    'codePostal'     => $codePostal,
                    'ville'          => $ville,
                    'latitude'       => $latitude,
                    'longitude'      => $longitude,
                    'contact'        => $contact,
    
                    'horaireFixe'    => array(
                            'debut'      => $dateDebut,
                            'fin'        => $dateFin,
                            'heureDebut' => $heureDebut,
                            'heureFin'   => $heureFin,
                        ),
    
                );
            }

            // On passe l'array des datas de chaque post à l'array global des posts
            $data[] = $array_single_post;
        }

        // On passe $data dans rest_ensure_response pour s'assurer que c'est un format accepté par l'API REST
        return rest_ensure_response($data);
    }

    // Si la requête est vide, on renvoie un message d'erreur
    else {
            echo 'Aucun resultat pour cette requete !';
        }

    }

// ------------------------------------------------------------------------------------------------
// FIN API REST BEATTRACTIVE
// ------------------------------------------------------------------------------------------------
