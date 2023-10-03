<div>
    <h1><?php echo $args['title'];?></h1>
    <form id="extrums_search_form"
        data-action="search_form_submit"
    >
        <input type="text" name="search_string"
            id="extrums_search_string"
            placeholder="keyword..."
            required
            class=""
        >
        <input type="hidden" name="action" value="search_form_submit">
        <?php wp_nonce_field('search_form_submit_action', '_extrums_search_nonce');?>
        <input type="submit" value="Search"
            class="btn btn-secondary"
        >
    </form>
    <table class="table table-bordered mt-3" id="extrums_results"></table>
</div>