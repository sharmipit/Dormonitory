<?php
$page_titles = [
    'home.php'           => 'Home',
    'digital-key.php'    => 'Digital Key',
    'invite-visitor.php' => 'Invite Visitor',
    'dashboard.php' => 'Dashboard',
    'residency-directory.php' => 'Residency Directory',
    'room-allocation.php' => 'Room Allocation',
    'announcements.php' => 'Announcements',
];

$current_page = basename($_SERVER['PHP_SELF']);
$page_title   = $page_titles[$current_page] ?? 'Page';

$name = "Juan Dela Cruz"; // TODO: replace with session/DB
?>

//please disregard the this for now...