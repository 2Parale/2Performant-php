<?php

namespace TPerformant\API\Model;

class Affrequest extends GenericEntity {
    protected $id;
    protected $status;
    protected $deleteAt;
    protected $suspendAt;
    protected $joinDate;
    protected $subscription;
}
