<?php
include 'head.php';

switch ($requestType) {
    case 'POST':
        createStory();
        break;
    case 'PUT':
        updateStory();
        break;
    case 'DELETE':
        removeStory();
        break;
    case 'GET':
        getStory();
        break;
    default:
        echo "You are about to do an unimplemented feature";
        break;
}


function createStory(){
    global $conn;
    $data = json_decode(file_get_contents("php://input"));
    $err_flag = false;
    $err = [];

    if(empty($data->story_title)){
        $err_flag = true;
        $err["storyTitleRequired"] = "story Title is required";
    }

    if(empty($data->story_body)){
        $err_flag = true;
        $err["storyBodyRequired"] = "story body is required";
    }

    if(empty($data->cat_id)){
        $err_flag = true;
        $err["catIdRequired"] = "Category Id is required";
    }else{
        if(is_numeric($data->cat_id)){
            if(!checkCat($data->cat_id)){
                $err_flag = true;
                $err["CategoryIdNotValid"] = "Invalid category ID";
            }
        }else{
            $err_flag = true;
            $err["CategoryNotNumeric"] = "Non Numeric cartegory ID supplied";
        }
    }

    if(empty($data->story_by)){
        $err_flag = true;
        $err["UserFieldRequired"] = "User Id is required";
    }else{
        if(is_numeric($data->story_by)){
            if(!checkUserId($data->story_by)){
                $err_flag = true;
                $err["userIdNotValid"] = "Invalid user ID";
            }
        }else{
            $err_flag = true;
            $err["userNotNumeric"] = "Non Numeric cartegory ID supplied";
        }
    }

    if(isset($data->image_url)){
        $name = basename($data->image_url);
        list($txt, $ext) = explode(".", $name);

        $allowed_ext = array("jpg", "png","gif","jpeg");
        if(in_array($ext, $allowed_ext)){
            $name = "storiesphoto".rand(100000,999999).time();
            $name = $name.".".$ext;
            $filePath = "../uploads/$name";
            $upload = file_put_contents($filePath,file_get_contents($data->image_url));
            if($upload){
                $image_url = "http://localhost:8080/storiesapi/uploads/$name";
            }
            else{
                $err_flag = true;
                $err["imageUploadError"] = "An error occured while uploading the image";
            }
        }
        else{
            $err_flag = true;
            $err["imageformatError"] = "The image format supplied is not supported. Use jpg, png, gif or jpeg formats";
        }
    }

    if($err_flag){
        echo json_encode($err);
        return;
    }

    $insert_query = (isset($data->image_url)) ? "INSERT INTO `stories` (story_title, story_body,	cat_id, story_by, image_url, create_date) VALUES (:story_title, :story_body, :cat_id, :story_by, :image_url, :create_date)" : "INSERT INTO `stories` (story_title, story_body,	cat_id, story_by, create_date) VALUES (:story_title, :story_body, :cat_id, :story_by,:create_date)";


    $insert_stmt = $conn->prepare($insert_query);
    $create_date = time();
    $insert_stmt->bindValue(":story_title", htmlspecialchars(strip_tags(ucwords($data->story_title))),PDO::PARAM_STR);
    $insert_stmt->bindValue(":story_body", htmlspecialchars(strip_tags($data->story_body)),PDO::PARAM_STR);
    $insert_stmt->bindValue(":cat_id", htmlspecialchars(strip_tags($data->cat_id)),PDO::PARAM_STR);
    $insert_stmt->bindValue(":story_by", htmlspecialchars(strip_tags($data->story_by)),PDO::PARAM_STR);
    $insert_stmt->bindValue(":create_date", htmlspecialchars(strip_tags($create_date)),PDO::PARAM_STR);

    if(isset($image_url)){
        $insert_stmt->bindValue(":image_url", htmlspecialchars(strip_tags($image_url)),PDO::PARAM_STR);
    }
    if($insert_stmt->execute()){
        $msg["message"] = 'Data Inserted Successfully';
    }else{
        $msg["message"] = 'Data not Inserted';
    } 
    
    echo json_encode($msg);
    
}

function getStory(){
    global $conn;
    $story_id = @$_GET["story_id"];
    $get_stories = $sql = is_numeric($story_id) ? "SELECT stories.*, users.*, story_category.* FROM `stories`INNER JOIN users, story_category WHERE stories.story_id = '$story_id' AND stories.is_active='1' AND stories.story_by = users.profile_id AND stories.cat_id = story_category.category_id" : "SELECT stories.*, users.*, story_category.* FROM `stories`INNER JOIN users, story_category WHERE  stories.is_active='1' AND stories.story_by = users.profile_id AND stories.cat_id = story_category.category_id";
    $get_stmt = $conn->prepare($get_stories);
    $get_stmt->execute();
    if($get_stmt->rowCount() > 0){
        $posts_array = [];
        while($row = $get_stmt->fetch(PDO::FETCH_ASSOC)){
            
            $post_data = [
                'story_id' => $row['story_id'],
                'story_title' => $row['story_title'],
                'story_body' => $row['story_body'],
                'image_url' => $row['image_url'],
                'create_date' => $row['create_date'],
                'num_replies' => $row['num_replies'],
                'creator_details' => [
                    'creator_id' => $row['story_by'],
                    'creator_username' => $row['username']
                ],
                'story_category' => [
                    'category_id' => $row['cat_id'],
                    'category_name' => $row['category_name']
                ]
            ];
            // PUSH POST DATA IN OUR $posts_array ARRAY
            array_push($posts_array, $post_data);
        }
        //SHOW POST/POSTS IN JSON FORMAT
        echo json_encode($posts_array);        
    }else{
        $msg['message'] = 'Invlid ID or No stories';
        // ECHO MESSAGE IN JSON FORMAT
        echo  json_encode($msg);
    }
}

function removeStory(){
    global $conn;
    $story_id = @$_GET["story_id"];
    $get_story = "SELECT * FROM `stories` WHERE story_id=:story_id";
    $get_stmt = $conn->prepare($get_story);
    $get_stmt->bindValue(':story_id', $story_id,PDO::PARAM_STR);
    $get_stmt->execute();
    if($get_stmt->rowCount() > 0){
        $get_delete_story = "UPDATE `stories` SET is_active = 0 WHERE story_id=:story_id";
        $get_delete = $conn->prepare($get_delete_story);
        $get_delete->bindValue(':story_id', $story_id,PDO::PARAM_INT);
       if($get_delete->execute()){
            $msg['message'] = 'Story Deleted Successfully';
        }else{
            $msg['message'] = 'Story Not Deleted';
        }
    }else{
        $msg['message'] = 'Invlid ID';
    }
    // ECHO MESSAGE IN JSON FORMAT
    echo  json_encode($msg);
}

function  updateStory(){
    global $conn;
    $story_id = @$_GET["story_id"];
    $data = json_decode(file_get_contents("php://input"));
    $err_flag = false;
    $err = [];
    $get_story = "SELECT * FROM `stories` WHERE story_id=:story_id";
    $get_stmt = $conn->prepare($get_story);
    $get_stmt->bindValue(':story_id', $story_id,PDO::PARAM_STR);
    $get_stmt->execute();
    if($get_stmt->rowCount() > 0){
        $row = $get_stmt->fetch(PDO::FETCH_ASSOC);
        if(isset($data->cat_id)){
            if(is_numeric($data->cat_id)){
                if(!checkCat($data->cat_id)){
                    $err_flag = true;
                    $err["CategoryIdNotValid"] = "Invalid category ID";
                }
            }else{
                $err_flag = true;
                $err["CategoryNotNumeric"] = "Non Numeric cartegory ID supplied";
            }
            $cat_id = $data->cat_id;

        }else{
            $cat_id = $row['cat_id'];
        }
    
        if(isset($data->story_by)){
            if(is_numeric($data->story_by)){
                if(!checkUserId($data->story_by)){
                    $err_flag = true;
                    $err["userIdNotValid"] = "Invalid user ID";
                }
            }else{
                $err_flag = true;
                $err["userNotNumeric"] = "Non Numeric cartegory ID supplied";
            }
            $story_by = $data->story_by;
        }else{
            $story_by = $row['story_by'];
        }
        if(isset($data->image_url)){
            $name = basename($data->image_url);
            list($txt, $ext) = explode(".", $name);
    
            $allowed_ext = array("jpg", "png","gif","jpeg");
            if(in_array($ext, $allowed_ext)){
                $name = "storiesphoto".rand(100000,999999).time();
                $name = $name.".".$ext;
                $filePath = "../uploads/$name";
                $upload = file_put_contents($filePath,file_get_contents($data->image_url));
                if($upload){
                    $image_url = "http://localhost:8080/storiesapi/uploads/$name";
                }
                else{
                    $err_flag = true;
                    $err["imageUploadError"] = "An error occured while uploading the image";
                }
            }
            else{
                $err_flag = true;
                $err["imageformatError"] = "The image format supplied is not supported. Use jpg, png, gif or jpeg formats";
            }
        }else{
            $image_url = $row['image_url'];
        }
    
        if($err_flag){
            echo json_encode($err);
            return;
        }
        $story_title = isset($data->story_title) ? $data->story_title : $row['story_title'];
        $story_body = isset($data->story_body) ? $data->story_body : $row['story_body'];

        $update_query = "UPDATE `stories` SET story_title = :story_title, story_body = :story_body, image_url = :image_url, cat_id = :cat_id, story_by = :story_by 
        WHERE story_id = :story_id";
        
        $update_stmt = $conn->prepare($update_query);
        
        // DATA BINDING AND REMOVE SPECIAL CHARS AND REMOVE TAGS
        $update_stmt->bindValue(':story_title', htmlspecialchars(strip_tags($story_title)),PDO::PARAM_STR);
        $update_stmt->bindValue(':story_body', htmlspecialchars(strip_tags($story_body)),PDO::PARAM_STR);
        $update_stmt->bindValue(':story_by', htmlspecialchars(strip_tags($story_by)),PDO::PARAM_STR);
        $update_stmt->bindValue(':image_url', htmlspecialchars(strip_tags($image_url)),PDO::PARAM_STR);
        $update_stmt->bindValue(':cat_id', htmlspecialchars(strip_tags($cat_id)),PDO::PARAM_STR);
        $update_stmt->bindValue(':story_id', $story_id,PDO::PARAM_INT);

        if($update_stmt->execute()){
            $msg["message"] = 'Data updated Successfully';
        }else{
            $msg["message"] = 'Data not Inserted';
        }
    }
    else{
        $msg['message'] = 'Invlid ID';
    }
    echo json_encode($msg);
}
?>