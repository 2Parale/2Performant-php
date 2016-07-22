<?php

namespace TPerformant\API\Model;

class Banner extends GenericEntity {
    protected $id;
    protected $imgPath;
    protected $dimensions;
    protected $category;
    protected $friendlyType;
    protected $height;
    protected $width;
    protected $uniqueCode;
    protected $url;
    protected $bType;
    protected $conversionRate;
    protected $link;
    protected $markup;
    protected $program;

    /**
     * @inheritdoc
     */
    protected function classMap() {
        return [
            'program' => 'Program'
        ];
    }
}
