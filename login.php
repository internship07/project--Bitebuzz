<?php
session_start();
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

include 'conf.php'; // Include your database connection file

// Database connection
$conn = new mysqli("localhost", "root", "", "bitebuzz");

if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "Database connection failed."]);
    exit;
}

// Decode JSON input from script.js
$data = json_decode(file_get_contents("php://input"), true);

// Validate JSON data
if (!$data || !isset($data['username']) || !isset($data['password'])) {
    echo json_encode(["success" => false, "message" => "Invalid request."]);
    exit;
}

// Extract login credentials
$username = mysqli_real_escape_string($conn, $data['username']);
$password = $data['password'];

$_SESSION["username"] = $username; // Set this during login

// Authenticate user using user table
$stmt = $conn->prepare("SELECT user_id, password FROM user WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    if (password_verify($password, $row['password']) || $password === $row['password']) {
        $_SESSION['username'] = $username;

        // Get user_id from query result
        $user_id = $row['user_id'];

        // Log login event in login_logs
        $log_stmt = $conn->prepare("INSERT INTO login_logs (username, user_id, login_time) VALUES (?, ?, NOW())");
        $log_stmt->bind_param("si", $username, $user_id);
        $log_stmt->execute();
        $log_stmt->close();

        $_SESSION['user_id'] = $user_id; // Store user_id in session

        // **Retrieve user's saved cart**
        $cart_stmt = $conn->prepare("SELECT menu_id, item_name, quantity, price, total_price FROM cart WHERE user_id = ?");
        $cart_stmt->bind_param("i", $user_id);
        $cart_stmt->execute();
        $cart_result = $cart_stmt->get_result();
        $cartItems = [];

        while ($cart_row = $cart_result->fetch_assoc()) {
            $cartItems[] = $cart_row;
        }

        $cart_stmt->close();

        echo json_encode([
            "success" => true, 
            "redirect" => "homepage.html",
            "cart" => $cartItems
        ]);

    } else {
        echo json_encode(["success" => false, "message" => "Incorrect password."]);
    }
} else {
    echo json_encode(["success" => false, "message" => "User not found."]);
}

$stmt->close();
$conn->close();
?>