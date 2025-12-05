<?php
/**
 * StudentDataSet Class
 * Handles database operations for students
 */

require_once('Database.php');
require_once('StudentData.php');

class StudentDataSet
{
    protected $_dbHandle;
    protected $_dbInstance;

    /**
     * Constructor - establishes database connection
     */
    public function __construct()
    {
        $this->_dbInstance = Database::getInstance();
        $this->_dbHandle = $this->_dbInstance->getdbConnection();
    }

    /**
     * Check user credentials for login
     * @param string $email
     * @param string $password
     * @return StudentData|false
     */
    public function checkCredentials($email, $password)
    {
        try {
            // Get user from users table
            $sqlQuery = "SELECT u.user_id, u.email, u.password, s.* 
                        FROM users u 
                        INNER JOIN students s ON u.user_id = s.user_id 
                        WHERE u.email = :email AND u.role = 'student'";

            $statement = $this->_dbHandle->prepare($sqlQuery);
            $statement->bindParam(':email', $email, PDO::PARAM_STR);
            $statement->execute();

            $row = $statement->fetch(PDO::FETCH_ASSOC);

            if ($row && password_verify($password, $row['password'])) {
                return new StudentData($row);
            }

            return false;
        } catch (PDOException $e) {
            echo "Error checking credentials: " . $e->getMessage();
            return false;
        }
    }

    /**
     * Register a new student
     * @param string $email
     * @param string $password
     * @param string $firstName
     * @param string $lastName
     * @return bool
     */
    public function registerStudent($email, $password, $firstName, $lastName)
    {
        try {
            $this->_dbHandle->beginTransaction();

            // Hash password
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            // Insert into users table
            $sqlQuery = "INSERT INTO users (email, password, role) 
                        VALUES (:email, :password, 'student')";
            $statement = $this->_dbHandle->prepare($sqlQuery);
            $statement->bindParam(':email', $email, PDO::PARAM_STR);
            $statement->bindParam(':password', $passwordHash, PDO::PARAM_STR);
            $statement->execute();

            // Get the new user ID
            $userId = $this->_dbHandle->lastInsertId();

            // Insert into students table
            $sqlQuery = "INSERT INTO students (user_id, first_name, last_name) 
                        VALUES (:user_id, :first_name, :last_name)";
            $statement = $this->_dbHandle->prepare($sqlQuery);
            $statement->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $statement->bindParam(':first_name', $firstName, PDO::PARAM_STR);
            $statement->bindParam(':last_name', $lastName, PDO::PARAM_STR);
            $statement->execute();

            $this->_dbHandle->commit();
            return true;

        } catch (PDOException $e) {
            $this->_dbHandle->rollBack();
            echo "Error registering student: " . $e->getMessage();
            return false;
        }
    }

    /**
     * Check if email already exists
     * @param string $email
     * @return bool
     */
    public function emailExists($email)
    {
        try {
            $sqlQuery = "SELECT COUNT(*) FROM users WHERE email = :email";
            $statement = $this->_dbHandle->prepare($sqlQuery);
            $statement->bindParam(':email', $email, PDO::PARAM_STR);
            $statement->execute();

            return $statement->fetchColumn() > 0;
        } catch (PDOException $e) {
            echo "Error checking email: " . $e->getMessage();
            return false;
        }
    }

    /**
     * Get student by user ID
     * @param int $userId
     * @return StudentData|false
     */
    public function getStudentByUserId($userId)
    {
        try {
            $sqlQuery = "SELECT u.email, s.* 
                        FROM students s 
                        INNER JOIN users u ON s.user_id = u.user_id 
                        WHERE s.user_id = :user_id";

            $statement = $this->_dbHandle->prepare($sqlQuery);
            $statement->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $statement->execute();

            $row = $statement->fetch(PDO::FETCH_ASSOC);

            if ($row) {
                return new StudentData($row);
            }

            return false;
        } catch (PDOException $e) {
            echo "Error fetching student: " . $e->getMessage();
            return false;
        }
    }

    /**
     * Update student profile
     * @param int $userId
     * @param array $data
     * @return bool
     */
    public function updateProfile($userId, $data)
    {
        try {
            $sqlQuery = "UPDATE students 
                        SET phone = :phone, 
                            address = :address, 
                            updated_at = CURRENT_TIMESTAMP 
                        WHERE user_id = :user_id";

            $statement = $this->_dbHandle->prepare($sqlQuery);
            $statement->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $statement->bindParam(':phone', $data['phone'], PDO::PARAM_STR);
            $statement->bindParam(':address', $data['address'], PDO::PARAM_STR);

            return $statement->execute();
        } catch (PDOException $e) {
            echo "Error updating profile: " . $e->getMessage();
            return false;
        }
    }
}