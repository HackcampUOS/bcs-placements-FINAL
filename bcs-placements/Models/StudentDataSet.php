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
                            university = :university,
                            course = :course,
                            updated_at = CURRENT_TIMESTAMP 
                        WHERE user_id = :user_id";

            $statement = $this->_dbHandle->prepare($sqlQuery);
            $statement->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $statement->bindParam(':phone', $data['phone'], PDO::PARAM_STR);
            $statement->bindParam(':address', $data['address'], PDO::PARAM_STR);
            $statement->bindParam(':university', $data['university'], PDO::PARAM_STR);
            $statement->bindParam(':course', $data['course'], PDO::PARAM_STR);

            return $statement->execute();
        } catch (PDOException $e) {
            echo "Error updating profile: " . $e->getMessage();
            return false;
        }
    }

    /**
     * Update student name
     */
    public function updateName($userId, $firstName, $lastName)
    {
        try {
            $sqlQuery = "UPDATE students 
                        SET first_name = :first_name, 
                            last_name = :last_name,
                            updated_at = CURRENT_TIMESTAMP 
                        WHERE user_id = :user_id";

            $statement = $this->_dbHandle->prepare($sqlQuery);
            $statement->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $statement->bindParam(':first_name', $firstName, PDO::PARAM_STR);
            $statement->bindParam(':last_name', $lastName, PDO::PARAM_STR);

            return $statement->execute();
        } catch (PDOException $e) {
            echo "Error updating name: " . $e->getMessage();
            return false;
        }
    }

    /**
     * Update email (also updates users table)
     */
    public function updateEmail($userId, $newEmail, $password)
    {
        try {
            // First verify password
            $sqlQuery = "SELECT password FROM users WHERE user_id = :user_id";
            $statement = $this->_dbHandle->prepare($sqlQuery);
            $statement->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $statement->execute();
            $row = $statement->fetch(PDO::FETCH_ASSOC);

            if (!$row || !password_verify($password, $row['password'])) {
                return false; // Invalid password
            }

            // Update email in users table
            $sqlQuery = "UPDATE users SET email = :email WHERE user_id = :user_id";
            $statement = $this->_dbHandle->prepare($sqlQuery);
            $statement->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $statement->bindParam(':email', $newEmail, PDO::PARAM_STR);

            return $statement->execute();
        } catch (PDOException $e) {
            echo "Error updating email: " . $e->getMessage();
            return false;
        }
    }

    /**
     * Update password
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
     * Upload CV
     */
    public function uploadCV($userId, $filename, $filepath)
    {
        try {
            $sqlQuery = "UPDATE students 
                        SET cv_filename = :filename,
                            cv_filepath = :filepath,
                            cv_uploaded_at = CURRENT_TIMESTAMP,
                            updated_at = CURRENT_TIMESTAMP
                        WHERE user_id = :user_id";

            $statement = $this->_dbHandle->prepare($sqlQuery);
            $statement->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $statement->bindParam(':filename', $filename, PDO::PARAM_STR);
            $statement->bindParam(':filepath', $filepath, PDO::PARAM_STR);

            return $statement->execute();
        } catch (PDOException $e) {
            echo "Error uploading CV: " . $e->getMessage();
            return false;
        }
    }

    /**
     * Delete CV
     */
    public function deleteCV($userId)
    {
        try {
            // Get current CV filepath to delete file
            $student = $this->getStudentByUserId($userId);
            if ($student && $student->hasCv()) {
                $filepath = $student->getCvFilepath();
                if (file_exists($filepath)) {
                    unlink($filepath);
                }
            }

            // Clear CV data from database
            $sqlQuery = "UPDATE students 
                        SET cv_filename = NULL,
                            cv_filepath = NULL,
                            cv_uploaded_at = NULL,
                            updated_at = CURRENT_TIMESTAMP
                        WHERE user_id = :user_id";

            $statement = $this->_dbHandle->prepare($sqlQuery);
            $statement->bindParam(':user_id', $userId, PDO::PARAM_INT);

            return $statement->execute();
        } catch (PDOException $e) {
            echo "Error deleting CV: " . $e->getMessage();
            return false;
        }
    }

    /**
     * Get student skills
     */
    public function getStudentSkills($studentId)
    {
        try {
            $sqlQuery = "SELECT ss.id, s.skill_id, s.skill_code, s.skill_name, s.category, ss.proficiency_level
                        FROM student_skills ss
                        INNER JOIN sfia_skills s ON ss.skill_id = s.skill_id
                        WHERE ss.student_id = :student_id
                        ORDER BY ss.proficiency_level DESC, s.skill_name ASC";

            $statement = $this->_dbHandle->prepare($sqlQuery);
            $statement->bindParam(':student_id', $studentId, PDO::PARAM_INT);
            $statement->execute();

            return $statement->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            echo "Error fetching skills: " . $e->getMessage();
            return [];
        }
    }

    /**
     * Add skill to student
     */
    public function addSkill($studentId, $skillId, $proficiencyLevel)
    {
        try {
            $sqlQuery = "INSERT INTO student_skills (student_id, skill_id, proficiency_level) 
                        VALUES (:student_id, :skill_id, :proficiency_level)";

            $statement = $this->_dbHandle->prepare($sqlQuery);
            $statement->bindParam(':student_id', $studentId, PDO::PARAM_INT);
            $statement->bindParam(':skill_id', $skillId, PDO::PARAM_INT);
            $statement->bindParam(':proficiency_level', $proficiencyLevel, PDO::PARAM_INT);

            return $statement->execute();
        } catch (PDOException $e) {
            // Ignore duplicate errors
            if (strpos($e->getMessage(), 'UNIQUE constraint failed') === false) {
                echo "Error adding skill: " . $e->getMessage();
            }
            return false;
        }
    }

    /**
     * Remove skill from student
     */
    public function removeSkill($studentId, $skillId)
    {
        try {
            $sqlQuery = "DELETE FROM student_skills 
                        WHERE student_id = :student_id AND skill_id = :skill_id";

            $statement = $this->_dbHandle->prepare($sqlQuery);
            $statement->bindParam(':student_id', $studentId, PDO::PARAM_INT);
            $statement->bindParam(':skill_id', $skillId, PDO::PARAM_INT);

            return $statement->execute();
        } catch (PDOException $e) {
            echo "Error removing skill: " . $e->getMessage();
            return false;
        }
    }

    /**
     * Get all SFIA skills for dropdown
     */
    public function getAllSkills()
    {
        try {
            $sqlQuery = "SELECT skill_id, skill_code, skill_name, category 
                        FROM sfia_skills 
                        ORDER BY skill_name ASC";

            $statement = $this->_dbHandle->prepare($sqlQuery);
            $statement->execute();

            return $statement->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            echo "Error fetching all skills: " . $e->getMessage();
            return [];
        }
    }

    /**
     * Delete student account (and all related data) after password check
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

            // Delete user (cascades to students, skills, shortlist, matches, etc.)
            $sql = "DELETE FROM users WHERE user_id = :user_id";
            $statement = $this->_dbHandle->prepare($sql);
            $statement->bindParam(':user_id', $userId, PDO::PARAM_INT);

            return $statement->execute();
        } catch (PDOException $e) {
            echo "Error deleting student account: " . $e->getMessage();
            return false;
        }
    }
}