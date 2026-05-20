<?php
/**
 * Configuration file – central settings, data handling, and helper functions.
 */

session_start();

// ==================== PATH CONSTANTS ====================
define('DATA_FILE', __DIR__ . '/data/site.json');
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('ADMIN_PASSWORD', '4562526'); // plain text password
define('ADMIN_EMAIL', 'thanevora86@gmail.com');

// ==================== SMTP SETTINGS (Gmail App Password) ====================
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_USER', 'thanevora86@gmail.com');
define('SMTP_PASS', 'vqpw ciwq bqjr sljt');
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls');

// ==================== CREATE NECESSARY DIRECTORIES ====================
$directories = [
    __DIR__ . '/data',
    UPLOAD_DIR,
    UPLOAD_DIR . 'slides',
    UPLOAD_DIR . 'projects',
    UPLOAD_DIR . 'journey',
    UPLOAD_DIR . 'icons'  // folder for social icons
];
foreach ($directories as $dir) {
    if (!is_dir($dir)) mkdir($dir, 0777, true);
}

// ==================== DEFAULT DATA (if JSON does not exist) ====================
if (!file_exists(DATA_FILE)) {
    $default = [
        'owner' => [
            'name'     => 'Than Evora',
            'title'    => 'Business Analyst & Data Scientist',
            'tagline'  => 'Turning data into insights that drive business success.',
            'location' => 'Payatas, Quezon, Philippines',
            'email'    => 'thanevora86@gmail.com',
            'phone'    => '(+63) 9270726974',
            'cv_url'   => '',
            'socials'  => [
                'github'    => 'https://github.com/',
                'jobstreet' => 'https://jobstreet.com/',
                'facebook'  => 'https://facebook.com/',
                'instagram' => 'https://instagram.com/'
            ],
            'social_icons' => [
                'github'    => '',
                'jobstreet' => '',
                'facebook'  => '',
                'instagram' => ''
            ]
        ],
        'footer' => [
            'copyright' => '© 2025 Than Evora. All rights reserved.',
            'credit'    => 'Built with PHP & Tailwind'
        ],
        'hero_slides' => [[
            'images'   => [],
            'title'    => 'Hi, I\'m Than Evora',
            'subtitle' => 'I transform data into actionable insights',
            'link'     => '#about'
        ]],
        'about' => [
            'bio'   => "Motivated IT professional with experience in leadership, public speaking, programming, and system development.",
            'stats' => [
                ['label' => 'Projects Delivered', 'value' => '15+'],
                ['label' => 'Years Experience',   'value' => '3+'],
                ['label' => 'Happy Clients',      'value' => '10+']
            ]
        ],
        'projects' => [[
            'title'       => 'E-Commerce Platform',
            'description' => 'Full-featured online store with cart, payments, and admin dashboard.',
            'tags'        => ['PHP', 'Laravel', 'MySQL', 'Tailwind'],
            'image'       => '',
            'url'         => '#',
            'featured'    => true
        ]],
        'skills' => [
            "PHP / Laravel", "JavaScript (ES6+)", "Vue.js / React",
            "Tailwind CSS", "MySQL / PostgreSQL", "Git / Docker"
        ],
        'experience' => [],
        'journey' => [[
            'year'        => '1st Year',
            'description' => 'Introduction to programming fundamentals, algorithms, and logic.',
            'images'      => []
        ]],
        'contact' => [
            'heading'    => "Let's Work Together",
            'subheading' => "I'm always open to discussing new projects and opportunities.",
            'extra_text' => "Feel free to reach out anytime!"
        ],
        'nav_items' => [
            ['id' => 'home',     'label' => 'Home',      'icon' => 'home'],
            ['id' => 'about',    'label' => 'About',     'icon' => 'user'],
            ['id' => 'projects', 'label' => 'Projects',  'icon' => 'folder-git-2'],
            ['id' => 'skills',   'label' => 'Skills',    'icon' => 'zap'],
            // ['id' => 'experience','label' => 'Experience','icon' => 'briefcase'], // commented out
            ['id' => 'journey',  'label' => 'College',   'icon' => 'graduation-cap'],
            ['id' => 'contact',  'label' => 'Contact',   'icon' => 'mail']
        ]
    ];
    file_put_contents(DATA_FILE, json_encode($default, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

// ==================== CORE DATA FUNCTIONS ====================
function loadData() {
    $data = json_decode(file_get_contents(DATA_FILE), true);
    
    // Migrate old hero_slides format (single image → array of images)
    if (isset($data['hero_slides'][0]) && !isset($data['hero_slides'][0]['images'])) {
        foreach ($data['hero_slides'] as &$slide) {
            $oldImg = $slide['image'] ?? '';
            $slide['images'] = $oldImg ? [$oldImg] : [];
            unset($slide['image']);
        }
    }
    
    // Migrate old skills format (object with level) to simple array
    if (isset($data['skills'][0]['name'])) {
        $data['skills'] = array_column($data['skills'], 'name');
    }
    
    // Ensure contact section exists
    if (!isset($data['contact'])) {
        $data['contact'] = [
            'heading'    => "Let's Work Together",
            'subheading' => "I'm always open to discussing new projects.",
            'extra_text' => ""
        ];
    }
    
    // Ensure social_icons array exists (migration)
    if (!isset($data['owner']['social_icons'])) {
        $data['owner']['social_icons'] = [
            'github'    => '',
            'jobstreet' => '',
            'facebook'  => '',
            'instagram' => ''
        ];
    }
    
    // Ensure all social links exist
    $defaultSocials = ['github', 'jobstreet', 'facebook', 'instagram'];
    foreach ($defaultSocials as $platform) {
        if (!isset($data['owner']['socials'][$platform])) {
            $data['owner']['socials'][$platform] = '';
        }
    }
    
    return $data;
}

function saveData($data) {
    if (isset($data['skills'])) {
        $data['skills'] = array_values(array_filter($data['skills']));
    }
    file_put_contents(DATA_FILE, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

// ==================== FILE UPLOAD / DELETE HELPERS ====================
function uploadFile($file, $subfolder = '') {
    $targetDir = UPLOAD_DIR . $subfolder;
    if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
    
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp', 'pdf', 'doc', 'docx'];
    if (!in_array($ext, $allowed)) return false;
    
    $filename = uniqid() . '.' . $ext;
    $destination = $targetDir . $filename;
    return move_uploaded_file($file['tmp_name'], $destination) ? 'uploads/' . $subfolder . $filename : false;
}

function deleteFile($filepath) {
    $fullPath = __DIR__ . '/' . ltrim($filepath, '/');
    if (file_exists($fullPath) && is_file($fullPath)) unlink($fullPath);
}

// ==================== PREVENT DIRECT ACCESS ====================
if (basename($_SERVER['SCRIPT_FILENAME']) === basename(__FILE__)) {
    header('Location: index.php');
    exit;
}
?>