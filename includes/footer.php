        </main>
    </div>
</div>

<?php
$scriptPath = APP_ROOT . '/assets/js/script.js';
$scriptVersion = file_exists($scriptPath) ? (string) filemtime($scriptPath) : APP_VERSION;
?>
<script src="<?= BASE_URL ?>/assets/js/script.js?v=<?= h($scriptVersion) ?>"></script>
</body>
</html>

