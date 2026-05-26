<?php

declare(strict_types=1);

session_start();

require_once __DIR__ . '/config/helpers.php';
require_once __DIR__ . '/controlador/HomeController.php';
require_once __DIR__ . '/controlador/TutorController.php';
require_once __DIR__ . '/controlador/PostulacionController.php';
require_once __DIR__ . '/controlador/AuthController.php';
require_once __DIR__ . '/controlador/PerfilController.php';
require_once __DIR__ . '/controlador/CitaController.php';
require_once __DIR__ . '/controlador/ContenidoController.php';
require_once __DIR__ . '/controlador/AdminController.php';
require_once __DIR__ . '/controlador/GoogleAuthController.php';
require_once __DIR__ . '/controlador/PasswordController.php';
require_once __DIR__ . '/controlador/NotificacionController.php';
require_once __DIR__ . '/controlador/VideollamadaController.php';
require_once __DIR__ . '/controlador/MensajeController.php';

$page = $_GET['page'] ?? 'inicio';
$action = $_GET['action'] ?? null;

if ($action) {
    $authController = new AuthController();
    $postulacionController = new PostulacionController();
    $citaController = new CitaController();
    $adminController = new AdminController();
    $tutorController = new TutorController();

    $passwordController = new PasswordController();

    switch ($action) {
        case 'auth/login':
            $authController->login();
            break;
        case 'auth/register':
            $authController->register();
            break;
        case 'auth/logout':
            $authController->logout();
            break;
        case 'auth/verify':
            $authController->verify();
            break;
        case 'auth/resend':
            $authController->resendVerification();
            break;
        case 'password/solicitar-cambio':
            $passwordController->solicitarCambio();
            break;
        case 'password/solicitar-reset':
            $passwordController->solicitarReset();
            break;
        case 'password/do-reset':
            $passwordController->doReset();
            break;
        case 'auth/google':
            (new GoogleAuthController())->redirect();
            break;
        case 'auth/google/callback':
            (new GoogleAuthController())->callback();
            break;
        case 'postulacion/store':
            $postulacionController->store();
            break;
        case 'citas/store':
            $citaController->store();
            break;
        case 'citas/update':
            $citaController->update();
            break;
        case 'citas/delete':
            $citaController->cancel();
            break;
        case 'citas/cancel':
            $citaController->cancel();
            break;
        case 'admin/promote':
            $adminController->promote();
            break;
        case 'admin/ban':
            $adminController->banUser();
            break;
        case 'admin/activate':
            $adminController->activateUser();
            break;
        case 'admin/tutores/approve':
            $adminController->approveTutor();
            break;
        case 'admin/tutores/reject':
            $adminController->rejectTutor();
            break;
        case 'admin/tutores/delete':
            $adminController->deleteTutor();
            break;
        case 'admin/contenido/delete':
            $adminController->deleteContenido();
            break;
        case 'admin/contenido/approve':
            $adminController->approveContenido();
            break;
        case 'tutor/agenda':
            $tutorController->updateAgenda();
            break;
        case 'tutor/citas/update':
            $tutorController->updateCitaEstado();
            break;
        case 'tutor/contenido/store':
            $tutorController->contenidoStore();
            break;
        case 'tutor/contenido/update':
            $tutorController->contenidoUpdate();
            break;
        case 'tutor/contenido/delete':
            $tutorController->contenidoDelete();
            break;
        case 'tutores/rate':
            $tutorController->rate();
            break;
        case 'tutores/resena':
            $tutorController->resena();
            break;
        case 'tutores/unirse':
            $tutorController->unirse();
            break;
        case 'perfil/foto':
            (new PerfilController())->uploadFoto();
            break;
        case 'perfil/completar':
            (new PerfilController())->guardarPerfil();
            break;
        case 'notificaciones/get':
            (new NotificacionController())->getJson();
            break;
        case 'notificaciones/read-all':
            (new NotificacionController())->readAll();
            break;
        case 'notificaciones/read-one':
            (new NotificacionController())->readOne();
            break;
        case 'mensajes/send':
            (new MensajeController())->send();
            break;
        case 'mensajes/get':
            (new MensajeController())->getConversacion();
            break;
        case 'mensajes/conversaciones':
            (new MensajeController())->getConversaciones();
            break;
        case 'mensajes/unread':
            (new MensajeController())->getUnread();
            break;
        default:
            http_response_code(404);
            echo 'Accion no encontrada';
            exit;
    }
}

switch ($page) {
    case 'inicio':
        (new HomeController())->index();
        break;
    case 'tutores':
        (new TutorController())->index();
        break;
    case 'tutor':
        (new TutorController())->panel();
        break;
    case 'ser-tutor':
        (new PostulacionController())->form();
        break;
    case 'perfil':
        (new PerfilController())->index();
        break;
    case 'completar-perfil':
        (new PerfilController())->completarPerfil();
        break;
    case 'nueva-contrasena':
        (new PasswordController())->showResetForm();
        break;
    case 'perfil-tutor':
        (new TutorController())->perfilTutor();
        break;
    case 'contenido':
        (new ContenidoController())->index();
        break;
    case 'admin':
        (new AdminController())->index();
        break;
    case 'admin-tutores':
        (new AdminController())->tutorRequests();
        break;
    case 'admin-tutores-gestion':
        (new AdminController())->manageTutores();
        break;
    case 'admin-contenido':
        (new AdminController())->manageContenido();
        break;
    case 'admin-reportes':
        (new AdminController())->reportesCitas();
        break;
    case 'videollamada':
        (new VideollamadaController())->sala();
        break;
    case 'mensajes':
        (new MensajeController())->index();
        break;
    default:
        http_response_code(404);
        echo 'Pagina no encontrada';
        break;
}
