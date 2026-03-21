<?php
require 'config.php';
session_start();
session_destroy();
respond(['success'=>true]);
