<?php
session_start(); // Start the session at the beginning
require_once 'db_connect.php';
require_once 'auth_function.php';

// Check admin login
checkAdminLogin();

// Verify user is logged in
if (!isset($_SESSION['userID'])) {
    header('Location: login.php');
    exit();
}

$user_ID = $_SESSION['userID'];

try {
    // Prepared statement to fetch user details
    $sql = "SELECT username FROM users WHERE user_ID = ?";
    $stmt = $connect->prepare($sql);
    
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $connect->error);
    }
    
    $stmt->bind_param("i", $user_ID);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if (!$result) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    
    $user = $result->fetch_assoc();
    
    if (!$user) {
        throw new Exception("No user found");
    }
    
    $stmt->close();
} catch (Exception $e) {
    // Log error and redirect
    error_log("User fetch error: " . $e->getMessage());
    header('Location: login.php');
    exit();
}

// Include header after error handling
include('header.php');
?>

<div class="row">
    <div class="col-md-12">
        <ol class="breadcrumb">
            <li><a href="dashboard.php">Dashboard</a></li>		  
            <li class="active">Settings</li>
        </ol>

        <div class="panel panel-default">
            <div class="panel-heading">
                <div class="page-heading"> <i class="glyphicon glyphicon-wrench"></i> Settings</div>
            </div>

            <div class="panel-body">
                <!-- Username Change Form -->
                <form action="php_action/changeUsername.php" method="post" class="form-horizontal" id="changeUsernameForm">
                    <fieldset>
                        <legend>Change Username</legend>

                        <div class="changeUsernameMessages"></div>			

                        <div class="form-group">
                            <label for="username" class="col-sm-2 control-label">Username</label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control" id="username" name="username" 
                                       placeholder="Username" 
                                       value="<?php echo htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8'); ?>" 
                                       required 
                                       minlength="3" 
                                       maxlength="50" />
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="col-sm-offset-2 col-sm-10">
                                <input type="hidden" name="user_ID" id="user_ID" value="<?php echo htmlspecialchars($user_ID, ENT_QUOTES, 'UTF-8'); ?>" /> 
                                <button type="submit" class="btn btn-success" data-loading-text="Loading..." id="changeUsernameBtn"> 
                                    <i class="glyphicon glyphicon-ok-sign"></i> Save Changes 
                                </button>
                            </div>
                        </div>
                    </fieldset>
                </form>

                <!-- Password Change Form -->
                <form action="php_action/changePassword.php" method="post" class="form-horizontal" id="changePasswordForm">
                    <fieldset>
                        <legend>Change Password</legend>

                        <div class="changePasswordMessages"></div>

                        <div class="form-group">
                            <label for="password" class="col-sm-2 control-label">Current Password</label>
                            <div class="col-sm-10">
                                <input type="password" class="form-control" id="password" name="password" 
                                       placeholder="Current Password" 
                                       required 
                                       minlength="8" />
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="npassword" class="col-sm-2 control-label">New Password</label>
                            <div class="col-sm-10">
                                <input type="password" class="form-control" id="npassword" name="npassword" 
                                       placeholder="New Password" 
                                       required 
                                       minlength="8" />
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="cpassword" class="col-sm-2 control-label">Confirm Password</label>
                            <div class="col-sm-10">
                                <input type="password" class="form-control" id="cpassword" name="cpassword" 
                                       placeholder="Confirm Password" 
                                       required 
                                       minlength="8" />
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="col-sm-offset-2 col-sm-10">
                                <input type="hidden" name="user_ID" id="user_ID" value="<?php echo htmlspecialchars($user_ID, ENT_QUOTES, 'UTF-8'); ?>" /> 
                                <button type="submit" class="btn btn-primary"> 
                                    <i class="glyphicon glyphicon-ok-sign"></i> Save Changes 
                                </button>
                            </div>
                        </div>
                    </fieldset>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
// Close database connection at the end
if (isset($connect) && $connect instanceof mysqli) {
    $connect->close();
}
?>