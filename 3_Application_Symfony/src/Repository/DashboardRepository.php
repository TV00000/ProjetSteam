<?php

namespace App\Repository;

use App\Entity\Dashboard;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Dashboard>
 */
class DashboardRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Dashboard::class);
    }


    // Appel AJAX - Select
    public function findDataSelectedGames()
    {
        // on récupère toutes les données des jeux selectionnés pour le dashboard
        $query = "SELECT *
                    FROM dashboard";
        
        $result = $this->getEntityManager()->getConnection()->executeQuery($query);
        return $result->fetchAll();
    }


    // Appel AJAX - Select & Unselect
    public function findDataTableGames($appID)
    {
        // utile pour les tests
        if(is_null($appID)){
            $appID = 330720;
        }

        // on récupère toutes les données associées au jeu dans la table game
        $query = "SELECT *
                    FROM games
                    WHERE app_id = $appID";
        
        $result = $this->getEntityManager()->getConnection()->executeQuery($query);
        //dd($result);
        return $result->fetchAll();
    }


    // Appel AJAX - Select
    public function insertIntoDashboardTable(Array $dataGameToInsert) : Bool
    {
        //insérer les données récupérées de la table games dans la table Dashboard.
        $query = "INSERT INTO dashboard (
                            app_id, 
                            game_name,
                            release_date, 
                            release_month,
                            release_year, 
                            pegi,
                            english_supported, 
                            header_img,
                            notes,
                            categories, 
                            publisher_class, 
                            publishers, 
                            developers, 
                            systems, 
                            copies_sold, 
                            revenue, 
                            price, 
                            avg_play_time, 
                            review_score, 
                            achievements, 
                            recommandations 
                                ) 
                            VALUES (
                            :app_id, 
                            :game_name,
                            :release_date, 
                            :release_month,
                            :release_year, 
                            :pegi,
                            :english_supported, 
                            :header_img,
                            :notes,
                            :categories, 
                            :publisher_class, 
                            :publishers, 
                            :developers, 
                            :systems, 
                            :copies_sold, 
                            :revenue, 
                            :price, 
                            :avg_play_time, 
                            :review_score, 
                            :achievements, 
                            :recommandations  
                                )";

        
        $result = $this->getEntityManager()->getConnection()->prepare($query)->execute($dataGameToInsert);
        $result->fetchAll();

        return TRUE;
    }



    public function truncateDashboardTable()
    {
        // on récupère toutes les données des jeux selectionnés pour le dashboard
        $query = "TRUNCATE TABLE dashboard";
        
        $result = $this->getEntityManager()->getConnection()->executeQuery($query);
        $result->fetchAll();
        
        return TRUE;
    }


    // Appel AJAX - Unselect
    public function deleteGameFromDashboardTable($appID)
    {
        // on récupère toutes les données des jeux selectionnés pour le dashboard
        $query = "DELETE FROM dashboard
                    WHERE app_id = $appID;";
        
        $result = $this->getEntityManager()->getConnection()->executeQuery($query);
        $result->fetchAll();

        return TRUE;
    }


    // Appel AJAX - Count
    public function countSelectedGames()
    {
        // on récupère toutes les données des jeux selectionnés pour le dashboard
        $query = "SELECT COUNT(*) AS account
                    FROM dashboard";
        
        $result = $this->getEntityManager()->getConnection()->executeQuery($query);
        return $result->fetchAll();
    }



    /////
    ///// Page Dashboard
    /////

    /// Barchart Age
    ///

    // Requête SQL
    //
    public function findDataBarChartAge()
    {
        $query = "SELECT 
                        pegi, 
                        count(*) as nbJeuxPegi
                    FROM dashboard
                    GROUP BY pegi";
        $result = $this->getEntityManager()->getConnection()->executeQuery($query);
        return $result->fetchAll();
    }

    // Mise en forme des données pour chart js
    //
    public function constructArray_DataBarChartAge()
    {
        // Appel de la requête SQL - obtention des données
        $data = $this->findDataBarChartAge();
        
        // création du tableau qui sera renvoyé
        $result = array();
        $labels = array();
        $datasets = array();

        // remplissage du tableau avec les données de la reqête
        // a modifier notamment tout ce qui concerne les couleurs -  modifier ici.
        foreach ($data as $key) {
            array_push($labels, strval($key["pegi"]));
            array_push($datasets, $key["nbJeuxPegi"]);
        };

        array_push($result, 
                [
                    'label'=>$labels,
                    'data'=>$datasets,
                    
                    'backgroundColor' => 'rgb(255, 99, 132)',
                    'borderColor' => 'rgb(255, 99, 132)',
                ]
            );
    
        return $result;
    }
   

    /// Barchart ReviewScore
    ///

    // Requête SQL
    //
    public function findDataBarChartReviewScore()
    {
        $query = "SELECT 
                        review_score, 
                        count(*) as nbJeuxReviewScore
                    FROM dashboard
                    GROUP BY review_score";
        $result = $this->getEntityManager()->getConnection()->executeQuery($query);
        return $result->fetchAll();
    }

    // Mise en forme des données pour chart js
    //
    public function constructArray_DataBarChartReviewScore()
    {
        // Appel de la requête SQL - obtention des données
        $data = $this->findDataBarChartReviewScore();
        
        // création du tableau qui sera renvoyé
        $result = array();
        $labels = array();
        $datasets = array();

        // remplissage du tableau avec les données de la reqête
        // a modifier notamment tout ce qui concerne les couleurs -  modifier ici.
        foreach ($data as $key) {
            array_push($labels, strval($key["review_score"]));
            array_push($datasets, $key["nbJeuxReviewScore"]);
        };

        array_push($result, 
                [
                    'label'=>$labels,
                    'data'=>$datasets,
                ]
            );
    
        return $result;
    }

    
    /// Top 3 - games
    ///

    // Requête SQL
    //
    public function findDataTopGamesInSelection()
    {
        $query = "SELECT 
                        review_score, header_img
                    FROM dashboard
                    ORDER BY review_score DESC LIMIT 3";
        $result = $this->getEntityManager()->getConnection()->executeQuery($query);
        return $result->fetchAll();
    }
}