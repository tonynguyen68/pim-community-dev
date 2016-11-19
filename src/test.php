<?php

    function multiRequest($data, $headers, $options = array()) {


        // array of curl handles
        $curly = array();
        // data to be returned
        $result = array();

        // multi handle
        $mh = curl_multi_init();

        // loop through $data and create curl handles
        // then add them to the multi-handle
        foreach ($data as $id => $d) {

            $username = "admin";
            $apiKey   = "5d17bea29e20b4cbf7e80f685fe5b9f9f426f048";
            $salt     = "je8ruddq7pc0ocgc44o4cwg0g0o4cok";

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

            $url = (is_array($d) && !empty($d['url'])) ? $d['url'] : $d;

            $curly[$url] = curl_init();

            curl_setopt($curly[$url], CURLOPT_URL,            $url);
            curl_setopt($curly[$url], CURLOPT_HTTPHEADER,     $headers);
            curl_setopt($curly[$url], CURLOPT_RETURNTRANSFER, 1);

            // post?
            //if (is_array($d)) {
            //    if (!empty($d['post'])) {
            //        curl_setopt($curly[$id], CURLOPT_POST,       1);
            //        curl_setopt($curly[$id], CURLOPT_POSTFIELDS, $d['post']);
            //    }
            //}

            // extra options?
            if (!empty($options)) {
                curl_setopt_array($curly[$url], $options);
            }

            //curl_close($curly[$url]);

            curl_multi_add_handle($mh, $curly[$url]);
        }

        /*
        // Start performing the request
        do {
            $execReturnValue = curl_multi_exec($mh, $runningHandles);
        } while ($execReturnValue == CURLM_CALL_MULTI_PERFORM);
        // Loop and continue processing the request
        while ($runningHandles && $execReturnValue == CURLM_OK) {
            // Wait forever for network
            $numberReady = curl_multi_select($mh, 3);
            var_dump(curl_multi_strerror($numberReady));
            if ($numberReady != -1) {
                // Pull in any new data, or at least handle timeouts
                do {
                    $execReturnValue = curl_multi_exec($mh, $runningHandles);
                } while ($execReturnValue == CURLM_CALL_MULTI_PERFORM);
            }
        }


        // Extract the content
        foreach($curly as $i => $url)
        {
            // Check for errors
            $curlError = curl_error($curly[$i]);
            if($curlError == "") {
                $res[$i] = curl_multi_getcontent($curly[$i]);
            } else {
                print "Curl error on handle $i: $curlError\n";
            }
            // Remove and close the handle
            curl_multi_remove_handle($mh, $curly[$i]);
            curl_close($curly[$i]);
        }
        // Clean up the curl_multi handle
        curl_multi_close($mh);

        // Print the response data
        print_r($res);

        return $res;
        */

        $running = null;
        do {
            do {
                $mrc = curl_multi_exec($mh, $running);
            } while ($mrc == CURLM_CALL_MULTI_PERFORM);
        } while($running > 0);

        // get content and remove handles
        foreach($curly as $id => $c) {
            $result[$id] = curl_multi_getcontent($c);
            var_dump($result[$id]);
            curl_multi_remove_handle($mh, $c);
        }

        // all done
        curl_multi_close($mh);

        return $result;
    }

    $username = "admin";
    $apiKey   = "5d17bea29e20b4cbf7e80f685fe5b9f9f426f048";
    $salt     = "je8ruddq7pc0ocgc44o4cwg0g0o4cok";

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

    $file        = fopen("sku.txt","r");
    $identifiers = stream_get_contents($file);
    $identifiers = explode(',', $identifiers);

    $urls = [];
    foreach ($identifiers as $identifier) {
        $urls[] = sprintf('http://pcd.dev/api/rest/products/%s.json', $identifier);
        //$urls[] = 'http://pcd.dev/api/rest/products/AKNTS_PWXXL_VSO.json';
    }


    $batchSize = 2;
    $offset = 0;
    while($offset < count($identifiers)) {
        $r = multiRequest(array_slice($urls, $offset, $batchSize), $headers);
        $offset += $batchSize;
        var_dump($r);
    }


