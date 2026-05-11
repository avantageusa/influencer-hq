<?php
/**
 * Template Name: Portal Challenge Calendar
 * Description: A calendar view for private challenge scheduling.
 *
 * @package Avantage_Baccarat
 */
get_header();
get_template_part( 'template-parts/portal-styles' );
?>
<style>
    .challenge-calendar-page {
        max-width: 760px;
        margin: 210px auto;
        padding: 30px 20px 100px;
    }

    .challenge-calendar-page .page-title {
        font-family: 'Cinzel', serif;
        font-size: 1.8rem;
        font-weight: 900;
        letter-spacing: .12em;
        text-transform: uppercase;
        color: #fff;
        margin-bottom: 24px;
    }
</style>

<main id="primary" class="site-main">

    <?php get_template_part( 'template-parts/portal-header' ); ?>

    <div class="challenge-calendar-page">

        <h1 class="page-title">Private Challenge Schedule</h1>

        <?php
        ihq_calendar([
            date('Y-n') => [2, 3, 4, 15, 16, 24, 25, 26],
        ]);
        ?>

    </div>

    <?php get_template_part( 'template-parts/portal-footer' ); ?>

</main>

<?php
get_template_part( 'template-parts/portal-scripts' );
get_footer();
?>
