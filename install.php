<?php



/*
 * vast-n3 installation script
 *
 * From the root of your neoan3 installation, run "php frame/vastn3/install-vastn3.php"
 *
 * Developers: set dependencies & version
 *
 * */

$installerVersion = 'master';

$dependencies = [
    'neoan3-model/user' => ['model', 'https://github.com/sroehrl/neoan3-userModel.git']
];

define('CREDENTIAL_PATH', DIRECTORY_SEPARATOR . 'credentials' . DIRECTORY_SEPARATOR . 'credentials.json');

/**
 * --------------- End of setup part ----------------
 * */

$iv3 = new InstallVastn3();

// availability npm & neoan3

$npmAvailable = $iv3->io('npm -v',"npm is either not installed or not available to the PHP user.\n");

$fatal = "neoan3-cli is not available to PHP. Please check PATH & permissions!";
if(!$iv3->io('neoan3 -v', $fatal)){
    exit(1);
}

/**
 * Installations
 * */

// installation frame & components
$iv3->io('neoan3 add frame vast-n3/vastn3 https://github.com/vast-n3/vastn3.git');
$iv3->io('neoan3 add component vast-n3/home https://github.com/vast-n3/component-home.git');
$iv3->io('neoan3 add component vast-n3/header https://github.com/vast-n3/component-header.git');
$iv3->setDefaultRoute('home');

// install dependencies
echo "Installing dependencies...\n";

foreach ($dependencies as $name => $typeLocation) {
    $execStr = 'neoan3 add ' . $typeLocation[0] . ' ' . $name . (isset($typeLocation[1]) ? ' ' . $typeLocation[1] : '');
    $iv3->io($execStr);
}

// create template-folder
$iv3->writeTemplates($installerVersion);


// credentials
try {
    $credentials = $iv3->getCredentials();
    if (!isset($credentials['salts']['vastn3'])) {
        $credentials['salts']['vastn3'] = $iv3->randomString();
    }
    if (!isset($credentials['vastn3_db'])) {
        $credentials['vastn3_db'] = [
            'host' => 'localhost',
            'name' => 'vastn_three',
            'password' => '',
            'user' => 'root'
        ];
    } else {
        echo "\nThe credentials 'vastn3_db' already exists.\n";
    }
    echo "\nPlease verify correct credentials for your database by running 'neoan3 credentials' (used namespace is 'vastn3_db') \n";
    // write to store
    $iv3->writeCredentials($credentials);
} catch (Exception $e) {
    echo "Failed handling credentials. \nPlease run 'neoan3 credentials'\n";
}


// install tailwind
$iv3->io('npm install tailwindcss');

// compile css
echo "Compiling CSS...\n";
$iv3->io('npx tailwind build frame/vastn3/style.dev.css -o frame/vastn3/style.css');

// install axios & vue
$iv3->io('npm i vue');
$iv3->io('npm i axios');

// finally, start server

echo "You can stop this script or wait for the testing environment to start (4 seconds).\n";
sleep(4);
$iv3->run();



/**
 *  Installation class
 */

class InstallVastn3
{
    private array $output;

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
    function writeTemplates($tag){
        if (!is_dir(__DIR__ . '/_template')) {
            mkdir(__DIR__ . '/_template');
        }
        foreach (['ce.html', 'ce.js', 'route.php'] as $templateFile) {
            try{
                $content = file_get_contents(
                    'https://raw.githubusercontent.com/vast-n3/start/' . $tag . '/templates/' . $templateFile
                );
                file_put_contents(__DIR__ . '/_template/' . $templateFile, $content);
            } catch (Exception $e){
                echo "\n" . $templateFile ." not found.\n";
            }

        }
    }
    function setDefaultRoute($component){
        $default = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'default.php');
        $newContent = preg_replace('/\'default_ctrl\',[\w\']+\)/',"'default_ctrl', '$component')", $default);
        file_put_contents(__DIR__ . DIRECTORY_SEPARATOR . 'default.php', $newContent);
    }

    function io($execString, $warning = "Warning: Command did not return\n")
    {
        exec($execString, $this->output, $return);
        if(empty($this->output)){
            $this->clearOutput();
            echo $warning;
            return false;
        }
        $this->printOutput();
        return true;
    }
    function run(){
        exec('php -S localhost:8080 _neoan/server.php');
        exit();
    }
}
