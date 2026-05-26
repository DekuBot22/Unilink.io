<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../modelo/ContenidoModel.php';

final class ContenidoController
{
    private ContenidoModel $model;

    public function __construct()
    {
        $this->model = new ContenidoModel();
    }

    public function index(): void
    {
        $activePage = 'contenido';
        $contenidos = $this->model->getApproved();

        require __DIR__ . '/../vista/paginas/contenido.php';
    }
}
