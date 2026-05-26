<?php
if (!defined('APP_STARTED')) {
    http_response_code(403);
    exit('Доступ запрещен');
}

function render_viewer($sort, $page)
{
    $orders = array(
        'added' => 'contacts.id ASC',
        'last_name' => 'contacts.last_name ASC, contacts.first_name ASC, contacts.id ASC',
        'birth_date' => 'contacts.birth_date ASC, contacts.last_name ASC, contacts.first_name ASC',
    );
    $order = isset($orders[$sort]) ? $orders[$sort] : $orders['added'];
    $perPage = 10;
    $showAuthors = isset($_GET['show_authors']) && $_GET['show_authors'] === '1';
    $authorId = isset($_GET['author_id']) ? (int)$_GET['author_id'] : 0;
    if ($authorId > 0) {
        $showAuthors = true;
    }

    $where = '';
    $params = array();
    if ($authorId > 0) {
        $where = ' WHERE contacts.author_id = :author_id';
        $params['author_id'] = $authorId;
    }

    $countStmt = db()->prepare('SELECT COUNT(*) FROM contacts' . $where);
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();
    $totalPages = max(1, (int)ceil($total / $perPage));
    $page = min(max(1, (int)$page), $totalPages);
    $offset = ($page - 1) * $perPage;

    $stmt = db()->prepare(
        "SELECT contacts.*, users.name AS author_name
        FROM contacts
        LEFT JOIN users ON users.id = contacts.author_id
        $where
        ORDER BY $order
        LIMIT :limit OFFSET :offset"
    );
    foreach ($params as $key => $value) {
        $stmt->bindValue(':' . $key, $value, PDO::PARAM_INT);
    }
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $html = '<nav class="submenu">';
    if ($showAuthors) {
        $html .= '<a href="index.php?action=view&sort=' . h($sort) . '">Скрыть авторов</a>';
    } else {
        $html .= '<a href="index.php?action=view&sort=' . h($sort) . '&show_authors=1">Показать авторов</a>';
    }
    if ($authorId > 0) {
        $html .= '<a href="index.php?action=view&sort=' . h($sort) . '&show_authors=1">Показать все статьи</a>';
    }
    $html .= '</nav>';

    if (!$contacts) {
        return $html . '<div>Записей пока нет.</div>';
    }

    $html .= '<table><thead><tr>';
    foreach (contact_fields() as $title) {
        $html .= '<th>' . h($title) . '</th>';
    }
    if ($showAuthors) {
        $html .= '<th>Автор</th>';
    }
    $html .= '</tr></thead><tbody>';

    foreach ($contacts as $contact) {
        $html .= '<tr>';
        foreach (array_keys(contact_fields()) as $field) {
            $html .= '<td>' . nl2br(h($contact[$field])) . '</td>';
        }
        if ($showAuthors) {
            $authorName = isset($contact['author_name']) && $contact['author_name'] !== '' ? $contact['author_name'] : 'Гость';
            $html .= '<td><a href="index.php?action=view&sort=' . h($sort) . '&author_id=' . (int)$contact['author_id'] . '&show_authors=1">' . h($authorName) . '</a></td>';
        }
        $html .= '</tr>';
    }

    $html .= '</tbody></table>';

    if ($totalPages > 1) {
        $html .= '<nav class="submenu">';
        for ($i = 1; $i <= $totalPages; $i++) {
            $active = $i === $page ? ' class="select"' : '';
            $href = 'index.php?action=view&sort=' . h($sort) . '&page=' . $i;
            if ($showAuthors) {
                $href .= '&show_authors=1';
            }
            if ($authorId > 0) {
                $href .= '&author_id=' . $authorId;
            }
            $html .= '<a' . $active . ' href="' . $href . '">' . $i . '</a>';
        }
        $html .= '</nav>';
    }

    return $html;
}
