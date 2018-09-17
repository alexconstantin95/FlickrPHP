<?php

require_once __DIR__.'/FlickrApi.php';

class ExampleMethod
{
    //test method flickr.test.login from https://www.flickr.com/services/api/
    public function testAccess()
    {
        $flickr = new FlickrApi();
        $flickr->auth();

        $params=[];
        $flickr->callMethod('flickr.test.login',$params);
    }
}

$obj=new ExampleMethod();
$obj->testAccess();

