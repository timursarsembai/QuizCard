    </main>
    
    <footer class="mt-5 py-4 bg-light border-top">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <p class="text-muted mb-0">
                        &copy; <?php echo date('Y'); ?> QuizCard. Все права защищены.
                    </p>
                </div>
                <div class="col-md-6 text-md-right">
                    <small class="text-muted">
                        Версия 2.0 | 
                        <a href="/teacher/security-dashboard" class="text-muted">Безопасность</a> | 
                        <a href="/teacher/language-switcher" class="text-muted">Язык</a>
                    </small>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- JavaScript -->
    <script src="/js/security.js"></script>
    <script src="/js/audio-player.js"></script>
    <script src="/js/audio-upload.js"></script>
    
    <!-- Page-specific scripts -->
    <?php if (isset($page_scripts)): ?>
        <?php foreach ($page_scripts as $script): ?>
            <script src="<?php echo htmlspecialchars($script); ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <?php if (isset($inline_scripts)): ?>
        <script>
            <?php echo $inline_scripts; ?>
        </script>
    <?php endif; ?>
</body>
</html>
