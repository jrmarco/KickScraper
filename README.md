# KickScraper v0.1

This PHP class provides a tool to fetch projects data from Kickstarter getting the latest project on the website or based on filters. It's possible to store this information into a DB. Please read ToS and ToU on Kickstarter

## Requirements

- PHP 5.6 or greater
- cURL (php-curl) library
- MySql database ( optional )

## Installation

1. Copy or clone the repository
2. Place the KickScraper.php and config.php in the same folder
3. Include the KickScraper.php inside a dedicated page or in any place of your code
4. Change the setting into the config.php to match your installation

## Run the tool

1. Create a new instance of the KickScraper : 
    $ks = new KickScraper();
2. Start fetching content live or save it into your database

## Data and information

All data coming from this class it is publicly accessible. We do not :
- use any illegal action to fetch them
- interfere with service or security service of the website
- steal this data from any protected section of the website

Any type of abuse it's reasonable on a wrong usage of the component or modification by the original code and purpose  

## Live fetching

 When using live fetch, tool reads data from the Kickstarter website page. Project can be filtered by category and sorted :
```
 $arrProject = $ks->getProjects(terms,category,sorting,status,page)
```

1. Params represent filters and they are all optionals: 
    1. Terms : it's the search string, will search for in in the whole website under project data and creator 
    2. Category : it's one of the available Kickstarter project categories ( scroll for explanation ) 
    3. Sorting : it's one of the available sorting method ( scroll for explanation ) 
    4. Status : it's the project status ( live or successful ) 
    5. Page : it's the page you like to fetch ( used for paginated searches ) 

2. Method returns an array of object made like this :
    

```
    $project = $arrProject[0];
    
    $project->id              // Kickstarter id
    $project->image           // Kickstarter image
    $project->name            // Name
    $project->desc            // Description
    $project->url             // Kickstarter page url
    $project->goal            // Goal sum ( needed )
    $project->pledged         // Pledged sum ( reached ) 
    $project->country         // Country appartenence
    $project->currency        // Used currency
    $project->currency_symbol // Currency symbolic
    $project->backers         // Actual backers number
    $project->state           // State ( live,successful, cancelled, etc. )
    $project->created         // Creation date
    $project->launched        // Lunch date
    $project->deadline        // Ending date
    $project->category        // Category

    $project->creator         // Creator name
    $project->creator_nick    // Creator Nick (might be blank)
    $project->creator_id      // Creator Id
    $project->creator_avatar  // Creator Kickstarter avatar
    $project->creator_url     // Kickstarter Creator page url
```
- Available categories :
```
    id 1  : 'Art';
    id 3  : 'Comics';
    id 25 : 'Crafts';
    id 6  : 'Dance';
    id 7  : 'Design';
    id 9  : 'Fashion';
    id 14 : 'Video';
    id 10 : 'Food';
    id 12 : 'Games';
    id 13 : 'Journalism';
    id 14 : 'Music';
    id 15 : 'Photography';
    id 18 : 'Publishing';
    id 16 : 'Technology';
    id 17 : 'Theater';  
```

- Available sorting :
```
    'magic','popularity','newest','end_date','most_funded','most_backed'
```

- Example :
```
// Search for 'board' live project under technology, sorted by popularity
$ks->getProjects('board',16,'popularity','live');
```
```
// Search for 'space' successful project, page 5, under game, sorted by most funded
$ks->getProjects('space',12,'popularity','successful',5);
```

## Store data into DB

 It's possible to directly store all information into a predefined set of tables. How to do it :
```
$ks = new KickScraper();
// Params are the same used for the live content and follow the same rules.
ks->storeIntoDB(terms,category,sorting,status,page);
```
 
The function takes care of all the necessary steps to be able to store all information. Prior this, you have to define in the config.php file, your setting to be able to communicate with the database.
1. Choose your database deamon driver : default value mysql ( leave this if you don't know what to do )
2. Set your host address : default value => "localhost" ( leave this if you don't know what to do )
3. Set your database user
4. Set your user password
5. Set your database name
6. Choose the tables prefix : default value => "kick_" ( leave this if you don't know what to do ) 

7. Tables follows this structure :

```
    //Projects table
    CREATE TABLE IF NOT EXISTS `kick_project` (
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
    );
```

```        
    // Creators table
    CREATE TABLE IF NOT EXISTS `kick_creator` (
        `id` int(11) NOT NULL,
        `nick` varchar(255) CHARACTER SET utf8 NULL,              
        `creator_name` varchar(255) CHARACTER SET utf8 NULL,
        `avatar` longtext CHARACTER SET utf8 NULL,
        `url` longtext CHARACTER SET utf8 NULL,
        PRIMARY KEY (`id`),
        KEY `kid` (`id`)
    );              
```

# DISCLAIMER
 Please remember : all contents fetched,loaded,read from Kickstarter it is/might be owned by Kickstarter and/or third party, protected by copyright and/or trademarks and/or intellectual property and/or licenses. All infos on Kickstarter pages \[ [Privacy Policy](https://www.kickstarter.com/privacy?ref=global-footer),[ToU](https://www.kickstarter.com/terms-of-use?ref=global-footer) \] . 
 
 I'm not responsible for any issue nor any kind of legal affair that this component could or would cause at your person/business.
