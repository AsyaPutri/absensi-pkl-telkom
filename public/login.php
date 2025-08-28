<?php
session_start();
include "../config/database.php";

if(isset($_POST['login'])){
    $email = $_POST['email'];
    $password = $_POST['password'];

    $result = $conn->query("SELECT * FROM users WHERE email='$email' AND password='$password'");
    if($result->num_rows > 0){
        $row = $result->fetch_assoc();
        $_SESSION['user_id'] = $row['id'];
        $_SESSION['nama'] = $row['nama'];
        $_SESSION['role'] = $row['role'];

        if($row['role'] == 'admin'){
            header("Location: admin/dashboard.php");
        } else {
            header("Location: magang/dashboard.php");
        }
        exit;
    } else {
        $error = "Email atau password salah!";
    }
}
?>

<form method="POST">
  <input type="text" name="email" placeholder="Email" required><br>
  <input type="password" name="password" placeholder="Password" required><br>
  <button type="submit" name="login">Login</button>
</form>
<?php if(isset($error)) echo $error; ?>