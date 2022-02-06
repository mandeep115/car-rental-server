<?php
// $servername = "localhost";
// $username = "username";
// $password = "password";

// $conn = new mysqli($servername, $username, $password);
require 'vendor/autoload.php';
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: X-Requested-With, Content-Type, Origin, Cache-Control, Pragma, Authorization, Accept, Accept-Encoding");
session_start();
use Dotenv\Dotenv;
$dotenv = new DotEnv(__DIR__);
$dotenv->load();

function getUser($user_id) {
    $db = new mysqli(getenv('DB_HOST'), getenv('DB_USER'), getenv('DB_PASS'), getenv('DB_NAME'));
    $sql = "SELECT * FROM user WHERE id = '$user_id'";
    $result = $db->query($sql);
    // var_dump($db->error);
    // var_dump($result);
    $user = $result->fetch_assoc();
    $db->close();
    return $user;
}

function getCar($car_id) {
    $db = new mysqli(getenv('DB_HOST'), getenv('DB_USER'), getenv('DB_PASS'), getenv('DB_NAME'));
    $sql = "SELECT * FROM car WHERE id = '$car_id'";
    $result = $db->query($sql);
    $car = $result->fetch_assoc();
    $db->close();
    // var_dump($car);
    return $car;
}

function updateCar($car_id) {
    $car = getCar($car_id);
    $db = new mysqli(getenv('DB_HOST'), getenv('DB_USER'), getenv('DB_PASS'), getenv('DB_NAME'));
    $sql = "SELECT * FROM rent WHERE car_id = '$car_id' ORDER BY start_date DESC";
    $result = $db->query($sql);
    // var_dump($result);
    $rent = $result->fetch_assoc();
    $num_of_days = $rent["num_of_days"];
    $start_date = $rent["start_date"];
    $end_date = date('Y-m-d', strtotime($start_date. ' + '. $num_of_days .' days'));
    if ($end_date < date('Y-m-d')) {
        $sql = "UPDATE car SET is_available = true WHERE id = '$car_id'";
        if ($db->query($sql) === TRUE) {
            $db->close();
            return TRUE;
        } 
    } else {
        return FALSE;
    }
}

function register() {
    $jsonData = trim(file_get_contents('php://input'), "\xEF\xBB\xBF");
    $data = json_decode($jsonData, true);
    $username = $data['username'];
    $password = $data['password'];
    $usertype = $data['usertype'];
    if (empty($username) || empty($password) || empty($usertype)) {
        return json_encode(array('status' => 'error', 'message' => 'Username or password or usertype is empty'));
    }
    $db = new mysqli(getenv('DB_HOST'), getenv('DB_USER'), getenv('DB_PASS'), getenv('DB_NAME'));
    if ($db->connect_error) {
        return json_encode(array('status' => 'error', 'message' => 'Database connection error'));
    }
    $sql = "SELECT * FROM user WHERE username = '$username'";
    $result = $db->query($sql);
    // var_dump($result);
    if ($result->num_rows > 0) {
        return json_encode(array('status' => 'error', 'message' => 'Username already exists'));
    } 
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $sql = "INSERT INTO user (username, password, usertype) VALUES ('$username', '$hash', '$usertype')";
    if ($db->query($sql) === TRUE) {
        $getUserSQL = "SELECT * FROM user WHERE username = '$username'";
        $result = $db->query($getUserSQL);
        $row = $result->fetch_assoc(); 
        $_SESSION['username'] = $row['id'];
        // var_dump($row);
        return json_encode(array('status' => 'success', 'message' => 'User registered', 'user' => $row));
    } else {
        return json_encode(array('status' => 'error', 'message' => 'Error: ' . $sql . '<br>' . $db->error));
    }
}

function login() {
    $jsonData = trim(file_get_contents('php://input'), "\xEF\xBB\xBF");
    $data = json_decode($jsonData, true);
    $username = $data['username'];
    $password = $data['password'];
    if (empty($username) || empty($password)) {
        return json_encode(array('status' => 'error', 'message' => 'Username or password is empty'));
    }
    $db = new mysqli(getenv('DB_HOST'), getenv('DB_USER'), getenv('DB_PASS'), getenv('DB_NAME'));
    if ($db->connect_error) {
        return json_encode(array('status' => 'error', 'message' => 'Database connection error'));
    }
    $sql = "SELECT * FROM user WHERE username = '$username'";
    $result = $db->query($sql);
    if ($result->num_rows == 0) {
        return json_encode(array('status' => 'error', 'message' => 'Username does not exist'));
    } 
    $row = $result->fetch_assoc();
    if (password_verify($password, $row['password'])) {
        $_SESSION['username'] = $row['id'];
        // var_dump($_SESSION['username']);

        return json_encode(array('status' => 'success', 'message' => 'User logged in', 'user' => $row));
    } else {
        return json_encode(array('status' => 'error', 'message' => 'Incorrect password'));
    }
}

function logout() {
    $_SESSION['username'] = "";
    return json_encode(array('status' => 'success', 'message' => 'User logged out'));
}

function addCarForRent() {
    $jsonData = trim(file_get_contents('php://input'), "\xEF\xBB\xBF");
    $data = json_decode($jsonData, true);
    // $user_id = $data['user_id'];
    $user_id = $_SESSION['username'];
    $agency_id = $data['agency_id'];
    $car_model = $data['car_model'];
    $car_num = $data['car_num'];
    $car_seat_cap = $data['car_seat_cap'];
    $car_rent_per_day = $data['car_rent_per_day'];
    $car_is_available = true;
    if (empty($agency_id) || empty($car_model) || empty($car_num) || empty($car_seat_cap) || empty($car_rent_per_day) || empty($car_is_available)) {
        return json_encode(array('status' => 'error', 'message' => 'Agency id or car model or car number or car seat capacity or car rent per day or car is available is empty'));
    }
    $user = getUser($user_id);
    if ($user['usertype'] != 'agency') {
        return json_encode(array('status' => 'error', 'message' => 'You are not a agency'));
    }
    $db = new mysqli(getenv('DB_HOST'), getenv('DB_USER'), getenv('DB_PASS'), getenv('DB_NAME'));
    if ($db->connect_error) {
        return json_encode(array('status' => 'error', 'message' => 'Database connection error'));
    }
    $sql = "INSERT INTO car (agency_id, model, num, seat_cap, rent_per_day, is_available ) 
                     VALUES ('$agency_id', '$car_model', '$car_num', '$car_seat_cap', '$car_rent_per_day', '$car_is_available')";
    $result = $db->query($sql);
    if ($result === true) {
        $car = [
            "id" => null ,
            "agency_id" => $data["agency_id"] ,
            "model" => $data["car_model"] ,
            "num" => $data["car_num"] ,
            "seat_cap" => $data["car_seat_cap"] ,
            "rent_per_day" => $data["car_rent_per_day"] ,
            "is_available" => $data["car_is_available"] 
        ];

        return json_encode(array('status' => 'success', 'message' => 'Car added for rent', 'car' => $car));
    } else {
        return json_encode(array('status' => 'error', 'message' => 'Error: ' . $sql . '<br>' . $db->error));
    }
}

function updateCarForRent() {
    $jsonData = trim(file_get_contents('php://input'), "\xEF\xBB\xBF");
    $data = json_decode($jsonData, true);
    // $user_id = $data['user_id'];
    $user_id = $_SESSION['username'];
    $car_id = $data['car_id'];
    $agency_id = $data['agency_id'];
    $car_model = $data['car_model'];
    $car_num = $data['car_num'];
    $car_seat_cap = $data['car_seat_cap'];
    $car_rent_per_day = $data['car_rent_per_day'];
    
    $car = getCar($car_id);
    updateCar($car_id);
    if (empty($agency_id) || empty($car_model) || empty($car_num) || empty($car_seat_cap) || empty($car_rent_per_day)) {
        return json_encode(array('status' => 'error', 'message' => 'Agency id or car model or car number or car seat capacity or car rent per day or car is available is empty'));
    }
    $user = getUser($user_id);
    if ($user['usertype'] != 'agency') {
        return json_encode(array('status' => 'error', 'message' => 'You are not a agency'));
    }
    // var_dump($user_id, $car);
    if ($user_id != $car["agency_id"]) {
        return json_encode(array('status' => 'error', 'message' => 'This is not your car'));
    }
    $db = new mysqli(getenv('DB_HOST'), getenv('DB_USER'), getenv('DB_PASS'), getenv('DB_NAME'));
    if ($db->connect_error) {
        return json_encode(array('status' => 'error', 'message' => 'Database connection error'));
    }
    $sql = "UPDATE car SET model='$car_model', num='$car_num', seat_cap='$car_seat_cap', rent_per_day='$car_rent_per_day' WHERE id='$car_id'";
    if ($db->query($sql) === TRUE) {
        return json_encode(array('status' => 'success', 'message' => 'Car updated'));
    }
}


function bookCarForRent() {
    $jsonData = trim(file_get_contents('php://input'), "\xEF\xBB\xBF");
    $data = json_decode($jsonData, true);
    $car_id = $data['car_id'];
    // $user_id = $data['user_id'];
    $user_id = $_SESSION['username'];
    $start_date = $data['start_date'];
    $num_of_days = $data['num_of_days'];
    if (empty($car_id)  || empty($num_of_days)) {
        return json_encode(array('status' => 'error', 'message' => 'Car id or user id or start date or number of days is empty'));
    }
    $user = getUser($user_id);
    if ($user['usertype'] != 'customer') {
        return json_encode(array('status' => 'error', 'message' => 'You are not a customer'));
    }
    // $start_date = date('Y-m-d');
    $car = getCar($car_id);
    $is_car_available = updateCar($car_id);
    if ($is_car_available == false) {
        return json_encode(array('status' => 'error', 'message' => 'Car is not available'));
    }
    $db = new mysqli(getenv('DB_HOST'), getenv('DB_USER'), getenv('DB_PASS'), getenv('DB_NAME'));
    if ($db->connect_error) {
        return json_encode(array('status' => 'error', 'message' => 'Database connection error'));
    }
    $sql = "INSERT INTO rent (car_id, booked_by_id, start_date, num_of_days) 
                     VALUES ('$car_id', '$user_id', '$start_date', '$num_of_days')";
    if ($db->query($sql) === TRUE) {
        $sql = "UPDATE car SET is_available = false WHERE id='$car_id'";
        if ($db->query($sql) === TRUE) {
            return json_encode(array('status' => 'success', 'message' => 'Car booked for rent', 'car' => $car));
        } else {
            return json_encode(array('status' => 'error', 'message' => 'Error:  while updating car is_available \n' . $sql . '<br>' . $db->error));
        }
    } else {
        return json_encode(array('status' => 'error', 'message' => 'Error: ' . $sql . '\n' . $db->error));
    }
    // return date("Y-m-d");
}

function getAllCars() {
    $db = new mysqli(getenv('DB_HOST'), getenv('DB_USER'), getenv('DB_PASS'), getenv('DB_NAME'));
    if ($db->connect_error) {
        return json_encode(array('status' => 'error', 'message' => 'Database connection error'));
    }
    $sql = "SELECT * FROM car";
    $result = $db->query($sql);
    // if ($result->num_rows > 0) {
        $cars = array();
        while($row = $result->fetch_assoc()) {
            array_push($cars, $row);
        }
        return json_encode(array('status' => 'success', 'message' => 'Cars fetched', 'cars' => $cars));
    // } else {
    //     return json_encode(array('status' => 'error', 'message' => 'No cars found'));
    // }
}

function getAllAgencyCars() {
    $db = new mysqli(getenv('DB_HOST'), getenv('DB_USER'), getenv('DB_PASS'), getenv('DB_NAME'));
    if ($db->connect_error) {
        return json_encode(array('status' => 'error', 'message' => 'Database connection error'));
    }
    $user_id = $_SESSION['username'];
    $sql = "SELECT * FROM car WHERE agency_id='$user_id'";
    $result = $db->query($sql);
    // if ($result->num_rows > 0) {
        $cars = array();
        while($row = $result->fetch_assoc()) {
            array_push($cars, $row);
        }
        return json_encode(array('status' => 'success', 'message' => 'Cars fetched', 'cars' => $cars));
    // } else {
    //     return json_encode(array('status' => 'error', 'message' => 'No cars found'));
    // }
}

function getBookedCars() {
    $jsonData = trim(file_get_contents('php://input'), "\xEF\xBB\xBF");
    $data = json_decode($jsonData, true);
    // $user_id = $data['user_id'];
    $user_id = $_SESSION['username'];
    $db = new mysqli(getenv('DB_HOST'), getenv('DB_USER'), getenv('DB_PASS'), getenv('DB_NAME'));
    if ($db->connect_error) {
        return json_encode(array('status' => 'error', 'message' => 'Database connection error'));
    }
    $sql = "SELECT * FROM rent WHERE booked_by_id = '$user_id'";
    $result = $db->query($sql);
    // if ($result->num_rows == 0) {
    //     return json_encode(array('status' => 'error', 'message' => 'No cars booked'));
    // }
    $cars = array();
    while ($row = $result->fetch_assoc()) {
        // $car = getCar($row['car_id']);
        array_push($cars, $row);
    }
    return json_encode(array('status' => 'success', 'message' => 'Cars booked', 'cars' => $cars));
}
function getCurrentUser() {
    $user_id = $_SESSION['username'];
    if ($user_id) {
        return json_encode(getUser($user_id));
    } else {
        return json_encode(null);
    }
}
function getItems() {
    $items = array(1,2,3,4);
    $response = json_encode($items);

    echo $response;
}

function postItem() {
    $jsonData = trim(file_get_contents('php://input'), "\xEF\xBB\xBF");
    $data = json_decode($jsonData, true);

    $response = json_encode($data);
    echo $response;
}



$request = $_SERVER['REQUEST_URI'];
switch ($request) {
    case '/get' :
        // require __DIR__ . '/getItem.php';
        echo getItems();
        break;
    case '/post' :
        // require __DIR__ . '/postItem.php';
        echo postItem();
        break;
    case '/register' :
        // require __DIR__ . '/postItem.php';
        echo register();
        break;
    case '/login' :
        // require __DIR__ . '/postItem.php';
        echo login();
        break;
    case '/logout' :
        // require __DIR__ . '/postItem.php';
        echo logout();
        break;

    case '/rent' :
        // require __DIR__ . '/postItem.php';
        echo bookCarForRent();
        break;
    case '/add-car' :
        // require __DIR__ . '/postItem.php';
        echo addCarForRent();
        break;
    case '/get-booked-cars' :
        // require __DIR__ . '/postItem.php';
        echo getBookedCars();
        break;
    case '/get-all-agency-cars' :
        echo getAllAgencyCars();
        break;
    case '/get-all-cars' :
        echo getAllCars();
        break;
    case '/update-car' :
        echo updateCarForRent();
        break;
    case '/get-car-by-id' :
        $jsonData = trim(file_get_contents('php://input'), "\xEF\xBB\xBF");
        $data = json_decode($jsonData, true);
        $car_id = $data['car_id'];
        $car = getCar($car_id);
        // var_dump($car_id);
        // var_dump($car);
        echo json_encode( $car );
        break;
    case '/get-current-user' :
        echo getCurrentUser();
        break;

    case '/test' :
        // require __DIR__ . '/postItem.php';
        echo json_encode($_SESSION['username']);
        break;
    case '' :
        require __DIR__ . '/home.html';
        break;
    default:
        http_response_code(404);
        require __DIR__ . '/404.html';
        break;
}


?>