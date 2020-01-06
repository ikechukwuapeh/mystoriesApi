<?php
include 'head.php';

switch ($requestType) {
    case 'POST':
        echo "You are about to do an unimplemented feature";
        break;
    case 'PUT':
        echo "You are about to do an unimplemented feature";
        break;
    case 'DELETE':
        echo "You are about to do an unimplemented feature";
        break;
    case 'GET':
        getStory();
        break;
    default:
        echo "You are about to do an unimplemented feature";
        break;
}


function getStory(){
    global $conn;
    $category_id = @$_GET["category_id"];
    if(is_numeric($category_id)){
        $get_stories = "SELECT stories.*, users.* FROM `stories`INNER JOIN users WHERE stories.cat_id = '$category_id' AND stories.is_active='1' AND stories.story_by = users.profile_id";
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
    }else{
        $msg['message'] = 'Invlid ID or No stories';
        // ECHO MESSAGE IN JSON FORMAT
        echo  json_encode($msg);
    }
   
    
}
?>