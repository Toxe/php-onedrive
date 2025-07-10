"use strict";

function show_rename_dialog(item_id, old_name) {
    const dialog = document.getElementById("rename_dialog");
    const name_field = dialog.querySelector("input[name='new_name']");
    const item_id_field = dialog.querySelector("input[name='item_id']");

    name_field.value = old_name;
    item_id_field.value = item_id;

    dialog.showModal();
}
