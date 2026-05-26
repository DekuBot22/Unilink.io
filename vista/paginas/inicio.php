<?php
$pageTitle = 'Inicio';
require __DIR__ . '/../partials/head.php';
require __DIR__ . '/../partials/header.php';
require __DIR__ . '/../partials/flash.php';
require __DIR__ . '/../partials/auth-modal.php';
?>

<section class="hero">
    <div class="hero-content">
        <h2>Aprende con quienes ya pasaron por lo mismo</h2>
        <p>Conectamos estudiantes que necesitan apoyo academico con tutores voluntarios de la Universidad del Magdalena. 100% gratuito, entre companeros.</p>
        <div class="hero-buttons">
            <a href="index.php?page=tutores" class="btn-primary">Buscar tutor</a>
            <a href="index.php?page=ser-tutor" class="btn-secondary">Quiero ser tutor</a>
        </div>
    </div>
    <div class="hero-image" id="heroCarousel">
        <button class="carousel-btn carousel-prev" type="button" aria-label="Imagen anterior">&#10094;</button>
        <img src="img/estudianteUnimag.webp" alt="Estudiantes UniLink" class="hero-slide is-active">
        <img src="img/estudianteUnimag2.webp" alt="Tutoria entre estudiantes" class="hero-slide">
        <img src="img/estudianteUnimag3.jpg" alt="Estudiantes Universidad del Magdalena" class="hero-slide">
        <button class="carousel-btn carousel-next" type="button" aria-label="Imagen siguiente">&#10095;</button>
        <div class="carousel-dots" aria-hidden="true">
            <span class="carousel-dot is-active"></span>
            <span class="carousel-dot"></span>
            <span class="carousel-dot"></span>
        </div>
    </div>
</section>

<section class="como-funciona">
    <h2>Como funciona?</h2>
    <div class="pasos-container">
        <div class="paso-card">
            <div class="paso-icon">1</div>
            <h3>Registrate</h3>
            <p>Crea tu perfil como estudiante que busca apoyo o como tutor voluntario.</p>
        </div>
        <div class="paso-card">
            <div class="paso-icon">2</div>
            <h3>Conecta</h3>
            <p>Encuentra un tutor en la materia que necesitas o unete a un grupo de estudio.</p>
        </div>
        <div class="paso-card">
            <div class="paso-icon">3</div>
            <h3>Aprende</h3>
            <p>Programa tus sesiones de estudio, resuelve tus dudas y mejora tu rendimiento.</p>
        </div>
    </div>
</section>

<section class="materias">
    <h2>Materias con mas demanda</h2>
    <div class="materias-grid">
        <?php
        $materiasDemandadas = $materiasDemandadas ?? [];
        if ($materiasDemandadas):
            foreach ($materiasDemandadas as $m):
                $tag = e($m['tag'] !== '' ? $m['tag'] : $m['nombre']);
                $plural = $m['count'] === 1 ? 'tutor' : 'tutores';
        ?>
            <div class="materia-card" onclick="location.href='index.php?page=tutores&materia=<?= $tag ?>'">
                <h4><?= e($m['nombre']) ?></h4>
                <span><?= (int) $m['count'] ?> <?= $plural ?></span>
            </div>
        <?php
            endforeach;
        else:
        ?>
            <div class="materia-card" onclick="location.href='index.php?page=tutores'">
                <h4>Sin tutores aun</h4>
                <span>0 tutores</span>
            </div>
        <?php endif; ?>
    </div>
    <a href="index.php?page=tutores" class="ver-todas">Ver todas las materias</a>
</section>

<?php require __DIR__ . '/../partials/footer.php'; ?>
