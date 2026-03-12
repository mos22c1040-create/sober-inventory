        </main>
    </div> <!-- End Main Content Wrapper -->
    <script>
    window.APP_BASE = <?= json_encode($basePath ?? (rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\') ?: '/'), JSON_UNESCAPED_UNICODE) ?>;
    </script>
    <script>
    (function() {
        var btn = document.getElementById('sidebar-toggle');
        var backdrop = document.getElementById('sidebar-backdrop');
        var sidebar = document.getElementById('app-sidebar');
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
    </script>
</body>
</html>
