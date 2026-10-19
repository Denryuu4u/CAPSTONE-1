<?php
// Connectivity only: do not load sessions, credentials or the database.
header('Cache-Control: no-store, max-age=0');
header('X-App-Connection: ok');
http_response_code(204);
