<?php
/**
 * Sample Data Population Script
 * Creates sample employers, placements, and SFIA skills
 * Run this once to populate your database with test data
 */

require_once('Models/Database.php');

echo "<h1>BCS Placements - Sample Data Population</h1>";
echo "<p>Starting data population...</p>";

try {
    $db = Database::getInstance();
    $dbHandle = $db->getdbConnection();

    // Start transaction
    $dbHandle->beginTransaction();

    echo "<h2>Step 1: Creating Sample Employers</h2>";

    // Sample Employers with real UK companies
    $employers = [
        [
            'email' => 'careers@bbc.co.uk',
            'password' => password_hash('employer123', PASSWORD_DEFAULT),
            'company_name' => 'BBC',
            'contact_name' => 'Sarah Johnson',
            'phone' => '020 7946 0001',
            'address' => 'Broadcasting House, Portland Place, London, W1A 1AA',
            'company_description' => 'The BBC is the world\'s leading public service broadcaster. We\'re impartial and independent, and every day we create distinctive, world-class programmes and content.',
            'website' => 'https://www.bbc.co.uk/careers'
        ],
        [
            'email' => 'recruitment@sky.uk',
            'password' => password_hash('employer123', PASSWORD_DEFAULT),
            'company_name' => 'Sky',
            'contact_name' => 'David Williams',
            'phone' => '020 7805 5000',
            'address' => 'Grant Way, Isleworth, Middlesex, TW7 5QD',
            'company_description' => 'Sky is Europe\'s leading media and entertainment company. We\'re proud to be part of Comcast Corporation.',
            'website' => 'https://careers.sky.com'
        ],
        [
            'email' => 'jobs@bt.com',
            'password' => password_hash('employer123', PASSWORD_DEFAULT),
            'company_name' => 'BT Group',
            'contact_name' => 'Emma Thompson',
            'phone' => '020 7356 5000',
            'address' => '1 Braham Street, London, E1 8EE',
            'company_description' => 'BT Group is a leading telecommunications and network provider, serving customers in 180 countries worldwide.',
            'website' => 'https://www.bt.com/careers'
        ],
        [
            'email' => 'careers@rolls-royce.com',
            'password' => password_hash('employer123', PASSWORD_DEFAULT),
            'company_name' => 'Rolls-Royce',
            'contact_name' => 'James Anderson',
            'phone' => '0133 238 0000',
            'address' => '62 Buckingham Gate, London, SW1E 6AT',
            'company_description' => 'Rolls-Royce pioneers cutting-edge technologies that deliver the cleanest, safest and most competitive solutions.',
            'website' => 'https://www.rolls-royce.com/careers'
        ],
        [
            'email' => 'talent@baesystems.com',
            'password' => password_hash('employer123', PASSWORD_DEFAULT),
            'company_name' => 'BAE Systems',
            'contact_name' => 'Michael Brown',
            'phone' => '0125 237 3232',
            'address' => 'Warwick House, Farnborough Aerospace Centre, Farnborough, GU14 6YU',
            'company_description' => 'BAE Systems is a global defence, aerospace and security company with approximately 93,100 employees worldwide.',
            'website' => 'https://www.baesystems.com/careers'
        ],
        [
            'email' => 'university@arm.com',
            'password' => password_hash('employer123', PASSWORD_DEFAULT),
            'company_name' => 'Arm',
            'contact_name' => 'Sophie Martin',
            'phone' => '0122 340 0400',
            'address' => '110 Fulbourn Road, Cambridge, CB1 9NJ',
            'company_description' => 'Arm technology is at the heart of a computing and connectivity revolution that is transforming the way people live and businesses operate.',
            'website' => 'https://www.arm.com/careers'
        ],
        [
            'email' => 'graduates@jaguarlandrover.com',
            'password' => password_hash('employer123', PASSWORD_DEFAULT),
            'company_name' => 'Jaguar Land Rover',
            'contact_name' => 'Oliver Davis',
            'phone' => '024 7640 5000',
            'address' => 'Abbey Road, Whitley, Coventry, CV3 4LF',
            'company_description' => 'Jaguar Land Rover is the UK\'s largest automotive manufacturer, built around two iconic British car brands.',
            'website' => 'https://www.jaguarlandrover.com/careers'
        ],
        [
            'email' => 'earlycareers@hsbc.com',
            'password' => password_hash('employer123', PASSWORD_DEFAULT),
            'company_name' => 'HSBC',
            'contact_name' => 'Rachel Wilson',
            'phone' => '020 7991 8888',
            'address' => '8 Canada Square, London, E14 5HQ',
            'company_description' => 'HSBC is one of the world\'s largest banking and financial services organisations.',
            'website' => 'https://www.hsbc.com/careers'
        ],
        [
            'email' => 'students@lloydsbanking.com',
            'password' => password_hash('employer123', PASSWORD_DEFAULT),
            'company_name' => 'Lloyds Banking Group',
            'contact_name' => 'Thomas Taylor',
            'phone' => '020 7626 1500',
            'address' => '25 Gresham Street, London, EC2V 7HN',
            'company_description' => 'Lloyds Banking Group is a leading UK-based financial services group providing banking and financial services.',
            'website' => 'https://www.lloydsbankinggroup.com/careers'
        ],
        [
            'email' => 'opportunities@gsk.com',
            'password' => password_hash('employer123', PASSWORD_DEFAULT),
            'company_name' => 'GSK',
            'contact_name' => 'Hannah Clark',
            'phone' => '020 8047 5000',
            'address' => '980 Great West Road, Brentford, Middlesex, TW8 9GS',
            'company_description' => 'GSK is a global biopharma company with a purpose to unite science, technology and talent to get ahead of disease together.',
            'website' => 'https://www.gsk.com/careers'
        ]
    ];

    $employerIds = [];
    foreach ($employers as $emp) {
        // Insert user
        $sql = "INSERT INTO users (email, password, role) VALUES (:email, :password, 'employer')";
        $stmt = $dbHandle->prepare($sql);
        $stmt->execute([':email' => $emp['email'], ':password' => $emp['password']]);
        $userId = $dbHandle->lastInsertId();

        // Insert employer
        $sql = "INSERT INTO employers (user_id, company_name, contact_name, phone, address, company_description, website) 
                VALUES (:user_id, :company_name, :contact_name, :phone, :address, :description, :website)";
        $stmt = $dbHandle->prepare($sql);
        $stmt->execute([
            ':user_id' => $userId,
            ':company_name' => $emp['company_name'],
            ':contact_name' => $emp['contact_name'],
            ':phone' => $emp['phone'],
            ':address' => $emp['address'],
            ':description' => $emp['company_description'],
            ':website' => $emp['website']
        ]);
        $employerIds[$emp['company_name']] = $dbHandle->lastInsertId();

        echo "✓ Created employer: {$emp['company_name']}<br>";
    }

    echo "<h2>Step 2: Creating Sample SFIA Skills</h2>";

    // Sample SFIA Skills (simplified version)
    $skills = [
        ['PROG', 'Programming/software development', 'Technical Skills'],
        ['TEST', 'Testing', 'Technical Skills'],
        ['DBAD', 'Database administration', 'Technical Skills'],
        ['NTAS', 'Network support', 'Technical Skills'],
        ['SCTY', 'Information security', 'Technical Skills'],
        ['DENG', 'Data engineering', 'Technical Skills'],
        ['DTAN', 'Data analysis', 'Technical Skills'],
        ['UNAN', 'User research', 'Design'],
        ['HCEV', 'User experience design', 'Design'],
        ['DEVOPS', 'DevOps engineering', 'Technical Skills'],
        ['CLOD', 'Cloud computing', 'Technical Skills'],
        ['MLNG', 'Machine learning', 'Technical Skills'],
        ['WBEN', 'Web development', 'Technical Skills'],
        ['MOBL', 'Mobile development', 'Technical Skills'],
        ['SYST', 'Systems administration', 'Technical Skills']
    ];

    $skillIds = [];
    foreach ($skills as $skill) {
        $sql = "INSERT INTO sfia_skills (skill_code, skill_name, category) VALUES (:code, :name, :category)";
        $stmt = $dbHandle->prepare($sql);
        $stmt->execute([':code' => $skill[0], ':name' => $skill[1], ':category' => $skill[2]]);
        $skillIds[$skill[0]] = $dbHandle->lastInsertId();
        echo "✓ Created skill: {$skill[1]}<br>";
    }

    echo "<h2>Step 3: Creating Sample Placements</h2>";

    // Sample Placements
    $placements = [
        [
            'employer' => 'BBC',
            'title' => 'Software Engineer Placement 2026',
            'description' => "Join BBC's Engineering team as a Software Engineer placement student. You'll work on cutting-edge web and mobile applications that reach millions of users daily.\n\nResponsibilities:\n- Develop and maintain web applications using modern frameworks\n- Collaborate with cross-functional teams\n- Participate in code reviews and testing\n- Learn from experienced engineers\n\nWhat we offer:\n- Mentorship from senior engineers\n- Exposure to large-scale systems\n- Flexible working arrangements\n- Social and networking events",
            'location' => 'London',
            'salary_min' => 24000,
            'salary_max' => 28000,
            'start_date' => '2026-09-01',
            'end_date' => '2027-08-31',
            'deadline' => '2026-03-31',
            'duration' => 12,
            'logo' => 'https://via.placeholder.com/150x80/BB1919/FFFFFF?text=BBC',
            'url' => 'https://www.bbc.co.uk/careers/work-experience',
            'skills' => ['PROG' => 3, 'WBEN' => 3, 'TEST' => 2]
        ],
        [
            'employer' => 'Sky',
            'title' => 'IT Support Placement 2026',
            'description' => "Sky is looking for an enthusiastic IT Support placement student to join our Technology team.\n\nYou will:\n- Provide technical support to employees\n- Troubleshoot hardware and software issues\n- Assist with IT infrastructure projects\n- Document support processes\n\nIdeal candidate:\n- Strong problem-solving skills\n- Good communication abilities\n- Interest in IT systems\n- Team player attitude",
            'location' => 'Osterley, London',
            'salary_min' => 22000,
            'salary_max' => 26000,
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
            'deadline' => '2026-02-28',
            'duration' => 12,
            'logo' => 'https://via.placeholder.com/150x80/0072C6/FFFFFF?text=Sky',
            'url' => 'https://careers.sky.com',
            'skills' => ['NTAS' => 2, 'SYST' => 2]
        ],
        [
            'employer' => 'BT Group',
            'title' => 'Cybersecurity Placement 2026',
            'description' => "BT Group is offering an exciting opportunity in our Cybersecurity team. Gain hands-on experience protecting critical infrastructure.\n\nWhat you'll do:\n- Monitor security systems and alerts\n- Assist with vulnerability assessments\n- Learn about threat intelligence\n- Support security incident response\n\nRequirements:\n- Studying Computer Science or related field\n- Interest in cybersecurity\n- Analytical mindset\n- Attention to detail",
            'location' => 'London',
            'salary_min' => 26000,
            'salary_max' => 30000,
            'start_date' => '2026-09-01',
            'end_date' => '2027-08-31',
            'deadline' => '2026-04-15',
            'duration' => 12,
            'logo' => 'https://via.placeholder.com/150x80/5514B4/FFFFFF?text=BT',
            'url' => 'https://www.bt.com/careers',
            'skills' => ['SCTY' => 3, 'NTAS' => 2, 'SYST' => 2]
        ],
        [
            'employer' => 'Rolls-Royce',
            'title' => 'Data Analytics Placement 2026',
            'description' => "Join Rolls-Royce as a Data Analytics placement student and work with big data in the aerospace industry.\n\nKey responsibilities:\n- Analyze large datasets to extract insights\n- Create data visualizations and dashboards\n- Support data-driven decision making\n- Work with cutting-edge analytics tools\n\nBenefits:\n- Competitive salary\n- Relocation support available\n- Professional development\n- Real-world project experience",
            'location' => 'Derby',
            'salary_min' => 25000,
            'salary_max' => 29000,
            'start_date' => '2026-09-01',
            'end_date' => '2027-09-01',
            'deadline' => '2026-01-31',
            'duration' => 12,
            'logo' => 'https://via.placeholder.com/150x80/000080/FFFFFF?text=Rolls-Royce',
            'url' => 'https://www.rolls-royce.com/careers',
            'skills' => ['DTAN' => 3, 'DENG' => 2, 'PROG' => 2]
        ],
        [
            'employer' => 'BAE Systems',
            'title' => 'Network Engineering Placement 2026',
            'description' => "BAE Systems is seeking a Network Engineering placement student for our Defence sector.\n\nWhat we're looking for:\n- Understanding of networking fundamentals\n- Problem-solving abilities\n- Security clearance eligible (UK national)\n- Passion for technology\n\nYou'll gain experience in:\n- Network design and implementation\n- Security protocols\n- Enterprise networking\n- Defence systems",
            'location' => 'Farnborough',
            'salary_min' => 27000,
            'salary_max' => 31000,
            'start_date' => '2026-09-01',
            'end_date' => '2027-08-31',
            'deadline' => '2025-12-31',
            'duration' => 12,
            'logo' => 'https://via.placeholder.com/150x80/004C97/FFFFFF?text=BAE+Systems',
            'url' => 'https://www.baesystems.com/careers',
            'skills' => ['NTAS' => 3, 'SCTY' => 3, 'SYST' => 2]
        ],
        [
            'employer' => 'Arm',
            'title' => 'DevOps Engineer Placement 2026',
            'description' => "Arm is looking for a DevOps placement student to join our world-class engineering team in Cambridge.\n\nResponsibilities:\n- Support CI/CD pipelines\n- Automate deployment processes\n- Monitor system performance\n- Collaborate with development teams\n\nWhat you'll learn:\n- Cloud technologies (AWS, Azure)\n- Container orchestration\n- Infrastructure as code\n- Agile methodologies",
            'location' => 'Cambridge',
            'salary_min' => 28000,
            'salary_max' => 32000,
            'start_date' => '2026-06-01',
            'end_date' => '2027-05-31',
            'deadline' => '2026-02-15',
            'duration' => 12,
            'logo' => 'https://via.placeholder.com/150x80/0091BD/FFFFFF?text=Arm',
            'url' => 'https://www.arm.com/careers',
            'skills' => ['DEVOPS' => 3, 'CLOD' => 3, 'PROG' => 2, 'SYST' => 2]
        ],
        [
            'employer' => 'Jaguar Land Rover',
            'title' => 'Software Testing Placement 2026',
            'description' => "Join JLR's software testing team and help ensure quality in next-generation automotive systems.\n\nWhat you'll do:\n- Test automotive software systems\n- Write automated test scripts\n- Report and track defects\n- Collaborate with development teams\n\nIdeal for students interested in:\n- Automotive technology\n- Software quality assurance\n- Test automation\n- Agile development",
            'location' => 'Coventry',
            'salary_min' => 24000,
            'salary_max' => 27000,
            'start_date' => '2026-09-01',
            'end_date' => '2027-08-31',
            'deadline' => '2026-03-15',
            'duration' => 12,
            'logo' => 'https://via.placeholder.com/150x80/005A2B/FFFFFF?text=JLR',
            'url' => 'https://www.jaguarlandrover.com/careers',
            'skills' => ['TEST' => 3, 'PROG' => 2]
        ],
        [
            'employer' => 'HSBC',
            'title' => 'Full Stack Developer Placement 2026',
            'description' => "HSBC is offering a Full Stack Developer placement in our Technology division.\n\nYou will:\n- Develop web applications using modern frameworks\n- Work on both frontend and backend systems\n- Participate in agile ceremonies\n- Learn banking technology systems\n\nRequirements:\n- Programming knowledge (Java, JavaScript, Python)\n- Understanding of web technologies\n- Interest in fintech\n- Strong communication skills",
            'location' => 'London',
            'salary_min' => 30000,
            'salary_max' => 34000,
            'start_date' => '2026-09-01',
            'end_date' => '2027-08-31',
            'deadline' => '2026-01-15',
            'duration' => 12,
            'logo' => 'https://via.placeholder.com/150x80/DB0011/FFFFFF?text=HSBC',
            'url' => 'https://www.hsbc.com/careers',
            'skills' => ['PROG' => 3, 'WBEN' => 3, 'DBAD' => 2, 'TEST' => 2]
        ],
        [
            'employer' => 'Lloyds Banking Group',
            'title' => 'Cloud Engineering Placement 2026',
            'description' => "Lloyds Banking Group is seeking a Cloud Engineering placement student.\n\nWhat we offer:\n- Work with AWS and Azure\n- Learn cloud architecture\n- Exposure to financial services technology\n- Excellent training and development\n\nYou'll be involved in:\n- Cloud infrastructure deployment\n- Cost optimization\n- Security implementation\n- Automation projects",
            'location' => 'London',
            'salary_min' => 29000,
            'salary_max' => 33000,
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
            'deadline' => '2026-02-01',
            'duration' => 12,
            'logo' => 'https://via.placeholder.com/150x80/006A4D/FFFFFF?text=Lloyds',
            'url' => 'https://www.lloydsbankinggroup.com/careers',
            'skills' => ['CLOD' => 3, 'DEVOPS' => 3, 'SCTY' => 2]
        ],
        [
            'employer' => 'GSK',
            'title' => 'Systems Administrator Placement 2026',
            'description' => "GSK is offering a Systems Administration placement in our IT Operations team.\n\nKey activities:\n- Maintain and monitor IT systems\n- Support server infrastructure\n- Troubleshoot technical issues\n- Implement system updates\n\nWhat you'll gain:\n- Experience with enterprise systems\n- Healthcare IT knowledge\n- Professional certifications support\n- Mentorship from senior staff",
            'location' => 'Brentford, London',
            'salary_min' => 23000,
            'salary_max' => 26000,
            'start_date' => '2026-06-01',
            'end_date' => '2027-05-31',
            'deadline' => '2026-03-01',
            'duration' => 12,
            'logo' => 'https://via.placeholder.com/150x80/FF6900/FFFFFF?text=GSK',
            'url' => 'https://www.gsk.com/careers',
            'skills' => ['SYST' => 3, 'NTAS' => 2, 'SCTY' => 2]
        ]
    ];

    foreach ($placements as $p) {
        // Insert placement
        $sql = "INSERT INTO placements (employer_id, title, description, location, salary_min, salary_max, 
                start_date, end_date, deadline, duration_months, company_logo, application_url, status, created_at) 
                VALUES (:employer_id, :title, :description, :location, :salary_min, :salary_max, 
                :start_date, :end_date, :deadline, :duration, :logo, :url, 'active', CURRENT_TIMESTAMP)";
        $stmt = $dbHandle->prepare($sql);
        $stmt->execute([
            ':employer_id' => $employerIds[$p['employer']],
            ':title' => $p['title'],
            ':description' => $p['description'],
            ':location' => $p['location'],
            ':salary_min' => $p['salary_min'],
            ':salary_max' => $p['salary_max'],
            ':start_date' => $p['start_date'],
            ':end_date' => $p['end_date'],
            ':deadline' => $p['deadline'],
            ':duration' => $p['duration'],
            ':logo' => $p['logo'],
            ':url' => $p['url']
        ]);
        $placementId = $dbHandle->lastInsertId();

        // Insert required skills
        foreach ($p['skills'] as $skillCode => $proficiency) {
            $sql = "INSERT INTO placement_skills (placement_id, skill_id, required_proficiency) 
                    VALUES (:placement_id, :skill_id, :proficiency)";
            $stmt = $dbHandle->prepare($sql);
            $stmt->execute([
                ':placement_id' => $placementId,
                ':skill_id' => $skillIds[$skillCode],
                ':proficiency' => $proficiency
            ]);
        }

        echo "✓ Created placement: {$p['title']} at {$p['employer']}<br>";
    }

    // Commit transaction
    $dbHandle->commit();

    echo "<h2>✅ Success!</h2>";
    echo "<p><strong>Sample data has been populated successfully!</strong></p>";
    echo "<p>Created:</p>";
    echo "<ul>";
    echo "<li>" . count($employers) . " employers</li>";
    echo "<li>" . count($skills) . " SFIA skills</li>";
    echo "<li>" . count($placements) . " placements</li>";
    echo "</ul>";
    echo "<p><a href='placements.php'>View Placements →</a></p>";

    echo "<hr>";
    echo "<h3>Test Login Credentials:</h3>";
    echo "<p><strong>Any Employer Account:</strong><br>";
    echo "Email: careers@bbc.co.uk (or any other employer email above)<br>";
    echo "Password: employer123</p>";

} catch (PDOException $e) {
    $dbHandle->rollBack();
    echo "<p style='color: red;'><strong>Error:</strong> " . $e->getMessage() . "</p>";
}