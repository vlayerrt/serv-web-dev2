<?php
if (!defined('APP_STARTED')) {
    http_response_code(403);
    exit('Доступ запрещен');
}

function render_viewer($sort, $page)
{
    $orders = array(
        'added' => 'id ASC',
        'last_name' => 'last_name ASC, first_name ASC, id ASC',
        'birth_date' => 'birth_date ASC, last_name ASC, first_name ASC',
    );
    $order = isset($orders[$sort]) ? $orders[$sort] : $orders['added'];
    $perPage = 10;
    $total = (int)db()->query('SELECT COUNT(*) FROM contacts')->fetchColumn();
    $totalPages = max(1, (int)ceil($total / $perPage));
    $page = min(max(1, (int)$page), $totalPages);
    $offset = ($page - 1) * $perPage;

    $stmt = db()->prepare("SELECT * FROM contacts ORDER BY $order LIMIT :limit OFFSET :offset");
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$contacts) {
        return '<div>Записей пока нет.</div>';
    }

    $html = '<table><thead><tr>';
    foreach (contact_fields() as $title) {
        $html .= '<th>' . h($title) . '</th>';
    }
    $html .= '</tr></thead><tbody>';

    foreach ($contacts as $contact) {
        $html .= '<tr>';
        foreach (array_keys(contact_fields()) as $field) {
            $html .= '<td>' . nl2br(h($contact[$field])) . '</td>';
        }
        $html .= '</tr>';
    }

    $html .= '</tbody></table>';

    if ($totalPages > 1) {
        $html .= '<nav class="submenu">';
        for ($i = 1; $i <= $totalPages; $i++) {
            $active = $i === $page ? ' class="select"' : '';
            $html .= '<a' . $active . ' href="index.php?action=view&sort=' . h($sort) . '&page=' . $i . '">' . $i . '</a>';
        }
        $html .= '</nav>';
    }

    return $html;
}
