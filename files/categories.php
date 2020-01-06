<?php
include 'head.php';

switch ($requestType) {
    case 'POST':
        createCategory();
        break;
    case 'PUT':
        updateCategory();
        break;
    case 'DELETE':
        removeCategory();
        break;
    case 'GET':
        getCategory();
        break;
    default:
        echo "You are about to do an unimplemented feature";
        break;
}

function createCategory(){
    global $conn;
    $data = json_decode(file_get_contents("php://input"));
    $err_flag = false;
    $err = [];

    if(empty($data->category_name)){
        $err_flag = true;
        $err["categoryRequired"] = "category name field is required";
    }else{
        if(checkCatName($data->category_name)){
            $err_flag = true;
            $err["categoryExist"] = "Category exists";
        }
    }

    if(empty($data->category_description)){
        $err_flag = true;
        $err["categoryDescriptionRequired"] = "category Description field is required";
    }
    if($err_flag){
        echo json_encode($err);
        return;
    }
    $insert_query = "INSERT INTO `story_category` (category_name, category_description) VALUES (:category_name, :category_description)";
    $insert_stmt = $conn->prepare($insert_query);
    $insert_stmt->bindValue(":category_name", htmlspecialchars(strip_tags(ucwords($data->category_name))),PDO::PARAM_STR);
    $insert_stmt->bindValue(":category_description", htmlspecialchars(strip_tags($data->category_description)),PDO::PARAM_STR);
    if($insert_stmt->execute()){
        $msg["message"] = 'Data Inserted Successfully';
    }else{
        $msg["message"] = 'Data not Inserted';
    } 
    
    echo json_encode($msg);

}

function getCategory(){
    global $conn;
    $category_id = @$_GET["category_id"];
    $get_story_category = $sql = is_numeric($category_id) ? "SELECT * FROM `story_category` WHERE category_id='$category_id' AND active=1" : "SELECT * FROM `story_category` WHERE active=1";
    $get_stmt = $conn->prepare($get_story_category);
    $get_stmt->execute();
    if($get_stmt->rowCount() > 0){
        $posts_array = [];
        while($row = $get_stmt->fetch(PDO::FETCH_ASSOC)){
            $post_data = [
                'category_id' => $row['category_id'],
                'category_name' => $row['category_name'],
                'category_description' => $row['category_description']
            ];
            // PUSH POST DATA IN OUR $posts_array ARRAY
            array_push($posts_array, $post_data);
        }
        //SHOW POST/POSTS IN JSON FORMAT
        echo json_encode($posts_array);        
    }else{
        $msg['message'] = 'Invlid ID or No story_category';
        // ECHO MESSAGE IN JSON FORMAT
        echo  json_encode($msg);
    }
}

function removeCategory(){
    global $conn;
    $category_id = @$_GET["category_id"];
    $get_category = "SELECT * FROM `story_category` WHERE category_id=:category_id";
    $get_stmt = $conn->prepare($get_category);
    $get_stmt->bindValue(':category_id', $category_id,PDO::PARAM_STR);
    $get_stmt->execute();
    if($get_stmt->rowCount() > 0){
        $get_delete_category = "UPDATE `story_category` SET active = 0 WHERE category_id=:category_id";
        $get_delete = $conn->prepare($get_delete_category);
        $get_delete->bindValue(':category_id', $category_id,PDO::PARAM_INT);
       if($get_delete->execute()){
            $msg['message'] = 'Category Deleted Successfully';
        }else{
            $msg['message'] = 'Category Not Deleted';
        }
    }else{
        $msg['message'] = 'Invlid ID';
    }
    // ECHO MESSAGE IN JSON FORMAT
    echo  json_encode($msg);
}

function updateCategory(){
    global $conn;
    $category_id = @$_GET["category_id"];
    $data = json_decode(file_get_contents("php://input"));
    $err_flag = false;
    $err = [];
    $get_category = "SELECT * FROM `story_category` WHERE category_id=:categoryid";
    $get_update_category = $conn->prepare($get_category);
    $get_update_category->bindValue(':categoryid', $category_id,PDO::PARAM_STR);
    $get_update_category->execute();
    if($get_update_category->rowCount() > 0){
        $row = $get_update_category->fetch(PDO::FETCH_ASSOC);
        if(isset($data->category_name) && (ucwords($data->category_name) !=$row["category_name"])){
            if(checkCatName($data->category_name)){
                $err_flag = true;
                $err["category_nameExist"] = "category_name exists";
            }
        }
        if($err_flag){
            echo json_encode($err);
            return;
        }

        $keys = (array)$data;
        $fields = ['category_name', 'category_description'];
        $availableFields = [];
        $availableFieldsKey = [];
        foreach ($keys as $key=>$value) {
            if(in_array($key,$fields)){
                array_push($availableFields,"$key=:$key");
                array_push($availableFieldsKey,$key);
            }
        }
        $polishedVals = implode(", ", $availableFields);
        $stmt  = "UPDATE `story_category` SET ";
        $stmt .= $polishedVals;
        $stmt .= " WHERE category_id=:categoryid";
        $update_stmt = $conn->prepare($stmt);
            // DATA BINDING
        foreach ($availableFieldsKey as $value) {
            $update_stmt->bindValue(":$value", htmlspecialchars(strip_tags($data->$value)),PDO::PARAM_STR);
        }
        $update_stmt->bindValue(":categoryid", htmlspecialchars(strip_tags($category_id)),PDO::PARAM_INT);
        if($update_stmt->execute()){
            $msg["message"] = 'Data updated Successfully';
        }else{
            $msg["message"] = 'Data not Inserted';
        }

    }else{
        $msg['message'] = 'Invlid ID';
    }
    echo json_encode($msg);
}
?>