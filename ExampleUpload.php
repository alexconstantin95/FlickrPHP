<?php

require_once __DIR__.'/FlickrApi.php';

class ExampleUpload
{
    public function uploadPhoto()
    {
        $file=$_FILES['photo']['tmp_name'];
        $title=$_POST['title'];
        $description=$_POST['description'];
        $tags=$_POST['tags'];
        $is_public=$_POST['is_public'];
        $is_family=$_POST['is_family'];
        $is_friend=$_POST['is_friend'];

        $flickApi = new FlickrApi();
        $flickApi->auth();
        $flickApi->uploadPhoto($file, $title, $description, $tags,$is_public,$is_family,$is_friend);
    }
}

$obj=new ExampleUpload();
$obj->uploadPhoto();