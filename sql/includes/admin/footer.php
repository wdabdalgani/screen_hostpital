      </main>
    </div>
  </div>
  <div class="saas-overlay" id="saasOverlay" hidden aria-hidden="true"></div>
  <?php if (!empty($adminFooterScripts) && is_array($adminFooterScripts)): ?>
    <?php foreach ($adminFooterScripts as $adminScriptSrc): ?>
  <script src="<?= esc($adminScriptSrc) ?>" defer></script>
    <?php endforeach; ?>
  <?php endif; ?>
  <script src="<?= esc(url('assets/js/admin.js')) ?>?v=3" defer></script>
</body>
</html>
