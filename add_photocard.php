<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

include 'includes/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $album_name = $_POST['album_name'];
    $member_name = $_POST['member_name'];
    $version_name = $_POST['version_name'];
    $release_date = $_POST['release_date'];
    $pob_version = !empty($_POST['pob_version']) ? $_POST['pob_version'] : NULL;
    $image = $_FILES['image']['name'];
    $status = $_POST['status'];

    $target_dir = "uploads/";
    $target_file = $target_dir . basename($image);
    move_uploaded_file($_FILES["image"]["tmp_name"], $target_file);

    // insert or find the album
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

    // insert or find the version
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

    // insert the album-version relationship
    $sql = "SELECT * FROM album_versions WHERE album_id = '$album_id' AND version_id = '$version_id'";
    $result = $conn->query($sql);
    if ($result->num_rows == 0) {
        $sql = "INSERT INTO album_versions (album_id, version_id) VALUES ('$album_id', '$version_id')";
        if ($conn->query($sql) !== TRUE) {
            echo "Error inserting album-version relationship: " . $conn->error;
            exit();
        }
    }

    // insert the POB if provided
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
        $pob_id = 'NULL';
    }

    // retrieve member_id from members table
    $sql = "SELECT member_id FROM members WHERE name = '$member_name'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        $member = $result->fetch_assoc();
        $member_id = $member['member_id'];
    } else {
        echo "Error: Member not found";
        exit();
    }

    // insert the photocard
    $sql = "INSERT INTO photocards (album_id, member_id, version_id, pob_id, image) 
            VALUES ('$album_id', '$member_id', '$version_id', $pob_id, '$image')";
    if ($conn->query($sql) === TRUE) {
        $photocard_id = $conn->insert_id;

        // insert into user_photocards
        $user_id = $_SESSION['user_id'];
        $sql = "INSERT INTO user_photocards (user_id, photocard_id, status) VALUES ('$user_id', '$photocard_id', '$status')";
        if ($conn->query($sql) === TRUE) {
            echo "Photocard added successfully";
        } else {
            echo "Error inserting into user_photocards: " . $conn->error;
        }
    } else {
        echo "Error inserting photocard: " . $conn->error;
    }
}
?>

<?php include 'includes/header.php'; ?>
<h2>Add Photocard</h2>
<form method="post" enctype="multipart/form-data">
    <label for="album_name">Album Name:</label>
    <input type="text" id="album_name" name="album_name" required> <br>

    <label for="member_name">Member:</label>
    <div class="select-dropdown">
        <select id="member_name" name="member_name" required>
            <option value="Minji">Minji</option>
            <option value="Hanni">Hanni</option>
            <option value="Danielle">Danielle</option>
            <option value="Haerin">Haerin</option>
            <option value="Hyein">Hyein</option>
        </select> 
    </div><br>

    <label for="version_name">Version Name:</label>
    <input type="text" id="version_name" name="version_name"> <br>

    <label for="release_date">Release Date:</label>
    <input type="date" id="release_date" name="release_date" required> <br>

    <label for="pob_version">POB Version (if POB):</label>
    <input type="text" id="pob_version" name="pob_version"> <br>

    <label for="image">Image:</label>
    <input type="file" id="image" name="image" required> <br>

    <label for="status">Status:</label>
    <div class="select-dropdown">
    <select id="status" name="status" required>
        <option value="Wishlist">Wishlist</option>
        <option value="On Hand">On Hand</option>
        <option value="Not On Hand">Not On Hand</option>
    </select>
    </div> <br>

    <button type="submit">Add Photocard</button>
</form>
<?php include 'includes/footer.php'; ?>
