<?php if (isset($is_dashboard) && $is_dashboard): ?>
        </div><!-- /.dashboard-content -->
    </main><!-- /.dashboard-main -->
</div><!-- /.dashboard-layout -->
<?php endif; ?>

<!-- JavaScript -->
<script src="<?= BASE_URL ?>assets/js/main.js"></script>

<?php if (isset($extra_js)): ?>
    <?= $extra_js ?>
<?php endif; ?>

</body>
</html>
