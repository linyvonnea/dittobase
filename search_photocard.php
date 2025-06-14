<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

include 'includes/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'GET' && !empty($_GET)) {
    $album_name = $_GET['album_name'] ?? '';
    $member_names = $_GET['member_name'] ?? [];
    $version_name = $_GET['version_name'] ?? '';
    $pob_version = $_GET['pob_version'] ?? '';
    $status = $_GET['status'] ?? '';

    $sql = "SELECT photocards.*, members.name AS member_name, albums.album_name, versions.version_name, pobs.pob_version, user_photocards.status 
            FROM photocards 
            JOIN members ON photocards.member_id = members.member_id
            JOIN albums ON photocards.album_id = albums.album_id
            JOIN versions ON photocards.version_id = versions.version_id
            LEFT JOIN pobs ON photocards.pob_id = pobs.pob_id
            JOIN user_photocards ON user_photocards.photocard_id = photocards.photocard_id
            WHERE user_photocards.user_id = '".$_SESSION['user_id']."'";

    if (!empty($album_name)) {
        $sql .= " AND albums.album_name LIKE '%$album_name%'";
    }
    if (!empty($member_names)) {
        $member_ids = implode(",", array_map(function($name) use ($conn) {
            $name = $conn->real_escape_string($name);
            $result = $conn->query("SELECT member_id FROM members WHERE name='$name'");
            return $result->fetch_assoc()['member_id'];
        }, $member_names));
        $sql .= " AND photocards.member_id IN ($member_ids)";
    }
    if (!empty($version_name)) {
        $sql .= " AND versions.version_name LIKE '%$version_name%'";
    }
    if (!empty($pob_version)) {
        $sql .= " AND pobs.pob_version LIKE '%$pob_version%'";
    }
    if (!empty($status)) {
        $sql .= " AND user_photocards.status = '$status'";
    }

    $sql .= " ORDER BY albums.release_date";
    $result = $conn->query($sql);

    if (!$result) {
        echo "Error: " . $conn->error;
        exit();
    }
}
?>

<?php include 'includes/header.php'; ?>
<h2>Search Photocard</h2>
<form method="get" class="form-container">
    <div class="form-group">
        <label for="album_name">Album Name:</label>
        <input type="text" id="album_name" name="album_name">
    </div>

    <div class="form-group">
        <label for="member_name">Member Name:</label>
        <div class="checkbox-group">
            <input type="checkbox" id="minji" name="member_name[]" value="Minji">
            <label for="minji">Minji</label>
            <input type="checkbox" id="hanni" name="member_name[]" value="Hanni">
            <label for="hanni">Hanni</label>
            <input type="checkbox" id="danielle" name="member_name[]" value="Danielle">
            <label for="danielle">Danielle</label>
            <input type="checkbox" id="haerin" name="member_name[]" value="Haerin">
            <label for="haerin">Haerin</label>
            <input type="checkbox" id="hyein" name="member_name[]" value="Hyein">
            <label for="hyein">Hyein</label>
        </div>
    </div>

    <div class="form-group">
        <label for="version_name">Version Name:</label>
        <input type="text" id="version_name" name="version_name">
    </div>

    <div class="form-group">
        <label for="pob_version">POB Version:</label>
        <input type="text" id="pob_version" name="pob_version">
    </div>

    <div class="form-group">
        <label for="status">Status:</label>
        <div class="select-dropdown">
            <select id="status" name="status">
                <option value="">Any</option>
                <option value="wishlist">Wishlist</option>
                <option value="on hand">On Hand</option>
                <option value="not on hand">Not On Hand</option>
            </select>
        </div>
    </div>

    <button type="submit">Search</button>
</form>

<?php if (isset($result)): ?>
    <h3>Results:</h3>
    <div class="gallery">
        <?php 
        $current_album = '';
        while ($row = $result->fetch_assoc()): 
            if ($current_album != $row['album_name']) {
                if ($current_album != '') {
                    echo "</div>"; // Close the previous album group
                }
                $current_album = $row['album_name'];
                echo "<h4>Album: " . htmlspecialchars($current_album) . "</h4>";
                echo "<div class='album-group'>";
            }
        ?>
        <div class="card">
            <img src="uploads/<?php echo htmlspecialchars($row['image']); ?>" alt="Photocard Image">
            <p>Member: <?php echo htmlspecialchars($row['member_name']); ?></p>
            <p>Version: <?php echo htmlspecialchars($row['version_name']); ?></p>
            <p><?php echo htmlspecialchars($row['pob_version'] != '' ? 'POB - ' . $row['pob_version'] : ''); ?></p>
            <p>Status: <?php echo htmlspecialchars($row['status']); ?></p>
            <a href="update_photocard.php?id=<?php echo $row['photocard_id']; ?>">Update</a>
            <a href="delete_photocard.php?id=<?php echo $row['photocard_id']; ?>">Delete</a>
        </div>
        <?php endwhile; ?>
        <?php if ($current_album != '') {
            echo "</div>"; // Close the last album group
        } ?>
    </div>
<?php endif; ?>
<?php include 'includes/footer.php'; ?>
