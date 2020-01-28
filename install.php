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



/**
 * --------------- End of setup part ----------------
 * */

foreach ($vastComponents as $vastComponent) {

    $neoanComponents['vast-n3/' . $vastComponent] = ['component' , 'https://github.com/vast-n3/component-' . $vastComponent . '.git'];

}



foreach (['package.json', 'postcss.config.js', 'tailwind.config.js', 'setup.php'] as $file) {
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
    echo "Retrieving " . $typeLocation[0] . " " . $name . "\n";
    $iv3->io($execStr);
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

echo "
*************************************************************
*
*   Installation complete
*
*   IMPORTANT: The file 'setup.php' should never be deployed.
*   Run 'php setup.php' now and delete the file afterwards.
*   You can always create/edit your credentials with 'neoan3 credentials'
*   
*   Leave us a star at https://github.com/vast-n3/vastn3 
*
*   If you are in a directory outside your local host, you can develop using the command:
*   'php -S localhost:8080 _neoan/server.php'
*
**************************************************************
";


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
    function writeFiles($fileArray)
    {
        foreach ($fileArray as $file) {
            $folder = explode('/', $file['target']);
            array_pop($folder);
            $folder = (count($folder) > 0 ? implode('/', $folder) : '/');
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
