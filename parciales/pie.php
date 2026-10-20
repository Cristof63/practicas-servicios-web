</main>

<footer class="pie">
    Cristhopher Robledo · Aplicaciones Web Orientadas a Servicios · UPVM
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php if (!empty($scriptsP4)): ?>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
            integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script type="module" src="<?= e(enlace('assets/p4/main.js')) ?>"></script>
<?php endif; ?>
</body>
</html>