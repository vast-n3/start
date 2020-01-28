<?php

// credentials location
define('CREDENTIAL_PATH', DIRECTORY_SEPARATOR . 'credentials' . DIRECTORY_SEPARATOR . 'credentials.json');

// credentials
$credentials = [];

$handler = new handleCredentials();

try {
    $credentials = $handler->getCredentials();

    // stateless credentials
    if (!isset($credentials['salts']['vastn3'])) {
        $credentials['salts']['vastn3'] = $handler->randomString();
    }

    // database credentials
    if (!isset($credentials['vastn3_db'])) {
        $db = new databaseCredentials();
        $credentials['vastn3_db'] = $db->credentials;
    } else {
        echo "\nNOTE: neoan3 already holds database credentials for vastn3_db. You can change them by running 'neoan3 credentials' after installation.\n";
    }


    // mail credentials
    if (!isset($credentials['vastn3_mail'])) {
        $mail = new mailCredentials();
        $credentials['vastn3_mail'] = $mail->credentials;
    } else {
        echo "\nNOTE: neoan3 already holds database credentials for vastn3_mail. You can change them by running 'neoan3 credentials' after installation.\n";
    }

    // write...

    $handler->writeCredentials($credentials);
} catch (Exception $e) {
    echo "Failed handling credentials. \nPlease run 'neoan3 credentials' after installation\n";
}

// rename me
echo "renaming myself to setup.php_n3\n";
if(strpos(__FILE__, 'configs') === false){
    rename(__FILE__, __FILE__ . '_n3');
}
echo "Trying to execute migration (neoan3 migrate models up)\n";
exec('neoan3 migrate models up', $output, $return);

echo "Done. You can delete this file.\n";
exit();

class mailCredentials
{
    public $credentials;
    function __construct()
    {
        $credentialHandler = new handleCredentials();
        $this->credentials = $credentialHandler->captureCredentials('mail');
    }
}

class databaseCredentials
{
    public $credentials;
    function __construct()
    {
        $credentialHandler = new handleCredentials();
        $databaseCredentials = $credentialHandler->captureCredentials('database');
        mysqli_report(MYSQLI_REPORT_STRICT);
        try {
            $connection =
                new mysqli(
                    $databaseCredentials['host'], $databaseCredentials['user'], $databaseCredentials['password']
                );
            $connection->query('CREATE DATABASE ' . $databaseCredentials['name']);
        } catch (mysqli_sql_exception $e) {
            echo "Could not establish database connection. After installation, please run 'neoan3 credentials'\n";
            sleep(1);
        }
        $databaseCredentials['assumes_uuid'] = true;
        $this->credentials = $databaseCredentials;
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

    function randomString()
    {
        return mb_substr(bin2hex(random_bytes(32)), 0, 32);
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

    function mail()
    {
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
