<?php

namespace TPerformant\API\HTTP;

interface AuthInterface {
    public function getAccessToken();
    public function getClientToken();
    public function getUid();
}
