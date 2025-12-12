<?php
/**
 * StudentData Class
 * Represents a single student record
 */

class StudentData
{
    protected $_studentId;
    protected $_userId;
    protected $_firstName;
    protected $_lastName;
    protected $_email;
    protected $_phone;
    protected $_address;
    protected $_cvFilename;
    protected $_cvFilepath;
    protected $_cvUploadedAt;
    protected $_profileCompleted;
    protected $_university;
    protected $_course;
    protected $_createdAt;
    protected $_updatedAt;

    /**
     * Constructor - initialize from database row
     */
    public function __construct($dbRow)
    {
        $this->_studentId = $dbRow['student_id'] ?? 0;
        $this->_userId = $dbRow['user_id'] ?? 0;
        $this->_firstName = $dbRow['first_name'] ?? '';
        $this->_lastName = $dbRow['last_name'] ?? '';
        $this->_email = $dbRow['email'] ?? '';
        $this->_phone = $dbRow['phone'] ?? '';
        $this->_address = $dbRow['address'] ?? '';
        $this->_cvFilename = $dbRow['cv_filename'] ?? '';
        $this->_cvFilepath = $dbRow['cv_filepath'] ?? '';
        $this->_cvUploadedAt = $dbRow['cv_uploaded_at'] ?? null;
        $this->_profileCompleted = $dbRow['profile_completed'] ?? 0;
        $this->_university = $dbRow['university'] ?? '';
        $this->_course = $dbRow['course'] ?? '';
        $this->_createdAt = $dbRow['created_at'] ?? null;
        $this->_updatedAt = $dbRow['updated_at'] ?? null;
    }

    // Getter methods
    public function getStudentID() { return $this->_studentId; }
    public function getID() { return $this->_userId; } // Alias for compatibility
    public function getUserID() { return $this->_userId; }
    public function getFirstName() { return $this->_firstName; }
    public function getLastName() { return $this->_lastName; }
    public function getFullName() { return $this->_firstName . ' ' . $this->_lastName; }
    public function getEmail() { return $this->_email; }
    public function getPhone() { return $this->_phone; }
    public function getAddress() { return $this->_address; }
    public function getCvFilename() { return $this->_cvFilename; }
    public function getCvFilepath() { return $this->_cvFilepath; }
    public function getCvUploadedAt() { return $this->_cvUploadedAt; }
    public function isProfileCompleted() { return $this->_profileCompleted == 1; }
    public function getUniversity() { return $this->_university; }
    public function getCourse() { return $this->_course; }
    public function getCreatedAt() { return $this->_createdAt; }
    public function getUpdatedAt() { return $this->_updatedAt; }

    // Helper methods
    public function hasCv() { return !empty($this->_cvFilepath); }

    /**
     * Get CV file size in human readable format
     */
    public function getCvFileSize()
    {
        if (!$this->hasCv() || !file_exists($this->_cvFilepath)) {
            return null;
        }
        $bytes = filesize($this->_cvFilepath);
        $units = ['B', 'KB', 'MB', 'GB'];
        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Calculate profile completion percentage
     */
    public function getProfileCompletionPercentage()
    {
        $total = 6; // Total criteria
        $completed = 0;

        if (!empty($this->_firstName) && !empty($this->_lastName)) $completed++;
        if (!empty($this->_phone)) $completed++;
        if (!empty($this->_address)) $completed++;
        if (!empty($this->_university)) $completed++;
        if (!empty($this->_course)) $completed++;
        if ($this->hasCv()) $completed++;

        return round(($completed / $total) * 100);
    }
}