<?php
$username = "admin";
$apiKey   = "58b5fb2e8b852ccdbb96055e607d40ef9f11c4b5";
$salt     = "d2rxypg2whcs40w8woocsokg8oks88s";

$nonce   = uniqid();
$created = date('c');

$digest  = base64_encode(sha1(base64_decode($nonce) . $created . $apiKey.'{'.$salt.'}', true));

$headers = array();
$headers[] = 'CONTENT_TYPE: application/json';
$headers[] = 'Authorization: WSSE profile="UsernameToken"';
$headers[] =
    sprintf(
        'X-WSSE: UsernameToken Username="%s", PasswordDigest="%s", Nonce="%s", Created="%s"',
        $username,
        $digest,
        $nonce,
        $created
    );

$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, 'http://behat-pce-master.local/api/rest/product.json');
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, ['products' => file_get_contents('/home/marie/Workspaces/pim-master/orm-ce/products.json')]);

/*
curl_setopt($ch, CURLOPT_URL, 'http://behat-pce-master.local/api/rest/products/list.json');
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
*/
$result = curl_exec($ch);

if (false === $result) {
    echo "ERROR:".curl_error($ch)."\n";
} else {
    echo $result;
}
