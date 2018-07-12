<?php

include __DIR__.'/config.php';

/**
 * Class KickScraper v0.1
 */
class KickScraper {

    public $pdo;
    public $totProject;
    public $liveProject;
    public $endedProject;
    public $fullSet;

    /**
     * Array of eligible sorting type
     * @var array
     */
    private $availSort = ['magic','popularity','newest','end_date','most_funded','most_backed'];

    /**
     * Array of eligible project status
     * @var array
     */
    private $availStatus = ['live','successful'];

    public function __construct() {}

    /**
     * Category mapper : int to string and vice-versa
     * @param null $category Category value
     * @return int|null|string Mixed category value
     */
    public function categoryMap($category = null) {
        if(!is_numeric($category)) {
            $category = strtolower($category);
        }

        switch($category) {
            case 1  : return 'Art'; break;          case 'art' : return 1; break;
            case 3  : return 'Comics'; break;       case 'comics' : return 3; break;
            case 25 : return 'Crafts'; break;       case 'crafts' : return 25; break;
            case 6  : return 'Dance'; break;        case 'dance' : return 6; break;
            case 7  : return 'Design'; break;       case 'design' : return 7; break;
            case 9  : return 'Fashion'; break;      case 'fashion' : return 9; break;
            case 14 : return 'Video'; break;        case 'video' : return 14; break;
            case 10 : return 'Food'; break;         case 'food' : return 10; break;
            case 12 : return 'Games'; break;        case 'games' : return 12; break;
            case 13 : return 'Journalism'; break;   case 'journalism' : return 13; break;
            case 14 : return 'Music'; break;        case 'music' : return 14; break;
            case 15 : return 'Photography'; break;  case 'photography' : return 15; break;
            case 18 : return 'Publishing'; break;   case 'publishing' : return 18; break;
            case 16 : return 'Technology'; break;   case 'technology' : return 16; break;
            case 17 : return 'Theater'; break;      case 'theater' : return 17; break;
            default : return null;
        }
    }

    /**
     * Fetch searched project from Kickstarter
     * @param $strTerms Searched terms
     * @param null $category Cateogry id
     * @param string $sort Sorting type
     * @param $status Project status
     * @param int $page Page to fetch
     * @return array Array of projects
     */
    public function getProjects($strTerms,$category = null, $sort = 'magic', $status = null, $page = 1) {
        if(!in_array($sort,$this->availSort)) { $sort = 'magic'; }
        if(!in_array($status,$this->availStatus)) { $status = null; }
        if(!intval($page) || $page<1) { $page = 1; }

        $results            = $this->makeSearch($strTerms,$category,$sort,$status,$page);
        $this->totProject   = $results->total_hits;
        $this->liveProject  = $results->live_projects_count;
        $this->endedProject = $results->past_projects_count;
        $this->fullSet      = "https://www.kickstarter.com";
        if(!empty($strTerms)) {
            $this->fullSet      = "https://www.kickstarter.com".$results->search_url;
        }

        $arrProject = [];
        foreach ($results->projects as $prj) {
            $project           = new stdClass();

            $project->id       = $prj->id;
            $project->image    = $prj->photo->full;

            $project->name     = $prj->name;
            $project->desc     = $prj->blurb;

            if(!isset($prj->creator->slug) || empty($prj->creator->slug)) {
                $project->url  = 'https://www.kickstarter.com/projects/'.$prj->creator->id.'/'.$prj->slug;
            } else {
                $project->url  = 'https://www.kickstarter.com/projects/'.$prj->creator->slug.'/'.$prj->slug;
            }

            $project->goal     = $prj->goal;
            $project->pledged  = $prj->pledged;
            $project->country  = $prj->country;
            $project->currency = $prj->currency;
            $project->currency_symbol = $prj->currency_symbol;

            $project->backers  = $prj->backers_count;

            $project->state    = $prj->state;
            $project->created  = $prj->created_at;
            $project->launched = $prj->launched_at;
            $project->deadline = $prj->deadline;

            $project->creator  = $prj->creator->name;

            $project->creator_nick = null;
            if(isset($prj->creator->slug) && !empty($prj->creator->slug)) {
                $project->creator_nick   = $prj->creator->slug;
            }
            $project->creator_id     = $prj->creator->id;
            $project->creator_avatar = $prj->creator->avatar->medium;
            $project->creator_url    = $prj->creator->urls->web->user;

            $project->category = $prj->category->name;
            $arrProject[] = $project;
        }

        return $arrProject;
    }

    /**
     * Prepare and execute the curl call
     * @param $strTerms Searched Terms
     * @param $category Category id
     * @param $sort Sorting type
     * @param $status Project status
     * @param $page Page to fetch
     * @return mixed JSON response
     */
    private function makeSearch($strTerms,$category,$sort,$status,$page) {
        $strTerms = preg_replace("[^A-Za-z0-9 ]",'',$strTerms);
        $url      = $this->setUrl($strTerms,$category,$sort,$status,$page);
        $response = $this->cURL($url);
        return json_decode($response);
    }

    /**
     * Create the curl string to fire the call
     * @param $strTerms Searched Terms
     * @param $category Category id
     * @param $sort Sorting type
     * @param $status Project status
     * @param $page Page to fetch
     * @return string URL
     */
    private function setUrl($strTerms,$category,$sort,$status,$page) {
        $catUrl    = '';
        $statusUrl = '';
        if(!is_null($category)) { $catUrl = '&category_id='.$this->categoryMap($category);}
        if(!is_null($status)) { $statusUrl = '&status='.$status;}
        $sorting = '&sort='.$sort;
        $pageUrl = '&page='.$page;

        $url = 'https://www.kickstarter.com/projects/search.json?term='.urlencode(str_replace('+','%20',$strTerms)).
            $catUrl.$sorting.$statusUrl.$pageUrl;
        return $url;
    }

    /**
     * Generate random token for cUrl call
     * @return bool|string
     */
    private function generateToken() {
        $token = '';
        for($i=0;$i<5;$i++) {
            $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
            $charactersLength = strlen($characters);
            for ($i = 0; $i < 6; $i++) {
                $token .= $characters[rand(0, $charactersLength - 1)];
            }
            $token .= '-';
        }
        $token = substr($token,0,-1);

        return $token;
    }

    /**
     * Make the curl call to fetch data from kickstarter
     * @param $url CURL url
     * @return array|mixed
     */
    private function cURL($url) {
        $curl = curl_init();



        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
                "Cache-Control: no-cache",
                "KS-Token: ".$this->generateToken()
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            return [];
        } else {
            return $response;
        }
    }

    /**
     * Create or update resulting projects by searched terms
     * @param $strTerms Searched terms
     * @param int $category Category id
     * @param string $sort Sorting type
     * @param $status Project status
     * @param int $page Page to fetch
     */
    public function storeIntoDB($strTerms,$category = -1, $sort = 'magic', $status = null, $page = 1) {
        global $db;

        $this->initDB();
        $this->initTable();

        $results = $this->getProjects($strTerms,$category, $sort, $status, $page);
        foreach ($results as $result) {

            $cid = $this->searchExistingCreator($result);

            $sql = 'INSERT INTO `'.$db['prefix'].'project` VALUES'.
                '(:id,:creator_id,:name,:desc,:url,:img,:goal,:pledged,:country,:currency,:currency_symbol,'.
                ':backers,:state,:created,:launched,:deadline,:category) '.
                'ON DUPLICATE KEY UPDATE creator_id=:creator_id, project_name=:name, project_desc=:desc, url=:url, img=:img,'.
                'goal=:goal, pledged=:pledged, country=:country, currency=:currency, currency_symbol=:currency_symbol,'.
                'backers=:backers, state=:state, created=:created, launched=:launched, deadline=:deadline, category=:category;';

            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':id',$result->id,PDO::PARAM_INT);
            $stmt->bindValue(':creator_id',$cid,PDO::PARAM_INT);
            $stmt->bindValue(':name',$result->name,PDO::PARAM_STR);
            $stmt->bindValue(':desc',$result->desc,PDO::PARAM_STR);
            $stmt->bindValue(':url',$result->url,PDO::PARAM_STR);
            $stmt->bindValue(':img',$result->image,PDO::PARAM_STR);
            $stmt->bindValue(':goal',$result->goal,PDO::PARAM_INT);
            $stmt->bindValue(':pledged',$result->pledged,PDO::PARAM_STR);
            $stmt->bindValue(':country',$result->country,PDO::PARAM_STR);
            $stmt->bindValue(':currency',$result->currency,PDO::PARAM_STR);
            $stmt->bindValue(':currency_symbol',$result->currency_symbol,PDO::PARAM_STR);
            $stmt->bindValue(':backers',$result->backers,PDO::PARAM_INT);
            $stmt->bindValue(':state',$result->state,PDO::PARAM_STR);
            $stmt->bindValue(':created',$result->created,PDO::PARAM_INT);
            $stmt->bindValue(':backers',$result->backers,PDO::PARAM_INT);
            $stmt->bindValue(':launched',$result->launched,PDO::PARAM_INT);
            $stmt->bindValue(':deadline',$result->deadline,PDO::PARAM_INT);
            $stmt->bindValue(':category',$result->category,PDO::PARAM_STR);
            $stmt->execute();
        }
    }

    /**
     * Create or update creator db dataset
     * @param $objProject Project object
     * @return mixed
     */
    private function searchExistingCreator($objProject) {
        global $db;

        $intCid = $objProject->creator_id;
        $sql    = 'SELECT * FROM `'.$db['prefix'].'creator` WHERE id=:cid';
        $stmt   = $this->pdo->prepare($sql);
        $stmt->bindValue(':cid',$intCid,PDO::PARAM_INT);
        $stmt->execute();
        $cid = $stmt->fetch(PDO::FETCH_ASSOC);

        $sql  = 'INSERT INTO `'.$db['prefix'].'creator` VALUES ('.
            ':id,:nick,:name,:avatar,:url) ON DUPLICATE KEY UPDATE '.
            'nick=:nick, creator_name=:name, avatar=:avatar, url=:url;';
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':id',intval($objProject->creator_id),PDO::PARAM_INT);
        $stmt->bindValue(':nick',$objProject->creator_nick,PDO::PARAM_STR);
        $stmt->bindValue(':name',$objProject->creator,PDO::PARAM_STR);
        $stmt->bindValue(':avatar',$objProject->creator_avatar,PDO::PARAM_STR);
        $stmt->bindValue(':url',$objProject->creator_url,PDO::PARAM_STR);
        $stmt->execute();

        return $cid['id'];
    }

    /**
     * Initialize the DB connection
     * Database parameters must be inserted into config.php file to be able to connect and use it
     */
    private function initDB() {
        global $db;
        try {
            $this->pdo = new PDO ($db['daemon'] . ':host=' . $db['host'] . ';dbname=' . $db['dbname'], $db['user'], $db['psw']);
        } catch (PDOException $e) {
            print_r($e->getMessage());
            die('PDO Init error');
        }
    }

    /**
     * Create required tables
     */
    private function initTable() {
        global $db;

        $sql = 'CREATE TABLE IF NOT EXISTS `'.$db['prefix'].'project` (
              `id` int(11) NOT NULL,
              `creator_id` int(11) NOT NULL,
              `project_name` varchar(255) CHARACTER SET utf8 NOT NULL,
              `project_desc` varchar(255) CHARACTER SET utf8 NULL,
              `url` longtext CHARACTER SET utf8 NULL,
              `img` longtext CHARACTER SET utf8 NULL,              
              `goal` int(11) NULL,
              `pledged` int(11) NULL,
              `country` varchar(2) CHARACTER SET utf8 NULL,
              `currency` varchar(5) CHARACTER SET utf8 NULL,
              `currency_symbol` varchar(10) CHARACTER SET utf8 NULL,
              `backers` int(11) NULL,
              `state` varchar(11) CHARACTER SET utf8 NULL,
              `created` int(11) NULL,
              `launched` int(11) NULL,
              `deadline` int(11) NULL,
              `category` varchar(255) CHARACTER SET utf8 NULL,
              PRIMARY KEY (`id`),
              KEY `kid` (`id`)
            );';

        $sql .= 'CREATE TABLE IF NOT EXISTS `'.$db['prefix'].'creator` (
              `id` int(11) NOT NULL,
              `nick` varchar(255) CHARACTER SET utf8 NULL,              
              `creator_name` varchar(255) CHARACTER SET utf8 NULL,
              `avatar` longtext CHARACTER SET utf8 NULL,
              `url` longtext CHARACTER SET utf8 NULL,
              PRIMARY KEY (`id`),
              KEY `kid` (`id`)
            );';

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            $stmt = null;
        } catch(Exception $e) {
            print_r($e->getMessage());
            die('Cannot create tables');
        }
    }
}