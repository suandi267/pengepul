<?php
// Initialize the session
session_start();

// Unset all of the session variables
$_SESSION = array();

// Destroy the session.
if (session_destroy()) {
    // Redirect to login page
    header("location: login.php");
    exit;
} else {
    // If destroying session fails, print an error
    echo "Error: Could not log out. Please try again.";
}
?>
