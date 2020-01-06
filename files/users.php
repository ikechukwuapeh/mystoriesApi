<?php
include 'head.php';

switch ($requestType) {
    case 'POST':
        createUser();
        break;
    case 'PUT':
        updateUser();
        break;
    case 'DELETE':
        removeUser();
        break;
    case 'GET':
        getUser();
        break;
    default:
        echo "You are about to do an unimplemented feature";
        break;
}

function createUser(){
    global $conn;
    $data = json_decode(file_get_contents("php://input"));
    $err_flag = false;
    $err = [];

    if(empty($data->username)){
        $err_flag = true;
        $err["usernameRequired"] = "username field is required";
    }else{
        if(checkUsername($data->username)){
            $err_flag = true;
            $err["usernameExist"] = "Username has already been used";
        }
    }

    if(empty($data->phone)){
        $err_flag = true;
        $err["phoneRequired"] = "phone field is required";
    }else{
        if(checkPhone($data->phone)){
            $err_flag = true;
            $err["phoneExist"] = "Phone has already been used";
        }
    }

    if(empty($data->email)){
        $err_flag = true;
        $err["emailRequired"] = "email field is required";
    }else{
        $email = filter_var($data->email, FILTER_SANITIZE_EMAIL);
		if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            if(checkEmail($data->email)){
                $err_flag = true;
                $err["emailExist"] = "Email has already been used";
            }
		}else{
            $err["BadEmail"] = "Invalid Email format";
			$err_flag = true;
		}
    }

    if(empty($data->password)){
        $err_flag = true;
        $err["passwordRequired"] = "password field is required";
    }else{
        $password = md5(sha1($data->password));
    }
    
    
    if($err_flag){
        echo json_encode($err);
        return;
    }
    
    $keys = (array)$data;
    $fields = ['name', 'username','email','password','user_img_path','bio','gender','phone'];
    $availableFields = [];
    foreach ($keys as $key=>$value) {
        // $value = key($key);
        if(in_array($key,$fields)){
           if($key == "password"){
               continue;
           }
           array_push($availableFields,$key);
        }
    }

    $word = "";
    foreach ($availableFields as $key) {
        $word .= "`". $key ."`" . ",";
    }
    $word .= "`password`,";
    $word .= "`register_date`";

    $values = "";
    foreach ($availableFields as $key) {
        $values .= ":". $key. ",";
    }
    $password = md5(sha1($data->password));

    $values .= ":password,";
    $values .= ":register_date";

    $insert_query = "INSERT INTO `users` ($word) VALUES ($values)";

    $insert_stmt = $conn->prepare($insert_query);
    
        // DATA BINDING
    foreach ($availableFields as $value) {
        $insert_stmt->bindValue(":$value", htmlspecialchars(strip_tags($data->$value)),PDO::PARAM_STR);
    }
    $regdate = time();
    $insert_stmt->bindValue(":password", htmlspecialchars(strip_tags($password)),PDO::PARAM_STR);
    $insert_stmt->bindValue(":register_date",$regdate,PDO::PARAM_STR);

    if($insert_stmt->execute()){
        $msg["message"] = 'Data Inserted Successfully';
    }else{
        $msg["message"] = 'Data not Inserted';
    } 
    
    echo json_encode($msg);
    
}

function getUser(){
    global $conn;
    $user_id = @$_GET["user_id"];
    $get_users = $sql = is_numeric($user_id) ? "SELECT * FROM `users` WHERE profile_id='$user_id'" : "SELECT * FROM `users`";;
    $get_stmt = $conn->prepare($get_users);
    $get_stmt->execute();
    if($get_stmt->rowCount() > 0){
        $posts_array = [];
        while($row = $get_stmt->fetch(PDO::FETCH_ASSOC)){
            $post_data = [
                'profile_id' => $row['profile_id'],
                'name' => $row['name'],
                'email' => $row['email'],
                'phone' => $row['phone'],
                'user_img_path' => $row['user_img_path'],
                'username' => $row['username'],
                'register_date' => $row['register_date'],
                'bio' => $row['bio'],
                'ban' => $row['ban']
            ];
            // PUSH POST DATA IN OUR $posts_array ARRAY
            array_push($posts_array, $post_data);
        }
        //SHOW POST/POSTS IN JSON FORMAT
        echo json_encode($posts_array);        
    }else{
        $msg['message'] = 'Invlid ID or No Users';
        // ECHO MESSAGE IN JSON FORMAT
        echo  json_encode($msg);
    }
}

function removeUser(){
    global $conn;
    $user_id = @$_GET["user_id"];
    $get_user = "SELECT * FROM `users` WHERE profile_id=:userid";
    $get_stmt = $conn->prepare($get_user);
    $get_stmt->bindValue(':userid', $user_id,PDO::PARAM_STR);
    $get_stmt->execute();
    if($get_stmt->rowCount() > 0){
        $get_delete_user = "DELETE FROM  `users` WHERE profile_id=:userid";
        $get_delete = $conn->prepare($get_delete_user);
        $get_delete->bindValue(':userid', $user_id,PDO::PARAM_INT);
       if($get_delete->execute()){
            $msg['message'] = 'User Deleted Successfully';
        }else{
            $msg['message'] = 'User Not Deleted';
        }
    }else{
        $msg['message'] = 'Invlid ID';
    }
    // ECHO MESSAGE IN JSON FORMAT
    echo  json_encode($msg);
}

function updateUser(){
    global $conn;
    $data = json_decode(file_get_contents("php://input"));
    $err_flag = false;
    $err = [];
    $user_id = @$_GET["user_id"];

    $get_user = "SELECT * FROM `users` WHERE profile_id=:userid";
    $get_stmt = $conn->prepare($get_user);
    $get_stmt->bindValue(':userid', $user_id,PDO::PARAM_STR);
    $get_stmt->execute();
    if($get_stmt->rowCount() > 0){
        $row = $get_stmt->fetch(PDO::FETCH_ASSOC);
        if(isset($data->username) && ($data->username !=$row["username"])){
            if(checkUsername($data->username)){
                $err_flag = true;
                $err["usernameExist"] = "Username has already been used";
            }
        }
    
        if(isset($data->phone) && ($data->phone !=$row["phone"])){
            if(checkPhone($data->phone)){
                $err_flag = true;
                $err["phoneExist"] = "Phone has already been used";
            }
        }
    
        if(isset($data->email) && ($data->email !=$row["email"])){
            $email = filter_var($data->email, FILTER_SANITIZE_EMAIL);
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                if(checkEmail($data->email)){
                    $err_flag = true;
                    $err["emailExist"] = "Email has already been used";
                }
            }else{
                $err["BadEmail"] = "Invalid Email format";
                $err_flag = true;
            }
        }
        
        
        if($err_flag){
            echo json_encode($err);
            return;
        }

        $keys = (array)$data;
        $fields = ['name', 'username','email','password','user_img_path','bio','gender','phone','ban'];
        $availableFields = [];
        $availableFieldsKey = [];
        foreach ($keys as $key=>$value) {
            if(in_array($key,$fields)){
            if($key == "password"){
                continue;
            }
            array_push($availableFields,"$key=:$key");
            array_push($availableFieldsKey,$key);
            }
        }
        $polishedVals = implode(", ", $availableFields);
        if(isset($data->password)){
            $password = md5(sha1($data->password));
            $polishedVals .= ", password=:password";
        }

        $stmt  = "UPDATE `users` SET ";
        $stmt .= $polishedVals;
        $stmt .= " WHERE profile_id=:userid";

        $update_stmt = $conn->prepare($stmt);
            // DATA BINDING
        foreach ($availableFieldsKey as $value) {
            $update_stmt->bindValue(":$value", htmlspecialchars(strip_tags($data->$value)),PDO::PARAM_STR);
        }
        $update_stmt->bindValue(":userid", htmlspecialchars(strip_tags($user_id)),PDO::PARAM_INT);

        if(isset($data->password)){
        $update_stmt->bindValue(":password", htmlspecialchars(strip_tags($password)),PDO::PARAM_STR);
        }
        if($update_stmt->execute()){
            $msg["message"] = 'Data updated Successfully';
        }else{
            $msg["message"] = 'Data not Inserted';
        }          
        echo json_encode($msg);         
    }else{
        $msg['message'] = 'Invlid ID';
    }
    echo json_encode($msg);
}
?>