<?php 

get_header(''); 
the_post();
?>

<section class="py-5" style="background-color:#4b555f;">
    <div class="container">
        <div class="row align-items-center">

            <!-- Text Content -->
            <div class="col-md-6 mb-4 mb-md-0 text-white">
                <h2 class="fw-bold mb-3"><?php the_title() ?></h2>
                <p class="text-light">
                    <?php the_content() ?>
                    <!-- We are a passionate team dedicated to delivering high-quality digital solutions.
                    Our focus is on creating user-friendly, responsive, and scalable applications
                    that help businesses grow and succeed in the digital world. -->
                </p>
            </div>

            <!-- Image -->
            <div class="col-md-6 text-center">
                <?php if ( has_post_thumbnail() ) : ?>

                    <?php the_post_thumbnail( array(500, 300), [
                        'class' => 'img-fluid rounded shadow',
                        'alt'   => get_the_title()
                    ] ); ?>

                <?php else : ?>

                    <img 
                        src="https://placehold.co/500x300"
                        alt="About Us Image"
                        class="img-fluid rounded shadow"
                    >

                <?php endif; ?>
            </div>

        </div>
    </div>
</section>

<?php
  get_footer();
?>