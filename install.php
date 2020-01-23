<?php

/*
 * vast-n3 installation script
 *
 * From the root of your neoan3 installation, run "php frame/vastn3/install-vastn3.php"
 *
 * Developers: set dependencies
 *
 * */

$installerVersion = 'master';

$dependencies = [
    'neoan3-model/user' => ['model', 'https://github.com/sroehrl/neoan3-userModel.git']
];

define('CREDENTIAL_PATH', DIRECTORY_SEPARATOR . 'credentials' . DIRECTORY_SEPARATOR . 'credentials.json');

/*
 * End of setup part
 * */

function clearOutput(&$output): void
{
    $output = [];
}

function printOutput(&$output)
{
    foreach ($output as $line) {
        echo $line . "\n";
    }
    clearOutput($output);
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

function io($execString, &$output){
    exec($execString, $output, $return);
    printOutput($output);
}

// 1. make sure npm is installed & available
exec('npm -v', $output, $return);
if (empty($output)) {
    echo "npm is either not installed or not available to the PHP user.\n
    Please run 'npm install tailwindcss' manually after ensuring you have node & npm installed.\n\n";
    exit(1);
}
echo "Found npm version " . $output[0] . "\n";
clearOutput($output);

// 2. make sure neoan3 is installed & available
exec('neoan3 -v', $output, $return);
if (empty($output)) {
    echo "neoan3 is either not installed or not available to the PHP user.\n
    Please run 'npm install neoan3-cli -g' manually after ensuring you have node & npm installed.\n\n";
    exit(1);
}
printOutput($output);

// 3. install frame
io('neoan3 add frame vast-n3/vastn3 https://github.com/vast-n3/vastn3.git', $output);


// 4. install tailwind
io('npm install tailwindcss', $output);

// 5. compile css
echo "Compiling CSS...\n";
io('npx tailwind build frame/vastn3/style.dev.css -o frame/vastn3/style.css', $output);

// 6. install vue & axios
echo "Installing Vue...\n";
io('npm i vue', $output);
io('npm i axios', $output);


// 7. install dependencies
echo "Installing dependencies...\n";

foreach ($dependencies as $name => $typeLocation) {
    $execStr = 'neoan3 add ' . $typeLocation[0] . ' ' . $name . (isset($typeLocation[1]) ? ' ' . $typeLocation[1] : '');
    io($execStr, $output);
}

// 8. create template
if(!is_dir(__DIR__ . '/_template')){
    mkdir(__DIR__ . '/template');
}
foreach(['ce.html','ce.js','route.php'] as $templateFile){
    $content = file_get_contents('https://raw.githubusercontent.com/vast-n3/start/' . $installerVersion . '/templates/' . $templateFile);
    file_put_contents(__DIR__ . '/_template/' . $templateFile, $content);
}


// 9. Credentials
try {
    $credentials = getCredentials();
    if (!isset($credentials['salts']['vastn3'])) {
        $credentials['salts']['vastn3'] = randomString();
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
    writeCredentials($credentials);
} catch (Exception $e) {
    echo "Failed handling credentials. \nPlease run 'neoan3 credentials'\n";
    exit(1);
}


// Final text

echo "\n\nThis file should not be deployed!\n\n";
