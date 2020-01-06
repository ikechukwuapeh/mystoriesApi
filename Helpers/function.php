<?php
// INCLUDING DATABASE AND MAKING OBJECT
require "connection.php";

function checkUsername($username){
    global $conn;
    $get_username = "SELECT * FROM `users` WHERE username=:username";
    $get_stmt = $conn->prepare($get_username);
    $get_stmt->bindValue(':username', $username,PDO::PARAM_STR);
    $get_stmt->execute();
    if($get_stmt->rowCount() > 0){
       return true;
    }
    return false;
}

function checkEmail($email){
    global $conn;
    $get_email = "SELECT * FROM `users` WHERE email=:email";
    $get_stmt = $conn->prepare($get_email);
    $get_stmt->bindValue(':email', $email,PDO::PARAM_STR);
    $get_stmt->execute();
    if($get_stmt->rowCount() > 0){
       return true;
    }
    return false;
}

function checkPhone($phone){
    global $conn;
    $get_phone = "SELECT * FROM `users` WHERE phone=:phone";
    $get_stmt = $conn->prepare($get_phone);
    $get_stmt->bindValue(':phone', $phone,PDO::PARAM_STR);
    $get_stmt->execute();
    if($get_stmt->rowCount() > 0){
       return true;
    }
    return false;
}

function checkCatName($category){
    global $conn;
    $category = ucwords($category);
    $get_category = "SELECT * FROM `story_category` WHERE category_name=:category AND active=1";
    $get_stmt = $conn->prepare($get_category);
    $get_stmt->bindValue(':category', $category,PDO::PARAM_STR);
    $get_stmt->execute();
    if($get_stmt->rowCount() > 0){
       return true;
    }
    return false;
}


//returns true if category exists
function checkCat($categoryId){
    global $conn;
    $get_categoryId = "SELECT * FROM `story_category` WHERE category_id=:categoryId AND active=1";
    $get_stmt = $conn->prepare($get_categoryId);
    $get_stmt->bindValue(':categoryId', $categoryId,PDO::PARAM_STR);
    $get_stmt->execute();
    if($get_stmt->rowCount() > 0){
       return true;
    }
    return false;
}

function checkUserId($userId){
    global $conn;
    $get_userId = "SELECT * FROM `users` WHERE profile_id=:userId AND ban=0";
    $get_stmt = $conn->prepare($get_userId);
    $get_stmt->bindValue(':userId', $userId,PDO::PARAM_STR);
    $get_stmt->execute();
    if($get_stmt->rowCount() > 0){
       return true;
    }
    return false;
}

function checkStoryId($storyId){
    global $conn;
    $get_storyId = "SELECT * FROM `stories` WHERE story_id=:storyId";
    $get_stmt = $conn->prepare($get_storyId);
    $get_stmt->bindValue(':storyId', $storyId,PDO::PARAM_STR);
    $get_stmt->execute();
    if($get_stmt->rowCount() > 0){
       return true;
    }
    return false;
}
?>