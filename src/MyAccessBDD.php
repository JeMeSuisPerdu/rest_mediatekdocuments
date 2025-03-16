<?php
include_once("AccessBDD.php");

/**
 * Classe de construction des requêtes SQL
 * hérite de AccessBDD qui contient les requêtes de base
 * Pour ajouter une requête :
 * - créer la fonction qui crée une requête (prendre modèle sur les fonctions 
 *   existantes qui ne commencent pas par 'traitement')
 * - ajouter un 'case' dans un des switch des fonctions redéfinies 
 * - appeler la nouvelle fonction dans ce 'case'
 */
class MyAccessBDD extends AccessBDD {
	    
    /**
     * Constructeur qui appelle celui de la classe mère
     * @throws \Exception Si une erreur survient lors de l'initialisation
     */
    public function __construct(){
        try{
            parent::__construct();
        }catch(\Exception $e){
            throw $e;
        }
    }

    /**
     * Demande de recherche
     * @param string $table Nom de la table à interroger
     * @param array|null $champs Nom et valeur de chaque champ pour la recherche
     * @return array|null Tuples du résultat de la requête ou null si erreur
     * @override
     */	
    protected function traitementSelect(string $table, ?array $champs) : ?array{
        switch($table){
            case "livre" :
                return $this->selectAllLivres();
            case "dvd" :
                return $this->selectAllDvd();
            case "revue" :
                return $this->selectAllRevues();
            case "exemplaire" :
                return $this->selectExemplairesRevue($champs);
            case "infocommandedocument":
                return $this->selectInfoLivreCommande($champs);
            case "commandeabonnementinfo":
                return $this->selectInfoAbonnement($champs);
            case "infolistefinabonnement":
                return $this->selectListeFinAbonnement();
            case "infoUser":
                return $this->selectUser($champs);
            case "genre" :
            case "public" :
            case "rayon" :
            case "etat" :
                // select portant sur une table contenant juste id et libelle
                return $this->selectTableSimple($table);
            case "allsuivi":
                return $this->selectAllEtatsSuivi();
            case "" :
                // return $this->uneFonction(parametres);
            default:
                // cas général
                return $this->selectTuplesOneTable($table, $champs);
        }
    }

    /**
     * Demande d'ajout (insert)
     * @param string $table Nom de la table où insérer les données
     * @param array|null $champs Nom et valeur de chaque champ à insérer
     * @return int|null Nombre de tuples ajoutés ou null si erreur
     * @override
     */	
    protected function traitementInsert(string $table, ?array $champs) : ?int{
        switch($table){ 
            case "commandeDocAjout":
                return $this->insertLivreDvdCommande($champs);
            case "commandeAbonnementAjout":
                return $this->insertCommandeAbonnement($champs);                    
            default:
                // cas général
                return $this->insertOneTupleOneTable($table, $champs);	
        }
    }
    
    /**
     * Demande de modification (update)
     * @param string $table Nom de la table à modifier
     * @param string|null $id Identifiant du tuple à modifier
     * @param array|null $champs Nom et valeur de chaque champ à modifier
     * @return int|null Nombre de tuples modifiés ou null si erreur
     * @override
     */	
    protected function traitementUpdate(string $table, ?string $id, ?array $champs) : ?int{
        switch($table){
                case "commandeDocModifier" :
                    return $this->modifierDetailCommande($champs);
                case "commandeDocSupprimer" :
                    return $this->supprimerDetailCommande($champs);
                case "commandeAbonnementModifier":
                    return $this->modifierDetailAbonnement($champs);
                case "commandeAbonnementSupprimer":
                    return $this->supprimerDetailAbonnement($champs);
                default:
                    // cas général
                    return $this->updateOneTupleOneTable($table, $id, $champs);
            }
        }
        
        /**
         * Demande de suppression (delete)
         * @param string $table Nom de la table où supprimer les données
         * @param array|null $champs Nom et valeur de chaque champ pour la suppression
         * @return int|null Nombre de tuples supprimés ou null si erreur
         * @override
         */	
    protected function traitementDelete(string $table, ?array $champs) : ?int{
            switch($table){
                default:
                    // cas général
                    return $this->deleteTuplesOneTable($table, $champs);
        }
    }
        
    /**
     * Récupère les tuples d'une seule table
     * @param string $table Nom de la table à interroger
     * @param array|null $champs Nom et valeur de chaque champ pour la recherche
     * @return array|null Résultat de la requête ou null si erreur
     */
    private function selectTuplesOneTable(string $table, ?array $champs) : ?array{
        if(empty($champs)){
            // tous les tuples d'une table
            $requete = "select * from $table;";
            return $this->conn->queryBDD($requete);
        }else{
            // tuples spécifiques d'une table
            $requete = "select * from $table where ";
            foreach ($champs as $key => $value){
                $requete .= "$key=:$key and ";
            }
            // (enlève le dernier and)
            $requete = substr($requete, 0, strlen($requete)-5);
            return $this->conn->queryBDD($requete, $champs);
        }
    }

    /**
     * Demande d'ajout (insert) d'un tuple dans une table
     * @param string $table Nom de la table où insérer les données
     * @param array|null $champs Nom et valeur de chaque champ à insérer
     * @return int|null Nombre de tuples ajoutés (0 ou 1) ou null si erreur
     */	
    private function insertOneTupleOneTable(string $table, ?array $champs) : ?int{
        if(empty($champs)){
            return null;
        }
        // construction de la requête
        $requete = "insert into $table (";
        foreach ($champs as $key => $value){
            $requete .= "$key,";
        }
        // (enlève la dernière virgule)
        $requete = substr($requete, 0, strlen($requete)-1);
        $requete .= ") values (";
        foreach ($champs as $key => $value){
            $requete .= ":$key,";
        }
        // (enlève la dernière virgule)
        $requete = substr($requete, 0, strlen($requete)-1);
        $requete .= ");";
        return $this->conn->updateBDD($requete, $champs);
    }

    /**
     * Demande de modification (update) d'un tuple dans une table
     * @param string $table Nom de la table à modifier
     * @param string|null $id Identifiant du tuple à modifier
     * @param array|null $champs Nom et valeur de chaque champ à modifier
     * @return int|null Nombre de tuples modifiés (0 ou 1) ou null si erreur
     */	
    private function updateOneTupleOneTable(string $table, ?string $id, ?array $champs) : ?int {
        if(empty($champs)){
            return null;
        }
        if(is_null($id)){
            return null;
        }
        // construction de la requête
        $requete = "update $table set ";
        foreach ($champs as $key => $value){
            $requete .= "$key=:$key,";
        }
        // (enlève la dernière virgule)
        $requete = substr($requete, 0, strlen($requete)-1);
        $champs["id"] = $id;
        $requete .= " where id=:id;";
        return $this->conn->updateBDD($requete, $champs);
    }

    /**
     * Demande de suppression (delete) d'un ou plusieurs tuples dans une table
     * @param string $table Nom de la table où supprimer les données
     * @param array|null $champs Nom et valeur de chaque champ pour la suppression
     * @return int|null Nombre de tuples supprimés ou null si erreur
     */
    private function deleteTuplesOneTable(string $table, ?array $champs) : ?int{
        if(empty($champs)){
            return null;
        }
        // construction de la requête
        $requete = "delete from $table where ";
        foreach ($champs as $key => $value){
            $requete .= "$key=:$key and ";
        }
        // (enlève le dernier and)
        $requete = substr($requete, 0, strlen($requete)-5);
        return $this->conn->updateBDD($requete, $champs);
    }

    /**
     * Récupère toutes les lignes d'une table simple (qui contient juste id et libelle)
     * @param string $table Nom de la table à interroger
     * @return array|null Résultat de la requête ou null si erreur
     */
    private function selectTableSimple(string $table) : ?array{
        $requete = "select * from $table order by libelle;";
        return $this->conn->queryBDD($requete);
    }

    /**
     * Récupère toutes les lignes de la table Livre et les tables associées
     * @return array|null Résultat de la requête ou null si erreur
     */
    private function selectAllLivres() : ?array{
        $requete = "Select l.id, l.ISBN, l.auteur, d.titre, d.image, l.collection, ";
        $requete .= "d.idrayon, d.idpublic, d.idgenre, g.libelle as genre, p.libelle as lePublic, r.libelle as rayon ";
        $requete .= "from livre l join document d on l.id=d.id ";
        $requete .= "join genre g on g.id=d.idGenre ";
        $requete .= "join public p on p.id=d.idPublic ";
        $requete .= "join rayon r on r.id=d.idRayon ";
        $requete .= "order by titre ";
        return $this->conn->queryBDD($requete);
    }

    /**
     * Récupère toutes les lignes de la table DVD et les tables associées
     * @return array|null Résultat de la requête ou null si erreur
     */
    private function selectAllDvd() : ?array{
        $requete = "Select l.id, l.duree, l.realisateur, d.titre, d.image, l.synopsis, ";
        $requete .= "d.idrayon, d.idpublic, d.idgenre, g.libelle as genre, p.libelle as lePublic, r.libelle as rayon ";
        $requete .= "from dvd l join document d on l.id=d.id ";
        $requete .= "join genre g on g.id=d.idGenre ";
        $requete .= "join public p on p.id=d.idPublic ";
        $requete .= "join rayon r on r.id=d.idRayon ";
        $requete .= "order by titre ";
        return $this->conn->queryBDD($requete);
    }

    /**
     * Récupère toutes les lignes de la table Revue et les tables associées
     * @return array|null Résultat de la requête ou null si erreur
     */
    private function selectAllRevues() : ?array{
        $requete = "Select l.id, l.periodicite, d.titre, d.image, l.delaiMiseADispo, ";
        $requete .= "d.idrayon, d.idpublic, d.idgenre, g.libelle as genre, p.libelle as lePublic, r.libelle as rayon ";
        $requete .= "from revue l join document d on l.id=d.id ";
        $requete .= "join genre g on g.id=d.idGenre ";
        $requete .= "join public p on p.id=d.idPublic ";
        $requete .= "join rayon r on r.id=d.idRayon ";
        $requete .= "order by titre ";
        return $this->conn->queryBDD($requete);
    }

    /**
     * Récupère tous les exemplaires d'une revue
     * @param array|null $champs Nom et valeur de chaque champ pour la recherche
     * @return array|null Résultat de la requête ou null si erreur
     */
    private function selectExemplairesRevue(?array $champs) : ?array{
        if(empty($champs)){
            return null;
        }
        if(!array_key_exists('id', array: $champs)){
            return null;
        }
        $champNecessaire['id'] = $champs['id'];
        $requete = "Select e.id, e.numero, e.dateAchat, e.photo, e.idEtat ";
        $requete .= "from exemplaire e join document d on e.id=d.id ";
        $requete .= "where e.id = :id ";
        $requete .= "order by e.dateAchat DESC";
        return $this->conn->queryBDD($requete, $champNecessaire);
    }

    /**
     * Récupère toutes les infos d'une commande
     * @param array|null $champs Nom et valeur de chaque champ pour la recherche
     * @return array|null Résultat de la requête ou null si erreur
     */
    private function selectInfoLivreCommande(?array $champs) : ?array {
        if (empty($champs) || !array_key_exists('idLivreDvd', $champs) || empty($champs['idLivreDvd'])) {
            return null;
        }
    
        // Récupérer l'idLivreDvd
        $champNecessaire['idLivreDvd'] = $champs['idLivreDvd'];

        // Modifie la requête pour inclure idLivreDvd dans les résultats
        $requete = "SELECT c.id, c.dateCommande, c.montant, cd.nbExemplaire, s.id AS idSuivi, s.etat, cd.idLivreDvd ";  // Ajouter cd.idLivreDvd
        $requete .= "FROM commande c ";
        $requete .= "JOIN commandedocument cd ON c.id = cd.id ";
        $requete .= "JOIN suivi s ON cd.idSuivi = s.id ";
        $requete .= "WHERE cd.idLivreDvd = :idLivreDvd ";
        $requete .= "ORDER BY c.dateCommande DESC";

        // Exécuter la requête et retourner les résultats
        return $this->conn->queryBDD($requete, $champNecessaire);
    }

    /**
     * Renvoie tous les champs dans la table suivi
     * @return array|null Résultat de la requête ou null si erreur
     */
    private function selectAllEtatsSuivi() : ?array {
        // Requête pour récupérer tous les états distincts de la table suivi
        $requete = "SELECT id, etat FROM suivi";
        
        // Exécuter la requête et retourner les résultats
        return $this->conn->queryBDD($requete, []);
    }

    /**
     * Permet d'ajouter une commande dans la bdd
     * @param array|null $champs Nom et valeur de chaque champ à insérer
     * @return bool True si l'insertion a réussi, false sinon
     */
    public function insertLivreDvdCommande(?array $champs): bool {
        // Vérification de la présence de tous les champs nécessaires et qu'ils ne sont pas vides
        if ($champs === null || 
            !isset($champs['DateCommande']) || empty($champs['DateCommande']) || 
            !isset($champs['Montant']) || empty($champs['Montant']) || 
            !isset($champs['NbExemplaire']) || empty($champs['NbExemplaire']) || 
            !isset($champs['IdLivreDvd']) || empty($champs['IdLivreDvd']) || 
            !isset($champs['IdSuivi']) || empty($champs['IdSuivi']) || 
            !isset($champs['Etat']) || empty($champs['Etat'])) {
            
            return false;
        }
        // Requête pour insérer une commande dans la table "commande"
        $requeteCommande = "INSERT INTO commande (dateCommande, montant) VALUES (:dateCommande, :montant)";
        $paramsCommande = [
            'dateCommande' => $champs['DateCommande'],
            'montant' => $champs['Montant']
        ];
        $resultCommande = $this->conn->updateBDD($requeteCommande, $paramsCommande);
    
        // Requête pour insérer une ligne dans la table "commandedocument"
        $requeteCommandeDocument = "INSERT INTO commandedocument (nbExemplaire, idLivreDvd, idSuivi) VALUES (:nbExemplaire, :idLivreDvd, :idSuivi)";
        $paramsCommandeDocument = [
            'nbExemplaire' => $champs['NbExemplaire'],
            'idLivreDvd' => $champs['IdLivreDvd'],
            'idSuivi' => $champs['IdSuivi']
        ];
        $resultCommandeDocument = $this->conn->updateBDD($requeteCommandeDocument, $paramsCommandeDocument);
    
        // Si les deux requêtes ont réussi, on retourne true
        if ($resultCommande > 0 && $resultCommandeDocument > 0) {
            return true;
        } else {
            // Si une des requêtes échoue, on retourne false
            return false;
        }
    }

    /**
     * Permet de modifier une commande dans la bdd
     * @param array|null $champs Nom et valeur de chaque champ à modifier
     * @return bool True si la modification a réussi, false sinon
     */
    public function modifierDetailCommande(?array $champs): bool{
        if ($champs === null || 
            !isset($champs['Id']) || 
            empty($champs['Id'])) {
        return false;
        }
        // Requête UPDATE pour la table "commande"
        $requeteCommande = "UPDATE commande SET dateCommande = :dateCommande, montant = :montant WHERE id = :id";
        $paramsCommande = [
            'dateCommande' => $champs['DateCommande'],
            'montant' => $champs['Montant'],
            'id' => $champs['Id']
        ];
        $resultCommande = $this->conn->updateBDD($requeteCommande, $paramsCommande);
    
        // Requête UPDATE pour la table "commandedocument"
        $requeteCommandeDocument = "UPDATE commandedocument SET nbExemplaire = :nbExemplaire, idLivreDvd = :idLivreDvd, idSuivi = :idSuivi WHERE id = :id";
        $paramsCommandeDocument = [
            'nbExemplaire' => $champs['NbExemplaire'],
            'idLivreDvd' => $champs['IdLivreDvd'],
            'idSuivi' => $champs['IdSuivi'],
            'id' => $champs['Id']

        ];
        $resultCommandeDocument = $this->conn->updateBDD($requeteCommandeDocument, $paramsCommandeDocument);
    
        // Si les deux requêtes ont réussi = true
        if ($resultCommande > 0 && $resultCommandeDocument > 0) {
            return true;
        } else {
            // Si une des requêtes échoue = false
            return false;
        }
    }
    
    /**
     * Permet de supprimer une commande dans la bdd
     * @param array|null $champs Nom et valeur de chaque champ pour la suppression
     * @return bool True si la suppression a réussi, false sinon
     */
    public function supprimerDetailCommande(?array $champs): bool {
        if ($champs === null || 
            !isset($champs['Id']) || 
            empty($champs['Id'])) {
            return false;
        }
    $requeteCommandeDocument = "DELETE FROM commandedocument WHERE id = :id";
    $paramsCommandeDocument = [
        'id' => $champs['Id']
    ];
    $resultCommandeDocument = $this->conn->updateBDD($requeteCommandeDocument, $paramsCommandeDocument);
    
    $requeteCommande = "DELETE FROM commande WHERE id = :id";
    $paramsCommande = [
        'id' => $champs['Id']
    ];
    $resultCommande = $this->conn->updateBDD($requeteCommande, $paramsCommande);
    

        return $resultCommande + $resultCommandeDocument;
    
    }
    
    /**
     * Récupère toutes les infos d'un abonnement
     * @param array|null $champs Nom et valeur de chaque champ pour la recherche
     * @return array|null Résultat de la requête ou null si erreur
     */
    private function selectInfoAbonnement(?array $champs) : ?array {
        if (empty($champs) || 
            !array_key_exists('idRevue', $champs) || 
            empty($champs['idRevue'])) {
        return null;
        }
        // Récupérer l'idRevue
        $champNecessaire['idRevue'] = $champs['idRevue'];

        $requete = "SELECT c.id, c.dateCommande, c.montant, cd.dateFinAbonnement, cd.idRevue ";
        $requete .= "FROM commandeabonnement c ";
        $requete .= "JOIN abonnement cd ON c.id = cd.id ";
        $requete .= "WHERE cd.idRevue = :idRevue ";
        $requete .= "ORDER BY c.dateCommande DESC";
        // Exécuter la requête et retourner les résultats
        return $this->conn->queryBDD($requete, $champNecessaire);
    }

    /**
     * Permet d'ajouter une commande d'abonnement dans la bdd
     * @param array|null $champs Nom et valeur de chaque champ à insérer
     * @return bool|null True si l'insertion a réussi, false sinon, null si erreur
     */
    private function insertCommandeAbonnement(?array $champs): ?bool {
    // Vérification de la présence de tous les champs nécessaires et de leur non-nullité
        if ($champs === null || 
        !isset($champs['IdRevue']) || 
        !isset($champs['DateCommande']) || 
        !isset($champs['Montant']) || 
        !isset($champs['DateFinAbonnement']) || 
        empty($champs['IdRevue']) ||
        empty($champs['DateCommande']) || 
        empty($champs['Montant']) || 
        empty($champs['DateFinAbonnement'])) {
        // Si l'un des champs est manquant ou nul, on ne fait rien et retourne null
        return null;
    }
    
        // Si tous les champs sont présents et non nuls, on procède à l'insertion
        // Requête pour insérer une commande dans la table "commande"
        $requeteCommande = "INSERT INTO commandeabonnement (dateCommande, montant) VALUES (:dateCommande, :montant)";
        $paramsCommande = [
            'dateCommande' => $champs['DateCommande'],
            'montant' => $champs['Montant']
        ];
        $resultCommande = $this->conn->updateBDD($requeteCommande, $paramsCommande);
    
        // Requête pour insérer une ligne dans la table "commandedocument"
        $requeteCommandeDocument = "INSERT INTO abonnement (dateFinAbonnement, idRevue) VALUES (:dateFinAbonnement, :idRevue)";
        $paramsCommandeDocument = [
            'dateFinAbonnement' => $champs['DateFinAbonnement'],
            'idRevue' => $champs['IdRevue']
        ];
        $resultCommandeDocument = $this->conn->updateBDD($requeteCommandeDocument, $paramsCommandeDocument);
    
        // Si les deux requêtes ont réussi, on retourne true
        if ($resultCommande > 0 && $resultCommandeDocument > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Permet de supprimer une commande d'abonnement dans la bdd
     * @param array|null $champs Nom et valeur de chaque champ pour la suppression
     * @return bool True si la suppression a réussi, false sinon
     */
    public function supprimerDetailAbonnement(?array $champs): bool {
        if ($champs === null || 
            !isset($champs['Id']) ||
            empty($champs['Id'])) {
            return false;
        }
    
    
    $requeteCommandeDocument = "DELETE FROM abonnement WHERE id = :id";
    $paramsCommandeDocument = [
        'id' => $champs['Id']
    ];
    $resultCommandeDocument = $this->conn->updateBDD($requeteCommandeDocument, $paramsCommandeDocument);
    
    $requeteCommande = "DELETE FROM commandeabonnement WHERE id = :id";
    $paramsCommande = [
        'id' => $champs['Id']
    ];
    $resultCommande = $this->conn->updateBDD($requeteCommande, $paramsCommande);
    

        return $resultCommande + $resultCommandeDocument;
    
    }

    /**
     * Permet de modifier une commande d'abonnement dans la bdd
     * @param array|null $champs Nom et valeur de chaque champ à modifier
     * @return bool True si la modification a réussi, false sinon
     */
    public function modifierDetailAbonnement(?array $champs): bool {
        if ($champs === null || 
            !isset($champs['Id']) || 
            empty($champs['Id']) || 
            !isset($champs['IdRevue']) || 
            empty($champs['IdRevue']) || 
            !isset($champs['DateCommande']) || 
            empty($champs['DateCommande']) || 
            !isset($champs['Montant']) || 
            empty($champs['Montant']) || 
            !isset($champs['DateFinAbonnement']) || 
            empty($champs['DateFinAbonnement'])) {
            // Retourne false si les paramètres sont manquants ou vides
            return false;
        }
        // Requête UPDATE pour la table "commande"
        $requeteCommande = "UPDATE commandeabonnement SET dateCommande = :dateCommande, montant = :montant WHERE id = :id";
        $paramsCommande = [
            'dateCommande' => $champs['DateCommande'],
            'montant' => $champs['Montant'],
            'id' => $champs['Id']
        ];
        $resultCommande = $this->conn->updateBDD($requeteCommande, $paramsCommande);
    
        // Requête UPDATE pour la table "abonnement"
            $requeteCommandeDocument = "UPDATE abonnement SET dateFinAbonnement = :dateFinAbonnement, idRevue = :idRevue WHERE id = :id";
            $paramsCommandeDocument = [
                'dateFinAbonnement' => $champs['DateFinAbonnement'],
                'idRevue' => $champs['IdRevue'],
                'id' => $champs['Id']
            ];
            $resultCommandeDocument = $this->conn->updateBDD($requeteCommandeDocument, $paramsCommandeDocument);
        // Si les deux requêtes ont réussi = true
        if ($resultCommande > 0 && $resultCommandeDocument > 0) {
            return true;
        } else {
            // Si une des requêtes échoue = false
            return false;
        }
    }

    /**
     * Obtenir la liste des revues dont l'abonnement se termine dans moins de 30 jours
     * @return array|null Résultat de la requête ou null si erreur
     */
    private function selectListeFinAbonnement() : ?array {
        
        $requete = "SELECT d.titre, a.dateFinAbonnement ";
        $requete .= "FROM abonnement a ";
        $requete .= "JOIN document d ON a.idRevue = d.id ";
        $requete .= "WHERE a.dateFinAbonnement BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) ";
        $requete .= "ORDER BY a.dateFinAbonnement ASC";
        return $this->conn->queryBDD($requete);
    }

    /**
     * Récupère les informations d'un utilisateur
     * @param array|null $champs Nom et valeur de chaque champ pour la recherche
     * @return array|null Résultat de la requête ou null si erreur
     */
    private function selectUser(?array $champs) : ?array{
        if ($champs === null || 
        !isset($champs['Nom']) || 
        empty($champs['Nom'])) {
        return null;
    }
        $requete = "SELECT u.nom, u.motDePasse, u.idService ";
        $requete .= "FROM utilisateur u ";
        $requete .= "WHERE u.nom = :nom";

        $paramsCommande = [
            'nom' => $champs['Nom'],
        ];
        return $this->conn->queryBDD($requete, $paramsCommande);
    }
}