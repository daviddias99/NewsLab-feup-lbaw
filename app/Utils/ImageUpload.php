<?php

namespace App\Utils;


class ImageUpload {

  public static function uploadPostImage($postID, $image) {
    // function assumes that the image is valid and verification was done before
    $date = date('Y-m-d H:i:s');
    $imageSalt = random_bytes(5);
    $imageName = hash("sha256", $postID . $date . $imageSalt) . "." . $image->getClientOriginalExtension();

    // Generate filenames for original, small and medium files
    $image->move(storage_path('app/public/images/posts'), $imageName);

    // returns the image name, so it can be used to update the user photo in the database
    return $imageName;
  }

  public static function uploadUserImage($userID, $image) {
    // function assumes that the image is valid and verification was done before
    $date = date('Y-m-d H:i:s');
    $imageSalt = random_bytes(5);
    $imageName = hash("sha256", $userID . $date . $imageSalt) . "." . $image->getClientOriginalExtension();

    // Generate filenames for original, small and medium files
    $image->move(storage_path('app/public/images/users'), $imageName);

    // returns the image name, so it can be used to update the user photo in the database
    return $imageName;
  }

  public static function deletePostImage($imageName) {
    unlink(storage_path('app/public/images/posts') . "/$imageName");
  }

  public static function deleteUserImage($imageName) {
    unlink(storage_path('app/public/images/users') . "/$imageName");
  }
}

?>
