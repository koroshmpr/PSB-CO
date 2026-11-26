<?php
function searchfilter($query)
{
    // An explicit ?post_type= (e.g. the blog search form sends post_type=post) is respected.
    if ($query->is_search && $query->is_main_query() && !is_admin() && empty($_GET['post_type'])) {
        $query->set('post_type', array('product'));
    }

    return $query;
}
add_filter('pre_get_posts', 'searchfilter');
