<?php
use Cigital\Safetynet\JwsPayloadValidator;
use Cigital\Safetynet\Noncer;
use Cigital\Safetynet\PayloadValidator;
use Cigital\Safetynet\Session;

include(__DIR__ . '/vendor/autoload.php');
include(__DIR__ . '/settings.php');

function invalidRequest($message = "Invalid request.")
{
    http_response_code(500);
    die($message);
}

function customLog($message)
{
    $timestamp = '[ ' . date('Y-m-d H:i:s') . ' ] ';
    file_put_contents(__DIR__ . "/debug.log", $timestamp . $message . PHP_EOL, FILE_APPEND);
}

/**
 * Call the original getGift() API without all the verification steps. This API gives access to the data the user wants.
 *
 * @return bool
 */
function call_unchecked_getGiftAPI()
{
    echo "SUCCESS!";
}

/**
 * Validate the JWS object in the POST parameters of any request. All API calls can use this.
 *
 * @return bool
 */

function validateJWS()
{
    if (!isset($_POST['jws']) || $_POST['jws'] == '') {
        invalidRequest();
    }

    $session = new Session();
    $payload_validator = new PayloadValidator(
        $session,
        EXPECTED_PACKAGE_NAME, EXPECTED_APK_CERT_DIGEST, EXPECTED_APK_DIGEST
    );

    $jws = new JwsPayloadValidator($payload_validator, APIKEY, $_POST['jws']);

    $session->destroy();

    return $jws->isValid;
}

/**
 * Generate and send a nonce to the user
 *
 * @return bool
 */
function getNonceAPI()
{
    $noncer = new Noncer(new Session());
    $nonce = $noncer->generate();
    $noncer->store($nonce);

    echo $nonce;
}

/**
 * The secure getGift API. Before calling getGift() it verifies the JWS object in the POST parameters.
 */
function getGiftAPI()
{
    if (!validateJWS()) {
        invalidRequest("Invalid JWS.");
    }

    call_unchecked_getGiftAPI();
}

// entry point
$request_uri = str_replace("/index.php", "", $_SERVER['REQUEST_URI']);

if (0 === strpos($request_uri, '/api/getnonce')) {
    getNonceAPI();
} elseif (0 === strpos($request_uri, '/api/getgift')) {
    getGiftAPI();
} else {
    invalidRequest("Action not supported.");
}
