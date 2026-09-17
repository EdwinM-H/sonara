<?php
$css = file_get_contents('resources/css/app.css');
$root = 'resources/views';
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
$bare = false;
$noProto = $safe = $skipLink = false;

function check($label, $haystack, $pat) {
    echo sprintf("  [%s] %s\n", preg_match($pat, $haystack) ? 'OK ' : 'FALTA', $label);
}
echo "=== AUDITORÍA INFRAESTRUCTURA (app.css global => se propaga a TODAS las vistas) ===\n";
check('Focus visible ring global', $css, '/:focus-visible/');
check('Focus-visible util', $css, '/focus-visible/');
check('focus ring en btn/inputs', $css, '/focus:ring|focus-visible/');
check('Reduced motion (prefers-reduced-motion)', $css, '/prefers-reduced-motion/');
check('Alto contraste (high-contrast body)', $css, '/high-contrast/');
check('Skeleton loading', $css, '/\.skeleton/');
check('Shimmer keyframes', $css, '/shimmer/');
check('Empty state', $css, '/\.empty-state/');
check('Sky/error-message', $css, '/error-message/');
check('Badges status (badge-success/warning/error/info)', $css, '/badge-(success|warning|error|info)/');
check('Chips', $css, '/\.chip/');
check('Sombras tokens (--shadow-soft/lift/fab)', $css, '/--shadow-(soft|lift|fab)/');
check('Radios tokens (--radius-sm/md/lg/xl)', $css, '/--radius-(sm|md|lg|xl)/');
check('Colores primarios tokens', $css, '/--color-primary(-dark|-soft)?/');
check('Tipografía Plus Jakarta Sans', $css, '/Plus Jakarta Sans/');
check('Animación fadeIn', $css, '/fadeIn/');
check('Animación voice-pulse', $css, '/voice-pulse/');
check('transition-colors/duration en btn', $css, '/transition-|duration-150/');
check('Hover card translate', $css, '/card-hover/');
check('Sticky header', $css, '/sticky top-0/');
check('Nav móvil inferior (nav-bottom)', $css, '/nav-bottom/');
check('Boton flotante voz (voice-fab)', $css, '/voice-fab/');
check('Alpine x-cloak', $css, '/x-cloak/');

echo "\n=== AUDITORÍA VISTAS (elementos presentes en la mayoría) ===\n";
$t0 = ['total' => 0, 'conCloak' => 0, 'conSkip' => 0, 'conAria' => 0, 'conAlt' => 0];
foreach ($it as $f) {
    if ($f->getExtension() !== 'php') continue;
    $t0['total']++;
    $t = file_get_contents($f->getPathname());
    if (str_contains($t, 'x-cloak')) $t0['conCloak']++;
    if (str_contains($t, 'skip-link')) $t0['conSkip']++;
    if (preg_match('/\b(role=|aria-label=|aria-labelledby=|aria-hidden)/', $t)) $t0['conAria']++;
    if (preg_match('/<img\b[^>]*\balt=/', $t)) $t0['conAlt']++;
}
foreach ($t0 as $k => $v) printf("  %-26s %s/%d\n", $k, $v, $t0['total']);

echo "\n=== COMPONENTES DE DISEÑO EN USO (recount) ===\n";
$c = ['x-panel-header' => 0, 'x-card' => 0, 'x-stat-card' => 0, 'x-publication-card' => 0, 'x-status-badge' => 0, 'x-icon' => 0];
$it2 = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
foreach ($it2 as $f) {
    if ($f->getExtension() !== 'php') continue;
    $t = file_get_contents($f->getPathname());
    foreach ($c as $k => $v) {
        $c[$k] += substr_count($t, '<' . $k);
    }
}
foreach ($c as $k => $v) printf("  %-22s %d\n", $k, $v Tigr);
