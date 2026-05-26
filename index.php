<?php
define('APP_STARTED', true);
session_start();

function h($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function db()
{
    static $pdo = null;

    if ($pdo === null) {
        $pdo = new PDO('sqlite:' . __DIR__ . DIRECTORY_SEPARATOR . 'contacts.sqlite');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS contacts (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                last_name TEXT NOT NULL,
                first_name TEXT NOT NULL,
                middle_name TEXT,
                gender TEXT NOT NULL,
                birth_date TEXT NOT NULL,
                phone TEXT,
                address TEXT,
                email TEXT,
                comment TEXT,
                created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
            )"
        );
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL UNIQUE,
                created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
            )"
        );

        $pdo->prepare('INSERT OR IGNORE INTO users (name) VALUES (:name)')->execute(array('name' => 'Гость'));

        $columns = $pdo->query('PRAGMA table_info(contacts)')->fetchAll(PDO::FETCH_ASSOC);
        $hasAuthorId = false;
        foreach ($columns as $column) {
            if ($column['name'] === 'author_id') {
                $hasAuthorId = true;
                break;
            }
        }

        if (!$hasAuthorId) {
            $pdo->exec('ALTER TABLE contacts ADD COLUMN author_id INTEGER');
        }

        $guestId = (int)$pdo->query("SELECT id FROM users WHERE name = 'Гость'")->fetchColumn();
        $stmt = $pdo->prepare('UPDATE contacts SET author_id = :author_id WHERE author_id IS NULL');
        $stmt->execute(array('author_id' => $guestId));
    }

    return $pdo;
}

function current_user_name()
{
    $name = isset($_SESSION['user_name']) ? trim((string)$_SESSION['user_name']) : '';

    return $name === '' ? 'Гость' : $name;
}

function user_id_by_name($name)
{
    $name = trim((string)$name);
    $name = $name === '' ? 'Гость' : $name;

    $stmt = db()->prepare('INSERT OR IGNORE INTO users (name) VALUES (:name)');
    $stmt->execute(array('name' => $name));

    $stmt = db()->prepare('SELECT id FROM users WHERE name = :name');
    $stmt->execute(array('name' => $name));

    return (int)$stmt->fetchColumn();
}

function current_user_id()
{
    return user_id_by_name(current_user_name());
}

function current_action()
{
    if (route_hello_name() !== null) {
        return 'hello';
    }

    if (route_bye_name() !== null) {
        return 'bye';
    }

    $allowed = array('view', 'add', 'edit', 'delete', 'hello', 'bye');
    $action = isset($_GET['action']) ? $_GET['action'] : 'view';

    return in_array($action, $allowed, true) ? $action : 'view';
}

function current_route_path()
{
    $path = parse_url(isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '', PHP_URL_PATH);
    if ($path === null) {
        return '';
    }

    $basePath = rtrim(str_replace('\\', '/', dirname(isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '')), '/');
    if ($basePath !== '' && $basePath !== '/' && strpos($path, $basePath) === 0) {
        $path = substr($path, strlen($basePath));
    }

    return $path;
}

function route_hello_name()
{
    $path = current_route_path();

    if (preg_match('#^/hello(?:/([^/]+))?/?$#u', $path, $matches)) {
        return isset($matches[1]) ? rawurldecode($matches[1]) : '';
    }

    return null;
}

function route_bye_name()
{
    $path = current_route_path();

    if (preg_match('#^/bye(?:/([^/]*))?/?$#u', $path, $matches)) {
        return isset($matches[1]) ? rawurldecode($matches[1]) : '';
    }

    return null;
}

function sayHello($name)
{
    $name = trim((string)$name);
    $html = '<section class="bye-message"><p>Вход</p>';
    $html .= '<form class="login-form is-open" method="post" action="index.php">';
    $html .= '<input type="text" name="user_name" placeholder="Введите имя" value="' . h($name) . '">';
    $html .= '<button class="form-btn" type="submit">Войти</button>';
    $html .= '</form>';
    $html .= '</section>';

    return $html;
}

function sayBye($name)
{
    $name = trim((string)$name);
    if ($name === '' && isset($_SESSION['user_name'])) {
        $name = trim((string)$_SESSION['user_name']);
    }

    $isGuest = $name === '';
    $name = $isGuest ? 'Гость' : h($name);

    $html = '<section class="bye-message"><p>Пока, ' . $name . '</p>';
    $html .= '<a class="form-btn login-link" href="index.php?action=hello">Войти</a>';
    $html .= '</section>';

    return $html;
}

function current_sort()
{
    $allowed = array('added', 'last_name', 'birth_date');
    $sort = isset($_GET['sort']) ? $_GET['sort'] : 'added';

    return in_array($sort, $allowed, true) ? $sort : 'added';
}

function current_page()
{
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

    return max(1, $page);
}

function contact_fields()
{
    return array(
        'last_name' => 'Фамилия',
        'first_name' => 'Имя',
        'middle_name' => 'Отчество',
        'gender' => 'Пол',
        'birth_date' => 'Дата рождения',
        'phone' => 'Телефон',
        'address' => 'Адрес',
        'email' => 'E-mail',
        'comment' => 'Комментарий',
    );
}

function read_contact_from_post()
{
    $data = array();
    foreach (array_keys(contact_fields()) as $field) {
        $data[$field] = isset($_POST[$field]) ? trim($_POST[$field]) : '';
    }

    return $data;
}

function contact_is_valid($data)
{
    return $data['last_name'] !== ''
        && $data['first_name'] !== ''
        && $data['gender'] !== ''
        && $data['birth_date'] !== '';
}

function first_utf8_char($value)
{
    return preg_match('/^./us', $value, $matches) ? $matches[0] : '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_name'])) {
    $userName = trim((string)$_POST['user_name']);
    $_SESSION['user_name'] = $userName;
    user_id_by_name($userName);

    header('Location: index.php');
    exit;
}

require_once __DIR__ . '/menu.php';
require_once __DIR__ . '/viewer.php';
require_once __DIR__ . '/add.php';
require_once __DIR__ . '/edit.php';
require_once __DIR__ . '/delete.php';

$action = current_action();
$hasUserName = array_key_exists('user_name', $_SESSION);
$isStartPage = (current_route_path() === '/' || current_route_path() === '/index.php') && !isset($_GET['action']);
if (!$hasUserName && $action === 'view' && $isStartPage) {
    $action = 'hello';
}

$content = '';
$title = null;
$blogTitle = isset($_SESSION['user_name']) && trim((string)$_SESSION['user_name']) !== ''
    ? trim((string)$_SESSION['user_name'])
    : 'Мой блог';

if ($action === 'hello') {
    $title = 'Страница приветствия';
    $content = sayHello(route_hello_name());
} elseif ($action === 'bye') {
    $content = sayBye(route_bye_name());
} elseif ($action === 'add') {
    $content = render_add();
} elseif ($action === 'edit') {
    $content = render_edit();
} elseif ($action === 'delete') {
    $content = render_delete();
} else {
    $content = render_viewer(current_sort(), current_page());
}

$pageTitle = isset($title) && trim((string)$title) !== '' ? $title : 'Мой блог';
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo h($pageTitle); ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body<?php echo ($action === 'bye' || $action === 'hello') ? ' class="bye-page"' : ''; ?>>
    <main>
        <?php if ($action !== 'bye' && $action !== 'hello') { ?>
        <div class="blog-title"><?php echo h($blogTitle); ?></div>
        <h1>Записная книжка</h1>
        <?php echo main_menu(); ?>
        <?php } ?>
        <?php echo $content; ?>
    </main>
</body>
</html>
