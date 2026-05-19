<?php
require_once 'config.php';
$data = loadData();
extract($data);

$formSent = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['contact_submit'])) {
    $name    = trim(strip_tags($_POST['name'] ?? ''));
    $email   = trim(strip_tags($_POST['email'] ?? ''));
    $message = trim(strip_tags($_POST['message'] ?? ''));
    if ($name && $email && filter_var($email, FILTER_VALIDATE_EMAIL) && $message) {
        $formSent = true;
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
                        navy: {
                            50: '#eef2ff', 100: '#e0e7ff', 200: '#c7d2fe',
                            300: '#a5b4fc', 400: '#818cf8', 500: '#1e3a8a',
                            600: '#1e40af', 700: '#1d4ed8', 800: '#1e3a8a', 900: '#0f172a'
                        }
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
        .modal { display: none; position: fixed; top:0; left:0; width:100%; height:100%; background: rgba(0,0,0,0.6); z-index:10001; align-items: center; justify-content: center; }
        .modal.active { display: flex; }
        .modal-content { background: white; padding: 2rem; border-radius: 1.5rem; max-width: 400px; width: 90%; }
    </style>
</head>
<body class="bg-white text-navy-900 font-sans antialiased">
    <!-- LOADING ANIMATION -->
    <div id="loader"><div class="loader-spinner"></div></div>

    <!-- LIGHTBOX -->
    <div id="lightbox" class="lightbox">
        <span class="close-lightbox">&times;</span>
        <img id="lightbox-img" alt="">
        <div class="lightbox-nav">
            <button id="prev-img">❮ Prev</button>
            <button id="next-img">Next ❯</button>
        </div>
    </div>

    <!-- ADMIN LOGIN MODAL with Show Password toggle -->
    <div id="adminModal" class="modal">
        <div class="modal-content">
            <div class="text-center mb-4">
                <i data-lucide="lock" class="w-12 h-12 mx-auto text-navy-800"></i>
                <h2 class="text-xl font-bold mt-2">Admin Login</h2>
            </div>
            <div id="modalError" class="text-red-600 text-sm mb-3 hidden"></div>
            <input type="password" id="adminPassword" placeholder="Password" class="w-full px-4 py-2 border rounded-lg mb-2">
            <label class="inline-flex items-center text-sm text-gray-600 mb-3">
                <input type="checkbox" id="showPasswordCheckbox" class="mr-2"> Show Password
            </label>
            <button id="modalLoginBtn" class="w-full bg-navy-800 text-white py-2 rounded-lg hover:bg-navy-900">Login</button>
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
            <!-- HERO SLIDER (multiple images per slide) -->
            <section id="home" class="section-anchor min-h-[90vh] flex items-center justify-center relative overflow-hidden">
                <div class="absolute inset-0 z-0">
                    <?php foreach ($hero_slides as $slideIdx => $slide): ?>
                        <div class="hero-slide absolute inset-0 transition-opacity duration-700 <?= $slideIdx === 0 ? 'opacity-100' : 'opacity-0' ?>" data-slide="<?= $slideIdx ?>">
                            <?php $images = $slide['images'] ?? []; ?>
                            <?php if (!empty($images)): ?>
                                <div class="hero-image-wrapper w-full h-full relative">
                                    <?php foreach ($images as $imgIdx => $img): ?>
                                        <div class="hero-image absolute inset-0 bg-cover bg-center transition-opacity duration-1000" style="background-image: url('<?= htmlspecialchars($img) ?>'); opacity: <?= $imgIdx === 0 ? '1' : '0' ?>;" data-img-index="<?= $imgIdx ?>"></div>
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
                        <span class="text-navy-700 font-semibold text-sm uppercase tracking-widest">About Me</span>
                        <h2 class="text-3xl lg:text-4xl font-bold text-navy-900 mt-2">Get to know me</h2>
                    </div>
                    <div class="grid lg:grid-cols-3 gap-10">
                        <div class="lg:col-span-2 space-y-5 text-navy-700 leading-relaxed text-lg">
                            <?= nl2br(htmlspecialchars($about['bio'])) ?>
                            <?php if (!empty($about['stats'])): ?>
                                <div class="grid grid-cols-2 sm:grid-cols-3 gap-5 pt-4">
                                    <?php foreach ($about['stats'] as $stat): ?>
                                        <div class="bg-white rounded-xl p-5 shadow-sm border border-navy-100 text-center">
                                            <p class="text-3xl font-bold text-navy-900"><?= htmlspecialchars($stat['value']) ?></p>
                                            <p class="text-sm text-navy-500 mt-1"><?= htmlspecialchars($stat['label']) ?></p>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="bg-white rounded-2xl p-6 shadow-md border border-navy-100 h-fit space-y-4">
                            <h3 class="font-semibold text-navy-900 text-lg">Quick Info</h3>
                            <div class="space-y-3 text-sm text-navy-700">
                                <div class="flex items-center gap-3"><i data-lucide="map-pin" class="w-4 h-4"></i><span><?= htmlspecialchars($owner['location']) ?></span></div>
                                <div class="flex items-center gap-3"><i data-lucide="mail" class="w-4 h-4"></i><span><?= htmlspecialchars($owner['email']) ?></span></div>
                                <div class="flex items-center gap-3"><i data-lucide="phone" class="w-4 h-4"></i><span><?= htmlspecialchars($owner['phone']) ?></span></div>
                            </div>
                            <?php if (!empty($owner['cv_url'])): ?>
                                <a href="<?= htmlspecialchars($owner['cv_url']) ?>" download class="w-full inline-flex items-center justify-center gap-2 mt-3 px-4 py-2.5 bg-gradient-to-r from-navy-700 to-navy-800 text-white text-sm font-medium rounded-lg">Download CV</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </section>

                <!-- PROJECTS SECTION -->
                <section id="projects" class="section-anchor py-16 lg:py-20 fade-section">
                    <div class="mb-10 text-center lg:text-left">
                        <span class="text-navy-700 font-semibold text-sm uppercase tracking-widest">Portfolio</span>
                        <h2 class="text-3xl lg:text-4xl font-bold text-navy-900 mt-2">Featured Projects</h2>
                    </div>
                    <div class="grid sm:grid-cols-2 gap-6">
                        <?php foreach ($projects as $project): ?>
                            <div class="group bg-white rounded-2xl overflow-hidden shadow-sm border border-navy-100 hover:shadow-lg transition-all">
                                <img src="<?= htmlspecialchars($project['image']) ?>" class="h-48 w-full object-cover" onerror="this.src='https://placehold.co/600x400?text=Project'">
                                <div class="p-6">
                                    <h3 class="font-bold text-lg text-navy-900"><?= htmlspecialchars($project['title']) ?></h3>
                                    <p class="text-navy-600 text-sm mt-2"><?= htmlspecialchars($project['description']) ?></p>
                                    <div class="flex flex-wrap gap-2 mt-4">
                                        <?php foreach ($project['tags'] as $tag): ?>
                                            <span class="px-2.5 py-1 bg-navy-50 text-navy-700 text-xs font-medium rounded-md"><?= htmlspecialchars($tag) ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                    <?php if (!empty($project['url'])): ?>
                                        <a href="<?= htmlspecialchars($project['url']) ?>" class="inline-flex items-center gap-1.5 mt-5 text-navy-700 font-medium text-sm hover:underline">View Project <i data-lucide="arrow-up-right" class="w-4 h-4"></i></a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>

                <!-- SKILLS (no levels) -->
                <section id="skills" class="section-anchor py-16 lg:py-20 fade-section">
                    <div class="mb-10 text-center lg:text-left">
                        <span class="text-navy-700 font-semibold text-sm uppercase tracking-widest">Skills</span>
                        <h2 class="text-3xl lg:text-4xl font-bold text-navy-900 mt-2">Technologies I Use</h2>
                    </div>
                    <div class="flex flex-wrap gap-3">
                        <?php foreach ($skills as $skill): ?>
                            <span class="px-4 py-2 bg-navy-50 text-navy-800 rounded-full text-sm font-medium border border-navy-200 shadow-sm"><?= htmlspecialchars($skill) ?></span>
                        <?php endforeach; ?>
                    </div>
                </section>

                <!-- EXPERIENCE SECTION -->
                <section id="experience" class="section-anchor py-16 lg:py-20 fade-section">
                    <div class="mb-10 text-center lg:text-left">
                        <span class="text-navy-700 font-semibold text-sm uppercase tracking-widest">Experience</span>
                        <h2 class="text-3xl lg:text-4xl font-bold text-navy-900 mt-2">Work History</h2>
                    </div>
                    <div class="space-y-0 relative before:absolute before:left-5 before:top-0 before:bottom-0 before:w-px before:bg-navy-200">
                        <?php $first = true; foreach ($experience as $exp): ?>
                            <div class="relative pl-14 pb-10 last:pb-0">
                                <div class="absolute left-3.5 top-1 w-3.5 h-3.5 rounded-full border-2 <?= $first ? 'border-navy-700 bg-navy-700 shadow-lg shadow-navy-700/40' : 'border-navy-300 bg-white' ?> z-10"></div>
                                <div class="bg-white rounded-xl p-6 shadow-sm border border-navy-100 hover:shadow-md transition-shadow">
                                    <span class="text-xs font-semibold text-navy-700 uppercase tracking-wider"><?= htmlspecialchars($exp['period']) ?></span>
                                    <h3 class="text-lg font-bold text-navy-900 mt-1"><?= htmlspecialchars($exp['role']) ?></h3>
                                    <p class="text-navy-600 font-medium text-sm"><?= htmlspecialchars($exp['company']) ?></p>
                                    <p class="text-navy-500 text-sm mt-2"><?= htmlspecialchars($exp['desc']) ?></p>
                                </div>
                            </div>
                            <?php $first = false; ?>
                        <?php endforeach; ?>
                    </div>
                </section>

                <!-- COLLEGE JOURNEY (enhanced images) -->
                <section id="journey" class="section-anchor py-16 lg:py-20 fade-section">
                    <div class="mb-10 text-center lg:text-left">
                        <span class="text-navy-700 font-semibold text-sm uppercase tracking-widest">Education</span>
                        <h2 class="text-3xl lg:text-4xl font-bold text-navy-900 mt-2">My College Journey</h2>
                    </div>
                    <div class="space-y-12">
                        <?php foreach ($journey as $yearIndex => $yearData): ?>
                            <div>
                                <h3 class="text-2xl font-bold text-navy-900 mb-2 border-l-4 border-navy-700 pl-4"><?= htmlspecialchars($yearData['year']) ?> Year</h3>
                                <p class="text-navy-700 mb-4 leading-relaxed"><?= nl2br(htmlspecialchars($yearData['description'])) ?></p>
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

                <!-- CONTACT (dynamic) -->
                <section id="contact" class="section-anchor py-16 lg:py-20 fade-section">
                    <div class="mb-10 text-center lg:text-left">
                        <span class="text-navy-700 font-semibold text-sm uppercase tracking-widest">Contact</span>
                        <h2 class="text-3xl lg:text-4xl font-bold text-navy-900 mt-2"><?= htmlspecialchars($contact['heading']) ?></h2>
                    </div>
                    <div class="grid lg:grid-cols-5 gap-10">
                        <div class="lg:col-span-3">
                            <?php if ($formSent): ?>
                                <div class="mb-6 p-4 rounded-xl bg-navy-50 text-navy-800 border border-navy-200 text-sm font-medium">Thank you! Your message has been sent successfully.</div>
                            <?php endif; ?>
                            <form method="POST" action="#contact" class="space-y-5 bg-white rounded-2xl p-6 lg:p-8 shadow-sm border border-navy-100">
                                <div class="grid sm:grid-cols-2 gap-5">
                                    <div><input type="text" name="name" required placeholder="Full Name" class="w-full px-4 py-3 border border-navy-200 rounded-xl focus:ring-2 focus:ring-navy-500 focus:border-navy-500 outline-none"></div>
                                    <div><input type="email" name="email" required placeholder="Email Address" class="w-full px-4 py-3 border border-navy-200 rounded-xl focus:ring-2 focus:ring-navy-500 outline-none"></div>
                                </div>
                                <div><textarea name="message" rows="5" required placeholder="Message..." class="w-full px-4 py-3 border border-navy-200 rounded-xl focus:ring-2 focus:ring-navy-500 outline-none resize-none"></textarea></div>
                                <button type="submit" name="contact_submit" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-8 py-3.5 bg-gradient-to-r from-navy-700 to-navy-800 text-white font-semibold rounded-xl hover:from-navy-800 hover:to-navy-900 shadow-lg"><i data-lucide="send" class="w-5 h-5"></i> Send Message</button>
                            </form>
                        </div>
                        <div class="lg:col-span-2 space-y-5">
                            <div class="bg-navy-800 rounded-2xl p-6 lg:p-8 text-white space-y-6 shadow-xl">
                                <h3 class="font-bold text-xl">Get in touch</h3>
                                <p class="text-white/70 text-sm leading-relaxed"><?= htmlspecialchars($contact['subheading']) ?></p>
                                <div class="space-y-4 text-sm">
                                    <div class="flex items-center gap-3"><div class="w-10 h-10 rounded-lg bg-white/10 flex items-center justify-center"><i data-lucide="mail" class="w-5 h-5 text-white/70"></i></div><span><?= htmlspecialchars($owner['email']) ?></span></div>
                                    <div class="flex items-center gap-3"><div class="w-10 h-10 rounded-lg bg-white/10 flex items-center justify-center"><i data-lucide="phone" class="w-5 h-5 text-white/70"></i></div><span><?= htmlspecialchars($owner['phone']) ?></span></div>
                                    <div class="flex items-center gap-3"><div class="w-10 h-10 rounded-lg bg-white/10 flex items-center justify-center"><i data-lucide="map-pin" class="w-5 h-5 text-white/70"></i></div><span><?= htmlspecialchars($owner['location']) ?></span></div>
                                </div>
                                <?php if (!empty($contact['extra_text'])): ?>
                                    <p class="text-white/60 text-xs italic"><?= htmlspecialchars($contact['extra_text']) ?></p>
                                <?php endif; ?>
                                <div class="flex gap-3 pt-2">
                                    <?php foreach ($owner['socials'] as $platform => $url): if (!empty($url)): ?>
                                        <a href="<?= $url ?>" target="_blank" class="w-9 h-9 rounded-lg bg-white/10 flex items-center justify-center hover:bg-white/20 transition-colors"><i data-lucide="<?= $platform === 'facebook' ? 'facebook' : $platform ?>" class="w-4 h-4"></i></a>
                                    <?php endif; endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </main>

        <!-- FOOTER -->
        <footer class="py-8 border-t border-navy-100 mt-8 text-center text-sm bg-white">
            <div class="max-w-6xl mx-auto px-4">
                <p class="text-navy-600"><?= htmlspecialchars($footer['copyright'] ?? '© ' . date('Y') . ' ' . $owner['name']) ?></p>
                <?php if (!empty($footer['credit'])): ?>
                    <p class="text-navy-400 text-xs mt-1"><?= htmlspecialchars($footer['credit']) ?></p>
                <?php endif; ?>
            </div>
        </footer>
    </div>

    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
    <script>
        // ==================== INITIALIZE ICONS ====================
        lucide.createIcons();

        // ==================== HIDE LOADER ====================
        window.addEventListener('load', () => {
            let loader = document.getElementById('loader');
            loader.style.opacity = '0';
            setTimeout(() => loader.style.display = 'none', 500);
        });

        // ==================== HERO SLIDER (multi‑image slides) ====================
        let currentSlide = 0;
        let slides = document.querySelectorAll('.hero-slide');
        let slideImageTimers = [];

        function initSlideImages() {
            slides.forEach((slide, idx) => {
                let images = slide.querySelectorAll('.hero-image');
                if (images.length > 1) {
                    let imgIndex = 0;
                    let timer = setInterval(() => {
                        if (currentSlide !== idx) return;
                        imgIndex = (imgIndex + 1) % images.length;
                        images.forEach((img, i) => { img.style.opacity = i === imgIndex ? '1' : '0'; });
                    }, 4000);
                    slideImageTimers.push(timer);
                }
            });
        }

        function showSlide(index) {
            slides.forEach((s, i) => { s.classList[i === index ? 'remove' : 'add']('opacity-0'); });
            currentSlide = index;
        }

        if (slides.length > 1) {
            document.getElementById('next-slide')?.addEventListener('click', () => { let next = (currentSlide + 1) % slides.length; showSlide(next); });
            document.getElementById('prev-slide')?.addEventListener('click', () => { let prev = (currentSlide - 1 + slides.length) % slides.length; showSlide(prev); });
            setInterval(() => { let next = (currentSlide + 1) % slides.length; showSlide(next); }, 8000);
        }
        initSlideImages();

        // ==================== ACTIVE NAVIGATION HIGHLIGHT ====================
        const sections = document.querySelectorAll('.section-anchor');
        const navLinks = document.querySelectorAll('.top-nav-link, .mobile-nav-link');

        function updateActiveNav() {
            let curId = 'home';
            let scrollPos = window.scrollY + 120;
            sections.forEach(sec => {
                let top = sec.offsetTop, height = sec.offsetHeight;
                if (scrollPos >= top && scrollPos < top + height) curId = sec.id;
            });
            navLinks.forEach(link => {
                let secId = link.getAttribute('data-section');
                if (secId === curId) {
                    link.classList.add('bg-navy-50', 'text-navy-900');
                    link.classList.remove('text-navy-700');
                } else {
                    link.classList.remove('bg-navy-50', 'text-navy-900');
                    link.classList.add('text-navy-700');
                }
            });
        }
        window.addEventListener('scroll', updateActiveNav);
        updateActiveNav();

        // ==================== FADE SECTIONS ON SCROLL ====================
        const fadeSections = document.querySelectorAll('.fade-section');
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(e => {
                if (e.isIntersecting) e.target.classList.add('revealed');
                else e.target.classList.remove('revealed');
            });
        }, { threshold: 0.2 });
        fadeSections.forEach(el => observer.observe(el));

        // ==================== MOBILE MENU ====================
        document.getElementById('mobile-menu-btn').addEventListener('click', () => document.getElementById('mobile-menu').classList.toggle('hidden'));
        document.querySelectorAll('.mobile-nav-link').forEach(l => l.addEventListener('click', () => document.getElementById('mobile-menu').classList.add('hidden')));

        // ==================== LIGHTBOX GALLERY ====================
        const lightbox = document.getElementById('lightbox');
        const lightboxImg = document.getElementById('lightbox-img');
        const closeLightbox = document.querySelector('.close-lightbox');
        const prevBtn = document.getElementById('prev-img');
        const nextBtn = document.getElementById('next-img');
        let currentImages = [], currentIdx = 0;

        function openLightbox(images, idx) {
            currentImages = images;
            currentIdx = idx;
            lightboxImg.src = currentImages[currentIdx];
            lightbox.classList.add('active');
        }
        closeLightbox.addEventListener('click', () => lightbox.classList.remove('active'));
        lightbox.addEventListener('click', (e) => { if (e.target === lightbox) lightbox.classList.remove('active'); });
        prevBtn.addEventListener('click', () => {
            if (currentImages.length) {
                currentIdx = (currentIdx - 1 + currentImages.length) % currentImages.length;
                lightboxImg.src = currentImages[currentIdx];
            }
        });
        nextBtn.addEventListener('click', () => {
            if (currentImages.length) {
                currentIdx = (currentIdx + 1) % currentImages.length;
                lightboxImg.src = currentImages[currentIdx];
            }
        });

        const journeyImages = document.querySelectorAll('.journey-img');
        const journeyYears = <?= json_encode(array_map(fn($y) => $y['images'], $journey)) ?>;
        journeyImages.forEach(img => {
            img.addEventListener('click', function() {
                let y = parseInt(this.dataset.year);
                let i = parseInt(this.dataset.imgIndex);
                if (journeyYears[y]) openLightbox(journeyYears[y], i);
            });
        });

        // ==================== ADMIN LOGIN MODAL (AJAX) ====================
        const modal = document.getElementById('adminModal');
        const openModalBtns = [document.getElementById('adminModalBtn'), document.getElementById('mobileAdminBtn')];
        const closeModalBtn = document.getElementById('closeModalBtn');
        const modalLoginBtn = document.getElementById('modalLoginBtn');
        const adminPassword = document.getElementById('adminPassword');
        const modalError = document.getElementById('modalError');
        const showPwdCheck = document.getElementById('showPasswordCheckbox');

        // Show/hide password toggle
        if (showPwdCheck && adminPassword) {
            showPwdCheck.addEventListener('change', function() {
                adminPassword.type = this.checked ? 'text' : 'password';
            });
        }

        openModalBtns.forEach(btn => { if (btn) btn.addEventListener('click', () => { modal.classList.add('active'); adminPassword.value = ''; modalError.classList.add('hidden'); adminPassword.type = 'password'; showPwdCheck.checked = false; }); });
        closeModalBtn.addEventListener('click', () => modal.classList.remove('active'));
        modal.addEventListener('click', (e) => { if (e.target === modal) modal.classList.remove('active'); });
        modalLoginBtn.addEventListener('click', () => {
            let pwd = adminPassword.value;
            fetch('admin-login.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'password=' + encodeURIComponent(pwd) + '&ajax=1'
            }).then(res => res.json()).then(data => {
                if (data.success) { window.location.href = 'admin.php'; }
                else { modalError.textContent = data.error || 'Invalid password'; modalError.classList.remove('hidden'); }
            }).catch(() => { modalError.textContent = 'Server error'; modalError.classList.remove('hidden'); });
        });
        document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && modal.classList.contains('active')) modal.classList.remove('active'); });
    </script>
</body>
</html>