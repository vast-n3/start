<?php

/*
 * vast-n3 installation script
 * GPL v3
 *
 * Developers: set dependencies & version
 *
 * */

$installerVersion = 'master';

$neoanComponents = [
    'vast-n3/vastn3' => ['frame', 'https://github.com/vast-n3/vastn3.git'],
    'vast-n3/home' => ['frame', 'https://github.com/vast-n3/component-home.git'],
    'vast-n3/header' => ['frame', 'https://github.com/vast-n3/component-header.git'],
    'neoan3-model/user' => ['model', 'https://github.com/sroehrl/neoan3-userModel.git']
];

$npmPackages = ['vue', 'axios', 'tailwindcss', 'postcss', 'autoprefixer', 'postcss-import'];

$placedFiles = [];

foreach (['package.json', 'postcss.config.js', 'tailwind.config.js'] as $file){
    $placedFiles[] = [
        'src' => 'https://raw.githubusercontent.com/vast-n3/start/' . $installerVersion . '/configs/' . $file,
        'target' => $file
    ];
}
foreach (['ce.html', 'ce.js', 'route.php'] as $file) {
    $placedFiles[] = [
        'src' => 'https://raw.githubusercontent.com/vast-n3/start/' . $installerVersion . '/templates/' . $file,
        'target' => '_template/' . $file
    ];
}

define('CREDENTIAL_PATH', DIRECTORY_SEPARATOR . 'credentials' . DIRECTORY_SEPARATOR . 'credentials.json');

/**
 * --------------- End of setup part ----------------
 * */

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


// install npm dependencies
foreach ($npmPackages as $package) {
    echo "\ninstalling " . $package . "\n";
    $iv3->io('npm install ' . $package);
}


// compile css
echo "Compiling ...\n";
$iv3->io('npm build');

// fund us:

// bobby's awesome fundme page

// done

echo "\nAll done.\nYou can run 'php -S localhost:8080 _neoan/server.php'";


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

    function writeFiles($fileArray)
    {
        foreach ($fileArray as $file) {
            $folder = explode('/', $file['target']);
            array_pop($folder);
            $folder = (count($folder) > 0 ? implode('/', $folder) : '/');
            echo "$folder\n";

            if (!is_dir($folder)) {
                mkdir($folder, 755, true);
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
