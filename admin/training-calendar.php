<?php
// admin/training-calendar.php - Training Calendar View
require_once '../includes/config.php';
require_once '../includes/session.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: ../login.php');
    exit();
}

// Get all trainings for calendar
$trainings = $conn->query("
    SELECT id, title, start_date, end_date, status, location 
    FROM training_programs 
    WHERE status != 'cancelled'
    ORDER BY start_date ASC
");

// Prepare events for FullCalendar
$events = [];
while ($training = $trainings->fetch_assoc()) {
    $color = '#667eea';
    if ($training['status'] == 'ongoing') $color = '#48bb78';
    if ($training['status'] == 'completed') $color = '#6c757d';
    if ($training['status'] == 'upcoming') $color = '#f6c23e';
    
    $events[] = [
        'id' => $training['id'],
        'title' => $training['title'],
        'start' => $training['start_date'],
        'end' => date('Y-m-d', strtotime($training['end_date'] . ' +1 day')),
        'color' => $color,
        'extendedProps' => [
            'status' => $training['status'],
            'location' => $training['location'] ?? 'TBD'
        ]
    ];
}

$page_title = 'Training Calendar';
$page_scripts = '
<script>
document.addEventListener("DOMContentLoaded", function() {
    var calendarEl = document.getElementById("calendar");
    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: "dayGridMonth",
        headerToolbar: {
            left: "prev,next today",
            center: "title",
            right: "dayGridMonth,timeGridWeek,listWeek"
        },
        events: ' . json_encode($events) . ',
        eventClick: function(info) {
            window.location.href = "view-training.php?id=" + info.event.id;
        },
        eventDidMount: function(info) {
            // Add tooltip
            var tooltip = new bootstrap.Tooltip(info.el, {
                title: info.event.title + " (" + info.event.extendedProps.status + ")",
                placement: "top",
                trigger: "hover",
                container: "body"
            });
        }
    });
    calendar.render();
});
</script>
';
?>
<?php require_once 'includes/header.php'; ?>
<?php require_once 'includes/navbar.php'; ?>
<?php require_once 'includes/sidebar.php'; ?>

<div class="main-content">
    <div class="page-header">
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center">
            <div>
                <h1><i class="fas fa-calendar-alt text-primary"></i> Training Calendar</h1>
                <p class="text-muted">View all training programs in calendar format</p>
            </div>
            <div>
                <a href="add-training.php" class="btn btn-primary">
                    <i class="fas fa-plus-circle"></i> Add Training
                </a>
                <a href="trainings.php" class="btn btn-secondary">
                    <i class="fas fa-list"></i> List View
                </a>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div id="calendar" style="height: 700px;"></div>
        </div>
    </div>

    <!-- Legend -->
    <div class="card mt-3">
        <div class="card-body">
            <h6>Status Legend</h6>
            <div class="d-flex flex-wrap gap-3">
                <span><span class="badge" style="background: #f6c23e;">&nbsp;&nbsp;&nbsp;&nbsp;</span> Upcoming</span>
                <span><span class="badge" style="background: #48bb78;">&nbsp;&nbsp;&nbsp;&nbsp;</span> Ongoing</span>
                <span><span class="badge" style="background: #6c757d;">&nbsp;&nbsp;&nbsp;&nbsp;</span> Completed</span>
                <span><span class="badge" style="background: #667eea;">&nbsp;&nbsp;&nbsp;&nbsp;</span> Other</span>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>