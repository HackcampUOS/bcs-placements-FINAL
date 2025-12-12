<?php
/**
 * UserAuthentication Class
 * Manages user sessions and authentication state
 */

require_once('StudentDataSet.php');
require_once('EmployerDataSet.php');

class UserAuthentication
{
    protected $_userID;
    protected $_userName;
    protected $_userEmail;
    protected $_userType; // 'student' or 'employer'
    protected $_isLoggedIn;

    /**
     * Constructor - checks for existing session
     */
    public function __construct()
    {
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Set default values
        $this->_isLoggedIn = false;
        $this->_userID = 0;
        $this->_userName = '';
        $this->_userEmail = '';
        $this->_userType = '';

        // Check if user is logged in
        if (isset($_SESSION['user_id']) && isset($_SESSION['user_type'])) {
            $this->_isLoggedIn = true;
            $this->_userID = $_SESSION['user_id'];
            $this->_userName = $_SESSION['user_name'];
            $this->_userEmail = $_SESSION['user_email'];
            $this->_userType = $_SESSION['user_type'];
        }
    }

    /**
     * Authenticate user (login)
     */
    public function login($email, $password, $userType)
    {
        if ($userType === 'student') {
            $studentDS = new StudentDataSet();
            $user = $studentDS->checkCredentials($email, $password);

            if ($user) {
                // Set session variables
                $_SESSION['user_id'] = $user->getID();
                $_SESSION['user_name'] = $user->getFirstName() . ' ' . $user->getLastName();
                $_SESSION['user_email'] = $user->getEmail();
                $_SESSION['user_type'] = 'student';

                // Update object properties
                $this->_isLoggedIn = true;
                $this->_userID = $user->getID();
                $this->_userName = $user->getFirstName() . ' ' . $user->getLastName();
                $this->_userEmail = $user->getEmail();
                $this->_userType = 'student';

                return true;
            }
        }
        elseif ($userType === 'employer') {
            $employerDS = new EmployerDataSet();
            $user = $employerDS->checkCredentials($email, $password);

            if ($user) {
                // Set session variables
                $_SESSION['user_id'] = $user->getID();
                $_SESSION['user_name'] = $user->getContactName();
                $_SESSION['user_email'] = $user->getEmail();
                $_SESSION['user_type'] = 'employer';

                // Update object properties
                $this->_isLoggedIn = true;
                $this->_userID = $user->getID();
                $this->_userName = $user->getContactName();
                $this->_userEmail = $user->getEmail();
                $this->_userType = 'employer';

                return true;
            }
        }

        return false;
    }

    /**
     * Logout user
     */
    public function logout()
    {
        // Clear session variables
        unset($_SESSION['user_id']);
        unset($_SESSION['user_name']);
        unset($_SESSION['user_email']);
        unset($_SESSION['user_type']);

        // Destroy session
        session_destroy();

        // Reset object properties
        $this->_isLoggedIn = false;
        $this->_userID = 0;
        $this->_userName = '';
        $this->_userEmail = '';
        $this->_userType = '';
    }

    // Getter methods
    public function isLoggedIn() { return $this->_isLoggedIn; }
    public function getUserID() { return $this->_userID; }
    public function getUserName() { return $this->_userName; }
    public function getUserEmail() { return $this->_userEmail; }
    public function getUserType() { return $this->_userType; }

    // Helper methods for role checking
    public function isStudent() { return $this->_userType === 'student'; }
    public function isEmployer() { return $this->_userType === 'employer'; }
}