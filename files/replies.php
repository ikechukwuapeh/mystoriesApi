<?php
include 'head.php';

switch ($requestType) {
    case 'POST':
        createReply();
        break;
    case 'PUT':
        updateReply();
        break;
    case 'DELETE':
        removeReply();
        break;
    case 'GET':
        getReply();
        break;
    default:
        echo "You are about to do an unimplemented feature";
        break;
}


function createReply(){
    global $conn;
    $data = json_decode(file_get_contents("php://input"));
    $err_flag = false;
    $err = [];

    if(empty($data->reply_content)){
        $err_flag = true;
        $err["replycontentRequired"] = "Reply content is required";
    }

    if(empty($data->story_id)){
        $err_flag = true;
        $err["storyIdRequired"] = "story Id is required";
    }else{
        if(is_numeric($data->story_id)){
            if(!checkStoryId($data->story_id)){
                $err_flag = true;
                $err["storyIdNotValid"] = "Invalid story ID";
            }
        }else{
            $err_flag = true;
            $err["storyNotNumeric"] = "Non Numeric cartegory ID supplied";
        }
    }

    if(empty($data->reply_user_id)){
        $err_flag = true;
        $err["UserIdRequired"] = "User Id is required";
    }else{
        if(is_numeric($data->reply_user_id)){
            if(!checkUserId($data->reply_user_id)){
                $err_flag = true;
                $err["UserIdNotValid"] = "Invalid User ID";
            }
        }else{
            $err_flag = true;
            $err["UserNotNumeric"] = "Non Numeric cartegory ID supplied";
        }
    }

    if($err_flag){
        echo json_encode($err);
        return;
    }
    $reply_date = time();
    $insert_query = "INSERT INTO `story_replies` (reply_content, reply_date, story_id, reply_user_id) VALUES (:reply_content, :reply_date, :story_id, :reply_user_id)";
    $insert_stmt = $conn->prepare($insert_query);
    $insert_stmt->bindValue(":reply_content", htmlspecialchars(strip_tags(ucwords($data->reply_content))),PDO::PARAM_STR);
    $insert_stmt->bindValue(":story_id", htmlspecialchars(strip_tags(ucwords($data->story_id))),PDO::PARAM_STR);
    $insert_stmt->bindValue(":reply_user_id", htmlspecialchars(strip_tags($data->reply_user_id)),PDO::PARAM_STR);
    $insert_stmt->bindValue(":reply_date", htmlspecialchars(strip_tags($reply_date)),PDO::PARAM_STR);
    if($insert_stmt->execute()){
        $msg["message"] = 'Data Inserted Successfully';
    }else{
        $msg["message"] = 'Data not Inserted';
    } 
    
    echo json_encode($msg);
}


//Requires that you provide the ID of the story you want to fetch its replies or returns all replies if the story ID is not provided
function getReply(){
    global $conn;
    $story_id = @$_GET["story_id"];
    $get_story_replies = is_numeric($story_id) ? "SELECT story_replies.*,  users.* FROM `story_replies` INNER JOIN users WHERE story_replies.story_id='$story_id' AND story_replies.reply_user_id = users.profile_id AND active=1" : "SELECT story_replies.*,  users.* FROM `story_replies` INNER JOIN users WHERE story_replies.reply_user_id = users.profile_id AND active=1";
    $get_stmt = $conn->prepare($get_story_replies);
    $get_stmt->execute();
    if($get_stmt->rowCount() > 0){
        $posts_array = [];
        while($row = $get_stmt->fetch(PDO::FETCH_ASSOC)){
            $post_data = [
                'reply_id' => $row['reply_id'],
                'story_id' => $row['story_id'],
                'reply_content' => $row['reply_content'],
                'user_detail' => [
                    'user_id' => $row['reply_user_id'],
                    'username' => $row['username']
                ]
            ];
            // PUSH POST DATA IN OUR $posts_array ARRAY
            array_push($posts_array, $post_data);
        }
        //SHOW POST/POSTS IN JSON FORMAT
        echo json_encode($posts_array);        
    }else{
        $msg['message'] = 'Invlid ID or No story_replies';
        // ECHO MESSAGE IN JSON FORMAT
        echo  json_encode($msg);
    }
}


//Requires that you supply the reply_id that you want to remove
function removeReply(){
    global $conn;
    $reply_id = @$_GET["story_id"]; //this represents the REPLY_ID you want to remove and not the story ID as shown here
    $get_reply = "SELECT * FROM `story_replies` WHERE reply_id=:reply_id"; // fetch the particular reply row
    $get_stmt = $conn->prepare($get_reply);
    $get_stmt->bindValue(':reply_id', $reply_id,PDO::PARAM_STR);
    $get_stmt->execute();
    if($get_stmt->rowCount() > 0){
        $get_delete_reply = "UPDATE `story_replies` SET active = 0 WHERE reply_id=:reply_id";
        $get_delete = $conn->prepare($get_delete_reply);
        $get_delete->bindValue(':reply_id', $reply_id,PDO::PARAM_INT);
       if($get_delete->execute()){
            $msg['message'] = 'Reply Deleted Successfully';
        }else{
            $msg['message'] = 'Reply Not Deleted';
        }
    }else{
        $msg['message'] = 'Invlid ID';
    }
    // ECHO MESSAGE IN JSON FORMAT
    echo  json_encode($msg);
}

//This updates the reply row from the REPLY ID supplied to it and just requires the reply content field
function updateReply(){
    global $conn;
    $reply_id = @$_GET["story_id"]; //this represents the REPLY_ID you want to remove and not the story ID as shown here
    $data = json_decode(file_get_contents("php://input"));
    $err_flag = false;
    $err = [];
    $get_reply = "SELECT * FROM `story_replies` WHERE reply_id=:reply_id";
    $get_stmt = $conn->prepare($get_reply);
    $get_stmt->bindValue(':reply_id', $reply_id,PDO::PARAM_STR);
    $get_stmt->execute();
    if($get_stmt->rowCount() > 0){
        if(empty($data->reply_content)){
            $err_flag = true;
            $err["replyContentNeeded"] = "Reply content is required";
        }
        if($err_flag){
            echo json_encode($err);
            return;
        }

        $update_query = "UPDATE story_replies SET reply_content = :reply_content WHERE reply_id = :reply_id";
        $update_stmt = $conn->prepare($update_query);
        
        // DATA BINDING AND REMOVE SPECIAL CHARS AND REMOVE TAGS
        $update_stmt->bindValue(':reply_content', htmlspecialchars(strip_tags($data->reply_content)),PDO::PARAM_STR);
        $update_stmt->bindValue(':reply_id', htmlspecialchars(strip_tags($reply_id)),PDO::PARAM_STR);
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