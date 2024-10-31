<div class="myposts-form" id="myposts-form-wrapper">
    <?php if( is_user_logged_in() ) : ?>
    <form action="<?php echo get_permalink() ?>" method="post" id="myposts-form">
        <input type="hidden" name="myposts_action" value="save_post">
        <input type="hidden" name="image" value="">
        <input type="hidden" name="provider" value="">
        <?php wp_nonce_field( 'post', 'myposts_nonce' ) ?>
        <div>
            <label><?php _e( 'Title', 'myposts' ) ?> <span class="myposts-required">*</span></label>
            <input type="text" name="title" value="<?php esc_attr_e( isset( $_POST['title'] ) ? $_POST['title'] : '' ) ?>" class="form-control" placeholder="<?php esc_attr_e( 'Title', 'myposts' ) ?>" required>
        </div>
        <div>
            <span class="myposts-loading"></span>
            <label><?php _e('Link', 'myposts') ?></label>
            <input type="text" name="url" value="<?php esc_attr_e( isset( $_POST['url'] ) ? $_POST['url'] : '' ) ?>" class="form-control" placeholder="<?php esc_attr_e( 'Link to your post', 'myposts' ) ?>">
        </div>
        <div>
            <textarea name="content" id="" cols="30" rows="10" class="form-control" placeholder="<?php esc_attr_e( 'Your description or comment', 'myposts' ) ?>"><?php echo esc_html_e( isset( $_POST['content'] ) ? $_POST['content'] : '' ) ?></textarea>
        </div>
        <div class="myposts-categories">
            <label><?php _e('Category', 'myposts') ?></label>
            <?php foreach ( get_terms( 'category', array( 'hide_empty' => false ) ) as $category) : ?>
                <div>
                    <label><input type="radio" name="category" value="<?php echo $category->term_id ?>" <?php checked( esc_attr( isset( $_POST['category'] ) ? $_POST['category'] : '' ), $category->term_id ) ?> required /><?php echo $category->name ?></label>
                </div>
            <?php endforeach ?>
            <label for="category" class="myposts-category-error error" style="display:none;">...</label>
        </div>
        <button type="submit" name="button" class="btn btn-primary"><?php _e('Save', 'myposts') ?></button>
    </form>
    <?php else : ?>
        <p>
            <?php _e('Please login to access this page.', 'myposts') ?>
        </p>
        <?php wp_login_form( esc_url( get_permalink() ) ) ?>
    <?php endif ?>
</div>
