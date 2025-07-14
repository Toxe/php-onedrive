"use strict";

function show_rename_dialog(item_id, old_name) {
    const dialog = document.getElementById("rename_dialog");
    const name_field = dialog.querySelector("input[name='new_name']");
    const item_id_field = dialog.querySelector("input[name='item_id']");

    name_field.value = old_name;
    item_id_field.value = item_id;

    dialog.showModal();
}

function show_delete_dialog(item_id, item_name, item_type) {
    const dialog = document.getElementById("delete_dialog");
    const item_id_field = dialog.querySelector("input[name='item_id']");
    const p_visible = dialog.querySelector(`p[name='${item_type}']`);
    const p_hidden = dialog.querySelector(`p[name='${item_type == "file" ? "folder" : "file"}']`);
    const name_field = p_visible.querySelector(".item_name");

    item_id_field.value = item_id;
    name_field.innerHTML = item_name;
    p_visible.className = "";
    p_hidden.className = "hidden";

    dialog.showModal();
}

function show_upload_dialog() {
    const dialog = document.getElementById("upload_dialog");
    const form = dialog.querySelector("form");

    form.reset();
    dialog.showModal();
}

function show_new_folder_dialog() {
    const dialog = document.getElementById("new_folder_dialog");
    const form = dialog.querySelector("form");

    form.reset();
    dialog.showModal();
}
