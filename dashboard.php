<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

include 'includes/db.php';

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

$sql = "SELECT photocards.*, members.name AS member_name, albums.album_name, albums.release_date, versions.version_name, user_photocards.status, pobs.pob_version 
        FROM user_photocards 
        JOIN photocards ON user_photocards.photocard_id = photocards.photocard_id
        JOIN members ON photocards.member_id = members.member_id
        JOIN albums ON photocards.album_id = albums.album_id
        JOIN versions ON photocards.version_id = versions.version_id
        LEFT JOIN pobs ON photocards.pob_id = pobs.pob_id
        WHERE user_photocards.user_id = '$user_id'
        ORDER BY albums.release_date";
$result = $conn->query($sql);
?>

<?php include 'includes/header.php'; ?>
<h2>Welcome, <?php echo htmlspecialchars($username); ?>!</h2>
<h2>All Photocards</h2>
<div class="gallery">
    <?php while ($row = $result->fetch_assoc()): ?>
    <div class="card">
        <img src="uploads/<?php echo htmlspecialchars($row['image']); ?>" alt="Photocard Image">
        <p><?php echo htmlspecialchars($row['member_name']); ?></p>
        <p><?php echo htmlspecialchars($row['album_name']); ?></p>
        <p><?php echo htmlspecialchars($row['version_name']); ?></p>
        <p><?php echo htmlspecialchars($row['pob_version'] != '' ? 'POB - ' . $row['pob_version'] : ''); ?></p>
        <p><?php echo htmlspecialchars($row['status']); ?></p>
        <a href="update_photocard.php?id=<?php echo $row['photocard_id']; ?>">Update</a>
        <a href="delete_photocard.php?id=<?php echo $row['photocard_id']; ?>">Delete</a>
    </div>
    <?php endwhile; ?>
</div>
<?php include 'includes/footer.php'; ?>
