<?php

session_start();

function checkAdminLogin() {
    if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Admin') {
        header('Location: index.php');
        exit;
    } 
}

function redirectIfLoggedIn() {
    if(isset($_SESSION['user_logged_in'])){
        header('Location: dashboard.php');
    }
}

function checkAdminOrUserLogin(){
    if (!isset($_SESSION['user_logged_in'])) {
        header('Location: index.php');
        exit;
    }
}

function getConfigData($pdo){
    $stmt = $pdo->query('SELECT * FROM pos_configuration');
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC)[0];
    return $data;
}

function getCategoryName($pdo, $category_id){
    $stmt = $pdo->query('SELECT category_name FROM pos_category WHERE category_id = "'.$category_id.'"');
    $stmt->execute();
    return $stmt->fetchColumn();
}


?>