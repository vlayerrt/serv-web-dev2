<?php
if (!defined('APP_STARTED')) {
    http_response_code(403);
    exit('Доступ запрещен');
}

function main_menu()
{
    $action = current_action();
    $sort = current_sort();
    $items = array(
        'view' => 'Просмотр',
        'add' => 'Добавление записи',
        'edit' => 'Редактирование записи',
        'delete' => 'Удаление записи',
    );

    $html = '<header>';
    foreach ($items as $key => $title) {
        $active = $action === $key ? ' class="select"' : '';
        $html .= '<a' . $active . ' href="index.php?action=' . h($key) . '">' . h($title) . '</a>';
    }
    $html .= '<a href="index.php?action=bye">Выход</a>';
    $html .= '</header>';

    if ($action === 'view') {
        $sortItems = array(
            'added' => 'По порядку добавления',
            'last_name' => 'По фамилии',
            'birth_date' => 'По дате рождения',
        );

        $html .= '<nav class="submenu">';
        foreach ($sortItems as $key => $title) {
            $active = $sort === $key ? ' class="select"' : '';
            $html .= '<a' . $active . ' href="index.php?action=view&sort=' . h($key) . '">' . h($title) . '</a>';
        }
        $html .= '</nav>';
    }

    return $html;
}
