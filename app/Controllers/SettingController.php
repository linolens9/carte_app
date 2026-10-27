<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Logger;
use App\Core\Session;

class SettingController extends Controller
{
    public function index(): void
    {
        (new ParametreController($this->request, $this->response))->index();
    }

    public function update(): void
    {
        (new ParametreController($this->request, $this->response))->update();
    }
}
