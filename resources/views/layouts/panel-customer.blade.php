@extends('layouts.panel', [
    'panel' => 'Panel del Cliente',
    'sidebarItems' => [
        ['url' => route('customer.dashboard'), 'label' => 'Inicio', 'icon' => 'home', 'active' => 'customer.dashboard'],
        ['url' => route('public.explore'), 'label' => 'Explorar', 'icon' => 'search', 'active' => 'public.explore'],
        ['url' => route('profile.edit'), 'label' => 'Mi perfil', 'icon' => 'user', 'active' => 'profile.edit'],
    ],
])