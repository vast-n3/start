<?php

/*
 * vast-n3 installation script
 * GPL v3
 *
 * Developers: set dependencies & version
 *
 * */

$installerVersion = 'master';

// vast-n3 components

$vastComponents = ['home', 'header', 'register', 'login', 'modal', 'email', 'animation', 'introduction'];

// npm packages

$npmPackages = ['vue', 'axios', 'tailwindcss', 'postcss', 'postcss-cli', 'autoprefixer', 'postcss-import'];

// frame && user-model
$neoanComponents = [
    'vast-n3/vastn3' => ['frame', 'https://github.com/vast-n3/vastn3.git'],
    'neoan3-model/user' => ['model', 'https://github.com/sroehrl/neoan3-userModel.git']
];

// _template files
foreach (['ce.html', 'ce.js', 'route.php'] as $file) {
    $placedFiles[] = [
        'src' => 'https://raw.githubusercontent.com/vast-n3/start/' . $installerVersion . '/templates/' . $file,
        'target' => '_template/' . $file
    ];
}

// credentials location
define('CREDENTIAL_PATH', DIRECTORY_SEPARATOR . 'credentials' . DIRECTORY_SEPARATOR . 'credentials.json');


/**
 * --------------- End of setup part ----------------
 * */

foreach ($vastComponents as $vastComponent) {
    $neoanComponents['vast-n3/' . $vastComponent] = ['component' , 'https://github.com/vast-n3/component-' . $vastComponent . '.git'];
}



foreach (['package.json', 'postcss.config.js', 'tailwind.config.js'] as $file) {
    $placedFiles[] = [
        'src' => 'https://raw.githubusercontent.com/vast-n3/start/' . $installerVersion . '/configs/' . $file,
        'target' => $file
    ];
}




$iv3 = new InstallVastn3();

// availability npm & neoan3

echo "NPM check: ";
$npmAvailable = $iv3->io('npm -v', "npm is either not installed or not available to the PHP user.\n");

$fatal = "neoan3-cli is not available to PHP. Please check PATH & permissions!";
if (!$iv3->io('neoan3 -v', $fatal)) {
    exit(1);
}

/**
 * Installations
 * */

// add files

$iv3->writeFiles($placedFiles);

// set home

$iv3->setDefaultRoute('home');

// install dependencies
echo "Installing dependencies...\n";

foreach ($neoanComponents as $name => $typeLocation) {
    $execStr = 'neoan3 add ' . $typeLocation[0] . ' ' . $name . (isset($typeLocation[1]) ? ' ' . $typeLocation[1] : '');
    $iv3->io($execStr);
}

// credentials
$credentials = [];
try{
    $credentials = $iv3->getCredentials();

    // stateless credentials
    if (!isset($credentials['salts']['vastn3'])) {
        $credentials['salts']['vastn3'] = $iv3->randomString();
    }
    // database credentials
    if(!isset($credentials['vastn3_db'])){
        $credentials['vastn3_db'] = new databaseCredentials();
    } else {
        echo "\nNOTE: neoan3 already holds database credentials for vastn3_db. You can change them by running 'neoan3 credentials' after installation.\n";
    }


    // mail credentials
    if(!isset($credentials['vastn3_mail'])){
        $credentials['vastn3_mail'] = new mailCredentials();
    } else {
        echo "\nNOTE: neoan3 already holds database credentials for vastn3_mail. You can change them by running 'neoan3 credentials' after installation.\n";
    }

    // write...
    $iv3->writeCredentials($credentials);
} catch (Exception $e){
    echo "Failed handling credentials. \nPlease run 'neoan3 credentials' after installation\n";
}



// install npm dependencies
foreach ($npmPackages as $package) {
    echo "\ninstalling " . $package . "\n";
    $iv3->io('npm install ' . $package);
}


// compile css
echo "Compiling ...\n";
$iv3->io('npm run build');

// fund us:

// bobby's awesome fundme page

// done

echo "\nAll done.\nYou can run 'php -S localhost:8080 _neoan/server.php'\n\n";


/**
 *  Installation class
 */
class InstallVastn3
{
    private $output;

    function __construct()
    {
        $this->output = [];
    }


    function clearOutput()
    {
        $this->output = [];
    }

    function printOutput()
    {
        foreach ($this->output as $line) {
            echo $line . "\n";
        }
        $this->clearOutput();
    }

    function randomString()
    {
        return mb_substr(bin2hex(random_bytes(32)), 0, 32);
    }

    function getCredentials()
    {
        if (file_exists(CREDENTIAL_PATH)) {
            return json_decode(file_get_contents(CREDENTIAL_PATH), true);
        } else {
            return [];
        }
    }

    function writeCredentials($credentials)
    {
        file_put_contents(CREDENTIAL_PATH, json_encode($credentials));
    }

    function writeFiles($fileArray)
    {
        foreach ($fileArray as $file) {
            $folder = explode('/', $file['target']);
            array_pop($folder);
            $folder = (count($folder) > 0 ? implode('/', $folder) : '/');
            print_r($file);
            echo $folder .' - ';
            if (!is_dir($folder)) {
                mkdir($folder, 0777, true);
            }
            try {
                $content = file_get_contents($file['src']);
                file_put_contents($file['target'], $content);
            } catch (Exception $e) {
                echo "\n" . $file['src'] . " not found.\n";
            }
        }
    }

    function setDefaultRoute($component)
    {
        $default = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'default.php');
        $newContent = preg_replace('/\'default_ctrl\',[\w\']+\)/', "'default_ctrl', '$component')", $default);
        file_put_contents(__DIR__ . DIRECTORY_SEPARATOR . 'default.php', $newContent);
    }

    function io($execString, $warning = "Warning: Command did not return\n")
    {
        exec($execString, $this->output, $return);
        if (empty($this->output)) {
            $this->clearOutput();
            echo $warning;
            return false;
        }
        $this->printOutput();
        return true;
    }

}
class mailCredentials{
    function __construct()
    {
        $credentialHandler = new handleCredentials();
        return $credentialHandler->captureCredentials('mail');
    }
}

class databaseCredentials{
    function __construct()
    {
        $credentialHandler = new handleCredentials();
        $databaseCredentials = $credentialHandler->captureCredentials('database');
        mysqli_report(MYSQLI_REPORT_STRICT);
        try {
            $connection =
                new mysqli($databaseCredentials['host'], $databaseCredentials['user'], $databaseCredentials['password']);
            $connection->query('CREATE DATABASE ' . $databaseCredentials['name']);
        } catch (mysqli_sql_exception $e) {
            echo "Could not establish database connection. After installation, please run 'neoan3 credentials'\n";
            sleep(1);
        }
        $databaseCredentials['assumes_uuid'] = true;
        return $databaseCredentials;
    }

}

class prompt
{
    static function user($question, $default)
    {
        $return = $default;
        echo $question . " ( Default value: '$default' )\n";
        $handle = fopen("php://stdin", "r");
        $input = rtrim(fgets($handle));
        if (!empty($input)) {
            $return = $input;
        }
        fclose($handle);
        return $return;
    }
}


class handleCredentials
{
    function captureCredentials($entity)
    {
        $defaults = $this->$entity();
        foreach ($defaults as $key => $value) {
            $defaults[$key] = prompt::user("Please enter your $entity '$key'.", $value);
        }
        return $defaults;
    }

    private function database()
    {
        return [
            'host' => 'localhost',
            'name' => 'vastn_three',
            'password' => '',
            'user' => 'root'
        ];
    }
    function mail(){
        return [
            'Username' => 'sam@vastn3.uber',
            'Password' => 'super-secret',
            'Host' => 'mail.example.com',
            'Port' => 25,
            'SMTPSecure' => 'tls',
            'SMTPAuth' => true,
            'From' => 'noreply@example.com',
            'FromName' => 'vastn3-system'
        ];
    }
}
