<div class="col-xs-offset-4 col-xs-8 col-sm-offset-4 col-sm-7 col-md-offset-6 col-md-6" style="margin-top: 50px"> <!-- login-box -->
    <a href="<?= SITE ?>Designers class">
        <blockquote class="pull-right" style="background-color: rgba(0, 0, 0, 0.7); border-radius: 10px; ">
            <a href="<?=SITE?>CarbonPHP" class="text-gray">
                <h1 class="text-primary" style="margin-top: 0"><b>Restaurants & Schedules</b></h1>
                Do Work Son.</a>
            <a href=""><small><cite title="Source Title">Gold Team Forever</cite></small></a>
        </blockquote>
    </a>
</div>
<script>
    Carbon(()=> {
        $('.wrapper').css('background-color', 'transparent');
        let remove = () => $('.wrapper').css('background-color', 'rgba(0, 0, 0, 0.7)');
        $(document).off("pjax:beforeSend", remove).on("pjax:beforeSend", remove);
    });
</script>

<div class="clearfix"></div>