<?php
get_header();

while ( have_posts() ) : the_post();

    $agent     = get_post_meta( get_the_ID(), 'agent', true );
    $location  = get_post_meta( get_the_ID(), 'location', true );
    $price     = get_post_meta( get_the_ID(), 'price', true );
    $bedrooms  = get_post_meta( get_the_ID(), 'bedrooms', true );
    $bathrooms = get_post_meta( get_the_ID(), 'bathrooms', true );
    $zip       = get_post_meta( get_the_ID(), 'zip', true );
    $address   = get_post_meta( get_the_ID(), 'address', true );
    $city      = get_post_meta( get_the_ID(), 'city', true );
    $state     = get_post_meta( get_the_ID(), 'state', true );
    $country   = get_post_meta( get_the_ID(), 'country', true );
?>

<div class="property-details" style="max-width:800px;margin:40px auto;font-family:Arial,sans-serif;">
    <h1><?php the_title(); ?></h1>

    <ul style="list-style:none;padding:0;font-size:16px;line-height:1.8;">
        <li><strong>Agent:</strong> <?php echo esc_html( $agent ); ?></li>
        <li><strong>Location:</strong> <?php echo esc_html( $location ); ?></li>
        <li><strong>Price:</strong> $<?php echo number_format( (float)$price, 2 ); ?></li>
        <li><strong>Bedrooms:</strong> <?php echo esc_html( $bedrooms ); ?></li>
        <li><strong>Bathrooms:</strong> <?php echo esc_html( $bathrooms ); ?></li>
        <li><strong>ZIP Code:</strong> <?php echo esc_html( $zip ); ?></li>
        <li><strong>Address:</strong> <?php echo esc_html( $address ); ?></li>
        <li><strong>City:</strong> <?php echo esc_html( $city ); ?></li>
        <li><strong>State:</strong> <?php echo esc_html( $state ); ?></li>
        <li><strong>Country:</strong> <?php echo esc_html( $country ); ?></li>
    </ul>

    <div class="property-content">
        <?php the_content(); ?>
    </div>
</div>

<?php
endwhile;

get_footer();
