<?php

namespace App\Repository;

use App\Entity\Games;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Games>
 */
class GamesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Games::class);
    }


    /////
    ///// Page Analysis
    /////

    /// Premier graphique
    ///

    // Requête SQL
    //
    public function findDataGraphFiveDim()
    {
        $query = "SELECT 
                        label, 
                        COUNT(*) AS nbGames,
                        SUM(revenue) AS sommeRevenue, 
                        SUM(copies_sold) AS sommeCopiesSold, 
                        AVG(review_score) AS avgReviewScore
                    FROM games
                        JOIN link_games_genres USING (app_id)
	                    JOIN genres USING(genres_id)
                    GROUP BY label";
        $result = $this->getEntityManager()->getConnection()->executeQuery($query);
        return $result->fetchAll();
    }

    // Mise en forme des données pour chart js
    //
    public function constructArray_DataGraphFiveDim ()
    {
        // Appel de la requête SQL - obtention des données
        $data = $this->findDataGraphFiveDim();

        // création des variable pour la partie visuelle (couleurs)
        $background_red = 50;
        $background_green = 60;
        $background_blue = 100;
        $border_red = 80;
        $border_green = 10;
        $border_blue = 120;
        
        // création du tableau qui sera renvoyé
        $result = array();

        // remplissage du tableau avec les données de la reqête
        // a modifier notamment tout ce qui concerne les couleurs -  modifier ici.
        foreach ($data as $key) {
            $color_ratio = intval($key["avgReviewScore"]);

            array_push($result, 
                [
                    'label'=>$key["label"],
                    'data'=>[
                        [
                        'x'=>(int) $key["sommeRevenue"],
                        'y'=>(int) $key["sommeCopiesSold"],
                        'r'=>intval($key["nbGames"]/1500),
                        ],
                    ],
                    // modifier ici.
                    'backgroundColor'=> "rgb(".$background_red+$color_ratio.",".$background_green+$color_ratio.",".$background_blue+$color_ratio.")",
                    'borderColor' => "rgb(".$border_red+$color_ratio.",".$border_green+$color_ratio.",".$border_blue+$color_ratio.")",
                ]
            );
        };
    
        return $result;
    }

    
    /// Second graphique
    ///

    // Requêtes SQL
    //
    
    // Pour obtenir un array de mois/années - condition sur la periode
    public function findData_Period($period)
    {
        if ($period === 'year'){
            $query = "SELECT DISTINCT release_year
                        FROM games
                        ORDER BY release_year";
            
            }elseif($period === 'month'){
                $query = "SELECT DISTINCT release_month
                        FROM games
                        ORDER BY release_month";
                
                }
        $result = $this->getEntityManager()->getConnection()->executeQuery($query);
        return $result->fetchAll();
    }

    
    // Pour obtenir un array des genres
    public function findData_Genre()
    {
        $query = "SELECT DISTINCT label
                    FROM genres
                    ORDER BY label";
            
        $result = $this->getEntityManager()->getConnection()->executeQuery($query);
        return $result->fetchAll();
    }

    
    // Reqête principale - condition sur la periode
    // Utilisation du code de création des vues sur la BD test
    public function findDataGraphYearGenre($period)
    {
        if ($period === 'year'){
            $query = "SELECT Label, release_year, SUM(copies_sold) AS sommeCopiesSold
                    FROM games 
                        JOIN link_games_genres USING (app_id)
                        JOIN genres USING(genres_id)
                    GROUP BY genres_id, release_year
                    ORDER BY release_year, Label";
            
            }elseif($period === 'month'){
                $query = "SELECT Label, release_month, SUM(copies_sold) AS sommeCopiesSold
                    FROM games 
                        JOIN link_games_genres USING (app_id)
                        JOIN genres USING(genres_id)
                    GROUP BY genres_id, release_month
                    ORDER BY release_month, Label";
                
                }
        
            
        $result = $this->getEntityManager()->getConnection()->executeQuery($query);
        return $result->fetchAll();
    }

    
    // Mise en forme des données pour chart js
    //
    public function constructArray_DataGraphYearGenre ($period)
    {
        //$period = "year";
        //$period = "month";

        // Appel des requêtes SQL précédentes
        $data_period = $this->findData_Period($period);
        $data_genres = $this->findData_Genre();
        $data_copiesSold = $this->findDataGraphYearGenre($period);
     
        // Création d'objets vides - présents dans le resultats
        $labels = array();
        $datasets = array();
        
        // Nt :  voir documentation chart js - structure des données d'un chart LINETYPE
        // creation des données de l'axes x du LINE CHART
        foreach ($data_period as $year_month) {
            array_push($labels, $year_month['release_'.$period]);
        };

        // creation des données du graphes - a partir des données des requêtes
        foreach ($data_genres as $genre) {
            $datasets_data = array();
            foreach ($data_copiesSold as $line) {
                    if ($line['Label']===$genre["label"]){
                        array_push($datasets_data, $line["sommeCopiesSold"]);
                    };
                }
            
            //vérification de la longueur du tableau de données pour chaque genre
            // Si le tableau n'est pas complet, on rajoute des 0 au debut 
            //(les données eco sont connues pour tous les genres dans les dernière années mais pas forcement 
            //dans les années 90-2000)
            if (count($datasets_data)<count($labels)){
                $add_zero_limit = count($labels)-count($datasets_data);
                //dd($add_zero_limit);
                for ($i = 0; $i < $add_zero_limit; $i++) {
                    array_splice($datasets_data, 0,0,0);
                }
                //dd($datasets_data);
            };
            
            // intégration des données dans les objets de résultats $labels et $datasets
            array_push($datasets, [
                'label'=> $genre["label"],
                'data'=> $datasets_data,
            
            ]);
        };

        $result = ["label" => $labels, "datasets" =>$datasets];
        return $result;
    }
}