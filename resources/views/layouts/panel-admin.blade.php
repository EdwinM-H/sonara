@extends('layouts.panel', [
    'panel' => 'Panel de Administración',
    'sidebarItems' => [
        ['url' => route('admin.dashboard'), 'label' => 'Inicio', 'icon' => 'home', 'active' => 'admin.dashboard'],
        ['url' => route('admin.users'), 'label' => 'Usuarios', 'icon' => 'user', 'active' => 'admin.users'],
        ['url' => route('admin.entrepreneurs.index'), 'label' => 'Emprendedores', 'icon' => 'sparkles', 'active' => 'admin.entrepreneurs.*'],
        ['url' => route('admin.assisted.index'), 'label' => 'Registro asistido', 'icon' => 'mic', 'active' => 'admin.assisted.*'],
        ['url' => route('admin.businesses.index'), 'label' => 'Emprendimientos', 'icon' => 'tag', 'active' => 'admin.businesses.*'],
        ['url' => route('admin.publications.index'), 'label' => 'Publicaciones', 'icon' => 'box', 'active' => 'admin.publications.*'],
        ['url' => route('admin.categories.index'), 'label' => 'Categorías', 'icon' => 'grid', 'active' => 'admin.categories.*'],
        ['url' => route('admin.subcategories.index'), 'label' => 'Subcategorías', 'icon' => 'tag', 'active' => 'admin.subcategories.*'],
        ['url' => route('admin.requests'), 'label' => 'Solicitudes', 'icon' => 'clipboard', 'active' => 'admin.requests'],
        ['url' => route('admin.assistance.index'), 'label' => 'Asistencia', 'icon' => 'chat', 'active' => 'admin.assistance.*'],
        ['url' => route('admin.audit.index'), 'label' => 'Auditoría', 'icon' => 'document', 'active' => 'admin.audit.*'],
        ['url' => route('admin.settings.index'), 'label' => 'Configuración', 'icon' => 'cog', 'active' => 'admin.settings.*'],
        ['url' => route('admin.notifications.index'), 'label' => 'Notificaciones', 'icon' => 'bell', 'active' => 'admin.notifications.*'],
    ],
])