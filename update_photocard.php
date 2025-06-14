<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

ini_set('display_errors', 1);
error_reporting(E_ALL);

include 'includes/db.php';

$photocard_id = $_GET['id'];

// Fetch photocard details
$sql = "SELECT photocards.*, albums.album_name, members.name AS member_name, versions.version_name, pobs.pob_version 
        FROM photocards 
        JOIN albums ON photocards.album_id = albums.album_id 
        JOIN members ON photocards.member_id = members.member_id 
        JOIN versions ON photocards.version_id = versions.version_id 
        LEFT JOIN pobs ON photocards.pob_id = pobs.pob_id
        WHERE photocards.photocard_id = '$photocard_id'";
$result = $conn->query($sql);
$photocard = $result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $album_name = $_POST['album_name'];
    $member_name = $_POST['member_name'];
    $version_name = $_POST['version_name'];
    $release_date = $_POST['release_date'];
    $pob_version = !empty($_POST['pob_version']) ? $_POST['pob_version'] : NULL;
    $image = !empty($_FILES['image']['name']) ? $_FILES['image']['name'] : $_POST['existing_image'];
    $status = $_POST['status'];

    // File upload
    if (!empty($_FILES['image']['name'])) {
        $target_dir = "uploads/";
        $target_file = $target_dir . basename($image);
        move_uploaded_file($_FILES["image"]["tmp_name"], $target_file);
    }

    // Update or find the album
    $sql = "SELECT album_id FROM albums WHERE album_name = '$album_name'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        $album = $result->fetch_assoc();
        $album_id = $album['album_id'];
    } else {
        $sql = "INSERT INTO albums (album_name, release_date) VALUES ('$album_name', '$release_date')";
        if ($conn->query($sql) === TRUE) {
            $album_id = $conn->insert_id;
        } else {
            echo "Error inserting album: " . $conn->error;
            exit();
        }
    }

    // Update or find the version
    $sql = "SELECT version_id FROM versions WHERE version_name = '$version_name'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        $version = $result->fetch_assoc();
        $version_id = $version['version_id'];
    } else {
        $sql = "INSERT INTO versions (version_name) VALUES ('$version_name')";
        if ($conn->query($sql) === TRUE) {
            $version_id = $conn->insert_id;
        } else {
            echo "Error inserting version: " . $conn->error;
            exit();
        }
    }

    // Check if POB
    if ($pob_version !== NULL) {
        $sql = "SELECT pob_id FROM pobs WHERE pob_version = '$pob_version' AND album_id = '$album_id'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $pob = $result->fetch_assoc();
            $pob_id = $pob['pob_id'];
        } else {
            $sql = "INSERT INTO pobs (album_id, pob_version) VALUES ('$album_id', '$pob_version')";
            if ($conn->query($sql) === TRUE) {
                $pob_id = $conn->insert_id;
            } else {
                echo "Error inserting POB: " . $conn->error;
                exit();
            }
        }
    } else {
        // If the photocard is no longer a POB, set the pob_id to NULL and then delete the POB entry if it exists
        if (!is_null($photocard['pob_id'])) {
            $sql = "UPDATE photocards SET pob_id = NULL WHERE photocard_id = '$photocard_id'";
            $conn->query($sql);

            $sql = "DELETE FROM pobs WHERE pob_id = '" . $photocard['pob_id'] . "'";
            $conn->query($sql);
        }
        $pob_id = 'NULL';
    }

    // Update photocard details
    $sql = "UPDATE photocards 
            SET album_id = '$album_id', member_id = (SELECT member_id FROM members WHERE name = '$member_name'), 
                version_id = '$version_id', pob_id = $pob_id, image = '$image' 
            WHERE photocard_id = '$photocard_id'";
    if ($conn->query($sql) === TRUE) {
        // Update the status in the user_photocards table
        $sql = "UPDATE user_photocards SET status = '$status' WHERE photocard_id = '$photocard_id' AND user_id = '".$_SESSION['user_id']."'";
        if ($conn->query($sql) === TRUE) {
            header('Location: dashboard.php');
            exit();
        } else {
            echo "Error updating user_photocard: " . $conn->error;
        }
    } else {
        echo "Error updating photocard: " . $conn->error;
    }
}
?>

<?php include 'includes/header.php'; ?>
<h2>Update Photocard</h2>
<form action="" method="post" enctype="multipart/form-data">
    <label for="album_name">Album Name:</label>
    <input type="text" id="album_name" name="album_name" value="<?php echo htmlspecialchars($photocard['album_name']); ?>" required> <br>

    <label for="member_name">Member Name:</label>
    <select id="member_name" name="member_name" required>
        <option value="Minji" <?php echo $photocard['member_name'] == 'Minji' ? 'selected' : ''; ?>>Minji</option>
        <option value="Hanni" <?php echo $photocard['member_name'] == 'Hanni' ? 'selected' : ''; ?>>Hanni</option>
        <option value="Danielle" <?php echo $photocard['member_name'] == 'Danielle' ? 'selected' : ''; ?>>Danielle</option>
        <option value="Haerin" <?php echo $photocard['member_name'] == 'Haerin' ? 'selected' : ''; ?>>Haerin</option>
        <option value="Hyein" <?php echo $photocard['member_name'] == 'Hyein' ? 'selected' : ''; ?>>Hyein</option>
    </select> <br>

    <label for="version_name">Version Name:</label>
    <input type="text" id="version_name" name="version_name" value="<?php echo htmlspecialchars($photocard['version_name']); ?>"> <br>

    <label for="release_date">Release Date:</label>
    <input type="date" id="release_date" name="release_date" value="<?php echo htmlspecialchars($photocard['release_date']); ?>" required> <br>

    <label for="pob_version">POB Version (if POB):</label>
    <input type="text" id="pob_version" name="pob_version" value="<?php echo htmlspecialchars($photocard['pob_version']); ?>"> <br>

    <label for="image">Image:</label>
    <input type="file" id="image" name="image">
    <input type="hidden" name="existing_image" value="<?php echo htmlspecialchars($photocard['image']); ?>"> <br>

    <label for="status">Status:</label>
    <select id="status" name="status" required>
        <option value="wishlist" <?php echo $photocard['status'] == 'wishlist' ? 'selected' : ''; ?>>Wishlist</option>
        <option value="on hand" <?php echo $photocard['status'] == 'on hand' ? 'selected' : ''; ?>>On Hand</option>
        <option value="not on hand" <?php echo $photocard['status'] == 'not on hand' ? 'selected' : ''; ?>>Not On Hand</option>
    </select><br>

    <button type="submit">Update Photocard</button>
</form>
<?php include 'includes/footer.php'; ?>