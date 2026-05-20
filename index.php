<?php
/**
 * Front‑end portfolio – displays all content, handles contact form with PHPMailer,
 * and provides admin login modal with two‑factor authentication.
 */

require_once 'config.php';
require_once 'PHPMailer/PHPMailerAutoload.php';

$data = loadData();
extract($data);

$formSent = false;
$emailError = '';
$showSuccess = false;

// Check for successful send after redirect
if (isset($_GET['sent']) && $_GET['sent'] == 1) {
    $showSuccess = true;
}

// ==================== PROCESS CONTACT FORM (standard POST) ====================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['contact_submit'])) {
    $name    = trim(strip_tags($_POST['name'] ?? ''));
    $email   = trim(strip_tags($_POST['email'] ?? ''));
    $message = trim(strip_tags($_POST['message'] ?? ''));
    
    if ($name && $email && filter_var($email, FILTER_VALIDATE_EMAIL) && $message) {
        // --- Admin email (modern professional template) ---
        $mailAdmin = new PHPMailer;
        $mailAdmin->isSMTP();
        $mailAdmin->Host = SMTP_HOST;
        $mailAdmin->SMTPAuth = true;
        $mailAdmin->Username = SMTP_USER;
        $mailAdmin->Password = SMTP_PASS;
        $mailAdmin->SMTPSecure = SMTP_SECURE;
        $mailAdmin->Port = SMTP_PORT;
        $mailAdmin->setFrom(ADMIN_EMAIL, 'Portfolio Contact');
        $mailAdmin->addAddress(ADMIN_EMAIL);
        $mailAdmin->Subject = 'New Contact Message from ' . $name;
        $mailAdmin->isHTML(true);
        $mailAdmin->Body = '
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>New Contact Message</title>
                <style>
                    body { font-family: "Segoe UI", Arial, sans-serif; background-color: #f4f7fc; margin: 0; padding: 20px; }
                    .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
                    .header { background: linear-gradient(135deg, #1e3a8a, #1e40af); padding: 30px; text-align: center; color: white; }
                    .header h1 { margin: 0; font-size: 24px; }
                    .content { padding: 30px; }
                    .field { margin-bottom: 20px; }
                    .field-label { font-weight: 600; color: #1e3a8a; display: inline-block; width: 100px; }
                    .field-value { color: #333; }
                    .message-box { background: #f8fafc; padding: 15px; border-radius: 12px; margin-top: 10px; border-left: 4px solid #1e3a8a; }
                    .footer { background: #eef2ff; padding: 15px; text-align: center; font-size: 12px; color: #4b5563; }
                </style>
            </head>
            <body>
                <div class="container">
                    <div class="header"><h1>📬 New Contact Form Submission</h1></div>
                    <div class="content">
                        <div class="field"><span class="field-label">Name:</span> <span class="field-value">' . htmlspecialchars($name) . '</span></div>
                        <div class="field"><span class="field-label">Email:</span> <span class="field-value">' . htmlspecialchars($email) . '</span></div>
                        <div class="message-box"><strong>Message:</strong><br>' . nl2br(htmlspecialchars($message)) . '</div>
                    </div>
                    <div class="footer">Sent via your portfolio contact form</div>
                </div>
            </body>
            </html>
        ';
        $adminMailSent = $mailAdmin->send();
        
        // --- Auto‑reply to sender (modern professional template) ---
        $mailReply = new PHPMailer;
        $mailReply->isSMTP();
        $mailReply->Host = SMTP_HOST;
        $mailReply->SMTPAuth = true;
        $mailReply->Username = SMTP_USER;
        $mailReply->Password = SMTP_PASS;
        $mailReply->SMTPSecure = SMTP_SECURE;
        $mailReply->Port = SMTP_PORT;
        $mailReply->setFrom(ADMIN_EMAIL, 'Than Evora Portfolio');
        $mailReply->addAddress($email);
        $mailReply->Subject = 'We received your message';
        $mailReply->isHTML(true);
        $mailReply->Body = '
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Thank you for reaching out</title>
                <style>
                    body { font-family: "Segoe UI", Arial, sans-serif; background-color: #f4f7fc; margin: 0; padding: 20px; }
                    .container { max-width: 500px; margin: 0 auto; background: white; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
                    .header { background: linear-gradient(135deg, #1e3a8a, #1e40af); padding: 30px; text-align: center; color: white; }
                    .content { padding: 30px; text-align: center; }
                    .content p { color: #333; line-height: 1.6; }
                    .btn { display: inline-block; background: #1e3a8a; color: white; padding: 10px 20px; border-radius: 30px; text-decoration: none; margin-top: 20px; }
                    .footer { background: #eef2ff; padding: 15px; text-align: center; font-size: 12px; color: #4b5563; }
                </style>
            </head>
            <body>
                <div class="container">
                    <div class="header"><h1>✨ Thank You, ' . htmlspecialchars($name) . '!</h1></div>
                    <div class="content">
                        <p>We have received your message and will get back to you as soon as possible (usually within 24 hours).</p>
                        <p>In the meantime, feel free to explore more of my work on my portfolio.</p>
                        <a href="' . (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . '" class="btn text-white">Visit Portfolio</a>
                    </div>
                    <div class="footer">© ' . date('Y') . ' Than Evora – Business Analyst & Data Scientist</div>
                </div>
            </body>
            </html>
        ';
        $replySent = $mailReply->send();
        
        if ($adminMailSent && $replySent) {
            header('Location: index.php?sent=1#contact');
            exit;
        } else {
            $emailError = 'There was a problem sending your message. Please try again later.';
        }
    } else {
        $emailError = 'Please fill in all fields correctly.';
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($owner['name']) ?> — Portfolio</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        navy: { 50:'#eef2ff',100:'#e0e7ff',200:'#c7d2fe',300:'#a5b4fc',400:'#818cf8',500:'#1e3a8a',600:'#1e40af',700:'#1d4ed8',800:'#1e3a8a',900:'#0f172a' }
                    },
                    fontFamily: { sans: ['Inter', 'system-ui', 'sans-serif'] }
                }
            }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        .fade-section { opacity: 0; transform: translateY(30px); transition: opacity 0.8s cubic-bezier(0.2,0.9,0.4,1), transform 0.6s ease; }
        .fade-section.revealed { opacity: 1; transform: translateY(0); }
        .hero-slide { transition: opacity 1s ease; }
        .hero-image { transition: opacity 0.8s ease; }
        #loader { position:fixed; top:0; left:0; width:100%; height:100%; background:white; display:flex; align-items:center; justify-content:center; z-index:9999; }
        .loader-spinner { width:50px; height:50px; border:4px solid #e2e8f0; border-top:4px solid #1e3a8a; border-radius:50%; animation:spin 0.8s linear infinite; }
        @keyframes spin { 0%{transform:rotate(0deg)} 100%{transform:rotate(360deg)} }
        .lightbox { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.9); z-index:10000; justify-content:center; align-items:center; flex-direction:column; }
        .lightbox.active { display:flex; }
        .lightbox img { max-width:90%; max-height:80%; border-radius:8px; }
        .close-lightbox { position:absolute; top:20px; right:30px; font-size:40px; color:white; cursor:pointer; }
        .lightbox-nav { margin-top:20px; display:flex; gap:20px; }
        .lightbox-nav button { background:rgba(255,255,255,0.2); color:white; padding:10px 20px; border-radius:8px; cursor:pointer; }
        /* Animated Modal */
        .modal { display: none; position: fixed; top:0; left:0; width:100%; height:100%; background: rgba(0,0,0,0.6); z-index:10001; align-items: center; justify-content: center; }
        .modal.active { display: flex; }
        .modal-content { background: white; padding: 2rem; border-radius: 1.5rem; max-width: 400px; width: 90%; transform: scale(0.9); opacity: 0; transition: transform 0.2s ease-out, opacity 0.2s ease-out; }
        .modal.active .modal-content { transform: scale(1); opacity: 1; }
        /* Loading spinner for buttons */
        .btn-loading { opacity: 0.7; pointer-events: none; position: relative; }
        .btn-loading::after { content: ''; position: absolute; width: 16px; height: 16px; top: 50%; right: 12px; margin-top: -8px; border: 2px solid #fff; border-top-color: transparent; border-radius: 50%; animation: spin 0.6s linear infinite; }
    </style>
</head>
<body class="bg-white text-black font-sans antialiased">
    <!-- LOADING ANIMATION -->
    <div id="loader"><div class="loader-spinner"></div></div>

    <!-- LIGHTBOX -->
    <div id="lightbox" class="lightbox">
        <span class="close-lightbox">&times;</span>
        <img id="lightbox-img" alt="">
        <div class="lightbox-nav"><button id="prev-img">❮ Prev</button><button id="next-img">Next ❯</button></div>
    </div>

    <!-- ADMIN LOGIN MODAL (2FA, animated, with resend) -->
    <div id="adminModal" class="modal">
        <div class="modal-content">
            <div class="text-center mb-4">
                <i data-lucide="lock" class="w-12 h-12 mx-auto text-navy-800"></i>
                <h2 id="modalTitle" class="text-xl font-bold mt-2">Admin Login</h2>
            </div>
            <div id="modalError" class="text-red-600 text-sm mb-3 hidden"></div>
            <div id="passwordStep">
                <input type="password" id="adminPassword" placeholder="Password" class="w-full px-4 py-2 border rounded-lg mb-2">
                <label class="inline-flex items-center text-sm text-gray-600 mb-3">
                    <input type="checkbox" id="showPasswordCheckbox" class="mr-2"> Show Password
                </label>
                <button id="submitPasswordBtn" class="w-full bg-navy-800 text-white py-2 rounded-lg hover:bg-navy-900">Continue</button>
            </div>
            <div id="otpStep" style="display:none;">
                <p class="text-sm text-gray-600 mb-2">A 6-digit code has been sent to the admin email. Enter it below.</p>
                <input type="text" id="otpCode" placeholder="6-digit code" class="w-full px-4 py-2 border rounded-lg mb-2" maxlength="6">
                <button id="verifyOtpBtn" class="w-full bg-navy-800 text-white py-2 rounded-lg hover:bg-navy-900">Verify OTP</button>
                <button id="resendOtpBtn" class="w-full mt-2 text-blue-600 text-sm hover:underline">Resend OTP</button>
            </div>
            <button id="closeModalBtn" class="w-full mt-2 text-gray-500 text-sm">Cancel</button>
        </div>
    </div>

    <!-- MAIN CONTENT -->
    <div class="min-h-screen bg-white">
        <!-- STICKY NAVBAR -->
        <header class="sticky top-0 z-30 bg-white/95 backdrop-blur-sm border-b border-navy-100 shadow-sm">
            <div class="flex items-center justify-between px-6 lg:px-8 py-3">
                <a href="#" class="text-xl font-bold text-navy-800"><?= htmlspecialchars($owner['name']) ?></a>
                <div class="hidden md:flex items-center gap-3">
                    <nav class="flex gap-1">
                        <?php foreach ($nav_items as $item): ?>
                            <a href="#<?= $item['id'] ?>" class="top-nav-link px-4 py-2 text-sm font-medium text-navy-700 rounded-lg hover:bg-navy-50" data-section="<?= $item['id'] ?>"><?= $item['label'] ?></a>
                        <?php endforeach; ?>
                    </nav>
                    <?php if (!empty($owner['cv_url'])): ?>
                        <a href="<?= htmlspecialchars($owner['cv_url']) ?>" download class="inline-flex gap-2 px-4 py-2 bg-gradient-to-r from-navy-700 to-navy-800 text-white text-sm font-medium rounded-lg shadow-md"><i data-lucide="download" class="w-4 h-4"></i> CV</a>
                    <?php endif; ?>
                    <a href="#contact" class="inline-flex gap-2 px-4 py-2 border border-navy-300 text-navy-800 text-sm font-medium rounded-lg hover:bg-navy-50"><i data-lucide="send" class="w-4 h-4"></i> Hire Me</a>
                    <button id="adminModalBtn" class="inline-flex gap-2 px-4 py-2 bg-navy-900/10 text-navy-800 text-sm font-medium rounded-lg hover:bg-navy-900/20"><i data-lucide="lock" class="w-4 h-4"></i> Admin</button>
                </div>
                <button id="mobile-menu-btn" class="md:hidden p-2 rounded-lg hover:bg-navy-50"><i data-lucide="menu" class="w-6 h-6"></i></button>
            </div>
            <div id="mobile-menu" class="md:hidden hidden border-t border-navy-100 bg-white px-4 py-3 space-y-2">
                <?php foreach ($nav_items as $item): ?>
                    <a href="#<?= $item['id'] ?>" class="mobile-nav-link block px-3 py-2 text-sm font-medium text-navy-700 rounded-lg hover:bg-navy-50" data-section="<?= $item['id'] ?>"><?= $item['label'] ?></a>
                <?php endforeach; ?>
                <?php if (!empty($owner['cv_url'])): ?>
                    <a href="<?= htmlspecialchars($owner['cv_url']) ?>" download class="flex items-center gap-2 px-3 py-2 text-sm"><i data-lucide="download"></i> CV</a>
                <?php endif; ?>
                <a href="#contact" class="flex items-center gap-2 px-3 py-2 text-sm"><i data-lucide="send"></i> Hire Me</a>
                <button id="mobileAdminBtn" class="flex items-center gap-2 px-3 py-2 text-sm w-full text-left"><i data-lucide="lock"></i> Admin Login</button>
            </div>
        </header>

        <main>
            <!-- HERO SLIDER -->
            <section id="home" class="section-anchor min-h-[90vh] flex items-center justify-center relative overflow-hidden">
                <div class="absolute inset-0 z-0">
                    <?php foreach ($hero_slides as $slideIdx => $slide): ?>
                        <div class="hero-slide absolute inset-0 transition-opacity duration-700 <?= $slideIdx === 0 ? 'opacity-100' : 'opacity-0' ?>" data-slide="<?= $slideIdx ?>">
                            <?php $images = $slide['images'] ?? []; ?>
                            <?php if (!empty($images)): ?>
                                <div class="hero-image-wrapper w-full h-full relative">
                                    <?php foreach ($images as $imgIdx => $img): ?>
                                        <div class="hero-image absolute inset-0 bg-cover bg-center transition-opacity duration-1000" style="background-image: url('<?= htmlspecialchars($img) ?>'); opacity: <?= $imgIdx === 0 ? '1' : '0' ?>;"></div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="absolute inset-0 bg-navy-800"></div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                    <div class="absolute inset-0 bg-gradient-to-r from-navy-900/95 to-navy-800/80"></div>
                </div>
                <div class="relative z-10 text-center text-white max-w-4xl px-4 mx-auto">
                    <h1 class="text-4xl md:text-6xl font-extrabold mb-4"><?= htmlspecialchars($hero_slides[0]['title'] ?? 'Hi, I\'m ' . $owner['name']) ?></h1>
                    <p class="text-xl md:text-2xl text-white/80 mb-8"><?= htmlspecialchars($hero_slides[0]['subtitle'] ?? $owner['tagline']) ?></p>
                    <div class="flex flex-wrap gap-4 justify-center">
                        <a href="#projects" class="px-6 py-3 bg-white text-navy-900 font-semibold rounded-xl">View My Work</a>
                        <a href="#contact" class="px-6 py-3 border-2 border-white text-white font-semibold rounded-xl hover:bg-white/10">Get In Touch</a>
                    </div>
                </div>
                <?php if (count($hero_slides) > 1): ?>
                    <button id="prev-slide" class="absolute left-4 top-1/2 z-20 -translate-y-1/2 bg-white/20 hover:bg-white/40 text-white p-2 rounded-full"><i data-lucide="chevron-left"></i></button>
                    <button id="next-slide" class="absolute right-4 top-1/2 z-20 -translate-y-1/2 bg-white/20 hover:bg-white/40 text-white p-2 rounded-full"><i data-lucide="chevron-right"></i></button>
                <?php endif; ?>
            </section>

            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <!-- ABOUT SECTION -->
                <section id="about" class="section-anchor py-16 lg:py-20 fade-section">
                    <div class="mb-10 text-center lg:text-left">
                        <span class="text-navy-700 font-semibold text-sm uppercase">About Me</span>
                        <h2 class="text-3xl lg:text-4xl font-bold text-navy-900 mt-2">Get to know me</h2>
                    </div>
                    <div class="grid lg:grid-cols-3 gap-10">
                        <div class="lg:col-span-2 space-y-5 text-black leading-relaxed text-lg">
                            <?= nl2br(htmlspecialchars($about['bio'])) ?>
                            <?php if (!empty($about['stats'])): ?>
                                <div class="grid grid-cols-2 sm:grid-cols-3 gap-5 pt-4">
                                    <?php foreach ($about['stats'] as $stat): ?>
                                        <div class="bg-white rounded-xl p-5 shadow-sm border text-center">
                                            <p class="text-3xl font-bold text-navy-900"><?= htmlspecialchars($stat['value']) ?></p>
                                            <p class="text-sm text-black mt-1"><?= htmlspecialchars($stat['label']) ?></p>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="bg-white rounded-2xl p-6 shadow-md border h-fit space-y-4">
                            <h3 class="font-semibold text-navy-900 text-lg">Quick Info</h3>
                            <div class="space-y-3 text-sm text-black">
                                <div class="flex items-center gap-3"><i data-lucide="map-pin"></i><span><?= htmlspecialchars($owner['location']) ?></span></div>
                                <div class="flex items-center gap-3"><i data-lucide="mail"></i><span><?= htmlspecialchars($owner['email']) ?></span></div>
                                <div class="flex items-center gap-3"><i data-lucide="phone"></i><span><?= htmlspecialchars($owner['phone']) ?></span></div>
                            </div>
                            <?php if (!empty($owner['cv_url'])): ?>
                                <a href="<?= htmlspecialchars($owner['cv_url']) ?>" download class="w-full inline-flex justify-center gap-2 mt-3 px-4 py-2.5 bg-gradient-to-r from-navy-700 to-navy-800 text-white text-sm font-medium rounded-lg">Download CV</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </section>

                <!-- PROJECTS -->
                <section id="projects" class="section-anchor py-16 lg:py-20 fade-section">
                    <div class="mb-10 text-center lg:text-left"><span class="text-navy-700 text-sm uppercase">Portfolio</span><h2 class="text-3xl lg:text-4xl font-bold mt-2">Featured Projects</h2></div>
                    <div class="grid sm:grid-cols-2 gap-6">
                        <?php foreach ($projects as $project): ?>
                            <div class="group bg-white rounded-2xl overflow-hidden shadow-sm border hover:shadow-lg">
                                <img src="<?= htmlspecialchars($project['image']) ?>" class="h-48 w-full object-cover" onerror="this.src='https://placehold.co/600x400?text=Project'">
                                <div class="p-6">
                                    <h3 class="font-bold text-lg"><?= htmlspecialchars($project['title']) ?></h3>
                                    <p class="text-black text-sm mt-2"><?= htmlspecialchars($project['description']) ?></p>
                                    <div class="flex flex-wrap gap-2 mt-4">
                                        <?php foreach ($project['tags'] as $tag): ?>
                                            <span class="px-2.5 py-1 bg-navy-50 text-navy-700 text-xs rounded-md"><?= htmlspecialchars($tag) ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                    <?php if (!empty($project['url'])): ?>
                                        <a href="<?= htmlspecialchars($project['url']) ?>" class="inline-flex items-center gap-1 mt-5 text-navy-700 text-sm hover:underline">View Project <i data-lucide="arrow-up-right"></i></a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>

                <!-- SKILLS -->
                <section id="skills" class="section-anchor py-16 lg:py-20 fade-section">
                    <div class="mb-10 text-center lg:text-left"><span class="text-navy-700 text-sm uppercase">Skills</span><h2 class="text-3xl lg:text-4xl font-bold mt-2">Technologies I Use</h2></div>
                    <div class="flex flex-wrap gap-3"><?php foreach ($skills as $skill): ?><span class="px-4 py-2 bg-navy-50 text-navy-800 rounded-full text-sm font-medium border shadow-sm"><?= htmlspecialchars($skill) ?></span><?php endforeach; ?></div>
                </section>

                <!-- EXPERIENCE -->
                <section id="experience" class="section-anchor py-16 lg:py-20 fade-section">
                    <div class="mb-10 text-center lg:text-left"><span class="text-navy-700 text-sm uppercase">Experience</span><h2 class="text-3xl lg:text-4xl font-bold mt-2">Work History</h2></div>
                    <div class="space-y-0 relative before:absolute before:left-5 before:top-0 before:bottom-0 before:w-px before:bg-navy-200">
                        <?php $first = true; foreach ($experience as $exp): ?>
                            <div class="relative pl-14 pb-10 last:pb-0">
                                <div class="absolute left-3.5 top-1 w-3.5 h-3.5 rounded-full border-2 <?= $first ? 'border-navy-700 bg-navy-700 shadow-lg' : 'border-navy-300 bg-white' ?>"></div>
                                <div class="bg-white rounded-xl p-6 shadow-sm border">
                                    <span class="text-xs font-semibold text-navy-700 uppercase"><?= htmlspecialchars($exp['period']) ?></span>
                                    <h3 class="text-lg font-bold mt-1"><?= htmlspecialchars($exp['role']) ?></h3>
                                    <p class="text-navy-600 text-sm"><?= htmlspecialchars($exp['company']) ?></p>
                                    <p class="text-black text-sm mt-2"><?= htmlspecialchars($exp['desc']) ?></p>
                                </div>
                            </div>
                            <?php $first = false; ?>
                        <?php endforeach; ?>
                    </div>
                </section>

                <!-- COLLEGE JOURNEY -->
                <section id="journey" class="section-anchor py-16 lg:py-20 fade-section">
                    <div class="mb-10 text-center lg:text-left"><span class="text-navy-700 text-sm uppercase">Education</span><h2 class="text-3xl lg:text-4xl font-bold mt-2">My College Journey</h2></div>
                    <div class="space-y-12">
                        <?php foreach ($journey as $yearIndex => $yearData): ?>
                            <div>
                                <h3 class="text-2xl font-bold border-l-4 border-navy-700 pl-4 mb-2"><?= htmlspecialchars($yearData['year']) ?> Year</h3>
                                <p class="text-black mb-4 leading-relaxed"><?= nl2br(htmlspecialchars($yearData['description'])) ?></p>
                                <?php if (!empty($yearData['images'])): ?>
                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-5">
                                        <?php foreach ($yearData['images'] as $imgIdx => $img): ?>
                                            <img src="<?= htmlspecialchars($img) ?>" class="w-full h-40 object-cover rounded-xl shadow-md cursor-pointer hover:scale-105 transition-transform journey-img" data-year="<?= $yearIndex ?>" data-img-index="<?= $imgIdx ?>" data-full="<?= htmlspecialchars($img) ?>" alt="Journey image">
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>

                <!-- CONTACT (with custom icons or fallback) -->
                <section id="contact" class="section-anchor py-16 lg:py-20 fade-section">
                    <div class="mb-10 text-center lg:text-left">
                        <span class="text-navy-700 text-sm uppercase">Contact</span>
                        <h2 class="text-3xl lg:text-4xl font-bold mt-2"><?= htmlspecialchars($contact['heading']) ?></h2>
                    </div>
                    <div class="grid lg:grid-cols-5 gap-10">
                        <div class="lg:col-span-3">
                            <?php if ($showSuccess): ?>
                                <div class="mb-6 p-4 rounded-xl bg-green-50 text-green-800 border text-sm">Thank you! Your message has been sent. We will reply soon.</div>
                            <?php elseif ($emailError): ?>
                                <div class="mb-6 p-4 rounded-xl bg-red-50 text-red-800 border text-sm"><?= $emailError ?></div>
                            <?php endif; ?>
                            <form method="POST" action="#contact" class="space-y-5 bg-white rounded-2xl p-6 shadow-sm border">
                                <div class="grid sm:grid-cols-2 gap-5">
                                    <input type="text" name="name" required placeholder="Full Name" class="w-full px-4 py-3 border rounded-xl">
                                    <input type="email" name="email" required placeholder="Email Address" class="w-full px-4 py-3 border rounded-xl">
                                </div>
                                <textarea name="message" rows="5" required placeholder="Message..." class="w-full px-4 py-3 border rounded-xl resize-none"></textarea>
                                <button type="submit" name="contact_submit" class="inline-flex gap-2 px-8 py-3.5 bg-gradient-to-r from-navy-700 to-navy-800 text-white font-semibold rounded-xl shadow-lg"><i data-lucide="send"></i> Send Message</button>
                            </form>
                        </div>
                        
<!-- Contact Sidebar with Custom Icons - Larger corners, no margins -->
<div class="lg:col-span-2">
    <div class="bg-navy-800 rounded-3xl p-6 text-white shadow-xl">
        <h3 class="font-bold text-xl">Get in touch</h3>
        <p class="text-white/70 text-sm mt-1"><?= htmlspecialchars($contact['subheading']) ?></p>
        
        <!-- Contact details with icons -->
        <div class="space-y-4 text-sm mt-6">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-lg bg-white/10 flex items-center justify-center"><i data-lucide="mail" class="w-6 h-6"></i></div>
                <span><?= htmlspecialchars($owner['email']) ?></span>
            </div>
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-lg bg-white/10 flex items-center justify-center"><i data-lucide="phone" class="w-6 h-6"></i></div>
                <span><?= htmlspecialchars($owner['phone']) ?></span>
            </div>
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-lg bg-white/10 flex items-center justify-center"><i data-lucide="map-pin" class="w-6 h-6"></i></div>
                <span><?= htmlspecialchars($owner['location']) ?></span>
            </div>
        </div>

        <?php if (!empty($contact['extra_text'])): ?>
            <p class="text-white/60 text-xs italic mt-4"><?= htmlspecialchars($contact['extra_text']) ?></p>
        <?php endif; ?>

        <!-- Social links with custom icons (rounded corners) -->
        <div class="space-y-3 pt-4">
            <?php if (!empty($owner['socials']['github'])): ?>
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-lg bg-white/10 flex items-center justify-center">
                        <?php if (!empty($owner['social_icons']['github'])): ?>
                            <img src="<?= htmlspecialchars($owner['social_icons']['github']) ?>" class="w-8 h-8 object-contain rounded-lg">
                        <?php else: ?>
                            <i data-lucide="github" class="w-6 h-6"></i>
                        <?php endif; ?>
                    </div>
                    <a href="<?= htmlspecialchars($owner['socials']['github']) ?>" target="_blank" class="text-white/80 hover:text-white text-sm break-all"><?= htmlspecialchars($owner['socials']['github']) ?></a>
                </div>
            <?php endif; ?>
            <?php if (!empty($owner['socials']['jobstreet'])): ?>
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-lg bg-white/10 flex items-center justify-center">
                        <?php if (!empty($owner['social_icons']['jobstreet'])): ?>
                            <img src="<?= htmlspecialchars($owner['social_icons']['jobstreet']) ?>" class="w-8 h-8 object-contain rounded-lg">
                        <?php else: ?>
                            <i data-lucide="briefcase" class="w-6 h-6"></i>
                        <?php endif; ?>
                    </div>
                    <a href="<?= htmlspecialchars($owner['socials']['jobstreet']) ?>" target="_blank" class="text-white/80 hover:text-white text-sm break-all"><?= htmlspecialchars($owner['socials']['jobstreet']) ?></a>
                </div>
            <?php endif; ?>
            <?php if (!empty($owner['socials']['facebook'])): ?>
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-lg bg-white/10 flex items-center justify-center">
                        <?php if (!empty($owner['social_icons']['facebook'])): ?>
                            <img src="<?= htmlspecialchars($owner['social_icons']['facebook']) ?>" class="w-8 h-8 object-contain rounded-lg">
                        <?php else: ?>
                            <i data-lucide="facebook" class="w-6 h-6"></i>
                        <?php endif; ?>
                    </div>
                    <a href="<?= htmlspecialchars($owner['socials']['facebook']) ?>" target="_blank" class="text-white/80 hover:text-white text-sm break-all"><?= htmlspecialchars($owner['socials']['facebook']) ?></a>
                </div>
            <?php endif; ?>
            <?php if (!empty($owner['socials']['instagram'])): ?>
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-lg bg-white/10 flex items-center justify-center">
                        <?php if (!empty($owner['social_icons']['instagram'])): ?>
                            <img src="<?= htmlspecialchars($owner['social_icons']['instagram']) ?>" class="w-8 h-8 object-contain rounded-lg">
                        <?php else: ?>
                            <i data-lucide="instagram" class="w-6 h-6"></i>
                        <?php endif; ?>
                    </div>
                    <a href="<?= htmlspecialchars($owner['socials']['instagram']) ?>" target="_blank" class="text-white/80 hover:text-white text-sm break-all"><?= htmlspecialchars($owner['socials']['instagram']) ?></a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
                    </div>
                </section>
            </div>
        </main>

        <footer class="py-8 border-t mt-8 text-center text-sm">
            <div class="max-w-6xl mx-auto px-4">
                <p class="text-navy-600"><?= htmlspecialchars($footer['copyright'] ?? '© ' . date('Y') . ' ' . $owner['name']) ?></p>
                <?php if (!empty($footer['credit'])): ?>
                    <p class="text-navy-400 text-xs mt-1"><?= htmlspecialchars($footer['credit']) ?></p>
                <?php endif; ?>
            </div>
        </footer>
    </div>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        lucide.createIcons();

        // Hide loader
        window.addEventListener('load', () => {
            let loader = document.getElementById('loader');
            loader.style.opacity = '0';
            setTimeout(() => loader.style.display = 'none', 500);
        });

        // Hero slider
        let currentSlide = 0;
        let slides = document.querySelectorAll('.hero-slide');
        function showSlide(idx) {
            slides.forEach((s, i) => s.classList[i === idx ? 'remove' : 'add']('opacity-0'));
            currentSlide = idx;
        }
        if (slides.length > 1) {
            document.getElementById('next-slide')?.addEventListener('click', () => showSlide((currentSlide + 1) % slides.length));
            document.getElementById('prev-slide')?.addEventListener('click', () => showSlide((currentSlide - 1 + slides.length) % slides.length));
            setInterval(() => showSlide((currentSlide + 1) % slides.length), 8000);
        }
        slides.forEach((slide, idx) => {
            let imgs = slide.querySelectorAll('.hero-image');
            if (imgs.length > 1) {
                let imgIdx = 0;
                setInterval(() => {
                    if (currentSlide !== idx) return;
                    imgIdx = (imgIdx + 1) % imgs.length;
                    imgs.forEach((img, i) => img.style.opacity = i === imgIdx ? '1' : '0');
                }, 4000);
            }
        });

        // Active nav + fade sections
        const sections = document.querySelectorAll('.section-anchor');
        const navLinks = document.querySelectorAll('.top-nav-link, .mobile-nav-link');
        function updateActive() {
            let cur = 'home', sp = window.scrollY + 120;
            sections.forEach(sec => { let t = sec.offsetTop, h = sec.offsetHeight; if (sp >= t && sp < t + h) cur = sec.id; });
            navLinks.forEach(link => {
                if (link.dataset.section === cur) {
                    link.classList.add('bg-navy-50', 'text-navy-900');
                    link.classList.remove('text-navy-700');
                } else {
                    link.classList.remove('bg-navy-50', 'text-navy-900');
                    link.classList.add('text-navy-700');
                }
            });
        }
        window.addEventListener('scroll', updateActive);
        updateActive();

        const fadeObs = new IntersectionObserver((entries) => entries.forEach(e => e.target.classList.toggle('revealed', e.isIntersecting)), { threshold: 0.2 });
        document.querySelectorAll('.fade-section').forEach(el => fadeObs.observe(el));

        // Mobile menu
        document.getElementById('mobile-menu-btn').addEventListener('click', () => document.getElementById('mobile-menu')?.classList.toggle('hidden'));
        document.querySelectorAll('.mobile-nav-link').forEach(l => l.addEventListener('click', () => document.getElementById('mobile-menu')?.classList.add('hidden')));

        // Lightbox
        const lightbox = document.getElementById('lightbox'), lightboxImg = document.getElementById('lightbox-img'), closeBtn = document.querySelector('.close-lightbox'), prevBtn = document.getElementById('prev-img'), nextBtn = document.getElementById('next-img');
        let curImgs = [], curIdx = 0;
        function openLightbox(imgs, idx) { curImgs = imgs; curIdx = idx; lightboxImg.src = curImgs[curIdx]; lightbox.classList.add('active'); }
        closeBtn.addEventListener('click', () => lightbox.classList.remove('active'));
        lightbox.addEventListener('click', (e) => { if (e.target === lightbox) lightbox.classList.remove('active'); });
        prevBtn.addEventListener('click', () => { if (curImgs.length) { curIdx = (curIdx - 1 + curImgs.length) % curImgs.length; lightboxImg.src = curImgs[curIdx]; } });
        nextBtn.addEventListener('click', () => { if (curImgs.length) { curIdx = (curIdx + 1) % curImgs.length; lightboxImg.src = curImgs[curIdx]; } });
        const journeyImages = document.querySelectorAll('.journey-img');
        const journeyYears = <?= json_encode(array_map(fn($y) => $y['images'], $journey)) ?>;
        journeyImages.forEach(img => {
            img.addEventListener('click', function() { let y = parseInt(this.dataset.year), i = parseInt(this.dataset.imgIndex); if (journeyYears[y]) openLightbox(journeyYears[y], i); });
        });

        // Admin 2FA modal with animation, loading states, and resend
        const modal = document.getElementById('adminModal');
        const openBtns = [document.getElementById('adminModalBtn'), document.getElementById('mobileAdminBtn')];
        const closeModal = document.getElementById('closeModalBtn');
        const passwordStep = document.getElementById('passwordStep');
        const otpStep = document.getElementById('otpStep');
        const adminPwd = document.getElementById('adminPassword');
        const showPwd = document.getElementById('showPasswordCheckbox');
        const submitPwd = document.getElementById('submitPasswordBtn');
        const otpInput = document.getElementById('otpCode');
        const verifyOtp = document.getElementById('verifyOtpBtn');
        const resendOtp = document.getElementById('resendOtpBtn');
        const modalError = document.getElementById('modalError');

        function setButtonLoading(btn, loading) {
            if (loading) {
                btn.classList.add('btn-loading');
                btn.disabled = true;
            } else {
                btn.classList.remove('btn-loading');
                btn.disabled = false;
            }
        }

        if (showPwd && adminPwd) showPwd.addEventListener('change', () => adminPwd.type = showPwd.checked ? 'text' : 'password');
        openBtns.forEach(btn => { if (btn) btn.addEventListener('click', () => {
            modal.classList.add('active');
            passwordStep.style.display = 'block';
            otpStep.style.display = 'none';
            adminPwd.value = '';
            otpInput.value = '';
            modalError.classList.add('hidden');
            if (showPwd) showPwd.checked = false;
            adminPwd.type = 'password';
            setButtonLoading(submitPwd, false);
            setButtonLoading(verifyOtp, false);
            setButtonLoading(resendOtp, false);
        }); });
        closeModal.addEventListener('click', () => modal.classList.remove('active'));
        modal.addEventListener('click', (e) => { if (e.target === modal) modal.classList.remove('active'); });
        
        submitPwd.addEventListener('click', () => {
            let pwd = adminPwd.value;
            setButtonLoading(submitPwd, true);
            fetch('admin-login.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'step=password&password=' + encodeURIComponent(pwd)
            }).then(res => res.json()).then(data => {
                setButtonLoading(submitPwd, false);
                if (data.success && data.step === 'otp') {
                    passwordStep.style.display = 'none';
                    otpStep.style.display = 'block';
                    modalError.classList.add('hidden');
                } else {
                    modalError.textContent = data.error || 'Invalid password';
                    modalError.classList.remove('hidden');
                }
            }).catch(() => {
                setButtonLoading(submitPwd, false);
                modalError.textContent = 'Server error';
                modalError.classList.remove('hidden');
            });
        });
        
        verifyOtp.addEventListener('click', () => {
            let otp = otpInput.value;
            setButtonLoading(verifyOtp, true);
            fetch('admin-login.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'step=otp&otp=' + encodeURIComponent(otp)
            }).then(res => res.json()).then(data => {
                setButtonLoading(verifyOtp, false);
                if (data.success) {
                    window.location.href = 'admin.php';
                } else {
                    modalError.textContent = data.error || 'Invalid OTP';
                    modalError.classList.remove('hidden');
                }
            }).catch(() => {
                setButtonLoading(verifyOtp, false);
                modalError.textContent = 'Server error';
                modalError.classList.remove('hidden');
            });
        });
        
        resendOtp.addEventListener('click', () => {
            setButtonLoading(resendOtp, true);
            fetch('admin-login.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'step=resend'
            }).then(res => res.json()).then(data => {
                setButtonLoading(resendOtp, false);
                if (data.success) {
                    modalError.textContent = data.message || 'A new OTP has been sent to your email.';
                    modalError.classList.remove('hidden');
                    setTimeout(() => modalError.classList.add('hidden'), 5000);
                } else {
                    modalError.textContent = data.error || 'Failed to resend OTP';
                    modalError.classList.remove('hidden');
                }
            }).catch(() => {
                setButtonLoading(resendOtp, false);
                modalError.textContent = 'Server error';
                modalError.classList.remove('hidden');
            });
        });
        
        document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && modal.classList.contains('active')) modal.classList.remove('active'); });
    </script>
</body>
</html>