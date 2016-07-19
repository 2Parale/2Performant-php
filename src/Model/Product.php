<?php

namespace TPerformant\API\Model;

class Product extends GenericEntity {
    protected $id;
    protected $title;
    protected $category;
    protected $subcategory;
    protected $brand;
    protected $uniqueCode;
    protected $price;
    protected $caption;
    protected $structuredImageUrls;
    protected $url;
    protected $description;
}
