<style>
.landing-gradient {
    background: linear-gradient(135deg, #0F172A 0%, #1E3A5F 40%, #312E81 100%);
    background-size: 200% 200%;
    animation: gradientShift 12s ease infinite;
}
.landing-gradient-solid {
    background: linear-gradient(135deg, #1E3A5F 0%, #312E81 100%);
}
</style>

<!-- Hero -->
<section id="home" class="landing-gradient relative overflow-hidden min-h-screen flex items-center" style="padding-top: 4rem;">
    <div class="particles" id="particles"></div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 sm:py-16 relative z-10 w-full">
        <div class="grid lg:grid-cols-2 gap-10 lg:gap-16 items-center">
            <div class="text-center lg:text-left">
                <div class="inline-flex items-center px-3 py-1 bg-white/10 backdrop-blur-sm rounded-full text-white/70 text-xs sm:text-sm mb-5 border border-white/10 hero-fade-in delay-1">
                    <i class="fa-solid fa-graduation-cap mr-2"></i> Your Learning Companion
                </div>
                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold text-white leading-tight mb-5 hero-fade-in delay-2">
                    Master Every<br>
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-300 to-cyan-300">Exam with Confidence</span>
                </h1>
                <p class="text-base sm:text-lg text-white/70 mb-7 max-w-xl mx-auto lg:mx-0 hero-fade-in delay-3 leading-relaxed">
                    Practice with thousands of questions, track your progress, and ace your academic, job, and general knowledge exams.
                </p>
                <div class="flex flex-col sm:flex-row gap-3 justify-center lg:justify-start hero-fade-in delay-4">
                    <button onclick="openDownloadModal('apk')" class="download-btn inline-flex items-center justify-center px-7 py-3.5 bg-white text-blue-600 rounded-xl font-bold hover:bg-gray-50 transition-all shadow-xl text-sm cursor-pointer">
                        <i class="fa-brands fa-android text-xl mr-2.5"></i> Download APK
                    </button>
                    <button onclick="openDownloadModal('play')" class="download-btn inline-flex items-center justify-center px-7 py-3.5 bg-white/10 backdrop-blur-sm text-white rounded-xl font-bold hover:bg-white/20 transition-all border border-white/20 text-sm cursor-pointer">
                        <i class="fa-brands fa-google-play text-xl mr-2.5"></i> Google Play
                    </button>
                </div>
            </div>
            <div class="hidden lg:flex justify-center hero-fade-in delay-3">
                <div class="grid grid-cols-2 gap-3 max-w-sm">
                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 text-center border border-white/10">
                        <div class="text-3xl font-bold text-white">10K+</div>
                        <div class="text-white/60 text-xs mt-1">Questions</div>
                    </div>
                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 text-center border border-white/10">
                        <div class="text-3xl font-bold text-white">50+</div>
                        <div class="text-white/60 text-xs mt-1">Categories</div>
                    </div>
                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 text-center border border-white/10">
                        <div class="text-3xl font-bold text-white">95%</div>
                        <div class="text-white/60 text-xs mt-1">Success Rate</div>
                    </div>
                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 text-center border border-white/10">
                        <div class="text-3xl font-bold text-white">Free</div>
                        <div class="text-white/60 text-xs mt-1">To Download</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Features -->
<section id="features" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 sm:py-24 scroll-section">
    <div class="text-center mb-12 anim-fade-up">
        <h2 class="text-3xl sm:text-4xl font-bold text-gray-900 dark:text-gray-100 mb-3">Why Choose Shikhbo?</h2>
        <p class="text-base text-gray-500 dark:text-gray-400 max-w-2xl mx-auto">Everything you need to prepare for your exams in one place.</p>
    </div>
    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5 sm:gap-6 anim-stagger" id="featuresGrid">
        <?php
        $features = [
            ['fa-layer-group', 'Multiple Categories', 'Academic, job, and general knowledge exams organized by subject and difficulty level.'],
            ['fa-chart-line', 'Track Progress', 'Detailed analytics on your performance, strengths, and areas that need improvement.'],
            ['fa-bolt', 'Timed Practice', 'Simulate real exam conditions with timed tests and instant result feedback.'],
            ['fa-mobile-screen', 'Mobile First', 'Native Android app with offline support. Study anytime, anywhere.'],
            ['fa-users', 'Referral Program', 'Invite friends and earn rewards. Learning together is better.'],
            ['fa-shield-halved', 'Secure & Private', 'Your data is protected with enterprise-grade security and encryption.'],
        ];
        foreach ($features as $i => [$icon, $title, $desc]):
        ?>
        <div class="feature-card bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm">
            <div class="w-11 h-11 bg-blue-50 dark:bg-blue-900/20 rounded-xl flex items-center justify-center mb-3">
                <i class="fa-solid <?php echo $icon; ?> text-blue-600 dark:text-blue-400"></i>
            </div>
            <h3 class="font-bold text-gray-900 dark:text-gray-100 mb-1.5"><?php echo $title; ?></h3>
            <p class="text-gray-500 dark:text-gray-400 text-sm leading-relaxed"><?php echo $desc; ?></p>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- Download CTA -->
<section id="download" class="landing-gradient-solid relative overflow-hidden scroll-section">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 sm:py-20 text-center relative z-10">
        <div class="anim-fade-up">
        <img src="/image/app_logo.png" alt="Shikhbo" class="logo-img-lg mx-auto mb-5 logo-glow">
        <h2 class="text-3xl sm:text-4xl font-bold text-white mb-3">Ready to Get Started?</h2>
        <p class="text-base text-white/70 mb-8 max-w-2xl mx-auto">Download the Shikhbo Android app and start mastering your exams today.</p>
        <div class="flex flex-col sm:flex-row gap-3 justify-center">
            <button onclick="openDownloadModal('apk')" class="download-btn inline-flex items-center justify-center px-7 py-3.5 bg-white text-blue-600 rounded-xl font-bold hover:bg-gray-50 transition-all shadow-xl text-sm cursor-pointer">
                <i class="fa-brands fa-android text-xl mr-2.5"></i> Download APK
            </button>
            <button onclick="openDownloadModal('play')" class="download-btn inline-flex items-center justify-center px-7 py-3.5 bg-white/10 backdrop-blur-sm text-white rounded-xl font-bold hover:bg-white/20 transition-all border border-white/20 text-sm cursor-pointer">
                <i class="fa-brands fa-google-play text-xl mr-2.5"></i> Get on Google Play
            </button>
        </div>
        </div>
    </div>
</section>

<!-- Privacy Policy -->
<section id="privacy" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 sm:py-20 scroll-section">
    <div class="anim-fade-up max-w-4xl mx-auto">
        <h2 class="text-3xl sm:text-4xl font-bold text-gray-900 dark:text-gray-100 mb-3">Privacy Policy</h2>
        <p class="text-gray-500 dark:text-gray-400 text-sm mb-8">Last updated: January 2026</p>
        <div class="prose prose-gray dark:prose-invert max-w-none space-y-4 text-gray-600 dark:text-gray-400 text-sm leading-relaxed">
            <p>Shikhbo respects your privacy. This Privacy Policy explains how we collect, use, and safeguard your information when you use our mobile application and website.</p>
            <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100 mt-6">Information We Collect</h3>
            <p>We collect information you provide when creating an account, including your name, email address, and academic interests. We also collect anonymized usage data to improve the learning experience.</p>
            <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100 mt-6">How We Use Your Data</h3>
            <p>Your data is used solely to personalize your learning journey, track progress, and improve our services. We never sell your personal information to third parties.</p>
            <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100 mt-6">Data Security</h3>
            <p>We implement industry-standard encryption and security measures to protect your data. All communications between the app and our servers are encrypted via HTTPS.</p>
            <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100 mt-6">Contact</h3>
            <p>For privacy-related inquiries, reach out to us at <a href="mailto:privacy@shikhbo.com" class="text-blue-600 dark:text-blue-400 hover:underline">privacy@shikhbo.com</a>.</p>
        </div>
    </div>
</section>

<!-- Terms of Service -->
<section id="terms" class="bg-gray-50 dark:bg-gray-800/50 scroll-section">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 sm:py-20">
        <div class="anim-fade-up max-w-4xl mx-auto">
            <h2 class="text-3xl sm:text-4xl font-bold text-gray-900 dark:text-gray-100 mb-3">Terms of Service</h2>
            <p class="text-gray-500 dark:text-gray-400 text-sm mb-8">Last updated: January 2026</p>
            <div class="prose prose-gray dark:prose-invert max-w-none space-y-4 text-gray-600 dark:text-gray-400 text-sm leading-relaxed">
                <p>By using Shikhbo, you agree to these terms. Please read them carefully before using our platform.</p>
                <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100 mt-6">Account Responsibilities</h3>
                <p>You are responsible for maintaining the confidentiality of your account credentials. Any activity under your account is your responsibility.</p>
                <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100 mt-6">Acceptable Use</h3>
                <p>You agree not to misuse the platform for unauthorized commercial purposes, distribute harmful content, or attempt to breach our security systems.</p>
                <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100 mt-6">Intellectual Property</h3>
                <p>All content, questions, and materials within Shikhbo are owned by Shikhbo and protected by copyright laws. Redistribution without permission is prohibited.</p>
                <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100 mt-6">Limitation of Liability</h3>
                <p>Shikhbo is provided "as is" without warranties. We are not liable for any damages arising from your use of the platform.</p>
            </div>
        </div>
    </div>
</section>
