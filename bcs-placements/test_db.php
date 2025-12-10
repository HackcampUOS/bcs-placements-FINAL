<?php
/**
 * To test the database connection
 */

require_once 'Models/Database.php';

$db = Database::getInstance();
$conn = $db->getdbConnection();

if ($conn)
{
    echo "✅ Connection successful!";
}
else
{
    echo "❌ Connection failed!";
}
