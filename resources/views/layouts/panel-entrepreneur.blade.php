@extends('layouts.panel', [
    'panel' => 'Panel del Emprendedor',
    'sidebarItems' => [
        ['url' => route('entrepreneur.dashboard'), 'label' => 'Inicio', 'icon' => 'home', 'active' => 'entrepreneur.dashboard'],
        ['url' => route('entrepreneur.profile'), 'label' => 'Mi perfil', 'icon' => 'user', 'active' => 'entrepreneur.profile'],
        ['url' => route('entrepreneur.businesses.index'), 'label' => 'Emprendimientos', 'icon' => 'tag', 'active' => 'entrepreneur.businesses.*'],
        ['url' => route('entrepreneur.publications.index'), 'label' => 'Productos y servicios', 'icon' => 'box', 'active' => 'entrepreneur.publications.*'],
        ['url' => route('entrepreneur.flyers.history'), 'label' => 'Mis flyers IA', 'icon' => 'sparkles', 'active' => 'entrepreneur.flyers.*'],
        ['url' => route('entrepreneur.requests.index'), 'label' => 'Solicitudes recibidas', 'icon' => 'clipboard', 'active' => 'entrepreneur.requests.*'],
        ['url' => route('entrepreneur.documents.index'), 'label' => 'Documentación', 'icon' => 'document', 'active' => 'entrepreneur.documents.*'],
        ['url' => route('entrepreneur.assistance.index'), 'label' => 'Asistencia', 'icon' => 'chat', 'active' => 'entrepreneur.assistance.*'],
        ['url' => route('entrepreneur.accessibility'), 'label' => 'Accesibilidad', 'icon' => 'eye', 'active' => 'entrepreneur.accessibility'],
        ['url' => route('entrepreneur.notifications.index'), 'label' => 'Notificaciones', 'icon' => 'bell', 'active' => 'entrepreneur.notifications.*'],
    ],
])