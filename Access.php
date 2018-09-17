<?php

require_once __DIR__.'/FlickrApi.php';

class Access
{
    public function getTokens()
    {
        $flickr = new FlickrApi();
        $flickr->auth();
        $flickr->validateAccount();
    }
}

$obj=new Access();
$obj->getTokens();

