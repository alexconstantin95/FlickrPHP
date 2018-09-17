<?php

class FlickrApi
{
    /*todo: complete YOUR CREDENTIALS from https://www.flickr.com/services/apps/by/YOUR_ACCOUNT*/
    const KEY = "90bc0aa0aca691a86a97ba4bab311222";
    const SECRET = "69edb0e10981d55d";


    /*todo: complete these variables with the ones from access_tokens.txt generated file*/
    const ACCESS_TOKEN = "72157640585471663-c3a4a50665e4fc61";
    const ACCESS_SECRET = "bad794d5f2bf3dab";

    /*Oauth System Data*/
    const VERSION = "1.0";
    const SIGNATURE_METHOD = "HMAC-SHA1";

    /*urls*/
    const CALLBACK_URL = "http://localhost/FlickrPHP/Authorization.php"; //IMPORTANT: path to Authorization.php file
    const REQUEST_TOKEN_URL = "https://www.flickr.com/services/oauth/request_token";
    const AUTH_URL = "https://www.flickr.com/services/oauth/authorize";
    const ACCESS_URL = "https://www.flickr.com/services/oauth/access_token";
    const OAUTH_URL = "https://api.flickr.com/services/rest";
    const UPLOAD_URL = "https://up.flickr.com/services/upload/";

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
        $signature = $this->signingRequests();

        //get request token
        $requestToken = $this->requestToken($signature);

        if (is_array($requestToken))
        {
            $authUrl = $this->getUserAuthUrl($requestToken);
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

        $this->setParams($data);

        $baseString = "GET&" . urlencode(self::REQUEST_TOKEN_URL) . "&" . urlencode(http_build_query($this->getParams()));

        $key = self::SECRET . "&";

        $signature = base64_encode(hash_hmac('sha1', $baseString, $key, true));

        $this->removeParams($data);

        return $signature;
    }

    private function requestToken($signature)
    {
        $data=[
            'oauth_callback' => self::CALLBACK_URL,
            'oauth_signature' => $signature
        ];

        $this->setParams($data);

        //get oauth tokens
        $url=self::REQUEST_TOKEN_URL."?".http_build_query($this->getParams());
        $content = $this->getPage($url);

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


        $this->removeParams($data);

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

    public function setParams($data)
    {
        foreach($data as $key=>$value)
        {
            if(!isset($this->params[$key]))
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

        $baseString= "GET&".urlencode(self::OAUTH_URL)."&".urlencode(http_build_query($this->getParams()));

        $key=self::SECRET."&".self::ACCESS_SECRET;
        $signature = base64_encode(hash_hmac('sha1', $baseString, $key, true));

        $data2=[
            'oauth_signature'=>$signature,
        ];

        $this->setParams($data2);

        $url=self::OAUTH_URL."?".http_build_query($this->getParams());

        header('Location:'.$url);
    }

    public function uploadPhoto($tmp_file, $title, $description, $tags, $is_public, $is_family, $is_friend)
    {
        $data = [
            'oauth_token'=>self::ACCESS_TOKEN,
            'title' => $title,
            'description' => $description,
            'tags' => $tags,
            'is_public' => $is_public,
            'is_family' => $is_family,
            'is_friend' => $is_friend,
            'async' => '1'
        ];

        $this->setParams($data);

        $baseString = "POST&" . urlencode(self::UPLOAD_URL) . "&" . urlencode(http_build_query($this->getParams()));

        $key = self::SECRET . "&" . self::ACCESS_SECRET;
        $signature = base64_encode(hash_hmac('sha1', $baseString, $key, true));

        $data2=[
            'oauth_signature' => $signature,
        ];

        $this->setParams($data2);

        $params=[
            'photo' => $this->makeCurlFile($tmp_file)
        ];
        $this->setParams($params);

        $response = $this->getPage(self::UPLOAD_URL,1,1);
    }

    private function makeCurlFile($file)
    {
        $mime = mime_content_type($file);
        $info = pathinfo($file);
        $name = $info['basename'];
        $output = new CURLFile($file, $mime, $name);
        return $output;
    }

    private function build_data_files($boundary, $fields, $files)
    {
        $data = '';
        $eol = "\r\n";

        $delimiter = '-------------' . $boundary;

        foreach ($fields as $name => $content)
        {
            $data .= "--" . $delimiter . $eol
                . 'Content-Disposition: form-data; name="' . $name . "\"".$eol.$eol
                . $content . $eol;
        }


        foreach ($files as $name => $cURLFile)
        {
            //transform file to binary
            $file = fopen($cURLFile->name, "rb");
            $photoContent = fread($file, filesize($cURLFile->name));
            fclose($file);

            $data .= "--" . $delimiter . "\r\n"
                . 'Content-Disposition: form-data; name="' . $name . '"; filename="' . $name . '"' . "\r\n"
                .'Content-type: '.$cURLFile->mime.
                "\r\n\r\n"
                . $photoContent . "\r\n";
        }
        $data .= "--" . $delimiter . "--".$eol;

        return $data;
    }

    public function getPage($url, $post=0, $uploadPhoto=0)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);

        if($post)
        {
            curl_setopt($ch, CURLOPT_POST, TRUE);
            if($uploadPhoto)
            {
                $files = ['photo'=> $this->params['photo']];
                unset($this->params['photo']);

                $boundary = uniqid();
                $delimiter = '-------------' . $boundary;

                $data = $this->getParams();
                $post_data = $this->build_data_files($boundary, $data, $files);

                $header= [
                    'Accept:',
                    "Content-Type: multipart/form-data; boundary=" . $delimiter,
                    "Content-Length: " . strlen($post_data)
                ];

                curl_setopt($ch, CURLOPT_SAFE_UPLOAD, true);  //mandatory for PHP version> 5.5
                curl_setopt($ch, CURLOPT_HEADER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
            }
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        }
        else
        {
            curl_setopt($ch, CURLOPT_HEADER, false);
        }


        $content = curl_exec($ch);
        return $content;
    }

}
