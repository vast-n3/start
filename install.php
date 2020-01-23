<?php

/*
 * vast-n3 installation script
 *
 * From the root of your neoan3 installation, run "php frame/vastn3/install-vastn3.php"
 *
 * Developers: set dependencies
 *
 * */

$dependencies = [
    'neoan3-model/user' => ['model', 'https://github.com/sroehrl/neoan3-userModel.git']
];

define('CREDENTIAL_PATH', DIRECTORY_SEPARATOR . 'credentials' . DIRECTORY_SEPARATOR . 'credentials.json');

/*
 * End of setup part
 * */

function clearOutput(&$output)
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

// 1. make sure npm is installed & available
exec('npm -v', $output, $return);
if (empty($output)) {
    echo "npm is either not installed or not available to the PHP user.\n
    Please ensure you have node & npm installed.\n\n";
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


// 3. install tailwind
exec('npm install tailwindcss', $output, $return);
printOutput($output);

// 4. compile css
echo "Compiling CSS...\n";
exec('npx tailwind build frame/vastn3/style.dev.css -o frame/vastn3/style.css', $output, $return);
printOutput($output);

// 5. install vue & axios
echo "Installing Vue...\n";
exec('npm i vue', $output, $return);
exec('npm i axios', $output, $return);
printOutput($output);


// 6. install dependencies
echo "Installing dependencies...\n";

foreach ($dependencies as $name => $typeLocation) {
    $execStr = 'neoan3 add ' . $typeLocation[0] . ' ' . $name . (isset($typeLocation[1]) ? ' ' . $typeLocation[1] : '');
    exec($execStr, $output, $return);
    printOutput($output);
}

// 7. Credentials
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

echo "\n\nThis file ( frame/vastn3/install-vastn3.php ) should not be deployed!\n\n";
