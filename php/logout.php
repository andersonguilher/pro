<?php
session_start();
session_destroy();
header("Location: /pro/index.html");
exit;
