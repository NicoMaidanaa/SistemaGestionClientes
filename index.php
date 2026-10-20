<?php
// ============================================
// SISTEMA SIMPLE DE GESTIÓN DE CLIENTES
// ============================================

// 1. Conexión a SQLite (crea el archivo si no existe)
try {
    $db = new PDO('sqlite:clientes.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

// 2. Crear la tabla si no existe
$db->exec("CREATE TABLE IF NOT EXISTS clientes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nombre TEXT NOT NULL,
    email TEXT,
    telefono TEXT,
    direccion TEXT,
    fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP
)");

// 3. Procesar acciones (crear, editar, eliminar)
$mensaje = '';

// CREAR nuevo cliente
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'crear') {
    $nombre = trim($_POST['nombre']);
    $email = trim($_POST['email']);
    $telefono = trim($_POST['telefono']);
    $direccion = trim($_POST['direccion']);

    if ($nombre !== '') {
        $stmt = $db->prepare("INSERT INTO clientes (nombre, email, telefono, direccion) VALUES (?, ?, ?, ?)");
        $stmt->execute([$nombre, $email, $telefono, $direccion]);
        $mensaje = "✅ Cliente agregado correctamente";
    } else {
        $mensaje = "❌ El nombre es obligatorio";
    }
}

// EDITAR cliente
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'editar') {
    $id = $_POST['id'];
    $nombre = trim($_POST['nombre']);
    $email = trim($_POST['email']);
    $telefono = trim($_POST['telefono']);
    $direccion = trim($_POST['direccion']);

    if ($nombre !== '') {
        $stmt = $db->prepare("UPDATE clientes SET nombre=?, email=?, telefono=?, direccion=? WHERE id=?");
        $stmt->execute([$nombre, $email, $telefono, $direccion, $id]);
        $mensaje = "✅ Cliente actualizado";
    }
}

// ELIMINAR cliente
if (isset($_GET['eliminar'])) {
    $id = $_GET['eliminar'];
    $stmt = $db->prepare("DELETE FROM clientes WHERE id=?");
    $stmt->execute([$id]);
    $mensaje = "🗑️ Cliente eliminado";
}

// Buscar cliente a editar
$cliente_editar = null;
if (isset($_GET['editar'])) {
    $stmt = $db->prepare("SELECT * FROM clientes WHERE id=?");
    $stmt->execute([$_GET['editar']]);
    $cliente_editar = $stmt->fetch(PDO::FETCH_ASSOC);
}

// 4. Obtener todos los clientes
// 4. Obtener clientes (con búsqueda opcional)
$busqueda = trim($_GET['buscar'] ?? '');

if ($busqueda !== '') {
    $stmt = $db->prepare("SELECT * FROM clientes
        WHERE nombre LIKE ? OR email LIKE ? OR telefono LIKE ?
        ORDER BY id DESC");
    $like = "%$busqueda%";
    $stmt->execute([$like, $like, $like]);
    $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $clientes = $db->query("SELECT * FROM clientes ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Gestión de Clientes</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f0f2f5;
            padding: 20px;
            color: #333;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        h1 {
            color: #2c3e50;
            margin-bottom: 20px;
        }

        .mensaje {
            padding: 10px 15px;
            background: #e8f5e9;
            border-left: 4px solid #4caf50;
            margin-bottom: 20px;
            border-radius: 4px;
        }

        form {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        form .full {
            grid-column: 1 / -1;
        }

        label {
            display: block;
            font-weight: 600;
            margin-bottom: 5px;
            font-size: 14px;
        }

        input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }

        input:focus {
            outline: none;
            border-color: #3498db;
        }

        button {
            padding: 10px 20px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
        }

        button:hover {
            background: #2980b9;
        }

        button.cancelar {
            background: #95a5a6;
        }

        button.cancelar:hover {
            background: #7f8c8d;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th,
        td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        th {
            background: #34495e;
            color: white;
        }

        tr:hover {
            background: #f8f9fa;
        }

        .acciones a {
            display: inline-block;
            padding: 5px 10px;
            margin-right: 5px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 13px;
            color: white;
        }

        .editar {
            background: #f39c12;
        }

        .eliminar {
            background: #e74c3c;
        }

        .editar:hover {
            background: #d68910;
        }

        .eliminar:hover {
            background: #c0392b;
        }

        .vacio {
            text-align: center;
            padding: 30px;
            color: #888;
        }

        form.buscador {
            display: flex;
            grid-template-columns: none;
            gap: 10px;
            padding: 15px;
            margin-bottom: 20px;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>👥 Gestión de Clientes</h1>

        <?php if ($mensaje): ?>
            <div class="mensaje"><?= $mensaje ?></div>
        <?php endif; ?>

        <!-- Formulario -->
        <form method="POST">
            <input type="hidden" name="accion" value="<?= $cliente_editar ? 'editar' : 'crear' ?>">
            <?php if ($cliente_editar): ?>
                <input type="hidden" name="id" value="<?= $cliente_editar['id'] ?>">
            <?php endif; ?>

            <div>
                <label>Nombre *</label>
                <input type="text" name="nombre" required value="<?= $cliente_editar['nombre'] ?? '' ?>">
            </div>
            <div>
                <label>Email</label>
                <input type="email" name="email" value="<?= $cliente_editar['email'] ?? '' ?>">
            </div>
            <div>
                <label>Telefono</label>
                <input type="text" name="telefono" value="<?= $cliente_editar['telefono'] ?? '' ?>">
            </div>
            <div>
                <label>Direccion</label>
                <input type="text" name="direccion" value="<?= $cliente_editar['direccion'] ?? '' ?>">
            </div>
            <div class="full">
                <button type="submit"><?= $cliente_editar ? 'Actualizar' : 'Agregar' ?></button>
                <?php if ($cliente_editar): ?>
                    <a href="index.php"><button type="button" class="cancelar">Cancelar</button></a>
                <?php endif; ?>
            </div>
        </form>
        <form method="GET" class="buscador">
            <input type="text" name="buscar" placeholder="Buscar por nombre, email o telefono..."
                value="<?= htmlspecialchars($busqueda) ?>">
            <button type="submit">Buscar</button>
            <?php if ($busqueda !== ''): ?>
                <a href="index.php"><button type="button" class="cancelar">Limpiar</button></a>
            <?php endif; ?>
        </form>
        <!-- Tabla de clientes -->
        <h2 style="margin-bottom: 10px;">Listado de Clientes (<?= count($clientes) ?>)</h2>

        <?php if (count($clientes) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nombre</th>
                        <th>Email</th>
                        <th>Telefono</th>
                        <th>Direccion</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($clientes as $c): ?>
                        <tr>
                            <td><?= $c['id'] ?></td>
                            <td><?= htmlspecialchars($c['nombre']) ?></td>
                            <td><?= htmlspecialchars($c['email']) ?></td>
                            <td><?= htmlspecialchars($c['telefono']) ?></td>
                            <td><?= htmlspecialchars($c['direccion']) ?></td>
                            <td class="acciones">
                                <a href="?editar=<?= $c['id'] ?>" class="editar">Editar</a>
                                <a href="?eliminar=<?= $c['id'] ?>" class="eliminar"
                                    onclick="return confirm('Eliminar este cliente?')">Eliminar</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="vacio">
                <?= $busqueda !== ''
                    ? 'No se encontraron clientes para "' . htmlspecialchars($busqueda) . '"'
                    : 'No hay clientes registrados. Agrega el primero!' ?>
            </div>
        <?php endif; ?>
    </div>
</body>

</html>