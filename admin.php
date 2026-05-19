<?php
require_once 'config.php';

// ==================== AUTHENTICATION CHECK ====================
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

$data = loadData();
$message = '';

// ==================== HELPER FUNCTIONS FOR FORM FIELDS ====================
function textField($label, $name, $value = '', $type = 'text') {
    return "<div>
        <label class='block text-sm font-medium text-gray-700 mb-1'>$label</label>
        <input type='$type' name='$name' value='" . htmlspecialchars($value) . "' class='w-full px-4 py-2 border border-gray-200 rounded-lg'>
    </div>";
}

function textareaField($label, $name, $value = '') {
    return "<div>
        <label class='block text-sm font-medium text-gray-700 mb-1'>$label</label>
        <textarea name='$name' rows='4' class='w-full px-4 py-2 border border-gray-200 rounded-lg'>" . htmlspecialchars($value) . "</textarea>
    </div>";
}

// ==================== PROCESS FORM SUBMISSIONS ====================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ---------- Owner Info (with Facebook) ----------
    if (isset($_POST['update_owner'])) {
        $data['owner']['name']     = $_POST['name'] ?? '';
        $data['owner']['title']    = $_POST['title'] ?? '';
        $data['owner']['tagline']  = $_POST['tagline'] ?? '';
        $data['owner']['location'] = $_POST['location'] ?? '';
        $data['owner']['email']    = $_POST['email'] ?? '';
        $data['owner']['phone']    = $_POST['phone'] ?? '';
        $data['owner']['socials'] = [
            'github'   => $_POST['github'] ?? '',
            'linkedin' => $_POST['linkedin'] ?? '',
            'facebook' => $_POST['facebook'] ?? ''
        ];
        if (!empty($_FILES['cv_file']['name'])) {
            $cvPath = uploadFile($_FILES['cv_file'], '');
            if ($cvPath) {
                if (!empty($data['owner']['cv_url'])) deleteFile($data['owner']['cv_url']);
                $data['owner']['cv_url'] = $cvPath;
            }
        }
        $message = 'Owner information updated.';
    }
    // ---------- Footer ----------
    elseif (isset($_POST['update_footer'])) {
        $data['footer']['copyright'] = $_POST['copyright'] ?? '';
        $data['footer']['credit']    = $_POST['credit'] ?? '';
        $message = 'Footer updated.';
    }
    // ---------- Hero Slides (multi‑image) ----------
    elseif (isset($_POST['update_slides'])) {
        $newSlides = [];
        $titles    = $_POST['slide_title'] ?? [];
        $subtitles = $_POST['slide_subtitle'] ?? [];
        $links     = $_POST['slide_link'] ?? [];
        $existingImagesJson = $_POST['existing_slide_images'] ?? [];

        foreach ($titles as $i => $title) {
            $images = [];
            // Handle newly uploaded images for this slide
            if (!empty($_FILES['slide_images']['name'][$i])) {
                foreach ($_FILES['slide_images']['name'][$i] as $k => $imgName) {
                    if ($_FILES['slide_images']['error'][$i][$k] === UPLOAD_ERR_OK) {
                        $uploaded = uploadFile([
                            'name'     => $imgName,
                            'tmp_name' => $_FILES['slide_images']['tmp_name'][$i][$k],
                            'error'    => 0
                        ], 'slides/');
                        if ($uploaded) $images[] = $uploaded;
                    }
                }
            }
            // Merge existing images
            if (!empty($existingImagesJson[$i])) {
                $existing = json_decode($existingImagesJson[$i], true);
                if (is_array($existing)) $images = array_merge($images, $existing);
            }
            $newSlides[] = [
                'images'   => $images,
                'title'    => $title,
                'subtitle' => $subtitles[$i] ?? '',
                'link'     => $links[$i] ?? ''
            ];
        }
        $data['hero_slides'] = $newSlides;
        $message = 'Hero slides updated.';
    }
    // ---------- About Section ----------
    elseif (isset($_POST['update_about'])) {
        $data['about']['bio'] = $_POST['bio'] ?? '';
        $data['about']['stats'] = [];
        $labels = $_POST['stat_label'] ?? [];
        $values = $_POST['stat_value'] ?? [];
        foreach ($labels as $i => $label) {
            if (!empty($label)) {
                $data['about']['stats'][] = ['label' => $label, 'value' => $values[$i] ?? ''];
            }
        }
        $message = 'About section updated.';
    }
    // ---------- Projects ----------
    elseif (isset($_POST['update_projects'])) {
        $projects = [];
        $titles    = $_POST['proj_title'] ?? [];
        $descs     = $_POST['proj_desc'] ?? [];
        $urls      = $_POST['proj_url'] ?? [];
        $tagsRaw   = $_POST['proj_tags'] ?? [];
        $existing  = $_POST['existing_proj_image'] ?? [];

        foreach ($titles as $i => $title) {
            if (empty($title)) continue;
            $img = '';
            if (!empty($_FILES['proj_image']['name'][$i])) {
                $img = uploadFile([
                    'name'     => $_FILES['proj_image']['name'][$i],
                    'tmp_name' => $_FILES['proj_image']['tmp_name'][$i],
                    'error'    => 0
                ], 'projects/');
            }
            if (!$img && !empty($existing[$i])) $img = $existing[$i];
            $tags = array_filter(array_map('trim', explode(',', $tagsRaw[$i] ?? '')));
            $projects[] = [
                'title'       => $title,
                'description' => $descs[$i] ?? '',
                'url'         => $urls[$i] ?? '',
                'tags'        => $tags,
                'image'       => $img,
                'featured'    => false
            ];
        }
        $data['projects'] = $projects;
        $message = 'Projects updated.';
    }
    // ---------- Skills (no levels) ----------
    elseif (isset($_POST['update_skills'])) {
        $skillNames = $_POST['skill_name'] ?? [];
        $skillNames = array_filter(array_map('trim', $skillNames));
        $data['skills'] = array_values($skillNames);
        $message = 'Skills updated.';
    }
    // ---------- Experience (calendar dropdowns) ----------
    elseif (isset($_POST['update_experience'])) {
        $experienceData = [];
        $roles      = $_POST['exp_role'] ?? [];
        $companies  = $_POST['exp_company'] ?? [];
        $startMonths = $_POST['exp_start_month'] ?? [];
        $startYears  = $_POST['exp_start_year'] ?? [];
        $endMonths   = $_POST['exp_end_month'] ?? [];
        $endYears    = $_POST['exp_end_year'] ?? [];
        $presentFlags = $_POST['exp_present'] ?? [];
        $descs       = $_POST['exp_desc'] ?? [];

        foreach ($roles as $i => $role) {
            if (empty($role)) continue;
            $startStr = $startMonths[$i] . ' ' . $startYears[$i];
            $endStr = (!empty($presentFlags[$i]) && $presentFlags[$i] == 'on') ? 'Present' : ($endMonths[$i] . ' ' . $endYears[$i]);
            $period = $startStr . ' - ' . $endStr;
            $experienceData[] = [
                'role'    => $role,
                'company' => $companies[$i] ?? '',
                'period'  => $period,
                'desc'    => $descs[$i] ?? ''
            ];
        }
        $data['experience'] = $experienceData;
        $message = 'Experience updated.';
    }
    // ---------- College Journey (fixed multi‑image) ----------
    elseif (isset($_POST['update_journey'])) {
        $journeyData = [];
        $years = $_POST['journey_year'] ?? [];
        $descs = $_POST['journey_desc'] ?? [];
        $existingImgsJson = $_POST['existing_journey_imgs'] ?? [];

        foreach ($years as $i => $year) {
            if (empty($year)) continue;
            $images = [];
            if (!empty($_FILES['journey_images']['name'][$i])) {
                foreach ($_FILES['journey_images']['name'][$i] as $k => $imgName) {
                    if ($_FILES['journey_images']['error'][$i][$k] === UPLOAD_ERR_OK) {
                        $uploaded = uploadFile([
                            'name'     => $imgName,
                            'tmp_name' => $_FILES['journey_images']['tmp_name'][$i][$k],
                            'error'    => 0
                        ], 'journey/');
                        if ($uploaded) $images[] = $uploaded;
                    }
                }
            }
            if (!empty($existingImgsJson[$i])) {
                $existing = json_decode($existingImgsJson[$i], true);
                if (is_array($existing)) $images = array_merge($images, $existing);
            }
            $journeyData[] = [
                'year'        => $year,
                'description' => $descs[$i] ?? '',
                'images'      => $images
            ];
        }
        $data['journey'] = $journeyData;
        $message = 'College journey updated.';
    }
    // ---------- Contact Section (dynamic) ----------
    elseif (isset($_POST['update_contact'])) {
        $data['contact']['heading']    = $_POST['contact_heading'] ?? '';
        $data['contact']['subheading'] = $_POST['contact_subheading'] ?? '';
        $data['contact']['extra_text'] = $_POST['contact_extra'] ?? '';
        $message = 'Contact section updated.';
    }

    saveData($data);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        .tab-btn.active { background: #1e3a8a; color: white; }
        .loading-overlay {
            position: fixed; top:0; left:0; width:100%; height:100%;
            background: rgba(0,0,0,0.5); display: none;
            justify-content: center; align-items: center; z-index: 1000;
        }
        .spinner {
            width: 50px; height: 50px; border: 5px solid #fff;
            border-top: 5px solid #1e3a8a; border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
        @keyframes spin { 0% { transform: rotate(0); } 100% { transform: rotate(360deg); } }
    </style>
</head>
<body class="bg-gray-100">
    <!-- LOADING OVERLAY -->
    <div id="loadingOverlay" class="loading-overlay"><div class="spinner"></div></div>

    <!-- HEADER -->
    <header class="bg-blue-900 text-white shadow-lg sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 py-3 flex justify-between items-center">
            <div class="flex items-center gap-2"><i data-lucide="settings"></i><span class="text-xl font-bold">Portfolio Admin</span></div>
            <div>
                <a href="index.php" target="_blank" class="bg-white/10 px-3 py-1.5 rounded mr-3">View Site</a>
                <a href="admin-login.php?logout=1" class="text-gray-300" onclick="return confirm('Logout?')">Logout</a>
            </div>
        </div>
    </header>

    <!-- MAIN PANEL -->
    <div class="max-w-7xl mx-auto py-8 px-4">
        <?php if ($message): ?>
            <div class="mb-6 bg-green-100 text-green-700 px-4 py-3 rounded-xl"><?= $message ?></div>
        <?php endif; ?>

        <div class="bg-white rounded-xl shadow-sm p-6">
            <!-- TABS -->
            <div class="flex flex-wrap gap-2 border-b pb-4 mb-6" id="tabs">
                <button class="tab-btn active px-4 py-2 rounded-lg text-sm" data-tab="owner">Owner Info</button>
                <button class="tab-btn px-4 py-2 rounded-lg text-sm" data-tab="footer">Footer</button>
                <button class="tab-btn px-4 py-2 rounded-lg text-sm" data-tab="slides">Hero Slides</button>
                <button class="tab-btn px-4 py-2 rounded-lg text-sm" data-tab="about">About</button>
                <button class="tab-btn px-4 py-2 rounded-lg text-sm" data-tab="projects">Projects</button>
                <button class="tab-btn px-4 py-2 rounded-lg text-sm" data-tab="skills">Skills</button>
                <button class="tab-btn px-4 py-2 rounded-lg text-sm" data-tab="experience">Experience</button>
                <button class="tab-btn px-4 py-2 rounded-lg text-sm" data-tab="journey">College Journey</button>
                <button class="tab-btn px-4 py-2 rounded-lg text-sm" data-tab="contact">Contact</button>
            </div>

            <!-- TAB CONTENT: OWNER INFO -->
            <div id="tab-owner" class="tab-content">
                <form method="POST" enctype="multipart/form-data" class="save-form">
                    <input type="hidden" name="update_owner" value="1">
                    <div class="grid md:grid-cols-2 gap-4">
                        <?= textField('Full Name', 'name', $data['owner']['name']) ?>
                        <?= textField('Title', 'title', $data['owner']['title']) ?>
                        <?= textField('Tagline', 'tagline', $data['owner']['tagline']) ?>
                        <?= textField('Location', 'location', $data['owner']['location']) ?>
                        <?= textField('Email', 'email', $data['owner']['email']) ?>
                        <?= textField('Phone', 'phone', $data['owner']['phone']) ?>
                    </div>
                    <div class="grid grid-cols-3 gap-4 mt-4">
                        <?= textField('GitHub URL', 'github', $data['owner']['socials']['github'] ?? '') ?>
                        <?= textField('LinkedIn URL', 'linkedin', $data['owner']['socials']['linkedin'] ?? '') ?>
                        <?= textField('Facebook URL', 'facebook', $data['owner']['socials']['facebook'] ?? '') ?>
                    </div>
                    <div class="mt-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Upload CV (PDF/DOCX)</label>
                        <input type="file" name="cv_file" class="block w-full text-sm">
                        <?php if (!empty($data['owner']['cv_url'])): ?>
                            <p class="text-xs text-gray-500 mt-1">Current: <?= htmlspecialchars($data['owner']['cv_url']) ?></p>
                        <?php endif; ?>
                    </div>
                    <button type="submit" class="mt-6 px-6 py-2.5 bg-blue-900 text-white rounded-lg hover:bg-blue-600">Save Owner Info</button>
                </form>
            </div>

            <!-- TAB CONTENT: FOOTER -->
            <div id="tab-footer" class="tab-content hidden">
                <form method="POST" class="save-form">
                    <input type="hidden" name="update_footer" value="1">
                    <?= textField('Copyright Text', 'copyright', $data['footer']['copyright'] ?? '') ?>
                    <?= textField('Credit Line', 'credit', $data['footer']['credit'] ?? '') ?>
                    <button type="submit" class="mt-6 px-6 py-2.5 bg-blue-900 text-white rounded-lg hover:bg-blue-600">Save Footer</button>
                </form>
            </div>

            <!-- TAB CONTENT: HERO SLIDES (multi‑image) -->
            <div id="tab-slides" class="tab-content hidden">
                <form method="POST" enctype="multipart/form-data" class="save-form">
                    <input type="hidden" name="update_slides" value="1">
                    <div id="slides-array" class="space-y-4">
                        <?php foreach ($data['hero_slides'] as $i => $slide): ?>
                            <div class="slide-entry border-b pb-4 mb-4">
                                <div class="grid gap-3">
                                    <div><label class="text-sm font-medium">Title</label><input name="slide_title[]" value="<?= htmlspecialchars($slide['title']) ?>" class="w-full px-3 py-2 border rounded"></div>
                                    <div><label class="text-sm font-medium">Subtitle</label><input name="slide_subtitle[]" value="<?= htmlspecialchars($slide['subtitle']) ?>" class="w-full px-3 py-2 border rounded"></div>
                                    <div><label class="text-sm font-medium">Link</label><input name="slide_link[]" value="<?= htmlspecialchars($slide['link']) ?>" class="w-full px-3 py-2 border rounded"></div>
                                    <div>
                                        <label class="text-sm font-medium">Images (multiple)</label>
                                        <input type="file" name="slide_images[<?= $i ?>][]" multiple class="block w-full text-sm">
                                        <input type="hidden" name="existing_slide_images[<?= $i ?>]" value='<?= htmlspecialchars(json_encode($slide['images'])) ?>'>
                                        <div class="flex flex-wrap gap-2 mt-2 existing-slide-images">
                                            <?php foreach ($slide['images'] as $img): ?>
                                                <div class="relative"><img src="<?= htmlspecialchars($img) ?>" class="h-16 rounded"><button type="button" onclick="removeSlideImage(this, '<?= htmlspecialchars($img) ?>', <?= $i ?>)" class="absolute -top-2 -right-2 bg-red-600 text-white rounded-full w-5 h-5 text-xs">×</button></div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    <button type="button" onclick="this.closest('.slide-entry').remove()" class="text-red-500 text-sm mt-2">Remove slide</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" onclick="addSlideEntry()" class="mt-2 text-blue-600 text-sm hover:underline">+ Add slide</button>
                    <div class="mt-4"><button type="submit" class="px-6 py-2.5 bg-blue-900 text-white rounded-lg hover:bg-blue-600">Save Slides</button></div>
                </form>
            </div>

            <!-- TAB CONTENT: ABOUT -->
            <div id="tab-about" class="tab-content hidden">
                <form method="POST" class="save-form">
                    <input type="hidden" name="update_about" value="1">
                    <?= textareaField('Bio / Description', 'bio', $data['about']['bio']) ?>
                    <div class="mt-6">
                        <h3 class="text-lg font-semibold text-navy-800 mb-3">Statistics</h3>
                        <div id="stats-array" class="space-y-3">
                            <?php foreach ($data['about']['stats'] as $stat): ?>
                                <div class="flex gap-3 stat-entry items-end">
                                    <div class="flex-1"><input name="stat_label[]" value="<?= htmlspecialchars($stat['label']) ?>" placeholder="Label" class="w-full px-3 py-2 border rounded-lg"></div>
                                    <div class="w-32"><input name="stat_value[]" value="<?= htmlspecialchars($stat['value']) ?>" placeholder="Value" class="w-full px-3 py-2 border rounded-lg"></div>
                                    <button type="button" onclick="this.closest('.stat-entry').remove()" class="text-red-500 text-sm">Remove</button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <button type="button" onclick="addStat()" class="mt-2 text-blue-600 text-sm hover:underline">+ Add stat</button>
                    </div>
                    <button type="submit" class="mt-6 px-6 py-2.5 bg-blue-900 text-white rounded-lg">Save About</button>
                </form>
            </div>

            <!-- TAB CONTENT: PROJECTS -->
            <div id="tab-projects" class="tab-content hidden">
                <form method="POST" enctype="multipart/form-data" class="save-form">
                    <input type="hidden" name="update_projects" value="1">
                    <div id="projects-array" class="space-y-5">
                        <?php foreach ($data['projects'] as $proj): ?>
                            <div class="project-entry border-b pb-4 mb-4">
                                <div class="grid md:grid-cols-2 gap-4">
                                    <div><input name="proj_title[]" value="<?= htmlspecialchars($proj['title']) ?>" placeholder="Project Title" class="w-full px-3 py-2 border rounded-lg"></div>
                                    <div><input name="proj_url[]" value="<?= htmlspecialchars($proj['url']) ?>" placeholder="Project URL" class="w-full px-3 py-2 border rounded-lg"></div>
                                    <div><textarea name="proj_desc[]" rows="2" placeholder="Description" class="w-full px-3 py-2 border rounded-lg"><?= htmlspecialchars($proj['description']) ?></textarea></div>
                                    <div><input name="proj_tags[]" value="<?= htmlspecialchars(implode(',', $proj['tags'])) ?>" placeholder="Tags (comma separated)" class="w-full px-3 py-2 border rounded-lg"></div>
                                    <div>
                                        <input type="file" name="proj_image[]" class="block w-full text-sm">
                                        <input type="hidden" name="existing_proj_image[]" value="<?= htmlspecialchars($proj['image'] ?? '') ?>">
                                        <?php if (!empty($proj['image'])): ?><img src="<?= htmlspecialchars($proj['image']) ?>" class="h-12 mt-1 rounded"><?php endif; ?>
                                    </div>
                                    <div class="flex items-end"><button type="button" onclick="this.closest('.project-entry').remove()" class="text-red-500 text-sm">Remove project</button></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" onclick="addProjectEntry()" class="mt-2 text-blue-600 text-sm hover:underline">+ Add project</button>
                    <div class="mt-4"><button type="submit" class="px-6 py-2.5 bg-blue-900 text-white rounded-lg">Save Projects</button></div>
                </form>
            </div>

            <!-- TAB CONTENT: SKILLS (no levels) -->
            <div id="tab-skills" class="tab-content hidden">
                <form method="POST" class="save-form">
                    <input type="hidden" name="update_skills" value="1">
                    <div id="skills-array" class="space-y-3">
                        <?php foreach ($data['skills'] as $skill): ?>
                            <div class="skill-entry flex gap-3 items-end">
                                <div class="flex-1"><input name="skill_name[]" value="<?= htmlspecialchars($skill) ?>" placeholder="Skill name" class="w-full px-3 py-2 border rounded-lg"></div>
                                <button type="button" onclick="this.closest('.skill-entry').remove()" class="text-red-500 text-sm">Remove</button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" onclick="addSkillEntry()" class="mt-2 text-blue-600 text-sm hover:underline">+ Add skill</button>
                    <div class="mt-4"><button type="submit" class="px-6 py-2.5 bg-blue-900 text-white rounded-lg">Save Skills</button></div>
                </form>
            </div>

            <!-- TAB CONTENT: EXPERIENCE (calendar dropdowns) -->
            <div id="tab-experience" class="tab-content hidden">
                <form method="POST" class="save-form">
                    <input type="hidden" name="update_experience" value="1">
                    <div id="experience-array" class="space-y-5">
                        <?php foreach ($data['experience'] as $exp):
                            $parts = explode(' - ', $exp['period']);
                            $startRaw = $parts[0] ?? '';
                            $endRaw = $parts[1] ?? 'Present';
                            preg_match('/(\w+)\s+(\d{4})/', $startRaw, $sm);
                            $startMonth = $sm[1] ?? 'Jan';
                            $startYear  = $sm[2] ?? date('Y');
                            $isPresent = ($endRaw === 'Present');
                            if (!$isPresent) {
                                preg_match('/(\w+)\s+(\d{4})/', $endRaw, $em);
                                $endMonth = $em[1] ?? 'Dec';
                                $endYear  = $em[2] ?? date('Y');
                            } else {
                                $endMonth = $endYear = '';
                            }
                        ?>
                            <div class="experience-entry border-b pb-4 mb-4">
                                <div class="grid md:grid-cols-2 gap-4">
                                    <div><label class="text-sm font-medium">Role</label><input name="exp_role[]" value="<?= htmlspecialchars($exp['role']) ?>" class="w-full px-3 py-2 border rounded-lg"></div>
                                    <div><label class="text-sm font-medium">Company</label><input name="exp_company[]" value="<?= htmlspecialchars($exp['company']) ?>" class="w-full px-3 py-2 border rounded-lg"></div>
                                    <div>
                                        <label class="text-sm font-medium">Start Date</label>
                                        <div class="flex gap-2">
                                            <select name="exp_start_month[]" class="px-3 py-2 border rounded-lg">
                                                <?php foreach (['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'] as $m): ?>
                                                    <option <?= $m == $startMonth ? 'selected' : '' ?>><?= $m ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <select name="exp_start_year[]" class="px-3 py-2 border rounded-lg">
                                                <?php for ($y = date('Y')-10; $y <= date('Y')+5; $y++): ?>
                                                    <option <?= $y == $startYear ? 'selected' : '' ?>><?= $y ?></option>
                                                <?php endfor; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="text-sm font-medium">End Date</label>
                                        <div class="flex gap-2">
                                            <select name="exp_end_month[]" class="px-3 py-2 border rounded-lg" <?= $isPresent ? 'disabled' : '' ?>>
                                                <?php foreach (['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'] as $m): ?>
                                                    <option <?= $m == $endMonth ? 'selected' : '' ?>><?= $m ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <select name="exp_end_year[]" class="px-3 py-2 border rounded-lg" <?= $isPresent ? 'disabled' : '' ?>>
                                                <?php for ($y = date('Y')-10; $y <= date('Y')+5; $y++): ?>
                                                    <option <?= $y == $endYear ? 'selected' : '' ?>><?= $y ?></option>
                                                <?php endfor; ?>
                                            </select>
                                        </div>
                                        <label class="inline-flex items-center mt-1"><input type="checkbox" name="exp_present[]" class="mr-1 present-check" <?= $isPresent ? 'checked' : '' ?>> Currently working here</label>
                                    </div>
                                    <div><label class="text-sm font-medium">Description</label><textarea name="exp_desc[]" rows="2" class="w-full px-3 py-2 border rounded-lg"><?= htmlspecialchars($exp['desc']) ?></textarea></div>
                                    <div class="flex items-end"><button type="button" onclick="this.closest('.experience-entry').remove()" class="text-red-500 text-sm">Remove</button></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" onclick="addExperienceEntry()" class="mt-2 text-blue-600 text-sm hover:underline">+ Add experience</button>
                    <div class="mt-4"><button type="submit" class="px-6 py-2.5 bg-blue-900 text-white rounded-lg">Save Experience</button></div>
                </form>
            </div>

            <!-- TAB CONTENT: COLLEGE JOURNEY (enhanced) -->
            <div id="tab-journey" class="tab-content hidden">
                <form method="POST" enctype="multipart/form-data" class="save-form">
                    <input type="hidden" name="update_journey" value="1">
                    <div id="journey-array" class="space-y-6">
                        <?php foreach ($data['journey'] as $i => $yearData): ?>
                            <div class="journey-entry border-b pb-4 mb-4">
                                <div class="grid md:grid-cols-2 gap-4">
                                    <div><label class="text-sm font-medium">Year</label><input name="journey_year[]" value="<?= htmlspecialchars($yearData['year']) ?>" class="w-full px-3 py-2 border rounded-lg"></div>
                                    <div><label class="text-sm font-medium">Description</label><textarea name="journey_desc[]" rows="2" class="w-full px-3 py-2 border rounded-lg"><?= htmlspecialchars($yearData['description']) ?></textarea></div>
                                </div>
                                <div class="mt-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Images (multiple allowed)</label>
                                    <input type="file" name="journey_images[<?= $i ?>][]" multiple class="block w-full text-sm">
                                    <input type="hidden" name="existing_journey_imgs[<?= $i ?>]" value='<?= htmlspecialchars(json_encode($yearData['images'])) ?>'>
                                    <div class="flex flex-wrap gap-3 mt-3 existing-journey-images">
                                        <?php foreach ($yearData['images'] as $img): ?>
                                            <div class="relative"><img src="<?= htmlspecialchars($img) ?>" class="w-20 h-20 object-cover rounded shadow"><button type="button" onclick="removeJourneyImage(this, '<?= htmlspecialchars($img) ?>', <?= $i ?>)" class="absolute -top-2 -right-2 bg-red-600 text-white rounded-full w-5 h-5 text-xs">×</button></div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <button type="button" onclick="this.closest('.journey-entry').remove()" class="text-red-500 text-sm mt-2">Remove year</button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" onclick="addJourneyEntry()" class="mt-2 text-blue-600 text-sm hover:underline">+ Add year</button>
                    <div class="mt-4"><button type="submit" class="px-6 py-2.5 bg-blue-900 text-white rounded-lg">Save Journey</button></div>
                </form>
            </div>

            <!-- TAB CONTENT: CONTACT (dynamic) -->
            <div id="tab-contact" class="tab-content hidden">
                <form method="POST" class="save-form">
                    <input type="hidden" name="update_contact" value="1">
                    <?= textField('Heading', 'contact_heading', $data['contact']['heading']) ?>
                    <?= textareaField('Subheading text', 'contact_subheading', $data['contact']['subheading']) ?>
                    <?= textareaField('Extra note', 'contact_extra', $data['contact']['extra_text']) ?>
                    <button type="submit" class="mt-6 px-6 py-2.5 bg-blue-900 text-white rounded-lg">Save Contact</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        lucide.createIcons();

        // Show loading overlay on form submit
        document.querySelectorAll('.save-form').forEach(form => {
            form.addEventListener('submit', () => document.getElementById('loadingOverlay').style.display = 'flex');
        });

        // Tabs switching
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
                document.querySelectorAll('.tab-content').forEach(c => c.classList.add('hidden'));
                btn.classList.add('active');
                document.getElementById('tab-' + btn.dataset.tab).classList.remove('hidden');
            });
        });

        // ==================== DYNAMIC ADD FUNCTIONS ====================
        function addSlideEntry() {
            let idx = document.querySelectorAll('.slide-entry').length;
            let div = document.createElement('div');
            div.className = 'slide-entry border-b pb-4 mb-4';
            div.innerHTML = `
                <div class="grid gap-3">
                    <div><label class="text-sm font-medium">Title</label><input name="slide_title[]" class="w-full px-3 py-2 border rounded"></div>
                    <div><label class="text-sm font-medium">Subtitle</label><input name="slide_subtitle[]" class="w-full px-3 py-2 border rounded"></div>
                    <div><label class="text-sm font-medium">Link</label><input name="slide_link[]" class="w-full px-3 py-2 border rounded"></div>
                    <div>
                        <label class="text-sm font-medium">Images (multiple)</label>
                        <input type="file" name="slide_images[${idx}][]" multiple class="block w-full text-sm">
                        <input type="hidden" name="existing_slide_images[${idx}]" value="[]">
                        <div class="existing-slide-images flex flex-wrap gap-2 mt-2"></div>
                    </div>
                    <button type="button" onclick="this.closest('.slide-entry').remove()" class="text-red-500 text-sm mt-2">Remove slide</button>
                </div>
            `;
            document.getElementById('slides-array').appendChild(div);
        }

        function addStat() {
            let div = document.createElement('div');
            div.className = 'flex gap-3 stat-entry items-end';
            div.innerHTML = `
                <div class="flex-1"><input name="stat_label[]" placeholder="Label" class="w-full px-3 py-2 border rounded-lg"></div>
                <div class="w-32"><input name="stat_value[]" placeholder="Value" class="w-full px-3 py-2 border rounded-lg"></div>
                <button type="button" onclick="this.closest('.stat-entry').remove()" class="text-red-500 text-sm">Remove</button>
            `;
            document.getElementById('stats-array').appendChild(div);
        }

        function addProjectEntry() {
            let div = document.createElement('div');
            div.className = 'project-entry border-b pb-4 mb-4';
            div.innerHTML = `
                <div class="grid md:grid-cols-2 gap-4">
                    <div><input name="proj_title[]" placeholder="Title" class="w-full px-3 py-2 border rounded-lg"></div>
                    <div><input name="proj_url[]" placeholder="URL" class="w-full px-3 py-2 border rounded-lg"></div>
                    <div><textarea name="proj_desc[]" rows="2" placeholder="Description" class="w-full px-3 py-2 border rounded-lg"></textarea></div>
                    <div><input name="proj_tags[]" placeholder="Tags" class="w-full px-3 py-2 border rounded-lg"></div>
                    <div><input type="file" name="proj_image[]"><input type="hidden" name="existing_proj_image[]"></div>
                    <div><button type="button" onclick="this.closest('.project-entry').remove()" class="text-red-500 text-sm">Remove project</button></div>
                </div>
            `;
            document.getElementById('projects-array').appendChild(div);
        }

        function addSkillEntry() {
            let div = document.createElement('div');
            div.className = 'skill-entry flex gap-3 items-end';
            div.innerHTML = `
                <div class="flex-1"><input name="skill_name[]" placeholder="Skill name" class="w-full px-3 py-2 border rounded-lg"></div>
                <button type="button" onclick="this.closest('.skill-entry').remove()" class="text-red-500 text-sm">Remove</button>
            `;
            document.getElementById('skills-array').appendChild(div);
        }

        function addExperienceEntry() {
            let div = document.createElement('div');
            div.className = 'experience-entry border-b pb-4 mb-4';
            div.innerHTML = `
                <div class="grid md:grid-cols-2 gap-4">
                    <div><label class="text-sm font-medium">Role</label><input name="exp_role[]" class="w-full px-3 py-2 border rounded-lg"></div>
                    <div><label class="text-sm font-medium">Company</label><input name="exp_company[]" class="w-full px-3 py-2 border rounded-lg"></div>
                    <div><label class="text-sm font-medium">Start Date</label><div class="flex gap-2"><select name="exp_start_month[]" class="px-3 py-2 border rounded-lg"><?php foreach(['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'] as $m) echo "<option>$m</option>"; ?></select><select name="exp_start_year[]" class="px-3 py-2 border rounded-lg"><?php for($y=date('Y')-10;$y<=date('Y')+5;$y++) echo "<option>$y</option>"; ?></select></div></div>
                    <div><label class="text-sm font-medium">End Date</label><div class="flex gap-2"><select name="exp_end_month[]" class="px-3 py-2 border rounded-lg"><?php foreach(['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'] as $m) echo "<option>$m</option>"; ?></select><select name="exp_end_year[]" class="px-3 py-2 border rounded-lg"><?php for($y=date('Y')-10;$y<=date('Y')+5;$y++) echo "<option>$y</option>"; ?></select></div><label class="inline-flex items-center mt-1"><input type="checkbox" name="exp_present[]" class="mr-1 present-check"> Currently working here</label></div>
                    <div><label class="text-sm font-medium">Description</label><textarea name="exp_desc[]" rows="2" class="w-full px-3 py-2 border rounded-lg"></textarea></div>
                    <div><button type="button" onclick="this.closest('.experience-entry').remove()" class="text-red-500 text-sm">Remove</button></div>
                </div>
            `;
            document.getElementById('experience-array').appendChild(div);
            attachPresentToggle(div);
        }

        function attachPresentToggle(entry) {
            let cb = entry.querySelector('.present-check');
            let endMonth = entry.querySelector('select[name="exp_end_month[]"]');
            let endYear = entry.querySelector('select[name="exp_end_year[]"]');
            if (cb) {
                cb.addEventListener('change', function() {
                    let disabled = this.checked;
                    if (endMonth) endMonth.disabled = disabled;
                    if (endYear) endYear.disabled = disabled;
                    if (disabled) {
                        if (endMonth) endMonth.value = '';
                        if (endYear) endYear.value = '';
                    }
                });
            }
        }

        document.querySelectorAll('.experience-entry .present-check').forEach(cb => {
            attachPresentToggle(cb.closest('.experience-entry'));
        });

        function addJourneyEntry() {
            let idx = document.querySelectorAll('.journey-entry').length;
            let div = document.createElement('div');
            div.className = 'journey-entry border-b pb-4 mb-4';
            div.innerHTML = `
                <div class="grid md:grid-cols-2 gap-4">
                    <div><label class="text-sm font-medium">Year</label><input name="journey_year[]" class="w-full px-3 py-2 border rounded-lg"></div>
                    <div><label class="text-sm font-medium">Description</label><textarea name="journey_desc[]" rows="2" class="w-full px-3 py-2 border rounded-lg"></textarea></div>
                </div>
                <div class="mt-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Images (multiple allowed)</label>
                    <input type="file" name="journey_images[${idx}][]" multiple class="block w-full text-sm">
                    <input type="hidden" name="existing_journey_imgs[${idx}]" value="[]">
                    <div class="existing-journey-images flex flex-wrap gap-3 mt-3"></div>
                </div>
                <button type="button" onclick="this.closest('.journey-entry').remove()" class="text-red-500 text-sm mt-2">Remove year</button>
            `;
            document.getElementById('journey-array').appendChild(div);
        }

        // Image removal helpers
        function removeSlideImage(btn, imgPath, slideIdx) {
            if (confirm('Delete this image?')) {
                btn.parentElement.remove();
                let container = btn.closest('.slide-entry');
                let hidden = container.querySelector('input[name="existing_slide_images[' + slideIdx + ']"]');
                let remaining = [];
                container.querySelectorAll('.existing-slide-images .relative img').forEach(img => remaining.push(img.src));
                hidden.value = JSON.stringify(remaining);
            }
        }

        function removeJourneyImage(btn, imgPath, yearIdx) {
            if (confirm('Delete this image?')) {
                btn.parentElement.remove();
                let container = btn.closest('.journey-entry');
                let hidden = container.querySelector('input[name="existing_journey_imgs[' + yearIdx + ']"]');
                let remaining = [];
                container.querySelectorAll('.existing-journey-images .relative img').forEach(img => remaining.push(img.src));
                hidden.value = JSON.stringify(remaining);
            }
        }
    </script>
</body>
</html>