<?php
if (!defined('APP_STARTED')) {
    http_response_code(403);
    exit('Доступ запрещен');
}

function render_add()
{
    $data = array(
        'last_name' => '',
        'first_name' => '',
        'middle_name' => '',
        'gender' => 'Мужской',
        'birth_date' => '',
        'phone' => '',
        'address' => '',
        'email' => '',
        'comment' => '',
    );
    $message = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = read_contact_from_post();

        if (contact_is_valid($data)) {
            try {
                $stmt = db()->prepare(
                    'INSERT INTO contacts
                    (last_name, first_name, middle_name, gender, birth_date, phone, address, email, comment)
                    VALUES
                    (:last_name, :first_name, :middle_name, :gender, :birth_date, :phone, :address, :email, :comment)'
                );
                $stmt->execute($data);
                $message = '<p class="success">Запись добавлена</p>';
            } catch (Exception $e) {
                $message = '<p class="error">Ошибка: запись не добавлена</p>';
            }
        } else {
            $message = '<p class="error">Ошибка: запись не добавлена</p>';
        }
    }

    return $message . contact_form('index.php?action=add', $data, 'Добавить запись');
}

function contact_form($action, $data, $buttonText, $confirmText = '')
{
    $gender = isset($data['gender']) ? $data['gender'] : 'Мужской';
    $confirm = $confirmText !== '' ? ' onsubmit="return confirm(\'' . h($confirmText) . '\');"' : '';
    $html = '<form name="form_add" method="post" action="' . h($action) . '"' . $confirm . '>';
    $html .= '<div class="column">';
    $html .= '<div class="add"><label>Фамилия</label><input type="text" name="last_name" placeholder="Фамилия" value="' . h(isset($data['last_name']) ? $data['last_name'] : '') . '" required></div>';
    $html .= '<div class="add"><label>Имя</label><input type="text" name="first_name" placeholder="Имя" value="' . h(isset($data['first_name']) ? $data['first_name'] : '') . '" required></div>';
    $html .= '<div class="add"><label>Отчество</label><input type="text" name="middle_name" placeholder="Отчество" value="' . h(isset($data['middle_name']) ? $data['middle_name'] : '') . '"></div>';
    $html .= '<div class="add"><label>Пол</label><select name="gender" required>';
    foreach (array('Мужской', 'Женский') as $option) {
        $selected = $gender === $option ? ' selected' : '';
        $html .= '<option value="' . h($option) . '"' . $selected . '>' . h($option) . '</option>';
    }
    $html .= '</select></div>';
    $html .= '<div class="add"><label>Дата рождения</label><input type="date" name="birth_date" value="' . h(isset($data['birth_date']) ? $data['birth_date'] : '') . '" required></div>';
    $html .= '<div class="add"><label>Телефон</label><input type="tel" name="phone" placeholder="Телефон" value="' . h(isset($data['phone']) ? $data['phone'] : '') . '"></div>';
    $html .= '<div class="add"><label>Адрес</label><input type="text" name="address" placeholder="Адрес" value="' . h(isset($data['address']) ? $data['address'] : '') . '"></div>';
    $html .= '<div class="add"><label>Email</label><input type="email" name="email" placeholder="Email" value="' . h(isset($data['email']) ? $data['email'] : '') . '"></div>';
    $html .= '<div class="add"><label>Комментарий</label><textarea name="comment" placeholder="Краткий комментарий">' . h(isset($data['comment']) ? $data['comment'] : '') . '</textarea></div>';
    $html .= '<button type="submit" value="' . h($buttonText) . '" name="button" class="form-btn">' . h($buttonText) . '</button>';
    $html .= '</div>';
    $html .= '</form>';

    return $html;
}
