        </main>
    </div> <!-- End Main Content Wrapper -->
    <script>
    (function () {
        var meta = document.querySelector('meta[name="app-base"]');
        window.APP_BASE = meta ? meta.content : <?= json_encode(rtrim((string)($_ENV['APP_SUBDIR'] ?? getenv('APP_SUBDIR') ?: ''), '/'), JSON_UNESCAPED_UNICODE) ?>;
    })();
    </script>
    <script>
    (function() {
        var btn = document.getElementById('sidebar-toggle');
        var backdrop = document.getElementById('sidebar-backdrop');
        var sidebar = document.getElementById('app-sidebar');
        if (window.matchMedia('(min-width: 768px)').matches) {
            document.body.classList.remove('mobile-menu-open');
            if (backdrop) backdrop.style.display = 'none';
        }
        function openMenu() {
            document.body.classList.add('mobile-menu-open');
            if (btn) btn.setAttribute('aria-expanded', 'true');
        }
        function closeMenu() {
            document.body.classList.remove('mobile-menu-open');
            if (btn) btn.setAttribute('aria-expanded', 'false');
        }
        if (btn) btn.addEventListener('click', function() {
            document.body.classList.toggle('mobile-menu-open');
            btn.setAttribute('aria-expanded', document.body.classList.contains('mobile-menu-open'));
        });
        if (backdrop) backdrop.addEventListener('click', closeMenu);
        if (sidebar) {
            sidebar.querySelectorAll('a').forEach(function(a) { a.addEventListener('click', closeMenu); });
        }
    })();
    (function() {
        var root = document.documentElement;
        var btn = document.getElementById('theme-toggle');
        var icon = document.getElementById('theme-icon');
        function setIcon() {
            if (!icon) return;
            var dark = root.getAttribute('data-theme') === 'dark';
            icon.className = 'fa-solid text-xs ' + (dark ? 'fa-sun' : 'fa-moon');
        }
        if (btn) {
            btn.addEventListener('click', function() {
                var dark = root.getAttribute('data-theme') === 'dark';
                root.setAttribute('data-theme', dark ? 'light' : 'dark');
                try { localStorage.setItem('sober-theme', dark ? 'light' : 'dark'); } catch (e) {}
                setIcon();
            });
        }
        setIcon();
    })();
    </script>
</body>
</html>
