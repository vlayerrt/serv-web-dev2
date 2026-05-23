<?php
if (!defined('APP_STARTED')) {
    http_response_code(403);
    exit('Доступ запрещен');
}

function render_delete()
{
    $message = '';

    if (isset($_GET['delete_id'])) {
        $id = (int)$_GET['delete_id'];
        $stmt = db()->prepare('SELECT last_name FROM contacts WHERE id = :id');
        $stmt->execute(array('id' => $id));
        $contact = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($contact) {
            $delete = db()->prepare('DELETE FROM contacts WHERE id = :id');
            $delete->execute(array('id' => $id));
            $message = '<p class="success">Запись с фамилией ' . h($contact['last_name']) . ' удалена</p>';
        }
    }

    $contacts = db()->query('SELECT id, last_name, first_name, middle_name FROM contacts ORDER BY last_name ASC, first_name ASC, id ASC')->fetchAll(PDO::FETCH_ASSOC);

    if (!$contacts) {
        return $message . '<div>Нет записей для удаления.</div>';
    }

    $selectedId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $selected = null;
    foreach ($contacts as $contact) {
        if ((int)$contact['id'] === $selectedId) {
            $selected = $contact;
            break;
        }
    }

    if ($selected === null) {
        $selectedId = 0;
    }

    $html = '<form class="edit-choice" method="get" action="index.php">';
    $html .= '<input type="hidden" name="action" value="delete">';
    $html .= '<label for="delete-id">Выберите запись</label>';
    $html .= '<select id="delete-id" name="id" onchange="this.form.submit()">';
    $html .= '<option value="">-- Выбор записи --</option>';
    foreach ($contacts as $contact) {
        $id = (int)$contact['id'];
        $selectedAttr = $id === $selectedId ? ' selected' : '';
        $text = delete_contact_title($contact);
        $html .= '<option value="' . $id . '"' . $selectedAttr . '>' . h($text) . '</option>';
    }
    $html .= '</select>';
    $html .= '<noscript><button class="form-btn" type="submit">Выбрать</button></noscript>';
    $html .= '</form>';

    if ($selectedId === 0 || $selected === null) {
        return $message . $html . '<div class="edit-hint">Выберите запись из списка, чтобы открыть подтверждение удаления.</div>';
    }

    $text = delete_contact_title($selected);
    $html .= '<div class="delete-card">';
    $html .= '<p>Выбрана запись: <strong>' . h($text) . '</strong></p>';
    $html .= '<a class="form-btn delete-btn" href="index.php?action=delete&delete_id=' . $selectedId . '" onclick="return confirm(\'Удалить запись ' . h($text) . '?\');">Удалить запись</a>';
    $html .= '</div>';

    return $message . $html;
}

function delete_contact_title($contact)
{
    $initials = '';
    if ($contact['first_name'] !== '') {
        $initials .= first_utf8_char($contact['first_name']) . '.';
    }
    if ($contact['middle_name'] !== '') {
        $initials .= first_utf8_char($contact['middle_name']) . '.';
    }

    return trim($contact['last_name'] . ' ' . $initials);
}
