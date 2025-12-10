<?php
/**
 * ShortlistDataSet Class
 * Handles shortlisting placements (saved for later)
 */

require_once('Database.php');

class ShortlistDataSet
{
    protected $_dbHandle;
    protected $_dbInstance;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->_dbInstance = Database::getInstance();
        $this->_dbHandle = $this->_dbInstance->getdbConnection();
    }

    /**
     * Add placement to shortlist
     */
    public function addToShortlist($studentId, $placementId)
    {
        try {
            $sql = "INSERT INTO shortlisted_placements (student_id, placement_id) 
                    VALUES (:student_id, :placement_id)";

            $statement = $this->_dbHandle->prepare($sql);
            $statement->bindParam(':student_id', $studentId, PDO::PARAM_INT);
            $statement->bindParam(':placement_id', $placementId, PDO::PARAM_INT);

            return $statement->execute();

        } catch (PDOException $e) {
            // If duplicate entry (already shortlisted), return true
            if (strpos($e->getMessage(), 'UNIQUE constraint failed') !== false) {
                return true;
            }
            echo "Error adding to shortlist: " . $e->getMessage();
            return false;
        }
    }

    /**
     * Remove placement from shortlist
     */
    public function removeFromShortlist($studentId, $placementId)
    {
        try {
            $sql = "DELETE FROM shortlisted_placements 
                    WHERE student_id = :student_id AND placement_id = :placement_id";

            $statement = $this->_dbHandle->prepare($sql);
            $statement->bindParam(':student_id', $studentId, PDO::PARAM_INT);
            $statement->bindParam(':placement_id', $placementId, PDO::PARAM_INT);

            return $statement->execute();

        } catch (PDOException $e) {
            echo "Error removing from shortlist: " . $e->getMessage();
            return false;
        }
    }

    /**
     * Check if placement is shortlisted by student
     */
    public function isShortlisted($studentId, $placementId)
    {
        try {
            $sql = "SELECT COUNT(*) FROM shortlisted_placements 
                    WHERE student_id = :student_id AND placement_id = :placement_id";

            $statement = $this->_dbHandle->prepare($sql);
            $statement->bindParam(':student_id', $studentId, PDO::PARAM_INT);
            $statement->bindParam(':placement_id', $placementId, PDO::PARAM_INT);
            $statement->execute();

            return $statement->fetchColumn() > 0;

        } catch (PDOException $e) {
            echo "Error checking shortlist: " . $e->getMessage();
            return false;
        }
    }

    /**
     * Get all shortlisted placements for a student
     */
    public function getShortlistedPlacements($studentId)
    {
        try {
            $sql = "SELECT p.placement_id 
                    FROM shortlisted_placements sp
                    INNER JOIN placements p ON sp.placement_id = p.placement_id
                    WHERE sp.student_id = :student_id
                    ORDER BY sp.shortlisted_at DESC";

            $statement = $this->_dbHandle->prepare($sql);
            $statement->bindParam(':student_id', $studentId, PDO::PARAM_INT);
            $statement->execute();

            return $statement->fetchAll(PDO::FETCH_COLUMN);

        } catch (PDOException $e) {
            echo "Error fetching shortlist: " . $e->getMessage();
            return [];
        }
    }

    /**
     * Count shortlisted placements for a student
     */
    public function countShortlisted($studentId)
    {
        try {
            $sql = "SELECT COUNT(*) FROM shortlisted_placements WHERE student_id = :student_id";

            $statement = $this->_dbHandle->prepare($sql);
            $statement->bindParam(':student_id', $studentId, PDO::PARAM_INT);
            $statement->execute();

            return $statement->fetchColumn();

        } catch (PDOException $e) {
            return 0;
        }
    }
}