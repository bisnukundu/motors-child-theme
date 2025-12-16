<?php

namespace Bisnu\Backend\Controller;

class Car_Filter_By_Tag
{
    public function __construct()
    {
        add_action('elementor/query/car_filter', [$this, 'filter_cars_by_tag']);
    }


    function filter_cars_by_tag($query)
    {
        // Check if 'car_tag' parameter exists in URL
        if (isset($_GET['car_tag']) && ! empty($_GET['car_tag'])) {
            $car_tag = sanitize_text_field($_GET['car_tag']);

            // Set taxonomy query to filter by the tag
            $query->set('post_type', ['product']);
            $query->set('tax_query', [
                [
                    'taxonomy' => 'product_tag',
                    'field' => 'slug',
                    'terms' => $car_tag
                ]
            ]);
        }
    }
}
