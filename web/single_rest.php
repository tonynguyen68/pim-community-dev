<?php

    $products = json_decode(file_get_contents('/Users/ahocquard/Workspace/akeneo/pcd/products.json'));

    foreach ($products as $standardProduct) {
        updateProduct(json_encode($standardProduct));
    }

    function updateProduct($product)
    {
        $username = "admin";
        $apiKey   = "a1817e1550473d5af235df91c46aa2af12886da8";
        $salt     = "880zv8gjozggswcc0go8444sw4cck8g";

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

        curl_setopt($ch, CURLOPT_URL, 'http://pcdb.dev/api/rest/product/single.json');
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, ['product' => $product]);

        $result = curl_exec($ch);

        if (false === $result) {
            echo "ERROR:".curl_error($ch)."\n";
        } else {
            echo $result;
        }
    }
