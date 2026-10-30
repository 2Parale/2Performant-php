<?php

namespace TPerformant\API\Model;

class ProductFeed extends GenericEntity {
    protected $id;
    protected $updatedAt;
    protected $help;
    protected $productsCount;
    protected $name;
    protected $program;

    /**
     * @inheritdoc
     */
    protected function classMap() {
        return array_merge(parent::classMap(), [
            'program' => 'Program'
        ]);
    }
}
