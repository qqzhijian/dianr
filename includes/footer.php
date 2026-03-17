    </div> <!-- /.container -->

    <!-- 页脚 -->
    <footer class="footer mt-5 py-4 bg-light">
        <div class="container text-center">
            <p class="mb-1">
                &copy; <?php echo date('Y'); ?> 点燃 
                &bull; <a href="https://dianr.cn" class="link">dianr.cn</a> 
                &bull; <a href="mailto:admin@xingtu.org" class="link">admin@xingtu.org</a>
            </p>
            <small class="text-muted">轻量版社交平台，专注核心交互。</small>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- 全局脚本 -->
    <script>
        /**
         * 全局AJAX POST助手函数
         * 
         * 用于向服务器发送异步POST请求
         * 
         * @param {string} url - 请求URL
         * @param {object} data - 要发送的数据对象
         * @param {function} onSuccess - 成功处理函数
         * @param {function} onError - 错误处理函数（可选）
         * 
         * @example
         * ajaxPost('/api/send_message.php', {
         *     to_user_id: 123,
         *     message: 'Hello'
         * }, function(response) {
         *     console.log('发送成功', response);
         * }, function(error) {
         *     console.error('发送失败', error);
         * });
         */
        function ajaxPost(url, data, onSuccess, onError) {
            // 发送POST请求
            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(data)
            })
            // 解析为JSON
            .then(res => res.json())
            // 调用成功回调
            .then(onSuccess)
            // 错误处理
            .catch(err => {
                if (onError) onError(err);
            });
        }
    </script>
</body>
</html>
