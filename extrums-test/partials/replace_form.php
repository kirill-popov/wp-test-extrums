<form class="replace-form">
    <input type="text" name="replace" placeholder="new keyword...">
    <input type="hidden" name="action" value="replace_form_submit">
    <input type="hidden" name="field" value="%KEY%">
    <br>
    <?php wp_nonce_field('replace_form_submit_action', '_extrums_replace_nonce');?>
    <input type="submit" value="Replace" class="btn btn-secondary mt-1">
</form>