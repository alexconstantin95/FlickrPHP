<?php

class FlickrApi
{
    /*todo: complete YOUR CREDENTIALS from https://www.flickr.com/services/apps/by/YOUR_ACCOUNT*/
    const KEY = "API_KEY";
    const SECRET = "API_SECRET_KEY";


    /*todo: complete these variables with the ones from access_tokens.txt generated file*/
    const ACCESS_TOKEN = "YOUR_ACCESS_TOKEN";
    const ACCESS_SECRET = "YOUR_ACCESS_SECRET_TOKEN";

    /*Oauth System Data*/
    const VERSION = "1.0";
    const SIGNATURE_METHOD = "HMAC-SHA1";

    /*urls*/
    const CALLBACK_URL = "http://localhost/FlickrPHP/Authorization.php"; //IMPORTANT: path to Authorization.php file
    const REQUEST_TOKEN_URL = "https://www.flickr.com/services/oauth/request_token";
    const AUTH_URL = "https://www.flickr.com/services/oauth/authorize";
    const ACCESS_URL = "https://www.flickr.com/services/oauth/access_token";
    const OAUTH_URL = "https://api.flickr.com/services/rest";

    private $params;

    public function auth()
    {
        $oauthTimestamp = time();
        $nonce = md5(uniqid(rand(), true));
        $this->params=[
            'oauth_consumer_key' => self::KEY,
            'oauth_signature_method' => self::SIGNATURE_METHOD,
            'oauth_version' => self::VERSION,
            'oauth_nonce' => $nonce,
            'oauth_timestamp' => $oauthTimestamp,
        ];
    }

    public function validateAccount()
    {
        //get signature
        $signature = self::signingRequests();

        //get request token
        $requestToken = self::requestToken($signature);

        if (is_array($requestToken))
        {
            $authUrl = self::getUserAuthUrl($requestToken);
            file_put_contents("secret_token.txt", $requestToken['secret_token']);

            //redirect user to authorization page
            header('Location:'.$authUrl);
        }
        else
        {
            echo "Cannot find tokens";
        }
    }

    private function signingRequests()
    {
        $data=[
            'oauth_callback' => self::CALLBACK_URL
        ];

        self::setParams($data);

        $baseString = "GET&" . urlencode(self::REQUEST_TOKEN_URL) . "&" . urlencode(http_build_query(self::getParams()));

        $key = self::SECRET . "&";

        $signature = base64_encode(hash_hmac('sha1', $baseString, $key, true));

        self::removeParams($data);

        return $signature;
    }

    private function requestToken($signature)
    {
        $data=[
            'oauth_callback' => self::CALLBACK_URL,
            'oauth_signature' => $signature
        ];

        self::setParams($data);

        //get oauth tokens
        $url=self::REQUEST_TOKEN_URL."?".http_build_query(self::getParams());
        $content = self::getPage($url);

        if (preg_match("%&oauth_token=(.+?)&%s", $content, $t))
        {
            $token = $t[1];
        }
        else
        {
            echo "Cannot find auth token";
            return false;
        }

        if (preg_match("%&oauth_token_secret=(.*)%s", $content, $t))
        {
            $secret = $t[1];
        }
        else
        {
            echo "Cannot find secret token";
            return false;
        }

        $oauth_tokens = [
            'oauth_token' => $token,
            'secret_token' => $secret
        ];


        self::removeParams($data);

        return $oauth_tokens;
    }

    private function getUserAuthUrl($requestToken)
    {
        $data = [
            'oauth_token' => $requestToken['oauth_token'],
        ];

        $url = self::AUTH_URL . "?" . http_build_query($data);
        return $url;
    }

    public function getPage($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);

        $content = curl_exec($ch);
        return $content;
    }

    public function setParams($data)
    {
        foreach($data as $key=>$value)
        {
            if(!in_array($key, $this->params))
            {
                $this->params[$key]=$value;
            }
        }
        ksort($this->params);
    }

    public function removeParams($data)
    {
        foreach($data as $key=>$value)
        {
            if(isset($this->params[$key]))
            {
                unset($this->params[$key]);
            }
        }
    }

    public function getParams()
    {
        return $this->params;
    }

    public function callMethod($method, $params)
    {
        $data=[
            'nojsoncallback'=>1,
            'format'=>'json',
            'oauth_token'=>self::ACCESS_TOKEN,
            'method'=>$method
        ];

        $this->setParams($data);

        if(count($params))
        {
            $this->setParams($params);
        }

        $baseString= "GET&".urlencode(self::OAUTH_URL)."&".urlencode(http_build_query(self::getParams()));

        $key=self::SECRET."&".self::ACCESS_SECRET;
        $signature = base64_encode(hash_hmac('sha1', $baseString, $key, true));

        $data2=[
            'oauth_signature'=>$signature,
        ];

        $this->setParams($data2);

        $url=self::OAUTH_URL."?".http_build_query(self::getParams());

        $this->removeParams($data);
        $this->removeParams($data2);

        header('Location:'.$url);
    }

}
