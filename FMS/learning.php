<?php
require_once 'config/config.php';
requireLogin();

$pageTitle = 'Learning Management';
$pdo = getDBConnection();

$activeTab = $_GET['tab'] ?? 'catalog';
$userId = $_SESSION['user_id'];

// Get courses
$courses = $pdo->prepare("
    SELECT lc.*, 
           ce.progress, ce.status as enrollment_status, ce.completed_modules, ce.total_modules, ce.last_accessed
    FROM learning_courses lc
    LEFT JOIN course_enrollments ce ON lc.id = ce.course_id AND ce.employee_id = ?
    ORDER BY lc.created_at DESC
");
$courses->execute([$userId]);
$allCourses = $courses->fetchAll();

// Get my progress
$myProgress = $pdo->prepare("
    SELECT ce.*, lc.title as course_name
    FROM course_enrollments ce
    JOIN learning_courses lc ON ce.course_id = lc.id
    WHERE ce.employee_id = ?
    ORDER BY ce.last_accessed DESC
");
$myProgress->execute([$userId]);
$progress = $myProgress->fetchAll();

// Get certificates
$certificates = $pdo->prepare("SELECT * FROM certificates WHERE employee_id = ? ORDER BY issue_date DESC");
$certificates->execute([$userId]);
$myCertificates = $certificates->fetchAll();

// Get badges
$badges = $pdo->prepare("SELECT * FROM badges WHERE employee_id = ? ORDER BY earned_date DESC");
$badges->execute([$userId]);
$myBadges = $badges->fetchAll();

// Get examinations
$examinations = $pdo->prepare("
    SELECT e.*, lc.title as course_name
    FROM examinations e
    JOIN learning_courses lc ON e.course_id = lc.id
    WHERE e.employee_id = ?
    ORDER BY e.exam_date DESC
");
$examinations->execute([$userId]);
$myExams = $examinations->fetchAll();

ob_start();
?>

<div class="p-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Learning Management System</h1>
        <p class="text-gray-600">
            Browse courses, track your progress, and earn certifications
        </p>
    </div>

    <!-- Tabs -->
    <div class="mb-6 border-b border-gray-200">
        <div class="flex gap-4 overflow-x-auto">
            <a href="?tab=catalog" class="pb-3 px-2 border-b-2 transition-colors whitespace-nowrap <?php echo $activeTab == 'catalog' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-600 hover:text-gray-900'; ?>">
                <div class="flex items-center gap-2">
                    <i class="fas fa-book-open"></i>
                    <span>Course Catalog</span>
                </div>
            </a>
            <a href="?tab=progress" class="pb-3 px-2 border-b-2 transition-colors whitespace-nowrap <?php echo $activeTab == 'progress' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-600 hover:text-gray-900'; ?>">
                <div class="flex items-center gap-2">
                    <i class="fas fa-chart-line"></i>
                    <span>My Progress</span>
                </div>
            </a>
            <a href="?tab=certificates" class="pb-3 px-2 border-b-2 transition-colors whitespace-nowrap <?php echo $activeTab == 'certificates' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-600 hover:text-gray-900'; ?>">
                <div class="flex items-center gap-2">
                    <i class="fas fa-award"></i>
                    <span>Certificates & Badges</span>
                </div>
            </a>
            <a href="?tab=examinations" class="pb-3 px-2 border-b-2 transition-colors whitespace-nowrap <?php echo $activeTab == 'examinations' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-600 hover:text-gray-900'; ?>">
                <div class="flex items-center gap-2">
                    <i class="fas fa-file-alt"></i>
                    <span>Examinations</span>
                </div>
            </a>
        </div>
    </div>

    <!-- Course Catalog Tab -->
    <?php if ($activeTab == 'catalog'): ?>
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-bold text-gray-900">Available Courses</h2>
            </div>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <?php foreach ($allCourses as $course): ?>
                    <div class="border border-gray-200 rounded-lg p-6">
                        <div class="flex items-start justify-between mb-3">
                            <div class="flex-1">
                                <h3 class="text-lg font-semibold text-gray-900 mb-2"><?php echo htmlspecialchars($course['title']); ?></h3>
                                <p class="text-gray-600 text-sm mb-3"><?php echo htmlspecialchars($course['description']); ?></p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 mb-4 text-sm text-gray-600">
                            <span class="px-2 py-1 bg-purple-100 text-purple-700 rounded text-sm">
                                <?php echo htmlspecialchars($course['category']); ?>
                            </span>
                            <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded text-sm">
                                <?php echo htmlspecialchars($course['level']); ?>
                            </span>
                        </div>
                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div>
                                <p class="text-gray-600 text-sm mb-1">Duration</p>
                                <p class="text-gray-900"><?php echo htmlspecialchars($course['duration']); ?></p>
                            </div>
                            <div>
                                <p class="text-gray-600 text-sm mb-1">Modules</p>
                                <p class="text-gray-900"><?php echo $course['modules_count']; ?></p>
                            </div>
                            <div>
                                <p class="text-gray-600 text-sm mb-1">Instructor</p>
                                <p class="text-gray-900 text-sm"><?php echo htmlspecialchars($course['instructor']); ?></p>
                            </div>
                            <div>
                                <div class="flex items-center gap-1">
                                    <i class="fas fa-star text-yellow-500"></i>
                                    <span class="text-gray-900"><?php echo $course['rating']; ?></span>
                                    <span class="text-gray-600 text-sm">(<?php echo $course['reviews_count']; ?>)</span>
                                </div>
                            </div>
                        </div>
                        <?php if ($course['enrollment_status']): ?>
                            <div class="mb-4">
                                <div class="flex items-center justify-between mb-2">
                                    <p class="text-gray-700 text-sm">Your Progress</p>
                                    <span class="text-gray-900 text-sm"><?php echo $course['progress'] ?? 0; ?>%</span>
                                </div>
                                <div class="bg-gray-200 rounded-full h-2">
                                    <div class="bg-green-500 h-2 rounded-full course-progress-bar" style="width: 0%" data-target="<?php echo $course['progress'] ?? 0; ?>"></div>
                                </div>
                            </div>
                        <?php endif; ?>
                        <button onclick="handleCourseAction(<?php echo $course['id']; ?>, <?php echo $course['enrollment_status'] ? 'true' : 'false'; ?>)" class="w-full flex items-center justify-center gap-2 px-4 py-2 rounded-lg <?php echo $course['enrollment_status'] ? 'bg-indigo-600 text-white hover:bg-indigo-700' : 'border border-indigo-600 text-indigo-600 hover:bg-indigo-50'; ?>">
                            <?php if ($course['enrollment_status']): ?>
                                <i class="fas fa-play"></i>
                                <span>Continue Learning</span>
                            <?php else: ?>
                                <span>Enroll Now</span>
                            <?php endif; ?>
                        </button>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Progress Tab -->
    <?php if ($activeTab == 'progress'): ?>
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-bold text-gray-900 mb-6">My Learning Progress</h2>
            <div class="space-y-4">
                <?php foreach ($progress as $prog): ?>
                    <div class="border border-gray-200 rounded-lg p-6">
                        <div class="flex items-start justify-between mb-4">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 mb-2"><?php echo htmlspecialchars($prog['course_name']); ?></h3>
                                <p class="text-gray-600 text-sm">
                                    Last accessed: <?php echo formatDate($prog['last_accessed']); ?>
                                </p>
                            </div>
                            <span class="px-3 py-1 rounded-full text-sm <?php echo getStatusBadge($prog['status']); ?>">
                                <?php echo htmlspecialchars($prog['status']); ?>
                            </span>
                        </div>
                        <div class="grid grid-cols-3 gap-4 mb-4">
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <p class="text-gray-600 text-sm mb-1">Modules Completed</p>
                                <p class="text-gray-900"><?php echo $prog['completed_modules']; ?>/<?php echo $prog['total_modules']; ?></p>
                            </div>
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <p class="text-gray-600 text-sm mb-1">Time Spent</p>
                                <p class="text-gray-900"><?php echo htmlspecialchars($prog['time_spent'] ?? 'N/A'); ?></p>
                            </div>
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <p class="text-gray-600 text-sm mb-1">Progress</p>
                                <p class="text-gray-900"><?php echo $prog['progress']; ?>%</p>
                            </div>
                        </div>
                        <div class="mb-4">
                            <div class="bg-gray-200 rounded-full h-3">
                                <div class="h-3 rounded-full learning-progress-bar <?php echo $prog['progress'] == 100 ? 'bg-green-500' : 'bg-indigo-500'; ?>" style="width: 0%" data-target="<?php echo $prog['progress']; ?>"></div>
                            </div>
                        </div>
                        <?php if ($prog['status'] != 'Completed'): ?>
                            <button class="flex items-center gap-2 text-indigo-600 hover:text-indigo-700">
                                <i class="fas fa-play"></i>
                                <span>Continue Course</span>
                            </button>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Certificates & Badges Tab -->
    <?php if ($activeTab == 'certificates'): ?>
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-xl font-bold text-gray-900 mb-6">My Certificates</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <?php foreach ($myCertificates as $cert): ?>
                    <div class="border-2 border-indigo-200 rounded-lg p-6 bg-gradient-to-br from-indigo-50 to-white">
                        <div class="flex items-start justify-between mb-4">
                            <i class="fas fa-award text-indigo-600 text-3xl"></i>
                            <span class="text-indigo-600 font-semibold">Score: <?php echo $cert['score']; ?>%</span>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2"><?php echo htmlspecialchars($cert['certificate_name']); ?></h3>
                        <p class="text-gray-600 text-sm mb-1">Instructor: <?php echo htmlspecialchars($cert['instructor']); ?></p>
                        <p class="text-gray-600 text-sm mb-4">
                            Issued: <?php echo formatDate($cert['issue_date']); ?>
                        </p>
                        <p class="text-gray-500 text-sm mb-4">ID: <?php echo htmlspecialchars($cert['certificate_id']); ?></p>
                        <button onclick="downloadCertificate('<?php echo htmlspecialchars($cert['certificate_id']); ?>')" class="w-full px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                            Download Certificate
                        </button>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-bold text-gray-900 mb-6">Achievement Badges</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <?php foreach ($myBadges as $badge): ?>
                    <div class="border border-gray-200 rounded-lg p-6 text-center hover:border-indigo-300 transition-colors">
                        <div class="text-5xl mb-3"><?php echo htmlspecialchars($badge['icon']); ?></div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2"><?php echo htmlspecialchars($badge['badge_name']); ?></h3>
                        <p class="text-gray-600 text-sm mb-3"><?php echo htmlspecialchars($badge['description']); ?></p>
                        <p class="text-gray-500 text-sm">
                            Earned: <?php echo formatDate($badge['earned_date']); ?>
                        </p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Examinations Tab -->
    <?php if ($activeTab == 'examinations'): ?>
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-bold text-gray-900 mb-6">Course Examinations</h2>
            <div class="space-y-4">
                <?php foreach ($myExams as $exam): ?>
                    <div class="border border-gray-200 rounded-lg p-6">
                        <div class="flex items-start justify-between mb-4">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 mb-2"><?php echo htmlspecialchars($exam['course_name']); ?></h3>
                                <p class="text-gray-600 text-sm">
                                    Exam Date: <?php echo formatDate($exam['exam_date']); ?>
                                </p>
                            </div>
                            <span class="px-3 py-1 rounded-full text-sm <?php echo getStatusBadge($exam['status']); ?>">
                                <?php echo htmlspecialchars($exam['status']); ?>
                            </span>
                        </div>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                            <div class="bg-gray-50 p-3 rounded-lg">
                                <p class="text-gray-600 text-sm mb-1">Duration</p>
                                <p class="text-gray-900"><?php echo htmlspecialchars($exam['duration']); ?></p>
                            </div>
                            <div class="bg-gray-50 p-3 rounded-lg">
                                <p class="text-gray-600 text-sm mb-1">Passing Score</p>
                                <p class="text-gray-900"><?php echo $exam['passing_score']; ?>%</p>
                            </div>
                            <?php if ($exam['status'] == 'Passed' && $exam['score']): ?>
                                <div class="bg-green-50 p-3 rounded-lg">
                                    <p class="text-gray-600 text-sm mb-1">Your Score</p>
                                    <p class="text-green-700 font-semibold"><?php echo $exam['score']; ?>%</p>
                                </div>
                            <?php endif; ?>
                            <?php if ($exam['status'] == 'Scheduled'): ?>
                                <div class="bg-gray-50 p-3 rounded-lg">
                                    <p class="text-gray-600 text-sm mb-1">Attempts Allowed</p>
                                    <p class="text-gray-900"><?php echo $exam['attempts_allowed']; ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php if ($exam['status'] == 'Scheduled'): ?>
                            <button onclick="startExamination(<?php echo $exam['id']; ?>)" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                                Start Examination
                            </button>
                        <?php endif; ?>
                        <?php if ($exam['status'] == 'Passed'): ?>
                            <div class="flex items-center gap-2 text-green-600">
                                <i class="fas fa-check-circle"></i>
                                <span>Examination passed successfully!</span>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Course Details Modal -->
<div id="courseDetailsModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-xl max-w-3xl w-full mx-4 max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b border-gray-200 flex items-center justify-between">
            <h3 class="text-xl font-bold text-gray-900" id="courseModalTitle">Course Details</h3>
            <button onclick="closeCourseDetailsModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <div class="p-6" id="courseDetailsContent">
            <!-- Content will be populated by JavaScript -->
        </div>
        <div class="p-6 border-t border-gray-200 flex justify-end">
            <button onclick="closeCourseDetailsModal()" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                Close
            </button>
        </div>
    </div>
</div>

<script>
// Animate progress bars on page load
document.addEventListener('DOMContentLoaded', function() {
    // Animate course progress bars
    const courseBars = document.querySelectorAll('.course-progress-bar');
    courseBars.forEach(bar => {
        const target = parseInt(bar.getAttribute('data-target')) || 0;
        animateProgressBar(bar, target);
    });

    // Animate learning progress bars
    const learningBars = document.querySelectorAll('.learning-progress-bar');
    learningBars.forEach(bar => {
        const target = parseInt(bar.getAttribute('data-target')) || 0;
        animateProgressBar(bar, target);
    });

    // Add hover effects to course cards
    const courseCards = document.querySelectorAll('.border.border-gray-200.rounded-lg');
    courseCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-2px)';
            this.style.transition = 'transform 0.2s ease, box-shadow 0.2s ease';
            this.style.boxShadow = '0 4px 6px rgba(0, 0, 0, 0.1)';
        });
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
            this.style.boxShadow = '';
        });
    });

    // Add hover effects to badge cards
    const badgeCards = document.querySelectorAll('.border.border-gray-200.rounded-lg.text-center');
    badgeCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'scale(1.05)';
            this.style.transition = 'transform 0.2s ease';
        });
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'scale(1)';
        });
    });
});

// Animate Progress Bar
function animateProgressBar(element, target) {
    if (!element) return;
    let current = 0;
    const increment = target / 50;
    const timer = setInterval(() => {
        current += increment;
        if (current >= target) {
            element.style.width = target + '%';
            clearInterval(timer);
        } else {
            element.style.width = current + '%';
        }
    }, 30);
}

// Handle Course Action (Enroll or Continue)
function handleCourseAction(courseId, isEnrolled) {
    if (isEnrolled) {
        // Continue learning - redirect to course
        window.location.href = `learning.php?tab=catalog&course_id=${courseId}`;
    } else {
        // Enroll in course
        if (confirm('Are you sure you want to enroll in this course?')) {
            // You can add AJAX call here or form submission
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '';
            
            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = 'enroll';
            form.appendChild(actionInput);
            
            const courseInput = document.createElement('input');
            courseInput.type = 'hidden';
            courseInput.name = 'course_id';
            courseInput.value = courseId;
            form.appendChild(courseInput);
            
            document.body.appendChild(form);
            form.submit();
        }
    }
}

// Download Certificate
function downloadCertificate(certificateId) {
    // You can implement certificate download logic here
    window.location.href = `learning.php?action=download_certificate&cert_id=${certificateId}`;
}

// Start Examination
function startExamination(examId) {
    if (confirm('Are you ready to start the examination? You will not be able to pause once started.')) {
        window.location.href = `learning.php?tab=examinations&exam_id=${examId}&start=1`;
    }
}

// Course Details Modal (for future use)
function showCourseDetailsModal(courseData) {
    const modal = document.getElementById('courseDetailsModal');
    const title = document.getElementById('courseModalTitle');
    const content = document.getElementById('courseDetailsContent');
    
    title.textContent = courseData.title;
    content.innerHTML = `
        <div class="space-y-4">
            <p class="text-gray-600">${courseData.description}</p>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <p class="text-gray-600 text-sm">Duration</p>
                    <p class="text-gray-900">${courseData.duration}</p>
                </div>
                <div>
                    <p class="text-gray-600 text-sm">Modules</p>
                    <p class="text-gray-900">${courseData.modules_count}</p>
                </div>
            </div>
        </div>
    `;
    
    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeCourseDetailsModal() {
    const modal = document.getElementById('courseDetailsModal');
    modal.classList.add('hidden');
    document.body.style.overflow = 'auto';
}

// Close modal when clicking outside
document.addEventListener('click', function(event) {
    const modal = document.getElementById('courseDetailsModal');
    if (event.target === modal) {
        closeCourseDetailsModal();
    }
});

// Close modal with Escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeCourseDetailsModal();
    }
});
</script>

<?php
$content = ob_get_clean();
require_once 'includes/layout.php';
require_once 'includes/footer.php';
?>
