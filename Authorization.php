<?php

require_once __DIR__.'/FlickrApi.php';

class Authorization
{
    public function getAccessTokens()
    {
        $secretToken=file_get_contents('secret_token.txt');
        unlink("secret_token.txt");

        $data = [
            'oauth_verifier' => $_GET['oauth_verifier'],
            'oauth_token' => $_GET['oauth_token'],
        ];

        $flickApi = new FlickrApi();
        $flickApi->auth();
        $flickApi->setParams($data);

        $baseString = "GET&" . urlencode($flickApi::ACCESS_URL) . "&" . urlencode(http_build_query($flickApi->getParams()));

        $key = $flickApi::SECRET . "&" . $secretToken;
        $signature = base64_encode(hash_hmac('sha1', $baseString, $key, true));

        $data2 = [
            'oauth_signature' => $signature,
        ];

        $flickApi->setParams($data2);

        $url = $flickApi::ACCESS_URL . "?" . http_build_query($flickApi->getParams());

        $content = $flickApi->getPage($url);

        $output = [];

        if (preg_match('%&oauth_token=(.+?)&%s', $content, $oauthToken))
        {
            $output['oauth_token'] = $oauthToken[1];
        }
        else
        {
            echo "Couldn't find oauth token";
        }

        if (preg_match('%&oauth_token_secret=(.+?)&%s', $content, $secret))
        {
            $output['oauth_token_secret'] = $secret[1];
        }
        else
        {
            echo "Couldn't find oauth secret token";
        }

        file_put_contents('access_tokens.txt',$output['oauth_token'].','.$output['oauth_token_secret']);

        echo "Access tokens were provided in access_tokens.txt file";
    }
}

$obj=new Authorization();
$obj->getAccessTokens();