<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: index.php");
    exit();
}

// Database connection
$conn = new mysqli('localhost', 'root', '', 'user_activity');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize selected month and year
$selected_year = isset($_POST['year']) ? (int)$_POST['year'] : date('Y');
$selected_month = isset($_POST['month']) ? (int)$_POST['month'] : date('m');

// Fetch all unique courses (normalized to lowercase and trimmed)
$courses_query = $conn->query("
    SELECT DISTINCT TRIM(course) AS course 
    FROM user_logs 
    WHERE course IS NOT NULL 
    ORDER BY course ASC
");

if (!$courses_query) {
    die("Error fetching courses: " . $conn->error);
}

$courses = $courses_query->fetch_all(MYSQLI_ASSOC);

// Fetch all links from the database
$result = $conn->query("SELECT * FROM links");
$links = $result->fetch_all(MYSQLI_ASSOC);

// Fetch statistics for each course
$course_stats = [];
foreach ($courses as $course) {
    $course_name = $course['course'];
    $stats = [];

    foreach ($links as $index => $link) {
        $link_number = $index + 1;
        $query = "
            SELECT COUNT(*) AS click_count
            FROM link_clicks l
            JOIN user_logs u ON l.user_id = u.id
            WHERE l.link_number = ? AND TRIM(u.course) = ?
            AND YEAR(l.click_time) = ? AND MONTH(l.click_time) = ?
        ";
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            die("Query preparation failed: " . $conn->error);
        }

        $stmt->bind_param("isii", $link_number, $course_name, $selected_year, $selected_month);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stats[$link_number] = isset($row['click_count']) ? (int)$row['click_count'] : 0;
        $stmt->close();
    }

    // Calculate total clicks for the course
    $total_clicks = array_sum($stats);

    $course_stats[] = [
        'course' => $course_name,
        'stats' => $stats,
        'total_clicks' => $total_clicks,
        'percentage' => 0 // Placeholder for percentage
    ];
}

// Calculate total clicks across all courses
$total_clicks_all_courses = array_sum(array_column($course_stats, 'total_clicks'));

// Calculate percentages for each course safely
foreach ($course_stats as &$course) { 
    $course['percentage'] = ($total_clicks_all_courses > 0) 
        ? round(($course['total_clicks'] / $total_clicks_all_courses) * 100, 2) 
        : 0;
}
unset($course);

// Sort the course_stats array by percentage in descending order
usort($course_stats, function($a, $b) {
    return $b['percentage'] <=> $a['percentage'];
});

// Fetch login counts by course
$login_counts_query = $conn->query("
    SELECT course, COUNT(*) AS login_count
    FROM login_counts
    GROUP BY course
    ORDER BY login_count DESC
");

if (!$login_counts_query) {
    die("Error fetching login counts: " . $conn->error);
}

$login_counts = $login_counts_query->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        h1 {
            font-size: 2rem;
            margin-bottom: 20px;
            color: #343a40;
        }

        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
        }

        .table {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .table th, .table td {
            vertical-align: middle;
            text-align: center;
        }

        .table th {
            background-color: #343a40;
            color: white;
        }

        .btn-custom {
            background-color: #007bff;
            color: white;
            border-radius: 5px;
            padding: 8px 16px;
            border: none;
            transition: background-color 0.3s ease;
        }

        .btn-custom:hover {
            background-color: #0056b3;
        }

        .logout-button {
            background-color: #dc3545;
            color: white;
            border: none;
            border-radius: 5px;
            padding: 8px 16px;
            transition: background-color 0.3s ease;
        }

        .logout-button:hover {
            background-color: #c82333;
        }

        .filter-form {
            margin-bottom: 20px;
        }

        .hidden {
            display: none;
        }

        .chart-container {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            margin-top: 20px;
        }

        .footer {
            background-color: #196199; /* Updated footer color */
            color: white;
            text-align: center;
            padding: 10px 0;
            margin-top: auto; /* Pushes footer to the bottom */
            width: 100%;
        }

        .logout-button {
            background-color: #dc3545;
            color: white;
            border: none;
            border-radius: 5px;
            padding: 8px 16px;
            transition: background-color 0.3s ease;
            float: right;
        }

    </style>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script>
    <script>
        let showPercentagesInGraph = false;

        function toggleLinkPercentages() {
            var percentages = document.querySelectorAll('.link-percentage');
            percentages.forEach(function (element) {
                element.classList.toggle('hidden');
            });
            showPercentagesInGraph = !showPercentagesInGraph;
            renderChart(courseStats, showPercentagesInGraph);
        }

        function toggleChart() {
            var chartContainers = document.querySelectorAll('.chart-container');
            chartContainers.forEach(function (container) {
                container.classList.toggle('hidden');
            });
        }

        function renderChart(courseStats, showPercentages) {
            const ctx = document.getElementById('courseChart').getContext('2d');
            const labels = courseStats.map(course => course.course);
            const datasets = [];
            <?php foreach ($links as $index => $link): ?>
                datasets.push({
                    label: '<?php echo htmlspecialchars($link['title']); ?>',
                    data: courseStats.map(course => course.stats[<?php echo $index + 1; ?>]),
                    backgroundColor: `rgba(${Math.floor(Math.random() * 255)}, ${Math.floor(Math.random() * 255)}, ${Math.floor(Math.random() * 255)}, 0.2)`,
                    borderColor: `rgba(${Math.floor(Math.random() * 255)}, ${Math.floor(Math.random() * 255)}, ${Math.floor(Math.random() * 255)}, 1)`,
                    borderWidth: 1
                });
            <?php endforeach; ?>

            const config = {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: datasets
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.dataset.label || '';

                                    if (label) {
                                        label += ': ';
                                    }
                                    if (context.parsed.y !== null) {
                                        label += context.parsed.y;
                                        if (showPercentages) {
                                            label += ' (';
                                            label += courseStats[context.dataIndex].percentage;
                                            label += '%)';
                                        }
                                    }
                                    return label;
                                }
                            }
                        },
                        datalabels: {
                            anchor: 'center',
                            align: 'center',
                            formatter: function(value) {
                                return value;
                            },
                            color: 'black',
                            font: {
                                weight: 'bold'
                            }
                        }
                    }
                },
                plugins: [ChartDataLabels]
            };
            new Chart(ctx, config);
        }

        function renderLoginChart(loginCounts) {
            const ctx = document.getElementById('loginChart').getContext('2d');
            const labels = loginCounts.map(count => count.course);
            const data = loginCounts.map(count => count.login_count);

            const config = {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Login Count',
                        data: data,
                        backgroundColor: 'rgba(54, 162, 235, 0.2)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    },
                    plugins: {
                        datalabels: {
                            anchor: 'center',
                            align: 'center',
                            formatter: function(value) {
                                return value;
                            },
                            color: 'black',
                            font: {
                                weight: 'bold'
                            }
                        }
                    }
                },
                plugins: [ChartDataLabels]
            };
            new Chart(ctx, config);
        }

        function printPage() {
            window.print();
        }

        document.addEventListener('DOMContentLoaded', function () {
            const courseStats = <?php echo json_encode($course_stats); ?>;
            const loginCounts = <?php echo json_encode($login_counts); ?>;
            renderChart(courseStats, showPercentagesInGraph);
            renderLoginChart(loginCounts);
        });
    </script>
</head>
<body>
    <div class="container">
        <h1>Admin Dashboard - <?php echo date('F Y', mktime(0, 0, 0, $selected_month, 1, $selected_year)); ?></h1>

        <!-- Filter Form for Month and Year -->
        <form method="POST" action="admin.php" class="filter-form">
            <label for="year">Year:</label>
            <select name="year" id="year">
                <?php
                $current_year = date('Y');
                for ($year = $current_year; $year >= 2020; $year--) {
                    $selected = ($year == $selected_year) ? 'selected' : '';
                    echo "<option value='$year' $selected>$year</option>";
                }
                ?>
            </select>
            <label for="month">Month:</label>
            <select name="month" id="month">
                <?php
                for ($month = 1; $month <= 12; $month++) {
                    $selected = ($month == $selected_month) ? 'selected' : '';
                    echo "<option value='$month' $selected>" . date('F', mktime(0, 0, 0, $month, 1, $current_year)) . "</option>";
                }
                ?>
            </select>
            <button type="submit" class="btn-custom">Apply Filter</button>
        </form>

        <!-- Button to Toggle Link Percentages -->
        <button onclick="toggleLinkPercentages()" class="btn-custom">Show Link Percentages</button>

        <!-- Button to Toggle Chart -->
        <button onclick="toggleChart()" class="btn-custom">Show/Hide Chart</button>

        <!-- Print Button -->
        <button onclick="printPage()" class="btn-custom">Print</button>

        <!-- Table to Display Statistics -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div></div> <!-- Empty div to push the button to the right -->
            <a href="search_students.php" class="btn btn-info">Search Students</a>
        </div>
        <table class="table">
            <thead>
                <tr>
                    <th>Course</th>
                    <?php foreach ($links as $link): ?>
                        <th><?php echo htmlspecialchars($link['title']); ?></th>
                    <?php endforeach; ?>
                    <th>Total Clicks</th>
                    <th>Percentage</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($course_stats as $course): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($course['course']); ?></td>
                        <?php foreach ($links as $index => $link): ?>
                            <td>
                                <?php echo $course['stats'][$index + 1]; ?>
                                <div class="link-percentage hidden">
                                    (<?php echo ($course['total_clicks'] > 0) ? round(($course['stats'][$index + 1] / $course['total_clicks']) * 100, 2) : 0; ?>%)
                                </div>
                            </td>
                        <?php endforeach; ?>
                        <td><?php echo $course['total_clicks']; ?></td>
                        <td><?php echo $course['percentage']; ?>%</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Table to Display Login Counts by Course -->
        <table class="table mt-4">
            <thead>
                <tr>
                    <th>Course</th>
                    <th>Login Count</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($login_counts as $login_count): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($login_count['course']); ?></td>
                        <td><?php echo $login_count['login_count']; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Chart to Display Statistics -->
        <div id="chartContainer" class="chart-container hidden">
            <canvas id="courseChart" width="400" height="200"></canvas>
        </div>

        <!-- Chart to Display Login Counts -->
        <div id="loginChartContainer" class="chart-container hidden mt-4">
            <canvas id="loginChart" width="400" height="200"></canvas>
        </div>

        <!-- Logout Button -->
        <form method="POST" action="logout.php" class="text-center mt-4">
            <button type="submit" class="logout-button">Logout</button>
        </form>

        <!-- Create Admin Account Link -->
        <div class="text-left mt-3">
            <a href="create_admin.php" class="btn btn-success">Create Admin Account</a>
        </div>

        <!-- Manage Links Button -->
        <div class="text-left mt-3">
            <a href="manage_links.php" class="btn btn-info">Manage Links</a>
        </div>
    </div>
</body>
</html>
