<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

include 'includes/db.php';

$photocard_id = $_GET['id'];

// Fetch album_id, pob_id, and image path
$sql = "SELECT album_id, pob_id, image FROM photocards WHERE photocard_id='$photocard_id'";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    $photocard = $result->fetch_assoc();
    $album_id = $photocard['album_id'];
    $pob_id = $photocard['pob_id'];
    $image_path = 'uploads/' . $photocard['image'];

    // Delete the image from its directed file
    if (file_exists($image_path)) {
        unlink($image_path);
    }

    // Delete from the user_photocards table
    $sql = "DELETE FROM user_photocards WHERE photocard_id='$photocard_id' AND user_id='".$_SESSION['user_id']."'";
    if ($conn->query($sql) === TRUE) {
        // Delete the photocard entry
        $sql = "DELETE FROM photocards WHERE photocard_id='$photocard_id'";
        if ($conn->query($sql) === TRUE) {
            // If the photocard is a POB, delete the POB entry if it exists
            if (!is_null($pob_id)) {
                $sql = "DELETE FROM pobs WHERE pob_id='$pob_id'";
                $conn->query($sql);
            }

            // Delete the album version entry if it exists
            $sql = "DELETE FROM album_versions WHERE album_id='$album_id'";
            if ($conn->query($sql) !== TRUE) {
                echo "Error deleting album version: " . $conn->error;
            }
        } else {
            echo "Error deleting photocard: " . $conn->error;
        }
    } else {
        echo "Error deleting user_photocard: " . $conn->error;
    }
} else {
    echo "Photocard not found";
}

header('Location: dashboard.php');
?>
