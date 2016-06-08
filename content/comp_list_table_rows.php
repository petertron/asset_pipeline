<?php

use asset_pipeline\ap;

if ($compilation_list) {
    foreach ($compilation_list as $file => $subdir) {
        $mtime = ap\getFileMTime(ap\SOURCE_DIR . '/' . $subdir . '/' . $file);
        $column1 = Widget::TableData($file);
        $column1->appendChild(
            Widget::Input("items[$file]", 'on', 'checkbox')
        );

        $table_row = Widget::TableRow(
            array(
                //'<input name="items[0]" type="checkbox" value="on"/>'),
                $column1,
                Widget::TableData($subdir),
                    //$subdir'<a href="' . $subdir . '/">' . $subdir . '</a>'),
                Widget::TableData($mtime ? $mtime : __('<span class="inactive">Not found</span>')),
                Widget::TableData('<span class="inactive">-</span>')
            )
        );
        if (!$mtime) {
            $table_row->setAttribute('class', 'status-error');
        }
        $table_rows[] = $table_row;
    }
} else {
    $table_rows[] = Widget::TableRow(
        array(Widget::TableData(__('None found.'), 'inactive', null, 4))
    );
}

