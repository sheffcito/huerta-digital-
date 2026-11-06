<?php
// ==============================================================================
// LA HUERTA DIGITAL 2.0 - Catálogo de Productores Agrícolas
// Arquitectura Distribuida en Contenedores | Almacenamiento Persistente VDI
// ==============================================================================
header('Content-Type: text/html; charset=UTF-8');

$db_host = getenv('DB_HOST') ?: 'db';
$db_name = getenv('DB_NAME') ?: 'HUERTA_DIGITAL';
$db_user = getenv('DB_USER') ?: 'huerta_user';
$db_pass = getenv('DB_PASSWORD') ?: 'huerta_password';

$db_connected = false;
$error_message = '';
$productos = [];
$stats = ['total' => 0, 'stock' => 0];

try {
    $dsn = "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4";
    $pdo = new PDO($dsn, $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT => 2
    ]);
    $pdo->exec("SET NAMES utf8mb4");
    $db_connected = true;

    $filtro = isset($_GET['productor']) ? trim($_GET['productor']) : '';

    if (!empty($filtro) && $filtro !== 'todos') {
        $stmt = $pdo->prepare("SELECT id, nombre, productor, precio, stock, imagen FROM productos WHERE productor = :prod ORDER BY id ASC");
        $stmt->execute([':prod' => $filtro]);
    } else {
        $stmt = $pdo->query("SELECT id, nombre, productor, precio, stock, imagen FROM productos ORDER BY id ASC");
    }
    $productos = $stmt->fetchAll();

    $stmt_count = $pdo->query("SELECT COUNT(*) as t, SUM(stock) as s FROM productos");
    $stats = $stmt_count->fetch();

} catch (PDOException $e) {
    $error_message = $e->getMessage();
}

$productores = ['Don Pedro', 'Doña Rosa', 'Don Manuel', 'Doña Carmen'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>La Huerta Digital 2.0 — Catálogo Oficial</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #fafafa;
            --surface: #ffffff;
            --border: #e5e7eb;
            --border-subtle: #f3f4f6;
            --text-main: #111827;
            --text-muted: #6b7280;
            --accent: #15803d;
            --accent-soft: #f0fdf4;
            --danger: #b91c1c;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background-color: var(--bg);
            color: var(--text-main);
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
        }

        /* BARRA DE NAVEGACIÓN MINIMALISTA */
        header {
            background: var(--surface);
            border-bottom: 1px solid var(--border);
            position: sticky;
            top: 0;
            z-index: 20;
        }

        .header-content {
            max-width: 1100px;
            margin: 0 auto;
            padding: 1rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .brand-title {
            font-size: 1.05rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            color: var(--text-main);
        }

        .brand-tag {
            font-size: 0.7rem;
            font-weight: 600;
            background: var(--accent-soft);
            color: var(--accent);
            padding: 0.2rem 0.5rem;
            border-radius: 4px;
            border: 1px solid #bbf7d0;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .system-status {
            display: flex;
            align-items: center;
            gap: 1.25rem;
            font-size: 0.8rem;
            color: var(--text-muted);
        }

        .status-pill {
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }

        .dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #22c55e;
        }

        .dot-error { background: var(--danger); }

        /* CONTENIDO PRINCIPAL */
        main {
            max-width: 1100px;
            margin: 0 auto;
            padding: 2.5rem 1.5rem 4rem 1.5rem;
        }

        .section-header {
            margin-bottom: 2rem;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .section-header h1 {
            font-size: 1.6rem;
            font-weight: 700;
            letter-spacing: -0.03em;
        }

        .section-header p {
            color: var(--text-muted);
            font-size: 0.92rem;
        }

        /* FILTROS LIMPIOS */
        .filters {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-bottom: 2rem;
            padding-bottom: 1.25rem;
            border-bottom: 1px solid var(--border);
        }

        .filter-btn {
            font-size: 0.82rem;
            font-weight: 500;
            padding: 0.45rem 0.9rem;
            border-radius: 6px;
            text-decoration: none;
            color: var(--text-muted);
            background: var(--surface);
            border: 1px solid var(--border);
            transition: all 0.15s ease;
        }

        .filter-btn:hover {
            color: var(--text-main);
            border-color: #9ca3af;
        }

        .filter-btn.active {
            background: var(--text-main);
            color: #ffffff;
            border-color: var(--text-main);
        }

        /* GRILLA DE PRODUCTOS ESTILO EDITORIAL */
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(310px, 1fr));
            gap: 1.5rem;
        }

        .card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 8px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .card:hover {
            border-color: #cbd5e1;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04);
        }

        .card-media {
            width: 100%;
            height: 200px;
            background: #f3f4f6;
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            border-bottom: 1px solid var(--border-subtle);
        }

        .card-media img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .media-placeholder {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            color: #9ca3af;
            font-size: 0.78rem;
            text-align: center;
            padding: 1rem;
        }

        .media-placeholder svg {
            width: 28px;
            height: 28px;
            stroke: currentColor;
            stroke-width: 1.5;
        }

        .card-body {
            padding: 1.25rem;
            flex: 1;
        }

        .card-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
            font-size: 0.75rem;
        }

        .producer-label {
            font-weight: 600;
            color: var(--accent);
            letter-spacing: 0.02em;
        }

        .sku-label {
            color: #9ca3af;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        }

        .card-title {
            font-size: 1.05rem;
            font-weight: 600;
            color: var(--text-main);
            letter-spacing: -0.01em;
            margin-bottom: 0.25rem;
        }

        .card-footer {
            padding: 1rem 1.25rem;
            background: #fafbfc;
            border-top: 1px solid var(--border-subtle);
            display: flex;
            justify-content: space-between;
            align-items: baseline;
        }

        .price-label {
            font-size: 0.72rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            display: block;
        }

        .price-value {
            font-size: 1.15rem;
            font-weight: 700;
            color: var(--text-main);
            letter-spacing: -0.02em;
        }

        .stock-tag {
            font-size: 0.78rem;
            color: var(--text-muted);
            font-weight: 500;
        }

        /* ALERTA DE ERROR */
        .error-panel {
            background: #fff5f5;
            border: 1px solid #fed7d7;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .error-panel h3 {
            color: var(--danger);
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .error-panel p {
            color: #742a2a;
            font-size: 0.85rem;
        }

        /* PIE DE PÁGINA */
        footer {
            border-top: 1px solid var(--border);
            background: var(--surface);
            padding: 2rem 1.5rem;
            font-size: 0.8rem;
            color: var(--text-muted);
        }

        .footer-content {
            max-width: 1100px;
            margin: 0 auto;
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
        }

        .footer-links a {
            color: var(--text-muted);
            text-decoration: none;
            margin-left: 1rem;
            transition: color 0.15s;
        }

        .footer-links a:hover {
            color: var(--text-main);
        }
    </style>
</head>
<body>

    <!-- NAVEGACIÓN -->
    <header>
        <div class="header-content">
            <div class="brand">
                <span class="brand-title">La Huerta Digital</span>
                <span class="brand-tag">Versión 2.0</span>
            </div>
            <div class="system-status">
                <div class="status-pill">
                    <span class="dot <?= $db_connected ? '' : 'dot-error' ?>"></span>
                    <span>MySQL: <?= $db_connected ? 'En línea' : 'Sin conexión' ?></span>
                </div>
                <div class="status-pill">
                    <span class="dot"></span>
                    <span>VDI: /mnt/huerta_fotos</span>
                </div>
            </div>
        </div>
    </header>

    <!-- CUERPO PRINCIPAL -->
    <main>
        <?php if (!$db_connected): ?>
            <div class="error-panel">
                <h3>Error de Conexión a Base de Datos</h3>
                <p><?= htmlspecialchars($error_message) ?></p>
            </div>
        <?php endif; ?>

        <div class="section-header">
            <h1>Catálogo de Productos</h1>
            <p>Inventario agrícola en tiempo real disponible para la red del aula.</p>
        </div>

        <!-- FILTROS POR PRODUCTOR -->
        <div class="filters">
            <a href="index.php" class="filter-btn <?= empty($filtro) || $filtro === 'todos' ? 'active' : '' ?>">Todos los productos</a>
            <?php foreach ($productores as $p): ?>
                <a href="index.php?productor=<?= urlencode($p) ?>" class="filter-btn <?= $filtro === $p ? 'active' : '' ?>">
                    <?= htmlspecialchars($p) ?>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- GRILLA DE PRODUCTOS -->
        <div class="grid">
            <?php foreach ($productos as $item): ?>
                <?php 
                    $ruta_foto = 'imagenes/' . $item['imagen'];
                    $existe_foto = file_exists('/var/www/html/' . $ruta_foto);
                ?>
                <article class="card">
                    <div>
                        <div class="card-media">
                            <?php if ($existe_foto): ?>
                                <?php $v = filemtime('/var/www/html/' . $ruta_foto); ?>
                                <img src="<?= htmlspecialchars($ruta_foto) ?>?v=<?= $v ?>" alt="<?= htmlspecialchars($item['nombre']) ?>" onerror="this.onerror=null; this.parentElement.innerHTML='<div class=\'media-placeholder\'><svg viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'currentColor\'><rect x=\'3\' y=\'3\' width=\'18\' height=\'18\' rx=\'2\' ry=\'2\'></rect><circle cx=\'8.5\' cy=\'8.5\' r=\'1.5\'></circle><polyline points=\'21 15 16 10 5 21\'></polyline></svg><span>Foto no accesible (verifique permisos)</span></div>';">
                            <?php else: ?>
                                <div class="media-placeholder">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                        <circle cx="8.5" cy="8.5" r="1.5"></circle>
                                        <polyline points="21 15 16 10 5 21"></polyline>
                                    </svg>
                                    <span><?= htmlspecialchars($item['imagen']) ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <div class="card-meta">
                                <span class="producer-label"><?= htmlspecialchars($item['productor']) ?></span>
                                <span class="sku-label">#00<?= $item['id'] ?></span>
                            </div>
                            <h2 class="card-title"><?= htmlspecialchars($item['nombre']) ?></h2>
                        </div>
                    </div>
                    <div class="card-footer">
                        <div>
                            <span class="price-label">Precio</span>
                            <span class="price-value">$<?= number_format($item['precio'], 0, ',', '.') ?> <span style="font-size:0.75rem;font-weight:400;color:var(--text-muted)">CLP</span></span>
                        </div>
                        <span class="stock-tag">Stock: <?= $item['stock'] ?> un.</span>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </main>

    <!-- PIE DE PÁGINA -->
    <footer>
        <div class="footer-content">
            <div>
                <span>La Huerta Digital 2.0 &bull; Ubuntu Server &bull; Docker Compose</span>
            </div>
            <div class="footer-links">
                <a href="http://<?= $_SERVER['SERVER_NAME'] ?>:8080" target="_blank">phpMyAdmin</a>
                <a href="http://<?= $_SERVER['SERVER_NAME'] ?>:3001" target="_blank">Uptime Kuma</a>
            </div>
        </div>
    </footer>

</body>
</html>
