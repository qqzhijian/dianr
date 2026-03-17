    </div> <!-- /.container -->

    <footer class="footer mt-5 py-4 bg-light">
        <div class="container text-center">
            <p class="mb-1">&copy; <?php echo date('Y'); ?> 点燃 &bull; <a href="https://dianr.cn" class="link">dianr.cn</a> &bull; <a href="mailto:admin@xingtu.org" class="link">admin@xingtu.org</a></p>
            <small class="text-muted">轻量版社交平台，专注核心交互。</small>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Global AJAX helper
        function ajaxPost(url, data, onSuccess, onError) {
            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(data)
            })
            .then(res => res.json())
            .then(onSuccess)
            .catch(err => {
                if (onError) onError(err);
            });
        }
    </script>
</body>
</html>
