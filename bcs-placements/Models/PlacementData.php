<?php
/**
 * PlacementData Class
 * Represents a single placement opportunity
 */

class PlacementData
{
    protected $_placementId;
    protected $_employerId;
    protected $_title;
    protected $_description;
    protected $_location;
    protected $_salaryMin;
    protected $_salaryMax;
    protected $_startDate;
    protected $_endDate;
    protected $_deadline;
    protected $_durationMonths;
    protected $_companyLogo;
    protected $_applicationUrl;
    protected $_status;
    protected $_createdAt;
    protected $_updatedAt;

    // Employer details (from JOIN)
    protected $_companyName;
    protected $_contactName;
    protected $_website;
    protected $_companyDescription;

    // Skills array
    protected $_skills = [];

    // Match percentage (student-specific)
    protected $_matchPercentage = null;

    /**
     * Constructor - initialize from database row
     */
    public function __construct($dbRow)
    {
        $this->_placementId = $dbRow['placement_id'] ?? 0;
        $this->_employerId = $dbRow['employer_id'] ?? 0;
        $this->_title = $dbRow['title'] ?? '';
        $this->_description = $dbRow['description'] ?? '';
        $this->_location = $dbRow['location'] ?? '';
        $this->_salaryMin = $dbRow['salary_min'] ?? 0;
        $this->_salaryMax = $dbRow['salary_max'] ?? 0;
        $this->_startDate = $dbRow['start_date'] ?? null;
        $this->_endDate = $dbRow['end_date'] ?? null;
        $this->_deadline = $dbRow['deadline'] ?? null;
        $this->_durationMonths = $dbRow['duration_months'] ?? 12;
        $this->_companyLogo = $dbRow['company_logo'] ?? '';
        $this->_applicationUrl = $dbRow['application_url'] ?? '';
        $this->_status = $dbRow['status'] ?? 'active';
        $this->_createdAt = $dbRow['created_at'] ?? null;
        $this->_updatedAt = $dbRow['updated_at'] ?? null;

        // Employer details (if JOINed)
        $this->_companyName = $dbRow['company_name'] ?? '';
        $this->_contactName = $dbRow['contact_name'] ?? '';
        $this->_website = $dbRow['website'] ?? '';
        $this->_companyDescription = $dbRow['company_description'] ?? '';
    }

    // Getter methods
    public function getPlacementId() { return $this->_placementId; }
    public function getEmployerId() { return $this->_employerId; }
    public function getTitle() { return $this->_title; }
    public function getDescription() { return $this->_description; }
    public function getLocation() { return $this->_location; }
    public function getSalaryMin() { return $this->_salaryMin; }
    public function getSalaryMax() { return $this->_salaryMax; }
    public function getStartDate() { return $this->_startDate; }
    public function getEndDate() { return $this->_endDate; }
    public function getDeadline() { return $this->_deadline; }
    public function getDurationMonths() { return $this->_durationMonths; }
    public function getCompanyLogo() { return $this->_companyLogo; }
    public function getApplicationUrl() { return $this->_applicationUrl; }
    public function getStatus() { return $this->_status; }
    public function getCreatedAt() { return $this->_createdAt; }
    public function getUpdatedAt() { return $this->_updatedAt; }
    public function getCompanyName() { return $this->_companyName; }
    public function getContactName() { return $this->_contactName; }
    public function getWebsite() { return $this->_website; }
    public function getCompanyDescription() { return $this->_companyDescription; }

    // Skills methods
    public function setSkills($skills) { $this->_skills = $skills; }
    public function getSkills() { return $this->_skills; }
    public function hasSkills() { return !empty($this->_skills); }

    // Helper methods
    public function getSalaryRange()
    {
        $min = $this->_salaryMin;
        $max = $this->_salaryMax;

        // Treat null/empty/0 as "no value"
        $hasMin = $min !== null && $min !== '' && $min > 0;
        $hasMax = $max !== null && $max !== '' && $max > 0;

        // Case 1: both min and max set
        if ($hasMin && $hasMax) {
            // If same -> "£5000"
            if ((float)$min === (float)$max) {
                return '£' . number_format($min);
            }

            // Normal range -> "£4000 - £6000"
            return '£' . number_format($min) . ' - £' . number_format($max);
        }

        // Case 2: only min set -> "£5000+"
        if ($hasMin) {
            return '£' . number_format($min) . '+';
        }

        // Case 3: only max set -> "£5000 max"
        if ($hasMax) {
            return '£' . number_format($max) . ' max';
        }

        // Case 4: neither set -> "TBC"
        return 'TBC';
    }

    public function getFormattedStartDate()
    {
        return $this->_startDate ? date('M Y', strtotime($this->_startDate)) : 'TBC';
    }

    public function getFormattedEndDate()
    {
        return $this->_endDate ? date('M Y', strtotime($this->_endDate)) : 'TBC';
    }

    public function getFormattedDeadline()
    {
        return $this->_deadline ? date('d M Y', strtotime($this->_deadline)) : 'Rolling';
    }

    public function isDeadlineSoon()
    {
        if (!$this->_deadline) return false;
        $daysUntilDeadline = (strtotime($this->_deadline) - time()) / (60 * 60 * 24);
        return $daysUntilDeadline <= 7 && $daysUntilDeadline >= 0;
    }

    public function hasExpired()
    {
        if (!$this->_deadline) {
            return false;
        }

        // Work with date-only, ignore time of day
        $deadlineDate = new DateTime($this->_deadline);
        $today        = new DateTime('today');

        // Expired if today is strictly after deadline day
        return $today > $deadlineDate;
    }

    public function getDaysUntilDeadline()
    {
        if (!$this->_deadline) {
            return null;
        }

        $today    = new DateTime('today');
        $deadline = new DateTime($this->_deadline);

        // Signed difference in days: positive = future, 0 = today, negative = past
        $diffDays = (int)$today->diff($deadline)->format('%r%a');

        return $diffDays;
    }

    // Match percentage helpers (per-student)
    public function setMatchPercentage($percent)
    {
        // Normalise to 0–100 or null
        if ($percent === null) {
            $this->_matchPercentage = null;
        } else {
            $percent = (int) round($percent);
            if ($percent < 0) $percent = 0;
            if ($percent > 100) $percent = 100;
            $this->_matchPercentage = $percent;
        }
    }

    public function getMatchPercentage()
    {
        return $this->_matchPercentage;
    }
}