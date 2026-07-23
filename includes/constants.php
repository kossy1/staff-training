<?php
// Application constants

// User Roles
define('ROLE_ADMIN', 'admin');
define('ROLE_MANAGER', 'manager');
define('ROLE_EMPLOYEE', 'employee');

// Training Status
define('TRAINING_UPCOMING', 'upcoming');
define('TRAINING_ONGOING', 'ongoing');
define('TRAINING_COMPLETED', 'completed');
define('TRAINING_CANCELLED', 'cancelled');

// Enrollment Status
define('ENROLLMENT_ENROLLED', 'enrolled');
define('ENROLLMENT_IN_PROGRESS', 'in_progress');
define('ENROLLMENT_COMPLETED', 'completed');
define('ENROLLMENT_DROPPED', 'dropped');

// Employee Status
define('EMPLOYEE_ACTIVE', 'active');
define('EMPLOYEE_INACTIVE', 'inactive');
define('EMPLOYEE_ON_LEAVE', 'on_leave');

// Development Plan Status
define('PLAN_NOT_STARTED', 'not_started');
define('PLAN_IN_PROGRESS', 'in_progress');
define('PLAN_COMPLETED', 'completed');
define('PLAN_DELAYED', 'delayed');

// Priority Levels
define('PRIORITY_LOW', 'low');
define('PRIORITY_MEDIUM', 'medium');
define('PRIORITY_HIGH', 'high');
define('PRIORITY_CRITICAL', 'critical');

// Notification Types
define('NOTIFICATION_INFO', 'info');
define('NOTIFICATION_SUCCESS', 'success');
define('NOTIFICATION_WARNING', 'warning');
define('NOTIFICATION_ERROR', 'error');

// File Upload Settings
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx']);

// Pagination
define('ITEMS_PER_PAGE', 10);

// Date Formats
define('DATE_FORMAT', 'Y-m-d');
define('DATETIME_FORMAT', 'Y-m-d H:i:s');
define('DISPLAY_DATE_FORMAT', 'M d, Y');
define('DISPLAY_DATETIME_FORMAT', 'M d, Y H:i');
?>