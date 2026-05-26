<footer id="contacto">
    <div class="footer-content">
        <div class="footer-brand">
            <h3>UniLink</h3>
            <p>Red de apoyo academico entre estudiantes de la Universidad del Magdalena.</p>
            <br>
            <p>© 2026 Todos los derechos reservados</p>
        </div>
        <div class="footer-section">
            <h4>Enlaces</h4>
            <a href="index.php?page=inicio">Inicio</a>
            <a href="index.php?page=tutores">Tutores</a>
            <a href="index.php?page=ser-tutor">Ser Tutor</a>
            <a href="index.php?page=perfil">Ver Perfil</a>
        </div>
        <div class="footer-section">
            <h4>Contacto</h4>
            <p>apoyo@unimagdalena.edu.co</p>
            <p>Santa Marta, Colombia</p>
            <p>Universidad del Magdalena</p>
        </div>
        <div class="footer-section">
            <h4>Siguenos</h4>
            <div class="social-icons">
                <span title="Facebook">FB</span>
                <span title="Instagram">IG</span>
                <span title="Twitter">X</span>
            </div>
        </div>
    </div>
    <div class="footer-bottom">
        <p>Hecho con dedicacion por estudiantes para estudiantes</p>
    </div>
</footer>

<?php
$scriptPath = __DIR__ . '/../../js/script.js';
$scriptVersion = file_exists($scriptPath) ? (string) filemtime($scriptPath) : (string) time();
?>
<script src="js/script.js?v=<?= e($scriptVersion) ?>"></script>
</body>
</html>
