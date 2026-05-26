<?php

declare(strict_types=1);

require_once __DIR__ . '/../modelo/TutorModel.php';

final class HomeController
{
    public function index(): void
    {
        $activePage = 'inicio';
        $materiasDemandadas = [];

        try {
            $materiasDemandadas = (new TutorModel())->getMateriasDemandadas(3);
        } catch (Throwable $exception) {
            // silently ignore — view will show fallback
        }

        require __DIR__ . '/../vista/paginas/inicio.php';
    }
}
