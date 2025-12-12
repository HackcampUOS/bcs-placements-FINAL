<?php
/**
 * PlacementDataSet Class
 * Handles database operations for placements
 */

require_once('Database.php');
require_once('PlacementData.php');

class PlacementDataSet
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
     * Fetch placements with filters, search, and sorting
     */
    public function fetchPlacements($search = '', $filters = [], $sort = 'latest', $limit = 12, $offset = 0)
    {
        try {
            // Today’s date (used to hide expired placements from main listing)
            $today = date('Y-m-d');

            // Base query with employer information and deadline visibility
            $sql = "SELECT p.*, e.company_name, e.contact_name, e.company_logo
                FROM placements p
                INNER JOIN employers e ON p.employer_id = e.employer_id
                WHERE p.status = 'active'
                  AND (
                        p.deadline IS NULL
                        OR p.deadline = ''
                        OR p.deadline >= :today
                      )";

            $params = [
                ':today' => $today
            ];

            // Search filter
            if (!empty($search)) {
                $sql .= " AND (p.title LIKE :search
                      OR e.company_name LIKE :search
                      OR p.location LIKE :search
                      OR p.description LIKE :search)";
                $params[':search'] = '%' . $search . '%';
            }

            // Location filter
            if (!empty($filters['location'])) {
                $sql .= " AND p.location LIKE :location";
                $params[':location'] = '%' . $filters['location'] . '%';
            }

            // Employer filter
            if (!empty($filters['employer'])) {
                $sql .= " AND e.company_name LIKE :employer";
                $params[':employer'] = '%' . $filters['employer'] . '%';
            }

            // Salary filter
            if (!empty($filters['salary_min'])) {
                $sql .= " AND p.salary_max >= :salary_min";
                $params[':salary_min'] = $filters['salary_min'];
            }

            // Duration filter
            if (!empty($filters['duration'])) {
                $sql .= " AND p.duration_months = :duration";
                $params[':duration'] = $filters['duration'];
            }

            // Sorting
            switch ($sort) {
                case 'deadline':
                    $sql .= " ORDER BY p.deadline ASC, p.created_at DESC";
                    break;
                case 'start_date':
                    $sql .= " ORDER BY p.start_date ASC, p.created_at DESC";
                    break;
                case 'salary_high':
                    $sql .= " ORDER BY p.salary_max DESC, p.created_at DESC";
                    break;
                case 'salary_low':
                    $sql .= " ORDER BY p.salary_min ASC, p.created_at DESC";
                    break;
                case 'latest':
                default:
                    $sql .= " ORDER BY p.created_at DESC";
                    break;
            }

            // Pagination
            $sql .= " LIMIT :limit OFFSET :offset";

            $statement = $this->_dbHandle->prepare($sql);

            // Bind named params
            foreach ($params as $key => $value) {
                $statement->bindValue($key, $value);
            }

            // Bind pagination params
            $statement->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $statement->bindValue(':offset', (int)$offset, PDO::PARAM_INT);

            $statement->execute();

            $placements = [];
            while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                $placement = new PlacementData($row);

                // Fetch skills for this placement
                $skills = $this->fetchPlacementSkills($placement->getPlacementId());
                $placement->setSkills($skills);

                $placements[] = $placement;
            }

            return $placements;

        } catch (PDOException $e) {
            echo "Error fetching placements: " . $e->getMessage();
            return [];
        }
    }

    /**
     * Fetch ALL active, non-expired placements (no LIMIT/OFFSET),
     * used for global match-based sorting.
     */
    public function fetchAllPlacements($search = '', $filters = [])
    {
        try {
            $today = date('Y-m-d');

            $sql = "SELECT p.*, e.company_name, e.contact_name, e.company_logo
                FROM placements p
                INNER JOIN employers e ON p.employer_id = e.employer_id
                WHERE p.status = 'active'
                  AND (
                        p.deadline IS NULL
                        OR p.deadline = ''
                        OR p.deadline >= :today
                      )";

            $params = [
                ':today' => $today
            ];

            // Same filters as other methods
            if (!empty($search)) {
                $sql .= " AND (p.title LIKE :search
                      OR e.company_name LIKE :search
                      OR p.location LIKE :search
                      OR p.description LIKE :search)";
                $params[':search'] = '%' . $search . '%';
            }

            if (!empty($filters['location'])) {
                $sql .= " AND p.location LIKE :location";
                $params[':location'] = '%' . $filters['location'] . '%';
            }

            if (!empty($filters['employer'])) {
                $sql .= " AND e.company_name LIKE :employer";
                $params[':employer'] = '%' . $filters['employer'] . '%';
            }

            if (!empty($filters['salary_min'])) {
                $sql .= " AND p.salary_max >= :salary_min";
                $params[':salary_min'] = $filters['salary_min'];
            }

            if (!empty($filters['duration'])) {
                $sql .= " AND p.duration_months = :duration";
                $params[':duration'] = $filters['duration'];
            }

            $statement = $this->_dbHandle->prepare($sql);

            foreach ($params as $key => $value) {
                $statement->bindValue($key, $value);
            }

            $statement->execute();

            $placements = [];
            while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                $placement = new PlacementData($row);

                // Fetch skills here so match % function in placements.php works
                $skills = $this->fetchPlacementSkills($placement->getPlacementId());
                $placement->setSkills($skills);

                $placements[] = $placement;
            }

            return $placements;

        } catch (PDOException $e) {
            echo "Error fetching all placements: " . $e->getMessage();
            return [];
        }
    }

    /**
     * Count total placements (for pagination)
     */
    public function countPlacements($search = '', $filters = [])
    {
        try {
            $today = date('Y-m-d');

            $sql = "SELECT COUNT(*)
                FROM placements p
                INNER JOIN employers e ON p.employer_id = e.employer_id
                WHERE p.status = 'active'
                  AND (
                        p.deadline IS NULL
                        OR p.deadline = ''
                        OR p.deadline >= :today
                      )";

            $params = [
                ':today' => $today
            ];

            // Same filters as fetchPlacements()
            if (!empty($search)) {
                $sql .= " AND (p.title LIKE :search
                      OR e.company_name LIKE :search
                      OR p.location LIKE :search
                      OR p.description LIKE :search)";
                $params[':search'] = '%' . $search . '%';
            }

            if (!empty($filters['location'])) {
                $sql .= " AND p.location LIKE :location";
                $params[':location'] = '%' . $filters['location'] . '%';
            }

            if (!empty($filters['employer'])) {
                $sql .= " AND e.company_name LIKE :employer";
                $params[':employer'] = '%' . $filters['employer'] . '%';
            }

            if (!empty($filters['salary_min'])) {
                $sql .= " AND p.salary_max >= :salary_min";
                $params[':salary_min'] = $filters['salary_min'];
            }

            if (!empty($filters['duration'])) {
                $sql .= " AND p.duration_months = :duration";
                $params[':duration'] = $filters['duration'];
            }

            $statement = $this->_dbHandle->prepare($sql);

            foreach ($params as $key => $value) {
                $statement->bindValue($key, $value);
            }

            $statement->execute();
            return (int)$statement->fetchColumn();

        } catch (PDOException $e) {
            echo "Error counting placements: " . $e->getMessage();
            return 0;
        }
    }

    /**
     * Fetch a single placement by ID
     */
    public function fetchPlacementById($placementId)
    {
        try {
            $sql = "SELECT p.*, 
               e.company_name, 
               e.contact_name, 
               e.website, 
               e.company_description,
               e.company_logo AS company_logo
        FROM placements p 
        INNER JOIN employers e ON p.employer_id = e.employer_id 
        WHERE p.placement_id = :placement_id";

            $statement = $this->_dbHandle->prepare($sql);
            $statement->bindParam(':placement_id', $placementId, PDO::PARAM_INT);
            $statement->execute();

            $row = $statement->fetch(PDO::FETCH_ASSOC);

            if ($row) {
                $placement = new PlacementData($row);

                // Fetch skills
                $skills = $this->fetchPlacementSkills($placementId);
                $placement->setSkills($skills);

                return $placement;
            }

            return null;

        } catch (PDOException $e) {
            echo "Error fetching placement: " . $e->getMessage();
            return null;
        }
    }

    /**
     * Fetch skills for a placement
     */
    public function fetchPlacementSkills($placementId)
    {
        try {
            $sql = "SELECT s.skill_id, s.skill_name, s.skill_code, ps.required_proficiency
                    FROM placement_skills ps
                    INNER JOIN sfia_skills s ON ps.skill_id = s.skill_id
                    WHERE ps.placement_id = :placement_id
                    ORDER BY ps.required_proficiency DESC";

            $statement = $this->_dbHandle->prepare($sql);
            $statement->bindParam(':placement_id', $placementId, PDO::PARAM_INT);
            $statement->execute();

            return $statement->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            echo "Error fetching skills: " . $e->getMessage();
            return [];
        }
    }

    /**
     * Get unique locations for filter dropdown
     */
    public function getUniqueLocations()
    {
        try {
            $sql = "SELECT DISTINCT location FROM placements WHERE status = 'active' ORDER BY location";
            $statement = $this->_dbHandle->prepare($sql);
            $statement->execute();
            return $statement->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Get unique employers for filter dropdown
     */
    public function getUniqueEmployers()
    {
        try {
            $sql = "SELECT DISTINCT e.company_name 
                    FROM employers e 
                    INNER JOIN placements p ON e.employer_id = p.employer_id 
                    WHERE p.status = 'active' 
                    ORDER BY e.company_name";
            $statement = $this->_dbHandle->prepare($sql);
            $statement->execute();
            return $statement->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            return [];
        }
    }
}