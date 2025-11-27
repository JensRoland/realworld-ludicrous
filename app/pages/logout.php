<?php

use App\Lib\Auth;

Auth::clearTokenCookie();
header('Location: /');
exit;
