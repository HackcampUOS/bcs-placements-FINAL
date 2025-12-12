<?php
/**
 * EmployerData Class
 * Represents a single employer record
 */

class EmployerData
{
    protected $_employerId;
    protected $_userId;
    protected $_companyName;
    protected $_contactName;
    protected $_email;
    protected $_phone;
    protected $_address;
    protected $_companyDescription;
    protected $_website;
    protected $_profileCompleted;
    protected $_createdAt;
    protected $_updatedAt;
    protected $_companyLogo;

    /**
     * Constructor - initialize from database row
     */
    public function __construct($dbRow)
    {
        $this->_employerId = $dbRow['employer_id'] ?? 0;
        $this->_userId = $dbRow['user_id'] ?? 0;
        $this->_companyName = $dbRow['company_name'] ?? '';
        $this->_contactName = $dbRow['contact_name'] ?? '';
        $this->_email = $dbRow['email'] ?? '';
        $this->_phone = $dbRow['phone'] ?? '';
        $this->_address = $dbRow['address'] ?? '';
        $this->_companyDescription = $dbRow['company_description'] ?? '';
        $this->_website = $dbRow['website'] ?? '';
        $this->_profileCompleted = $dbRow['profile_completed'] ?? 0;
        $this->_createdAt = $dbRow['created_at'] ?? null;
        $this->_updatedAt = $dbRow['updated_at'] ?? null;
        $this->_companyLogo = $dbRow['company_logo'] ?? '';
    }

    // Getter methods
    public function getEmployerID() { return $this->_employerId; }
    public function getID() { return $this->_userId; } // Alias for compatibility
    public function getUserID() { return $this->_userId; }
    public function getCompanyName() { return $this->_companyName; }
    public function getContactName() { return $this->_contactName; }
    public function getEmail() { return $this->_email; }
    public function getPhone() { return $this->_phone; }
    public function getAddress() { return $this->_address; }
    public function getCompanyDescription() { return $this->_companyDescription; }
    public function getWebsite() { return $this->_website; }
    public function isProfileCompleted() { return $this->_profileCompleted == 1; }
    public function getCreatedAt() { return $this->_createdAt; }
    public function getUpdatedAt() { return $this->_updatedAt; }
    public function getCompanyLogo() { return $this->_companyLogo; }
}