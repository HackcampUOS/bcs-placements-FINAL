<?php
/**
 * EmployerDataSet Class
 * Handles database operations for employers
 */

require_once('Database.php');
require_once('EmployerData.php');

class EmployerDataSet
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
     * @return EmployerData|false
     */
    public function checkCredentials($email, $password)
    {
        try {
            // Get user from users table
            $sqlQuery = "SELECT u.user_id, u.email, u.password, e.* 
                        FROM users u 
                        INNER JOIN employers e ON u.user_id = e.user_id 
                        WHERE u.email = :email AND u.role = 'employer'";

            $statement = $this->_dbHandle->prepare($sqlQuery);
            $statement->bindParam(':email', $email, PDO::PARAM_STR);
            $statement->execute();

            $row = $statement->fetch(PDO::FETCH_ASSOC);

            if ($row && password_verify($password, $row['password'])) {
                return new EmployerData($row);
            }

            return false;
        } catch (PDOException $e) {
            echo "Error checking credentials: " . $e->getMessage();
            return false;
        }
    }

    /**
     * Register a new employer
     * @param string $email
     * @param string $password
     * @param string $companyName
     * @param string $contactName
     * @return bool
     */
    public function registerEmployer($email, $password, $companyName, $contactName)
    {
        try {
            $this->_dbHandle->beginTransaction();

            // Hash password
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            // Insert into users table
            $sqlQuery = "INSERT INTO users (email, password, role) 
                        VALUES (:email, :password, 'employer')";
            $statement = $this->_dbHandle->prepare($sqlQuery);
            $statement->bindParam(':email', $email, PDO::PARAM_STR);
            $statement->bindParam(':password', $passwordHash, PDO::PARAM_STR);
            $statement->execute();

            // Get the new user ID
            $userId = $this->_dbHandle->lastInsertId();

            // Insert into employers table
            $sqlQuery = "INSERT INTO employers (user_id, company_name, contact_name) 
                        VALUES (:user_id, :company_name, :contact_name)";
            $statement = $this->_dbHandle->prepare($sqlQuery);
            $statement->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $statement->bindParam(':company_name', $companyName, PDO::PARAM_STR);
            $statement->bindParam(':contact_name', $contactName, PDO::PARAM_STR);
            $statement->execute();

            $this->_dbHandle->commit();
            return true;

        } catch (PDOException $e) {
            $this->_dbHandle->rollBack();
            echo "Error registering employer: " . $e->getMessage();
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
     * Get employer by user ID
     * @param int $userId
     * @return EmployerData|false
     */
    public function getEmployerByUserId($userId)
    {
        try {
            $sqlQuery = "SELECT u.email, e.* 
                        FROM employers e 
                        INNER JOIN users u ON e.user_id = u.user_id 
                        WHERE e.user_id = :user_id";

            $statement = $this->_dbHandle->prepare($sqlQuery);
            $statement->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $statement->execute();

            $row = $statement->fetch(PDO::FETCH_ASSOC);

            if ($row) {
                return new EmployerData($row);
            }

            return false;
        } catch (PDOException $e) {
            echo "Error fetching employer: " . $e->getMessage();
            return false;
        }
    }

    /**
     * Update employer profile
     * @param int $userId
     * @param array $data
     * @return bool
     */
    public function updateProfile($userId, $data)
    {
        try {
            $sqlQuery = "UPDATE employers 
                    SET phone = :phone, 
                        address = :address, 
                        company_description = :company_description,
                        website = :website,
                        contact_name = :contact_name,
                        company_logo = :company_logo,
                        updated_at = CURRENT_TIMESTAMP 
                    WHERE user_id = :user_id";

            $statement = $this->_dbHandle->prepare($sqlQuery);
            $statement->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $statement->bindParam(':phone', $data['phone'], PDO::PARAM_STR);
            $statement->bindParam(':address', $data['address'], PDO::PARAM_STR);
            $statement->bindParam(':company_description', $data['company_description'], PDO::PARAM_STR);
            $statement->bindParam(':website', $data['website'], PDO::PARAM_STR);
            $statement->bindParam(':contact_name', $data['contact_name'], PDO::PARAM_STR);
            $statement->bindParam(':company_logo', $data['company_logo'], PDO::PARAM_STR);

            return $statement->execute();
        } catch (PDOException $e) {
            echo "Error updating profile: " . $e->getMessage();
            return false;
        }
    }

    /**
     * Update employer password
     * @param int $userId
     * @param string $currentPassword
     * @param string $newPassword
     * @return bool
     */
    public function updatePassword($userId, $currentPassword, $newPassword)
    {
        try {
            // Verify current password
            $sqlQuery = "SELECT password FROM users WHERE user_id = :user_id";
            $statement = $this->_dbHandle->prepare($sqlQuery);
            $statement->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $statement->execute();
            $row = $statement->fetch(PDO::FETCH_ASSOC);

            if (!$row || !password_verify($currentPassword, $row['password'])) {
                return false; // Invalid current password
            }

            // Update password
            $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $sqlQuery = "UPDATE users SET password = :password WHERE user_id = :user_id";
            $statement = $this->_dbHandle->prepare($sqlQuery);
            $statement->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $statement->bindParam(':password', $newPasswordHash, PDO::PARAM_STR);

            return $statement->execute();
        } catch (PDOException $e) {
            echo "Error updating password: " . $e->getMessage();
            return false;
        }
    }

    /**
     * Delete employer account (and all related data) after password check
     */
    public function deleteAccount($userId, $password)
    {
        try {
            // Verify password
            $sql = "SELECT password FROM users WHERE user_id = :user_id";
            $statement = $this->_dbHandle->prepare($sql);
            $statement->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $statement->execute();
            $row = $statement->fetch(PDO::FETCH_ASSOC);

            if (!$row || !password_verify($password, $row['password'])) {
                return false; // Incorrect password
            }

            // Delete user (cascades to employers, placements, matches, etc.)
            $sql = "DELETE FROM users WHERE user_id = :user_id";
            $statement = $this->_dbHandle->prepare($sql);
            $statement->bindParam(':user_id', $userId, PDO::PARAM_INT);

            return $statement->execute();
        } catch (PDOException $e) {
            echo "Error deleting employer account: " . $e->getMessage();
            return false;
        }
    }
}