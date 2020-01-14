<?php
/**
 *引用格式化树
 *
 * @param array $items 需要格式化的数组
 *
 * @return null|array
 */
function tree($items = [])
{
    if (!$items) {
        return null;
    }
    $tree = [];
    foreach ($items as $item) {
        if (isset($items[$item['parent_id']])) {
            $items[$item['parent_id']]['son'][] = &$items[$item['id']];
        } else {
            $tree[] = &$items[$item['id']];
        }
    }
    return $tree;
}

function getLastSql()
{
    echo '<pre>';
    print_r(db()->getLastSql());
    exit('</pre>');
}
