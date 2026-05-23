<?php
if (!defined('APP_STARTED')) {
    http_response_code(403);
    exit('Доступ запрещен');
}

function render_edit()
{
    $contacts = db()->query('SELECT * FROM contacts ORDER BY last_name ASC, first_name ASC, id ASC')->fetchAll(PDO::FETCH_ASSOC);

    if (!$contacts) {
        return '<div>Нет записей для редактирования.</div>';
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

    $message = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $selectedId > 0) {
        $data = read_contact_from_post();

        if (contact_is_valid($data)) {
            try {
                $data['id'] = $selectedId;
                $stmt = db()->prepare(
                    'UPDATE contacts SET
                        last_name = :last_name,
                        first_name = :first_name,
                        middle_name = :middle_name,
                        gender = :gender,
                        birth_date = :birth_date,
                        phone = :phone,
                        address = :address,
                        email = :email,
                        comment = :comment
                    WHERE id = :id'
                );
                $stmt->execute($data);
                $message = '<p class="success">Запись обновлена</p>';

                $stmt = db()->prepare('SELECT * FROM contacts WHERE id = :id');
                $stmt->execute(array('id' => $selectedId));
                $selected = $stmt->fetch(PDO::FETCH_ASSOC);
                $contacts = db()->query('SELECT * FROM contacts ORDER BY last_name ASC, first_name ASC, id ASC')->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                $message = '<p class="error">Ошибка: запись не обновлена</p>';
            }
        } else {
            $message = '<p class="error">Ошибка: запись не обновлена</p>';
        }
    }

    $html = '<form class="edit-choice" method="get" action="index.php">';
    $html .= '<input type="hidden" name="action" value="edit">';
    $html .= '<label for="edit-id">Выберите запись</label>';
    $html .= '<select id="edit-id" name="id" onchange="this.form.submit()">';
    $html .= '<option value="">-- Выбор записи --</option>';
    foreach ($contacts as $contact) {
        $id = (int)$contact['id'];
        $selectedAttr = $id === $selectedId ? ' selected' : '';
        $text = $contact['last_name'] . ' ' . $contact['first_name'];
        $html .= '<option value="' . $id . '"' . $selectedAttr . '>' . h($text) . '</option>';
    }
    $html .= '</select>';
    $html .= '<noscript><button class="form-btn" type="submit">Выбрать</button></noscript>';
    $html .= '</form>';

    if ($selectedId === 0 || $selected === null) {
        return $html . '<div class="edit-hint">Выберите запись из списка, чтобы открыть форму редактирования.</div>';
    }

    return $html . $message . contact_form(
        'index.php?action=edit&id=' . $selectedId,
        $selected,
        'Сохранить изменения',
        'Сохранить изменения в выбранной записи?'
    );
}
