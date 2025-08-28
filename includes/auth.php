<?php
session_start();

if(!isset($_SESSION['user_id'])){
    header("Location: ../login.php");
    exit;
}

function checkRole($role){
    if($_SESSION['role'] != $role){
        header("Location: ../login.php");
        exit;
    }
}
?>