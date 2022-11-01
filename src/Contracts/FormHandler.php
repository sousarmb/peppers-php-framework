<?php

namespace Peppers\Contracts;

use Peppers\Response;
use Peppers\Helpers\ResponseSent;

interface FormHandler {

    public function get(): Response;

    public function post(): Response|ResponseSent;
}
