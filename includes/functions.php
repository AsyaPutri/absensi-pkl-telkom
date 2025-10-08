<?php
// includes/functions.php

function safe($v) {
  return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
}

function uploadFileUnique($fileKey, $upload_dir) {
  if (!isset($_FILES[$fileKey]) || !$_FILES[$fileKey]['name']) return '';

  $name = basename($_FILES[$fileKey]['name']);
  $name = preg_replace('/[^A-Za-z0-9._-]/', '_', $name);
  $newname = time() . "_" . $name;

  return move_uploaded_file($_FILES[$fileKey]['tmp_name'], $upload_dir . $newname)
    ? $newname
    : '';
}
